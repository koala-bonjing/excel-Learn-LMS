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
$student_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, firstname, middlename, lastname, section_id, profile_pic FROM tblstudents WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$student_initials = strtoupper(
    substr($student['firstname'] ?? 'S', 0, 1) .
    substr($student['lastname']  ?? 'T', 0, 1)
);
$student_fullname = trim(($student['firstname'] ?? '') . ' ' .($student['middlename'] ?? ''). ' ' . ($student['lastname'] ?? ''));
$section_id = $student['section_id'] ?? 0;
$student_section  = '—';
$student_grade    = '—';

if ($section_id) {
    $stmtSec = $conn->prepare("
        SELECT s.section, g.grade_level
        FROM tblsections s
        LEFT JOIN tblgrade_levels g ON g.grade_id = s.grade_level_id
        WHERE s.section_id = ?
        LIMIT 1
    ");
    $stmtSec->bind_param("i", $section_id);
    $stmtSec->execute();
    $secRow = $stmtSec->get_result()->fetch_assoc();
    $stmtSec->close();

    if ($secRow) {
        $student_section = $secRow['section'] ?? '—';
        $student_grade   = $secRow['grade_level']  ?? '—';
    }
}
$student_pic = $student['profile_pic'] ?? '';


// GET ALL VIRTUAL CLASSES (based on your table)
$stmtClasses = $conn->prepare("
    SELECT id, teacher_id, teacher_subject_id, title, status, date, time, duration
    FROM tblvirtualclasses
    WHERE 1
");

$stmtClasses->execute();
$result = $stmtClasses->get_result();

// Pre-fetch all teacher names to avoid N+1 queries
$teacherNames = [];
$resTch = $conn->query("SELECT id, firstname, lastname FROM tblusers WHERE role = 'teacher'");
if ($resTch) {
    while ($trow = $resTch->fetch_assoc()) {
        $teacherNames[(int)$trow['id']] = trim($trow['firstname'] . ' ' . $trow['lastname']);
    }
}

$allClasses = [];

while ($row = $result->fetch_assoc()) {

    $dateStr  = date('M j', strtotime($row['date']));
    $dateSort = $row['date'];
    $timeStr     = date('g:i A', strtotime($row['time']));
    $time24      = date('H:i:s',  strtotime($row['time']));

    $icon = '📹';

    $teacher_name = $teacherNames[(int)$row['teacher_id']] ?? 'Instructor';

    $allClasses[] = [
        'id'           => (int)$row['id'],
        'courseId'     => (int)$row['teacher_subject_id'],
        'courseName'   => 'Subject ' . $row['teacher_subject_id'], // safe fallback
        'icon'         => $icon,

        'name'         => $row['title'],
        'status'       => $row['status'],

        'date'         => $dateStr,
        'dateSort'     => $dateSort,
        'time'         => $timeStr,
        'time24'       => $time24,
        'duration'     => (int)$row['duration'],

        // wala sa DB → safe defaults (para di mag error JS mo)
        'platform'     => 'Virtual Class',
        'link'         => '',
        'meetingId'    => '—',
        'recording'    => false,
        'attendance'   => 'pending',

        'teacherName'  => $teacher_name ?: 'Instructor',
        'description'  => '',
        'agenda'       => [],
    ];
}

$stmtClasses->close();

$lesson_count = (int)$conn->query("
    SELECT COUNT(DISTINCT l.lesson_id)
    FROM tbl_teacher_lessons l
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = l.teacher_subject_id
    JOIN tbl_teachers_students tts ON tts.teacher_id = ts.teacher_id
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
<link rel="stylesheet" href="../assets/css/student/student_virtual_class.css">
<!-- PALITAN ANG DATI MONG FIREBASE SCRIPT BLOCK NG ITO -->
<script>
// Firebase loaded as regular scripts (hindi module) para maabot ng ibang functions
window.firebaseDB = null;
window.firebaseRef = null;
</script>

<!-- Firebase compat version — pwedeng gamitin sa regular script (hindi module) -->
<script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-database-compat.js"></script>

<script>
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

  // Wrap firebase compat methods to match the API your code expects
  window.firebaseRef = {
    ref:          function(db, path) { return db.ref(path); },
    push:         function(refObj, data) { return refObj.push(data); },
    set:          function(refObj, data) { return refObj.set(data); },
    remove:       function(refObj) { return refObj.remove(); },
    onChildAdded: function(refObj, callback) { refObj.on('child_added', callback); },
    get:          function(refObj) { return refObj.once('value'); }
  };

  console.log("✅ Firebase ready:", window.firebaseDB ? "connected" : "failed");
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
  width: 860px;
  max-width: 98vw;
  background: #0f172a;
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.video-box.maximized {
  width: 100vw;
  height: 100vh;
  max-width: 100vw;
  border-radius: 0;
}
.video-header {
  padding: 12px 16px;
  background: #111827;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
}
.video-header-left { display:flex; flex-direction:column; gap:2px; }
.video-header-left .vh-title { font-size:15px; font-weight:700; }
.video-header-left .vh-sub   { font-size:11px; color:#9ca3af; }
.video-header-actions { display:flex; gap:8px; }
.video-header-actions button {
  background: rgba(255,255,255,0.08);
  border: none;
  color: white;
  padding: 6px 10px;
  border-radius: 8px;
  cursor: pointer;
  font-size:14px;
}
/* PALITAN NG: */
.video-layout {
  display: flex;
  width: 100%;
  height: 420px;
  overflow: hidden;
  gap: 2px;
  background: #000;
}
.video-pane {
  flex: 1;
  background: #0a0f1a;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}
.video-pane video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transform: scaleX(-1);
}
.video-pane.student-pane video {
  transform: scaleX(-1);
}
.video-pane-label {
  position: absolute;
  bottom: 8px;
  left: 10px;
  background: rgba(0,0,0,0.55);
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 6px;
  z-index: 2;
}
.teacher-pane-live {
  position: absolute;
  top: 8px;
  left: 10px;
  background: #ef4444;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 4px;
  z-index: 2;
}
.teacher-pane-live::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #fff;
  animation: blink 1s infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }
@media(max-width:640px){
  .video-layout { flex-direction:column; height:auto; }
  .video-pane { height:200px; width:100%; }
}
.video-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #64748b;
  gap: 10px;
  height: 100%;
}
.video-placeholder .vp-icon { font-size: 52px; }
.video-placeholder .vp-text { font-size: 14px; }
.video-footer {
  padding: 12px 16px;
  display: flex;
  gap: 10px;
  justify-content: center;
  background: #111827;
  flex-wrap: wrap;
}
.video-footer button {
  padding: 8px 18px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
}
.vf-btn-cam   { background: #1e293b; color: white; }
.vf-btn-mic   { background: #1e293b; color: white; }
.vf-btn-leave { background: #ef4444; color: white; }
@media(max-width:640px){
  .video-layout { flex-direction:column; height:auto; }
  .teacher-video-wrapper { height:240px; }
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
    <div class="nav-section-label">Overview</div>
    <a class="nav-item" href="index.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <div class="nav-section-label">Learning</div>
    <a class="nav-item" href="student_courses.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      My Subjects
      <span class="nav-badge">4</span>
    </a>
    <a class="nav-item" href="student_performance_tasks.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M9 2H7a2 2 0 00-2 2v16a2 2 0 002 2h10a2 2 0 002-2V4a2 2 0 00-2-2h-2"/>
        <rect x="9" y="2" width="6" height="4" rx="1" ry="1"/>
        <path d="M9 14l2 2 4-4"/>
      </svg>
      Performance Tasks
    </a>
    <a class="nav-item active" href="student_virtual_class.php">
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
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0A1.65 1.65 0 0 0 9 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
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
    <div class="user-card" >
      <?php if ($student_pic): ?>
        <img src="../uploads/profile_pic/students/<?= htmlspecialchars($student_pic) ?>" 
             alt="Profile"
             style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid rgba(255,255,255,0.15); background:linear-gradient(135deg,var(--cyan),var(--accent));overflow:hidden;padding:0"
             onerror="this.style.display='none';document.getElementById('student-initials-fallback').style.display='flex';">
        <div id="student-initials-fallback"
             class="user-avatar"
             style="display:none;background:linear-gradient(135deg,var(--cyan),var(--accent))">
          <?= htmlspecialchars($student_initials) ?>
        </div>
      <?php else: ?>
        <div class="user-avatar" style="background:linear-gradient(135deg,var(--cyan),var(--accent))">
          <?= htmlspecialchars($student_initials) ?>
        </div>
      <?php endif; ?>
      <div class="user-info">
        <p><?= htmlspecialchars($student_fullname) ?></p>
        <span><?= htmlspecialchars($student_grade) ?> — <?= htmlspecialchars($student_section) ?></span>
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
      <div class="section-title">Virtual Class</div>
      <div class="section-sub">Join live sessions, watch recordings, and track your attendance across all courses.</div>
    </div>

    <div class="summary-row" id="summaryRow"></div>

    <div class="filter-bar">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInput" placeholder="Search classes..." oninput="filterClasses()">
      </div>
      <select class="filter-select" id="courseFilter" onchange="filterClasses()">
        <option value="all">All Courses</option>
      </select>
      <select class="filter-select" id="sortSelect" onchange="filterClasses()">
        <option value="default">Sort: Default</option>
        <option value="course">Group by Course</option>
        <option value="date-asc">Date: Soonest</option>
        <option value="date-desc">Date: Latest</option>
        <option value="name-asc">Name: A → Z</option>
      </select>
    </div>

    <div class="status-tabs" id="statusTabs"></div>

    <div id="classesContainer"></div>
  </div>
</main>
</div>
<!-- STUDENT VIDEO MODAL -->
<div id="studentVideoModal" class="video-modal">
  <div class="video-box" id="studentVideoBox">

    <div class="video-header">
      <div class="video-header-left">
        <div class="vh-title" id="svTitle">Live Class</div>
        <div class="vh-sub">🔴 You are in a live session</div>
      </div>
      <div class="video-header-actions">
        <button onclick="toggleStudentVideoSize()">⛶</button>
        <button onclick="leaveClass()">✕</button>
      </div>
    </div>

    <!-- PALITAN NG: -->
<div class="video-layout">

  <!-- TEACHER PANE (left) -->
  <div class="video-pane" id="teacherPane">
    <div class="teacher-pane-live" id="teacherLiveBadge">LIVE</div>
    <div class="video-placeholder" id="teacherPlaceholder">
      <div class="vp-icon">👨‍🏫</div>
      <div class="vp-text" id="teacherPlaceholderText">Waiting for teacher's camera...</div>
    </div>
    <div class="video-pane-label" id="teacherNameLabel">Teacher</div>
  </div>

  <!-- STUDENT PANE (right) -->
  <div class="video-pane student-pane" id="studentPane">
    <video id="studentLocalVideo" autoplay muted playsinline style="display:none"></video>
    <div class="video-placeholder" id="svPlaceholder">
      <div class="vp-icon">📹</div>
      <div class="vp-text">Your camera is off</div>
    </div>
    <div class="video-pane-label">You</div>
  </div>

</div>

    <div class="video-footer">
      <button class="vf-btn-cam" id="svCamBtn" onclick="toggleStudentCam()">📷 Camera</button>
      <button class="vf-btn-mic" id="svMicBtn" onclick="toggleStudentMic()">🎤 Mic</button>
      <button class="vf-btn-leave" onclick="leaveClass()">🚪 Leave Class</button>
    </div>

  </div>
</div>
<div class="modal-backdrop" id="modalBackdrop" onclick="closeModalOutside(event)">
  <div class="modal" id="classModal">
    <div class="modal-header-banner" id="modalBanner">
      <span class="modal-banner-icon" id="modalBannerIcon"></span>
      <div class="banner-overlay"></div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body" id="modalBody"></div>
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
<div class="toast-container" id="toast-container"></div>
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
// All classes injected from PHP — same shape as original JS array
var allClasses = <?= json_encode(array_values($allClasses), JSON_UNESCAPED_UNICODE) ?>;
console.log("ALL CLASSES:", allClasses);
// Build coursesMeta dynamically from real data
var coursesMeta = {};
var instructors  = {};
allClasses.forEach(function(c) {
    if (!coursesMeta[c.courseId]) {
        coursesMeta[c.courseId] = {
            title:       c.courseName,
            icon:        c.icon,
            category:    c.courseName,
            badgeClass:  'badge-cyan',
            bannerClass: 'banner-science'
        };
    }
    if (!instructors[c.courseId]) {
        var parts    = (c.teacherName || 'Instructor').split(' ');
        var initials = parts.map(function(p){ return p[0] || ''; }).join('').substring(0,2).toUpperCase();
        instructors[c.courseId] = {
            name:     c.teacherName || 'Instructor',
            initials: initials,
            color:    'linear-gradient(135deg,var(--accent),var(--purple))'
        };
    }
});

var activeStatus = "all";
var statusOptions = [
  {key:"all",      label:"All Classes"},
  {key:"live",     label:"🔴 Live Now"},
  {key:"upcoming", label:"⏳ Upcoming"},
  {key:"ended",    label:"✅ Ended"},
  {key:"cancelled",label:"❌ Cancelled"}
];

// Map "ended" from tblvirtualclasses to "completed" display logic
function normalizeStatus(s) {
    return s === 'ended' ? 'completed' : s;
}

function computeStats(){
  var total     = allClasses.length;
  var live      = allClasses.filter(function(c){ return c.status === 'live'; }).length;
  var upcoming  = allClasses.filter(function(c){ return c.status === 'upcoming'; }).length;
  var ended     = allClasses.filter(function(c){ return c.status === 'ended'; }).length;
  var totalMins = 0;
  var attended  = allClasses.filter(function(c){ return c.attendance === 'present'; }).length;
  allClasses.forEach(function(c){ totalMins += c.duration; });
  return { total:total, live:live, upcoming:upcoming, ended:ended, completed:ended, totalHours:Math.round(totalMins/60), attended:attended };
}

function renderSummary(){
  var s = computeStats();
  document.getElementById('summaryRow').innerHTML =
    '<div class="summary-card"><div class="summary-icon" style="background:var(--red-bg)">🔴</div><div><div class="summary-value">'+s.live+'</div><div class="summary-label">Live Now</div></div></div>'+
    '<div class="summary-card"><div class="summary-icon" style="background:var(--amber-bg)">📅</div><div><div class="summary-value">'+s.upcoming+'</div><div class="summary-label">Upcoming</div></div></div>'+
    '<div class="summary-card"><div class="summary-icon" style="background:var(--green-bg)">✅</div><div><div class="summary-value">'+s.ended+'</div><div class="summary-label">Ended · '+s.attended+' attended</div></div></div>'+
    '<div class="summary-card"><div class="summary-icon" style="background:var(--purple-bg)">⏱️</div><div><div class="summary-value">'+s.totalHours+'h</div><div class="summary-label">Total Hours Scheduled</div></div></div>';
}

function renderCourseFilter(){
  var sel = document.getElementById('courseFilter');
  sel.innerHTML = '<option value="all">All Courses</option>';
  // Unique course names from real data
  var seen = {};
  allClasses.forEach(function(c){
    if (!seen[c.courseId]) {
      seen[c.courseId] = true;
      sel.innerHTML += '<option value="'+c.courseId+'">'+c.icon+' '+c.courseName+'</option>';
    }
  });
}

function renderStatusTabs(){
  var s = computeStats();
  document.getElementById('statusTabs').innerHTML = statusOptions.map(function(o){
    var count = o.key === 'all' ? s.total :
                o.key === 'ended' ? s.ended :
                (s[o.key] || 0);
    return '<button class="status-tab'+(activeStatus===o.key?' active':'')+'" onclick="setStatus(\''+o.key+'\')">'+o.label+' ('+count+')</button>';
  }).join('');
}

function setStatus(st){
  activeStatus = st;
  renderStatusTabs();
  filterClasses();
}

function filterClasses(){
  var query     = document.getElementById('searchInput').value.toLowerCase();
  var courseVal = document.getElementById('courseFilter').value;
  var sort      = document.getElementById('sortSelect').value;

  var filtered = allClasses.filter(function(c){
    var matchStatus = activeStatus === 'all' || c.status === activeStatus;
    var matchCourse = courseVal === 'all' || c.courseId == courseVal;
    var matchSearch = c.name.toLowerCase().indexOf(query) !== -1 ||
                      c.courseName.toLowerCase().indexOf(query) !== -1 ||
                      (instructors[c.courseId] ? instructors[c.courseId].name.toLowerCase().indexOf(query) !== -1 : false);
    return matchStatus && matchCourse && matchSearch;
  });

  switch(sort){
    case 'date-asc':  filtered.sort(function(a,b){ return a.dateSort.localeCompare(b.dateSort); }); break;
    case 'date-desc': filtered.sort(function(a,b){ return b.dateSort.localeCompare(a.dateSort); }); break;
    case 'name-asc':  filtered.sort(function(a,b){ return a.name.localeCompare(b.name); }); break;
    case 'course':    filtered.sort(function(a,b){ return a.courseId - b.courseId || a.dateSort.localeCompare(b.dateSort); }); break;
  }

  renderClasses(filtered, sort === 'course');
}

function renderClasses(list, groupByCourse){
  var el = document.getElementById('classesContainer');
  if (!list.length) {
    el.innerHTML = '<div class="empty-state"><div class="empty-icon">📹</div><p>No classes found</p><span>Try adjusting your filters or search query.</span></div>';
    return;
  }

  if (groupByCourse) {
    var html = '';
    var grouped = {};
    list.forEach(function(c){ if(!grouped[c.courseId]) grouped[c.courseId]=[]; grouped[c.courseId].push(c); });
    Object.keys(grouped).forEach(function(cid){
      var cm = coursesMeta[cid];
      var classes = grouped[cid];
      var liveCount = classes.filter(function(c){ return c.status === 'live'; }).length;
      html += '<div class="course-divider">'+
        '<span class="course-divider-label"><span class="course-divider-icon">'+cm.icon+'</span>'+cm.title+'</span>'+
        (liveCount ? '<span class="badge badge-red" style="font-size:10px">🔴 '+liveCount+' live</span>' : '')+
        '<div class="course-divider-line"></div>'+
      '</div>';
      html += '<div class="class-list">';
      classes.forEach(function(c){ html += buildClassItem(c); });
      html += '</div>';
    });
    el.innerHTML = html;
  } else {
    el.innerHTML = '<div class="class-list">' + list.map(buildClassItem).join('') + '</div>';
  }
}

function getComputedStatus(c){
  if (c.status === 'cancelled') return 'cancelled';

  // Always trust the DB status for live and ended —
  // teacher controls when class actually starts/ends
  if (c.status === 'live')    return 'live';
  if (c.status === 'ended')   return 'ended';

  // For upcoming, still use time as a fallback check
  var start = new Date(c.dateSort + 'T' + c.time24);
  var end   = new Date(start.getTime() + (c.duration * 60000));
  var now   = new Date();

  if (now > end)               return 'ended';
  if (now >= start && now <= end) return 'live';
  return 'upcoming';
}

function buildClassItem(c){
  var cm = coursesMeta[c.courseId] || {icon:'📹', category:c.courseName, bannerClass:'banner-science'};
  var computedStatus = getComputedStatus(c);

  var displayStatus = computedStatus;
  var itemClass  = 'class-item ' + displayStatus + '-class';

  var isLive      = computedStatus === 'live';
  var isUpcoming  = computedStatus === 'upcoming';
  var isCompleted = computedStatus === 'ended';
  var isCancelled = computedStatus === 'cancelled';

  var statusIcon = '';
  if (isLive)      statusIcon = '<div class="class-status-icon live">🔴</div>';
  else if (isUpcoming)  statusIcon = '<div class="class-status-icon upcoming">⏳</div>';
  else if (isCompleted) statusIcon = '<div class="class-status-icon completed">✅</div>';
  else                  statusIcon = '<div class="class-status-icon cancelled">❌</div>';

  var liveTag = isLive ? '<span class="live-tag">● Live Now</span>' : '';

  var recBadge = isCompleted ?
    (c.recording ? '<span class="class-recording-badge available">📹 Recording</span>' : '<span class="class-recording-badge unavailable">No Recording</span>') : '';

  var actionBtn = '';
  if (isLive)
    actionBtn = '<button class="class-action-btn join-btn" onclick="event.stopPropagation();joinClass('+c.id+')">▶ Join Now</button>';
  else if (isUpcoming)
    actionBtn = '<button class="class-action-btn upcoming-btn" onclick="event.stopPropagation();openModal('+c.id+')">📅 View Details</button>';
  else if (isCompleted && c.recording)
    actionBtn = '<button class="class-action-btn replay-btn" onclick="event.stopPropagation();watchRecording('+c.id+')">▶ Watch Recording</button>';
  else if (isCompleted)
    actionBtn = '<button class="class-action-btn" onclick="event.stopPropagation();openModal('+c.id+')">📋 Details</button>';
  else
    actionBtn = '<button class="class-action-btn cancelled-btn" disabled>Cancelled</button>';

  var attBadge = '';
  if (isCompleted) {
    if (c.attendance === 'present') attBadge = '<span class="badge badge-green" style="font-size:10px">✓ Present</span>';
    else if (c.attendance === 'absent') attBadge = '<span class="badge badge-red" style="font-size:10px">✗ Absent</span>';
  }

  var courseTag = '<span class="class-course-tag">'+cm.icon+' '+cm.category+'</span>';
  var inst = instructors[c.courseId] || {name:'Instructor'};

  return '<div class="'+itemClass+'" onclick="openModal('+c.id+')">'+
    statusIcon+
    '<div class="class-info">'+
      '<div class="class-title-row">'+
        '<span class="class-name">'+c.name+'</span>'+
        liveTag+attBadge+
      '</div>'+
      '<div class="class-meta-row">'+
        '<span class="class-meta-item">'+courseTag+'</span>'+
        '<span class="class-meta-item">👤 '+inst.name+'</span>'+
        '<span class="class-meta-item">⏱️ '+c.duration+' min</span>'+
        '<span class="class-meta-item">📡 '+c.platform+'</span>'+
      '</div>'+
    '</div>'+
    '<div class="class-right">'+
      recBadge+
      '<div class="class-datetime-box">'+
        '<div class="class-date-label">'+(isLive?'Started':isUpcoming?'Scheduled':'Date')+'</div>'+
        '<div class="class-date-value">'+c.date+'</div>'+
        '<div class="class-time-value">'+c.time+'</div>'+
      '</div>'+
      actionBtn+
    '</div>'+
  '</div>';
}

/* ========== MODAL ========== */
function openModal(id, autoJoin){
  var c = allClasses.find(function(x){ return x.id === id; });
  if (!c) return;
  var cm   = coursesMeta[c.courseId] || {icon:'📹', title:c.courseName, bannerClass:'banner-science'};
  var inst = instructors[c.courseId] || {name:'Instructor', initials:'IN', color:'linear-gradient(135deg,var(--accent),var(--purple))'};
  var computedStatus = getComputedStatus(c);

  var isLive      = computedStatus === 'live';
  var isUpcoming  = computedStatus === 'upcoming';
  var isCompleted = computedStatus === 'ended';
  var isCancelled = computedStatus === 'cancelled';

  document.getElementById('modalBanner').className = 'modal-header-banner ' + cm.bannerClass;
  document.getElementById('modalBannerIcon').textContent = cm.icon;

  var statusBadge = isLive      ? '<span class="badge badge-red">🔴 Live Now</span>' :
                    isUpcoming  ? '<span class="badge badge-amber">⏳ Upcoming</span>' :
                    isCompleted ? '<span class="badge badge-green">✅ Ended</span>' :
                    '<span class="badge" style="background:var(--bg4);color:var(--text3)">❌ Cancelled</span>';

  var infoGrid =
    '<div class="modal-info-grid">'+
      '<div class="modal-info-card"><div class="modal-info-value">'+c.date+'</div><div class="modal-info-label">Date</div></div>'+
      '<div class="modal-info-card"><div class="modal-info-value">'+c.duration+'m</div><div class="modal-info-label">Duration</div></div>'+
      '<div class="modal-info-card"><div class="modal-info-value">'+c.platform+'</div><div class="modal-info-label">Platform</div></div>'+
    '</div>';

  var meetingHTML = '';
  if (!isCancelled && c.link) {
    meetingHTML =
      '<div class="modal-meeting-box">'+
        '<div class="modal-meeting-row"><span class="modal-meeting-label">Time</span><span class="modal-meeting-value">'+c.time+'</span></div>'+
        '<div class="modal-meeting-row"><span class="modal-meeting-label">Meeting Link</span>'+
          '<span class="modal-meeting-value"><a href="'+c.link+'" target="_blank">'+c.link+'</a>'+
          ' <button class="copy-btn" onclick="event.stopPropagation();copyText(\''+c.link.replace(/'/g,"\\'")+'\')"  >Copy</button></span></div>'+
      '</div>';
  } else if (!isCancelled) {
    meetingHTML = '<div class="modal-meeting-box"><div class="modal-meeting-row"><span class="modal-meeting-label">Time</span><span class="modal-meeting-value">'+c.time+'</span></div><div class="modal-meeting-row" style="color:var(--text3);font-size:13px">No meeting link provided.</div></div>';
  }

  var instructorHTML =
    '<div class="modal-instructor-row">'+
      '<div class="modal-instructor-avatar" style="background:'+inst.color+'">'+inst.initials+'</div>'+
      '<div><div class="modal-instructor-name">'+inst.name+'</div><div class="modal-instructor-role">Course Instructor — '+cm.title+'</div></div>'+
    '</div>';

  var attendanceHTML = '';
  if (isCompleted) {
    var attClass = c.attendance==='present'?'present':c.attendance==='absent'?'absent':'pending';
    var attIcon  = c.attendance==='present'?'✅':c.attendance==='absent'?'❌':'⏳';
    var attLabel = c.attendance==='present'?'Present':c.attendance==='absent'?'Absent':'Pending';
    attendanceHTML = '<div class="modal-attendance '+attClass+'">'+attIcon+' Attendance: '+attLabel+'</div>';
  } else if (isCancelled) {
    attendanceHTML = '<div class="modal-attendance cancelled">❌ This class has been cancelled</div>';
  }

  var actionBtn = '';
  if (isLive) {
    actionBtn = '<button class="btn btn-danger" onclick="joinClass('+c.id+');closeModal()">▶ Join Class Now</button>'+
                '<button class="btn btn-ghost" onclick="closeModal()">Close</button>';
  } else if (isUpcoming && c.link) {
    actionBtn = '<button class="btn btn-primary" onclick="copyText(\''+c.link.replace(/'/g,"\\'")+'\');showToast(\'Link copied!\',\'success\')">📋 Copy Link</button>'+
                '<button class="btn btn-ghost" onclick="closeModal()">Close</button>';
  } else if (isCompleted && c.recording) {
    actionBtn = '<button class="btn btn-primary" onclick="watchRecording('+c.id+');closeModal()">▶ Watch Recording</button>'+
                '<button class="btn btn-ghost" onclick="closeModal()">Close</button>';
  } else {
    actionBtn = '<button class="btn btn-ghost" onclick="closeModal()">Close</button>';
  }

  document.getElementById('modalBody').innerHTML =
    '<div class="modal-class-title">'+c.name+'</div>'+
    '<div class="modal-class-meta">'+
      '<span class="modal-class-meta-item">'+cm.icon+' '+cm.title+'</span>'+
      '<span style="margin-left:auto">'+statusBadge+'</span>'+
    '</div>'+
    attendanceHTML+
    instructorHTML+
    infoGrid+
    meetingHTML+
    '<div class="modal-actions-row">'+actionBtn+'</div>';

  document.getElementById('modalBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
  if (autoJoin) {
    setTimeout(function(){ joinClass(id); }, 80);
  }
}

function closeModal(){
  document.getElementById('modalBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}
function closeModalOutside(e){ if(e.target===document.getElementById('modalBackdrop')) closeModal(); }
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeModal(); });

function joinClass(id){
  // Show loading state inside modal
  var actionsRow = document.querySelector('.modal-actions-row');
  if (actionsRow) {
    actionsRow.innerHTML = '<div style="text-align:center;padding:10px;color:var(--text2);font-size:0.9rem">⏳ Joining class...</div>';
  }

  fetch('join_virtual_class.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'class_id=' + encodeURIComponent(id)
  })
  .then(function(response){ return response.text(); })
  .then(function(text){
    console.log("RAW RESPONSE:", text);

    var jsonStart = text.indexOf('{');
    var jsonText  = jsonStart !== -1 ? text.substring(jsonStart) : text;
    var data;

    try {
      data = JSON.parse(jsonText);
    } catch(e) {
      // Show raw server error inside modal
      if (actionsRow) {
        actionsRow.innerHTML =
          '<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;padding:12px 14px;margin-bottom:12px;width:100%;box-sizing:border-box">'+
            '<div style="color:var(--red);font-weight:700;margin-bottom:6px">❌ Server Error — Invalid Response</div>'+
            '<pre style="font-size:11px;color:var(--text2);white-space:pre-wrap;word-break:break-all;max-height:120px;overflow:auto;margin:0">'+text.substring(0,600).replace(/</g,"&lt;").replace(/>/g,"&gt;")+'</pre>'+
          '</div>'+
          '<button class="btn btn-ghost" onclick="closeModal()">Close</button>';
      }
      return;
    }

    if (data.success) {
        if (actionsRow) {
          actionsRow.innerHTML = '<div style="text-align:center;padding:10px;color:var(--green);font-weight:600">✅ Joined! Opening class...</div>';
        }
        setTimeout(function(){
          closeModal();
          openClassRoom(data.class_id, data.link || '');
        }, 600);
    } else {
      if (actionsRow) {
        actionsRow.innerHTML =
          '<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;padding:12px 14px;margin-bottom:12px;width:100%;box-sizing:border-box">'+
            '<div style="color:var(--red);font-weight:700;margin-bottom:4px">❌ Unable to Join</div>'+
            '<div style="font-size:0.875rem;color:var(--text2)">'+(data.message || 'An unknown error occurred.')+'</div>'+
          '</div>'+
          '<button class="btn btn-danger" onclick="joinClass('+id+')">🔄 Try Again</button>'+
          '<button class="btn btn-ghost" onclick="closeModal()">Close</button>';
      }
    }
  })
  .catch(function(error){
    console.error("FETCH ERROR:", error);
    if (actionsRow) {
      actionsRow.innerHTML =
        '<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;padding:12px 14px;margin-bottom:12px;width:100%;box-sizing:border-box">'+
          '<div style="color:var(--red);font-weight:700;margin-bottom:4px">❌ Network Error</div>'+
          '<div style="font-size:0.875rem;color:var(--text2)">'+String(error)+'</div>'+
        '</div>'+
        '<button class="btn btn-danger" onclick="joinClass('+id+')">🔄 Try Again</button>'+
        '<button class="btn btn-ghost" onclick="closeModal()">Close</button>';
    }
  });
}
var svStream = null;
var svCamOn  = false;
var svMicOn  = false;

var svCurrentClassId = null;
var svPollTimer      = null;

function openClassRoom(classId, link) {
  var c = allClasses.find(function(x){ return x.id === classId; });
  if (!c) return;

  svCurrentClassId = classId;

  document.getElementById('svTitle').textContent = c.name;
  var inst = instructors[c.courseId];
  if (inst) document.getElementById('teacherNameLabel').textContent = inst.name;

  document.getElementById('studentVideoModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';

  svCamOn = false;
  svMicOn = false;
  document.getElementById('svPlaceholder').style.display = 'flex';
  document.getElementById('studentLocalVideo').style.display = 'none';
  document.getElementById('svCamBtn').textContent = '📷 Camera';
  document.getElementById('svMicBtn').textContent = '🎤 Mic';

  document.getElementById('teacherPlaceholder').style.display = 'flex';
  document.getElementById('teacherPlaceholderText').textContent = 'Waiting for teacher\'s camera...';

  studentLastSignalId = 0;
  if (studentPc) { studentPc.close(); studentPc = null; }

  (async function() {
    startStudentSignaling(); 

    await toggleStudentCam(); 

    svPollTimer = setInterval(pollTeacherStatus, 15000);

    setTimeout(function() {
      sendStudentSignal({ type: 'student-joined' });
    }, 2000);
  })();
}

function pollTeacherStatus() {
  if (!svCurrentClassId) return;
  var teacherPane = document.getElementById('teacherPane');
  if (teacherPane.querySelector('video.remote-stream')) return;

  fetch('join_virtual_class.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=teacher_status&class_id=' + encodeURIComponent(svCurrentClassId)
  })
  .then(function(r){ return r.json(); })
  .then(function(data) {
    var teacherPane = document.getElementById('teacherPane');
    if (teacherPane.querySelector('video.remote-stream')) return; // re-check after fetch

    var badge           = document.getElementById('teacherLiveBadge');
    var placeholder     = document.getElementById('teacherPlaceholder');
    var placeholderText = document.getElementById('teacherPlaceholderText');

    document.querySelectorAll('.teacher-cam-status').forEach(function(el){ el.remove(); });

    var isLive = data.status === 'live';
    var camOn  = data.camera_on == 1 || data.camera_on === '1' || data.camera_on === true;

    if (isLive && camOn) {
      badge.style.display = 'flex';
      placeholder.style.display = 'none';
    } else if (isLive && !camOn) {
      badge.style.display = 'flex';
      placeholder.style.display = 'flex';
      placeholderText.textContent = 'Teacher camera is off...';
    } else if (data.status === 'ended') {
      badge.style.display = 'none';
      placeholder.style.display = 'flex';
      placeholderText.textContent = 'Class has ended.';
      clearInterval(svPollTimer);
      showToast('Class has ended.', 'info');
    } else {
      badge.style.display = 'none';
      placeholder.style.display = 'flex';
      placeholderText.textContent = 'Waiting for teacher to start...';
    }
  })
  .catch(function(){ });
}
async function toggleStudentCam() {
  var video = document.getElementById('studentLocalVideo');
  var placeholder = document.getElementById('svPlaceholder');

  if (!svStream) {
    try {
      svStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
      svCamOn = true;
      svMicOn = true;
      video.srcObject = svStream;
      video.style.display = 'block';
      placeholder.style.display = 'none';
      document.getElementById('svCamBtn').textContent = '📷 Cam ON';
      document.getElementById('svMicBtn').textContent = '🎤 Mic ON';
      await video.play();
      updateMediaStatus();
    } catch(err) {
      showToast('Camera error: ' + err.message, 'error');
    }
    return;
  }

  svCamOn = !svCamOn;
  svStream.getVideoTracks().forEach(function(t){ t.enabled = svCamOn; });
  video.style.display = svCamOn ? 'block' : 'none';
  placeholder.style.display = svCamOn ? 'none' : 'flex';
  document.getElementById('svCamBtn').textContent = svCamOn ? '📷 Cam ON' : '📷 Camera';
  updateMediaStatus();

  // ✅ If peer connection exists, replace/update the video track
  if (studentPc && svStream) {
    const videoTrack = svStream.getVideoTracks()[0];
    const sender = studentPc.getSenders().find(s => s.track && s.track.kind === 'video');
    if (sender && videoTrack) {
      sender.replaceTrack(svCamOn ? videoTrack : null).catch(() => {});
    }
  }
}

function toggleStudentMic() {
  if (!svStream) { showToast('Turn on camera first.', 'info'); return; }
  svMicOn = !svMicOn;
  svStream.getAudioTracks().forEach(function(t){ t.enabled = svMicOn; });
  document.getElementById('svMicBtn').textContent = svMicOn ? '🎤 Mic ON' : '🎤 Mic';
  updateMediaStatus();
}

function updateMediaStatus() {
  if (!svCurrentClassId) return;
  fetch('join_virtual_class.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=update_media_status&class_id=' + svCurrentClassId +
          '&camera_status=' + (svCamOn ? 1 : 0) +
          '&mic_status='    + (svMicOn ? 1 : 0)
  });
}

function toggleStudentVideoSize() {
  document.getElementById('studentVideoBox').classList.toggle('maximized');
}
let studentPc = null;

function sendStudentSignal(data) {
  const { ref, push } = window.firebaseRef;
  const db = window.firebaseDB;
  if (!db || !svCurrentClassId) return;
  push(ref(db, 'signals/' + svCurrentClassId + '/to_teacher'), {
    from_role: 'student',
    from_id: <?= $student_id ?>,
    signal: JSON.stringify(data),
    created_at: Date.now()
  });
}


function startStudentSignaling() {
  if (window._studentSignalingStarted) return; // ← guard
  window._studentSignalingStarted = true;

  const { ref, onChildAdded } = window.firebaseRef;
  const db = window.firebaseDB;
  if (!db) return;

  const seenSignals = new Set(); // ← deduplicate

  onChildAdded(ref(db, 'signals/' + svCurrentClassId + '/to_student'), async (snapshot) => {
    if (!snapshot.key || seenSignals.has(snapshot.key)) return; // ← skip duplicates
    seenSignals.add(snapshot.key);
    const sig = snapshot.val();
    if (!sig) return;

    const data = JSON.parse(sig.signal);

    if (data.type === 'offer') {
      // Skip re-offer if already connected stably
      if (studentPc && studentPc.connectionState === 'connected') return;
      if (studentPc) studentPc.close();
      studentPc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });

      studentPc.onicecandidate = e => {
        if (e.candidate) sendStudentSignal({ type: 'ice-student', candidate: e.candidate });
      };

      studentPc.onconnectionstatechange = () => {
        const state = studentPc ? studentPc.connectionState : null;
        console.log('Student PC state:', state);
        if (state === 'connected') {
          // Hide placeholder once truly connected
          document.getElementById('teacherPlaceholder').style.display = 'none';
          document.getElementById('teacherLiveBadge').style.display = 'flex';
        }
        if (state === 'failed' || state === 'disconnected') {
          // Show placeholder again, teacher may reconnect
          document.getElementById('teacherPlaceholder').style.display = 'flex';
          document.getElementById('teacherPlaceholderText').textContent = 'Reconnecting...';
        }
      };

      if (!svStream) {
        await new Promise(resolve => {
          const check = setInterval(() => {
            if (svStream) { clearInterval(check); resolve(); }
          }, 200);
          setTimeout(() => { clearInterval(check); resolve(); }, 5000);
        });
      }
      if (svStream) svStream.getTracks().forEach(track => studentPc.addTrack(track, svStream));

      studentPc.ontrack = e => {
        const teacherPane = document.getElementById('teacherPane');
        teacherPane.querySelectorAll('.teacher-cam-status').forEach(el => el.remove());
        document.getElementById('teacherPlaceholder').style.display = 'none';
        document.getElementById('teacherLiveBadge').style.display = 'flex';

        let remoteVideo = teacherPane.querySelector('video.remote-stream');
        if (!remoteVideo) {
          remoteVideo = document.createElement('video');
          remoteVideo.className = 'remote-stream';
          remoteVideo.autoplay = true;
          remoteVideo.playsinline = true;
          remoteVideo.muted = false;
          remoteVideo.style.cssText = 'width:100%;height:100%;object-fit:cover;position:absolute;top:0;left:0;z-index:3;';
          teacherPane.appendChild(remoteVideo);
        }
        remoteVideo.srcObject = e.streams[0];
        remoteVideo.play().catch(() => {});
      };

      await studentPc.setRemoteDescription(new RTCSessionDescription(data.sdp));
      const answer = await studentPc.createAnswer();
      await studentPc.setLocalDescription(answer);
      sendStudentSignal({ type: 'answer', sdp: studentPc.localDescription });

    } else if (data.type === 'ice-teacher') {
      if (studentPc) await studentPc.addIceCandidate(new RTCIceCandidate(data.candidate));
    }
  });
}

function leaveClass() {
  document.getElementById('studentVideoModal').style.display = 'none';
  document.body.style.overflow = '';

  if (svStream) {
    svStream.getTracks().forEach(function(t){ t.stop(); });
    svStream = null;
  }
  var video = document.getElementById('studentLocalVideo');
  video.pause();
  video.srcObject = null;
  svCamOn = false;
  svMicOn = false;

  // Close WebRTC
  if (studentPc) { studentPc.close(); studentPc = null; }

  // Remove teacher remote video
  var remoteVid = document.querySelector('#teacherPane video.remote-stream');
  if (remoteVid) remoteVid.remove();

  clearInterval(svPollTimer);
  svPollTimer = null;
  // Mark student as left
    if (svCurrentClassId) {
      fetch('join_virtual_class.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=update_media_status&class_id=' + svCurrentClassId + '&camera_status=0&mic_status=0'
      });
      fetch('join_virtual_class.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=leave_class&class_id=' + svCurrentClassId
      });
    }
    svCurrentClassId = null;
    window._studentSignalingStarted = false;
}
function watchRecording(id){
  var c = allClasses.find(function(x){ return x.id === id; });
  if (!c) return;
  showToast('Opening recording: "'+c.name+'"', 'success');
}

function copyText(text){
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(function(){ showToast('Copied!','success'); });
  } else {
    var ta = document.createElement('textarea');
    ta.value = text; document.body.appendChild(ta); ta.select();
    document.execCommand('copy'); document.body.removeChild(ta);
    showToast('Copied!','success');
  }
}

/* ========== SIDEBAR ========== */
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('open');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}

/* ========== THEME ========== */
// PALITAN NG:
function applyTheme(theme) {
  var isLight = theme === 'light';
  document.body.classList.toggle('light', isLight);
  // theme-icon/theme-text are teacher-only elements, skip if not present
  var ti = document.getElementById('theme-icon');
  var tt = document.getElementById('theme-text');
  if (ti) ti.textContent = isLight ? '☀️' : '🌙';
  if (tt) tt.textContent = isLight ? 'Light Mode' : 'Dark Mode';
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

/* ========== TOAST ========== */
function showToast(msg, type){
  type = type || 'success';
  var icons = {success:'✅', error:'❌', info:'ℹ️'};
  var container = document.getElementById('toast-container');
  var t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = '<span class="toast-icon">'+(icons[type]||'✅')+'</span><span>'+msg+'</span>';
  container.appendChild(t);
  setTimeout(function(){
    t.style.opacity='0'; t.style.transform='translateX(60px)'; t.style.transition='all .3s ease';
    setTimeout(function(){ t.remove(); }, 300);
  }, 3000);
}

/* ========== INIT ========== */
renderSummary();
renderCourseFilter();
renderStatusTabs();
filterClasses();

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