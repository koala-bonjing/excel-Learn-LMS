<?php
session_start();
include '../connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

 $teacher_id = $_SESSION['user_id'];

function formatTimeAgo($datetime_str) {
    if (empty($datetime_str)) return 'unknown';
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

function buildUrl($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    $base = basename(__FILE__);
    return $base . (!empty($params) ? '?' . http_build_query($params) : '');
}

function getQuizIcon($strand) {
    if (stripos($strand, 'stem') !== false) return '🔬';
    if (stripos($strand, 'abm') !== false) return '💼';
    if (stripos($strand, 'humss') !== false) return '🎭';
    if (stripos($strand, 'gas') !== false) return '🌏';
    if (stripos($strand, 'tvl') !== false) return '🔧';
    return '📝';
}

function formatDueDate($datetime_str) {
    if (empty($datetime_str)) return 'TBD';
    return date('M d, Y', strtotime($datetime_str));
}

function formatGrade($grade) {
    if (empty($grade)) return '';
    if (stripos($grade, 'Grade') !== false) return htmlspecialchars($grade);
    return 'G' . htmlspecialchars($grade);
}
 $stmt = $conn->prepare("SELECT id, profile_pic, firstname, middlename, lastname, role FROM tblusers WHERE id = ?");
 $stmt->bind_param("i", $teacher_id);
 $stmt->execute();
 $user = $stmt->get_result()->fetch_assoc();
 $stmt->close();

if (!$user) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

 $firstname    = htmlspecialchars($user['firstname'] ?? '');
 $middlename   = htmlspecialchars($user['middlename'] ?? '');
 $lastname     = htmlspecialchars($user['lastname'] ?? '');
 $display_name = trim("$firstname " . ($middlename ? "$middlename " : '') . "$lastname");
 $initials     = strtoupper(substr($user['firstname'] ?? '', 0, 1) . substr($user['lastname'] ?? '', 0, 1));
if (strlen(trim($initials)) < 2) $initials = 'U';
 $role_display = htmlspecialchars(ucfirst($user['role'] ?? 'Teacher'));

 $profile_pic_path = '';
 $profile_pic_db   = $user['profile_pic'] ?? '';
if (!empty($profile_pic_db)) {
    foreach (['../uploads/profile_pic/teachers/', '../uploads/profile_pic/', '../'] as $dir) {
        $p = $dir . $profile_pic_db;
        if (file_exists($p)) { $profile_pic_path = $p; break; }
    }
}
 $sidebar_avatar_html = '';
if (!empty($profile_pic_path)) {
    $sidebar_avatar_html = '<img src="' . htmlspecialchars($profile_pic_path) . '" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">';
} else {
    $sidebar_avatar_html = htmlspecialchars($initials);
}

 $teacher_lessons = [];
$lres = $conn->prepare("
    SELECT 
        l.lesson_id,
        l.title,
        l.teacher_subject_id,
        l.description,
        l.duration
    FROM tbl_teacher_lessons l
    WHERE l.teacher_subject_id IN (
        SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?
    )
    AND l.lesson_id NOT IN (
        SELECT lesson_id FROM tblquizzes
    )
    ORDER BY l.title ASC
");

$lres->bind_param("i", $teacher_id);
$lres->execute();
$lres = $lres->get_result();
if ($lres) {
    while ($lr = $lres->fetch_assoc()) {
        $teacher_lessons[] = $lr;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quiz'])) {
    $lesson_id      = intval($_POST['lesson_id'] ?? 0);
    $description    = trim($_POST['description'] ?? '');
    $questions_raw  = isset($_POST['questions']) ? $_POST['questions'] : [];
    $status         = $_POST['status'] ?? 'draft';
    $duration       = intval($_POST['duration'] ?? 30);
    $question_count = intval($_POST['question_count'] ?? 0);
    $passing_score  = intval($_POST['passing_score'] ?? 75);
    $due_date_raw   = trim($_POST['due_date'] ?? '');

    if (!in_array($status, ['draft', 'published', 'scheduled'])) $status = 'draft';
    if ($duration < 1) $duration = 30;
    if ($duration > 600) $duration = 600;
    if ($question_count < 1) $question_count = 0;
    if ($passing_score < 0) $passing_score = 0;
    if ($passing_score > 100) $passing_score = 100;

    $due_date_sql = null;
    if (!empty($due_date_raw)) {
        $parsed = date_create_from_format('Y-m-d\TH:i', $due_date_raw);
        if ($parsed) {
            $due_date_sql = $parsed->format('Y-m-d H:i:s');
        } else {
            $parsed2 = date_create($due_date_raw);
            if ($parsed2) $due_date_sql = $parsed2->format('Y-m-d H:i:s');
        }
    }

    $errors = [];
    if ($lesson_id <= 0) $errors[] = "Please select a lesson.";

    $lesson_valid = false;
    if ($lesson_id > 0) {
        $cv = $conn->prepare("
            SELECT l.lesson_id
            FROM tbl_teacher_lessons l
            WHERE l.lesson_id = ?
            AND l.teacher_subject_id IN (
                SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?
            )
        ");
        $cv->bind_param("ii", $lesson_id, $teacher_id);
        $cv->execute();
        $lesson_valid = $cv->get_result()->num_rows > 0;
        $cv->close();
        if (!$lesson_valid) $errors[] = "Invalid lesson selection.";
    }

    if ($status === 'scheduled' && empty($due_date_sql)) {
        $errors[] = "Scheduled quizzes must have a due date.";
    }

    $questions_json = "[]";
    if (!empty($questions_raw)) {
        $clean_questions = [];
        foreach ($questions_raw as $q) {
            $q_type = trim($q['type'] ?? 'multiple_choice');
            if (!in_array($q_type, ['multiple_choice', 'fill_in_the_blank', 'identification'])) {
                $q_type = 'multiple_choice';
            }
            $item = [
                'type' => $q_type,
                'text' => trim($q['text'] ?? ''),
                'correct' => trim($q['correct'] ?? '')
            ];
            if ($q_type === 'multiple_choice') {
                $item['options'] = [
                    'a' => trim($q['opt_a'] ?? ''),
                    'b' => trim($q['opt_b'] ?? ''),
                    'c' => trim($q['opt_c'] ?? ''),
                    'd' => trim($q['opt_d'] ?? '')
                ];
            }
            $clean_questions[] = $item;
        }
        $questions_json = json_encode($clean_questions, JSON_UNESCAPED_UNICODE);
        $question_count = count($clean_questions);
    }

    if (empty($errors)) {
        $ins = $conn->prepare("INSERT INTO tblquizzes (lesson_id, questions, description, duration, question_count, passing_score, due_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $ins->bind_param(
            "issiiiss",
            $lesson_id,
            $questions_json,
            $description,
            $duration,
            $question_count,
            $passing_score,
            $due_date_sql,
            $status
        );

        if ($ins->execute()) {
            $_SESSION['quiz_success'] = "Quiz created successfully!";
            $workerUrl = 'https://excelsiorpolytechnicacademy.com/excelearn/background_worker/background_worker.php?task=quizzes';
            $ch = curl_init($workerUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 1,
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_NOBODY         => false,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            @curl_exec($ch);
            curl_close($ch);
        } else {
            $_SESSION['quiz_error'] = "Error creating quiz: " . $ins->error;
        }
        $ins->close();
    } else {
        $_SESSION['quiz_error'] = implode(" ", $errors);
    }
    header("Location: " . buildUrl(['edit' => null]));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_quiz'])) {
    $quiz_id = intval($_POST['quiz_id'] ?? 0);
    if ($quiz_id > 0) {
        // Check ownership
        $own = $conn->prepare("
            SELECT q.quiz_id
            FROM tblquizzes q
            JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id
            WHERE q.quiz_id = ?
            AND l.teacher_subject_id IN (
                SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?
            )
        ");
        $own->bind_param("ii", $quiz_id, $teacher_id);
        $own->execute();
        $exists = $own->get_result()->num_rows > 0;
        $own->close();

        if ($exists) {
            // Block delete if students have already taken this quiz
            $chk = $conn->prepare("SELECT COUNT(*) AS cnt FROM tblquiz_results WHERE quizzes_id = ?");
            $chk->bind_param("i", $quiz_id);
            $chk->execute();
            $has_results = (int)$chk->get_result()->fetch_assoc()['cnt'] > 0;
            $chk->close();

            if ($has_results) {
                $_SESSION['quiz_error'] = "Cannot delete this quiz — students have already submitted results. You can unpublish it instead.";
            } else {
                $del = $conn->prepare("
                    DELETE q FROM tblquizzes q
                    JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id
                    WHERE q.quiz_id = ?
                    AND l.teacher_subject_id IN (
                        SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?
                    )
                ");
                $del->bind_param("ii", $quiz_id, $teacher_id);
                $del->execute();
                $del->close();
                $_SESSION['quiz_success'] = "Quiz deleted successfully.";
            }
        }
    }
    header("Location: " . buildUrl(['edit' => null]));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quiz_status'])) {
    $quiz_id    = intval($_POST['quiz_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    if ($quiz_id > 0 && in_array($new_status, ['draft', 'published', 'scheduled'])) {
        $own = $conn->prepare("
            SELECT q.quiz_id
            FROM tblquizzes q
            JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id
            WHERE q.quiz_id = ?
            AND l.teacher_subject_id IN (
                SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?
            )
        ");
        $own->bind_param("ii", $quiz_id, $teacher_id);
        $own->execute();
        $qrow = $own->get_result()->fetch_assoc();
        $own->close();

        if (!$qrow) {
            $_SESSION['quiz_error'] = "Quiz not found.";
            header("Location: " . buildUrl(['edit' => null]));
            exit();
        }

        if ($new_status === 'scheduled' && empty($qrow['due_date'])) {
            $_SESSION['quiz_error'] = "Cannot schedule a quiz without a due date. Please edit the quiz and set a due date first.";
            header("Location: " . buildUrl(['edit' => null]));
            exit();
        }

        $upd = $conn->prepare("UPDATE tblquizzes SET status = ?, updated_at = NOW() WHERE quiz_id = ?");
        $upd->bind_param("si", $new_status, $quiz_id);
        $upd->execute();
        $upd->close();
        $labels = ['published' => 'Active', 'draft' => 'Draft', 'scheduled' => 'Scheduled'];
        $_SESSION['quiz_success'] = "Quiz status changed to " . ($labels[$new_status] ?? $new_status) . ".";
    }
    header("Location: " . buildUrl(['edit' => null]));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quiz'])) {
    $quiz_id        = intval($_POST['quiz_id'] ?? 0);
    $description    = trim($_POST['edit_description'] ?? '');
    $duration       = intval($_POST['edit_duration'] ?? 30);
    $passing_score  = intval($_POST['edit_passing_score'] ?? 75);
    $due_date_raw   = trim($_POST['edit_due_date'] ?? '');

    if ($duration < 1) $duration = 30;
    if ($duration > 600) $duration = 600;
    if ($passing_score < 0) $passing_score = 0;
    if ($passing_score > 100) $passing_score = 100;

    $due_date_sql = null;
    if (!empty($due_date_raw)) {
        $parsed = date_create_from_format('Y-m-d\TH:i', $due_date_raw);
        if ($parsed) {
            $due_date_sql = $parsed->format('Y-m-d H:i:s');
        } else {
            $parsed2 = date_create($due_date_raw);
            if ($parsed2) $due_date_sql = $parsed2->format('Y-m-d H:i:s');
        }
    }

    $questions_raw = isset($_POST['questions']) ? $_POST['questions'] : [];
    $clean_questions = [];
    if (!empty($questions_raw)) {
        foreach ($questions_raw as $q) {
            $q_type = trim($q['type'] ?? 'multiple_choice');
            if (!in_array($q_type, ['multiple_choice', 'fill_in_the_blank', 'identification'])) {
                $q_type = 'multiple_choice';
            }
            $item = [
                'type' => $q_type,
                'text' => trim($q['text'] ?? ''),
                'correct' => trim($q['correct'] ?? '')
            ];
            if ($q_type === 'multiple_choice') {
                $item['options'] = [
                    'a' => trim($q['opt_a'] ?? ''),
                    'b' => trim($q['opt_b'] ?? ''),
                    'c' => trim($q['opt_c'] ?? ''),
                    'd' => trim($q['opt_d'] ?? '')
                ];
            }
            $clean_questions[] = $item;
        }
    }
    $questions_json = json_encode($clean_questions, JSON_UNESCAPED_UNICODE);
    $question_count = count($clean_questions);

    $errors = [];

    if ($quiz_id > 0) {
        $own = $conn->prepare("
            SELECT q.quiz_id
            FROM tblquizzes q
            JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id
            WHERE q.quiz_id = ?
            AND l.teacher_subject_id IN (
                SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?
            )
        ");
        $own->bind_param("ii", $quiz_id, $teacher_id);
        $own->execute();
        $is_own = $own->get_result()->num_rows > 0;
        $own->close();
        if (!$is_own) $errors[] = "Quiz not found.";
    }

    if (empty($errors) && $quiz_id > 0) {
        if ($due_date_sql === null) {
            $upd = $conn->prepare("UPDATE tblquizzes SET description = ?, questions = ?, duration = ?, question_count = ?, passing_score = ?, due_date = NULL, updated_at = NOW() WHERE quiz_id = ?");
            $upd->bind_param("ssiiii", $description, $questions_json, $duration, $question_count, $passing_score, $quiz_id);
        } else {
            $upd = $conn->prepare("UPDATE tblquizzes SET description = ?, questions = ?, duration = ?, question_count = ?, passing_score = ?, due_date = ?, updated_at = NOW() WHERE quiz_id = ?");
            $upd->bind_param("ssiiisi", $description, $questions_json, $duration, $question_count, $passing_score, $due_date_sql, $quiz_id);
        }
        if ($upd->execute()) {
            $_SESSION['quiz_success'] = "Quiz updated successfully!";
        } else {
            $_SESSION['quiz_error'] = "Error updating quiz: " . $upd->error;
        }
        $upd->close();
    } else {
        $_SESSION['quiz_error'] = implode(" ", $errors);
    }
    header("Location: " . buildUrl(['edit' => null]));
    exit();
}

 $success_msg = $_SESSION['quiz_success'] ?? '';
 $error_msg   = $_SESSION['quiz_error'] ?? '';
unset($_SESSION['quiz_success'], $_SESSION['quiz_error']);

 $current_filter = $_GET['filter'] ?? 'all';
if (!in_array($current_filter, ['all', 'published', 'draft', 'scheduled'])) $current_filter = 'all';

 $current_sort = $_GET['sort'] ?? 'newest';
if (!in_array($current_sort, ['newest', 'oldest', 'name-az', 'name-za'])) $current_sort = 'newest';

 $search_query = trim($_GET['search'] ?? '');

 $current_lesson_id = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;

 $edit_mode = isset($_GET['edit']);
 $editing_quiz = null;

if ($edit_mode) {
    $edit_id = intval($_GET['edit']);
    if ($edit_id > 0) {
      $estmt = $conn->prepare("
          SELECT q.*,
                l.title        AS lesson_title,
                l.description  AS lesson_description,
                l.teacher_subject_id
          FROM tblquizzes q
          JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id
          WHERE q.quiz_id = ?
          AND l.teacher_subject_id IN (
              SELECT teacher_subject_id
              FROM tbl_teacher_subjects
              WHERE teacher_id = ?
          )
      ");
      $estmt->bind_param("ii", $edit_id, $teacher_id);
      $estmt->execute();
      $editing_quiz = $estmt->get_result()->fetch_assoc();
      $estmt->close();
    }
    if (!$editing_quiz) $edit_mode = false;
}

 $edit_questions_js = '[]';
if ($editing_quiz) {
    $eq_decoded = json_decode($editing_quiz['questions'] ?? '[]', true);
    if (is_array($eq_decoded)) {
        $edit_questions_js = json_encode($eq_decoded, JSON_UNESCAPED_UNICODE);
    }
}

$sql = "
    SELECT
        q.quiz_id,
        q.lesson_id,
        q.questions,
        q.description,
        q.duration,
        q.question_count,
        q.passing_score,
        q.due_date,
        q.status,
        q.created_at,
        q.updated_at,
        l.title AS lesson_title,
        l.description AS lesson_description
    FROM tblquizzes q
    JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id
    WHERE l.teacher_subject_id IN (
        SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?
    )
";

 $params = [$teacher_id];
 $types  = "i";

if ($current_lesson_id > 0) {
    $sql .= " AND q.lesson_id = ?";
    $params[] = $current_lesson_id;
    $types .= "i";
}

if ($current_filter !== 'all') {
    $sql .= " AND q.status = ?";
    $params[] = $current_filter;
    $types .= "s";
}

if (!empty($search_query)) {
    $like = "%" . $search_query . "%";
    $sql .= " AND (l.title LIKE ? OR q.description LIKE ?)";
    $params = array_merge($params, [$like, $like]);
    $types .= "ss";
}

switch ($current_sort) {
    case 'newest':  $sql .= " ORDER BY q.created_at DESC"; break;
    case 'oldest':  $sql .= " ORDER BY q.created_at ASC"; break;
    case 'name-az': $sql .= " ORDER BY l.title ASC"; break;
    case 'name-za': $sql .= " ORDER BY l.title DESC"; break;
}

 $stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
 $stmt->execute();
 $result = $stmt->get_result();
 $quizzes = [];
while ($row = $result->fetch_assoc()) {
    $quizzes[] = $row;
}
 $result->free();
 $stmt->close();

 $total_quizzes   = 0;
 $published_count = 0;
 $draft_count     = 0;
 $scheduled_count = 0;
try {
    $cnt_sql = "
        SELECT q.status, COUNT(*) AS cnt
        FROM tblquizzes q
        JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id
        WHERE l.teacher_subject_id IN (
            SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = $teacher_id
        )
    ";
    if ($current_lesson_id > 0) $cnt_sql .= " AND q.lesson_id = $current_lesson_id";
    $cnt_sql .= " GROUP BY q.status";
    $cr = $conn->query($cnt_sql);
    if ($cr) {
        while ($crow = $cr->fetch_assoc()) {
            $total_quizzes += (int)$crow['cnt'];
            if ($crow['status'] === 'published') $published_count = (int)$crow['cnt'];
            if ($crow['status'] === 'draft')     $draft_count = (int)$crow['cnt'];
            if ($crow['status'] === 'scheduled') $scheduled_count = (int)$crow['cnt'];
        }
    }
} catch (Exception $e) {}

 $selected_lesson_name = 'All Lessons';
if ($current_lesson_id > 0) {
    foreach ($teacher_lessons as $tl) {
        if ((int)$tl['lesson_id'] === $current_lesson_id) {
            $selected_lesson_name = htmlspecialchars($tl['title'] ?? 'Unknown Lesson');
            if (!empty($tl['subject'])) $selected_lesson_name .= ' — ' . htmlspecialchars($tl['subject']);
            break;
        }
    }
}

$sql_lessons = "
    SELECT COUNT(*) AS total_lessons
    FROM tbl_teacher_lessons l
    INNER JOIN tbl_teacher_subjects s 
        ON l.teacher_subject_id = s.teacher_subject_id
    WHERE s.teacher_id = ?
";

$stmt = $conn->prepare($sql_lessons);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total_lessons_badge = $row['total_lessons'] ?? 0;
$stmt->close();

$sql_subjects = "
    SELECT COUNT(*) AS total_subjects
    FROM tbl_teacher_subjects
    WHERE teacher_id = ?
";

$stmt = $conn->prepare($sql_subjects);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total_subjects_badge = $row['total_subjects'] ?? 0;
$stmt->close();

$unique_students = (int)$conn->query("
    SELECT COUNT(DISTINCT student_id) FROM tbl_teachers_students
    WHERE teacher_subject_id IN (SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = $teacher_id)
")->fetch_row()[0];

// ── Notifications ──────────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT notification_id, title, message, type, status, created_at
    FROM tblnotifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 30
");
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unread_count = count(array_filter($notifications, fn($n) => $n['status'] === 'unread'));

// ── Notification URL mapper ────────────────────────────────────────────────
function getNotifUrl(string $type): string {
    return match($type) {
        'new_subject_assigned'   => 'teacher_courses.php',
        'new_lesson',
        'lesson_expiring',
        'lesson_expired'         => 'teacher_lessons.php',
        'new_quiz',
        'quiz_due_soon',
        'quiz_overdue'           => 'teacher_quizzes.php',
        'quiz_passed',
        'quiz_failed',
        'student_submitted_quiz' => 'teacher_grades.php',
        'new_announcement'       => 'index.php#announcements',
        default                  => '#',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn LMS — Teacher Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/teacher/teacher_quizzes.css">
<style>
.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
@media (max-width: 640px) {
  .toolbar {
    gap: 5px;
  }
  .lesson-filter-form {
    width: 100% !important;
  }
  .lesson-filter-form .sort-select {
    width: 100% !important;
    font-size: 12px !important;
    padding: 6px 8px !important;
    height: 34px;
    min-width: unset !important;
  }
  .search-wrap {
    flex: 1 !important;
    min-width: 0 !important;
    max-width: unset !important;
  }
  .search-wrap input[type="text"] {
    width: 100% !important;
    font-size: 12px !important;
    padding: 6px 8px 6px 28px !important;
    height: 34px;
  }
  .filter-tabs {
    flex: 0 0 auto;
  }
  .filter-tab {
    font-size: 11px !important;
    padding: 4px 7px !important;
    white-space: nowrap;
  }
  .sort-select {
    font-size: 12px !important;
    padding: 6px 6px !important;
    height: 34px;
    min-width: unset !important;
    width: auto !important;
  }
}
#logoutModal {
  align-items: center;
}
#notifModal {
  align-items: center;
}
@media (max-width: 768px) {
  .topbar {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 100 !important;
    width: 100% !important;
  }
  .main {
    padding-top: 60px !important;
  }
}
.flash-msg {
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 18px;border-radius:10px;margin-bottom:16px;
  font-size:0.9rem;font-weight:500;
  animation:flashIn .3s ease;
}
.flash-success { background:rgba(34,197,94,0.15);color:var(--green);border:1px solid rgba(34,197,94,0.3); }
.flash-error   { background:rgba(239,68,68,0.15);color:var(--red);border:1px solid rgba(239,68,68,0.3); }
.flash-warning { background:rgba(245,166,35,0.15);color:var(--amber);border:1px solid rgba(245,166,35,0.3); }
@keyframes flashIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }

.q-block {
    border: 1px solid var(--border, rgba(255,255,255,0.1));
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 8px;
    background: var(--card, rgba(255,255,255,0.04));
    position: relative;
    color: var(--text);
}
body.light .q-block {
    border-color: #e2e8f0;
    background: #f9fafb;
}
.q-block input, .q-block select, .q-block textarea {
    margin-bottom: 5px;
    width: 100%;
    box-sizing: border-box;
    background: var(--input-bg, rgba(255,255,255,0.06));
    color: var(--text);
    border: 1px solid var(--border, rgba(255,255,255,0.12));
    border-radius: 6px;
    padding: 7px 10px;
    font-size: 0.875rem;
}
body.light .q-block input,
body.light .q-block select,
body.light .q-block textarea {
    background: #fff;
    color: #1e293b;
    border-color: #e2e8f0;
}
.q-block input::placeholder,
.q-block textarea::placeholder {
    color: var(--text3, #94a3b8);
}
.q-label {
    font-weight: 600;
    margin-bottom: 5px;
    display: block;
    color: var(--text2, #94a3b8);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.q-remove-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: none;
    border: none;
    font-size: 1.1rem;
    cursor: pointer;
    opacity: 0.5;
    padding: 2px 6px;
    color: var(--text);
}
.q-remove-btn:hover {
    opacity: 1;
    color: var(--red, #ef4444);
}

/* Mobile responsiveness for modals */
@media (max-width: 640px) {
    .modal {
        margin: 0;
        border-radius: 16px 16px 0 0;
        max-height: 92dvh;
        width: 100%;
    }
    .modal-overlay {
        align-items: flex-end;
    }
    .modal-body {
        padding: 12px 14px;
        overflow-y: auto;
        max-height: calc(92dvh - 120px);
    }
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    .q-block {
        padding: 12px 12px 12px 12px;
    }
    .q-remove-btn {
        top: 8px;
        right: 6px;
    }
    .modal-footer {
        padding: 10px 14px;
        gap: 8px;
    }
    .modal-footer .btn {
        flex: 1;
        text-align: center;
        justify-content: center;
    }
}
@media (max-width: 768px) {
  .topbar-logo-desktop {
    display: none !important;
  }
  .topbar-logo-mobile {
    display: flex !important;
  }
}
</style>
</head>
<body>
<div class="layout">
<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
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
    <div class="nav-section-label" style="margin-top:14px">Teaching</div>
    <a class="nav-item" href="teacher_courses.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Subjects
      <span class="nav-badge"><?php echo $total_subjects_badge; ?></span>
    </a>
    <a class="nav-item" href="teacher_lessons.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Lessons
      <span class="nav-badge"><?php echo $total_lessons_badge; ?></span>
    </a>
    <a class="nav-item active" href="teacher_quizzes.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      Quizzes
      <span class="nav-badge" id="quiz-count-badge"><?php echo $total_quizzes; ?></span>
    </a>
    <?php
    $badge_virtualclass = (int)$conn->query("SELECT COUNT(*) FROM tblvirtualclasses WHERE teacher_id = $teacher_id AND status IN ('upcoming','live')")->fetch_row()[0];
    ?>
    <a class="nav-item" href="teacher_virtual_class.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
      Virtual Class
      <?php if ($badge_virtualclass > 0): ?><span class="nav-badge" style="background:var(--cyan,#06b6d4);color:#000"><?= $badge_virtualclass ?></span><?php endif; ?>
    </a>
    <div class="nav-section-label" style="margin-top:14px">Students & Grades</div>
    <a class="nav-item" href="teacher_students.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Students
      <?php if ($unique_students > 0): ?><span class="nav-badge" style="background:var(--green);color:#fff"><?= $unique_students ?></span><?php endif; ?>
    </a>
    <?php
    $badge_gradebook = (int)$conn->query("
        SELECT COUNT(DISTINCT r.student_id)
        FROM tblquiz_results r
        JOIN tblquizzes q ON q.quiz_id = r.quizzes_id
        JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
        JOIN tbl_teacher_subjects s ON s.teacher_subject_id = l.teacher_subject_id
        WHERE s.teacher_id = $teacher_id
    ")->fetch_row()[0];
    ?>
    <a class="nav-item" href="teacher_grades.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Gradebook
      <?php if ($badge_gradebook > 0): ?><span class="nav-badge" style="background:var(--purple);color:#fff"><?= $badge_gradebook ?></span><?php endif; ?>
    </a>
    <!--<a class="nav-item" href="teacher_reports.php">-->
    <!--  <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>-->
    <!--  Reports-->
    <!--</a>-->
    <div class="nav-section-label" style="margin-top:14px">Account</div>
    <a class="nav-item" href="teacher_profile.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      My Profile
    </a>
    <a class="nav-item" href="settings.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33h0A1.65 1.65 0 009 3.09V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51h0a1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82v0a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
      Settings
    </a>
    <a class="nav-item" href="about.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      About
    </a>
    <div style="margin-top:8px;border-top:1px solid rgba(255,255,255,0.08);padding-top:8px"></div>
    <a class="nav-item" href="#" onclick="openLogoutModal(); return false;" style="color:var(--red)">
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
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--accent),var(--purple))"><?php echo $sidebar_avatar_html; ?></div>
      <div class="user-info">
        <p><?php echo htmlspecialchars($display_name); ?></p>
        <span>Faculty — <?php echo $role_display; ?></span>
      </div>
    </div>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- MAIN -->
<main class="main">
  <div class="topbar">
  <button class="menu-btn" id="menuBtn" aria-label="Open menu">
    <div class="hamburger"><span></span><span></span><span></span></div>
  </button>
  <img class="topbar-logo-desktop" src="../assets/img/Excelsior_Logo_long.png" alt="Logo" style="height:38px;width:auto;">
  <div class="topbar-logo-mobile" style="display:none;align-items:center;gap:10px;flex-wrap:nowrap;overflow:hidden;">
    <img src="../assets/img/Excelsior_Seal_Logo.png" alt="ExceLearn Logo"
         style="height:38px;width:auto;object-fit:contain;flex-shrink:0;">
    <div style="min-width:0">
      <div class="logo-name">Exce<span style="color:#22c55e">Learn</span></div>
      <div class="logo-sub">Learn Better. Anytime, Anywhere.</div>
    </div>
  </div>
  <div class="topbar-title"></div>
  <div class="topbar-actions">
    <button class="btn-icon" onclick="openNotifModal()" aria-label="Notifications" style="position:relative;">
      🔔
      <?php if ($unread_count > 0): ?>
      <span class="notif-dot" id="notif-badge" style="position:absolute;top:4px;right:4px;background:var(--red,#ef4444);color:#fff;font-size:9px;font-weight:700;border-radius:999px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 3px;line-height:1;"><?= $unread_count ?></span>
      <?php else: ?>
      <span class="notif-dot" id="notif-badge" style="display:none"></span>
      <?php endif; ?>
    </button>
  </div>
</div>

  <div class="content">
    <?php if (!empty($success_msg)): ?>
    <div class="flash-msg flash-success" id="flashMsg">
      <span>✅ <?php echo htmlspecialchars($success_msg); ?></span>
      <button onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;margin-left:12px;">✕</button>
    </div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
    <div class="flash-msg flash-error" id="flashMsg">
      <span>❌ <?php echo htmlspecialchars($error_msg); ?></span>
      <button onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;margin-left:12px;">✕</button>
    </div>
    <?php endif; ?>

    <?php
    // Count all lessons (with or without quiz) to detect which scenario
    $all_lessons_count = (int)$conn->query("
        SELECT COUNT(*) FROM tbl_teacher_lessons l
        JOIN tbl_teacher_subjects s ON l.teacher_subject_id = s.teacher_subject_id
        WHERE s.teacher_id = $teacher_id
    ")->fetch_row()[0];
    ?>
    <?php if ($all_lessons_count === 0): ?>
    <div class="flash-msg flash-warning" style="margin-bottom:16px;">
      <span>📚 You need to create at least one lesson before adding quizzes. <a href="teacher_lessons.php" style="color:inherit;text-decoration:underline;font-weight:600;">Go to Lessons →</a></span>
    </div>
    <?php elseif (empty($teacher_lessons)): ?>
    <div class="flash-msg flash-warning" style="margin-bottom:16px;">
      <span>📝 All your lessons already have quizzes. Please <a href="teacher_lessons.php" style="color:inherit;text-decoration:underline;font-weight:600;">add another lesson →</a> to create a new quiz.</span>
    </div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <div class="page-title">My Quizzes</div>
        <div class="page-sub" id="quiz-count-text">
          <?php if ($current_lesson_id > 0): ?>
            Showing <strong><?php echo count($quizzes); ?></strong> of <?php echo $total_quizzes; ?> quizzes in <strong><?php echo $selected_lesson_name; ?></strong> &middot; <?php echo $published_count; ?> active, <?php echo $draft_count; ?> drafts
          <?php else: ?>
            Showing <strong><?php echo count($quizzes); ?></strong> of <?php echo $total_quizzes; ?> quizzes &middot; <?php echo $published_count; ?> active, <?php echo $draft_count; ?> drafts
          <?php endif; ?>
        </div>
      </div>
      <div class="page-actions">
        <?php if (!empty($teacher_lessons)): ?>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('createModal').classList.add('open');document.body.style.overflow='hidden';setTimeout(function(){document.getElementById('newLesson').focus()},100)">+ New Quiz</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="toolbar">
      <form method="GET" action="<?php echo basename(__FILE__); ?>" class="lesson-filter-form" style="width:auto;flex:0 1 auto;min-width:0;">

        <?php if ($current_filter !== 'all'): ?>
          <input type="hidden" name="filter" value="<?php echo htmlspecialchars($current_filter); ?>">
        <?php endif; ?>

        <?php if ($current_sort !== 'newest'): ?>
          <input type="hidden" name="sort" value="<?php echo htmlspecialchars($current_sort); ?>">
        <?php endif; ?>

        <?php if (!empty($search_query)): ?>
          <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
        <?php endif; ?>

        <select class="sort-select" name="lesson_id" onchange="this.form.submit()">
          <option value="">All Lessons</option>
          <?php foreach ($teacher_lessons as $tl): ?>
            <?php
              $opt_label = htmlspecialchars($tl['title'] ?? 'Untitled Lesson');
              if (!empty($tl['subject'])) $opt_label .= ' — ' . htmlspecialchars($tl['subject']);
              if (!empty($tl['grade_level'])) $opt_label .= ' (G' . htmlspecialchars($tl['grade_level']) . ')';
            ?>
            <option value="<?php echo $tl['lesson_id']; ?>" 
              <?php echo ($current_lesson_id == $tl['lesson_id']) ? 'selected' : ''; ?>>
              <?php echo $opt_label; ?>
            </option>
          <?php endforeach; ?>
        </select>

      </form>

      <form method="GET" action="<?php echo basename(__FILE__); ?>" class="search-wrap" style="display:flex;align-items:center;position:relative;flex:1;min-width:80px;max-width:200px;">
        <?php if ($current_filter !== 'all'): ?><input type="hidden" name="filter" value="<?php echo htmlspecialchars($current_filter); ?>"><?php endif; ?>
        <?php if ($current_sort !== 'newest'): ?><input type="hidden" name="sort" value="<?php echo htmlspecialchars($current_sort); ?>"><?php endif; ?>
        <?php if ($current_lesson_id > 0): ?><input type="hidden" name="lesson_id" value="<?php echo $current_lesson_id; ?>"><?php endif; ?>
        <span class="search-icon">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search Quizzes">
      </form>

      <div class="filter-tabs" id="filterTabs">
        <a href="<?php echo buildUrl(['filter' => 'all', 'search' => null]); ?>" class="filter-tab<?php echo $current_filter === 'all' ? ' active' : ''; ?>">All</a>
        <a href="<?php echo buildUrl(['filter' => 'published', 'search' => null]); ?>" class="filter-tab<?php echo $current_filter === 'published' ? ' active' : ''; ?>">Active</a>
        <a href="<?php echo buildUrl(['filter' => 'draft', 'search' => null]); ?>" class="filter-tab<?php echo $current_filter === 'draft' ? ' active' : ''; ?>">Draft</a>
      </div>

      <form method="GET" action="<?php echo basename(__FILE__); ?>" style="display:inline;">
        <?php if ($current_filter !== 'all'): ?><input type="hidden" name="filter" value="<?php echo htmlspecialchars($current_filter); ?>"><?php endif; ?>
        <?php if (!empty($search_query)): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>"></<?php endif; ?>
        <?php if ($current_lesson_id > 0): ?><input type="hidden" name="lesson_id" value="<?php echo $current_lesson_id; ?>"><?php endif; ?>
        <select class="sort-select" name="sort" onchange="this.form.submit()">
          <option value="newest" <?php echo $current_sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
          <option value="oldest" <?php echo $current_sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
          <option value="name-az" <?php echo $current_sort === 'name-az' ? 'selected' : ''; ?>>Name A–Z</option>
          <option value="name-za" <?php echo $current_sort === 'name-za' ? 'selected' : ''; ?>>Name Z–A</option>
        </select>
      </form>
    </div>

    <div class="quiz-grid" id="quizGrid">
      <?php if (empty($quizzes)): ?>
      <div class="empty-state">
        <div class="empty-icon">📝</div>
        <p>No quizzes found</p>
        <span>
          <?php if (empty($teacher_lessons)): ?>
            Create a lesson first, then add quizzes to it.
          <?php elseif ($current_lesson_id > 0): ?>
            No quizzes in this lesson yet. Create one to get started.
          <?php else: ?>
            Try adjusting your search or filter criteria.
          <?php endif; ?>
        </span>
      </div>
      <?php else: ?>
        <?php foreach ($quizzes as $q):
          $title          = htmlspecialchars($q['lesson_title'] ?? 'Untitled Lesson');
          $quiz_desc      = htmlspecialchars($q['description'] ?? '');
          $subject_name   = htmlspecialchars($q['subject'] ?? '');
          $section        = htmlspecialchars($q['course_section'] ?? '');
          $status         = $q['status'] ?? 'draft';
          $duration       = (int)($q['duration'] ?? 30);
          $passing_score  = (int)($q['passing_score'] ?? 75);
          $due_date       = formatDueDate($q['due_date'] ?? '');

          $decoded_q = json_decode($q['questions'] ?? '[]', true);
          $actual_question_count = is_array($decoded_q) ? count($decoded_q) : ((int)($q['question_count'] ?? 0));

          /* Count question types for display */
          $type_counts = ['multiple_choice' => 0, 'fill_in_the_blank' => 0, 'identification' => 0];
          if (is_array($decoded_q)) {
              foreach ($decoded_q as $dq) {
                  $t = $dq['type'] ?? 'multiple_choice';
                  if (isset($type_counts[$t])) $type_counts[$t]++;
              }
          }
          $type_labels = [];
          if ($type_counts['multiple_choice'] > 0) $type_labels[] = $type_counts['multiple_choice'] . ' MC';
          if ($type_counts['fill_in_the_blank'] > 0) $type_labels[] = $type_counts['fill_in_the_blank'] . ' FITB';
          if ($type_counts['identification'] > 0) $type_labels[] = $type_counts['identification'] . ' ID';
          $type_display = !empty($type_labels) ? implode(', ', $type_labels) : '';

          $strand_val = $q['strand'] ?? '';
          $icon = getQuizIcon($strand_val);

          $statusBadge = $status === 'published' ? 'badge-green' : ($status === 'scheduled' ? 'badge-blue' : 'badge-amber');
          $statusLabel = $status === 'published' ? 'Active' : ($status === 'scheduled' ? 'Scheduled' : 'Draft');
          $dueText     = $due_date === 'TBD' ? 'No due date' : 'Due ' . $due_date;

           $course_display = $subject_name;
              if (!empty($section)) $course_display .= ' — ' . $section;
              if (!empty($q['grade_level'])) $course_display .= ' (' . formatGrade($q['grade_level']) . ')';

          $is_overdue = false;
          if (!empty($q['due_date']) && $status === 'scheduled') {
              $is_overdue = strtotime($q['due_date']) < time();
          }
        ?>
        <div class="quiz-card">
          <div class="quiz-card-header">
            <div class="quiz-icon"><?php echo $icon; ?></div>
            <div class="quiz-info-main">
              <div class="quiz-title-row">
                <span class="quiz-title"><?php echo $title; ?></span>
                <span class="badge <?php echo $statusBadge; ?>"><?php echo $statusLabel; ?></span>
                <?php if ($is_overdue): ?>
                <span class="badge" style="background:var(--red);color:#fff;font-size:0.65rem;padding:2px 6px;border-radius:4px;">Overdue</span>
                <?php endif; ?>
              </div>
              <div class="quiz-meta-row">
                <span><?php echo $course_display; ?></span>
              </div>
              <?php if (!empty($quiz_desc)): ?>
              <div class="quiz-meta-row" style="margin-top:4px;opacity:0.7;font-size:0.82rem;">
                <span><?php echo $quiz_desc; ?></span>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="quiz-card-body">
            <div class="quiz-stats-grid">
              <div class="quiz-stat-item">
                <span class="quiz-stat-label">Duration</span>
                <span class="quiz-stat-value"><?php echo $duration; ?> min</span>
              </div>
              <div class="quiz-stat-item">
                <span class="quiz-stat-label">Questions</span>
                <span class="quiz-stat-value"><?php echo $actual_question_count > 0 ? $actual_question_count : '—'; ?></span>
              </div>
              <div class="quiz-stat-item">
                <span class="quiz-stat-label">Passing</span>
                <span class="quiz-stat-value"><?php echo $passing_score; ?>%</span>
              </div>
            </div>
            <?php if (!empty($type_display)): ?>
            <div style="margin-top:8px;font-size:0.75rem;opacity:0.6;"><?php echo $type_display; ?></div>
            <?php endif; ?>
          </div>
          <div class="quiz-card-footer">
            <div class="quiz-date"><?php echo $dueText; ?></div>
            <div class="quiz-actions">
              <?php if ($status === 'draft'): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Activate this quiz? Students will be able to take it.')">
                <input type="hidden" name="update_quiz_status" value="1">
                <input type="hidden" name="quiz_id" value="<?php echo $q['quiz_id']; ?>">
                <input type="hidden" name="new_status" value="published">
                <button type="submit" class="card-action-btn" title="Activate Quiz">🟢</button>
              </form>
              <?php elseif ($status === 'published'): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Unpublish this quiz? Students will no longer be able to take it.')">
                <input type="hidden" name="update_quiz_status" value="1">
                <input type="hidden" name="quiz_id" value="<?php echo $q['quiz_id']; ?>">
                <input type="hidden" name="new_status" value="draft">
                <button type="submit" class="card-action-btn" title="Unpublish Quiz">📝</button>
              </form>
              <?php endif; ?>

              <a href="<?php echo buildUrl(['edit' => $q['quiz_id']]); ?>" class="card-action-btn" title="Edit Quiz">✏️</a>

              <form method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this quiz? This cannot be undone.')">
                <input type="hidden" name="delete_quiz" value="1">
                <input type="hidden" name="quiz_id" value="<?php echo $q['quiz_id']; ?>">
                <button type="submit" class="card-action-btn danger" title="Delete Quiz">🗑</button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</main>
</div>
<div class="modal-overlay" id="createModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Create New Quiz</div>
      <button class="modal-close" onclick="closeCreateModal()">✕</button>
    </div>
    <form method="POST" action="<?php echo basename(__FILE__); ?>">
      <input type="hidden" name="create_quiz" value="1">
      <div class="modal-body">
        <div class="form-group">
          <label>Lesson *</label>
          <select name="lesson_id" id="newLesson" required>
            <option value="">Select a lesson</option>
            <?php foreach ($teacher_lessons as $tl): ?>
              <?php
                $opt_label = htmlspecialchars($tl['title'] ?? 'Untitled Lesson');
                if (!empty($tl['subject'])) $opt_label .= ' — ' . htmlspecialchars($tl['subject']);
                if (!empty($tl['grade_level'])) $opt_label .= ' (' . formatGrade($tl['grade_level']) . ')';
              ?>
            <option value="<?php echo $tl['lesson_id']; ?>" <?php echo ($current_lesson_id == $tl['lesson_id']) ? 'selected' : ''; ?>><?php echo $opt_label; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="3" placeholder="Optional description for this quiz..."></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Status</label>
            <select name="status" id="newStatus">
              <option value="draft">Draft</option>
              <option value="published">Active Now</option>
            </select>
          </div>
          <div class="form-group">
            <label>Duration (mins)</label>
            <input type="number" name="duration" placeholder="30" min="1" max="600" value="30">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Passing Score (%)</label>
            <input type="number" name="passing_score" placeholder="75" min="0" max="100" value="75">
          </div>
          <div class="form-group">
            <label>Due Date & Time</label>
            <input type="datetime-local" name="due_date" id="newDue">
            <small style="color:var(--text3);font-size:0.75rem;">Required if status is "Schedule for Later".</small>
          </div>
        </div>

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <label style="font-weight:600;">Questions <span id="newQuestionCountBadge" style="font-weight:400;opacity:0.6;font-size:0.85rem;">(0)</span></label>
        <button type="button" class="btn btn-ghost btn-sm" onclick="addCreateQuestion()">+ Add Question</button>
      </div>
      <input type="hidden" name="question_count" id="newQuestions" value="0">
      <div id="questionsContainer"></div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeCreateModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Quiz</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="logoutModal">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <div class="modal-title">Confirm Logout</div>
      <button class="modal-close" onclick="closeLogoutModal()">✕</button>
    </div>
    <div class="modal-body" style="text-align:center;padding:28px 20px">
      <div style="font-size:48px;margin-bottom:12px">👋</div>
      <div style="font-size:15px;color:var(--text);font-weight:500;margin-bottom:6px">Are you sure you want to log out?</div>
      <div style="font-size:13px;color:var(--text3)">Any unsaved changes will be lost.</div>
    </div>
    <div class="modal-footer" style="justify-content:center;padding:0 20px 24px">
      <button class="btn btn-ghost" onclick="closeLogoutModal()">Cancel</button>
      <button class="btn btn-danger" onclick="confirmLogout()">Yes, Log Out</button>
    </div>
  </div>
</div>
<div class="modal-overlay<?php echo $edit_mode ? ' open' : ''; ?>" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Quiz</div>
      <a href="<?php echo buildUrl(['edit' => null]); ?>" class="modal-close" style="text-decoration:none;color:inherit;cursor:pointer;">✕</a>
    </div>
    <?php if ($editing_quiz): ?>
    <form method="POST" action="<?php echo buildUrl(['edit' => null]); ?>">
      <input type="hidden" name="update_quiz" value="1">
      <input type="hidden" name="quiz_id" value="<?php echo $editing_quiz['quiz_id']; ?>">
      <div class="modal-body">
        <div class="form-group">
          <label>Lesson</label>
          <input type="text" value="<?php
            $label = htmlspecialchars($editing_quiz['lesson_title'] ?? 'Unknown Lesson');
            if (!empty($editing_quiz['subject'])) $label .= ' — ' . htmlspecialchars($editing_quiz['subject']);
            if (!empty($editing_quiz['grade_level'])) $label .= ' (' . formatGrade($editing_quiz['grade_level']) . ')';
            if (!empty($editing_quiz['tc_section'])) $label .= ' — Sec ' . htmlspecialchars($editing_quiz['tc_section']);
            echo $label;
          ?>" disabled style="opacity:0.6;cursor:not-allowed;">
          <small style="color:var(--text3);font-size:0.75rem;">Lesson cannot be changed after creation.</small>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="edit_description" rows="3" placeholder="Optional description..."><?php echo htmlspecialchars($editing_quiz['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Duration (mins)</label>
            <input type="number" name="edit_duration" value="<?php echo (int)($editing_quiz['duration'] ?? 30); ?>" min="1" max="600">
          </div>
          <div class="form-group">
            <label>Passing Score (%)</label>
            <input type="number" name="edit_passing_score" value="<?php echo (int)($editing_quiz['passing_score'] ?? 75); ?>" min="0" max="100">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Questions Count</label>
            <input type="text" id="editQuestionCountDisplay" value="<?php
              $eq_decoded = json_decode($editing_quiz['questions'] ?? '[]', true);
              echo is_array($eq_decoded) ? count($eq_decoded) : (int)($editing_quiz['question_count'] ?? 0);
            ?>" readonly style="opacity:0.6;cursor:not-allowed;">
            <small style="color:var(--text3);font-size:0.75rem;">Auto-updated when questions change.</small>
          </div>
          <div class="form-group">
            <label>Due Date & Time</label>
            <?php
              $edit_due_value = '';
              if (!empty($editing_quiz['due_date'])) {
                  $edit_due_value = date('Y-m-d\TH:i', strtotime($editing_quiz['due_date']));
              }
            ?>
            <input type="datetime-local" name="edit_due_date" value="<?php echo $edit_due_value; ?>">
            <small style="color:var(--text3);font-size:0.75rem;">Leave empty for no due date.</small>
          </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
          <label style="font-weight:600;">Questions</label>
          <button type="button" class="btn btn-ghost btn-sm" onclick="addEditQuestion()">+ Add Question</button>
        </div>
        <div id="editQuestionsContainer"></div>

        <!-- Status -->
        <div class="form-group" style="margin-top:16px;">
          <label>Status</label>
          <div style="display:flex;gap:8px;margin-top:4px;flex-wrap:wrap;">
            <?php foreach (['published', 'draft'] as $st): ?>
            <?php
              $can_switch = true;
              $switch_label = ucfirst($st);
              if ($st === 'published') $switch_label = 'Active';
              if ($st === 'scheduled' && empty($editing_quiz['due_date'])) {
                  $can_switch = false;
                  $switch_label = 'Scheduled (needs due date)';
              }
              $form_id = 'statusForm_' . $st;
            ?>
            <?php if (($editing_quiz['status'] ?? '') !== $st): ?>
            <?php if ($can_switch): ?>
            <button type="button" class="btn btn-ghost btn-sm"
              onclick="if(confirm('Change status to <?php echo $switch_label; ?>?')) document.getElementById('<?php echo $form_id; ?>').submit();">
              <?php echo $switch_label; ?>
            </button>
            <?php else: ?>
            <span class="btn btn-ghost btn-sm" style="opacity:0.35;cursor:not-allowed;" title="Set a due date first"><?php echo $switch_label; ?></span>
            <?php endif; ?>
            <?php else: ?>
            <span class="btn btn-ghost btn-sm" style="opacity:0.5;cursor:default;"><?php echo $switch_label; ?> ✓</span>
            <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if (!empty($editing_quiz['due_date'])): ?>
        <div style="background:rgba(59,130,246,0.08);padding:10px 14px;border-radius:8px;border:1px solid rgba(59,130,246,0.2);font-size:0.82rem;color:var(--text2);">
          📅 Scheduled: <strong><?php echo date('F d, Y \a\t g:i A', strtotime($editing_quiz['due_date'])); ?></strong>
          <?php if (strtotime($editing_quiz['due_date']) < time()): ?>
          <span style="color:var(--red);font-weight:600;margin-left:8px;">⚠ Overdue</span>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <a href="<?php echo buildUrl(['edit' => null]); ?>" class="btn btn-ghost" style="text-decoration:none;">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  <?php endif; ?>
  </div>

  <?php if ($editing_quiz): ?>
  <?php foreach (['published', 'draft'] as $st):
    $form_id = 'statusForm_' . $st; ?>
  <form id="<?php echo $form_id; ?>" method="POST" style="display:none;">
    <input type="hidden" name="update_quiz_status" value="1">
    <input type="hidden" name="quiz_id" value="<?php echo $editing_quiz['quiz_id']; ?>">
    <input type="hidden" name="new_status" value="<?php echo $st; ?>">
  </form>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
<!-- NOTIFICATION MODAL -->
<div class="modal-overlay" id="notifModal">
  <div class="modal" style="max-width:460px;max-height:80vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="flex-shrink:0;display:flex;align-items:center;justify-content:space-between;">
      <div class="modal-title">🔔 Notifications
        <span id="notif-unread-count" style="font-size:11px;font-weight:500;color:var(--text3);margin-left:6px;"><?= $unread_count > 0 ? "($unread_count unread)" : '' ?></span>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <?php if ($unread_count > 0): ?>
        <button class="btn btn-ghost btn-sm" onclick="markAllRead()" style="font-size:11px;padding:4px 10px;">✓ Mark all read</button>
        <?php endif; ?>
        <button class="modal-close" onclick="closeNotifModal()">✕</button>
      </div>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1;padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
      <?php if (empty($notifications)): ?>
        <div style="text-align:center;padding:40px 16px;color:var(--text3);">
          <div style="font-size:36px;margin-bottom:8px;">🔕</div>
          <div style="font-size:13px;">No notifications yet.</div>
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $notif):
          $n_ts   = strtotime($notif['created_at'] . ' Asia/Manila');
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
            'student_submitted_quiz' => '📬',
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
                    background:<?= $is_unread ? 'rgba(99,102,241,0.08)' : 'var(--bg2,rgba(255,255,255,.03))' ?>;
                    border:1px solid <?= $is_unread ? 'rgba(99,102,241,0.2)' : 'var(--border,rgba(255,255,255,.07))' ?>;">
          <div style="font-size:20px;flex-shrink:0;margin-top:1px;"><?= $n_icon ?></div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:<?= $is_unread ? '700' : '500' ?>;color:var(--text);margin-bottom:2px;line-height:1.3;">
              <?= htmlspecialchars($notif['title']) ?>
              <?php if ($is_unread): ?>
              <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--accent,#6366f1);margin-left:5px;vertical-align:middle;"></span>
              <?php endif; ?>
            </div>
            <div style="font-size:12px;color:var(--text3);line-height:1.45;margin-bottom:4px;">
              <?= htmlspecialchars($notif['message']) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:2px;">
              <div style="font-size:10px;color:var(--text3);opacity:.7;"><?= $n_time ?></div>
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
<script>
function escapeAttr(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function getAnswerAreaHtml(idx, type, data) {
    if (type === 'multiple_choice') {
        var cv = data ? (data.correct || 'a') : 'a';
        var o = data && data.options ? data.options : {a:'',b:'',c:'',d:''};
        var h = '<label class="q-label" style="font-size:0.9rem;">Options</label>';
        h += '<input type="text" name="questions[' + idx + '][opt_a]" placeholder="Option A" value="' + escapeAttr(o.a) + '" required>';
        h += '<input type="text" name="questions[' + idx + '][opt_b]" placeholder="Option B" value="' + escapeAttr(o.b) + '" required>';
        h += '<input type="text" name="questions[' + idx + '][opt_c]" placeholder="Option C" value="' + escapeAttr(o.c) + '" required>';
        h += '<input type="text" name="questions[' + idx + '][opt_d]" placeholder="Option D" value="' + escapeAttr(o.d) + '" required>';
        h += '<label class="q-label" style="font-size:0.9rem;">Correct Answer</label>';
        h += '<select name="questions[' + idx + '][correct]" style="width:100%;">';
        h += '<option value="a"' + (cv==='a'?' selected':'') + '>A</option>';
        h += '<option value="b"' + (cv==='b'?' selected':'') + '>B</option>';
        h += '<option value="c"' + (cv==='c'?' selected':'') + '>C</option>';
        h += '<option value="d"' + (cv==='d'?' selected':'') + '>D</option>';
        h += '</select>';
        return h;
    } else {
        var av = (data && type !== 'multiple_choice') ? (data.correct || '') : '';
        var ph = type === 'fill_in_the_blank' ? 'Enter the word/phrase that fills the blank...' : 'Enter the correct answer...';
        var h = '<label class="q-label" style="font-size:0.9rem;">Correct Answer</label>';
        h += '<input type="text" name="questions[' + idx + '][correct]" placeholder="' + ph + '" value="' + escapeAttr(av) + '" required>';
        return h;
    }
}

function buildQuestionBlock(idx, data, removeCallback) {
    var type = data ? (data.type || 'multiple_choice') : 'multiple_choice';
    var h = '<div class="q-block" id="qBlock' + idx + '">';
    h += '<button type="button" class="q-remove-btn" onclick="' + removeCallback + '(' + idx + ')" title="Remove question">&times;</button>';
    h += '<label class="q-label">Question ' + idx + '</label>';
    h += '<select name="questions[' + idx + '][type]" onchange="swapAnswerArea(' + idx + ')" style="width:100%;margin-bottom:8px;">';
    h += '<option value="multiple_choice"' + (type==='multiple_choice'?' selected':'') + '>Multiple Choice</option>';
    h += '<option value="fill_in_the_blank"' + (type==='fill_in_the_blank'?' selected':'') + '>Fill in the Blank</option>';
    h += '<option value="identification"' + (type==='identification'?' selected':'') + '>Identification</option>';
    h += '</select>';
    h += '<input type="text" name="questions[' + idx + '][text]" placeholder="Enter question text..." value="' + escapeAttr(data ? data.text : '') + '" required style="width:100%;">';
    h += '<div id="qAnswerArea' + idx + '">' + getAnswerAreaHtml(idx, type, data) + '</div>';
    h += '</div>';
    return h;
}

function swapAnswerArea(idx) {
    var block = document.getElementById('qBlock' + idx);
    if (!block) return;
    var sel = block.querySelector('select[name*="[type]"]');
    var area = document.getElementById('qAnswerArea' + idx);
    if (sel && area) {
        area.innerHTML = getAnswerAreaHtml(idx, sel.value, null);
    }
}

// WITH THIS
var createQIdx = 0;

function addCreateQuestion() {
    createQIdx++;
    var container = document.getElementById('questionsContainer');
    if (container) {
        container.insertAdjacentHTML('beforeend', buildQuestionBlock(createQIdx, null, 'removeCreateQuestion'));
        syncCreateQuestionCount();
    }
}

function removeCreateQuestion(idx) {
    var block = document.getElementById('qBlock' + idx);
    if (block) {
        block.remove();
        syncCreateQuestionCount();
    }
}

function syncCreateQuestionCount() {
    var container = document.getElementById('questionsContainer');
    var count = container ? container.querySelectorAll('.q-block').length : 0;
    var hidden = document.getElementById('newQuestions');
    var badge = document.getElementById('newQuestionCountBadge');
    if (hidden) hidden.value = count;
    if (badge) badge.textContent = '(' + count + ')';
}

var editQIdx = 0;
var editQuestionsData = <?php echo $edit_questions_js; ?>;

function loadEditQuestions() {
    var container = document.getElementById('editQuestionsContainer');
    if (!container) return;
    container.innerHTML = '';
    editQIdx = 0;
    if (Array.isArray(editQuestionsData)) {
        for (var i = 0; i < editQuestionsData.length; i++) {
            editQIdx++;
            container.insertAdjacentHTML('beforeend', buildQuestionBlock(editQIdx, editQuestionsData[i], 'removeEditQuestion'));
        }
    }
    syncEditQuestionCount();
}

function addEditQuestion() {
    editQIdx++;
    var container = document.getElementById('editQuestionsContainer');
    if (container) {
        container.insertAdjacentHTML('beforeend', buildQuestionBlock(editQIdx, null, 'removeEditQuestion'));
        syncEditQuestionCount();
    }
}

function removeEditQuestion(idx) {
    var block = document.getElementById('qBlock' + idx);
    if (block) {
        block.remove();
        syncEditQuestionCount();
    }
}

function syncEditQuestionCount() {
    var display = document.getElementById('editQuestionCountDisplay');
    if (display) {
        var container = document.getElementById('editQuestionsContainer');
        var count = container ? container.querySelectorAll('.q-block').length : 0;
        display.value = count;
    }
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('open');
    document.body.style.overflow = '';
}

window.addEventListener('DOMContentLoaded', function() {
    syncCreateQuestionCount();
    loadEditQuestions();
});

var sidebar=document.getElementById('sidebar');
var overlay=document.getElementById('sidebar-overlay');
var menuBtn=document.getElementById('menuBtn');
function openSidebar(){sidebar.classList.add('open');overlay.classList.add('open');document.body.style.overflow='hidden';}
function closeSidebar(){sidebar.classList.remove('open');overlay.classList.remove('open');document.body.style.overflow='';}
menuBtn.addEventListener('click',function(){sidebar.classList.contains('open')?closeSidebar():openSidebar();});
overlay.addEventListener('click',closeSidebar);
document.querySelectorAll('.nav-item').forEach(function(item){item.addEventListener('click',function(){if(window.innerWidth<=768)closeSidebar();});});
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogoutModal();});
var themeToggle=document.getElementById('themeToggle');
var themeIcon=document.getElementById('theme-icon');
var themeText=document.getElementById('theme-text');
function applyTheme(isLight){if(isLight){document.body.classList.add('light');themeIcon.textContent='☀️';themeText.textContent='Light Mode';}else{document.body.classList.remove('light');themeIcon.textContent='🌙';themeText.textContent='Dark Mode';}}
if(localStorage.getItem('excelearn-theme')==='light')applyTheme(true);
themeToggle.addEventListener('click',function(){var isLight=document.body.classList.contains('light');applyTheme(!isLight);localStorage.setItem('excelearn-theme',!isLight?'light':'dark');});

document.getElementById('createModal').addEventListener('click',function(e){if(e.target===this)closeCreateModal();});

document.addEventListener('keydown',function(e){
  if(e.key==='Escape'){closeCreateModal();closeSidebar();}
});

setTimeout(function(){
  var flashes=document.querySelectorAll('.flash-msg');
  flashes.forEach(function(f){
    f.style.opacity='0';f.style.transform='translateY(-8px)';f.style.transition='all .3s ease';
    setTimeout(function(){f.remove()},300);
  });
},6000);

function openLogoutModal(){document.getElementById('logoutModal').classList.add('open');document.body.style.overflow='hidden';}
function closeLogoutModal(){document.getElementById('logoutModal').classList.remove('open');document.body.style.overflow='';}
function confirmLogout(){window.location.href='../logout.php';}

var resizeTimer;
window.addEventListener('resize',function(){clearTimeout(resizeTimer);resizeTimer=setTimeout(function(){if(window.innerWidth>768)closeSidebar();},150);});

// ── Notification Modal ────────────────────────────────────────────────────
function openNotifModal() {
  document.getElementById('notifModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeNotifModal() {
  document.getElementById('notifModal').classList.remove('open');
  document.body.style.overflow = '';
}
document.getElementById('notifModal').addEventListener('click', function(e) {
  if (e.target === this) closeNotifModal();
});

function markOneRead(id, el) {
  var url = el.dataset.url || '#';

  // Mark read in DB (fire and forget)
  fetch('notif_mark_read.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'notification_id=' + id
  });

  // Visual update immediately
  if (!el.dataset.read) {
    el.style.background = 'var(--bg2,rgba(255,255,255,.03))';
    el.style.border     = '1px solid var(--border,rgba(255,255,255,.07))';
    var dot   = el.querySelector('span[style*="border-radius:50%"]');
    var title = el.querySelector('div[style*="font-weight"]');
    if (dot)   dot.remove();
    if (title) title.style.fontWeight = '500';
    el.dataset.read = '1';
    updateBadgeCount(-1);
  }

  // Navigate after a brief delay so the fetch fires first
  if (url && url !== '#') {
    setTimeout(function() {
      window.location.href = url;
    }, 150);
  }
}

function markAllRead() {
  fetch('notif_mark_read.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'mark_all=1'
  });
  // Visual update — clear all unread styles
  document.querySelectorAll('.notif-item').forEach(function(el) {
    el.style.background = 'var(--bg2,rgba(255,255,255,.03))';
    el.style.border     = '1px solid var(--border,rgba(255,255,255,.07))';
    var dot   = el.querySelector('span[style*="border-radius:50%"]');
    var title = el.querySelector('div[style*="font-weight"]');
    if (dot)   dot.remove();
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
  if (next <= 0) {
    badge.style.display = 'none';
  } else {
    badge.textContent   = next;
    badge.style.display = 'flex';
  }
}

function deleteNotif(id, btn, e) {
  e.stopPropagation(); // prevent triggering markOneRead
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
    // Show empty state if no items left
    var remaining = document.querySelectorAll('.notif-item');
    if (remaining.length === 0) {
      var body = document.querySelector('#notifModal .modal-body');
      body.innerHTML = '<div style="text-align:center;padding:40px 16px;color:var(--text3);">'
        + '<div style="font-size:36px;margin-bottom:8px;">🔕</div>'
        + '<div style="font-size:13px;">No notifications yet.</div>'
        + '</div>';
    }
  }, 250);
  if (wasUnread) updateBadgeCount(-1);
}
</script>
</body>
</html>