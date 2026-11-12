<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
$teacher_id = $_SESSION['teacher_id'] ?? 1; 

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

$badge_students = (int)$conn->query("
    SELECT COUNT(DISTINCT student_id) FROM tbl_teachers_students
    WHERE teacher_subject_id IN (SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = $teacher_id)
")->fetch_row()[0];

$badge_virtualclass = (int)$conn->query("
    SELECT COUNT(*) FROM tblvirtualclasses 
    WHERE teacher_id = $teacher_id AND status IN ('upcoming','live')
")->fetch_row()[0];

$badge_gradebook = (int)$conn->query("
    SELECT COUNT(DISTINCT r.student_id)
    FROM tblquiz_results r
    JOIN tblquizzes q ON q.quiz_id = r.quizzes_id
    JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    JOIN tbl_teacher_subjects s ON s.teacher_subject_id = l.teacher_subject_id
    WHERE s.teacher_id = $teacher_id
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ExceLearn LMS — Teacher Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/teacher/about.css">
<style>
 /*── Modal overlay base ── */
.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  z-index: 9999;
  align-items: center;
  justify-content: center;
}
.modal-overlay.open {
  display: flex;
}
.modal {
  background: var(--bg2, #1e293b);
  border-radius: 16px;
  width: 90%;
  max-width: 460px;
  overflow: hidden;
  box-shadow: 0 25px 60px rgba(0,0,0,0.5);
}
.modal-header {
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}
.modal-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text, #f1f5f9);
}
.modal-close {
  background: none;
  border: none;
  color: var(--text3, #64748b);
  font-size: 18px;
  cursor: pointer;
  line-height: 1;
  padding: 4px;
}
.modal-body {
  padding: 16px 20px;
}
.modal-footer {
  display: flex;
  justify-content: center;
  gap: 10px;
  padding: 0 20px 24px;
}

 /*── Notif modal centered ── */
#notifModal {
  align-items: center;
}
#notifModal .modal {
  display: flex;
  flex-direction: column;
  max-height: 80vh;
}

@media (max-width: 640px) {
  #notifModal .modal {
    border-radius: 16px;
    max-height: 85dvh;
    width: calc(100% - 32px);
  }
}
.about-hero-name {
    margin-top: 50px;
  }
@media (max-width: 576px) {
  .about-hero-name {
    margin-top: -10px;
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
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo" style="padding:20px 16px 16px;display:flex;align-items:center;gap:10px;flex-wrap:nowrap;overflow:hidden;">
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
      Subjects
      <span class="nav-badge"><?= $active_courses ?></span>
    </a>
    <a class="nav-item" href="teacher_performance_tasks.php">
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
    <a class="nav-item active" href="about.php">
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
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <?php if ($teacher_pic): ?>
      <img src="<?= htmlspecialchars($teacher_pic) ?>" alt="Profile"
           class="user-avatar" style="object-fit:cover;">
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

<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<main class="main">
  <div class="topbar">
  <button class="menu-btn" onclick="toggleSidebar()"><div class="hamburger"><span></span><span></span><span></span></div></button>
  <img class="topbar-logo-desktop" src="../assets/img/Excelsior_Logo_long.png" alt="Logo" style="height:38px;width:auto;">
  <div class="topbar-logo-mobile" style="display:none;align-items:center;gap:10px;flex-wrap:nowrap;overflow:hidden;">
    <img src="../assets/img/Excelsior_Seal_Logo.png" alt="ExceLearn Logo"
         style="height:38px;width:auto;object-fit:contain;flex-shrink:0;">
    <div style="min-width:0">
      <div style="font-weight:800;font-size:1.15rem;letter-spacing:-0.05em;line-height:1;white-space:nowrap;">Exce<span style="color:#22c55e;">Learn</span></div>
      <div style="font-size:0.7rem;color:#9ca3af;white-space:nowrap;margin-top:2px;">Learn Better. Anytime, Anywhere.</div>
    </div>
  </div>
  <div class="topbar-title" style="flex:1"></div>
  <div class="topbar-actions">
    <button class="btn-icon" onclick="openNotifModal()" aria-label="Notifications" style="position:relative;background:rgba(255,255,255,0.08);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.12);font-size:0.8rem;cursor:pointer;padding:6px 10px;border-radius:10px;color:var(--text,#f1f5f9);transition:background .2s;">
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

    <!-- ===== HERO ===== -->
    <div class="about-hero fade-in">
      <div class="about-banner">
        
        <div class="about-banner-bg">
          <img src="../assets/img/Excelsior_Seal_Logo_cover.jpg" 
              alt="EPA Logo"
              onerror="this.style.display='none';this.parentElement.classList.add('no-image')">
        </div>

        <div class="about-banner-pattern"></div>
        <div class="about-banner-shape1"></div>
        <div class="about-banner-shape2"></div>
        <div class="about-banner-shape3"></div>

      </div>
      <div class="about-hero-content">
        <div class="about-logo-wrap">
          <div class="about-logo">
            <img src="../assets/img/Excelsior_Seal_Logo.png" alt="EPA Logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div class="about-logo-fallback" style="display:none">EPA</div>
          </div>
        </div>
        <div class="about-hero-info">
          <div class="about-hero-name">Excelsior Polytechnic Academy</div>
          <div class="about-hero-tagline">Excelsior Polytechnic Academy, Inc. is a private educational institution offering Senior High School, Preschool, Kindergarten, and Grade 1, with campuses in Cabitan, Mandaon, Masbate and in Crossing, Uson, Masbate. Visit us!</div>
          <div class="about-hero-tagline">« in avanti, in alto, sempre »</div>
          <div class="about-hero-badges">
            <span class="badge badge-blue">📍 Cabitan, Mandaon</span>
            <span class="badge badge-green">🎓 DepEd Recognized</span>
            <span class="badge badge-amber">💻 Blended Learning</span>
          </div>
        </div>
      </div>
    </div>

    <div class="vm-grid fade-in">
      <div class="vm-card">
        <div class="vm-card-glow" style="background:var(--school-blue)"></div>
        <div class="vm-card-icon" style="background:rgba(30,60,114,.15);color:var(--school-blue-light)">🔭</div>
        <div class="vm-card-label" style="color:var(--school-blue-light)">Our Vision</div>
        <div class="vm-card-text">Excelsior Polytechnic Academy is a leading educational institution that produces innovative, competent and resilient graduates for social development among Masbateños by <strong style="color:var(--text)">2030</strong>.</div>
      </div>
      <div class="vm-card">
        <div class="vm-card-glow" style="background:var(--school-orange)"></div>
        <div class="vm-card-icon" style="background:rgba(247,151,30,.12);color:var(--school-orange)">🎯</div>
        <div class="vm-card-label" style="color:var(--school-orange)">Our Mission</div>
        <div class="vm-card-text">Excelsior Polytechnic Academy commits in providing dynamic and relevant educational experiences in a <strong style="color:var(--text)">learner-friendly, gender-sensitive, safe and motivating environment</strong> for optimum development of life-long learners through a competency-based blended learning approach.</div>
      </div>
    </div>
    <div class="core-values-section fade-in">
      <div class="core-values-header">
        <div><div class="section-title" style="margin-bottom:2px">Core Values</div><div class="core-values-sub">The foundation of everything we do at Excelsior Polytechnic Academy</div></div>
        <div class="core-values-badge">e-CORE</div>
      </div>
      <div class="core-values-grid">
        <div class="core-value-card">
          <div class="core-value-card-glow" style="background:var(--school-blue)"></div>
          <div class="core-value-letter" style="background:linear-gradient(135deg,var(--school-blue),var(--school-blue-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">E</div>
          <div class="core-value-word">Excellence</div>
          <div class="core-value-desc">Striving for the highest standards in every endeavor — academically, personally, and as a community.</div>
        </div>
        <div class="core-value-card">
          <div class="core-value-card-glow" style="background:var(--school-blue-light)"></div>
          <div class="core-value-letter" style="background:linear-gradient(135deg,var(--school-blue-light),#4a7fd4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">C</div>
          <div class="core-value-word">Competence</div>
          <div class="core-value-desc">Equipping learners with the knowledge, skills, and confidence to thrive in a rapidly evolving world.</div>
        </div>
        <div class="core-value-card">
          <div class="core-value-card-glow" style="background:var(--school-orange)"></div>
          <div class="core-value-letter" style="background:linear-gradient(135deg,var(--school-orange),var(--school-orange-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">R</div>
          <div class="core-value-word">Resilience</div>
          <div class="core-value-desc">Building the strength and determination to overcome challenges and emerge stronger every time.</div>
        </div>
      </div>
    </div>
    <div class="about-footer-card fade-in">
      <div class="about-footer-card-bg"></div>
      <div class="about-footer-card-content">
        <div class="about-footer-logo">Excelsior Polytechnic Academy</div>
        <div class="about-footer-tagline">"Learn Better. Anytime, Anywhere."</div>
        <div class="about-footer-text">Dedicated to shaping the future of Masbateño youth through innovative education, blended learning technology, and unwavering commitment to the e-CORE values of Excellence, Competence, and Resilience.
        </div>
      </div>
    </div>

    <div style="text-align:center;padding:20px 0 8px;font-size:12px;color:var(--text3)">
      ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © JBH: 2025–2026
    </div>
  </div>
</main>
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
<div class="toast-container" id="toast-container"></div>
<div class="modal-overlay" id="logoutModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;">
  <div class="modal" style="max-width:400px;background:var(--bg2,#1e293b);border-radius:16px;width:90%;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.5);">
    <div class="modal-header" style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid rgba(255,255,255,0.08);">
      <div class="modal-title" style="font-size:16px;font-weight:700;color:var(--text,#f1f5f9);">Confirm Logout</div>
      <button class="modal-close" onclick="closeLogoutModal()" style="background:none;border:none;color:var(--text3,#64748b);font-size:18px;cursor:pointer;line-height:1;padding:4px;">✕</button>
    </div>
    <div class="modal-body" style="text-align:center;padding:28px 20px">
      <div style="font-size:48px;margin-bottom:12px">👋</div>
      <div style="font-size:15px;color:var(--text);font-weight:500;margin-bottom:6px">Are you sure you want to log out?</div>
      <div style="font-size:13px;color:var(--text3)">Any unsaved changes will be lost.</div>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:center;gap:10px;padding:0 20px 24px;">
      <button onclick="closeLogoutModal()" style="padding:9px 20px;border-radius:8px;border:1.5px solid rgba(255,255,255,0.15);background:transparent;color:var(--text,#f1f5f9);font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
      <button onclick="confirmLogout()" style="padding:9px 20px;border-radius:8px;border:none;background:#ef4444;color:white;font-size:14px;font-weight:600;cursor:pointer;">Yes, Log Out</button>
    </div>
  </div>
</div>
<script>
function openLogoutModal(){
  var m = document.getElementById('logoutModal');
  m.style.display = 'flex';
  document.body.style.overflow = 'hidden';
  document.querySelector('.layout').style.filter = 'blur(4px)';
  document.querySelector('.layout').style.transition = 'filter 0.2s ease';
}
function closeLogoutModal(){
  var m = document.getElementById('logoutModal');
  m.style.display = 'none';
  document.body.style.overflow = '';
  document.querySelector('.layout').style.filter = '';
}
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogoutModal();});
function confirmLogout(){window.location.href='../logout.php';}

function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebar-overlay').classList.toggle('open')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebar-overlay').classList.remove('open')}
function toggleTheme(){
  document.body.classList.toggle('light');
  var isLight = document.body.classList.contains('light');
  localStorage.setItem('excelearn-theme', isLight ? 'light' : 'dark');
}
(function(){
  var saved = localStorage.getItem('excelearn-theme');
  if(saved === 'light') document.body.classList.add('light');
})();
(function(){var saved=localStorage.getItem('excelearn-theme');if(saved==='light'){document.body.classList.add('light');}})();
function showToast(msg,type){type=type||'success';var icons={success:'✅',error:'❌',info:'ℹ️'};var container=document.getElementById('toast-container');var t=document.createElement('div');t.className='toast '+type;t.innerHTML='<span class="toast-icon">'+(icons[type]||'✅')+'</span><span>'+msg+'</span>';container.appendChild(t);setTimeout(function(){t.style.opacity='0';t.style.transform='translateX(60px)';t.style.transition='all .3s ease';setTimeout(function(){t.remove()},300)},3000)}
(function(){var observer=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add('visible');observer.unobserve(entry.target)}})},{threshold:0.06,rootMargin:'0px 0px -30px 0px'});document.querySelectorAll('.fade-in').forEach(function(el){observer.observe(el)})})();

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