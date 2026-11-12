<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../connection.php';
date_default_timezone_set('Asia/Manila');
$teacher_id = $_SESSION['teacher_id'];

$stmt = $conn->prepare("SELECT theme FROM tblteacher_settings WHERE teacher_id = ? LIMIT 1");
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$db_theme = $stmt->get_result()->fetch_assoc()['theme'] ?? 'dark';
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_announcement') {
    $title   = trim($_POST['title']   ?? '');
    $message = trim($_POST['message'] ?? '');
    $type    = $_POST['type'] ?? 'general';
    $user_id = $_SESSION['teacher_id'];

    if ($title && $message) {
        $stmt = $conn->prepare("INSERT INTO tblannouncements (user_id, title, message, type, category, created_at) VALUES (?, ?, ?, ?, 'for_student', CONVERT_TZ(NOW(), 'SYSTEM', 'Asia/Manila'))");
        $stmt->bind_param('isss', $user_id, $title, $message, $type);
        if ($stmt->execute()) {
            $announce_toast = ['msg' => 'Announcement posted!', 'type' => 'success'];

            $workerUrl = 'https://excelsiorpolytechnicacademy.com/excelearn/background_worker/background_worker.php?task=announcements';
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
            $announce_toast = ['msg' => 'Failed to post announcement.', 'type' => 'error'];
        }
        $stmt->close();
    } else {
        $announce_toast = ['msg' => 'Please fill in all fields.', 'type' => 'error'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_announcement') {
    $ann_id  = (int)($_POST['ann_id'] ?? 0);
    $title   = trim($_POST['title']   ?? '');
    $message = trim($_POST['message'] ?? '');
    $type    = $_POST['type'] ?? 'general';
    $user_id = (int)$_SESSION['teacher_id'];

    if ($ann_id && $title && $message) {
        $stmt = $conn->prepare("UPDATE tblannouncements SET title=?, message=?, type=?, category='for_student' WHERE id=? AND user_id=?");
        $stmt->bind_param('sssii', $title, $message, $type, $ann_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $announce_toast = ['msg' => 'Announcement updated!', 'type' => 'success'];
        } else {
            $announce_toast = ['msg' => 'Update failed — announcement not found or not yours.', 'type' => 'error'];
        }
        $stmt->close();
    } else {
        $announce_toast = ['msg' => 'Please fill in all fields.', 'type' => 'error'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_announcement') {
    $ann_id  = (int)($_POST['ann_id'] ?? 0);
    $user_id = (int)$_SESSION['teacher_id'];

    if ($ann_id) {
        $stmt = $conn->prepare("DELETE FROM tblannouncements WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $ann_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $announce_toast = ['msg' => 'Announcement deleted.', 'type' => 'info'];
        } else {
            $announce_toast = ['msg' => 'Delete failed — announcement not found or not yours.', 'type' => 'error'];
        }
        $stmt->close();
    }
}

$teacher_id = $_SESSION['teacher_id'];

$sql_user = "
    SELECT id, profile_pic, email, firstname, middlename, lastname,
           age, gender, contact_number, address, role, status
    FROM   tblusers
    WHERE  id = ? AND role = 'teacher'
    LIMIT  1
";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$teacher_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($teacher_row) {
    $first  = $teacher_row['firstname']  ?? '';
    $middle = $teacher_row['middlename'] ?? '';
    $last   = $teacher_row['lastname']   ?? '';
    $teacher_name = trim("$first $middle $last");
    if (!$teacher_name) $teacher_name = $teacher_row['email'] ?? 'Teacher';
    $teacher_dept = $_SESSION['teacher_dept'] ?? 'Faculty';
    $teacher_pic = !empty($teacher_row['profile_pic'])
      ? '../uploads/profile_pic/teachers/' . $teacher_row['profile_pic']
      : null;
} else {
    $teacher_name = 'Teacher';
    $teacher_dept = 'Faculty';
    $teacher_pic  = null;
}

$words = array_filter(explode(' ', $teacher_name));
$teacher_initials = implode('', array_map(fn($w) => strtoupper($w[0]), $words));

date_default_timezone_set('Asia/Manila');
$hour = (int) date('H');
if ($hour < 12)      { $greeting = 'Good morning';   $g_emoji = '☀️';  $greeting_bg = '#fef9c3'; $greeting_color = '#854d0e'; }
elseif ($hour < 18)  { $greeting = 'Good afternoon'; $g_emoji = '🌤️'; $greeting_bg = '#dbeafe'; $greeting_color = '#1e40af'; }
else                 { $greeting = 'Good evening';   $g_emoji = '🌙';  $greeting_bg = '#1e1b4b'; $greeting_color = '#a5b4fc'; }
$sql_subjects = "
    SELECT ts.teacher_subject_id,
           ts.subject_id,
           s.subject AS subject_name,
           ts.created_at
    FROM   tbl_teacher_subjects ts
    INNER JOIN tblsubjects s ON s.subject_id = ts.subject_id
    WHERE  ts.teacher_id = ?
    ORDER  BY ts.created_at ASC
";
$stmt = $conn->prepare($sql_subjects);
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$active_courses = count($subjects);
$subjectMap = [];
foreach ($subjects as $s) $subjectMap[$s['teacher_subject_id']] = $s;
$tsids = array_column($subjects, 'teacher_subject_id');

$lessons = [];
if ($tsids) {
    $in    = implode(',', array_fill(0, count($tsids), '?'));
    $types = str_repeat('i', count($tsids));
    $stmt  = $conn->prepare("
        SELECT lesson_id, teacher_subject_id, title, description,
               duration, resource_type, video_path, video_filename,
               status, order_index, created_at, updated_at
        FROM   tbl_teacher_lessons
        WHERE  teacher_subject_id IN ($in)
        ORDER  BY teacher_subject_id, order_index ASC
    ");
    $stmt->bind_param($types, ...$tsids);
    $stmt->execute();
    $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$lessonsBySubject = [];
foreach ($lessons as $l) $lessonsBySubject[$l['teacher_subject_id']][] = $l;

$stmt = $conn->prepare("
    SELECT id, student_id, teacher_subject_id, created_at
    FROM   tbl_teachers_students
    WHERE  teacher_id = ?
");
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$all_student_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unique_students = count(array_unique(array_column($all_student_rows, 'student_id')));
$studentsBySubject = [];
foreach ($all_student_rows as $row) $studentsBySubject[$row['teacher_subject_id']][] = $row['student_id'];

$quizzes = [];
$lessonIds = array_column($lessons, 'lesson_id');
if ($lessonIds) {
    $in2    = implode(',', array_fill(0, count($lessonIds), '?'));
    $types2 = str_repeat('i', count($lessonIds));
    $stmt   = $conn->prepare("
        SELECT q.quiz_id, q.lesson_id, q.description, q.duration,
               q.question_count, q.passing_score, q.due_date,
               q.status, q.created_at, q.updated_at
        FROM   tblquizzes q
        WHERE  q.lesson_id IN ($in2)
        ORDER  BY q.due_date ASC
    ");
    $stmt->bind_param($types2, ...$lessonIds);
    $stmt->execute();
    $quizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$pending_quizzes    = array_filter($quizzes, fn($q) => in_array(strtolower($q['status']), ['draft','pending']));
$pending_quiz_count = count($pending_quizzes);
$pending_reviews    = $pending_quiz_count;

$lessonSubjectMap = [];
foreach ($lessons as $l) $lessonSubjectMap[$l['lesson_id']] = $l['teacher_subject_id'];

$teacher_user_id = (int)$_SESSION['teacher_id'];

$ann_page     = max(1, (int)($_GET['ann_page'] ?? 1));
$ann_per_page = 3;
$ann_offset   = ($ann_page - 1) * $ann_per_page;
$ann_search   = trim($_GET['ann_search'] ?? '');
$ann_search_param = '%' . $ann_search . '%';

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM tblannouncements a
    WHERE (a.category IN ('for_teacher', 'both') OR a.user_id = ?)
      AND (? = '%%' OR a.title LIKE ? OR a.message LIKE ?)
");
$stmt->bind_param('isss', $teacher_user_id, $ann_search_param, $ann_search_param, $ann_search_param);
$stmt->execute();
$ann_total     = (int)$stmt->get_result()->fetch_row()[0];
$ann_pages     = max(1, (int)ceil($ann_total / $ann_per_page));
$stmt->close();

$stmt = $conn->prepare("
    SELECT a.id, a.user_id, a.title, a.message, a.type, a.category, a.created_at,
           u.firstname, u.lastname, u.role AS poster_role
    FROM   tblannouncements a
    INNER JOIN tblusers u ON u.id = a.user_id
    WHERE (a.category IN ('for_teacher', 'both') OR a.user_id = ?)
      AND (? = '%%' OR a.title LIKE ? OR a.message LIKE ?)
    ORDER  BY a.created_at DESC
    LIMIT  ? OFFSET ?
");
$stmt->bind_param('isssii', $teacher_user_id, $ann_search_param, $ann_search_param, $ann_search_param, $ann_per_page, $ann_offset);
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("
    SELECT id, title, message, type, created_at
    FROM tblannouncements
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->bind_param('i', $teacher_user_id);
$stmt->execute();
$teacher_announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$courseIcons  = ['🧬','⚗️','🌍','⚛️','🔬','📐','🧪','📡'];
$courseColors = ['blue','green','teal','amber','purple','cyan','red','pink'];

$coursesData = [];
foreach ($subjects as $i => $subj) {
    $tsid     = $subj['teacher_subject_id'];
    $sLessons = $lessonsBySubject[$tsid] ?? [];
    $total    = count($sLessons);
    $done     = count(array_filter($sLessons, fn($l) => strtolower($l['status']) === 'published'));
    $pct      = $total > 0 ? round($done / $total * 100) : 0;
    $stCount  = count($studentsBySubject[$tsid] ?? []);
    $coursesData[] = [
        'title'    => $subj['subject_name'],
        'icon'     => $courseIcons[$i % count($courseIcons)],
        'students' => $stCount,
        'lessons'  => $total,
        'completed'=> $done,
        'progress' => $pct,
        'color'    => $courseColors[$i % count($courseColors)],
    ];
}

$today = date('Y-m-d');
$upcomingDeadlines = [];
foreach ($quizzes as $q) {
    if (!empty($q['due_date']) && $q['due_date'] >= $today) {
        $diff    = (strtotime($q['due_date']) - time()) / 86400;
        $urgency = $diff <= 1 ? 'urgent' : ($diff <= 3 ? 'soon' : 'later');
        $tsid    = $lessonSubjectMap[$q['lesson_id']] ?? null;
        $upcomingDeadlines[] = [
            'title'   => ($q['description'] ?: 'Quiz') . ' — Due',
            'course'  => $tsid ? "Subject #{$subjectMap[$tsid]['subject_id']}" : '—',
            'due'     => date('M j, g:i A', strtotime($q['due_date'])),
            'urgency' => $urgency,
        ];
    }
}
usort($upcomingDeadlines, fn($a,$b) => strcmp($a['urgency'], $b['urgency']));
$upcomingDeadlines = array_slice($upcomingDeadlines, 0, 5);

$staticDeadlines = [
    ['title'=>'Biology Ch.5 Quiz — Grade & Release','course'=>'Introduction to Biology','due'=>'Today, 5:00 PM','urgency'=>'urgent'],
    ['title'=>'Chemistry Problem Set #5 — Review','course'=>'Advanced Chemistry','due'=>'Tomorrow, 11:59 PM','urgency'=>'urgent'],
    ['title'=>'Physics Lab Report #2 — Deadline','course'=>'General Physics','due'=>'Dec 18, 5:00 PM','urgency'=>'soon'],
];
$deadlinesToShow = $upcomingDeadlines ?: $staticDeadlines;

$statsData = [
    ['icon'=>'📚','value'=>$active_courses,'label'=>'Active Subjects','change'=>'↑ this semester','dir'=>'up','color'=>'blue'],
    ['icon'=>'👨‍🎓','value'=>$unique_students,'label'=>'Total Students','change'=>'enrolled','dir'=>'up','color'=>'green'],
    ['icon'=>'📝','value'=>$pending_reviews,'label'=>'Pending Reviews','change'=>$pending_quiz_count.' quiz(zes) to check','dir'=>'down','color'=>'amber'],
    ['icon'=>'📖','value'=>count($lessons),'label'=>'Total Lessons','change'=>count(array_filter($lessons,fn($l)=>strtolower($l['status'])==='published')).' published','dir'=>'up','color'=>'purple'],
];

function progressFillClass(int $pct): string {
    if ($pct >= 90) return 'green-fill';
    if ($pct >= 60) return 'blue-fill';
    if ($pct >= 40) return 'amber-fill';
    return 'red-fill';
}
function progressColor(int $pct): string {
    if ($pct >= 90) return 'var(--green)';
    if ($pct >= 60) return 'var(--accent)';
    if ($pct >= 40) return 'var(--amber)';
    return 'var(--red)';
}

function announcementMeta(string $type): array {
    return match(strtolower($type)) {
        'alert','warning' => ['🚨','badge-red'],
        'info'            => ['📢','badge-blue'],
        'success'         => ['✅','badge-green'],
        'event'           => ['📅','badge-cyan'],
        default           => ['📣','badge-amber'],
    };
}

$sql_quizzes = "
    SELECT COUNT(*) AS total_quizzes
    FROM tblquizzes q
    INNER JOIN tbl_teacher_lessons l
        ON q.lesson_id = l.lesson_id
    INNER JOIN tbl_teacher_subjects s
        ON l.teacher_subject_id = s.teacher_subject_id
    WHERE s.teacher_id = ?
";

$stmt = $conn->prepare($sql_quizzes);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total_quizzes = $row['total_quizzes'] ?? 0;

$stmt->close();

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

$sql_subjects = "
    SELECT ts.teacher_subject_id,
           ts.subject_id,
           s.subject AS subject_name,
           ts.created_at
    FROM   tbl_teacher_subjects ts
    INNER JOIN tblsubjects s ON s.subject_id = ts.subject_id
    WHERE  ts.teacher_id = ?
    ORDER  BY ts.created_at ASC
";
$stmt = $conn->prepare($sql_subjects);
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$active_courses = count($subjects);

$valid_subject_list = !empty($subjects)
    ? implode(',', array_map(fn($s) => (int)$s['teacher_subject_id'], $subjects))
    : '0';

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn LMS — Teacher Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/teacher/index.css">
<?php if (!empty($teacher_id)): ?>
<script>
  function registerOneSignal() {
    if (typeof median !== 'undefined' && median.onesignal) {
      median.onesignal.login("<?php echo $teacher_id; ?>");
      median.onesignal.tags.setTags({
        tags: { user_id: "<?php echo $teacher_id; ?>" }
      });
      console.log("✅ OneSignal tags set: user_id=<?php echo $teacher_id; ?>");
    } else if (typeof OneSignal !== 'undefined') {
      OneSignal.push(function() {
        OneSignal.sendTags({ user_id: "<?php echo $teacher_id; ?>" }, function(tagsSent) {
          console.log("✅ OneSignal web tags set:", tagsSent);
        });
      });
    } else {
      setTimeout(registerOneSignal, 300);
    }
  }
  document.addEventListener("median_deviceready", function() {
    registerOneSignal();
  });
  registerOneSignal();
</script>
<?php endif; ?>
</body>
<style>

.announce-list { display: flex; flex-direction: column; gap: 10px; }

.announce-item {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 12px 14px;
  border-radius: 10px;
  background: var(--bg2, rgba(255,255,255,.04));
  border: 1px solid var(--border, rgba(255,255,255,.08));
  transition: background .2s;
}
.announce-item:hover { background: var(--bg3, rgba(255,255,255,.07)); }

.announce-icon {
  font-size: 20px;
  line-height: 1;
  flex-shrink: 0;
  margin-top: 2px;
}

.announce-body { flex: 1; min-width: 0; }

.announce-title {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.announce-msg {
  font-size: 12px;
  color: var(--text3);
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.announce-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 5px;
}

.announce-date {
  font-size: 11px;
  color: var(--text3);
}

.empty-state {
  text-align: center;
  padding: 28px 16px;
  color: var(--text3);
  font-size: 13px;
}
.empty-state .empty-icon { font-size: 32px; margin-bottom: 6px; }

.modal-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.6);
    z-index:9999;
    align-items:center;
    justify-content:center;
}

.modal-overlay.open{
    display:flex;
}
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
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
.stat-card .stat-icon { font-size: 18px; margin-bottom: 4px; }
.stat-card .stat-value { font-size: 22px; line-height: 1.1; }
.stat-card .stat-label { font-size: 11px; margin-bottom: 2px; }
.stat-card .stat-change { font-size: 10px; }
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
<body class="<?= $db_theme === 'light' ? 'light' : '' ?>">
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
    <a class="nav-item active" href="index.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>

    <div class="nav-section-label" style="margin-top:14px">Teaching</div>
    <a class="nav-item" href="teacher_courses.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Subjects
      <span class="nav-badge" id="course-count-badge"><?php echo $active_courses; ?></span>
    </a>
    <a class="nav-item" href="teacher_performance_tasks.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>
      Performance Tasks <span class="nav-badge"><?php echo $total_tasks_badge; ?></span>
      <?php if ($pending_review_badge > 0): ?><span class="nav-badge" style="background:var(--red,#ef4444);color:#fff;margin-left:4px;"><?= $pending_review_badge ?></span><?php endif; ?>
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
      <?php if ($teacher_pic): ?>
      <img src="<?= htmlspecialchars($teacher_pic) ?>" alt="Profile"
           class="user-avatar" style="object-fit:cover; background:linear-gradient(135deg,var(--accent),var(--purple))">
      <?php else: ?>
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--accent),var(--purple))">
        <?= htmlspecialchars($teacher_initials) ?>
      </div>
      <?php endif; ?>
      <div class="user-info">
        <p><?= htmlspecialchars($teacher_name) ?></p>
        <span><?= htmlspecialchars($teacher_dept) ?> — Teacher</span>
      </div>
    </div>
  </div>
</aside>

<div class="sidebar-overlay" id="sidebar-overlay"></div>

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
    <div class="section-header">
      <div>
        <div class="section-title">
          <?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars(explode(' ', $teacher_name)[0]) ?>! <?= $g_emoji ?>
        </div>
        <div class="section-sub">
          <?php if ($pending_reviews > 0 || $pending_quiz_count > 0): ?>
            You have <?= $pending_reviews ?> pending review<?= $pending_reviews != 1 ? 's' : '' ?>
            <?= $pending_quiz_count > 0 ? "and $pending_quiz_count quiz(zes) to check." : '.' ?>
          <?php else: ?>
            Everything is up to date. Great job! 🎉
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div style="margin-bottom:5px">
      <div class="streak-grid" id="streak-grid"></div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
      <?php foreach ($statsData as $s): ?>
      <div class="stat-card <?= $s['color'] ?>" style="padding:12px 14px;">
        <div class="stat-icon"><?= $s['icon'] ?></div>
        <div class="stat-value"><?= htmlspecialchars((string)$s['value']) ?></div>
        <div class="stat-label"><?= htmlspecialchars($s['label']) ?></div>
        <div class="stat-change <?= $s['dir'] ?>"><?= $s['dir'] === 'up' ? '↑' : '↓' ?> <?= htmlspecialchars($s['change']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    
    <div class="card" style="margin-bottom:20px">
      <div class="card-header">
        <div class="card-title">📣 Announcements</div>
        <button class="btn btn-primary btn-sm" onclick="openAnnounceModal()">+ Post</button>
      </div>
    
      <!-- Search bar -->
      <form method="GET" action="index.php" id="ann-search-form"
            style="padding:0 0 12px 0;display:flex;gap:8px;align-items:center;">
        <input type="text" name="ann_search" id="ann-search-input"
               value="<?= htmlspecialchars($ann_search) ?>"
               placeholder="🔍 Search announcements..."
               style="flex:1;padding:8px 12px;border-radius:8px;border:1px solid var(--border);
                      background:var(--bg2);color:var(--text);font-size:13px;outline:none;">
        <button type="submit" class="btn btn-ghost btn-sm">Search</button>
        <?php if ($ann_search): ?>
        <a href="index.php" class="btn btn-ghost btn-sm" style="color:var(--red)">✕ Clear</a>
        <?php endif; ?>
      </form>
    
      <?php if (empty($announcements)): ?>
        <div class="empty-state">
          <div class="empty-icon"><?= $ann_search ? '🔍' : '📭' ?></div>
          <?= $ann_search ? 'No announcements found for "'.htmlspecialchars($ann_search).'".' : 'No announcements yet.' ?>
        </div>
      <?php else: ?>
        <div class="announce-list">
          <?php foreach ($announcements as $ann):
            [$annIcon, $annBadge] = announcementMeta($ann['type'] ?? '');
            $dt   = new DateTime($ann['created_at'], new DateTimeZone('UTC'));
            $ts   = $dt->getTimestamp();
            $diff = time() - $ts;
            if ($diff < 60)         $timeAgo = 'Just now';
            elseif ($diff < 3600)   $timeAgo = floor($diff/60).'m ago';
            elseif ($diff < 86400)  $timeAgo = floor($diff/3600).'h ago';
            elseif ($diff < 604800) $timeAgo = floor($diff/86400).'d ago';
            else                    $timeAgo = date('M j, Y', $ts);
    
            $poster_role = strtolower($ann['poster_role'] ?? 'unknown');
            $poster_name = trim(($ann['firstname'] ?? '') . ' ' . ($ann['lastname'] ?? ''));
            $is_mine     = ((int)$ann['user_id'] === $teacher_user_id);
            $role_color  = $poster_role === 'admin' ? '#ef4444' : '#8b5cf6';
            $role_label  = $poster_role === 'admin' ? '🛡️ Admin' : '👨‍🏫 Teacher';
          ?>
          <div class="announce-item" style="align-items:flex-start">
            <div class="announce-icon"><?= $annIcon ?></div>
            <div class="announce-body">
              <div class="announce-title"><?= htmlspecialchars($ann['title']) ?></div>
              <div class="announce-msg"><?= htmlspecialchars($ann['message']) ?></div>
              <div class="announce-meta">
                <span class="badge <?= $annBadge ?>" style="font-size:10px;padding:1px 7px">
                  <?= htmlspecialchars(ucfirst($ann['type'] ?? 'general')) ?>
                </span>
                <span style="font-size:10px;padding:1px 8px;border-radius:20px;background:<?= $role_color ?>22;color:<?= $role_color ?>;font-weight:600;">
                  <?= $role_label ?>: <?= htmlspecialchars($poster_name) ?>
                </span>
                <span class="announce-date"><?= $timeAgo ?></span>
              </div>
            </div>
            <?php if ($is_mine): ?>
            <div style="display:flex;gap:6px;flex-shrink:0;margin-left:8px;margin-top:2px;">
              <button class="btn btn-ghost btn-sm"
                onclick='openEditModal(
                  <?= (int)$ann["id"] ?>,
                  <?= json_encode($ann["title"]) ?>,
                  <?= json_encode($ann["message"]) ?>,
                  <?= json_encode($ann["type"]) ?>
                )'
                style="padding:4px 10px;font-size:11px;">✏️ Edit</button>
              <button class="btn btn-sm"
                onclick='openDeleteModal(
                  <?= (int)$ann["id"] ?>,
                  <?= json_encode($ann["title"]) ?>
                )'
                style="padding:4px 10px;font-size:11px;background:var(--red);color:white;border:none;border-radius:6px;cursor:pointer;">🗑 Delete</button>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
    
        <!-- Pagination -->
        <?php if ($ann_pages > 1): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:12px;border-top:1px solid var(--border);">
          <div style="font-size:12px;color:var(--text3);">
            Page <?= $ann_page ?> of <?= $ann_pages ?>
            <span style="margin-left:4px;">(<?= $ann_total ?> total)</span>
          </div>
          <div style="display:flex;gap:6px;">
            <?php
            $base_url = 'index.php?ann_search=' . urlencode($ann_search) . '&ann_page=';
            $prev = max(1, $ann_page - 1);
            $next = min($ann_pages, $ann_page + 1);
            ?>
            <a href="<?= $base_url . $prev ?>"
               class="btn btn-ghost btn-sm"
               style="padding:4px 10px;font-size:12px;<?= $ann_page <= 1 ? 'opacity:.4;pointer-events:none;' : '' ?>">
              ← Prev
            </a>
            <?php for ($p = max(1, $ann_page - 2); $p <= min($ann_pages, $ann_page + 2); $p++): ?>
            <a href="<?= $base_url . $p ?>"
               class="btn btn-sm"
               style="padding:4px 10px;font-size:12px;
                      <?= $p === $ann_page
                        ? 'background:var(--accent);color:#fff;border:none;border-radius:6px;'
                        : 'background:var(--bg2);color:var(--text);border:1px solid var(--border);border-radius:6px;' ?>">
              <?= $p ?>
            </a>
            <?php endfor; ?>
            <a href="<?= $base_url . $next ?>"
               class="btn btn-ghost btn-sm"
               style="padding:4px 10px;font-size:12px;<?= $ann_page >= $ann_pages ? 'opacity:.4;pointer-events:none;' : '' ?>">
              Next →
            </a>
          </div>
        </div>
        <?php endif; ?>
    
      <?php endif; ?>
    </div>
    <div class="dash-grid dash-grid-2-1">
      <div class="card">
        <div class="card-header">
          <div class="card-title">📚 My Subjects</div>
          <a href="teacher_courses.php" class="btn btn-ghost btn-sm">View all</a>
        </div>
        <?php if (empty($coursesData)): ?>
          <p style="color:var(--text3);padding:16px;font-size:13px;">No subjects assigned yet.</p>
        <?php else: ?>
          <?php foreach ($coursesData as $c):
            $fillClass = progressFillClass($c['progress']);
            $pctColor  = progressColor($c['progress']);
          ?>
          <a href="teacher_courses.php" style="text-decoration:none;color:inherit">
            <div class="course-row">
              <div class="course-icon"><?= $c['icon'] ?></div>
              <div class="course-info">
                <div class="course-name"><?= htmlspecialchars($c['title']) ?></div>
                <div class="course-meta">
                  <span>👥 <?= $c['students'] ?> students</span>
                  <span>📖 <?= $c['completed'] ?>/<?= $c['lessons'] ?> lessons</span>
                </div>
              </div>
              <div class="course-stats">
                <div class="course-bar">
                  <div class="progress-bar">
                    <div class="progress-fill <?= $fillClass ?>" style="width:<?= $c['progress'] ?>%"></div>
                  </div>
                </div>
                <div class="course-pct" style="color:<?= $pctColor ?>"><?= $c['progress'] ?>%</div>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title">⚡ Quick Actions</div>
        </div>
          <div class="quick-actions">

            <div class="quick-action" onclick="window.location='teacher_quizzes.php'">
              <span class="qa-icon">📝</span>
              <span class="qa-label">Create Quiz</span>
            </div>

            <div class="quick-action" onclick="window.location='teacher_lessons.php'">
              <span class="qa-icon">📄</span>
              <span class="qa-label">Add Lesson</span>
            </div>

            <div class="quick-action" onclick="window.location='teacher_virtual_class.php'">
              <span class="qa-icon">📹</span>
              <span class="qa-label">Start Class</span>
            </div>

            <div class="quick-action" onclick="openAnnounceModal()">
              <span class="qa-icon">📢</span>
              <span class="qa-label">Post Announcement</span>
            </div>

            <div class="quick-action" onclick="window.location='teacher_reports.php'">
              <span class="qa-icon">📊</span>
              <span class="qa-label">View Reports</span>
            </div>

            <div class="quick-action" onclick="window.location='teacher_grades.php'">
              <span class="qa-icon">✅</span>
              <span class="qa-label">Grade Submissions</span>
            </div>

          </div>
      </div>
    </div>

    <div class="dash-grid dash-grid-1-1">
      <div class="card">
        <div class="card-header">
          <div class="card-title">📄 Recent Lessons</div>
          <a href="teacher_lessons.php" class="btn btn-ghost btn-sm">View all</a>
        </div>
        <?php
        $sortedLessons = $lessons;
        usort($sortedLessons, fn($a,$b) => strcmp($b['created_at'], $a['created_at']));
        $recentLessons = array_slice($sortedLessons, 0, 5);
        ?>
        <?php if (empty($recentLessons)): ?>
          <p style="color:var(--text3);padding:16px;font-size:13px;">No lessons uploaded yet.</p>
        <?php else:
          foreach ($recentLessons as $l):
            $statusColor = strtolower($l['status']) === 'published' ? 'var(--green)' : 'var(--amber)';
            $icon        = strtolower($l['resource_type']) === 'video' ? '📹' : '📄';
            $tsid        = $l['teacher_subject_id'];
            $subj        = isset($subjectMap[$tsid]) ? htmlspecialchars($subjectMap[$tsid]['subject_name']) : '—';
        ?>
          <div class="activity-item">
            <div class="activity-dot" style="background:<?= $statusColor ?>;font-size:14px"><?= $icon ?></div>
            <div>
              <div class="activity-text"><strong><?= htmlspecialchars($l['title']) ?></strong></div>
              <div class="activity-time">
                <?= htmlspecialchars($subj) ?> ·
                <?= ucfirst($l['status']) ?> ·
                <?= $l['duration'] ? $l['duration'].' min' : '' ?>
                · <?= date('M j', strtotime($l['created_at'])) ?>
              </div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title">❓ Recent Quizzes</div>
          <a href="teacher_quizzes.php" class="btn btn-ghost btn-sm">View all</a>
        </div>
        <?php
        $sortedQuizzes = $quizzes;
        usort($sortedQuizzes, fn($a,$b) => strcmp($b['created_at'], $a['created_at']));
        $recentQuizzes = array_slice($sortedQuizzes, 0, 5);
        ?>
        <?php if (empty($recentQuizzes)): ?>
          <p style="color:var(--text3);padding:16px;font-size:13px;">No quizzes created yet.</p>
        <?php else:
          foreach ($recentQuizzes as $q):
            $st       = strtolower($q['status'] ?? '');
            $dotColor = $st === 'published' ? 'green-dot' : ($st === 'draft' ? 'blue-dot' : 'amber-dot');
            $tsid     = $lessonSubjectMap[$q['lesson_id']] ?? null;
            $subj     = ($tsid && isset($subjectMap[$tsid])) ? htmlspecialchars($subjectMap[$tsid]['subject_name']) : '—';
        ?>
          <div class="activity-item">
            <div class="activity-dot <?= $dotColor ?>">❓</div>
            <div>
              <div class="activity-text">
                <strong><?= htmlspecialchars($q['description'] ?: 'Quiz #'.$q['quiz_id']) ?></strong>
              </div>
              <div class="activity-time">
                <?= htmlspecialchars($subj) ?> ·
                <?= $q['question_count'] ?> Qs ·
                <?= ucfirst($q['status']) ?> ·
                <?= $q['due_date'] ? 'Due: '.date('M j', strtotime($q['due_date'])) : 'No due date' ?>
              </div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

  </div>
</main>
</div>

<!-- POST ANNOUNCEMENT MODAL -->
<div class="modal-overlay" id="announceModal">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <div class="modal-title">📢 Post Announcement</div>
      <button class="modal-close" onclick="closeAnnounceModal()">✕</button>
    </div>
    <form method="POST" action="index.php">
      <input type="hidden" name="action" value="post_announcement">
      <div class="modal-body" style="display:flex;flex-direction:column;gap:14px;padding:20px">
        <div class="form-group">
          <label style="font-size:13px;font-weight:600;color:var(--text2);margin-bottom:4px;display:block">Title *</label>
          <input type="text" name="title" placeholder="Announcement title..."
                 style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);color:var(--text);font-size:13px;box-sizing:border-box" required>
        </div>
        <div class="form-group">
          <label style="font-size:13px;font-weight:600;color:var(--text2);margin-bottom:4px;display:block">Type</label>
          <select name="type"
                  style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);color:var(--text);font-size:13px;box-sizing:border-box">
            <option value="system">System</option>
            <option value="important">Important</option>
            <option value="event">Event</option>
          </select>
        </div>
        <div class="form-group">
          <label style="font-size:13px;font-weight:600;color:var(--text2);margin-bottom:4px;display:block">Message *</label>
          <textarea name="message" rows="4" placeholder="Write your announcement here..."
                    style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);color:var(--text);font-size:13px;resize:vertical;box-sizing:border-box" required></textarea>
        </div>
      </div>
      <div class="modal-footer" style="justify-content:flex-end;padding:0 20px 20px;gap:8px">
        <button type="button" class="btn btn-ghost" onclick="closeAnnounceModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Post Announcement</button>
      </div>
    </form>
  </div>
</div>
<!-- EDIT ANNOUNCEMENT MODAL -->
<div class="modal-overlay" id="editAnnounceModal">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <div class="modal-title">✏️ Edit Announcement</div>
      <button class="modal-close" onclick="closeEditModal()">✕</button>
    </div>
    <form method="POST" action="index.php">
      <input type="hidden" name="action" value="edit_announcement">
      <input type="hidden" name="ann_id" id="editAnnId">
      <div class="modal-body" style="display:flex;flex-direction:column;gap:14px;padding:20px">
        <div class="form-group">
          <label style="font-size:13px;font-weight:600;color:var(--text2);margin-bottom:4px;display:block">Title *</label>
          <input type="text" name="title" id="editAnnTitle" placeholder="Announcement title..."
                 style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);color:var(--text);font-size:13px;box-sizing:border-box" required>
        </div>
        <div class="form-group">
          <label style="font-size:13px;font-weight:600;color:var(--text2);margin-bottom:4px;display:block">Type</label>
          <select name="type" id="editAnnType"
                  style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);color:var(--text);font-size:13px;box-sizing:border-box">
            <option value="system">System</option>
            <option value="important">Important</option>
            <option value="event">Event</option>
          </select>
        </div>
        <div class="form-group">
          <label style="font-size:13px;font-weight:600;color:var(--text2);margin-bottom:4px;display:block">Message *</label>
          <textarea name="message" id="editAnnMessage" rows="4" placeholder="Write your announcement here..."
                    style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);color:var(--text);font-size:13px;resize:vertical;box-sizing:border-box" required></textarea>
        </div>
      </div>
      <div class="modal-footer" style="justify-content:flex-end;padding:0 20px 20px;gap:8px">
        <button type="button" class="btn btn-ghost" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE ANNOUNCEMENT MODAL -->
<div class="modal-overlay" id="deleteAnnounceModal">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <div class="modal-title">🗑 Delete Announcement</div>
      <button class="modal-close" onclick="closeDeleteModal()">✕</button>
    </div>
    <form method="POST" action="index.php">
      <input type="hidden" name="action" value="delete_announcement">
      <input type="hidden" name="ann_id" id="deleteAnnId">
      <div class="modal-body" style="text-align:center;padding:24px 20px">
        <div style="font-size:40px;margin-bottom:12px">🗑️</div>
        <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:6px">Delete this announcement?</div>
        <div id="deleteAnnTitle" style="font-size:13px;color:var(--text3);margin-bottom:4px"></div>
        <div style="font-size:12px;color:var(--text3)">This action cannot be undone.</div>
      </div>
      <div class="modal-footer" style="justify-content:center;padding:0 20px 20px;gap:8px">
        <button type="button" class="btn btn-ghost" onclick="closeDeleteModal()">Cancel</button>
        <button type="submit" class="btn" style="background:var(--red);color:white;border:none">Yes, Delete</button>
      </div>
    </form>
  </div>
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
          $n_dt   = new DateTime($notif['created_at'], new DateTimeZone('UTC'));
        $n_ts   = $n_dt->getTimestamp();
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

<div class="toast-container" id="toast-container"></div>

<script>
function openLogoutModal(){document.getElementById('logoutModal').classList.add('open');document.body.style.overflow='hidden';}
function closeLogoutModal(){document.getElementById('logoutModal').classList.remove('open');document.body.style.overflow='';}
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogoutModal();});
function confirmLogout(){window.location.href='../logout.php';}

var sidebar = document.getElementById('sidebar');
var overlay = document.getElementById('sidebar-overlay');
var menuBtn = document.getElementById('menuBtn');
function openSidebar(){sidebar.classList.add('open');overlay.classList.add('open');document.body.style.overflow='hidden';}
function closeSidebar(){sidebar.classList.remove('open');overlay.classList.remove('open');document.body.style.overflow='';}
menuBtn.addEventListener('click',function(){sidebar.classList.contains('open')?closeSidebar():openSidebar();});
overlay.addEventListener('click',closeSidebar);
document.querySelectorAll('.nav-item').forEach(function(item){
  item.addEventListener('click',function(){if(window.innerWidth<=768)closeSidebar();});
});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeSidebar();});
var resizeTimer;
window.addEventListener('resize',function(){clearTimeout(resizeTimer);resizeTimer=setTimeout(function(){if(window.innerWidth>768)closeSidebar();},150);});

var themeToggle = document.getElementById('themeToggle');
var themeIcon   = document.getElementById('theme-icon');
var themeText   = document.getElementById('theme-text');
function applyTheme(isLight){
  if(isLight){ document.body.classList.add('light'); if(themeIcon) themeIcon.textContent='☀️'; if(themeText) themeText.textContent='Light Mode'; }
  else{ document.body.classList.remove('light'); if(themeIcon) themeIcon.textContent='🌙'; if(themeText) themeText.textContent='Dark Mode'; }
}
(function(){
  var dbTheme = '<?= htmlspecialchars($db_theme) ?>';
  localStorage.setItem('excelearn-theme', dbTheme);
  if(dbTheme === 'light') applyTheme(true);
})();
if(themeToggle) themeToggle.addEventListener('click',function(){
  var isLight=document.body.classList.contains('light');
  applyTheme(!isLight);
  var newTheme = !isLight ? 'light' : 'dark';
localStorage.setItem('excelearn-theme', newTheme);
fetch('settings.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/x-www-form-urlencoded'},
  body: 'ajax_save=1&theme=' + newTheme +
        '&language=en&timezone=PHT' +
        '&email_alerts=1&email_digest=0&sms_alerts=0&sms_reminder=1&push_browser=1&push_system=1'
});
});

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

if('IntersectionObserver' in window){
  var observer=new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if(entry.isIntersecting){
        entry.target.querySelectorAll('.progress-fill,.dist-fill').forEach(function(fill){
          var w=fill.style.width;fill.style.width='0%';
          requestAnimationFrame(function(){requestAnimationFrame(function(){fill.style.width=w;});});
        });
        observer.unobserve(entry.target);
      }
    });
  },{threshold:0.15});
  document.querySelectorAll('.card').forEach(function(card){observer.observe(card);});
}

function openAnnounceModal(){
  document.getElementById('announceModal').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeAnnounceModal(){
  document.getElementById('announceModal').classList.remove('open');
  document.body.style.overflow='';
}
document.getElementById('announceModal').addEventListener('click',function(e){
  if(e.target===this) closeAnnounceModal();
});
<?php if (!empty($announce_toast)): ?>
showToast(<?= json_encode($announce_toast['msg']) ?>, <?= json_encode($announce_toast['type']) ?>);
<?php endif; ?>

function openEditModal(id, title, message, type) {
  document.getElementById('editAnnId').value      = id;
  document.getElementById('editAnnTitle').value   = title;
  document.getElementById('editAnnMessage').value = message;
  var sel = document.getElementById('editAnnType');
  for (var i = 0; i < sel.options.length; i++) {
    sel.options[i].selected = sel.options[i].value === type;
  }
  document.getElementById('editAnnounceModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeEditModal() {
  document.getElementById('editAnnounceModal').classList.remove('open');
  document.body.style.overflow = '';
}
const editModal = document.getElementById('editAnnounceModal');
if (editModal) {
    editModal.addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
}

function openDeleteModal(id, title) {
  document.getElementById('deleteAnnId').value       = id;
  document.getElementById('deleteAnnTitle').textContent = '"' + title + '"';
  document.getElementById('deleteAnnounceModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeDeleteModal() {
  document.getElementById('deleteAnnounceModal').classList.remove('open');
  document.body.style.overflow = '';
}
const deleteModal = document.getElementById('deleteAnnounceModal');
if (deleteModal) {
    deleteModal.addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
}

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

// Reset to page 1 when search changes
var annSearchInput = document.getElementById('ann-search-input');
if (annSearchInput) {
  annSearchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      document.getElementById('ann-search-form').submit();
    }
  });
}
</script>
</body>
</html>