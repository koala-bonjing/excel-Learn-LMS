<?php
session_start();
date_default_timezone_set('Asia/Manila');
include __DIR__ . '/../connection.php';
$connection = $conn;
$connection->set_charset("utf8mb4");
ini_set('display_errors', 1);
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

 $teacher_theme = 'dark';
 $ts_stmt = $conn->prepare("SELECT theme FROM tblteacher_settings WHERE teacher_id = ?");
 $ts_stmt->bind_param("i", $teacher_id);
 $ts_stmt->execute();
 $ts_row = $ts_stmt->get_result()->fetch_assoc();
 $ts_stmt->close();
if (!empty($ts_row['theme'])) $teacher_theme = $ts_row['theme'];

// ── Helpers ──────────────────────────────────────────────────────────────
function buildUrl($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) unset($params[$k]);
        else $params[$k] = $v;
    }
    $base = basename(__FILE__);
    return $base . (!empty($params) ? '?' . http_build_query($params) : '');
}

function parseDueDate($raw) {
    if (empty($raw)) return null;
    $raw = trim($raw);
    $tz = new DateTimeZone('Asia/Manila');
    foreach (['Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d H:i:s', 'Y-m-d\TH:i:s'] as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $raw, $tz);
        if ($dt !== false) {
            $errors = DateTime::getLastErrors();
            if (empty($errors['errors']) && empty($errors['warnings'])) {
                return $dt->format('Y-m-d H:i:s');
            }
        }
    }
    return false;
}

function isTaskOverdue($due_date) {
    if (empty($due_date)) return false;
    $tz = new DateTimeZone('Asia/Manila');
    return new DateTime($due_date, $tz) < new DateTime('now', $tz);
}

function getDueLabel($due_date) {
    if (empty($due_date)) return ['label' => 'No deadline', 'class' => 'active-expiry'];
    if (isTaskOverdue($due_date)) {
        return ['label' => 'Past due ' . date('M d, Y g:i A', strtotime($due_date)), 'class' => 'expired'];
    }
    $diff_days = (strtotime($due_date) - time()) / 86400;
    if ($diff_days <= 2) {
        return ['label' => 'Due ' . date('M d, Y g:i A', strtotime($due_date)), 'class' => 'expiring-soon'];
    }
    return ['label' => 'Due ' . date('M d, Y g:i A', strtotime($due_date)), 'class' => 'active-expiry'];
}

function deleteFileByPath($path) {
    if (empty($path)) return;
    foreach (['../' . $path, $path] as $fp) {
        if (file_exists($fp)) @unlink($fp);
    }
}

function formatGradeLabel($grade_level) {
    if (empty($grade_level)) return '';
    $gl = htmlspecialchars($grade_level);
    if (preg_match('/^(G|Grade)\s*/i', $gl)) return $gl;
    return 'G' . $gl;
}

function escHtmlPHP($s) { return htmlspecialchars($s ?? '', ENT_QUOTES); }

// ── Logged-in teacher info ────────────────────────────────────────────────
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
$sidebar_avatar_html = !empty($profile_pic_path)
    ? '<img src="' . htmlspecialchars($profile_pic_path) . '" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">'
    : htmlspecialchars($initials);

// ── Teacher's subjects ─────────────────────────────────────────────────────
$teacher_subjects = [];
$tsres = $conn->query("
    SELECT DISTINCT tts.teacher_subject_id, tts.subject_id, sub.subject, s.strand, s.track, g.grade_level
    FROM tbl_teacher_subjects tts
    LEFT JOIN tblsubjects sub ON tts.subject_id = sub.subject_id
    LEFT JOIN tblstrands s ON sub.strand_id = s.strand_id
    LEFT JOIN tblgrade_levels g ON s.grade_level_id = g.grade_id
    WHERE tts.teacher_id = $teacher_id AND tts.subject_id IS NOT NULL
    ORDER BY sub.subject ASC
");
if ($tsres) {
    while ($ts = $tsres->fetch_assoc()) $teacher_subjects[] = $ts;
}

$valid_teacher_subject_ids = array_map(fn($ts) => (int)$ts['teacher_subject_id'], $teacher_subjects);
$valid_subject_list = !empty($valid_teacher_subject_ids) ? implode(',', $valid_teacher_subject_ids) : '0';

// ── CREATE TASK ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $subject_id   = intval($_POST['subject_id'] ?? 0);
    $title        = trim($_POST['title'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $max_score    = max(1, intval($_POST['max_score'] ?? 100));
    $due_raw      = trim($_POST['due_date'] ?? '');
    $status       = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'published';

    $errors = [];
    $due_db = null;
    if (!empty($due_raw)) {
        $parsed = parseDueDate($due_raw);
        if ($parsed === false) $errors[] = "Invalid due date format.";
        else $due_db = $parsed;
    }
    if ($subject_id <= 0) $errors[] = "Please select a subject.";
    if (empty($title)) $errors[] = "Please enter a task title.";
    if (empty($description)) $errors[] = "Please describe the activity the student needs to perform.";

    if (empty($errors)) {
        $tc = $conn->prepare("SELECT teacher_subject_id AS id FROM tbl_teacher_subjects WHERE teacher_id = ? AND subject_id = ? LIMIT 1");
        $tc->bind_param("ii", $teacher_id, $subject_id);
        $tc->execute();
        $tc_row = $tc->get_result()->fetch_assoc();
        $tc->close();
        $teacher_subject_id = $tc_row['id'] ?? 0;

        if ($teacher_subject_id <= 0) {
            $_SESSION['task_error'] = "Invalid subject selection.";
        } else {
            $ins = $conn->prepare("
                INSERT INTO tblperformance_tasks
                    (teacher_subject_id, title, description, max_score, due_date, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $ins->bind_param("ississ", $teacher_subject_id, $title, $description, $max_score, $due_db, $status);
            if ($ins->execute()) {
                $_SESSION['task_success'] = "Performance task \"$title\" created successfully.";
            } else {
                $_SESSION['task_error'] = "Error creating task: " . $ins->error;
            }
            $ins->close();
        }
    } else {
        $_SESSION['task_error'] = implode(" ", $errors);
    }
    header("Location: " . buildUrl(['edit' => null]));
    exit();
}

// ── UPDATE TASK ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task'])) {
    $task_id      = intval($_POST['task_id'] ?? 0);
    $title        = trim($_POST['edit_title'] ?? '');
    $description  = trim($_POST['edit_description'] ?? '');
    $max_score    = max(1, intval($_POST['edit_max_score'] ?? 100));
    $due_raw      = trim($_POST['edit_due_date'] ?? '');
    $remove_due   = isset($_POST['remove_due']) ? 1 : 0;
    $status       = in_array($_POST['edit_status'] ?? '', ['draft', 'published']) ? $_POST['edit_status'] : 'draft';

    $errors = [];
    if (empty($title)) $errors[] = "Please enter a task title.";
    if (empty($description)) $errors[] = "Please describe the activity.";

    $due_db = null;
    if (!empty($due_raw)) {
        $parsed = parseDueDate($due_raw);
        if ($parsed === false) $errors[] = "Invalid due date format.";
        else $due_db = $parsed;
    } elseif (!$remove_due) {
        $cur = $conn->prepare("SELECT due_date FROM tblperformance_tasks WHERE task_id = ? AND teacher_subject_id IN ($valid_subject_list)");
        $cur->bind_param("i", $task_id);
        $cur->execute();
        $cur_row = $cur->get_result()->fetch_assoc();
        $cur->close();
        $due_db = $cur_row['due_date'] ?? null;
    }

    if (empty($errors) && $task_id > 0) {
        if ($status === 'published' && isTaskOverdue($due_db)) {
            $_SESSION['task_error'] = "Cannot publish a task whose due date has already passed. Update the due date first.";
        } else {
            $upd = $conn->prepare("
                UPDATE tblperformance_tasks
                SET title = ?, description = ?, max_score = ?, due_date = ?, status = ?, updated_at = NOW()
                WHERE task_id = ? AND teacher_subject_id IN ($valid_subject_list)
            ");
            $upd->bind_param("ssissi", $title, $description, $max_score, $due_db, $status, $task_id);
            if ($upd->execute()) {
                $_SESSION['task_success'] = "Performance task updated successfully.";
            } else {
                $_SESSION['task_error'] = "Error updating task: " . $upd->error;
            }
            $upd->close();
        }
    } else {
        $_SESSION['task_error'] = implode(" ", $errors);
    }
    header("Location: " . buildUrl(['edit' => null]));
    exit();
}

// ── DELETE TASK ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    $task_id = intval($_POST['task_id'] ?? 0);
    if ($task_id > 0) {
        $own = $conn->prepare("SELECT task_id FROM tblperformance_tasks WHERE task_id = ? AND teacher_subject_id IN ($valid_subject_list)");
        $own->bind_param("i", $task_id);
        $own->execute();
        $owned = $own->get_result()->fetch_assoc();
        $own->close();

        if ($owned) {
            $chk = $conn->prepare("SELECT COUNT(*) AS cnt FROM tblperformance_task_submissions WHERE task_id = ?");
            $chk->bind_param("i", $task_id);
            $chk->execute();
            $sub_count = (int)$chk->get_result()->fetch_assoc()['cnt'];
            $chk->close();

            if ($sub_count > 0) {
                $_SESSION['task_error'] = "Cannot delete this task — {$sub_count} student(s) already submitted a video. Review/grade their work first, or delete those submissions before removing the task.";
            } else {
                $del = $conn->prepare("DELETE FROM tblperformance_tasks WHERE task_id = ? AND teacher_subject_id IN ($valid_subject_list)");
                $del->bind_param("i", $task_id);
                $del->execute();
                $del->close();
                $_SESSION['task_success'] = "Performance task deleted successfully.";
            }
        }
    }
    header("Location: " . buildUrl(['edit' => null]));
    exit();
}

// ── PUBLISH / UNPUBLISH ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task_status'])) {
    $task_id    = intval($_POST['task_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    if ($task_id > 0 && in_array($new_status, ['published', 'draft'])) {
        if ($new_status === 'published') {
            $chk = $conn->prepare("SELECT due_date FROM tblperformance_tasks WHERE task_id = ? AND teacher_subject_id IN ($valid_subject_list)");
            $chk->bind_param("i", $task_id);
            $chk->execute();
            $chk_row = $chk->get_result()->fetch_assoc();
            $chk->close();
            if ($chk_row && isTaskOverdue($chk_row['due_date'] ?? '')) {
                $_SESSION['task_error'] = "Cannot publish a task that is already past its due date.";
                header("Location: " . buildUrl(['edit' => null]));
                exit();
            }
        }
        $upd = $conn->prepare("UPDATE tblperformance_tasks SET status = ?, updated_at = NOW() WHERE task_id = ? AND teacher_subject_id IN ($valid_subject_list)");
        $upd->bind_param("si", $new_status, $task_id);
        $upd->execute();
        $upd->close();
        $_SESSION['task_success'] = "Task status changed to " . ucfirst($new_status) . ".";
    }
    header("Location: " . buildUrl(['edit' => null]));
    exit();
}

// ── GRADE A SUBMISSION ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    $submission_id = intval($_POST['submission_id'] ?? 0);
    $view_task     = intval($_POST['view_task'] ?? 0);
    $score_raw     = $_POST['score'] ?? '';
    $feedback      = trim($_POST['feedback'] ?? '');

    $own = $conn->prepare("
        SELECT sub.submission_id, t.max_score
        FROM tblperformance_task_submissions sub
        INNER JOIN tblperformance_tasks t ON t.task_id = sub.task_id
        WHERE sub.submission_id = ? AND t.teacher_subject_id IN ($valid_subject_list)
    ");
    $own->bind_param("i", $submission_id);
    $own->execute();
    $own_row = $own->get_result()->fetch_assoc();
    $own->close();

    if ($own_row) {
        $max_score = (int)$own_row['max_score'];
        $score = ($score_raw === '' ? null : max(0, min($max_score, intval($score_raw))));
        $upd = $conn->prepare("
            UPDATE tblperformance_task_submissions
            SET score = ?, feedback = ?, status = 'graded', graded_at = NOW()
            WHERE submission_id = ?
        ");
        $upd->bind_param("isi", $score, $feedback, $submission_id);
        $upd->execute();
        $upd->close();
        $_SESSION['task_success'] = "Submission graded.";
    } else {
        $_SESSION['task_error'] = "Submission not found or access denied.";
    }
    header("Location: " . buildUrl(['edit' => null, 'view_task' => $view_task > 0 ? $view_task : null]));
    exit();
}

// ── DELETE A SUBMISSION ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_submission'])) {
    $submission_id = intval($_POST['submission_id'] ?? 0);
    $view_task     = intval($_POST['view_task'] ?? 0);

    $own = $conn->prepare("
        SELECT sub.submission_id, sub.video_path
        FROM tblperformance_task_submissions sub
        INNER JOIN tblperformance_tasks t ON t.task_id = sub.task_id
        WHERE sub.submission_id = ? AND t.teacher_subject_id IN ($valid_subject_list)
    ");
    $own->bind_param("i", $submission_id);
    $own->execute();
    $own_row = $own->get_result()->fetch_assoc();
    $own->close();

    if ($own_row) {
        deleteFileByPath($own_row['video_path']);
        $del = $conn->prepare("DELETE FROM tblperformance_task_submissions WHERE submission_id = ?");
        $del->bind_param("i", $submission_id);
        $del->execute();
        $del->close();
        $_SESSION['task_success'] = "Submission removed.";
    }
    header("Location: " . buildUrl(['edit' => null, 'view_task' => $view_task > 0 ? $view_task : null]));
    exit();
}

$success_msg = $_SESSION['task_success'] ?? '';
$error_msg   = $_SESSION['task_error'] ?? '';
unset($_SESSION['task_success'], $_SESSION['task_error']);

// ── Filters ──────────────────────────────────────────────────────────────────
$current_filter = $_GET['filter'] ?? 'all';
if (!in_array($current_filter, ['all', 'published', 'draft'])) $current_filter = 'all';

$current_sort = $_GET['sort'] ?? 'newest';
if (!in_array($current_sort, ['newest', 'oldest', 'name-az', 'name-za', 'due-soon'])) $current_sort = 'newest';

$search_query = trim($_GET['search'] ?? '');

$current_subject_id = intval($_GET['subject_id'] ?? 0);

$view_task_id = intval($_GET['view_task'] ?? 0);

// ── Edit mode ────────────────────────────────────────────────────────────────
$edit_mode = isset($_GET['edit']);
$editing_task = null;
if ($edit_mode) {
    $edit_id = intval($_GET['edit']);
    if ($edit_id > 0) {
        $estmt = $conn->prepare("
            SELECT t.*, sub.subject, s.strand, s.track, g.grade_level
            FROM tblperformance_tasks t
            LEFT JOIN tbl_teacher_subjects tts ON t.teacher_subject_id = tts.teacher_subject_id
            LEFT JOIN tblsubjects sub ON tts.subject_id = sub.subject_id
            LEFT JOIN tblstrands s ON sub.strand_id = s.strand_id
            LEFT JOIN tblgrade_levels g ON s.grade_level_id = g.grade_id
            WHERE t.task_id = ? AND t.teacher_subject_id IN ($valid_subject_list)
        ");
        $estmt->bind_param("i", $edit_id);
        $estmt->execute();
        $editing_task = $estmt->get_result()->fetch_assoc();
        $estmt->close();
    }
    if (!$editing_task) $edit_mode = false;
}

// ── Main tasks query ──────────────────────────────────────────────────────────
$sql = "
    SELECT
        t.task_id, t.teacher_subject_id, t.title, t.description, t.max_score,
        t.due_date, t.status, t.created_at, t.updated_at,
        sub.subject AS subject_name, s.strand, s.track, g.grade_level,
        COALESCE(sc.submitted_count, 0) AS submitted_count,
        COALESCE(sc.graded_count, 0)    AS graded_count,
        COALESCE(tot.total_students, 0) AS total_students
    FROM tblperformance_tasks t
    LEFT JOIN tbl_teacher_subjects tts ON t.teacher_subject_id = tts.teacher_subject_id
    LEFT JOIN tblsubjects sub ON tts.subject_id = sub.subject_id
    LEFT JOIN tblstrands s ON sub.strand_id = s.strand_id
    LEFT JOIN tblgrade_levels g ON s.grade_level_id = g.grade_id
    LEFT JOIN (
        SELECT task_id, COUNT(*) AS submitted_count, SUM(status = 'graded') AS graded_count
        FROM tblperformance_task_submissions GROUP BY task_id
    ) sc ON sc.task_id = t.task_id
    LEFT JOIN (
        SELECT teacher_subject_id, COUNT(DISTINCT student_id) AS total_students
        FROM tbl_teachers_students GROUP BY teacher_subject_id
    ) tot ON tot.teacher_subject_id = t.teacher_subject_id
    WHERE t.teacher_subject_id IN ($valid_subject_list)
";

$params = [];
$types  = "";

if ($current_subject_id > 0) {
    $sql .= " AND tts.subject_id = ?";
    $params[] = $current_subject_id;
    $types .= "i";
}
if ($current_filter !== 'all') {
    $sql .= " AND t.status = ?";
    $params[] = $current_filter;
    $types .= "s";
}
if (!empty($search_query)) {
    $like = "%" . $search_query . "%";
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ? OR sub.subject LIKE ?)";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}
switch ($current_sort) {
    case 'newest':   $sql .= " ORDER BY t.created_at DESC"; break;
    case 'oldest':   $sql .= " ORDER BY t.created_at ASC"; break;
    case 'name-az':  $sql .= " ORDER BY t.title ASC"; break;
    case 'name-za':  $sql .= " ORDER BY t.title DESC"; break;
    case 'due-soon': $sql .= " ORDER BY (t.due_date IS NULL), t.due_date ASC"; break;
}

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$tasks = [];
while ($row = $result->fetch_assoc()) $tasks[] = $row;
$result->free();
$stmt->close();

// ── Submissions grouped by task_id ────────────────────────────────────────────
$submissions_by_task = [];
$sstmt = $conn->prepare("
    SELECT sub.submission_id, sub.task_id, sub.student_id, sub.video_path, sub.video_filename,
           sub.notes, sub.score, sub.feedback, sub.status, sub.submitted_at, sub.graded_at,
           st.firstname, st.lastname, st.profile_pic
    FROM tblperformance_task_submissions sub
    INNER JOIN tblstudents st ON st.id = sub.student_id
    INNER JOIN tblperformance_tasks t ON t.task_id = sub.task_id
    INNER JOIN tbl_teacher_subjects tts ON tts.teacher_subject_id = t.teacher_subject_id
    WHERE tts.teacher_id = ?
    ORDER BY sub.submitted_at DESC
");
$sstmt->bind_param("i", $teacher_id);
$sstmt->execute();
$sres = $sstmt->get_result();
while ($row = $sres->fetch_assoc()) {
    $tid = (int)$row['task_id'];
    if (!isset($submissions_by_task[$tid])) $submissions_by_task[$tid] = [];
    $submissions_by_task[$tid][] = $row;
}
$sstmt->close();

// ── Badge counts for sidebar ───────────────────────────────────────────────────
$total_tasks_badge = 0;
$tb = $conn->query("SELECT COUNT(*) AS cnt FROM tblperformance_tasks WHERE teacher_subject_id IN ($valid_subject_list)");
if ($tb) $total_tasks_badge = (int)($tb->fetch_assoc()['cnt'] ?? 0);

$pending_review_badge = 0;
$pr = $conn->query("
    SELECT COUNT(*) AS cnt FROM tblperformance_task_submissions sub
    INNER JOIN tblperformance_tasks t ON t.task_id = sub.task_id
    WHERE t.teacher_subject_id IN ($valid_subject_list) AND sub.status = 'submitted'
");
if ($pr) $pending_review_badge = (int)($pr->fetch_assoc()['cnt'] ?? 0);

$total_lessons_badge = 0;
$lb = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_teacher_lessons WHERE teacher_subject_id IN ($valid_subject_list)");
if ($lb) $total_lessons_badge = (int)($lb->fetch_assoc()['cnt'] ?? 0);

$total_subjects_badge = count($teacher_subjects);

$total_quizzes = 0;
$tq = $conn->query("
    SELECT COUNT(*) AS cnt FROM tblquizzes q
    INNER JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id
    WHERE l.teacher_subject_id IN ($valid_subject_list)
");
if ($tq) $total_quizzes = (int)($tq->fetch_assoc()['cnt'] ?? 0);

$unique_students = (int)$conn->query("
    SELECT COUNT(DISTINCT student_id) FROM tbl_teachers_students
    WHERE teacher_subject_id IN ($valid_subject_list)
")->fetch_row()[0];

$badge_virtualclass = (int)$conn->query("SELECT COUNT(*) FROM tblvirtualclasses WHERE teacher_id = $teacher_id AND status IN ('upcoming','live')")->fetch_row()[0];
$badge_gradebook = (int)$conn->query("
    SELECT COUNT(DISTINCT r.student_id)
    FROM tblquiz_results r
    JOIN tblquizzes q ON q.quiz_id = r.quizzes_id
    JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    WHERE l.teacher_subject_id IN ($valid_subject_list)
")->fetch_row()[0];

// ── Notifications ──────────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT notification_id, title, message, type, status, created_at FROM tblnotifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30");
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$unread_count = count(array_filter($notifications, fn($n) => $n['status'] === 'unread'));

function getNotifUrl(string $type): string {
    return match($type) {
        'new_subject_assigned' => 'teacher_courses.php',
        'new_lesson', 'lesson_expiring', 'lesson_expired' => 'teacher_lessons.php',
        'new_quiz', 'quiz_due_soon', 'quiz_overdue' => 'teacher_quizzes.php',
        'quiz_passed', 'quiz_failed', 'student_submitted_quiz' => 'teacher_grades.php',
        'new_announcement' => 'index.php#announcements',
        default => '#',
    };
}

$selected_subject_name = 'All Subjects';
if ($current_subject_id > 0) {
    foreach ($teacher_subjects as $ts) {
        if ((int)$ts['subject_id'] === $current_subject_id) {
            $label = htmlspecialchars($ts['subject'] ?? 'Unknown');
            $fg = formatGradeLabel($ts['grade_level'] ?? '');
            if (!empty($fg)) $label .= ' (' . $fg . ')';
            $selected_subject_name = $label;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn LMS — Performance Tasks</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/teacher/teacher_lessons.css">
<style>
.task-card { display:flex; flex-direction:column; gap:10px; padding:16px; border-radius:14px; background:var(--card,rgba(255,255,255,.03)); border:1px solid var(--border,rgba(255,255,255,.08)); }
.task-card-top { display:flex; gap:10px; align-items:flex-start; justify-content:space-between; }
.task-title { font-size:14px; font-weight:700; color:var(--text); word-break:break-word; }
.task-desc { font-size:12.5px; color:var(--text2); line-height:1.5; max-height:60px; overflow:hidden; }
.task-stats { display:flex; gap:14px; flex-wrap:wrap; font-size:12px; color:var(--text2); }
.task-actions { display:flex; gap:6px; flex-wrap:wrap; margin-top:6px; }
.progress-bar-bg { width:100%; height:6px; border-radius:4px; background:rgba(255,255,255,0.08); overflow:hidden; }
.progress-bar-fill { height:100%; background:var(--accent,#6366f1); border-radius:4px; }
.submission-row { display:flex; gap:12px; padding:12px; border-radius:10px; background:var(--bg2,rgba(255,255,255,.03)); border:1px solid var(--border,rgba(255,255,255,.07)); margin-bottom:10px; flex-wrap:wrap; }
.submission-row video { width:220px; max-width:100%; border-radius:8px; background:#000; }
.task-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; margin-top:8px; }
@media (max-width: 768px) {
  .topbar { position: fixed !important; top:0 !important; left:0 !important; right:0 !important; z-index:100 !important; width:100% !important; }
  .main { padding-top: 60px !important; }
}
</style>
</head>
<body class="<?php echo $teacher_theme === 'light' ? 'light' : ''; ?>">
<div class="layout">
<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo" style="padding:20px 16px 16px;display:flex;align-items:center;gap:10px;">
    <img src="../assets/img/Excelsior_Seal_Logo.png" alt="ExceLearn Logo" style="height:38px;width:auto;object-fit:contain;flex-shrink:0;">
    <div style="min-width:0">
      <div style="font-weight:800;font-size:1.15rem;letter-spacing:-0.05em;line-height:1;white-space:nowrap;">Exce<span style="color:#22c55e;">Learn</span></div>
      <div style="font-size:0.7rem;color:#9ca3af;white-space:nowrap;margin-top:2px;">Learn Better. Anytime, Anywhere.</div>
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
      Subjects <span class="nav-badge"><?php echo $total_subjects_badge; ?></span>
    </a>
    <a class="nav-item active" href="teacher_performance_tasks.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>
      Performance Tasks <span class="nav-badge"><?php echo $total_tasks_badge; ?></span>
      <?php if ($pending_review_badge > 0): ?><span class="nav-badge" style="background:var(--red,#ef4444);color:#fff;margin-left:4px;"><?= $pending_review_badge ?></span><?php endif; ?>
    </a>
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
    <a class="nav-item" href="teacher_grades.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Gradebook
      <?php if ($badge_gradebook > 0): ?><span class="nav-badge" style="background:var(--purple);color:#fff"><?= $badge_gradebook ?></span><?php endif; ?>
    </a>
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
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
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
    <button class="menu-btn" id="menuBtn" aria-label="Open menu"><div class="hamburger"><span></span><span></span><span></span></div></button>
    <img src="../assets/img/Excelsior_Logo_long.png" alt="Logo" style="height:38px;width:auto;">
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
    <div class="flash-msg flash-success" id="flashMsg"><span>✅ <?php echo htmlspecialchars($success_msg); ?></span><button onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;margin-left:12px;">✕</button></div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
    <div class="flash-msg flash-error" id="flashMsg"><span>❌ <?php echo htmlspecialchars($error_msg); ?></span><button onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;margin-left:12px;">✕</button></div>
    <?php endif; ?>

    <?php if (empty($teacher_subjects)): ?>
    <div class="flash-msg flash-warning" style="margin-bottom:16px;">
      <span>📚 You need at least one subject before creating performance tasks. <a href="teacher_courses.php" style="color:inherit;text-decoration:underline;font-weight:600;">Go to My Subjects →</a></span>
    </div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <div class="page-title">Performance Tasks</div>
        <div class="page-sub">
          Showing <strong><?php echo count($tasks); ?></strong> task(s)<?php if ($current_subject_id > 0): ?> in <strong><?php echo $selected_subject_name; ?></strong><?php endif; ?>
          <?php if ($pending_review_badge > 0): ?> &middot; <span style="color:var(--amber,#f5a623);"><?php echo $pending_review_badge; ?> submission(s) awaiting review</span><?php endif; ?>
        </div>
      </div>
      <div class="page-actions">
        <?php if (!empty($teacher_subjects)): ?>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('createTaskModal').classList.add('open');document.body.style.overflow='hidden';">+ New Performance Task</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="toolbar">
      <form method="GET" action="<?php echo basename(__FILE__); ?>" class="subject-filter-form">
        <?php if ($current_filter !== 'all'): ?><input type="hidden" name="filter" value="<?php echo htmlspecialchars($current_filter); ?>"><?php endif; ?>
        <?php if ($current_sort !== 'newest'): ?><input type="hidden" name="sort" value="<?php echo htmlspecialchars($current_sort); ?>"><?php endif; ?>
        <?php if (!empty($search_query)): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>"><?php endif; ?>
        <select class="sort-select" name="subject_id" onchange="this.form.submit()">
          <option value="">All Subjects</option>
          <?php foreach ($teacher_subjects as $ts): ?>
            <?php
              $opt_label = htmlspecialchars($ts['subject'] ?? 'Untitled');
              $fg = formatGradeLabel($ts['grade_level'] ?? '');
              if (!empty($fg)) $opt_label .= ' (' . $fg . ')';
            ?>
            <option value="<?php echo $ts['subject_id']; ?>" <?php echo ($current_subject_id == $ts['subject_id']) ? 'selected' : ''; ?>><?php echo $opt_label; ?></option>
          <?php endforeach; ?>
        </select>
      </form>

      <form method="GET" action="<?php echo basename(__FILE__); ?>" class="search-wrap" style="display:flex;align-items:center;position:relative;">
        <?php if ($current_filter !== 'all'): ?><input type="hidden" name="filter" value="<?php echo htmlspecialchars($current_filter); ?>"><?php endif; ?>
        <?php if ($current_sort !== 'newest'): ?><input type="hidden" name="sort" value="<?php echo htmlspecialchars($current_sort); ?>"><?php endif; ?>
        <?php if ($current_subject_id > 0): ?><input type="hidden" name="subject_id" value="<?php echo $current_subject_id; ?>"><?php endif; ?>
        <span class="search-icon"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search tasks...">
      </form>

      <div class="filter-tabs">
        <a href="<?php echo buildUrl(['filter' => 'all']); ?>" class="filter-tab<?php echo $current_filter === 'all' ? ' active' : ''; ?>">All</a>
        <a href="<?php echo buildUrl(['filter' => 'published']); ?>" class="filter-tab<?php echo $current_filter === 'published' ? ' active' : ''; ?>">Published</a>
        <a href="<?php echo buildUrl(['filter' => 'draft']); ?>" class="filter-tab<?php echo $current_filter === 'draft' ? ' active' : ''; ?>">Draft</a>
      </div>

      <form method="GET" action="<?php echo basename(__FILE__); ?>" style="display:inline;">
        <?php if ($current_filter !== 'all'): ?><input type="hidden" name="filter" value="<?php echo htmlspecialchars($current_filter); ?>"><?php endif; ?>
        <?php if (!empty($search_query)): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>"><?php endif; ?>
        <?php if ($current_subject_id > 0): ?><input type="hidden" name="subject_id" value="<?php echo $current_subject_id; ?>"><?php endif; ?>
        <select class="sort-select" name="sort" onchange="this.form.submit()">
          <option value="newest" <?php echo $current_sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
          <option value="oldest" <?php echo $current_sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
          <option value="due-soon" <?php echo $current_sort === 'due-soon' ? 'selected' : ''; ?>>Due Soon</option>
          <option value="name-az" <?php echo $current_sort === 'name-az' ? 'selected' : ''; ?>>Name A–Z</option>
          <option value="name-za" <?php echo $current_sort === 'name-za' ? 'selected' : ''; ?>>Name Z–A</option>
        </select>
      </form>
    </div>

    <div class="task-grid">
      <?php if (empty($tasks)): ?>
      <div class="empty-state" style="grid-column:1/-1">
        <div class="empty-icon">🎬</div>
        <p>No performance tasks found</p>
        <span><?php echo empty($teacher_subjects) ? 'Create a subject first, then add a performance task.' : 'Click "+ New Performance Task" to add one.'; ?></span>
      </div>
      <?php else: ?>
        <?php foreach ($tasks as $t):
          $task_id     = (int)$t['task_id'];
          $title       = htmlspecialchars($t['title']);
          $desc_short  = htmlspecialchars(mb_strlen($t['description']) > 140 ? mb_substr($t['description'], 0, 140) . '…' : $t['description']);
          $subj_label  = htmlspecialchars($t['subject_name'] ?? 'Unknown Subject');
          $fg = formatGradeLabel($t['grade_level'] ?? '');
          if (!empty($fg)) $subj_label .= ' (' . $fg . ')';
          $due_info    = getDueLabel($t['due_date']);
          $is_overdue  = ($due_info['class'] === 'expired');
          $status      = $t['status'];
          $statusBadge = $status === 'published' ? 'badge-green' : 'badge-amber';
          $statusLabel = $status === 'published' ? 'Published' : 'Draft';
          if ($is_overdue && $status === 'published') { $statusBadge = 'badge-red'; $statusLabel = 'Past Due'; }
          $submitted   = (int)$t['submitted_count'];
          $graded      = (int)$t['graded_count'];
          $totalStud   = (int)$t['total_students'];
          $pct         = $totalStud > 0 ? round(($submitted / $totalStud) * 100) : 0;
        ?>
        <div class="task-card">
          <div class="task-card-top">
            <div style="min-width:0;">
              <div class="task-title">🎬 <?php echo $title; ?></div>
              <div style="font-size:11px;color:var(--text3);margin-top:2px;"><?php echo $subj_label; ?></div>
            </div>
            <span class="badge <?php echo $statusBadge; ?>" style="flex-shrink:0;"><?php echo $statusLabel; ?></span>
          </div>
          <div class="task-desc"><?php echo nl2br($desc_short); ?></div>
          <div class="expiry-info <?php echo $due_info['class']; ?>"><?php echo $is_overdue ? '⏰' : '📅'; ?> <?php echo $due_info['label']; ?></div>

          <div>
            <div class="task-stats" style="margin-bottom:4px;">
              <span>👥 <?php echo $submitted; ?><?php echo $totalStud > 0 ? '/' . $totalStud : ''; ?> submitted</span>
              <span>✅ <?php echo $graded; ?> graded</span>
              <span>🎯 Max: <?php echo (int)$t['max_score']; ?> pts</span>
            </div>
            <?php if ($totalStud > 0): ?>
            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:<?php echo $pct; ?>%;"></div></div>
            <?php endif; ?>
          </div>

          <div class="task-actions">
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('viewSubModal_<?php echo $task_id; ?>').classList.add('open');document.body.style.overflow='hidden';">👁 View Submissions (<?php echo $submitted; ?>)</button>
            <a href="<?php echo buildUrl(['edit' => $task_id]); ?>" class="btn btn-ghost btn-sm">✏️ Edit</a>
            <?php if (!$is_overdue): ?>
              <?php if ($status === 'draft'): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Publish this task? Students will see it.')">
                <input type="hidden" name="update_task_status" value="1">
                <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                <input type="hidden" name="new_status" value="published">
                <button type="submit" class="btn btn-ghost btn-sm">🟢 Publish</button>
              </form>
              <?php else: ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Unpublish this task?')">
                <input type="hidden" name="update_task_status" value="1">
                <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                <input type="hidden" name="new_status" value="draft">
                <button type="submit" class="btn btn-ghost btn-sm">📝 Unpublish</button>
              </form>
              <?php endif; ?>
            <?php endif; ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete &quot;<?php echo addslashes($title); ?>&quot;? This cannot be undone.')">
              <input type="hidden" name="delete_task" value="1">
              <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
              <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red,#ef4444);">🗑 Delete</button>
            </form>
          </div>
        </div>

        <!-- VIEW SUBMISSIONS MODAL for this task -->
        <div class="modal-overlay<?php echo ($view_task_id === $task_id) ? ' open' : ''; ?>" id="viewSubModal_<?php echo $task_id; ?>">
          <div class="modal modal-large" style="max-width:680px;width:92vw;max-height:88vh;display:flex;flex-direction:column;">
            <div class="modal-header" style="flex-shrink:0;">
              <div class="modal-title">🎬 <?php echo $title; ?> — Submissions</div>
              <button class="modal-close" onclick="document.getElementById('viewSubModal_<?php echo $task_id; ?>').classList.remove('open');document.body.style.overflow='';">✕</button>
            </div>
            <div class="modal-body" style="overflow-y:auto;flex:1;padding:16px;">
              <div style="margin-bottom:14px;padding:10px 12px;border-radius:10px;background:var(--bg2,rgba(255,255,255,.03));border:1px solid var(--border,rgba(255,255,255,.07));font-size:12.5px;color:var(--text2);white-space:pre-wrap;">
                <strong style="color:var(--text);">📋 Activity Instructions:</strong><br><?php echo nl2br(htmlspecialchars($t['description'])); ?>
              </div>
              <?php
                $rows = $submissions_by_task[$task_id] ?? [];
                if (empty($rows)):
              ?>
                <div style="text-align:center;padding:30px;color:var(--text3);font-size:13px;">📭 No student has submitted a video yet.</div>
              <?php else: foreach ($rows as $sb):
                  $sb_fn = htmlspecialchars($sb['firstname'] ?? '');
                  $sb_ln = htmlspecialchars($sb['lastname'] ?? '');
                  $sb_name = trim("$sb_fn $sb_ln") ?: 'Unknown Student';
                  $sb_status = $sb['status'];
                  $sb_video_url = '../' . htmlspecialchars($sb['video_path']);
              ?>
              <div class="submission-row">
                <video controls preload="metadata"><source src="<?php echo $sb_video_url; ?>"></video>
                <div style="flex:1;min-width:180px;">
                  <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:2px;"><?php echo htmlspecialchars($sb_name); ?>
                    <?php if ($sb_status === 'graded'): ?><span class="badge badge-green" style="margin-left:6px;">Graded</span><?php else: ?><span class="badge badge-amber" style="margin-left:6px;">Pending Review</span><?php endif; ?>
                  </div>
                  <div style="font-size:11px;color:var(--text3);margin-bottom:6px;">📅 Submitted <?php echo date('M d, Y g:i A', strtotime($sb['submitted_at'])); ?></div>
                  <?php if (!empty($sb['notes'])): ?>
                  <div style="font-size:11.5px;color:var(--text2);margin-bottom:6px;">📝 Note: <?php echo htmlspecialchars($sb['notes']); ?></div>
                  <?php endif; ?>
                  <form method="POST" action="<?php echo basename(__FILE__); ?>" style="display:flex;gap:6px;align-items:flex-end;flex-wrap:wrap;">
                    <input type="hidden" name="grade_submission" value="1">
                    <input type="hidden" name="submission_id" value="<?php echo (int)$sb['submission_id']; ?>">
                    <input type="hidden" name="view_task" value="<?php echo $task_id; ?>">
                    <div class="form-group" style="margin-bottom:0;flex:0 0 90px;">
                      <label style="font-size:10px;">Score /<?php echo (int)$t['max_score']; ?></label>
                      <input type="number" name="score" min="0" max="<?php echo (int)$t['max_score']; ?>" value="<?php echo $sb['score'] !== null ? (int)$sb['score'] : ''; ?>" style="padding:6px 8px;font-size:12px;">
                    </div>
                    <div class="form-group" style="margin-bottom:0;flex:1;min-width:140px;">
                      <label style="font-size:10px;">Feedback</label>
                      <input type="text" name="feedback" value="<?php echo htmlspecialchars($sb['feedback'] ?? ''); ?>" placeholder="Optional comments..." style="padding:6px 8px;font-size:12px;">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Save Grade</button>
                  </form>
                </div>
              </div>
              <?php endforeach; endif; ?>
            </div>
            <div class="modal-footer" style="flex-shrink:0;">
              <button class="btn btn-ghost" onclick="document.getElementById('viewSubModal_<?php echo $task_id; ?>').classList.remove('open');document.body.style.overflow='';">Close</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</main>
</div>

<!-- CREATE TASK MODAL -->
<div class="modal-overlay" id="createTaskModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">🎬 New Performance Task</div>
      <button class="modal-close" onclick="document.getElementById('createTaskModal').classList.remove('open');document.body.style.overflow='';">✕</button>
    </div>
    <form method="POST" action="<?php echo basename(__FILE__); ?>">
      <input type="hidden" name="create_task" value="1">
      <div class="modal-body">
        <div class="form-group">
          <label>Subject *</label>
          <select name="subject_id" required>
            <option value="">Select subject</option>
            <?php foreach ($teacher_subjects as $ts): ?>
            <option value="<?php echo $ts['subject_id']; ?>"><?php echo htmlspecialchars($ts['subject']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Task Title *</label>
          <input type="text" name="title" placeholder="e.g. Hand-washing Demonstration Video" required>
        </div>
        <div class="form-group">
          <label>Activity Instructions *</label>
          <textarea name="description" placeholder="Describe exactly what the student needs to perform/do for this video (steps, requirements, criteria, time limit, etc.)" style="min-height:160px;" required></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Max Score *</label>
            <input type="number" name="max_score" min="1" value="100" required>
          </div>
          <div class="form-group">
            <label>Due Date <small style="color:var(--text3);font-weight:400;">(optional)</small></label>
            <input type="datetime-local" name="due_date">
          </div>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="published">Published (visible to students)</option>
            <option value="draft">Draft (hidden)</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('createTaskModal').classList.remove('open');document.body.style.overflow='';">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Task</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT TASK MODAL -->
<div class="modal-overlay<?php echo $edit_mode ? ' open' : ''; ?>" id="editTaskModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">✏️ Edit Performance Task</div>
      <a href="<?php echo buildUrl(['edit' => null]); ?>" class="modal-close" style="text-decoration:none;color:inherit;">✕</a>
    </div>
    <?php if ($editing_task): ?>
    <?php
      $edit_due_val = '';
      if (!empty($editing_task['due_date'])) {
          $edt = new DateTime($editing_task['due_date'], new DateTimeZone('Asia/Manila'));
          $edit_due_val = $edt->format('Y-m-d\TH:i');
      }
    ?>
    <form method="POST" action="<?php echo buildUrl(['edit' => null]); ?>">
      <input type="hidden" name="update_task" value="1">
      <input type="hidden" name="task_id" value="<?php echo $editing_task['task_id']; ?>">
      <div class="modal-body">
        <div class="form-group">
          <label>Subject</label>
          <input type="text" value="<?php echo htmlspecialchars($editing_task['subject'] ?? ''); ?>" disabled style="opacity:0.6;">
        </div>
        <div class="form-group">
          <label>Task Title *</label>
          <input type="text" name="edit_title" value="<?php echo htmlspecialchars($editing_task['title']); ?>" required>
        </div>
        <div class="form-group">
          <label>Activity Instructions *</label>
          <textarea name="edit_description" style="min-height:160px;" required><?php echo htmlspecialchars($editing_task['description']); ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Max Score *</label>
            <input type="number" name="edit_max_score" min="1" value="<?php echo (int)$editing_task['max_score']; ?>" required>
          </div>
          <div class="form-group">
            <label>Due Date</label>
            <input type="datetime-local" name="edit_due_date" value="<?php echo $edit_due_val; ?>">
          </div>
        </div>
        <?php if (!empty($editing_task['due_date'])): ?>
        <div class="form-group">
          <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:0.82rem;color:var(--red);">
            <input type="checkbox" name="remove_due" value="1" onchange="this.form.edit_due_date.value=''"> Remove due date
          </label>
        </div>
        <?php endif; ?>
        <div class="form-group">
          <label>Status</label>
          <select name="edit_status">
            <option value="published" <?php echo $editing_task['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
            <option value="draft" <?php echo $editing_task['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <a href="<?php echo buildUrl(['edit' => null]); ?>" class="btn btn-ghost" style="text-decoration:none;">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<!-- NOTIFICATION MODAL -->
<div class="modal-overlay" id="notifModal">
  <div class="modal" style="max-width:460px;max-height:80vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="flex-shrink:0;display:flex;align-items:center;justify-content:space-between;">
      <div class="modal-title">🔔 Notifications <span id="notif-unread-count" style="font-size:11px;font-weight:500;color:var(--text3);margin-left:6px;"><?= $unread_count > 0 ? "($unread_count unread)" : '' ?></span></div>
      <div style="display:flex;gap:8px;align-items:center;">
        <?php if ($unread_count > 0): ?><button class="btn btn-ghost btn-sm" onclick="markAllRead()" style="font-size:11px;padding:4px 10px;">✓ Mark all read</button><?php endif; ?>
        <button class="modal-close" onclick="closeNotifModal()">✕</button>
      </div>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1;padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
      <?php if (empty($notifications)): ?>
        <div style="text-align:center;padding:40px 16px;color:var(--text3);"><div style="font-size:36px;margin-bottom:8px;">🔕</div><div style="font-size:13px;">No notifications yet.</div></div>
      <?php else: foreach ($notifications as $notif):
          $n_ts = strtotime($notif['created_at'] . ' Asia/Manila');
          $n_diff = time() - $n_ts;
          if ($n_diff < 60) $n_time = 'Just now';
          elseif ($n_diff < 3600) $n_time = floor($n_diff/60).'m ago';
          elseif ($n_diff < 86400) $n_time = floor($n_diff/3600).'h ago';
          elseif ($n_diff < 604800) $n_time = floor($n_diff/86400).'d ago';
          else $n_time = date('M j, Y', $n_ts);
          $is_unread = $notif['status'] === 'unread';
          $n_icon = match($notif['type']) {
            'new_lesson' => '📖', 'new_quiz' => '📝', 'quiz_passed' => '🎉', 'quiz_failed' => '😞',
            'quiz_due_soon' => '⏰', 'quiz_overdue' => '🚨', 'new_subject_assigned' => '📚',
            'student_submitted_quiz' => '📬', 'new_announcement' => '📢', 'lesson_expiring' => '⌛',
            'lesson_expired' => '🔒', default => '🔔',
          };
      ?>
      <div class="notif-item" data-id="<?= $notif['notification_id'] ?>" data-url="<?= htmlspecialchars(getNotifUrl($notif['type'])) ?>" onclick="markOneRead(<?= $notif['notification_id'] ?>, this)"
        style="display:flex;gap:12px;align-items:flex-start;padding:10px 12px;border-radius:10px;cursor:pointer;background:<?= $is_unread ? 'rgba(99,102,241,0.08)' : 'var(--bg2,rgba(255,255,255,.03))' ?>;border:1px solid <?= $is_unread ? 'rgba(99,102,241,0.2)' : 'var(--border,rgba(255,255,255,.07))' ?>;">
        <div style="font-size:20px;flex-shrink:0;"><?= $n_icon ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:<?= $is_unread ? '700' : '500' ?>;color:var(--text);margin-bottom:2px;"><?= htmlspecialchars($notif['title']) ?><?php if ($is_unread): ?><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--accent,#6366f1);margin-left:5px;"></span><?php endif; ?></div>
          <div style="font-size:12px;color:var(--text3);margin-bottom:4px;"><?= htmlspecialchars($notif['message']) ?></div>
          <div style="display:flex;justify-content:space-between;"><div style="font-size:10px;color:var(--text3);opacity:.7;"><?= $n_time ?></div>
          <button onclick="deleteNotif(<?= $notif['notification_id'] ?>, this, event)" style="font-size:10px;padding:2px 8px;border-radius:6px;border:none;background:rgba(239,68,68,0.12);color:#ef4444;cursor:pointer;">🗑 Delete</button></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<div class="modal-overlay" id="logoutModal">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><div class="modal-title">Confirm Logout</div><button class="modal-close" onclick="closeLogoutModal()">✕</button></div>
    <div class="modal-body" style="text-align:center;padding:28px 20px"><div style="font-size:48px;margin-bottom:12px">👋</div><div style="font-size:15px;color:var(--text);font-weight:500;margin-bottom:6px">Are you sure you want to log out?</div><div style="font-size:13px;color:var(--text3)">Any unsaved changes will be lost.</div></div>
    <div class="modal-footer" style="justify-content:center;padding:0 20px 24px"><button class="btn btn-ghost" onclick="closeLogoutModal()">Cancel</button><button class="btn btn-danger" onclick="confirmLogout()">Yes, Log Out</button></div>
  </div>
</div>

<style>
.flash-msg { display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-radius:10px;margin-bottom:16px;font-size:0.9rem;font-weight:500; }
.flash-success { background:rgba(34,197,94,0.15);color:var(--green);border:1px solid rgba(34,197,94,0.3); }
.flash-error { background:rgba(239,68,68,0.15);color:var(--red);border:1px solid rgba(239,68,68,0.3); }
.flash-warning { background:rgba(245,166,35,0.15);color:var(--amber);border:1px solid rgba(245,166,35,0.3); }
.expiry-info { font-size:0.75rem; display:flex; align-items:center; gap:4px; }
.expiry-info.expired { color:var(--red,#ef4444); font-weight:600; }
.expiry-info.expiring-soon { color:var(--amber,#f5a623); font-weight:500; }
.expiry-info.active-expiry { color:var(--text3,#9ca3af); }
</style>

<script>
var sidebar=document.getElementById('sidebar');
var overlay=document.getElementById('sidebar-overlay');
var menuBtn=document.getElementById('menuBtn');
function openSidebar(){sidebar.classList.add('open');overlay.classList.add('open');document.body.style.overflow='hidden';}
function closeSidebar(){sidebar.classList.remove('open');overlay.classList.remove('open');document.body.style.overflow='';}
menuBtn.addEventListener('click',function(){sidebar.classList.contains('open')?closeSidebar():openSidebar();});
overlay.addEventListener('click',closeSidebar);

document.getElementById('createTaskModal').addEventListener('click',function(e){if(e.target===this){this.classList.remove('open');document.body.style.overflow='';}});
document.getElementById('editTaskModal').addEventListener('click',function(e){if(e.target===this){window.location.href='<?php echo buildUrl(["edit"=>null]); ?>';}});
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogoutModal();});
document.querySelectorAll('[id^="viewSubModal_"]').forEach(function(m){
  m.addEventListener('click', function(e){ if(e.target===this){ this.classList.remove('open'); document.body.style.overflow=''; } });
});

function openNotifModal(){document.getElementById('notifModal').classList.add('open');document.body.style.overflow='hidden';}
function closeNotifModal(){document.getElementById('notifModal').classList.remove('open');document.body.style.overflow='';}
document.getElementById('notifModal').addEventListener('click',function(e){if(e.target===this)closeNotifModal();});

function markOneRead(id, el) {
  var url = el.dataset.url || '#';
  fetch('notif_mark_read.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'notification_id='+id });
  if (!el.dataset.read) {
    el.style.background='var(--bg2,rgba(255,255,255,.03))'; el.style.border='1px solid var(--border,rgba(255,255,255,.07))';
    var dot=el.querySelector('span[style*="border-radius:50%"]'); var title=el.querySelector('div[style*="font-weight"]');
    if(dot) dot.remove(); if(title) title.style.fontWeight='500';
    el.dataset.read='1'; updateBadgeCount(-1);
  }
  if(url && url!=='#') setTimeout(function(){ window.location.href=url; },150);
}
function markAllRead() {
  fetch('notif_mark_read.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'mark_all=1' });
  document.querySelectorAll('.notif-item').forEach(function(el){
    el.style.background='var(--bg2,rgba(255,255,255,.03))'; el.style.border='1px solid var(--border,rgba(255,255,255,.07))';
    var dot=el.querySelector('span[style*="border-radius:50%"]'); var title=el.querySelector('div[style*="font-weight"]');
    if(dot) dot.remove(); if(title) title.style.fontWeight='500'; el.dataset.read='1';
  });
  updateBadgeCount(0); document.getElementById('notif-unread-count').textContent='';
}
function updateBadgeCount(delta) {
  var badge=document.getElementById('notif-badge'); if(!badge) return;
  var current=parseInt(badge.textContent)||0; var next=delta===0?0:Math.max(0,current+delta);
  if(next<=0){ badge.style.display='none'; } else { badge.textContent=next; badge.style.display='flex'; }
}
function deleteNotif(id, btn, e) {
  e.stopPropagation();
  var item=btn.closest('.notif-item');
  fetch('notif_delete.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'notification_id='+id });
  item.style.transition='opacity .25s, transform .25s'; item.style.opacity='0'; item.style.transform='translateX(30px)';
  setTimeout(function(){ item.remove(); },250);
}

function openLogoutModal(){document.getElementById('logoutModal').classList.add('open');document.body.style.overflow='hidden';}
function closeLogoutModal(){document.getElementById('logoutModal').classList.remove('open');document.body.style.overflow='';}
function confirmLogout(){window.location.href='../logout.php';}

setTimeout(function(){
  var flashes=document.querySelectorAll('.flash-msg');
  flashes.forEach(function(f){ f.style.opacity='0'; f.style.transform='translateY(-8px)'; f.style.transition='all .3s ease'; setTimeout(function(){f.remove()},300); });
},6000);

var resizeTimer;
window.addEventListener('resize',function(){clearTimeout(resizeTimer);resizeTimer=setTimeout(function(){if(window.innerWidth>768)closeSidebar();},150);});
</script>
</body>
</html>