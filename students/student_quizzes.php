<?php
ob_start();
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$student_id = $_SESSION['user_id'];

if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_quiz') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    $quiz_id = intval($_GET['quiz_id'] ?? 0);
    if ($quiz_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid quiz.']);
        exit();
    }
    $q = $conn->prepare("
        SELECT quiz_id, questions, duration, passing_score, question_count
        FROM tblquizzes
        WHERE quiz_id = ? AND status = 'published'
        LIMIT 1
    ");
    $q->bind_param("i", $quiz_id);
    $q->execute();
    $qrow = $q->get_result()->fetch_assoc();
    $q->close();
    if (!$qrow) {
        echo json_encode(['success' => false, 'message' => 'Quiz not found or not available.']);
        exit();
    }
    $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM tblquiz_results WHERE quizzes_id = ? AND student_id = ?");
    $chk->bind_param("ii", $quiz_id, $student_id);
    $chk->execute();
    $chk_row = $chk->get_result()->fetch_assoc();
    $chk->close();

    $expChk = $conn->prepare("SELECT due_date FROM tblquizzes WHERE quiz_id = ? LIMIT 1");
    $expChk->bind_param("i", $quiz_id);
    $expChk->execute();
    $expRow = $expChk->get_result()->fetch_assoc();
    $expChk->close();
    $is_expired_review = !empty($expRow['due_date']) && strtotime($expRow['due_date']) < time();

    if (!empty($expRow['due_date']) && strtotime($expRow['due_date']) < time()) {
        echo json_encode(['success' => false, 'message' => 'This quiz has already expired and can no longer be taken.']);
        exit();
    }
    $decoded = json_decode($qrow['questions'] ?? '[]', true);
    if (!is_array($decoded) || empty($decoded)) {
        echo json_encode(['success' => false, 'message' => 'No questions found.']);
        exit();
    }
    $out = [];
    foreach ($decoded as $item) {
        $type      = $item['type'] ?? 'multiple_choice';
        $formatted = [
            'question' => htmlspecialchars($item['text'] ?? '', ENT_QUOTES, 'UTF-8'),
            'type'     => $type,
        ];
        if ($type === 'multiple_choice') {
            $opts    = $item['options'] ?? [];
            $choices = [];
            foreach (['a','b','c','d'] as $key) {
                if (isset($opts[$key]) && $opts[$key] !== '') {
                    $choices[] = $opts[$key]; 
                }
            }
            $formatted['choices']      = $choices;
            $formatted['correct_key']  = strtolower(trim($item['correct'] ?? '')); 
        } else {
            $formatted['choices'] = [];
        }
        $out[] = $formatted;
    }
    echo json_encode(['success' => true, 'questions' => $out, 'duration' => (int)$qrow['duration']]);
    exit();
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_quiz_review') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    $quiz_id = intval($_GET['quiz_id'] ?? 0);
    if ($quiz_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid quiz.']); exit(); }

    $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM tblquiz_results WHERE quizzes_id = ? AND student_id = ?");
    $chk->bind_param("ii", $quiz_id, $student_id);
    $chk->execute();
    $chk_row = $chk->get_result()->fetch_assoc();
    $chk->close();

    $expChk = $conn->prepare("SELECT due_date FROM tblquizzes WHERE quiz_id = ? LIMIT 1");
    $expChk->bind_param("i", $quiz_id);
    $expChk->execute();
    $expRow = $expChk->get_result()->fetch_assoc();
    $expChk->close();
    $is_expired_review = !empty($expRow['due_date']) && strtotime($expRow['due_date']) < time();

    if ($chk_row['cnt'] < 1 && !$is_expired_review) {
        echo json_encode(['success'=>false,'message'=>'Review is only available after attempting the quiz.']);
        exit();
    }

    $q = $conn->prepare("SELECT questions FROM tblquizzes WHERE quiz_id = ? AND status = 'published' LIMIT 1");
    $q->bind_param("i", $quiz_id);
    $q->execute();
    $qrow = $q->get_result()->fetch_assoc();
    $q->close();
    if (!$qrow) { echo json_encode(['success'=>false,'message'=>'Quiz not found.']); exit(); }

    $decoded = json_decode($qrow['questions'] ?? '[]', true);
    $review  = [];
    foreach ($decoded as $item) {
        $type        = $item['type'] ?? 'multiple_choice';
        $correct_key = strtolower(trim($item['correct'] ?? ''));
        $opts        = $item['options'] ?? [];
        $correct_text = $type === 'multiple_choice' ? ($opts[$correct_key] ?? $correct_key) : $correct_key;
        $choices = [];
        if ($type === 'multiple_choice') {
            foreach (['a','b','c','d'] as $k) {
                if (isset($opts[$k]) && $opts[$k] !== '') $choices[] = $opts[$k];
            }
        }
        $review[] = [
            'question'     => htmlspecialchars($item['text'] ?? '', ENT_QUOTES, 'UTF-8'),
            'type'         => $type,
            'choices'      => $choices,
            'correct_key'  => $correct_key,
            'correct_text' => htmlspecialchars($correct_text, ENT_QUOTES, 'UTF-8'),
        ];
    }

    $s = $conn->prepare("SELECT MAX(score) as best, COUNT(*) as cnt FROM tblquiz_results WHERE quizzes_id = ? AND student_id = ?");
    $s->bind_param("ii", $quiz_id, $student_id);
    $s->execute();
    $srow = $s->get_result()->fetch_assoc();
    $s->close();

    echo json_encode(['success'=>true, 'questions'=>$review, 'best_score'=>(int)$srow['best'], 'attempts'=>(int)$srow['cnt']]);
    exit();
}
if (isset($_GET['ajax']) && $_GET['ajax'] === 'submit_quiz') {
    while (ob_get_level()) ob_end_clean();
    ob_start(); // buffer anything that slips through (PHP notices/warnings)
    header('Content-Type: application/json');
    $raw       = file_get_contents('php://input');
    $post      = json_decode($raw, true);
    $quiz_id   = intval($post['quiz_id']   ?? 0);
    $answers   = $post['answers']          ?? [];
    $time_used = intval($post['time_used'] ?? 0);
    if ($quiz_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid quiz.']);
        exit();
    }
    $q = $conn->prepare("
        SELECT quiz_id, questions, passing_score
        FROM tblquizzes
        WHERE quiz_id = ? AND status = 'published'
        LIMIT 1
    ");
    $q->bind_param("i", $quiz_id);
    $q->execute();
    $qrow = $q->get_result()->fetch_assoc();
    $q->close();
    if (!$qrow) {
        echo json_encode(['success' => false, 'message' => 'Quiz not found.']);
        exit();
    }
    $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM tblquiz_results WHERE quizzes_id = ? AND student_id = ?");
    $chk->bind_param("ii", $quiz_id, $student_id);
    $chk->execute();
    $chk_row = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($chk_row['cnt'] >= 3) {
        echo json_encode(['success' => false, 'message' => 'You have reached the maximum number of attempts (3).']);
        exit();
    }
    $decoded = json_decode($qrow['questions'] ?? '[]', true);
    $total   = is_array($decoded) ? count($decoded) : 0;
    $correct = 0;
    foreach ($decoded as $idx => $item) {
        $type           = $item['type'] ?? 'multiple_choice';
        $correct_key    = strtolower(trim($item['correct'] ?? '')); // e.g. 'a', 'b', 'c', 'd'
        $student_answer = strtolower(trim($answers[(string)$idx] ?? ''));

        if ($type === 'multiple_choice') {
            $opts         = $item['options'] ?? [];
            $correct_text = strtolower(trim($opts[$correct_key] ?? $correct_key));
            if ($student_answer === $correct_text || $student_answer === $correct_key) {
                $correct++;
            }
        } else {
            if ($student_answer !== '' && $student_answer === strtolower(trim($correct_key))) {
                $correct++;
            }
        }
    }
    $score   = $correct;
    $passing = (int)($qrow['passing_score'] ?? 75);
    $passed  = $score >= $passing;
    $answers_json = json_encode($answers);
    $ins = $conn->prepare("INSERT INTO tblquiz_results (quizzes_id, student_id, score, answers, time_used, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if (!$ins) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $ins->bind_param("iiisi", $quiz_id, $student_id, $score, $answers_json, $time_used);
    if ($ins->execute()) {
        $ins->close();
        echo json_encode(['success' => true, 'score' => $score, 'correct' => $correct, 'total' => $total, 'passed' => $passed]);
    } else {
        $err = $ins->error;
        $ins->close();
        echo json_encode(['success' => false, 'message' => 'Failed to save: ' . $err]);
    }
    exit();
}

$stmt = $conn->prepare("
    SELECT s.id, s.firstname, s.middlename, s.lastname, s.profile_pic,
           s.section_id, sec.section, g.grade_level
    FROM tblstudents s
    LEFT JOIN tblsections sec ON s.section_id = sec.section_id
    LEFT JOIN tblgrade_levels g ON sec.grade_level_id = g.grade_id
    WHERE s.id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$student_name    = trim($student['firstname'] . ' ' . ($student['middlename'] ? $student['middlename'] . ' ' : '') . $student['lastname']);
$student_grade   = htmlspecialchars($student['grade_level'] ?? '');
$student_section = htmlspecialchars($student['section'] ?? '');

/* Avatar */
$fn       = $student['firstname'] ?? '';
$ln       = $student['lastname']  ?? '';
$initials = strtoupper(substr($fn,0,1).substr($ln,0,1));
if (strlen(trim($initials)) < 2) $initials = 'S';
$pic = $student['profile_pic'] ?? '';
$avatar_html = $initials;
if (!empty($pic)) {
    foreach (['../uploads/profile_pic/students/'.$pic,'../uploads/profile_pic/'.$pic,'../'.$pic] as $p) {
        if (file_exists($p)) {
            $avatar_html = '<img src="'.htmlspecialchars($p).'" style="width:100%;height:100%;object-fit:cover;border-radius:inherit" alt="'.htmlspecialchars($initials).'">';
            break;
        }
    }
}

// REPLACE WITH:
$stmt = $conn->prepare("SELECT DISTINCT teacher_id FROM tbl_teachers_students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$teacherRes = $stmt->get_result();
$teacher_ids = [];
while ($row = $teacherRes->fetch_assoc()) {
    $teacher_ids[] = (int)$row['teacher_id'];
}
$stmt->close();

$coursesMeta = [];
// REPLACE WITH:
if (!empty($teacher_ids)) {
    $placeholders = implode(',', array_fill(0, count($teacher_ids), '?'));
    $types = str_repeat('i', count($teacher_ids));
    $stmt = $conn->prepare("
        SELECT ts.teacher_subject_id, ts.subject_id, sub.subject,
               str.strand, str.track
        FROM tbl_teacher_subjects ts
        LEFT JOIN tblsubjects sub ON sub.subject_id = ts.subject_id
        LEFT JOIN tblstrands str ON str.strand_id = sub.strand_id
        WHERE ts.teacher_id IN ($placeholders)
        ORDER BY ts.created_at DESC
    ");
    $stmt->bind_param($types, ...$teacher_ids);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $subj = $row['subject'] ?? 'Subject';
        $s    = strtolower($subj);
        if      (strpos($s,'bio')!==false)                              $icon = '🧬';
        elseif  (strpos($s,'math')!==false||strpos($s,'algebra')!==false||strpos($s,'calc')!==false) $icon = '📐';
        elseif  (strpos($s,'history')!==false)                         $icon = '🌍';
        elseif  (strpos($s,'eng')!==false||strpos($s,'lit')!==false)   $icon = '📖';
        elseif  (strpos($s,'phys')!==false)                            $icon = '⚛️';
        elseif  (strpos($s,'chem')!==false)                            $icon = '⚗️';
        elseif  (strpos($s,'comp')!==false||strpos($s,'prog')!==false) $icon = '💻';
        else                                                             $icon = '📚';

        $banners = ['banner-science','banner-math','banner-history','banner-english','banner-art'];
        $banner  = $banners[abs(crc32($subj)) % count($banners)];

        $coursesMeta[(int)$row['teacher_subject_id']] = [
            'title'       => htmlspecialchars($subj),
            'icon'        => $icon,
            'category'    => htmlspecialchars($row['strand'] ?? $row['track'] ?? 'Subject'),
            'bannerClass' => $banner,
            'strand'      => htmlspecialchars($row['strand'] ?? ''),
            'track'       => htmlspecialchars($row['track']  ?? ''),
        ];
    }
    $stmt->close();
}

$allQuizzes = [];
// REPLACE WITH:
if (!empty($teacher_ids) && !empty($coursesMeta)) {
    $subject_ids = implode(',', array_keys($coursesMeta));

    $sql = "
        SELECT
            q.quiz_id,
            q.lesson_id,
            q.description,
            q.duration,
            q.question_count,
            q.questions,
            q.passing_score,
            q.due_date,
            q.status,
            q.created_at,
            l.title         AS lesson_title,
            l.teacher_subject_id
        FROM tblquizzes q
        JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id
        WHERE q.status = 'published'
          AND l.teacher_subject_id IN ($subject_ids)
        ORDER BY q.created_at DESC
    ";

    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $tid = (int)$row['teacher_subject_id'];

        $decoded = json_decode($row['questions'] ?? '[]', true);
        $q_count = is_array($decoded) ? count($decoded) : (int)($row['question_count'] ?? 0);

        $due_date_raw  = $row['due_date'] ?? '';
        $due_date_disp = '';
        $due_sort      = '';
        if (!empty($due_date_raw)) {
            $due_date_disp = date('M d', strtotime($due_date_raw));
            $due_sort      = date('Y-m-d', strtotime($due_date_raw));
        }
        $quiz_status = 'upcoming';

        $ans_stmt = $conn->prepare("
            SELECT score FROM tblquiz_results
            WHERE quizzes_id = ? AND student_id = ?
            ORDER BY score DESC
        ");
        $ans_stmt->bind_param("ii", $row['quiz_id'], $student_id);
        $ans_stmt->execute();
        $ans_result   = $ans_stmt->get_result();
        $attempt_count = $ans_result->num_rows;
        $best_row      = $ans_result->fetch_assoc();
        $ans_stmt->close();

        $student_score = null;
        $max_attempts  = 3;

        $is_expired = !empty($due_date_raw) && strtotime($due_date_raw) < time();

        if ($attempt_count > 0) {
            $student_score = (int)$best_row['score'];
            if ($attempt_count >= $max_attempts || $is_expired) {
                $quiz_status = 'completed';
            } else {
                $quiz_status = 'upcoming';  
            }
        } elseif ($is_expired) {
            $quiz_status = 'locked'; 
        }

        $allQuizzes[] = [
            'id'           => (int)$row['quiz_id'],
            'courseId'     => $tid,
            'name'         => htmlspecialchars($row['lesson_title'] ?? 'Quiz'),
            'description'  => htmlspecialchars($row['description'] ?? ''),
            'type'         => 'quiz',
            'status'       => $quiz_status,
            'score'        => $student_score,
            'passingScore' => (int)($row['passing_score'] ?? 75),
            'timeLimit'    => (int)($row['duration'] ?? 30),
            'questions'    => $q_count,
            'dueDate'      => $due_date_disp,
            'dueSort'      => $due_sort,
            'attempts'     => $attempt_count,
            'maxAttempts'  => $max_attempts,
        ];
    }
}

$total_subjects  = count($coursesMeta);
$coursesMetaJson = json_encode($coursesMeta);
$allQuizzesJson  = json_encode($allQuizzes);

$lesson_count = 0;
if (!empty($teacher_ids)) {
    $lesson_count = (int)$conn->query("
        SELECT COUNT(DISTINCT l.lesson_id)
        FROM tbl_teacher_lessons l
        JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = l.teacher_subject_id
        WHERE ts.teacher_id IN (".implode(',', $teacher_ids).") AND l.status = 'published'
    ")->fetch_row()[0];
}

// ── Notifications ──────────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT notification_id, title, message, type, status, created_at
    FROM tblnotifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 30
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unread_count = count(array_filter($notifications, fn($n) => $n['status'] === 'unread'));

function getNotifUrl(string $type): string {
    return match($type) {
        'new_lesson'             => 'student_lessons.php',
        'lesson_expiring',
        'lesson_expired'         => 'student_lessons.php',
        'new_quiz',
        'quiz_due_soon',
        'quiz_overdue'           => 'student_quizzes.php',
        'quiz_passed',
        'quiz_failed'            => 'student_grades.php',
        'new_announcement'       => 'index.php#announcements',
        'new_subject_assigned'   => 'student_courses.php',
        default                  => '#',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ExceLearn LMS — Student Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/student/student_quizzes.css">
<style>
  .quiz-item.locked-quiz {
  border-color: rgba(239, 68, 68, 0.25);
  background: rgba(239, 68, 68, 0.03);
  overflow: hidden;
}
html, body {
  overflow-x: hidden;
  max-width: 100%;
}

.layout {
  overflow-x: hidden;
  max-width: 100%;
}

.main {
  overflow-x: hidden;
  min-width: 0;
}

.content {
  overflow-x: hidden;
  min-width: 0;
}
.quiz-name {
  white-space: normal;
  word-break: break-word;
  overflow-wrap: break-word;
  min-width: 0;
}

.quiz-title-row {
  flex-wrap: wrap;
  align-items: flex-start;
  gap: 4px;
  min-width: 0;
}

.quiz-info {
  min-width: 0;
  flex: 1;
}
</style>
<style>
@media (max-width: 768px) {
  /* Summary cards */
  .summary-row {
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 8px !important;
  }
  .summary-card {
    padding: 10px 12px !important;
    gap: 8px !important;
  }
  .summary-icon {
    width: 32px !important;
    height: 32px !important;
    font-size: 0.9rem !important;
    flex-shrink: 0;
  }
  .summary-value {
    font-size: 1.1rem !important;
  }
  .summary-label {
    font-size: 0.65rem !important;
  }

  /* Filter bar */
  .filter-bar {
    flex-direction: column !important;
    gap: 8px !important;
  }
  .search-box {
    width: 100% !important;
  }
  .filter-select {
    width: 100% !important;
    font-size: 0.8rem !important;
    padding: 7px 10px !important;
  }

  /* Status tabs */
  .status-tabs {
    gap: 6px !important;
    flex-wrap: wrap !important;
  }
  .status-tab {
    font-size: 0.72rem !important;
    padding: 5px 10px !important;
  }

  /* Quiz items */
  .quiz-item {
    flex-direction: column !important;
    align-items: flex-start !important;
    padding: 10px 12px !important;
    gap: 8px !important;
  }
  .quiz-right {
    width: 100% !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    gap: 8px !important;
  }
  .quiz-action-btn {
    width: 100% !important;
    font-size: 0.78rem !important;
    padding: 7px 10px !important;
  }
  .quiz-name {
    font-size: 0.88rem !important;
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    min-width: 0 !important;
  }
  .quiz-meta-row {
    flex-wrap: wrap !important;
    gap: 4px 8px !important;
    font-size: 0.72rem !important;
  }
  .quiz-score-box,
  .quiz-due-box {
    font-size: 0.75rem !important;
  }
  .quiz-score-value {
    font-size: 1rem !important;
  }
}

@media (max-width: 400px) {
  .summary-row {
    grid-template-columns: 1fr 1fr !important;
  }
  .summary-value {
    font-size: 1rem !important;
  }
}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo" style="padding:20px 16px 16px;display:flex;align-items:center;gap:10px;flex-wrap:nowrap;overflow:hidden;">
    <img src="../assets/img/Excelsior_Seal_Logo.png" alt="ExceLearn Logo"
         style="height:38px;width:auto;object-fit:contain;flex-shrink:0;">
    <div style="min-width:0">
      <div style="font-weight:800;font-size:1.15rem;letter-spacing:-0.05em;line-height:1;white-space:nowrap;">
        Exce<span style="color:#22c55e;">Learn</span>
      </div>
      <div style="font-size:0.7rem;color:#9ca3af;white-space:nowrap;margin-top:2px;">
        Learn Better. Anytime, Anywhere.
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Learning</div>
    <a class="nav-item" href="index.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <a class="nav-item" href="student_courses.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      My Subjects
      <span class="nav-badge"><?php echo $total_subjects; ?></span>
    </a>
    <a class="nav-item" href="student_lessons.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Lessons
      <span class="nav-badge"><?= $lesson_count ?></span>
    </a>
    <a class="nav-item active" href="student_quizzes.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      My Quizzes
      <span class="nav-badge"><?php echo count($allQuizzes); ?></span>
    </a>
    <a class="nav-item" href="student_virtual_class.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
      Virtual Class
    </a>

    <div class="nav-section-label" style="margin-top:16px">Progress</div>
    <a class="nav-item" href="student_grades.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Grades
    </a>
    <div class="nav-section-label" style="margin-top:16px">Account</div>
    <a class="nav-item" href="student_profile.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      My Profile
    </a>
    <a class="nav-item" href="settings.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0A1.65 1.65 0 0 0 9 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      Settings
    </a>
    <a class="nav-item" href="about.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      About
    </a>
    <div style="margin-top:8px;border-top:1px solid rgba(255,255,255,0.08);padding-top:8px"></div>
    <a class="nav-item" href="#" onclick="confirmLogout(event)" style="color:var(--red)">
    <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
    </svg>
    Logout
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--cyan),var(--accent))"><?php echo $avatar_html; ?></div>
      <div class="user-info">
        <p><?php echo htmlspecialchars($student_name); ?></p>
        <span><?php echo $student_grade; ?> — <?php echo $student_section; ?></span>
      </div>
    </div>
  </div>
</aside>

<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<main class="main">
  <div class="topbar">
    <button class="menu-btn" onclick="toggleSidebar()">
      <div class="hamburger"><span></span><span></span><span></span></div>
    </button>
    <img src="../assets/img/Excelsior_Logo_long.png" alt="Logo" style="height:38px;width:auto;">
    <div class="topbar-title" style="flex:1"></div>
    <div class="topbar-actions">
      <button class="btn-icon" onclick="openNotifModal()" aria-label="Notifications" style="position:relative;background:rgba(255,255,255,0.08);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.12);font-size:1.0rem;cursor:pointer;padding:6px 10px;border-radius:10px;color:var(--text,#f1f5f9);transition:background .2s;">
      🔔
      <?php if ($unread_count > 0): ?>
      <span id="notif-badge" style="position:absolute;top:4px;right:4px;background:var(--red,#ef4444);color:#fff;font-size:9px;font-weight:700;border-radius:999px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 3px;line-height:1;"><?= $unread_count ?></span>
      <?php else: ?>
      <span id="notif-badge" style="display:none"></span>
      <?php endif; ?>
    </button>
    </div>
  </div>

  <div class="content">
    <div style="margin-bottom:4px">
      <div class="section-title">My Quizzes</div>
      <div class="section-sub">View upcoming quizzes, review past results, and track your quiz performance.</div>
    </div>

    <div class="summary-row" id="summaryRow"></div>

    <div class="filter-bar">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInput" placeholder="Search quizzes..." oninput="filterQuizzes()">
      </div>
      <select class="filter-select" id="courseFilter" onchange="filterQuizzes()">
        <option value="all">All Subjects</option>
      </select>
      <select class="filter-select" id="sortSelect" onchange="filterQuizzes()">
        <option value="default">Sort: Default</option>
        <option value="course">Group by Subject</option>
        <option value="due-asc">Due Date: Soonest</option>
        <option value="due-desc">Due Date: Latest</option>
        <option value="name-asc">Name: A → Z</option>
        <option value="score-desc">Score: High → Low</option>
      </select>
    </div>

    <div class="status-tabs" id="statusTabs"></div>

    <div id="quizzesContainer"></div>
  </div>
</main>
</div>

<div class="modal-backdrop" id="modalBackdrop" onclick="closeModalOutside(event)">
  <div class="modal" id="quizModal">
    <div class="modal-header-banner" id="modalBanner">
      <span class="modal-banner-icon" id="modalBannerIcon"></span>
      <div class="banner-overlay"></div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body" id="modalBody"></div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<div class="modal-backdrop" id="takeQuizBackdrop">
  <div class="modal tq-modal" id="takeQuizModal">
    <!--<button class="modal-close" onclick="confirmAbandonQuiz()" style="position:absolute;top:14px;right:16px;z-index:10">✕</button>-->
    <div class="modal-body" id="takeQuizContent" style="padding:0;max-height:85vh;overflow-y:auto"></div>
  </div>
</div>

<!-- NOTIFICATION MODAL -->
<div id="notifModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--bg2,#1e293b);border-radius:16px;width:90%;max-width:460px;max-height:80vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.5);">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.08);flex-shrink:0;">
      <div style="font-size:16px;font-weight:700;color:var(--text,#f1f5f9);">🔔 Notifications <br>
        <span id="notif-unread-count" style="font-size:11px;font-weight:500;color:var(--text3,#64748b);margin-left:6px;"><?= $unread_count > 0 ? "($unread_count unread)" : '' ?></span>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <?php if ($unread_count > 0): ?>
        <button onclick="markAllRead()" style="font-size:11px;padding:4px 10px;background:transparent;border:1.5px solid rgba(255,255,255,0.15);color:var(--text,#f1f5f9);border-radius:6px;cursor:pointer;">✓ Mark all read</button>
        <?php endif; ?>
        <button onclick="closeNotifModal()" style="background:none;border:none;color:var(--text3,#64748b);font-size:18px;cursor:pointer;padding:4px;line-height:1;">✕</button>
      </div>
    </div>
    <div style="overflow-y:auto;flex:1;padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
      <?php if (empty($notifications)): ?>
        <div style="text-align:center;padding:40px 16px;color:var(--text3,#64748b);">
          <div style="font-size:36px;margin-bottom:8px;">🔕</div>
          <div style="font-size:13px;">No notifications yet.</div>
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $notif):
          $n_ts   = strtotime($notif['created_at']);
          $n_diff = time() - $n_ts;
          if ($n_diff < 60)         $n_time = 'Just now';
          elseif ($n_diff < 3600)   $n_time = floor($n_diff/60).'m ago';
          elseif ($n_diff < 86400)  $n_time = floor($n_diff/3600).'h ago';
          elseif ($n_diff < 604800) $n_time = floor($n_diff/86400).'d ago';
          else                      $n_time = date('M j, Y', $n_ts);
          $is_unread = $notif['status'] === 'unread';
          $n_icon = match($notif['type']) {
            'new_lesson'             => '📖',
            'new_quiz'               => '📝',
            'quiz_passed'            => '🎉',
            'quiz_failed'            => '😞',
            'quiz_due_soon'          => '⏰',
            'quiz_overdue'           => '🚨',
            'new_subject_assigned'   => '📚',
            'new_announcement'       => '📢',
            'lesson_expiring'        => '⌛',
            'lesson_expired'         => '🔒',
            default                  => '🔔',
          };
        ?>
        <div class="notif-item" data-id="<?= $notif['notification_id'] ?>"
             data-url="<?= htmlspecialchars(getNotifUrl($notif['type'])) ?>"
             onclick="markOneRead(<?= $notif['notification_id'] ?>, this)"
             style="display:flex;gap:12px;align-items:flex-start;padding:10px 12px;border-radius:10px;cursor:pointer;transition:background .15s;
                    background:<?= $is_unread ? 'rgba(99,102,241,0.08)' : 'rgba(255,255,255,.03)' ?>;
                    border:1px solid <?= $is_unread ? 'rgba(99,102,241,0.2)' : 'rgba(255,255,255,.07)' ?>;">
          <div style="font-size:20px;flex-shrink:0;margin-top:1px;"><?= $n_icon ?></div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:<?= $is_unread ? '700' : '500' ?>;color:var(--text,#f1f5f9);margin-bottom:2px;line-height:1.3;">
              <?= htmlspecialchars($notif['title']) ?>
              <?php if ($is_unread): ?>
              <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#6366f1;margin-left:5px;vertical-align:middle;"></span>
              <?php endif; ?>
            </div>
            <div style="font-size:12px;color:var(--text3,#64748b);line-height:1.45;margin-bottom:4px;">
              <?= htmlspecialchars($notif['message']) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:2px;">
              <div style="font-size:10px;color:var(--text3,#64748b);opacity:.7;"><?= $n_time ?></div>
              <button onclick="deleteNotif(<?= $notif['notification_id'] ?>, this, event)"
                      style="font-size:10px;padding:2px 8px;border-radius:6px;border:none;background:rgba(239,68,68,0.12);color:#ef4444;cursor:pointer;line-height:1.5;">🗑 Delete</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<div class="modal-backdrop" id="logoutModal" onclick="cancelLogout(event)">
  <div class="modal" style="max-width:380px;padding:0;overflow:hidden">
    <div style="padding:28px 28px 20px;text-align:center">
      <div style="width:56px;height:56px;border-radius:50%;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px">🚪</div>
      <div style="font-size:1.15rem;font-weight:700;margin-bottom:8px">Log out?</div>
      <div style="font-size:0.875rem;color:var(--text2)">Are you sure you want to log out of your ExceLearn account?</div>
    </div>
    <div style="display:flex;gap:10px;padding:0 28px 24px">
      <button class="btn btn-ghost" style="flex:1" onclick="closeLogoutModal()">Cancel</button>
      <button class="btn" style="flex:1;background:var(--red);color:#fff;border:none" onclick="doLogout()">Yes, Log out</button>
    </div>
  </div>
</div>
<script>

  function confirmLogout(e) {
      e.preventDefault();
      document.getElementById('logoutModal').classList.add('open');
      document.body.style.overflow = 'hidden';
  }
  function closeLogoutModal() {
      document.getElementById('logoutModal').classList.remove('open');
      document.body.style.overflow = '';
  }
  function cancelLogout(e) {
      if (e.target === document.getElementById('logoutModal')) closeLogoutModal();
  }
  function doLogout() {
      window.location.href = '../logout.php';
  }
var coursesMeta = <?php echo $coursesMetaJson; ?>;

function isTimerExpiredForQuiz(id){
  // Only trust the expired flag if the student actually has attempts recorded
  var q = allQuizzes.find(function(x){ return x.id === id; });
  if(q && q.attempts === 0){
    // No attempts in DB — clear any stale timer/expired flags from sessionStorage
    sessionStorage.removeItem('quiz_time_expired_' + id);
    sessionStorage.removeItem('quiz_timer_' + id);
    return false;
  }
  var flag = sessionStorage.getItem('quiz_time_expired_' + id);
  if(flag === '1') return true;
  var saved = sessionStorage.getItem('quiz_timer_' + id);
  return saved !== null && parseInt(saved) <= 0;
}
var allQuizzes  = <?php echo $allQuizzesJson; ?>;

// On page load, patch status for quizzes whose timer expired client-side
allQuizzes.forEach(function(q){
  // If DB shows 0 attempts, always clear stale sessionStorage timer (result was deleted)
  if(q.attempts === 0){
    sessionStorage.removeItem('quiz_time_expired_' + q.id);
    sessionStorage.removeItem('quiz_timer_' + q.id);
    return;
  }
  if(isTimerExpiredForQuiz(q.id)){
    if(q.attempts > 0){
      q.status = 'completed';
    }
  }
});
var activeStatus = "all";
var statusOptions = [
  {key:"all",      label:"All Quizzes"},
  {key:"upcoming", label:"⏳ Available"},
  {key:"completed",label:"✅ Completed"},
  {key:"locked",   label:"🔒 Expired"}
];

function computeStats(){
  var total    = allQuizzes.length;
  var done     = allQuizzes.filter(function(q){return q.status==='completed' || q.attempts > 0}).length;
  var upcoming = allQuizzes.filter(function(q){return q.status==='upcoming' && q.attempts === 0}).length;
  var locked   = allQuizzes.filter(function(q){return q.status==='locked' && q.attempts === 0}).length;
  var scored   = allQuizzes.filter(function(q){return q.score!==null});
  var avgScore = scored.length ? Math.round(scored.reduce(function(s,q){return s+q.score},0)/scored.length) : 0;
  var highest  = scored.length ? Math.max.apply(null,scored.map(function(q){return q.score})) : 0;
  return {total:total,done:done,upcoming:upcoming,locked:locked,avgScore:avgScore,highest:highest};
}

function renderSummary(){
  var s = computeStats();
  document.getElementById('summaryRow').innerHTML =
    '<div class="summary-card"><div class="summary-icon" style="background:var(--accent-glow)">📝</div><div><div class="summary-value">'+s.total+'</div><div class="summary-label">Total Quizzes</div></div></div>'+
    '<div class="summary-card"><div class="summary-icon" style="background:var(--green-bg)">✅</div><div><div class="summary-value">'+s.done+'</div><div class="summary-label">Completed</div></div></div>'+
    '<div class="summary-card"><div class="summary-icon" style="background:var(--amber-bg)">⏳</div><div><div class="summary-value">'+s.upcoming+'</div><div class="summary-label">Available</div></div></div>'+
    '<div class="summary-card"><div class="summary-icon" style="background:var(--purple-bg)">📊</div><div><div class="summary-value">'+s.avgScore+'%</div><div class="summary-label">Avg. Score · Best: '+s.highest+'%</div></div></div>';
}

function renderCourseFilter(){
  var sel = document.getElementById('courseFilter');
  sel.innerHTML = '<option value="all">All Subjects</option>';
  Object.keys(coursesMeta).forEach(function(id){
    var c = coursesMeta[id];
    sel.innerHTML += '<option value="'+id+'">'+c.icon+' '+c.title+'</option>';
  });
}

function renderStatusTabs(){
  var s = computeStats();
  document.getElementById('statusTabs').innerHTML = statusOptions.map(function(o){
    var count = o.key==='all' ? s.total : (s[o.key] || s[o.key==='upcoming'?'upcoming':o.key==='completed'?'done':o.key] || 0);
    /* map key to stat field */
    var cnt = o.key==='all' ? s.total : o.key==='completed' ? s.done : o.key==='upcoming' ? s.upcoming : s.locked;
    return '<button class="status-tab'+(activeStatus===o.key?' active':'')+'" onclick="setStatus(\''+o.key+'\')">'+o.label+' ('+cnt+')</button>';
  }).join('');
}

function setStatus(st){
  activeStatus = st;
  renderStatusTabs();
  filterQuizzes();
}

function getDueUrgency(dueSort){
  if(!dueSort) return 'none';
  var now  = new Date();
  var due  = new Date(dueSort);
  var diff = Math.ceil((due-now)/(1000*60*60*24));
  if(diff < 0)  return 'past';
  if(diff <= 2) return 'urgent';
  if(diff <= 7) return 'soon';
  return 'ok';
}

function formatDueDisplay(dueDate, dueSort){
  if(!dueDate) return {label:'No due date',value:'—',cls:''};
  var urgency = getDueUrgency(dueSort);
  var cls = urgency==='past' ? 'past' : urgency==='urgent' ? 'urgent' : urgency==='soon' ? 'soon' : 'ok';
  return {label:'Due Date',value:dueDate,cls:cls};
}

function filterQuizzes(){
  var query     = document.getElementById('searchInput').value.toLowerCase();
  var courseVal = document.getElementById('courseFilter').value;
  var sort      = document.getElementById('sortSelect').value;

  var filtered = allQuizzes.filter(function(q){
    var isEffectivelyCompleted = q.status==='completed' || q.attempts > 0;
    var isEffectivelyLocked    = q.status==='locked' && q.attempts === 0;
    var isEffectivelyUpcoming  = q.status==='upcoming' && q.attempts === 0;
    var matchStatus = activeStatus==='all' ||
      (activeStatus==='completed' && isEffectivelyCompleted) ||
      (activeStatus==='locked'    && isEffectivelyLocked) ||
      (activeStatus==='upcoming'  && isEffectivelyUpcoming);
    var matchCourse = courseVal==='all' || q.courseId==courseVal;
    var cTitle = coursesMeta[q.courseId] ? coursesMeta[q.courseId].title.toLowerCase() : '';
    var matchSearch = q.name.toLowerCase().indexOf(query)!==-1 || cTitle.indexOf(query)!==-1;
    return matchStatus && matchCourse && matchSearch;
  });

  switch(sort){
    case 'due-asc':   filtered.sort(function(a,b){return (a.dueSort||'').localeCompare(b.dueSort||'')}); break;
    case 'due-desc':  filtered.sort(function(a,b){return (b.dueSort||'').localeCompare(a.dueSort||'')}); break;
    case 'name-asc':  filtered.sort(function(a,b){return a.name.localeCompare(b.name)}); break;
    case 'score-desc':filtered.sort(function(a,b){return (b.score||0)-(a.score||0)}); break;
    case 'course':    filtered.sort(function(a,b){return a.courseId-b.courseId || (a.dueSort||'').localeCompare(b.dueSort||'')}); break;
  }

  renderQuizzes(filtered, sort==='course');
}

function renderQuizzes(list, groupByCourse){
  var el = document.getElementById('quizzesContainer');
  if(!list.length){
    el.innerHTML = '<div class="empty-state"><div class="empty-icon">📝</div><p>No quizzes found</p><span>Try adjusting your filters or search query.</span></div>';
    return;
  }
  if(groupByCourse){
    var html='';
    var grouped={};
    list.forEach(function(q){if(!grouped[q.courseId])grouped[q.courseId]=[];grouped[q.courseId].push(q);});
    Object.keys(grouped).forEach(function(cid){
      var c=coursesMeta[cid];
      var quizzes=grouped[cid];
      var doneCount=quizzes.filter(function(q){return q.status==='completed'}).length;
      html+='<div class="course-divider">'+
        '<span class="course-divider-label"><span class="course-divider-icon">'+c.icon+'</span>'+c.title+'</span>'+
        '<span style="font-size:12px;color:var(--text3);font-weight:500;white-space:nowrap">'+doneCount+'/'+quizzes.length+' completed</span>'+
        '<div class="course-divider-line"></div>'+
      '</div>';
      html+='<div class="quiz-list">';
      quizzes.forEach(function(q){html+=buildQuizItem(q);});
      html+='</div>';
    });
    el.innerHTML=html;
  } else {
    el.innerHTML='<div class="quiz-list">'+list.map(buildQuizItem).join('')+'</div>';
  }
}

function buildQuizItem(q){
  var c = coursesMeta[q.courseId] || {icon:'📚',title:'Subject',category:'Subject'};
  var isExpired   = q.dueSort !== '' && new Date(q.dueSort) < new Date();
  var isPerfectScore = q.questions > 0 && q.score === q.questions;
  var isCompleted = q.status==='completed' || q.attempts > 0;
  var isLocked    = q.status==='locked' && q.attempts === 0;
  var itemClass   = isCompleted ? 'quiz-item completed-quiz' : isLocked ? 'quiz-item locked-quiz' : 'quiz-item upcoming-quiz';

  var statusIcon = isCompleted
    ? '<div class="quiz-status-icon done">✅</div>'
    : isLocked
    ? '<div class="quiz-status-icon" style="background:var(--bg3)">🔒</div>'
    : '<div class="quiz-status-icon upcoming">⏳</div>';

  var typeTag = '<span class="quiz-type-tag quiz">quiz</span>';

  var scoreHTML = '';
  if(q.score!==null){
  var qPct = q.questions > 0 ? Math.round(q.score/q.questions*100) : 0;
  var scoreColor = qPct>=90?'var(--green)':qPct>=80?'var(--accent)':qPct>=70?'var(--amber)':'var(--red)';
  var grade = qPct>=90?'Excellent':qPct>=80?'Good':qPct>=70?'Fair':'Needs Work';
    scoreHTML='<div class="quiz-score-box"><div class="quiz-score-value" style="color:'+scoreColor+'">'+q.score+'/'+q.questions+'</div><div class="quiz-score-label">'+grade+'</div></div>';
  }

  var dueInfo = formatDueDisplay(q.dueDate, q.dueSort);
  var dueHTML = '<div class="quiz-due-box"><div class="quiz-due-label">'+dueInfo.label+'</div><div class="quiz-due-value '+dueInfo.cls+'">'+dueInfo.value+'</div></div>';

  var actionBtn = '';
  if(isCompleted){
    var attLeft = q.maxAttempts - q.attempts;
    var isPerfectScore = q.questions > 0 && q.score === q.questions;
    if(!isPerfectScore && attLeft > 0 && !isTimerExpiredForQuiz(q.id) && !isExpired){
        actionBtn = '<button class="quiz-action-btn start-btn" onclick="event.stopPropagation();startQuiz('+q.id+')" title="Retake quiz">🔄 Retake ('+attLeft+' left)</button>';
    } else {
        actionBtn = '<button class="quiz-action-btn review-btn" onclick="event.stopPropagation();openReviewModal('+q.id+')" title="View answers">📋 View Answers</button>';
    }
  } else if(isLocked){
    actionBtn = '<button class="quiz-action-btn locked-btn" style="opacity:.5;cursor:not-allowed;background:var(--red-bg);color:var(--red)" disabled title="Quiz Expired">🔒 Quiz Expired — No Longer Available</button>';
  } else {
    var urgency    = getDueUrgency(q.dueSort);
    var isExpired2 = q.dueSort !== '' && new Date(q.dueSort) < new Date();
    var hasAttempt = q.attempts > 0;
    var attLeft2   = q.maxAttempts - q.attempts;
    var isPerfectUpcoming = q.questions > 0 && q.score === q.questions;

    if(isExpired2){
      actionBtn = '<button class="quiz-action-btn locked-btn" style="opacity:.5;cursor:not-allowed;background:var(--red-bg);color:var(--red)" disabled>🔒 Quiz Expired</button>';
    } else if(isPerfectUpcoming){
      actionBtn = '<button class="quiz-action-btn review-btn" onclick="event.stopPropagation();openReviewModal('+q.id+')" title="View Answers">📋 View Answers</button>';
    } else {
      if (isTimerExpiredForQuiz(q.id)) {
        actionBtn = '<button class="quiz-action-btn review-btn" onclick="event.stopPropagation();openReviewModal('+q.id+')" title="View Answers">📋 View Answers</button>';
      } else {
        var btnClass = 'start-btn';
        var btnLabel = hasAttempt
          ? '🔄 Retake Quiz ('+attLeft2+' left)'
          : '▶ Start Quiz';
        actionBtn = '<button class="quiz-action-btn '+btnClass+'" onclick="event.stopPropagation();startQuiz('+q.id+')" title="'+btnLabel+'">'+btnLabel+'</button>';
      }
    }
  }

   var expiredStrip = isLocked
    ? '<div style="background:rgba(239,68,68,0.07);border-left:3px solid var(--red);padding:6px 12px;font-size:0.78rem;color:var(--red);display:flex;align-items:center;gap:6px;">'+
        '⛔ <strong>Quiz Expired</strong> — This quiz is no longer available. The due date has passed.'+
      '</div>'
    : '';

  return '<div class="'+itemClass+(isLocked?' locked-quiz-item':'')+'" onclick="openModal('+q.id+')" style="'+(isLocked?'opacity:0.75;':'')+'">'+
    statusIcon+
    '<div class="quiz-info">'+
      '<div class="quiz-title-row">'+
        '<span class="quiz-name">'+q.name+'</span>'+
        typeTag+
      '</div>'+
      '<div class="quiz-meta-row">'+
        '<span class="quiz-meta-item"><span class="quiz-course-tag">'+c.icon+' '+c.title+'</span></span>'+
        '<span class="quiz-meta-item">❓ '+q.questions+' questions</span>'+
        '<span class="quiz-meta-item">⏱️ '+q.timeLimit+' min</span>'+
        '<span class="quiz-meta-item">🎯 Passing: '+q.passingScore+'%</span>'+
      '</div>'+
    '</div>'+
    '<div class="quiz-right">'+
      scoreHTML+
      dueHTML+
      actionBtn+
    '</div>'+
    expiredStrip+
  '</div>';
}

function openModal(id){
  var q = allQuizzes.find(function(x){return x.id===id});
  if(!q) return;
  var c = coursesMeta[q.courseId] || {icon:'📚',title:'Subject',bannerClass:''};
  var isCompleted  = q.status==='completed';
  var isLocked     = q.status==='locked';
  var isExpiredModal = q.dueSort !== '' && new Date(q.dueSort) < new Date();
  var noMoreAttempts = q.attempts >= q.maxAttempts || isExpiredModal || isTimerExpiredForQuiz(q.id);

  document.getElementById('modalBanner').className = 'modal-header-banner '+(c.bannerClass||'');
  document.getElementById('modalBannerIcon').textContent = c.icon;

  var statusBadge = (isCompleted || q.attempts > 0)
    ? '<span class="badge badge-green">✅ Completed</span>'
    : isLocked
    ? '<span class="badge" style="background:var(--red-bg);color:var(--red)">🔒 Expired</span>'
    : '<span class="badge badge-amber">⏳ Available</span>';

  var scoreSection = '';
  if(q.score !== null){
    var mPct = q.questions > 0 ? Math.round(q.score/q.questions*100) : 0;
    var scoreColor = mPct>=90?'var(--green)':mPct>=80?'var(--accent)':mPct>=70?'var(--amber)':'var(--red)';
    var grade = mPct>=90?'Excellent':mPct>=80?'Good':mPct>=70?'Fair':'Needs Work';
    var passed = mPct>=q.passingScore;
    var noMoreAttempts = q.attempts >= q.maxAttempts || isExpiredModal || isTimerExpiredForQuiz(q.id);
    scoreSection=
      '<div class="modal-score-display">'+
        '<div class="modal-score-big" style="color:'+scoreColor+'">'+q.score+'/'+q.questions+'</div>'+
        '<div class="modal-score-grade" style="color:'+scoreColor+'">'+grade+(passed?' · ✅ Passed':' · ❌ Failed')+'</div>'+
        '<div class="modal-score-detail">'+mPct+'% · Best score · '+q.attempts+'/'+q.maxAttempts+' attempts used</div>'+
        (noMoreAttempts ? '<div style="font-size:0.8rem;color:var(--red);margin-top:6px;font-weight:600">🚫 No more attempts available</div>' : '')+
      '</div>';
  }

  var infoGrid=
    '<div class="modal-info-grid">'+
      '<div class="modal-info-card"><div class="modal-info-value">'+q.questions+'</div><div class="modal-info-label">Questions</div></div>'+
      '<div class="modal-info-card"><div class="modal-info-value">'+q.timeLimit+'m</div><div class="modal-info-label">Time Limit</div></div>'+
      '<div class="modal-info-card"><div class="modal-info-value">'+q.passingScore+'%</div><div class="modal-info-label">Passing Score</div></div>'+
      '<div class="modal-info-card"><div class="modal-info-value">'+q.maxAttempts+'</div><div class="modal-info-label">Max Attempts</div></div>'+
    '</div>';

  var dueInfo = formatDueDisplay(q.dueDate, q.dueSort);
  var dueMeta = q.dueDate
    ? '<span class="modal-quiz-meta-item" style="color:var(--'+(dueInfo.cls==='urgent'?'red':dueInfo.cls==='soon'?'amber':dueInfo.cls==='past'?'red':'text3')+')">📅 Due: '+q.dueDate+'</span>'
    : '<span class="modal-quiz-meta-item">📅 No due date</span>';
  var attemptMeta = '<span class="modal-quiz-meta-item" style="'+(noMoreAttempts?'color:var(--red)':'')+'">'+(noMoreAttempts?'🚫':'🔄')+' '+q.attempts+'/'+q.maxAttempts+' attempts'+(noMoreAttempts?' — No more attempts':'')+'</span>';

  var descHTML = q.description ? '<div class="modal-desc">'+q.description+'</div>' : '';
  var expiredNotice = '';
  if (isLocked || (isExpiredModal && q.attempts > 0 && !isCompleted)) {
    expiredNotice = '<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;padding:14px 16px;margin-bottom:16px;color:var(--red);font-size:0.875rem;">'+
        '⛔ <strong>This quiz has expired.</strong> Retakes are no longer allowed, but you can still review the questions and correct answers.'+
    '</div>';
  } else if (isLocked || (isCompleted && isExpiredModal)) {
      expiredNotice = '<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;padding:14px 16px;margin-bottom:16px;color:var(--red);font-size:0.875rem;">'+
          '⛔ <strong>This quiz has expired.</strong> ' +
          (q.attempts > 0 ? 'Retakes are no longer allowed, but you can still review the questions and correct answers below.' : 'The due date has passed and this quiz was never attempted.') +
      '</div>';
  }
  var actionBtn = '';
  if(isCompleted){
    var attemptsLeft = q.maxAttempts - q.attempts;
    var isPerfectModal = q.questions > 0 && q.score === q.questions;
    if(!isPerfectModal && attemptsLeft > 0 && !isExpiredModal && !isTimerExpiredForQuiz(q.id)){
        actionBtn = '<button class="btn btn-ghost" onclick="closeModal()">Close</button>'+
            '<button class="btn btn-primary" onclick="startQuiz('+q.id+');closeModal()">🔄 Retake Quiz ('+attemptsLeft+' left)</button>';
    } else {
      var reviewBtn = q.attempts > 0
        ? '<button class="btn btn-primary" onclick="openReviewModal('+q.id+');closeModal()">📋 View Review & Answers</button>'
        : '<button class="btn" style="opacity:.6;cursor:not-allowed;background:var(--red-bg);color:var(--red);border:1px solid var(--red)" disabled>🔒 Quiz Expired — No Retakes Allowed</button>';
      actionBtn = '<button class="btn btn-ghost" onclick="closeModal()">Close</button>'+ reviewBtn;
    }
  } else if(isLocked){
    var lockedReviewBtn = '<button class="btn btn-primary" onclick="openReviewModal('+q.id+');closeModal()">📋 View Questions & Answers</button>';
    actionBtn = '<button class="btn btn-ghost" onclick="closeModal()">Close</button>'+ lockedReviewBtn;
  } else {
    var urgency     = getDueUrgency(q.dueSort);
    var hasAttempt2 = q.attempts > 0;
    var attLeft3    = q.maxAttempts - q.attempts;
    var isPerfectUpcomingModal = q.questions > 0 && q.score === q.questions;
    if(isExpiredModal){
      var expiredReviewBtn = '<button class="btn btn-primary" onclick="openReviewModal('+q.id+');closeModal()">📋 View Questions & Answers</button>';
      actionBtn = '<button class="btn btn-ghost" onclick="closeModal()">Close</button>'+ expiredReviewBtn;
    } else if(isPerfectUpcomingModal){
      actionBtn = '<button class="btn btn-ghost" onclick="closeModal()">Close</button>'+
        '<button class="btn btn-primary" onclick="openReviewModal('+q.id+');closeModal()">📋 View Answers</button>';
    } else {
      if(isTimerExpiredForQuiz(q.id)){
        actionBtn = '<button class="btn btn-ghost" onclick="closeModal()">Close</button>'+
          '<button class="btn btn-primary" onclick="openReviewModal('+q.id+');closeModal()">📋 View Answers</button>';
      } else {
        var btnLabel = hasAttempt2
          ? '🔄 Retake Quiz ('+attLeft3+' left)'
          : '▶ Start Quiz';
        actionBtn = '<button class="btn btn-ghost" onclick="closeModal()">Close</button>'+
          '<button class="btn btn-primary" onclick="startQuiz('+q.id+');closeModal()">'+btnLabel+'</button>';
      }
    }
  }

  document.getElementById('modalBody').innerHTML=
    '<div class="modal-quiz-title">'+q.name+'</div>'+
    '<div class="modal-quiz-meta">'+
      '<span class="modal-quiz-meta-item">'+c.icon+' '+c.title+'</span>'+
      dueMeta+
      attemptMeta+
      '<span style="margin-left:auto">'+statusBadge+'</span>'+
    '</div>'+
    scoreSection+
    descHTML+
    expiredNotice+
    infoGrid+
    '<div class="modal-actions-row">'+actionBtn+'</div>';

  document.getElementById('modalBackdrop').classList.add('open');
  document.body.style.overflow='hidden';
}

function closeModal(){
  document.getElementById('modalBackdrop').classList.remove('open');
  document.body.style.overflow='';
}
function closeModalOutside(e){if(e.target===document.getElementById('modalBackdrop'))closeModal();}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeModal();});

function startQuiz(id){
  var q = allQuizzes.find(function(x){return x.id===id});
  if(!q) return;

  var timerKey = 'quiz_timer_' + id;
  var saved = sessionStorage.getItem(timerKey);
  if (saved !== null && parseInt(saved) <= 0) {
    showToast('Your time for this quiz has run out. No more attempts allowed.', 'error');
    return;
  }

  closeModal();
  openTakeQuizModal(q);
}

function openTakeQuizModal(q){
  var c = coursesMeta[q.courseId] || {icon:'📚', title:'Subject'};

  document.getElementById('takeQuizBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
  document.getElementById('takeQuizContent').innerHTML =
    '<div style="text-align:center;padding:60px 20px;color:var(--text2)">'+
    '<div style="font-size:2.5rem;margin-bottom:16px">⏳</div>'+
    '<div style="font-size:1.1rem;font-weight:600">Loading quiz...</div></div>';

  fetch('student_quizzes.php?ajax=get_quiz&quiz_id='+q.id)
    .then(function(r){
      if(!r.ok) throw new Error('HTTP '+r.status);
      return r.text();
    })
  .then(function(text){
    var data;
    try { data = JSON.parse(text); }
    catch(e) {
        console.error('Raw server response:', text);
        document.getElementById('takeQuizContent').innerHTML =
          '<div style="text-align:center;padding:60px 20px;color:var(--red)">'+
          '<div style="font-size:2rem;margin-bottom:12px">❌</div>'+
          '<div>Server error. Check browser console for details.</div>'+
          '<pre style="text-align:left;font-size:11px;max-height:150px;overflow:auto;color:var(--text2)">'+text.substring(0,500)+'</pre>'+
          '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
        return;
    }
    if(!data.success){
      document.getElementById('takeQuizContent').innerHTML =
        '<div style="text-align:center;padding:60px 20px;color:var(--red)">'+
        '<div style="font-size:2rem;margin-bottom:12px">⚠️</div>'+
        '<div style="font-weight:600;margin-bottom:8px">'+(data.message||'Quiz not available.')+'</div>'+
        '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
      return;
    }
    renderTakeQuiz(q, c, data.questions);
  })
    .catch(function(err){
      document.getElementById('takeQuizContent').innerHTML =
        '<div style="text-align:center;padding:60px 20px;color:var(--red)">'+
        '<div style="font-size:1.1rem;font-weight:700;margin-bottom:10px">Error Loading Review</div>'+
        '<pre style="text-align:left;font-size:11px;max-height:200px;overflow:auto;background:var(--bg2);padding:10px;border-radius:8px;color:var(--text2);white-space:pre-wrap;word-break:break-all">'+
          (err && err.message ? err.message : String(err))+
        '</pre>'+
        '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
    });
}

var _quizAnswers    = {};
var _quizTimerInt   = null;
var _quizTimeLeft   = 0;
var _currentQuizObj = null;
var _currentQs      = [];
var _timerStorageKey = '';

function renderTakeQuiz(q, c, questions){
  _currentQuizObj = q;
  _currentQs      = questions;
  _quizAnswers    = {};
  _timerStorageKey = 'quiz_timer_' + q.id;

  var saved = sessionStorage.getItem(_timerStorageKey);
  if (saved !== null && parseInt(saved) > 0) {
    _quizTimeLeft = parseInt(saved);
  } else {
    _quizTimeLeft = q.timeLimit * 60;
    sessionStorage.setItem(_timerStorageKey, _quizTimeLeft);
  }

  var html =
    '<div class="tq-header">'+
      '<div class="tq-header-top">'+
        '<div>'+
          '<div class="tq-subject-tag">'+c.icon+' '+c.title+'</div>'+
          '<div class="tq-title">'+q.name+'</div>'+
        '</div>'+
        '<div class="tq-timer" id="tqTimer">⏱️ <span id="tqTimerVal">'+formatTime(_quizTimeLeft)+'</span></div>'+
      '</div>'+
      '<div class="tq-progress-bar"><div class="tq-progress-fill" id="tqProgressFill" style="width:0%"></div></div>'+
      '<div class="tq-progress-label" id="tqProgressLabel">0 of '+questions.length+' answered</div>'+
    '</div>'+
    '<div class="tq-questions" id="tqQuestions">';

  questions.forEach(function(qs, idx){
    html += '<div class="tq-question-block" id="tqQ'+idx+'">';
    html += '<div class="tq-q-number">Question '+(idx+1)+' <span style="color:var(--text3);font-weight:400">of '+questions.length+'</span></div>';
    html += '<div class="tq-q-text">'+qs.question+'</div>';
    html += '<div class="tq-choices">';

    var choices = qs.choices || qs.options || [];
    if (choices.length > 0) {
      choices.forEach(function(ch, ci){
        var choiceLabel = String.fromCharCode(65+ci);
        html += '<label class="tq-choice" id="tqChoice_'+idx+'_'+ci+'" onclick="selectAnswer('+idx+',\''+escapeAttr(ch)+'\','+ci+','+choices.length+')">';
        html += '<span class="tq-choice-letter">'+choiceLabel+'</span>';
        html += '<span class="tq-choice-text">'+ch+'</span>';
        html += '<input type="radio" name="q'+idx+'" value="'+escapeAttr(ch)+'" style="display:none">';
        html += '</label>';
      });
    } else {
      html += '<input type="text" class="tq-text-answer" id="tqText_'+idx+'" placeholder="Type your answer here..." '+
              'oninput="selectTextAnswer('+idx+', this.value)" '+
              'style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;background:var(--bg2);color:var(--text1);font-size:0.95rem;margin-top:8px;box-sizing:border-box;">';
    }

    html += '</div></div>';
  });

  html += '</div>';
  html += '<div class="tq-footer">'+
    '<button class="btn btn-ghost" onclick="confirmAbandonQuiz()">✕ Abandon</button>'+
    '<button class="btn btn-primary" id="tqSubmitBtn" onclick="submitQuiz()">Submit Quiz →</button>'+
  '</div>';

  document.getElementById('takeQuizContent').innerHTML = html;

  clearInterval(_quizTimerInt);
  _quizTimerInt = setInterval(function(){
    _quizTimeLeft--;
    sessionStorage.setItem(_timerStorageKey, _quizTimeLeft);
    var el = document.getElementById('tqTimerVal');
    if(el) el.textContent = formatTime(_quizTimeLeft);
    var timerBox = document.getElementById('tqTimer');
    if(timerBox){
      if(_quizTimeLeft <= 60)       timerBox.style.color = 'var(--red)';
      else if(_quizTimeLeft <= 300) timerBox.style.color = 'var(--amber)';
    }
    if(_quizTimeLeft <= 0){
      clearInterval(_quizTimerInt);
      sessionStorage.removeItem(_timerStorageKey);
      sessionStorage.setItem('quiz_time_expired_' + _currentQuizObj.id, '1');
      showToast('Time is up! Submitting quiz...','info');
      submitQuiz();
    }
  }, 1000);
}

function escapeAttr(str){
  return String(str).replace(/'/g,"\\'").replace(/"/g,'&quot;');
}

function selectTextAnswer(qIdx, value){
  _quizAnswers[qIdx] = value;
  var answered = Object.keys(_quizAnswers).filter(function(k){ return _quizAnswers[k] !== ''; }).length;
  var total2   = _currentQs.length;
  var pct      = Math.round(answered/total2*100);
  var fill     = document.getElementById('tqProgressFill');
  var label    = document.getElementById('tqProgressLabel');
  if(fill)  fill.style.width  = pct+'%';
  if(label) label.textContent = answered+' of '+total2+' answered';
}

function closeTakeQuizModal(){
  clearInterval(_quizTimerInt);
  document.getElementById('takeQuizBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}

function formatTime(sec){
  var m = Math.floor(sec/60);
  var s = sec % 60;
  return (m<10?'0':'')+m+':'+(s<10?'0':'')+s;
}

function selectAnswer(qIdx, value, choiceIdx, total){
  _quizAnswers[qIdx] = value;

  for(var ci=0; ci<total; ci++){
    var el = document.getElementById('tqChoice_'+qIdx+'_'+ci);
    if(el) el.classList.remove('selected');
  }
  var chosen = document.getElementById('tqChoice_'+qIdx+'_'+choiceIdx);
  if(chosen) chosen.classList.add('selected');

  var answered = Object.keys(_quizAnswers).length;
  var total2   = _currentQs.length;
  var pct      = Math.round(answered/total2*100);
  var fill     = document.getElementById('tqProgressFill');
  var label    = document.getElementById('tqProgressLabel');
  if(fill)  fill.style.width  = pct+'%';
  if(label) label.textContent = answered+' of '+total2+' answered';
}

function submitQuiz(){
  clearInterval(_quizTimerInt);

  var answered = Object.keys(_quizAnswers).length;
  var total    = _currentQs.length;

  document.getElementById('takeQuizContent').innerHTML =
    '<div style="text-align:center;padding:60px 20px;color:var(--text2)">'+
    '<div style="font-size:2.5rem;margin-bottom:16px">📤</div>'+
    '<div style="font-size:1.1rem;font-weight:600">Submitting your answers...</div></div>';

  fetch('student_quizzes.php?ajax=submit_quiz', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      quiz_id:   _currentQuizObj.id,
      answers:   _quizAnswers,
      time_used: (_currentQuizObj.timeLimit * 60) - _quizTimeLeft
    })
  })
  .then(function(r){ return r.text(); })
  .then(function(text){
    var data;
    try { data = JSON.parse(text); }
    catch(e) {
      console.error('Submit response was not JSON:', text);
      document.getElementById('takeQuizContent').innerHTML =
        '<div style="text-align:center;padding:60px 20px;color:var(--red)">'+
        '<div style="font-size:2rem;margin-bottom:12px">❌</div>'+
        '<div>Server error during submission.</div>'+
        '<pre style="text-align:left;font-size:11px;max-height:150px;overflow:auto;background:var(--bg2);padding:8px;border-radius:6px;color:var(--text2)">'+text.substring(0,600)+'</pre>'+
        '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
      return;
    }
    if(data.success){
      sessionStorage.removeItem(_timerStorageKey);
      renderQuizResult(data);
    } else {
      document.getElementById('takeQuizContent').innerHTML =
        '<div style="text-align:center;padding:60px 20px;color:var(--red)">'+
        '<div style="font-size:2rem;margin-bottom:12px">❌</div>'+
        '<div>'+(data.message||'Submission failed.')+'</div>'+
        '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
    }
  })
  .catch(function(err){
    console.error('Submit fetch error:', err);
    document.getElementById('takeQuizContent').innerHTML =
      '<div style="text-align:center;padding:60px 20px;color:var(--red)">'+
      '<div style="font-size:2rem;margin-bottom:12px">❌</div>'+
      '<div>Network error during submission. Please check your connection.</div>'+
      '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
  });
}

function renderQuizResult(data){
  var score   = data.score;
  var correct = data.correct;
  var total   = data.total;
  var rPct    = total > 0 ? Math.round(correct/total*100) : 0;
  var passed  = rPct >= _currentQuizObj.passingScore;
  var scoreColor = rPct>=90?'var(--green)':rPct>=80?'var(--accent)':rPct>=70?'var(--amber)':'var(--red)';
  var grade      = rPct>=90?'Excellent':rPct>=80?'Good':rPct>=70?'Fair':'Needs Work';

  var q = allQuizzes.find(function(x){return x.id===_currentQuizObj.id});
  if(q){
    q.attempts++;
    if(q.score === null || score > q.score) q.score = score;
    if(q.attempts >= q.maxAttempts || rPct === 100) q.status = 'completed';
  }
  var attemptsLeft = q ? (q.maxAttempts - q.attempts) : 0;
  var timerExpired = isTimerExpiredForQuiz(_currentQuizObj.id) || _quizTimeLeft <= 0;

  var isPerfect = (rPct === 100);
  var retakeBtn = (!isPerfect && attemptsLeft > 0 && !timerExpired)
    ? '<button class="btn btn-primary" onclick="closeTakeQuizModal();setTimeout(function(){startQuiz('+_currentQuizObj.id+')},200)">🔄 Retake Quiz ('+attemptsLeft+' left)</button>'
    : '<button class="btn btn-primary" onclick="closeTakeQuizModal();setTimeout(function(){openReviewModal('+_currentQuizObj.id+')},200)">📋 View Answers</button>';

  document.getElementById('takeQuizContent').innerHTML =
    '<div class="tq-result">'+
      '<div class="tq-result-icon">'+(passed?'🎉':'😔')+'</div>'+
      '<div class="tq-result-score" style="color:'+scoreColor+'">'+correct+'/'+total+'</div>'+
      '<div class="tq-result-grade" style="color:'+scoreColor+'">'+grade+' ('+rPct+'%) · '+(passed?'✅ Passed':'❌ Failed')+'</div>'+
      '<div class="tq-result-detail">'+rPct+'% score</div>'+
      (isPerfect ? '<div style="font-size:0.85rem;color:var(--green);margin-top:4px">🏆 Perfect score! Review the answers below.</div>' : timerExpired ? '<div style="font-size:0.85rem;color:var(--red);margin-top:4px">⏱️ Time expired — no more retakes available</div>' : attemptsLeft > 0 ? '<div style="font-size:0.85rem;color:var(--text2);margin-top:4px">Best score kept · '+attemptsLeft+' attempt(s) remaining</div>' : '<div style="font-size:0.85rem;color:var(--amber);margin-top:4px">All 3 attempts used — answers now unlocked</div>')+
      '<div class="tq-result-actions">'+
        '<button class="btn btn-ghost" onclick="closeTakeQuizModal();filterQuizzes()">✕ Close</button>'+
        retakeBtn+
      '</div>'+
    '</div>';
}

function confirmAbandonQuiz(){
  if(confirm('Abandon this quiz? Your answers so far will be saved as a partial attempt.')){
    clearInterval(_quizTimerInt);
    var timeUsed = _currentQuizObj ? (_currentQuizObj.timeLimit * 60) - _quizTimeLeft : 0;

    // Only submit if there's an active quiz
    if(!_currentQuizObj) { closeTakeQuizModal(); return; }

    fetch('student_quizzes.php?ajax=submit_quiz', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        quiz_id:   _currentQuizObj.id,
        answers:   _quizAnswers,
        time_used: timeUsed
      })
    })
    .then(function(r){ return r.text(); })
    .then(function(text){
      try {
        var data = JSON.parse(text);
        if(data.success){
          sessionStorage.removeItem(_timerStorageKey);
          var q = allQuizzes.find(function(x){ return x.id === _currentQuizObj.id; });
          if(q){
            q.attempts++;
            if(q.score === null || data.score > q.score) q.score = data.score;
            if(q.attempts >= q.maxAttempts) q.status = 'completed';
          }
          showToast('Partial attempt saved. You can still retake the quiz.', 'info');
        }
      } catch(e){ /* silent fail on parse error */ }
      closeTakeQuizModal();
      filterQuizzes();
      renderSummary();
      renderStatusTabs();
    })
    .catch(function(){
      closeTakeQuizModal();
      filterQuizzes();
    });
  }
}
function openReviewModal(id){
  var q = allQuizzes.find(function(x){return x.id===id});
  if(!q) return;

  document.getElementById('takeQuizBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
  document.getElementById('takeQuizContent').innerHTML =
    '<div style="text-align:center;padding:60px 20px;color:var(--text2)">'+
    '<div style="font-size:2.5rem;margin-bottom:16px">📋</div>'+
    '<div style="font-size:1.1rem;font-weight:600">Loading review...</div></div>';

  fetch('student_quizzes.php?ajax=get_quiz_review&quiz_id='+id)
    .then(function(r){
      return r.text().then(function(text){
        try { return JSON.parse(text); }
        catch(e){
          throw new Error('HTTP '+r.status+' | Raw PHP output:\n'+text.substring(0,1000));
        }
      });
    })
    .then(function(data){
      if(!data.success){
        document.getElementById('takeQuizContent').innerHTML =
          '<div style="text-align:center;padding:60px 20px;color:var(--red)">'+
          '<div style="font-size:2rem;margin-bottom:12px">🔒</div>'+
          '<div>'+(data.message||'Review not available.')+'</div>'+
          '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
        return;
      }
      var c = coursesMeta[q.courseId] || {icon:'📚', title:'Subject'};
      var reviewPct = q.questions > 0 ? Math.round(data.best_score/q.questions*100) : 0;
      var scoreColor = reviewPct>=90?'var(--green)':reviewPct>=80?'var(--accent)':reviewPct>=70?'var(--amber)':'var(--red)';
      var html =
        '<div class="tq-header">'+
          '<div class="tq-header-top">'+
            '<div>'+
              '<div class="tq-subject-tag">'+c.icon+' '+c.title+'</div>'+
              '<div class="tq-title">'+q.name+' — Answer Review</div>'+
            '</div>'+
            '<div style="text-align:right">'+
            (data.attempts > 0
              ? '<div style="font-size:1.5rem;font-weight:800;color:'+scoreColor+'">Best: '+data.best_score+'/'+q.questions+' ('+reviewPct+'%)</div>'
              : '<div style="font-size:1rem;font-weight:600;color:var(--red)">Never attempted — showing correct answers</div>')+
            '<div style="font-size:0.78rem;color:var(--text3)">'+data.attempts+'/3 attempts used</div>'+
            '</div>'+
          '</div>'+
        '</div>'+
        '<div class="tq-questions" id="tqQuestions">';

      data.questions.forEach(function(qs, idx){
        var letters = ['A','B','C','D'];
        html += '<div class="tq-question-block" style="border-left:3px solid var(--accent);padding-left:12px">';
        html += '<div class="tq-q-number">Question '+(idx+1)+'</div>';
        html += '<div class="tq-q-text">'+qs.question+'</div>';
        html += '<div class="tq-choices">';
        if(qs.choices && qs.choices.length > 0){
          qs.choices.forEach(function(ch, ci){
            var isCorrect = ch === qs.correct_text;
            var style = isCorrect
              ? 'background:rgba(34,197,94,0.15);border-color:var(--green);'
              : 'opacity:0.6;';
            html += '<div class="tq-choice" style="pointer-events:none;'+style+'">'+
              '<span class="tq-choice-letter" style="'+(isCorrect?'background:var(--green);color:#fff;':'')+'">'+letters[ci]+'</span>'+
              '<span class="tq-choice-text">'+ch+'</span>'+
              (isCorrect ? '<span style="margin-left:auto;color:var(--green);font-weight:700">✓ Correct</span>' : '')+
            '</div>';
          });
        } else {
          html += '<div style="padding:10px 14px;border:1.5px solid var(--green);border-radius:8px;background:rgba(34,197,94,0.1);color:var(--green);font-weight:600">✓ '+qs.correct_text+'</div>';
        }
        html += '</div></div>';
      });

      html += '</div>';
      html += '<div class="tq-footer">'+
        '<button class="btn btn-ghost" onclick="closeTakeQuizModal();filterQuizzes()">✕ Close</button>'+
      '</div>';

      document.getElementById('takeQuizContent').innerHTML = html;
    })
    .catch(function(err){
      document.getElementById('takeQuizContent').innerHTML =
        '<div style="text-align:center;padding:60px 20px;color:var(--red)">'+
        '<div style="font-size:1.1rem;font-weight:700;margin-bottom:10px">Error Loading Review</div>'+
        '<pre style="text-align:left;font-size:11px;max-height:200px;overflow:auto;background:var(--bg2);padding:10px;border-radius:8px;color:var(--text2);white-space:pre-wrap;word-break:break-all">'+
          (err && err.message ? err.message : String(err))+
        '</pre>'+
        '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
    });
}
function closeTakeQuizModal(){
  clearInterval(_quizTimerInt);
  document.getElementById('takeQuizBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}

function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('open');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}

function applyTheme(theme) {
  var isLight = theme === 'light';
  document.body.classList.toggle('light', isLight);
  document.getElementById('theme-icon').textContent = isLight ? '☀️' : '🌙';
  document.getElementById('theme-text').textContent = isLight ? 'Light Mode' : 'Dark Mode';
}

function toggleTheme() {
  var isLight = document.body.classList.contains('light');
  var newTheme = isLight ? 'dark' : 'light';
  applyTheme(newTheme);
  fetch('settings_handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ theme: newTheme })
  });
}

// Load theme from DB on page load
fetch('settings_handler.php')
  .then(function(r){ return r.json(); })
  .then(function(res){
    if (res.success && res.data) {
      applyTheme(res.data.theme || 'dark');
    }
  })
  .catch(function(){});

function showToast(msg,type){
  type=type||'success';
  var icons={success:'✅',error:'❌',info:'ℹ️'};
  var container=document.getElementById('toast-container');
  var t=document.createElement('div');
  t.className='toast '+type;
  t.innerHTML='<span class="toast-icon">'+(icons[type]||'✅')+'</span><span>'+msg+'</span>';
  container.appendChild(t);
  setTimeout(function(){
    t.style.opacity='0';t.style.transform='translateX(60px)';t.style.transition='all .3s ease';
    setTimeout(function(){t.remove();},300);
  },3000);
}

renderSummary();
renderCourseFilter();
renderStatusTabs();
filterQuizzes();

// ── Notification Modal ────────────────────────────────────────────────────
function openNotifModal() {
  var m = document.getElementById('notifModal');
  m.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeNotifModal() {
  var m = document.getElementById('notifModal');
  m.style.display = 'none';
  document.body.style.overflow = '';
}
document.getElementById('notifModal').addEventListener('click', function(e) {
  if (e.target === this) closeNotifModal();
});

function markOneRead(id, el) {
  var url = el.dataset.url || '#';
  fetch('notif_mark_read.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'notification_id=' + id
  });
  if (!el.dataset.read) {
    el.style.background = 'rgba(255,255,255,.03)';
    el.style.border = '1px solid rgba(255,255,255,.07)';
    var dot = el.querySelector('span[style*="border-radius:50%"]');
    var title = el.querySelector('div[style*="font-weight"]');
    if (dot) dot.remove();
    if (title) title.style.fontWeight = '500';
    el.dataset.read = '1';
    updateBadgeCount(-1);
  }
  if (url && url !== '#') {
    setTimeout(function() { window.location.href = url; }, 150);
  }
}

function markAllRead() {
  fetch('notif_mark_read.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'mark_all=1'
  });
  document.querySelectorAll('.notif-item').forEach(function(el) {
    el.style.background = 'rgba(255,255,255,.03)';
    el.style.border = '1px solid rgba(255,255,255,.07)';
    var dot = el.querySelector('span[style*="border-radius:50%"]');
    var title = el.querySelector('div[style*="font-weight"]');
    if (dot) dot.remove();
    if (title) title.style.fontWeight = '500';
    el.dataset.read = '1';
  });
  updateBadgeCount(0);
  document.getElementById('notif-unread-count').textContent = '';
}

function updateBadgeCount(delta) {
  var badge = document.getElementById('notif-badge');
  if (!badge) return;
  var current = parseInt(badge.textContent) || 0;
  var next = delta === 0 ? 0 : Math.max(0, current + delta);
  if (next <= 0) { badge.style.display = 'none'; }
  else { badge.textContent = next; badge.style.display = 'flex'; }
}

function deleteNotif(id, btn, e) {
  e.stopPropagation();
  var item = btn.closest('.notif-item');
  fetch('notif_delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'notification_id=' + id
  });
  var wasUnread = !item.dataset.read && item.style.border.includes('99,102,241');
  item.style.transition = 'opacity .25s, transform .25s';
  item.style.opacity = '0';
  item.style.transform = 'translateX(30px)';
  setTimeout(function() {
    item.remove();
    var remaining = document.querySelectorAll('.notif-item');
    if (remaining.length === 0) {
      var body = document.querySelector('#notifModal > div > div:last-child');
      body.innerHTML = '<div style="text-align:center;padding:40px 16px;color:var(--text3,#64748b);"><div style="font-size:36px;margin-bottom:8px;">🔕</div><div style="font-size:13px;">No notifications yet.</div></div>';
    }
  }, 250);
  if (wasUnread) updateBadgeCount(-1);
}
</script>
</body>
</html>