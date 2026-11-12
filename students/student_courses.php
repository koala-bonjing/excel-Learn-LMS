<?php
ob_start();
session_start();
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
            $formatted['choices']     = $choices;
            $formatted['correct_key'] = strtolower(trim($item['correct'] ?? ''));
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

    $ar = $conn->prepare("SELECT answers, manual_score FROM tblquiz_results WHERE quizzes_id = ? AND student_id = ? ORDER BY score DESC, created_at DESC LIMIT 1");
    $ar->bind_param("ii", $quiz_id, $student_id);
    $ar->execute();
    $arow = $ar->get_result()->fetch_assoc();
    $ar->close();
    $student_answers_review = $arow ? json_decode($arow['answers'] ?? '[]', true) : [];
    if (!is_array($student_answers_review)) $student_answers_review = [];

    echo json_encode([
        'success'         => true,
        'questions'       => $review,
        'best_score'      => (int)$srow['best'],
        'attempts'        => (int)$srow['cnt'],
        'manual_score'    => ($arow && $arow['manual_score'] !== null) ? (int)$arow['manual_score'] : null,
        'student_answers' => $student_answers_review,
    ]);
    exit();
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'submit_quiz') {
    while (ob_get_level()) ob_end_clean();
    ob_start();
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
    $has_manual_items = false;
    foreach ($decoded as $idx => $item) {
        $type           = $item['type'] ?? 'multiple_choice';
        $correct_key    = strtolower(trim($item['correct'] ?? ''));
        $student_answer = strtolower(trim($answers[(string)$idx] ?? ''));

        if ($type === 'multiple_choice') {
            $opts         = $item['options'] ?? [];
            $correct_text = strtolower(trim($opts[$correct_key] ?? $correct_key));
            if ($student_answer === $correct_text || $student_answer === $correct_key) {
                $correct++;
            }
        } else {
            // Essay/identification — teacher checks manually, skip auto-grade
            $has_manual_items = true;
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
        echo json_encode(['success' => true, 'score' => $score, 'correct' => $correct, 'total' => $total, 'passed' => $passed, 'needs_review' => $has_manual_items]);
    } else {
        $err = $ins->error;
        $ins->close();
        echo json_encode(['success' => false, 'message' => 'Failed to save: ' . $err]);
    }
    exit();
}

function formatTimeAgo($datetime_str) {
    if (empty($datetime_str)) return 'Just now';
    $now  = new DateTime();
    $then = new DateTime($datetime_str);
    if ($then > $now) $then = $now;
    $diff = $now->diff($then);
    if ($diff->y > 0) return $diff->y . ' year'  . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day'   . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour'  . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute'. ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/* NEW: Calculate duration since creation (days, weeks, months) */
function formatDurationSince($datetime_str) {
    if (empty($datetime_str)) return 'N/A';
    $now  = new DateTime();
    $then = new DateTime($datetime_str);
    if ($then > $now) return '0 days';
    $diff = $now->diff($then);
    
    $parts = [];
    if ($diff->y > 0) $parts[] = $diff->y . ' yr' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) $parts[] = $diff->m . ' mo' . ($diff->m > 1 ? 's' : '');
    
    /* Show weeks only if no months/years, and days > 6 */
    if (empty($parts)) {
        $total_days = $diff->days;
        if ($total_days >= 7) {
            $weeks = floor($total_days / 7);
            $remaining_days = $total_days % 7;
            $parts[] = $weeks . ' wk' . ($weeks > 1 ? 's' : '');
            if ($remaining_days > 0) {
                $parts[] = $remaining_days . ' d';
            }
        } else {
            if ($diff->d > 0) $parts[] = $diff->d . ' d';
        }
    }
    
    if (empty($parts)) return '0 days';
    return implode(' ', $parts);
}

function getStudentAvatar($student) {
    $pic = $student['profile_pic'] ?? '';
    $fn  = $student['firstname'] ?? '';
    $ln  = $student['lastname']  ?? '';
    $initials = strtoupper(substr($fn, 0, 1) . substr($ln, 0, 1));
    if (strlen(trim($initials)) < 2) $initials = 'S';

    if (!empty($pic)) {
        $paths = [
            '../uploads/profile_pic/students/' . $pic,
            '../uploads/profile_pic/' . $pic,
            '../' . $pic
        ];
        foreach ($paths as $p) {
            if (file_exists($p)) {
                return '<img src="' . htmlspecialchars($p) . '" style="width:100%;height:100%;object-fit:cover;border-radius:inherit" alt="' . htmlspecialchars($initials) . '">';
            }
        }
    }
    return htmlspecialchars($initials);
}

function getBannerClass($subject_name) {
    $classes = ['banner-science', 'banner-math', 'banner-history', 'banner-english', 'banner-art'];
    $idx = abs(crc32($subject_name)) % count($classes);
    return $classes[$idx];
}

function getBadgeClass($subject_name) {
    $classes = ['badge-cyan', 'badge-blue', 'badge-purple', 'badge-green', 'badge-red'];
    $idx = abs(crc32($subject_name)) % count($classes);
    return $classes[$idx];
}

function getSubjectIcon($subject_name) {
    $s = strtolower($subject_name);
    if (strpos($s, 'bio') !== false) return '🧬';
    if (strpos($s, 'math') !== false || strpos($s, 'algebra') !== false || strpos($s, 'calc') !== false) return '📐';
    if (strpos($s, 'history') !== false) return '🌍';
    if (strpos($s, 'eng') !== false || strpos($s, 'lit') !== false) return '📖';
    if (strpos($s, 'phys') !== false) return '⚛️';
    if (strpos($s, 'chem') !== false) return '⚗️';
    if (strpos($s, 'comp') !== false || strpos($s, 'prog') !== false) return '💻';
    return '📚';
}

// Resolves the actual uploaded file (video/pdf/ppt) path para sa lesson resource.
function getLessonResourceUrl($path, $resource_type = '') {
    if (empty($path)) return '';
    $typeFolders = ['pdf' => 'pdf', 'ppt' => 'ppts', 'video' => 'video'];
    $candidates = [];
    if (isset($typeFolders[$resource_type])) {
        $candidates[] = '../uploads/lesson/' . $typeFolders[$resource_type] . '/' . basename($path);
    }
    foreach ($typeFolders as $folder) {
        $candidates[] = '../uploads/lesson/' . $folder . '/' . basename($path);
    }
    $candidates[] = '../' . ltrim($path, '/');
    $candidates[] = $path;
    foreach ($candidates as $c) {
        if (file_exists($c)) return $c;
    }
    return '';
}

$stmt = $conn->prepare("
    SELECT 
        s.id,
        s.firstname,
        s.middlename,
        s.lastname,
        s.profile_pic,
        s.section_id,
        sec.section,
        g.grade_level
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

 $student_name = trim($student['firstname'] . ' ' . ($student['middlename'] ? $student['middlename'] . ' ' : '') . $student['lastname']);
 $student_avatar_html = getStudentAvatar($student);
 $student_grade = htmlspecialchars($student['grade_level'] ?? '');
 $student_section = htmlspecialchars($student['section'] ?? '');

 $effective_user_id = null;
 $debug_info = [];
 $debug_info[] = "Student ID: {$student_id}";

if (!empty($teacher_id)) {
    /* TRY A: Direct — teacher_id is already tblusers.id */
    $chk = $conn->prepare("
        SELECT tc.id FROM tblteachers_courses tc 
        WHERE tc.user_id = ? LIMIT 1
    ");
    $chk->bind_param("i", $teacher_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $effective_user_id = (int)$teacher_id;
        $debug_info[] = "Method: DIRECT (teacher_id = user_id)";
    }
    $chk->close();
}

if ($effective_user_id === null && !empty($teacher_id)) {
    /* TRY B: Through tblteachers table */
    $chk2 = $conn->prepare("SELECT user_id FROM tblteachers WHERE id = ? LIMIT 1");
    $chk2->bind_param("i", $teacher_id);
    $chk2->execute();
    $t_row = $chk2->get_result()->fetch_assoc();
    $chk2->close();
    if ($t_row && !empty($t_row['user_id'])) {
        $effective_user_id = (int)$t_row['user_id'];
        $debug_info[] = "Method: VIA tblteachers (teacher_id -> tblteachers.id -> user_id = {$effective_user_id})";
    } else {
        $debug_info[] = "Method B FAILED: tblteachers row not found OR user_id is empty for teacher_id={$teacher_id}";
    }
}

if ($effective_user_id === null) {
    $debug_info[] = "RESULT: Could not resolve any user_id. No courses will load.";
}


$coursesData = [];

$stmt = $conn->prepare("
    SELECT DISTINCT teacher_subject_id
    FROM tbl_teachers_students 
    WHERE student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$enrolledTSIds = [];
while ($row = $res->fetch_assoc()) {
    $enrolledTSIds[] = (int)$row['teacher_subject_id'];
}
$stmt->close();

if (!empty($enrolledTSIds)) {
    $placeholders = implode(',', array_fill(0, count($enrolledTSIds), '?'));
    $types = str_repeat('i', count($enrolledTSIds));

    $stmt = $conn->prepare("
        SELECT 
            ts.teacher_subject_id,
            ts.subject_id,
            ts.teacher_id,
            ts.updated_at,
            sub.subject,
            sub.description AS subject_description,
            st.strand,
            st.track,
            ts.created_at,
            u.firstname AS teacher_firstname,
            u.lastname  AS teacher_lastname,
            u.profile_pic AS teacher_pic,
            (SELECT COUNT(*) FROM tbl_teacher_lessons tl 
             WHERE tl.teacher_subject_id = ts.teacher_subject_id) AS lesson_count,
            (SELECT COUNT(DISTINCT tst.student_id) FROM tbl_teachers_students tst 
             WHERE tst.teacher_subject_id = ts.teacher_subject_id) AS student_count
        FROM tbl_teacher_subjects ts
        LEFT JOIN tblsubjects sub 
            ON sub.subject_id = ts.subject_id
        LEFT JOIN tblstrands st
            ON sub.strand_id = st.strand_id
        LEFT JOIN tblusers u
            ON u.id = ts.teacher_id
        WHERE ts.teacher_subject_id IN ($placeholders)
        ORDER BY ts.created_at DESC
    ");
    $stmt->bind_param($types, ...$enrolledTSIds);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $subject_id = $row['subject_id'];
        $teacher_subject_id = $row['teacher_subject_id'];
        $progress = 0;
        $status = "Not Started";

        $coursesData[] = [
            "id" => (int)$teacher_subject_id,
            "title" => htmlspecialchars($row['subject']),
            "icon" => getSubjectIcon($row['subject']),
            "category" => "Subject",
            "bannerClass" => getBannerClass($row['subject']),
            "badgeClass" => getBadgeClass($row['subject']),
            "track" => htmlspecialchars($row['track'] ?? ''),
            "strand" => htmlspecialchars($row['strand'] ?? ''),
            "createdDisplay" => !empty($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : '—',

            "instructor" => [
                "name" => htmlspecialchars(trim(($row['teacher_firstname'] ?? '') . ' ' . ($row['teacher_lastname'] ?? '')) ?: 'Unknown Teacher'),
                "initials" => strtoupper(substr($row['teacher_firstname'] ?? 'T', 0, 1) . substr($row['teacher_lastname'] ?? '', 0, 1)),
                "color" => "linear-gradient(135deg,var(--cyan),var(--accent))"
            ],

            "progress" => $progress,
            "totalLessons" => (int)$row['lesson_count'],
            "completedLessons" => 0,

            "duration" => "N/A",
            "students" => (int)$row['student_count'],
            "rating" => 0,

            "lastAccessed" => formatTimeAgo($row['created_at']),
            "status" => $status,
            "description" => !empty($row['subject_description']) ? htmlspecialchars($row['subject_description']) : "Subject handled by your assigned teacher.",

            "lessons" => (function() use ($conn, $teacher_subject_id) {
                $ls = $conn->prepare("
                    SELECT tl.lesson_id, tl.title, tl.description, tl.duration, tl.status, tl.resource_type,
                           tl.video_path, tl.video_filename, tl.manual_content,
                           (SELECT COUNT(*) FROM tblquizzes q WHERE q.lesson_id = tl.lesson_id AND q.status = 'published') AS quiz_count
                    FROM tbl_teacher_lessons tl
                    WHERE tl.teacher_subject_id = ? AND tl.status = 'published'
                    ORDER BY tl.order_index ASC, tl.created_at ASC
                ");
                $ls->bind_param("i", $teacher_subject_id);
                $ls->execute();
                $lr = $ls->get_result();
                $lessons = [];
                while ($l = $lr->fetch_assoc()) {
                    $lessons[] = [
                        "id"            => (int)$l['lesson_id'],
                        "name"          => htmlspecialchars($l['title']),
                        "description"   => $l['description'] ?? '',
                        "manualContent" => $l['manual_content'] ?? '',
                        "duration"      => $l['duration'] ?? 'N/A',
                        "type"          => htmlspecialchars($l['resource_type'] ?? 'lesson'),
                        "resourceUrl"   => getLessonResourceUrl($l['video_path'] ?? '', $l['resource_type'] ?? ''),
                        "fileName"      => $l['video_filename'] ?? '',
                        "quizCount"     => (int)$l['quiz_count'],
                        "status"        => "current"
                    ];
                }
                $ls->close();
                return $lessons;
            })()
        ];
    }
    $stmt->close();
}

 $quizzes_by_lesson = [];
 $quiz_engine_data = [];
    if (!empty($enrolledTSIds)) {
        $placeholders2 = implode(',', array_fill(0, count($enrolledTSIds), '?'));
        $types2 = str_repeat('i', count($enrolledTSIds));
        $qstmt = $conn->prepare("
            SELECT q.quiz_id, q.lesson_id, q.description, q.due_date, q.status, q.passing_score,
                   q.duration, q.questions, q.question_count, l.teacher_subject_id
            FROM tblquizzes q
            INNER JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
            WHERE l.teacher_subject_id IN ($placeholders2) AND q.status = 'published'
            ORDER BY q.created_at DESC
        ");
        $qstmt->bind_param($types2, ...$enrolledTSIds);
        $qstmt->execute();
        $qres = $qstmt->get_result();
        while ($row = $qres->fetch_assoc()) {
            $lid = (int)$row['lesson_id'];
            $quiz_id = (int)$row['quiz_id'];
            $due_raw = $row['due_date'] ?? '';

            if (!isset($quizzes_by_lesson[$lid])) $quizzes_by_lesson[$lid] = [];
            $quizzes_by_lesson[$lid][] = [
                "id"           => $quiz_id,
                "title"        => htmlspecialchars($row['description'] ?? ''),
                "dueDate"      => !empty($due_raw) ? date('M d, Y', strtotime($due_raw)) : '—',
                "passingScore" => (int)$row['passing_score'],
                "isOverdue"    => (!empty($due_raw) && strtotime($due_raw) < time())
            ];

            $decodedQs = json_decode($row['questions'] ?? '[]', true);
            $q_count   = is_array($decodedQs) ? count($decodedQs) : (int)($row['question_count'] ?? 0);

            $ans_stmt = $conn->prepare("SELECT id, score, manual_score FROM tblquiz_results WHERE quizzes_id = ? AND student_id = ? ORDER BY score DESC, created_at DESC");
            $ans_stmt->bind_param("ii", $quiz_id, $student_id);
            $ans_stmt->execute();
            $ans_result   = $ans_stmt->get_result();
            $attempt_count = $ans_result->num_rows;
            $best_row      = $ans_result->fetch_assoc();
            $ans_stmt->close();

            $is_essay_quiz = false;
            if (!empty($decodedQs) && is_array($decodedQs)) {
                $is_essay_quiz = true;
                foreach ($decodedQs as $dq) {
                    if (($dq['type'] ?? '') !== 'essay') { $is_essay_quiz = false; break; }
                }
            }

            $is_expired    = !empty($due_raw) && strtotime($due_raw) < time();
            $student_score = $attempt_count > 0 ? (int)$best_row['score'] : null;
            $manual_score_val = ($attempt_count > 0 && $best_row['manual_score'] !== null) ? (int)$best_row['manual_score'] : null;
            $max_attempts  = 3;
            $quiz_status   = 'upcoming';
            if ($attempt_count > 0) {
                $quiz_status = ($attempt_count >= $max_attempts || $is_expired) ? 'completed' : 'upcoming';
            } elseif ($is_expired) {
                $quiz_status = 'locked';
            }

            $quiz_engine_data[$quiz_id] = [
                "id"           => $quiz_id,
                "courseId"     => (int)$row['teacher_subject_id'],
                "name"         => htmlspecialchars($row['description'] ?? 'Quiz'),
                "description"  => htmlspecialchars($row['description'] ?? ''),
                "status"       => $quiz_status,
                "score"        => $student_score,
                "passingScore" => (int)$row['passing_score'],
                "timeLimit"    => (int)$row['duration'],
                "questions"    => $q_count,
                "dueDate"      => !empty($due_raw) ? date('M d', strtotime($due_raw)) : '',
                "dueSort"      => !empty($due_raw) ? date('Y-m-d', strtotime($due_raw)) : '',
                "attempts"     => $attempt_count,
                "maxAttempts"  => $max_attempts,
                "isEssay"      => $is_essay_quiz,
                "manualScore"  => $manual_score_val,
            ];
        }
        $qstmt->close();
    }
    $quizzes_by_lesson_json = json_encode($quizzes_by_lesson);
    $quiz_engine_json = json_encode($quiz_engine_data);

    $categories = ["All"];
    foreach ($coursesData as $c) {
    if (!in_array($c['category'], $categories)) {
        $categories[] = $c['category'];
    }
}

$lesson_count = (int)$conn->query("
    SELECT COUNT(DISTINCT l.lesson_id)
    FROM tbl_teacher_lessons l
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = l.teacher_subject_id
    JOIN tbl_teachers_students tts ON tts.teacher_subject_id = ts.teacher_subject_id
    WHERE tts.student_id = $student_id AND l.status = 'published'
")->fetch_row()[0];

$quiz_count = (int)$conn->query("
    SELECT COUNT(DISTINCT q.quiz_id)
    FROM tblquizzes q
    JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = l.teacher_subject_id
    JOIN tblsubjects subj ON subj.subject_id = ts.subject_id
    JOIN tbl_teachers_students tts ON tts.teacher_id = ts.teacher_id
    WHERE tts.student_id = $student_id AND q.status = 'published'
")->fetch_row()[0];

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
        'new_lesson',
        'lesson_expiring',
        'lesson_expired',
        'new_quiz',
        'quiz_due_soon',
        'quiz_overdue',
        'new_subject_assigned'   => 'student_courses.php',
        'quiz_passed',
        'quiz_failed'            => 'student_grades.php',
        'new_announcement'       => 'index.php#announcements',
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
<link rel="stylesheet" href="../assets/css/student/student_courses.css">
<style>
  @media (max-width: 768px) {
  .filter-bar {
    flex-wrap: nowrap;
    gap: 8px;
  }

  .search-box {
    flex: 1;
    min-width: 0;
  }

  .search-box input {
    width: 100%;
    font-size: 12px;
    padding: 6px 8px 6px 28px;
  }

  .sort-select {
    flex-shrink: 0;
    width: auto;
    font-size: 12px;
    padding: 6px 8px;
  }
}

@media (min-width: 769px) {
  #courseModal {
    max-width: 1000px !important;
    width: 100vw !important;
    max-height: 85vh !important;
  }
  #courseModal .modal-body {
    padding: 8px 32px 32px !important;
  }
}
.tq-modal{max-width:700px;width:95vw;max-height:90vh;}
.tq-header{padding:18px 22px;border-bottom:1px solid rgba(255,255,255,.08)}
.tq-header-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.tq-subject-tag{font-size:11px;font-weight:700;color:var(--accent,#6366f1);text-transform:uppercase;letter-spacing:.03em;margin-bottom:4px}
.tq-title{font-size:16px;font-weight:700;color:var(--text,#f1f5f9)}
.tq-timer{font-size:14px;font-weight:700;color:var(--text,#f1f5f9);white-space:nowrap}
.tq-progress-bar{height:6px;border-radius:4px;background:rgba(255,255,255,.08);margin-top:14px;overflow:hidden}
.tq-progress-fill{height:100%;background:var(--accent,#6366f1);transition:width .2s}
.tq-progress-label{font-size:11px;color:var(--text3,#64748b);margin-top:6px}
.tq-questions{padding:18px 22px;display:flex;flex-direction:column;gap:22px;max-height:55vh;overflow-y:auto}
.tq-question-block{}
.tq-q-number{font-size:11px;font-weight:700;color:var(--accent,#6366f1);margin-bottom:6px}
.tq-q-text{font-size:14px;font-weight:600;color:var(--text,#f1f5f9);margin-bottom:10px}
.tq-choices{display:flex;flex-direction:column;gap:8px}
.tq-choice{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;border:1.5px solid rgba(255,255,255,.1);cursor:pointer;transition:background .15s,border-color .15s}
.tq-choice:hover{background:rgba(99,102,241,.06)}
.tq-choice.selected{border-color:var(--accent,#6366f1);background:rgba(99,102,241,.1)}
.tq-choice-letter{width:24px;height:24px;border-radius:50%;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}
.tq-choice-text{font-size:13px;color:var(--text,#f1f5f9)}
.tq-footer{padding:14px 22px;border-top:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px}
.tq-result{text-align:center;padding:50px 24px}
.tq-result-icon{font-size:48px;margin-bottom:12px}
.tq-result-score{font-size:2.2rem;font-weight:800}
.tq-result-grade{font-size:1rem;font-weight:600;margin-top:4px}
.tq-result-detail{font-size:13px;color:var(--text3,#64748b);margin-top:6px}
.tq-result-actions{display:flex;justify-content:center;gap:10px;margin-top:24px}
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
    <div class="nav-section-label">Overview</div>
    <a class="nav-item" href="index.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <div class="nav-section-label">Learning</div>
    <a class="nav-item active" href="student_courses.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      My Subjects
      <span class="nav-badge"><?php echo count($coursesData); ?></span>
    </a>
    <a class="nav-item" href="student_performance_tasks.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M9 2H7a2 2 0 00-2 2v16a2 2 0 002 2h10a2 2 0 002-2V4a2 2 0 00-2-2h-2"/>
        <rect x="9" y="2" width="6" height="4" rx="1" ry="1"/>
        <path d="M9 14l2 2 4-4"/>
      </svg>
      Performance Tasks
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
    <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="3"/>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 
        1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 
        1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06 
        a1.65 1.65 0 0 0 .33-1.82 
        1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09 
        a1.65 1.65 0 0 0 1.51-1 
        1.65 1.65 0 0 0-.33-1.82l-.06-.06 
        a2 2 0 1 1 2.83-2.83l.06.06 
        a1.65 1.65 0 0 0 1.82.33h0 
        A1.65 1.65 0 0 0 9 3.09V3a2 2 0 1 1 4 0v.09 
        a1.65 1.65 0 0 0 1 1.51h0 
        a1.65 1.65 0 0 0 1.82-.33l.06-.06 
        a2 2 0 1 1 2.83 2.83l-.06.06 
        a1.65 1.65 0 0 0-.33 1.82v0 
        a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09 
        a1.65 1.65 0 0 0-1.51 1z"/>
    </svg>
    Settings
    </a>
    <a class="nav-item" href="about.php">
    <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="16" x2="12" y2="12"/>
        <line x1="12" y1="8" x2="12.01" y2="8"/>
    </svg>
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
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--cyan),var(--accent))"><?php echo $student_avatar_html; ?></div>
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
    <!-- Page Header -->
    <div class="page-header">
      <div class="page-header-left">
        <div class="section-title">My Courses</div>
        <div class="section-sub">Track your enrolled courses and continue learning where you left off.</div>
      </div>
    </div>

    <!-- Search & Filter -->
    <div class="filter-bar">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInput" placeholder="Search courses..." oninput="filterCourses()">
      </div>
      <select class="sort-select" id="sortSelect" onchange="filterCourses()">
        <option value="default">Sort by: Default</option>
        <option value="progress-asc">Progress: Low → High</option>
        <option value="progress-desc">Progress: High → Low</option>
        <option value="name-asc">Name: A → Z</option>
        <option value="name-desc">Name: Z → A</option>
        <option value="recent">Recently Accessed</option>
      </select>
    </div>

    <!-- Category Tabs -->
    <div class="cat-tabs" id="catTabs"></div>

    <!-- Courses Grid -->
    <div class="courses-grid" id="coursesGrid"></div>
  </div>
</main>
</div>

<!-- Course Detail Modal -->
<div class="modal-backdrop" id="modalBackdrop" onclick="closeModalOutside(event)">
  <div class="modal" id="courseModal">
    <div style="display:flex;justify-content:flex-end;padding:10px 14px 0;">
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body" id="modalBody"></div>
  </div>
</div>

<!-- Lesson Detail Modal -->
<div class="modal-backdrop" id="lessonModalBackdrop" onclick="closeLessonModalOutside(event)">
  <div class="modal" id="lessonDetailModal" style="max-width:800px;width:95vw;max-height:90vh;display:flex;flex-direction:column;">
    <div style="padding:18px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.08);flex-shrink:0;">
      <div id="lessonModal-title" style="font-size:15px;font-weight:700;color:var(--text,#f1f5f9);">📖 Lesson</div>
      <button class="modal-close" onclick="closeLessonDetailModal()">✕</button>
    </div>
    <div style="overflow-y:auto;flex:1;padding:18px 20px;">
      <div id="lessonModal-info" style="margin-bottom:16px;padding:12px;border-radius:10px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);font-size:13px;color:var(--text2,#cbd5e1);line-height:1.8;"></div>
      <div style="font-size:13px;font-weight:700;color:var(--text,#f1f5f9);margin-bottom:8px;">📝 Quizzes</div>
      <div id="lessonModal-quizzes" style="display:flex;flex-direction:column;gap:8px;"></div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid rgba(255,255,255,0.08);flex-shrink:0;text-align:right;">
      <button class="btn btn-ghost" onclick="closeLessonDetailModal()">Close</button>
    </div>
  </div>
</div>
<!-- Take Quiz Modal -->
<div class="modal-backdrop" id="takeQuizBackdrop">
  <div class="modal tq-modal" id="takeQuizModal">
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
<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>
<!-- Logout Modal -->
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

var courses = <?php echo json_encode($coursesData); ?>;
var quizzesByLesson = <?php echo $quizzes_by_lesson_json ?: '{}'; ?>;
var quizEngineData = <?php echo $quiz_engine_json ?: '{}'; ?>;

function getQuizById(id){ return quizEngineData[id] || null; }
function getCourseMetaById(id){
  return courses.find(function(c){ return c.id === id; }) || {icon:'📚', title:'Subject', bannerClass:''};
}
var activeCategory = "All";
var categories = <?php echo json_encode($categories); ?>;

function renderCatTabs(){
  var el = document.getElementById('catTabs');
  el.innerHTML = categories.map(function(cat){
    var count = cat === "All" ? courses.length : courses.filter(function(c){return c.category===cat}).length;
    return '<button class="cat-tab'+(activeCategory===cat?' active':'')+'" onclick="setCategory(\''+cat+'\')">'+cat+' ('+count+')</button>';
  }).join('');
}

function isTimerExpiredForQuiz(id){
  var q = getQuizById(id);
  if(q && q.attempts === 0){
    sessionStorage.removeItem('quiz_time_expired_' + id);
    sessionStorage.removeItem('quiz_timer_' + id);
    return false;
  }
  var flag = sessionStorage.getItem('quiz_time_expired_' + id);
  if(flag === '1') return true;
  var saved = sessionStorage.getItem('quiz_timer_' + id);
  return saved !== null && parseInt(saved) <= 0;
}

function startQuiz(id, fromLessonId){
  var q = getQuizById(id);
  if(!q) return;
  _returnToLessonId = fromLessonId || null;
  var saved = sessionStorage.getItem('quiz_timer_' + id);
  if (saved !== null && parseInt(saved) <= 0) {
    showToast('Your time for this quiz has run out. No more attempts allowed.', 'error');
    return;
  }
  document.getElementById('takeQuizBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
  document.getElementById('takeQuizContent').innerHTML =
    '<div style="text-align:center;padding:60px 20px;color:var(--text2)">'+
    '<div style="font-size:2.5rem;margin-bottom:16px">⏳</div>'+
    '<div style="font-size:1.1rem;font-weight:600">Loading quiz...</div></div>';

  fetch('student_courses.php?ajax=get_quiz&quiz_id='+id)
    .then(function(r){ return r.text(); })
    .then(function(text){
      var data;
      try { data = JSON.parse(text); }
      catch(e){
        console.error('Raw server response:', text);
        document.getElementById('takeQuizContent').innerHTML =
          '<div style="text-align:center;padding:60px 20px;color:var(--red)">'+
          '<div style="font-weight:600;margin-bottom:10px">Server error loading quiz.</div>'+
          '<pre style="text-align:left;font-size:11px;max-height:250px;overflow:auto;background:var(--bg2,#1e293b);padding:10px;border-radius:8px;color:var(--text2,#cbd5e1);white-space:pre-wrap;word-break:break-all">'+escHtmlS(text.substring(0,1500))+'</pre>'+
          '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
        return;
      }
      if(!data.success){
        document.getElementById('takeQuizContent').innerHTML =
          '<div style="text-align:center;padding:60px 20px;color:var(--red)">'+
          '<div style="font-weight:600;margin-bottom:8px">'+(data.message||'Quiz not available.')+'</div>'+
          '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
        return;
      }
      renderTakeQuiz(q, getCourseMetaById(q.courseId), data.questions);
    })
    .catch(function(){
      document.getElementById('takeQuizContent').innerHTML =
        '<div style="text-align:center;padding:60px 20px;color:var(--red)">Network error.'+
        '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
    });
}

var _quizAnswers = {};
var _quizTimerInt = null;
var _quizTimeLeft = 0;
var _currentQuizObj = null;
var _currentQs = [];
var _timerStorageKey = '';

function renderTakeQuiz(q, c, questions){
  _currentQuizObj = q;
  _currentQs = questions;
  _quizAnswers = {};
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
    var choices = qs.choices || [];
    if (choices.length > 0) {
      choices.forEach(function(ch, ci){
        var choiceLabel = String.fromCharCode(65+ci);
        html += '<label class="tq-choice" id="tqChoice_'+idx+'_'+ci+'" onclick="selectAnswer('+idx+',\''+escapeAttr(ch)+'\','+ci+','+choices.length+')">';
        html += '<span class="tq-choice-letter">'+choiceLabel+'</span>';
        html += '<span class="tq-choice-text">'+ch+'</span>';
        html += '</label>';
      });
    } else {
      html += '<input type="text" id="tqText_'+idx+'" placeholder="Type your answer here..." '+
              'oninput="selectTextAnswer('+idx+', this.value)" '+
              'style="width:100%;padding:10px 14px;border:1.5px solid var(--border,rgba(255,255,255,.1));border-radius:8px;background:rgba(255,255,255,.04);color:var(--text,#f1f5f9);font-size:0.95rem;box-sizing:border-box;">';
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
      if(_quizTimeLeft <= 60) timerBox.style.color = 'var(--red)';
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
  var total2 = _currentQs.length;
  var pct = Math.round(answered/total2*100);
  var fill = document.getElementById('tqProgressFill');
  var label = document.getElementById('tqProgressLabel');
  if(fill) fill.style.width = pct+'%';
  if(label) label.textContent = answered+' of '+total2+' answered';
}

var _returnToLessonId = null;

function closeTakeQuizModal(){
  clearInterval(_quizTimerInt);
  document.getElementById('takeQuizBackdrop').classList.remove('open');
  document.body.style.overflow = '';
  if(_returnToLessonId){
    var lid = _returnToLessonId;
    _returnToLessonId = null;
    setTimeout(function(){ openLessonDetailModal(lid); }, 100);
  }
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
  var total2 = _currentQs.length;
  var pct = Math.round(answered/total2*100);
  var fill = document.getElementById('tqProgressFill');
  var label = document.getElementById('tqProgressLabel');
  if(fill) fill.style.width = pct+'%';
  if(label) label.textContent = answered+' of '+total2+' answered';
}

function submitQuiz(){
  clearInterval(_quizTimerInt);
  document.getElementById('takeQuizContent').innerHTML =
    '<div style="text-align:center;padding:60px 20px;color:var(--text2)">'+
    '<div style="font-size:2.5rem;margin-bottom:16px">📤</div>'+
    '<div style="font-size:1.1rem;font-weight:600">Submitting your answers...</div></div>';

  fetch('student_courses.php?ajax=submit_quiz', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      quiz_id: _currentQuizObj.id,
      answers: _quizAnswers,
      time_used: (_currentQuizObj.timeLimit * 60) - _quizTimeLeft
    })
  })
  .then(function(r){ return r.text(); })
  .then(function(text){
    var data;
    try { data = JSON.parse(text); }
    catch(e){
      document.getElementById('takeQuizContent').innerHTML =
        '<div style="text-align:center;padding:60px 20px;color:var(--red)">Server error during submission.'+
        '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
      return;
    }
    if(data.success){
      sessionStorage.removeItem(_timerStorageKey);
      renderQuizResult(data);
    } else {
      document.getElementById('takeQuizContent').innerHTML =
        '<div style="text-align:center;padding:60px 20px;color:var(--red)">'+(data.message||'Submission failed.')+
        '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
    }
  })
  .catch(function(){
    document.getElementById('takeQuizContent').innerHTML =
      '<div style="text-align:center;padding:60px 20px;color:var(--red)">Network error during submission.'+
      '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
  });
}

function renderQuizResult(data){
  var score = data.score;
  var correct = data.correct;
  var total = data.total;
  var needsReview = data.needs_review || false;
  var rPct = total > 0 ? Math.round(correct/total*100) : 0;
  var passed = rPct >= _currentQuizObj.passingScore;
  var scoreColor = rPct>=90?'var(--green)':rPct>=80?'var(--accent)':rPct>=70?'var(--amber)':'var(--red)';
  var grade = rPct>=90?'Excellent':rPct>=80?'Good':rPct>=70?'Fair':'Needs Work';

  var q = getQuizById(_currentQuizObj.id);
  if(q){
    q.attempts++;
    if(q.score === null || score > q.score) q.score = score;
    if(q.attempts >= q.maxAttempts || rPct === 100) q.status = 'completed';
  }
  var attemptsLeft = q ? (q.maxAttempts - q.attempts) : 0;
  var timerExpired = isTimerExpiredForQuiz(_currentQuizObj.id) || _quizTimeLeft <= 0;
  var isPerfect = (rPct === 100);

  var resultIcon, resultTitle, resultSubtitle, scoreDisplay, actionBtn;

  if(needsReview){
    resultIcon = '📋';
    resultTitle = 'Submitted for Review';
    resultSubtitle = 'Your answers have been submitted. Your teacher will check your essay/identification answers and update your score.';
    scoreDisplay =
      '<div style="font-size:1rem;font-weight:600;color:var(--amber)">⏳ Awaiting Teacher Review</div>'+
      (correct > 0 ? '<div style="font-size:0.85rem;color:var(--text3);margin-top:4px;">Auto-graded: '+correct+' / '+total+' (multiple choice only)</div>' : '');
    actionBtn = '<button class="btn btn-ghost" onclick="closeTakeQuizModal()">✕ Close</button>';
  } else {
    resultIcon = passed ? '🎉' : '😔';
    resultTitle = grade + ' (' + rPct + '%) · ' + (passed ? '✅ Passed' : '❌ Failed');
    resultSubtitle = rPct + '% score';
    scoreDisplay = '<div class="tq-result-score" style="color:'+scoreColor+'">'+correct+'/'+total+'</div>'+
                   '<div class="tq-result-grade" style="color:'+scoreColor+'">'+resultTitle+'</div>'+
                   '<div class="tq-result-detail">'+resultSubtitle+'</div>';
    var retakeBtn = (!isPerfect && attemptsLeft > 0 && !timerExpired)
      ? '<button class="btn btn-primary" onclick="closeTakeQuizModal();setTimeout(function(){startQuiz('+_currentQuizObj.id+','+(_returnToLessonId||'null')+')},200)">🔄 Retake Quiz ('+attemptsLeft+' left)</button>'
      : '<button class="btn btn-primary" onclick="closeTakeQuizModal();setTimeout(function(){openReviewModal('+_currentQuizObj.id+','+(_returnToLessonId||'null')+')},200)">📋 View Answers</button>';
    actionBtn = '<button class="btn btn-ghost" onclick="closeTakeQuizModal()">✕ Close</button>' + retakeBtn;
  }

  if(needsReview){
    document.getElementById('takeQuizContent').innerHTML =
      '<div class="tq-result">'+
        '<div class="tq-result-icon">'+resultIcon+'</div>'+
        scoreDisplay+
        '<div class="tq-result-detail" style="color:var(--text3);margin-top:10px;font-size:12px;max-width:320px;margin-left:auto;margin-right:auto;line-height:1.6;">'+resultSubtitle+'</div>'+
        '<div class="tq-result-actions">'+actionBtn+'</div>'+
      '</div>';
  } else {
    document.getElementById('takeQuizContent').innerHTML =
      '<div class="tq-result">'+
        '<div class="tq-result-icon">'+resultIcon+'</div>'+
        scoreDisplay+
        '<div class="tq-result-actions">'+actionBtn+'</div>'+
      '</div>';
  }
}

function confirmAbandonQuiz(){
  if(confirm('Abandon this quiz? Your answers so far will be saved as a partial attempt.')){
    clearInterval(_quizTimerInt);
    if(!_currentQuizObj){ closeTakeQuizModal(); return; }
    var timeUsed = (_currentQuizObj.timeLimit * 60) - _quizTimeLeft;
    fetch('student_courses.php?ajax=submit_quiz', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ quiz_id: _currentQuizObj.id, answers: _quizAnswers, time_used: timeUsed })
    })
    .then(function(r){ return r.text(); })
    .then(function(text){
      try {
        var data = JSON.parse(text);
        if(data.success){
          sessionStorage.removeItem(_timerStorageKey);
          var q = getQuizById(_currentQuizObj.id);
          if(q){
            q.attempts++;
            if(q.score === null || data.score > q.score) q.score = data.score;
            if(q.attempts >= q.maxAttempts) q.status = 'completed';
          }
          showToast('Partial attempt saved. You can still retake the quiz.', 'info');
        } else {
          _returnToLessonId = null;
        }
      } catch(e){ _returnToLessonId = null; }
      closeTakeQuizModal();
    })
    .catch(function(){ _returnToLessonId = null; closeTakeQuizModal(); });
  }
}

function openReviewModal(id, fromLessonId){
  var q = getQuizById(id);
  if(!q) return;

  _returnToLessonId = fromLessonId || null;
  document.getElementById('takeQuizBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
  document.getElementById('takeQuizContent').innerHTML =
    '<div style="text-align:center;padding:60px 20px;color:var(--text2)">Loading review...</div>';

  fetch('student_courses.php?ajax=get_quiz_review&quiz_id='+id)
    .then(function(r){ return r.text(); })
    .then(function(text){
      var data;
      try { data = JSON.parse(text); }
      catch(e){
        document.getElementById('takeQuizContent').innerHTML =
          '<div style="text-align:center;padding:60px 20px;color:var(--red)">Error loading review.'+
          '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
        return;
      }
      if(!data.success){
        document.getElementById('takeQuizContent').innerHTML =
          '<div style="text-align:center;padding:60px 20px;color:var(--red)">'+(data.message||'Review not available.')+
          '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
        return;
      }
      var c = getCourseMetaById(q.courseId);
      var reviewPct = q.questions > 0 ? Math.round(data.best_score/q.questions*100) : 0;
      var scoreColor = reviewPct>=90?'var(--green)':reviewPct>=80?'var(--accent)':reviewPct>=70?'var(--amber)':'var(--red)';
      var manualScoreVal = (data.manual_score !== null && data.manual_score !== undefined) ? data.manual_score : q.manualScore;
      var html =
        '<div class="tq-header">'+
          '<div class="tq-header-top">'+
            '<div>'+
              '<div class="tq-subject-tag">'+c.icon+' '+c.title+'</div>'+
              '<div class="tq-title">'+q.name+' — Answer Review</div>'+
            '</div>'+
            '<div style="text-align:right">'+
            (q.isEssay
              ? '<div style="font-size:1.5rem;font-weight:800;color:var(--amber,#f59e0b)">Teacher Score: '+manualScoreVal+' pt'+(manualScoreVal==1?'':'s')+'</div>'
              : (data.attempts > 0
                  ? '<div style="font-size:1.5rem;font-weight:800;color:'+scoreColor+'">Best: '+data.best_score+'/'+q.questions+' ('+reviewPct+'%)</div>'
                  : '<div style="font-size:1rem;font-weight:600;color:var(--red)">Never attempted — showing correct answers</div>'))+
            (!q.isEssay ? '<div style="font-size:0.78rem;color:var(--text3)">'+data.attempts+'/3 attempts used</div>' : '')+
            '</div>'+
          '</div>'+
        '</div>'+
        '<div class="tq-questions">';

      data.questions.forEach(function(qs, idx){
        var letters = ['A','B','C','D'];
        html += '<div class="tq-question-block" style="border-left:3px solid var(--accent);padding-left:12px">';
        html += '<div class="tq-q-number">Question '+(idx+1)+'</div>';
        html += '<div class="tq-q-text">'+qs.question+'</div>';
        html += '<div class="tq-choices">';
        if(qs.choices && qs.choices.length > 0){
          qs.choices.forEach(function(ch, ci){
            var isCorrect = ch === qs.correct_text;
            var style = isCorrect ? 'background:rgba(34,197,94,0.15);border-color:var(--green);' : 'opacity:0.6;';
            html += '<div class="tq-choice" style="pointer-events:none;'+style+'">'+
              '<span class="tq-choice-letter" style="'+(isCorrect?'background:var(--green);color:#fff;':'')+'">'+letters[ci]+'</span>'+
              '<span class="tq-choice-text">'+ch+'</span>'+
              (isCorrect ? '<span style="margin-left:auto;color:var(--green);font-weight:700">✓ Correct</span>' : '')+
            '</div>';
          });
        } else if(qs.type === 'essay'){
          var stuAnsRev = (data.student_answers && data.student_answers[idx]) ? data.student_answers[idx] : '(No answer submitted)';
          html += '<div style="padding:10px 14px;border-radius:8px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);color:var(--text,#f1f5f9);white-space:pre-wrap;margin-bottom:6px;">'+escHtmlS(stuAnsRev)+'</div>'+
            '<div style="font-size:11px;color:var(--amber,#f59e0b);">⭐ Manually graded by your teacher — see total score above.</div>';
        } else {
          html += '<div style="padding:10px 14px;border:1.5px solid var(--green);border-radius:8px;background:rgba(34,197,94,0.1);color:var(--green);font-weight:600">✓ '+qs.correct_text+'</div>';
        }
        html += '</div></div>';
      });

      html += '</div>';
      html += '<div class="tq-footer"><button class="btn btn-ghost" onclick="closeTakeQuizModal()">✕ Close</button></div>';
      document.getElementById('takeQuizContent').innerHTML = html;
    })
    .catch(function(){
      document.getElementById('takeQuizContent').innerHTML =
        '<div style="text-align:center;padding:60px 20px;color:var(--red)">Error loading review.'+
        '<button class="btn btn-ghost" style="margin-top:16px" onclick="closeTakeQuizModal()">Close</button></div>';
    });
}

function setCategory(cat){
  activeCategory = cat;
  renderCatTabs();
  filterCourses();
}

function filterCourses(){
  var query = document.getElementById('searchInput').value.toLowerCase();
  var sort = document.getElementById('sortSelect').value;
  var filtered = courses.filter(function(c){
    var matchCat = activeCategory === "All" || c.category === activeCategory;
    var matchSearch = c.title.toLowerCase().indexOf(query) !== -1 ||
                      c.category.toLowerCase().indexOf(query) !== -1 ||
                      c.instructor.name.toLowerCase().indexOf(query) !== -1;
    return matchCat && matchSearch;
  });
  switch(sort){
    case 'progress-asc': filtered.sort(function(a,b){return a.progress-b.progress}); break;
    case 'progress-desc': filtered.sort(function(a,b){return b.progress-a.progress}); break;
    case 'name-asc': filtered.sort(function(a,b){return a.title.localeCompare(b.title)}); break;
    case 'name-desc': filtered.sort(function(a,b){return b.title.localeCompare(a.title)}); break;
    case 'recent': filtered.sort(function(a,b){return a.lastAccessed.localeCompare(b.lastAccessed)}); break;
  }
  renderCourses(filtered);
}

function renderCourses(list){
  var el = document.getElementById('coursesGrid');
  if(!list.length){
    el.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">🔍</div><p>No courses found</p><span>Try adjusting your search or filters.</span></div>';
    return;
  }
  el.innerHTML = list.map(function(c){
    var bannerText = '';
    if (c.track) bannerText += c.track;
    if (c.strand) bannerText += (bannerText ? ' · ' : '') + c.strand;

    var quizCountTotal = 0;
    (c.lessons || []).forEach(function(l){ quizCountTotal += (l.quizCount || 0); });

    return '<div class="course-card" onclick="openModal('+c.id+')" style="cursor:pointer;overflow:hidden;">'+
      '<div class="course-card-banner '+c.bannerClass+'" style="position:relative;display:flex;flex-direction:column;justify-content:flex-end;align-items:flex-start;min-height:90px;padding:10px 12px;text-align:left;">'+
        '<div class="banner-overlay"></div>'+
        '<div style="position:absolute;top:8px;left:8px;z-index:2;">'+
          '<span style="background:rgba(34,197,94,0.18);color:#22c55e;font-size:10px;font-weight:700;padding:3px 9px;border-radius:6px;letter-spacing:.03em;">ACTIVE</span>'+
        '</div>'+
        '<div style="position:relative;z-index:1;margin-top:18px;width:100%;text-align:left;">'+
          (bannerText ? '<div class="banner-cat" style="font-size:10px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;opacity:.85;margin-bottom:3px;overflow-wrap:break-word;text-align:left;">'+bannerText+'</div>' : '')+
          '<div class="banner-title" style="font-size:14px;font-weight:700;line-height:1.3;overflow-wrap:break-word;text-align:left;">'+c.title+'</div>'+
        '</div>'+
      '</div>'+
      '<div class="course-card-body">'+
        '<div class="course-card-meta" style="margin-top:10px;">'+
          '<span class="meta-item"><span class="meta-icon">👥</span>'+c.students+' students</span>'+
          '<span class="meta-item"><span class="meta-icon">📖</span>'+c.totalLessons+' lessons</span>'+
          '<span class="meta-item"><span class="meta-icon">📝</span>'+quizCountTotal+' quizzes</span>'+
        '</div>'+
      '</div>'+
      '<div class="course-card-footer" style="min-height:auto;height:auto;padding:8px 14px;display:flex;align-items:center;overflow:visible;">'+
        '<div style="font-size:11px;color:var(--text3,#64748b);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Created '+c.createdDisplay+'</div>'+
      '</div>'+
    '</div>';
  }).join('');
}

function escHtmlS(str){
  return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function openLessonDetailModal(lessonId){
  var lesson = null;
  for (var i=0;i<courses.length;i++){
    var found = courses[i].lessons.find(function(l){return l.id===lessonId});
    if (found){ lesson = found; break; }
  }
  if(!lesson) return;

  document.getElementById('lessonModal-title').textContent = '📖 ' + lesson.name;

  var infoHtml = '';
  if (lesson.description && lesson.description.trim() !== '') {
    infoHtml += '<div style="font-size:11px;font-weight:700;color:var(--text2,#cbd5e1);margin-bottom:4px;">📝 Description</div>';
    infoHtml += '<div style="margin-bottom:10px;padding:8px 10px;border-radius:8px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);font-size:12px;white-space:pre-wrap;">' + escHtmlS(lesson.description) + '</div>';
  }
  if (lesson.manualContent && lesson.manualContent.trim() !== '') {
    infoHtml += '<div style="font-size:11px;font-weight:700;color:var(--text2,#cbd5e1);margin-bottom:4px;">📘 Lesson Content</div>';
    infoHtml += '<div style="margin-bottom:10px;padding:8px 10px;border-radius:8px;background:rgba(99,102,241,0.06);border:1px solid rgba(99,102,241,0.12);font-size:12px;white-space:pre-wrap;">' + escHtmlS(lesson.manualContent) + '</div>';
  }
  if (lesson.type === 'video' && lesson.resourceUrl) {
    infoHtml += '<video controls style="width:100%;max-height:240px;border-radius:8px;margin-top:6px;background:#000;"><source src="' + escHtmlS(lesson.resourceUrl) + '"></video>';
  } else if ((lesson.type === 'pdf' || lesson.type === 'ppt') && lesson.resourceUrl) {
    infoHtml += '<a href="' + escHtmlS(lesson.resourceUrl) + '" target="_blank" rel="noopener" style="display:inline-block;margin-top:6px;font-size:12px;color:var(--accent,#6366f1);text-decoration:underline;">📎 ' + escHtmlS(lesson.fileName || 'Open File') + '</a>';
  }
  document.getElementById('lessonModal-info').innerHTML = infoHtml || '<div style="color:var(--text3,#64748b);">No additional content for this lesson.</div>';

  var quizzes = quizzesByLesson[lessonId] || [];
  var qEl = document.getElementById('lessonModal-quizzes');
  if (quizzes.length === 0) {
    qEl.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text3,#64748b);font-size:13px;">No quizzes for this lesson yet.</div>';
  } else {
    qEl.innerHTML = quizzes.map(function(q, idx){
      var overdueBadge = q.isOverdue ? '<span style="font-size:10px;padding:2px 7px;border-radius:5px;background:rgba(239,68,68,0.15);color:#ef4444;margin-left:4px;">⚠ Overdue</span>' : '';
      var qData = quizEngineData[q.id];
      var attempts = qData ? qData.attempts : 0;
      var maxAttempts = qData ? qData.maxAttempts : 3;
      var isExpired = q.isOverdue;

      var attemptsBadge = (qData && qData.isEssay) ? '' : '<span style="font-size:10px;padding:2px 7px;border-radius:5px;background:rgba(99,102,241,0.15);color:var(--accent,#6366f1);margin-left:4px;">'+attempts+'/'+maxAttempts+' attempts</span>';

      var actionBtn = '';
      if(qData && qData.isEssay && attempts > 0){
        if(qData.manualScore === null || qData.manualScore === undefined){
          actionBtn = '<button class="btn btn-ghost btn-sm" style="opacity:0.6;cursor:not-allowed;flex-shrink:0;" disabled>⏳ Waiting for Score</button>';
        } else {
          actionBtn = '<button class="btn btn-primary btn-sm" style="flex-shrink:0;" onclick="closeLessonDetailModal();openReviewModal('+q.id+','+lessonId+')">📋 View Score</button>';
        }
      } else if(isExpired && attempts === 0){
        actionBtn = '<button class="btn btn-ghost btn-sm" style="opacity:0.5;cursor:not-allowed;flex-shrink:0;" disabled>🔒 Locked</button>';
      } else if(attempts >= maxAttempts){
        actionBtn = '<button class="btn btn-primary btn-sm" style="flex-shrink:0;" onclick="closeLessonDetailModal();openReviewModal('+q.id+','+lessonId+')">📋 View Answers</button>';
      } else if(attempts > 0){
        actionBtn = '<button class="btn btn-primary btn-sm" style="flex-shrink:0;" onclick="closeLessonDetailModal();startQuiz('+q.id+','+lessonId+')">🔄 Retake ('+attempts+'/'+maxAttempts+')</button>';
      } else {
        actionBtn = '<button class="btn btn-primary btn-sm" style="flex-shrink:0;" onclick="closeLessonDetailModal();startQuiz('+q.id+','+lessonId+')">▶ Take Quiz</button>';
      }
      return '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:10px;background:rgba(255,255,255,.03);border:1px solid '+(q.isOverdue?'rgba(239,68,68,0.25)':'rgba(255,255,255,.07)')+';">'+
        '<div style="min-width:0;flex:1;">'+
          '<div style="font-size:13px;font-weight:600;color:var(--text,#f1f5f9);margin-bottom:3px;">Quiz '+(idx+1)+': '+escHtmlS(q.title)+overdueBadge+attemptsBadge+'</div>'+
          '<div style="font-size:11px;color:var(--text3,#64748b);">📅 Due: '+escHtmlS(q.dueDate)+' · 🎯 Pass: '+q.passingScore+'%</div>'+
        '</div>'+
        '<div style="display:flex;align-items:center;flex-shrink:0;">'+actionBtn+'</div>'+
      '</div>';
    }).join('');
  }

  document.getElementById('lessonModalBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeLessonDetailModal(){
  document.getElementById('lessonModalBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}
function closeLessonModalOutside(e){
  if(e.target === document.getElementById('lessonModalBackdrop')) closeLessonDetailModal();
}

function openModal(id){
  var c = courses.find(function(x){return x.id===id});
  if(!c) return;
  var lessonsHTML = c.lessons.map(function(l, idx){
    var typeIcon = l.type === 'video' ? '🎬' : l.type === 'pdf' ? '📄' : l.type === 'image' ? '🖼️' : l.type === 'link' ? '🔗' : '📝';
    var durationText = l.duration && l.duration !== 'N/A' ? ' · ' + l.duration : '';
    var quizText = l.quizCount > 0 ? ' · 📝 ' + l.quizCount + ' quiz' + (l.quizCount > 1 ? 'zes' : '') : '';
    return '<div onclick="openLessonDetailModal('+l.id+')" style="cursor:pointer;display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);margin-bottom:8px;transition:background .15s;" onmouseover="this.style.background=\'rgba(99,102,241,0.08)\'" onmouseout="this.style.background=\'rgba(255,255,255,.03)\'">'+
      '<div style="width:30px;height:30px;border-radius:8px;background:rgba(99,102,241,0.12);color:var(--accent,#6366f1);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">'+(idx+1)+'</div>'+
      '<div style="flex:1;min-width:0;">'+
        '<div style="font-size:13px;font-weight:600;color:var(--text,#f1f5f9);margin-bottom:2px;">'+l.name+'</div>'+
        '<div style="font-size:11px;color:var(--text3,#64748b);">'+typeIcon+' '+l.type+durationText+quizText+'</div>'+
      '</div>'+
      '<div style="font-size:13px;color:var(--text3,#64748b);flex-shrink:0;">→</div>'+
    '</div>';
  }).join('');
  var modalBannerText = '';
  if (c.track) modalBannerText += c.track;
  if (c.strand) modalBannerText += (modalBannerText ? ' · ' : '') + c.strand;

  document.getElementById('modalBody').innerHTML =
    (modalBannerText ? '<div style="font-size:11px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:var(--text3,#64748b);margin-bottom:4px;">'+modalBannerText+'</div>' : '')+
    '<div class="modal-title">'+c.title+'</div>'+
    '<div class="modal-meta">'+
      '<span class="modal-meta-item"><span class="instructor-avatar" style="background:'+c.instructor.color+';width:20px;height:20px;font-size:8px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;color:#fff">'+c.instructor.initials+'</span> '+c.instructor.name+'</span>'+
      '<span class="modal-meta-item">📚 '+c.totalLessons+' lessons</span>'+
      '<span class="modal-meta-item">👥 '+c.students+' students</span>'+
    '</div>'+
    '<div class="modal-desc" style="text-indent:24px;text-align:justify;">'+c.description+'</div>'+
    '<div class="modal-lessons-title">📋 Lesson List</div>'+
    lessonsHTML+
    '<div class="modal-actions">'+
      '<button class="btn btn-ghost" onclick="closeModal()">Close</button>'+
    '</div>';
  document.getElementById('modalBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal(){
  document.getElementById('modalBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}
function closeModalOutside(e){
  if(e.target === document.getElementById('modalBackdrop')) closeModal();
}
document.addEventListener('keydown', function(e){
  if(e.key === 'Escape') closeModal();
});

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

function showToast(msg, type){
  type = type || 'success';
  var icons = {success:'✅',error:'❌',info:'ℹ️'};
  var container = document.getElementById('toast-container');
  var t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = '<span class="toast-icon">'+(icons[type]||'✅')+'</span><span>'+msg+'</span>';
  container.appendChild(t);
  setTimeout(function(){
    t.style.opacity='0';t.style.transform='translateX(60px)';t.style.transition='all .3s ease';
    setTimeout(function(){t.remove()},300);
  },3000);
}

renderCatTabs();
filterCourses();

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