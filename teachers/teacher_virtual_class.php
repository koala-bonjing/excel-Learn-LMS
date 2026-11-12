<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
include '../connection.php';

// WebRTC Signaling
if (isset($_POST['action']) && $_POST['action'] === 'send_signal') {
    $class_id  = (int)$_POST['class_id'];
    $from_role = $_POST['from_role']; // 'teacher' or 'student'
    $to_role   = $_POST['to_role'];
    $signal    = $_POST['signal'];
    $from_id   = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("
        INSERT INTO tblvirtualclass_signals (class_id, from_id, from_role, to_role, `signal`, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iisss", $class_id, $from_id, $from_role, $to_role, $signal);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => 'ok']);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'get_signals') {
    $class_id  = (int)$_POST['class_id'];
    $for_role  = $_POST['for_role'];
    $last_id   = (int)($_POST['last_id'] ?? 0);

    $stmt = $conn->prepare("
        SELECT id, from_id, from_role, `signal`
        FROM tblvirtualclass_signals
        WHERE class_id = ? AND to_role = ? AND id > ?
        ORDER BY id ASC
        LIMIT 50
    ");
    $stmt->bind_param("isi", $class_id, $for_role, $last_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $signals = [];
    while ($row = $res->fetch_assoc()) {
        $signals[] = $row;
    }
    $stmt->close();
    echo json_encode($signals);
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'get_students') {

    $class_id = (int)$_POST['class_id'];
    $teacher_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    $stmt = $conn->prepare("
        SELECT 
            s.id AS student_id,
            s.firstname,
            s.lastname,
            s.profile_pic,

            p.camera_status,
            p.mic_status,
            p.joined_at

        FROM tblvirtualclass_participants p

        INNER JOIN tblstudents s 
            ON s.id = p.student_id

        INNER JOIN tblvirtualclasses vc
            ON vc.id = p.class_id

        WHERE p.class_id = ? AND p.join = 'YES'
    ");

    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $students = [];

    while ($row = $res->fetch_assoc()) {
      $students[] = [
          'id'      => $row['student_id'],
          'name'    => $row['firstname'] . ' ' . $row['lastname'],
          'pic'     => $row['profile_pic'],

          'camera'  => (int)$row['camera_status'],
          'mic'     => (int)$row['mic_status'],

          'joined'  => $row['joined_at']
      ];
    }

    echo json_encode($students);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'save_recording') {
    $class_id = (int)$_POST['class_id'];
    $file_path = $_POST['file_path'] ?? '';

    if ($class_id && $file_path) {
        $stmt = $conn->prepare("
            INSERT INTO tblvirtualclass_recordings (class_id, teacher_id, file_path)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $class_id, $teacher_id, $file_path);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['status' => 'success']);
    }

    exit;
}

$teacher_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

// Sidebar badge counts
$badge_subjects  = (int)$conn->query("SELECT COUNT(*) FROM tbl_teacher_subjects WHERE teacher_id = $teacher_id")->fetch_row()[0];
$badge_students  = (int)$conn->query("SELECT COUNT(DISTINCT student_id) FROM tbl_teachers_students WHERE teacher_subject_id IN (SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = $teacher_id)")->fetch_row()[0];
$badge_lessons   = (int)$conn->query("SELECT COUNT(*) FROM tbl_teacher_lessons l JOIN tbl_teacher_subjects s ON l.teacher_subject_id = s.teacher_subject_id WHERE s.teacher_id = $teacher_id")->fetch_row()[0];
$badge_quizzes   = (int)$conn->query("SELECT COUNT(*) FROM tblquizzes q JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id JOIN tbl_teacher_subjects s ON l.teacher_subject_id = s.teacher_subject_id WHERE s.teacher_id = $teacher_id")->fetch_row()[0];

$stmt = $conn->prepare("SELECT id, firstname, middlename, lastname, profile_pic, role FROM tblusers WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

$teacher_initials = strtoupper(
    substr($teacher['firstname'] ?? 'T', 0, 1) .
    substr($teacher['lastname']  ?? 'E', 0, 1)
);
$teacher_fullname = trim(($teacher['firstname'] ?? '') . ' ' . ($teacher['lastname'] ?? ''));
$teacher_pic = $teacher['profile_pic'] ?? '';
// GET TEACHER SUBJECTS
$subjects = [];

$stmtSub = $conn->prepare("
    SELECT ts.teacher_subject_id, s.subject
    FROM tbl_teacher_subjects ts
    INNER JOIN tblsubjects s ON s.subject_id = ts.subject_id
    WHERE ts.teacher_id = ?
");
$stmtSub->bind_param("i", $teacher_id);
$stmtSub->execute();
$resSub = $stmtSub->get_result();

while ($row = $resSub->fetch_assoc()) {
    $subjects[] = $row;
}

$stmtSub->close();

// --- HANDLE POST ACTIONS ---
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $title    = trim($_POST['title'] ?? '');
    $date     = $_POST['date'] ?? '';
    $time     = $_POST['time'] ?? '';
    $duration = (int)($_POST['duration'] ?? 60);
    $teacher_subject_id = (int)($_POST['teacher_subject_id'] ?? 0);

    if ($title && $date && $time && $teacher_subject_id) {

        $stmt = $conn->prepare("
          INSERT INTO tblvirtualclasses 
          (teacher_id, teacher_subject_id, title, status, date, time, duration)
          VALUES (?, ?, ?, 'upcoming', ?, ?, ?)
        ");

        $stmt->bind_param(
            "iisssi",
            $teacher_id,
            $teacher_subject_id,
            $title,
            $date,
            $time,
            $duration
        );

        $stmt->execute();
        $stmt->close();

        $toast = [
            'msg' => '"' . htmlspecialchars($title) . '" scheduled successfully!',
            'type' => 'success'
        ];

    } else {
        $toast = ['msg' => 'Please fill in all required fields.', 'type' => 'error'];
    }
}

if ($action === 'delete') {
    $del_id = (int)($_POST['del_id'] ?? 0);
    $stmt = $conn->prepare("SELECT title FROM tblvirtualclasses WHERE id = ? AND teacher_id = ? LIMIT 1");
    $stmt->bind_param("ii", $del_id, $teacher_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        // Delete participants first (foreign key child rows)
        $stmt = $conn->prepare("DELETE FROM tblvirtualclass_participants WHERE class_id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $stmt->close();
    
        // Delete signals if table exists
        $stmt = $conn->prepare("DELETE FROM tblvirtualclass_signals WHERE class_id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $stmt->close();
    
        // Now safe to delete the class
        $stmt = $conn->prepare("DELETE FROM tblvirtualclasses WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("ii", $del_id, $teacher_id);
        $stmt->execute();
        $stmt->close();
        $toast = ['msg' => '"' . htmlspecialchars($row['title']) . '" has been deleted.', 'type' => 'error'];
    }
}

if ($action === 'cancel') {
    $class_id = (int)($_POST['class_id'] ?? 0);

    if ($class_id) {
        $stmt = $conn->prepare("
            UPDATE tblvirtualclasses
            SET status = 'cancelled'
            WHERE id = ? AND teacher_id = ?
        ");
        $stmt->bind_param("ii", $class_id, $teacher_id);
        $stmt->execute();
        $stmt->close();

        $toast = ['msg' => 'Class cancelled successfully.', 'type' => 'error'];

        // IMPORTANT: return class id for frontend update
        echo "<script>window.__cancelledId = $class_id;</script>";
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'set_live') {
    $class_id   = (int)($_POST['class_id'] ?? 0);
    $camera_on  = (int)($_POST['camera_on'] ?? 1);
    $teacher_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    if ($class_id) {
        $camera_on = 1;
        $stmt = $conn->prepare("UPDATE tblvirtualclasses SET status = 'live', camera_on = ? WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("iii", $camera_on, $class_id, $teacher_id);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'set_ended') {
    $class_id  = (int)$_POST['class_id'];
    $teacher_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    if ($class_id) {
        $stmt = $conn->prepare("UPDATE tblvirtualclasses SET status = 'ended' WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("ii", $class_id, $teacher_id);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'set_camera') {
    $class_id   = (int)$_POST['class_id'];
    $camera_on  = (int)$_POST['camera_on'];
    $teacher_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $stmt = $conn->prepare("UPDATE tblvirtualclasses SET camera_on = ? WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("iii", $camera_on, $class_id, $teacher_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => 'ok']);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'set_mic') {
    $class_id   = (int)$_POST['class_id'];
    $mic_on     = (int)$_POST['mic_on'];
    $teacher_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $stmt = $conn->prepare("UPDATE tblvirtualclasses SET mic_on = ? WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("iii", $mic_on, $class_id, $teacher_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => 'ok']);
    exit;
}

// --- FETCH CLASSES FOR THIS TEACHER ---
// --- FETCH CLASSES FOR THIS TEACHER ---
$result = $conn->query("SELECT * FROM tblvirtualclasses WHERE teacher_id = $teacher_id ORDER BY date ASC, time ASC");
$vclasses = [];
while ($row = $result->fetch_assoc()) {
    $vclasses[] = $row;
}

// Pre-fetch badge counts BEFORE closing connection
$badge_virtualclass = (int)$conn->query("SELECT COUNT(*) FROM tblvirtualclasses WHERE teacher_id = $teacher_id AND status IN ('upcoming','live')")->fetch_row()[0];

$badge_gradebook = (int)$conn->query("
    SELECT COUNT(DISTINCT r.student_id)
    FROM tblquiz_results r
    JOIN tblquizzes q ON q.quiz_id = r.quizzes_id
    JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    JOIN tbl_teacher_subjects s ON s.teacher_subject_id = l.teacher_subject_id
    WHERE s.teacher_id = $teacher_id
")->fetch_row()[0];


// Build JS-safe array for frontend
$vclasses_js = [];
foreach ($vclasses as $c) {
    $dateStr = date('M j, Y', strtotime($c['date']));
    $timeStr = date('h:i A',  strtotime($c['time']));
    $icon = '📹';

    $vclasses_js[] = [
        'id'        => (int)$c['id'],
        'title'     => $c['title'],
        'status'    => $c['status'],
        'date'      => $dateStr,
        'raw_date'  => $c['date'],
        'raw_time'  => $c['time'],
        'time'      => $timeStr,
        'duration'  => (int)$c['duration'],
        'icon'      => $icon,
        'camera_on' => (int)$c['camera_on'],
        'mic_on'    => (int)$c['mic_on'],
    ];
}

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
<link rel="stylesheet" href="../assets/css/teacher/teacher_virtual_class.css">
<!-- Firebase compat (same as student page) -->
<script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-database-compat.js"></script>
<script>
  window.firebaseDB  = null;
  window.firebaseRef = null;

  var firebaseConfig = {
    apiKey: "AIzaSyBJTgcDklxa3u1XH1at9SL24lK03pQRjl8",
    authDomain: "excelearn.firebaseapp.com",
    databaseURL: "https://excelearn-default-rtdb.firebaseio.com",
    projectId: "excelearn",
    storageBucket: "excelearn.firebasestorage.app",
    messagingSenderId: "836777641589",
    appId: "1:836777641589:web:a51ae65bdee17afc6ada3c"
  };

  firebase.initializeApp(firebaseConfig);
  var db = firebase.database();
  window.firebaseDB = db;

  window.firebaseRef = {
    ref:          function(db, path) { return db.ref(path); },
    push:         function(refObj, data) { return refObj.push(data); },
    set:          function(refObj, data) { return refObj.set(data); },
    remove:       function(refObj) { return refObj.remove(); },
    onChildAdded: function(refObj, callback) { refObj.on('child_added', callback); },
    get:          function(refObj) { return refObj.once('value'); }
  };

  console.log("✅ Firebase Teacher ready:", window.firebaseDB ? "connected" : "failed");
</script>
<style>
.video-modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.85);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.video-box {
  width: 900px;
  max-width: 96vw;
  max-height: 96vh;
  background: #0f172a;
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.video-box.maximized {
  width: 100vw !important;
  height: 100dvh !important;
  max-width: 100vw !important;
  max-height: 100dvh !important;
  border-radius: 0 !important;
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  z-index: 99999 !important;
}

.video-box.maximized .video-header {
  position: sticky;
  top: 0;
  z-index: 100000;
  flex-shrink: 0;
}

.video-header {
  padding: 10px 14px;
  background: #111827;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  flex-shrink: 0;
  gap: 8px;
}

.video-header > div:first-child {
  min-width: 0;
  flex: 1;
}

.video-controls {
  display: flex;
  gap: 6px;
  flex-shrink: 0;
}

.video-controls button,
.video-header button {
  background: rgba(255,255,255,0.1);
  border: none;
  color: white;
  padding: 5px 10px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  white-space: nowrap;
}

.video-body {
  flex: 1;
  display: flex;
  overflow: hidden;
  background: #0b1220;
  min-height: 0;
}

.video-layout {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  width: 100%;
  height: 100%;
  overflow: hidden;
  box-sizing: border-box;
}

.teacher-video-wrapper {
  width: 260px;
  height: 260px;
  flex-shrink: 0;
  background: #1e293b;
  border: 1px solid #334155;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
}

.teacher-video-wrapper video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transform: scaleX(-1);
}
.students-sidebar {
  width: 260px;
  flex-shrink: 0;
  background: #0b1220;
  border-left: 1px solid #1e293b;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.students-header {
  padding: 10px 12px;
  font-weight: 700;
  font-size: 13px;
  color: white;
  border-bottom: 1px solid #1e293b;
  background: #111827;
  flex-shrink: 0;
}

.students-list {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.student-card {
  display: flex;
  flex-direction: column;
  border-radius: 10px;
  background: #1e293b;
  border: 1px solid #334155;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  overflow: hidden;
  box-sizing: border-box;
  width: 100%;
}

.student-video-box {
  width: 100%;
  height: 185px;
  background: #0a0f1a;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
}

.student-video-box video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 3;
  transform: scaleX(-1);
}

.student-video-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #475569;
  gap: 8px;
  z-index: 1;
}

.sv-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #334155;
}

.sv-initials {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 700;
  color: white;
}

.student-info-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 7px 10px;
  gap: 6px;
  flex-shrink: 0;
  background: #1e293b;
}

.student-name {
  font-size: 11px;
  font-weight: 600;
  color: white;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
  min-width: 0;
}

.student-status {
  display: flex;
  flex-direction: row;
  gap: 3px;
  flex-shrink: 0;
}

.status-pill {
  font-size: 9px;
  padding: 2px 6px;
  border-radius: 20px;
  text-align: center;
  color: white;
  white-space: nowrap;
  font-weight: 600;
}

/* ── FOOTER ── */
.video-footer {
  padding: 10px;
  display: flex;
  gap: 8px;
  justify-content: center;
  background: #111827;
  flex-shrink: 0;
  flex-wrap: wrap;
}

.video-footer button {
  padding: 8px 16px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  background: #1e293b;
  color: white;
  white-space: nowrap;
}

.end-btn {
  background: #ef4444 !important;
  color: white !important;
}

/* ── MOBILE ── */
@media (max-width: 768px) {
  /* Sheet slides up from bottom */
  .video-modal {
    align-items: flex-end;
    padding: 0;
  }

  .video-box {
    width: 100vw;
    max-width: 100vw;
    height: auto;
    max-height: 92vh;
    border-radius: 16px 16px 0 0;
  }

  /* Stack vertically */
  .video-layout {
    flex-direction: column;
    height: auto;
  }

  /* Small camera top-left overlay */
.video-body {
  position: relative;
  min-height: 120px;
  background: #0b1220;
}

  /* Students list fills the body area */
.students-sidebar {
  width: 280px;
  flex-shrink: 0;
  background: #0b1220;
  border-left: 1px solid #1e293b;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.students-header {
  padding: 8px 12px 8px 110px;
  font-size: 12px;
}

.students-list {
  padding: 6px 8px;
  padding-top: 100px;
  overflow-y: auto;
}

  /* Camera pinned top-left as small overlay */
.teacher-video-wrapper {
  position: absolute;
  top: 10px;
  left: 10px;
  width: 90px;
  height: 90px;
  flex: none;
  border-radius: 10px;
  border: 2px solid #475569;
  z-index: 10;
  overflow: hidden;
  background: #000;
}

  .teacher-video-wrapper video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transform: scaleX(-1);
    }

  /* Student cards on mobile */
  .student-card {
    display: flex;
    flex-direction: column;
    width: 100%;
  }

  .student-video-box {
    height: 180px;
  }

  .student-name { font-size: 11px; }

  .student-status {
    flex-direction: row;
    gap: 3px;
  }

  /* Footer compact */
  .video-footer {
    padding: 8px 10px;
    gap: 6px;
  }

  .video-footer button {
    padding: 8px 12px;
    font-size: 12px;
    flex: 1;
    min-width: 0;
  }

  /* Header compact */
  .video-header {
    padding: 8px 12px;
  }

  #videoTitle {
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}
/* ── TWO-COLUMN LAYOUT ── */
.vc-layout {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

.vc-left {
  flex: 1;
  min-width: 0;
}

.vc-right {
  width: 280px;
  flex-shrink: 0;
}

.schedule-panel {
  background: var(--card-bg, #1e293b);
  border: 1px solid var(--border, #334155);
  border-radius: 12px;
  overflow: hidden;
  position: sticky;
  top: 20px;
}

.schedule-panel-header {
  padding: 12px 16px;
  font-weight: 700;
  font-size: 14px;
  background: var(--card-header-bg, #111827);
  color: white;
  border-bottom: 1px solid var(--border, #334155);
}

.schedule-panel-body {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* ── TOOLBAR ROWS ── */
.toolbar-top {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 10px;
}

.toolbar-top .search-wrap {
  flex: 1;
  min-width: 0;
}

.toolbar-top .sort-select {
  flex-shrink: 0;
  width: auto;
  min-width: 110px;
}

.toolbar-bottom {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.toolbar-bottom::-webkit-scrollbar {
  display: none;
}

.toolbar-bottom .filter-tabs {
  display: flex;
  gap: 6px;
  flex-wrap: nowrap;
  min-width: max-content;
}

/* ── RESPONSIVE LAYOUT ── */
@media (max-width: 768px) {
  .vc-layout {
    flex-direction: column;
  }

  .vc-right {
    display: none;
  }

  .toolbar-top {
    flex-wrap: nowrap;
  }

  .toolbar-top .sort-select {
    min-width: 90px;
    font-size: 12px;
  }

  /* Filter tabs scroll horizontally on mobile — no wrap */
  .toolbar-bottom .filter-tabs {
    padding-bottom: 4px;
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
      <?php if ($badge_subjects > 0): ?><span class="nav-badge" style="background:var(--accent);color:#fff"><?= $badge_subjects ?></span><?php endif; ?>
    </a>
    <a class="nav-item" href="teacher_performance_tasks.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>
      Performance Tasks <span class="nav-badge"><?php echo $total_tasks_badge; ?></span>
      <?php if ($pending_review_badge > 0): ?><span class="nav-badge" style="background:var(--red,#ef4444);color:#fff;margin-left:4px;"><?= $pending_review_badge ?></span><?php endif; ?>
    </a>
    <a class="nav-item active" href="teacher_virtual_class.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
      Virtual Class
      <?php if ($badge_virtualclass > 0): ?><span class="nav-badge" style="background:var(--cyan,#06b6d4);color:#000"><?= $badge_virtualclass ?></span><?php endif; ?>
    </a>
    <div class="nav-section-label" style="margin-top:14px">Analytics</div>
    <a class="nav-item" href="teacher_students.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Students
      <?php if ($badge_students > 0): ?><span class="nav-badge" style="background:var(--green);color:#fff"><?= $badge_students ?></span><?php endif; ?>
    </a>
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
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--accent),var(--purple))">
        <?= htmlspecialchars($teacher_initials) ?>
      </div>
      <div class="user-info">
        <p><?= htmlspecialchars($teacher_fullname) ?></p>
        <span>Faculty — <?= htmlspecialchars(ucfirst($teacher['role'] ?? 'Teacher')) ?></span>
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
  <!-- PAGE HEADER -->
  <div class="page-header">
    <div>
      <div class="page-title">Virtual Class</div>
      <div class="page-sub" id="class-count-text">Loading sessions...</div>
    </div>
    <div class="page-actions">
      <button class="btn btn-primary btn-sm" onclick="openModal()">+ Schedule Class</button>
    </div>
  </div>

  <div class="vc-layout">

    <!-- LEFT: toolbar + grid -->
    <div class="vc-left">
      <div class="toolbar">
        <div class="toolbar-top">
          <div class="search-wrap">
            <span class="search-icon">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" id="searchInput" placeholder="Search classes...">
          </div>
          <select class="sort-select" id="sortSelect">
            <option value="date-asc">📅 Date ↑</option>
            <option value="date-desc">📅 Date ↓</option>
            <option value="name-az">🔤 A–Z</option>
          </select>
        </div>
        <div class="toolbar-bottom">
          <div class="filter-tabs" id="filterTabs">
            <div class="filter-tab active" data-filter="all">All</div>
            <div class="filter-tab" data-filter="upcoming">Upcoming</div>
            <div class="filter-tab" data-filter="live">Live</div>
            <div class="filter-tab" data-filter="ended">Ended</div>
            <div class="filter-tab" data-filter="cancelled">Cancelled</div>
          </div>
        </div>
      </div>
      <div class="vclass-grid" id="vclassGrid"></div>
    </div>
</main>
</div>

<!-- CREATE CLASS MODAL -->
<div class="modal-overlay" id="createModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Schedule Class</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Session Title *</label>
        <input type="text" id="newTitle" placeholder="e.g. Organic Chemistry Review">
      </div>
      <div class="form-group">
        <label>Subject *</label>
        <select id="newSubject">
          <option value="">Select subject</option>
          <?php foreach ($subjects as $sub): ?>
            <option value="<?= $sub['teacher_subject_id'] ?>">
              <?= htmlspecialchars($sub['subject']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Date *</label>
          <input type="date" id="newDate">
        </div>
        <div class="form-group">
          <label>Time *</label>
          <input type="time" id="newTime">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Duration (mins)</label>
          <input type="number" id="newDuration" placeholder="60" min="5" max="180">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" onclick="createClass()">Schedule</button>
    </div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<!-- VIDEO CLASS MODAL -->
<div id="videoModal" class="video-modal">
  <div class="video-box">

    <div class="video-header">
      <div>
        <div id="videoTitle">Live Class</div>
        <div style="font-size:12px;color:#9ca3af">
          Ends in: <span id="countdown">--:--:--</span>
        </div>
      </div>

      <div class="video-controls">
        <button onclick="toggleSize()">⛶</button>
        <button onclick="closeVideo()">✕</button>
      </div>
    </div>

    <div class="video-body video-layout">

      <!-- LEFT SIDE = TEACHER VIDEO -->
      <div class="teacher-video-wrapper">
        <video id="localVideo" autoplay muted playsinline style="transform: scaleX(-1);"></video>
        <div id="teacherCamOff" style="display:none;position:absolute;inset:0;z-index:5;background:#0f172a;align-items:center;justify-content:center;flex-direction:column;gap:6px;">
          <img id="teacherCamOffPic" src="" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #334155;display:none;">
          <div id="teacherCamOffInitials" style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:white;"><?= htmlspecialchars($teacher_initials) ?></div>
          <div style="font-size:11px;color:#64748b;margin-top:2px;">Camera Off</div>
        </div>
      </div>

      <!-- RIGHT SIDE = STUDENTS -->
      <div class="students-sidebar">
        <div class="students-header">
          👥 Students (<span id="studentCount">0</span>)
        </div>
        <div id="studentList" class="students-list"></div>
      </div>

    </div>

    <div class="video-footer">
      <button id="btnCamera" onclick="toggleCamera()">📷 Camera ON</button>
      <button id="btnMic" onclick="toggleMic()">🎤 Mic ON</button>
      <button onclick="endClass()" class="end-btn">❌ End Class</button>
    </div>

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
<script>
function openLogoutModal(){document.getElementById('logoutModal').classList.add('open');document.body.style.overflow='hidden';}
function closeLogoutModal(){document.getElementById('logoutModal').classList.remove('open');document.body.style.overflow='';}
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogoutModal();});
function confirmLogout(){window.location.href='../logout.php';}

let isStartingCamera = false;
let mediaRecorder;
let recordedChunks = [];

let classTimer = null;
let countdownTimer = null;
let classEndTime = null;

let localStream;
let currentClassId = null;

function updateCancelledClass(id) {
  const cls = vclasses.find(c => c.id === id);
  if (cls) {
    cls.status = 'cancelled';
  }
}

function loadStudents(classId) {
  fetch('teacher_virtual_class.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=get_students&class_id=' + classId
  })
  .then(res => res.json())
  .then(data => {
    const container = document.getElementById('studentList');
    const countEl   = document.getElementById('studentCount');
    if (countEl) countEl.textContent = data.length;

    // if (!data.length) {
    //   container.innerHTML = '<div style="color:#475569;font-size:12px;text-align:center;padding:20px 0;">No students joined yet.</div>';
    //   return;
    // }

    // Build cards — preserve existing video elements for students already rendered
    data.forEach(s => {
      const existingCard = document.getElementById('student-card-' + s.id);

      if (existingCard) {
        const camPill   = existingCard.querySelector('.pill-cam');
        const micPill   = existingCard.querySelector('.pill-mic');
        const placeholder = document.getElementById('sv-placeholder-' + s.id);
        const remoteVid   = existingCard.querySelector('video.sv-remote');

        if (camPill) { camPill.style.background = s.camera ? '#16a34a' : '#374151'; camPill.textContent = s.camera ? '📷 ON' : '📷 OFF'; }
        if (micPill) { micPill.style.background = s.mic    ? '#2563eb' : '#374151'; micPill.textContent = s.mic    ? '🎤 ON' : '🎤 OFF'; }

        // Toggle placeholder visibility based on camera status
        if (placeholder) {
          placeholder.style.display = s.camera ? 'none' : 'flex';
        }
        // Hide/show the live video stream too
        if (remoteVid) {
          remoteVid.style.display = s.camera ? 'block' : 'none';
          if (s.camera && remoteVid.srcObject) {
            remoteVid.play().catch(() => {});
          }
        }
        // Always update placeholder visibility
        if (placeholder) {
          placeholder.style.display = (s.camera && remoteVid && remoteVid.srcObject) ? 'none' : 'flex';
        }
        return;
      }

      // New student — build card
      const card = document.createElement('div');
      card.className = 'student-card';
      card.id = 'student-card-' + s.id;

      const initials = s.name.split(' ').map(p => p[0] || '').join('').substring(0, 2).toUpperCase();

      card.innerHTML = `
        <div class="student-video-box" id="sv-box-${s.id}">

          <!-- Camera OFF overlay: shows profile pic or initials -->
          <div class="student-video-placeholder" id="sv-placeholder-${s.id}" style="display:${s.camera ? 'none' : 'flex'};">
            ${s.pic
              ? `<img class="sv-avatar" id="sv-avatar-${s.id}" src="../uploads/profile_pic/students/${s.pic}"
                   onerror="this.style.display='none';document.getElementById('sv-initials-${s.id}').style.display='flex';">`
              : ''}
            <div class="sv-initials" id="sv-initials-${s.id}" style="${s.pic ? 'display:none' : 'display:flex'}">${initials}</div>
            <div style="font-size:10px;color:#64748b;margin-top:2px;">Camera Off</div>
          </div>

          <!-- Camera ON: video element (WebRTC stream goes here) -->
          <!-- placeholder hidden when camera is on; video injected by WebRTC ontrack -->

        </div>
        <div class="student-info-row">
          <span class="student-name">${s.name}</span>
          <div class="student-status">
            <div class="status-pill pill-cam" style="background:${s.camera ? '#16a34a' : '#374151'}">${s.camera ? '📷 ON' : '📷 OFF'}</div>
            <div class="status-pill pill-mic" style="background:${s.mic ? '#2563eb' : '#374151'}">${s.mic ? '🎤 ON' : '🎤 OFF'}</div>
          </div>
        </div>
      `;

      container.appendChild(card);
    });

    // Remove cards for students who left
    const activeIds = data.map(s => 'student-card-' + s.id);
    Array.from(container.querySelectorAll('.student-card')).forEach(card => {
      if (!activeIds.includes(card.id)) card.remove();
    });
  });
}

function startClass(id) {
  currentClassId = id;

  const cls = vclasses.find(c => c.id === id);
  if (!cls) return;

  // 🔥 ADD THIS: set status to live immediately
  cls.status = 'live';

// PALITAN NG:
  fetch('teacher_virtual_class.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=set_live&class_id=' + id + '&camera_on=1'
  });
  const start = new Date(cls.raw_date + 'T' + cls.raw_time);

  const durationMs = (cls.duration || 60) * 60000;
  classEndTime = new Date(start.getTime() + durationMs);

  document.getElementById('videoModal').style.display = 'flex';
  document.getElementById('videoTitle').innerText = "Live Class #" + id;

  setTimeout(() => {
    startCamera();
    startCountdown();
    loadStudents(id);
    // Refresh students list every 10 seconds while class is live
    if (!window._studentRefreshTimer) {
      window._studentRefreshTimer = setInterval(function(){
        if (currentClassId) loadStudents(currentClassId);
        else { clearInterval(window._studentRefreshTimer); window._studentRefreshTimer = null; }
      }, 2000);
    }
  }, 300);
}

function startCountdown() {
  clearInterval(countdownTimer);

  countdownTimer = setInterval(() => {
    const now = new Date();
    const diff = classEndTime - now;

    if (diff <= 0) {
      document.getElementById('countdown').innerText = "00:00:00";
      endClassAuto();
      return;
    }

    const hrs = Math.floor(diff / (1000 * 60 * 60));
    const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const secs = Math.floor((diff % (1000 * 60)) / 1000);

    document.getElementById('countdown').innerText =
      String(hrs).padStart(2,'0') + ":" +
      String(mins).padStart(2,'0') + ":" +
      String(secs).padStart(2,'0');

  }, 1000);
}

function endClassAuto() {
  // Double-check — kung walang currentClassId, wag mag-end
  if (!currentClassId) return;
  showToast("Class ended automatically.", "info");
  endClass();
}

let peerConnections = {}; // studentId -> RTCPeerConnection
const iceServers = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };

async function startCamera() {
  if (isStartingCamera) return;
  isStartingCamera = true;
  try {
    if (localStream) {
      localStream.getTracks().forEach(t => t.stop());
      localStream = null;
    }
    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
    localStream = stream;
    const video = document.getElementById('localVideo');
    video.pause();
    video.srcObject = stream;
    await new Promise(r => { video.onloadedmetadata = () => r(); });
    try { await video.play(); } catch(e) {}

    // Update camera_on in DB
    fetch('teacher_virtual_class.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=set_live&class_id=' + currentClassId + '&camera_on=1'
    });

    // Start WebRTC offer loop
    startTeacherSignaling();
  } catch(err) {
    alert("Camera error: " + err.message);
  } finally {
    isStartingCamera = false;
  }
}

async function createOfferForStudent(studentId) {
  // Don't recreate if already connected
  const existing = peerConnections[studentId];
  if (existing) {
    const s = existing.connectionState;
    if (s === 'connected' || s === 'connecting') return;
    existing.close();
    delete peerConnections[studentId];
  }

  if (!localStream) return; // no camera yet, abort

  const pc = new RTCPeerConnection(iceServers);
  peerConnections[studentId] = pc;

  localStream.getTracks().forEach(t => pc.addTrack(t, localStream));

  // Auto-retry if connection drops
  pc.onconnectionstatechange = () => {
    if (pc.connectionState === 'failed' || pc.connectionState === 'disconnected') {
      console.warn('PC dropped for student', studentId, '— will retry on next signal');
      if (peerConnections[studentId] === pc) {
        pc.close();
        delete peerConnections[studentId];
      }
    }
  };

  pc.onicecandidate = e => {
      if (e.candidate) {
        const { ref, push } = window.firebaseRef;
        push(ref(window.firebaseDB, 'signals/' + currentClassId + '/to_student'), {
          from_role: 'teacher',
          signal: JSON.stringify({ type: 'ice-teacher', candidate: e.candidate, targetId: studentId }),
          created_at: Date.now()
        });
      }
    };

  pc.ontrack = e => {
    const box         = document.getElementById('sv-box-' + studentId);
    const placeholder = document.getElementById('sv-placeholder-' + studentId);
    if (!box) return;

    let remoteVid = box.querySelector('video.sv-remote');
    if (!remoteVid) {
      remoteVid = document.createElement('video');
      remoteVid.className   = 'sv-remote';
      remoteVid.autoplay    = true;
      remoteVid.playsInline = true;
      remoteVid.muted       = false;
      remoteVid.style.cssText = 'width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;z-index:4;display:block;';
      box.appendChild(remoteVid);
    }
    remoteVid.srcObject = e.streams[0];
    remoteVid.style.display = 'block';
    remoteVid.play().catch(() => {});
    if (placeholder) placeholder.style.display = 'none';

    // Update cam pill to green
    const card = document.getElementById('student-card-' + studentId);
    if (card) {
      const camPill = card.querySelector('.pill-cam');
      if (camPill) { camPill.style.background = '#16a34a'; camPill.textContent = '📷 ON'; }
    }
  };

  const offer = await pc.createOffer();
  await pc.setLocalDescription(offer);
  sendSignal({ type: 'offer', sdp: pc.localDescription, targetId: studentId });
}

function sendSignal(data) {
  if (!window.firebaseRef || !window.firebaseDB) return;
  const { ref, push } = window.firebaseRef;
  const db = window.firebaseDB;
  if (!db) return;
  push(ref(db, 'signals/' + currentClassId + '/to_student'), {
    from_role: 'teacher',
    signal: JSON.stringify(data),
    created_at: Date.now()
  });
}

function startTeacherSignaling() {
  if (!window.firebaseRef || !window.firebaseDB) return;
  if (window._teacherSignalingStarted) return; // ← guard: isang beses lang
  window._teacherSignalingStarted = true;

  const { ref, onChildAdded } = window.firebaseRef;
  const db = window.firebaseDB;
  if (!db) return;

  const seenSignals = new Set(); // ← deduplicate signals

  onChildAdded(ref(db, 'signals/' + currentClassId + '/to_teacher'), async (snapshot) => {
    if (!snapshot.key || seenSignals.has(snapshot.key)) return; // ← skip duplicates
    seenSignals.add(snapshot.key);
    const sig = snapshot.val();
    if (!sig) return;

    const data = JSON.parse(sig.signal);
    const studentId = sig.from_id;

    if (data.type === 'answer') {
      const pc = peerConnections[studentId];
      if (pc && pc.signalingState === 'have-local-offer') {
        await pc.setRemoteDescription(new RTCSessionDescription(data.sdp));
      }
    } else if (data.type === 'ice-student') {
      const pc = peerConnections[studentId];
      if (pc) {
        try { await pc.addIceCandidate(new RTCIceCandidate(data.candidate)); } catch(e) {}
      }
    } else if (data.type === 'student-joined') {
      const existing = peerConnections[studentId];
      const state = existing ? existing.connectionState : null;
      // Also retry if connection is closed/failed
      if (!state || state === 'failed' || state === 'disconnected' || state === 'closed' || state === 'new') {
        await createOfferForStudent(studentId);
      } else if (state !== 'connected' && state !== 'connecting') {
        await createOfferForStudent(studentId);
      }
    }
    // ← 'student-ready' is intentionally removed — no longer triggers re-offer
  });
}

function toggleMic() {
  if (!localStream) return;

  const audioTrack = localStream.getAudioTracks()[0];
  if (!audioTrack) return;

  audioTrack.enabled = !audioTrack.enabled;
  const micOn = audioTrack.enabled ? 1 : 0;

  // Update button label
  const btn = document.getElementById('btnMic');
  if (btn) btn.textContent = micOn ? '🎤 Mic ON' : '🎤 Mic OFF';

  fetch('teacher_virtual_class.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=set_mic&class_id=' + currentClassId + '&mic_on=' + micOn
  });

  showToast(micOn ? 'Mic ON' : 'Mic OFF', 'info');
}

function toggleCamera() {
  if (!localStream) return;

  const videoTrack = localStream.getVideoTracks()[0];
  if (!videoTrack) return;

  videoTrack.enabled = !videoTrack.enabled;
  const camOn = videoTrack.enabled ? 1 : 0;

  // Update button label
  const btn = document.getElementById('btnCamera');
  if (btn) btn.textContent = camOn ? '📷 Camera ON' : '📷 Camera OFF';

  // Show/hide camera-off overlay
  const offDiv = document.getElementById('teacherCamOff');
  if (offDiv) {
    offDiv.style.display = camOn ? 'none' : 'flex';
    if (!camOn) {
      // Show teacher pic or initials
      const pic = document.getElementById('teacherCamOffPic');
      if (teacherPic && pic) {
        pic.src = '../uploads/profile_pic/teachers/' + teacherPic;
        pic.style.display = 'block';
        document.getElementById('teacherCamOffInitials').style.display = 'none';
      }
    }
  }

  fetch('teacher_virtual_class.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=set_camera&class_id=' + currentClassId + '&camera_on=' + camOn
  });

  showToast(camOn ? 'Camera ON' : 'Camera OFF', 'info');
}

function toggleSize() {
  document.querySelector('.video-box')
    .classList.toggle('maximized');
}

// Just closes the modal/camera — does NOT change status
function closeVideo() {
  document.getElementById('videoModal').style.display = 'none';

  if (localStream) {
    localStream.getTracks().forEach(track => track.stop());
    localStream = null;
  }

  const video = document.getElementById('localVideo');
  video.pause();
  video.srcObject = null;

  // Close all peer connections
  Object.values(peerConnections).forEach(pc => pc.close());
  peerConnections = {};

if (currentClassId) {
  fetch('teacher_virtual_class.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=set_camera&class_id=' + currentClassId + '&camera_on=0'
  });
}

  // Clear student refresh timer
  // Clear student refresh timer
  if (window._studentRefreshTimer) {
    clearInterval(window._studentRefreshTimer);
    window._studentRefreshTimer = null;
  }

  // Reset signaling flag para makapag-start ulit sa susunod
  window._teacherSignalingStarted = false;
}

// Called ONLY when teacher intentionally ends the class
function endClass() {
  if (!currentClassId) return;

  const cls = vclasses.find(c => c.id === currentClassId);
  if (cls) {
    cls.status = 'ended';
    fetch('teacher_virtual_class.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=set_ended&class_id=' + currentClassId
    });
  }
    // DAGDAG SA LOOB NG endClass(), bago ang closeVideo():
    if (window.firebaseRef && window.firebaseDB && currentClassId) {
    const { ref, remove } = window.firebaseRef;
      remove(ref(window.firebaseDB, 'signals/' + currentClassId));
    }
  closeVideo();
  clearInterval(countdownTimer);
  countdownTimer = null;
  renderVClasses();
  showToast("Class ended.", "info");
}

if (mediaRecorder && mediaRecorder.state !== "inactive") {
  mediaRecorder.stop();
}
function openRecording(id) {
  const cls = vclasses.find(c => c.id === id);
  if (!cls || !cls.recordingUrl) {
    showToast("No recording available yet", "error");
    return;
  }

  document.getElementById('videoModal').style.display = 'flex';
  document.getElementById('videoTitle').innerText = "Recorded Class #" + id;

  const video = document.getElementById('localVideo');

  // STOP camera if running
  if (localStream) {
    localStream.getTracks().forEach(t => t.stop());
    localStream = null;
  }

  video.srcObject = null;
  video.src = cls.recordingUrl;
  video.controls = true;
  video.play();
}
// Data from PHP — same shape as the original JS array
var vclasses = <?= json_encode(array_values($vclasses_js), JSON_UNESCAPED_UNICODE) ?>;
var teacherPic = <?= json_encode($teacher_pic) ?>;
var currentFilter = 'all';
var currentSort   = 'date-asc';
var searchQuery   = '';

function getFilteredClasses() {
  var filtered = vclasses.slice();

  if (currentFilter !== 'all') {
    filtered = filtered.filter(function(c) { return c.status === currentFilter; });
  }

  if (searchQuery.trim()) {
    var q = searchQuery.toLowerCase();
    filtered = filtered.filter(function(c) {
    return c.title.toLowerCase().indexOf(q) !== -1;
    });
  }

  filtered.sort(function(a, b) {
    switch(currentSort) {
        case 'date-asc':
          return (a.raw_date + ' ' + a.raw_time).localeCompare(b.raw_date + ' ' + b.raw_time);
        case 'date-desc':
          return (b.raw_date + ' ' + b.raw_time).localeCompare(a.raw_date + ' ' + a.raw_time);

      case 'name-az':
        return a.title.localeCompare(b.title);

      default:
        return 0;
    }
  });

  return filtered;
}

function cancelClass(id) {
  if (!confirm("Cancel this virtual class?")) return;

  fetch('teacher_virtual_class.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=cancel&class_id=' + id
  })
  .then(res => res.text())
  .then(() => {
    updateCancelledClass(id);   // 🔥 update JS memory
    renderVClasses();           // 🔥 refresh UI instantly
    showToast("Class cancelled successfully.", "error");
  });
}

function renderVClasses() {
  var grid     = document.getElementById('vclassGrid');
  var filtered = getFilteredClasses();

  var totalLive     = vclasses.filter(function(c){ return c.status === 'live'; }).length;
  var totalUpcoming = vclasses.filter(function(c){ return c.status === 'upcoming'; }).length;

  document.getElementById('class-count-text').innerHTML =
    'Showing <strong>' + filtered.length + '</strong> sessions &middot; ' +
    totalLive + ' live, ' + totalUpcoming + ' upcoming';

  if (filtered.length === 0) {
    grid.innerHTML = '<div class="empty-state"><div class="empty-icon">🎥</div><p>No classes found</p><span>Try adjusting your search or filter criteria.</span></div>';
    return;
  }

  grid.innerHTML = filtered.map(function(c) {
    var statusBadge  = c.status === 'live' ? 'badge-live' : c.status === 'upcoming' ? 'badge-blue' : 'badge-purple';
    var statusLabel = '';
    if (c.status === 'upcoming') statusLabel = 'Upcoming';
    else if (c.status === 'live') statusLabel = 'Live';
    else if (c.status === 'ended') statusLabel = 'Ended';
    else if (c.status === 'cancelled') statusLabel = 'Cancelled';
    else statusLabel = c.status;

    // ACTION BUTTON FIX
    var actionHtml = '';

    if (c.status === 'live') {

      actionHtml =
        '<button class="join-btn" style="background:var(--accent);color:white;font-weight:700;" onclick="startClass(' + c.id + ')">▶️ Start Now</button>' +
        '<button class="join-btn" style="background:#ef4444;color:white;font-weight:600;" onclick="endClass_direct(' + c.id + ')">❌ End Class</button>';

    } else if (c.status === 'upcoming') {

      var schedTime = new Date(c.date + ' ' + c.time);
      var now = new Date();
      // Allow start if within 5 minutes before or any time after scheduled time
      var canStart = now >= new Date(schedTime.getTime() - 5 * 60 * 1000);

    if (canStart) {
        actionHtml =
          '<button class="join-btn" style="background:var(--accent)" onclick="startClass(' + c.id + ')">▶️ Start Now</button>' +
          '<button class="join-btn" style="background:#ef4444;color:white;font-weight:600;" onclick="cancelClass(' + c.id + ')">❌ Cancel</button>';
      } else {
        actionHtml =
          '<button class="join-btn" style="background:#ef4444;color:white;font-weight:600;" onclick="cancelClass(' + c.id + ')">❌ Cancel</button>';
      }

    } else if (c.status === 'ended') {

      actionHtml =
        '<button class="join-btn" style="background:var(--border);color:var(--text2)" onclick="openRecording(' + c.id + ')">📼 Review</button>';

    } else if (c.status === 'cancelled') {

      actionHtml =
        '<span style="color:#ef4444;font-size:12px;font-weight:600;">Cancelled</span>';

    }

    return '<div class="vclass-card">' +
      '<div class="vclass-card-header">' +
        '<div class="vclass-icon">' + c.icon + '</div>' +
        '<div class="vclass-info-main">' +
          '<div class="vclass-title-row">' +
            '<span class="vclass-title">' + c.title + '</span>' +
            '<span class="badge ' + statusBadge + '">' + statusLabel + '</span>' +
          '</div>' +
          '<div class="vclass-meta-row">' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div class="vclass-card-body">' +
        '<div class="vclass-schedule">' +
          '<div class="sched-item"><span class="sched-label">Date</span><span class="sched-value">' + c.date + '</span></div>' +
          '<div class="sched-item"><span class="sched-label">Time</span><span class="sched-value">' + c.time + '</span></div>' +
          '<div class="sched-item"><span class="sched-label">Duration</span><span class="sched-value">' + c.duration + ' min</span></div>' +
        '</div>' +
      '</div>' +
      '<div class="vclass-card-footer">' +
        '<div class="vclass-actions">' +
          actionHtml +
          '<button class="card-action-btn" title="Edit" onclick="showToast(\'Edit mode\',\'info\')">✏️</button>' +
          '<button class="card-action-btn danger" title="Delete" onclick="deleteClass(' + c.id + ')">🗑</button>' +
        '</div>' +
      '</div>' +
    '</div>';
  }).join('');
}
// --- Filter tabs ---
var filterTabs = document.querySelectorAll('.filter-tab');
filterTabs.forEach(function(tab) {
  tab.addEventListener('click', function() {
    filterTabs.forEach(function(t) { t.classList.remove('active'); });
    tab.classList.add('active');
    currentFilter = tab.getAttribute('data-filter');
    renderVClasses();
  });
});

// --- Search ---
var searchInput = document.getElementById('searchInput');
var searchTimer;
searchInput.addEventListener('input', function() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(function() {
    searchQuery = searchInput.value;
    renderVClasses();
  }, 200);
});

// --- Sort ---
document.getElementById('sortSelect').addEventListener('change', function() {
  currentSort = this.value;
  renderVClasses();
});

// --- Delete (via hidden form POST) ---
function deleteClass(id) {
  var cls = vclasses.find(function(c) { return c.id === id; });
  if (!cls) return;
  if (confirm('Are you sure you want to delete this class session?')) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'teacher_virtual_class.php';
    form.innerHTML =
      '<input type="hidden" name="action"  value="delete">' +
      '<input type="hidden" name="del_id"  value="' + id + '">';
    document.body.appendChild(form);
    form.submit();
  }
}
function endClass_direct(id) {
  if (!confirm('End this live class?')) return;
  currentClassId = id;
  const cls = vclasses.find(c => c.id === id);
  if (cls) {
    cls.status = 'ended';
    fetch('teacher_virtual_class.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=set_ended&class_id=' + id
    });
  }
  closeVideo();
  clearInterval(countdownTimer);
  countdownTimer = null;
  renderVClasses();
  showToast('Class ended.', 'info');
}
// --- Modal open/close ---
function openModal() {
  document.getElementById('createModal').classList.add('open');
  document.body.style.overflow = 'hidden';
  setTimeout(function() { document.getElementById('newTitle').focus(); }, 100);
}
function closeModal() {
  document.getElementById('createModal').classList.remove('open');
  document.body.style.overflow = '';
  ['newTitle','newDate','newTime','newDuration'].forEach(function(id) {
    document.getElementById(id).value = '';
  });
}
document.getElementById('createModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') { closeModal(); closeSidebar(); }
});

// --- Create class (POST to PHP) ---
function createClass() {
  var title    = document.getElementById('newTitle').value.trim();
  var subject = document.getElementById('newSubject').value;
  var dateVal  = document.getElementById('newDate').value;
  var timeVal  = document.getElementById('newTime').value;
  var duration = document.getElementById('newDuration').value || '60';

  if (!title)            { showToast('Please enter a session title.', 'error'); document.getElementById('newTitle').focus(); return; }
  if (!subject) {
    showToast('Please select a subject.', 'error');
    document.getElementById('newSubject').focus();
    return;
  }
  if (!dateVal || !timeVal) { showToast('Please select date and time.', 'error'); return; }

  var form = document.createElement('form');
  form.method = 'POST';
  form.action = 'teacher_virtual_class.php';
  var fields = {
    action: 'create',
    title: title,
    teacher_subject_id: subject,
    date: dateVal,
    time: timeVal,
    duration: duration,
  };
  Object.keys(fields).forEach(function(k) {
    var i = document.createElement('input');
    i.type = 'hidden'; i.name = k; i.value = fields[k];
    form.appendChild(i);
  });
  document.body.appendChild(form);
  form.submit();
}

// --- Sidebar ---
var sidebar = document.getElementById('sidebar');
var overlay = document.getElementById('sidebar-overlay');
var menuBtn = document.getElementById('menuBtn');
function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow = ''; }
menuBtn.addEventListener('click', function() { sidebar.classList.contains('open') ? closeSidebar() : openSidebar(); });
overlay.addEventListener('click', closeSidebar);
document.querySelectorAll('.nav-item').forEach(function(item) {
  item.addEventListener('click', function() { if (window.innerWidth <= 768) closeSidebar(); });
});

// --- Theme ---
var themeToggle = document.getElementById('themeToggle');
var themeIcon   = document.getElementById('theme-icon');
var themeText   = document.getElementById('theme-text');
function applyTheme(isLight) {
  if (isLight) { document.body.classList.add('light'); if(themeIcon) themeIcon.textContent = '☀️'; if(themeText) themeText.textContent = 'Light Mode'; }
  else          { document.body.classList.remove('light'); if(themeIcon) themeIcon.textContent = '🌙'; if(themeText) themeText.textContent = 'Dark Mode'; }
}
(function() { if (localStorage.getItem('excelearn-theme') === 'light') applyTheme(true); })();
if (themeToggle) {
  themeToggle.addEventListener('click', function() {
    var isLight = document.body.classList.contains('light');
    applyTheme(!isLight);
    localStorage.setItem('excelearn-theme', !isLight ? 'light' : 'dark');
  });
}

// --- Toast ---
function showToast(msg, type) {
  type = type || 'success';
  var icons = { success:'✅', error:'❌', info:'ℹ️' };
  var container = document.getElementById('toast-container');
  var t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = '<span class="toast-icon">' + (icons[type] || '✅') + '</span><span>' + msg + '</span>';
  container.appendChild(t);
  setTimeout(function() {
    t.style.opacity = '0'; t.style.transform = 'translateX(60px)'; t.style.transition = 'all .3s ease';
    setTimeout(function() { t.remove(); }, 300);
  }, 3000);
}

// --- Resize ---
var resizeTimer;
window.addEventListener('resize', function() {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(function() { if (window.innerWidth > 768) closeSidebar(); }, 150);
});

// --- Boot ---
renderVClasses();
// Refresh "Start Now" button availability every 10 seconds
setInterval(function() {
  if (!currentClassId) renderVClasses();
}, 10000);
<?php if (!empty($toast)): ?>
showToast(<?= json_encode($toast['msg']) ?>, <?= json_encode($toast['type']) ?>);
<?php endif; ?>

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