<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
$teacher_id = $_SESSION['user_id'];

$sql_user = "SELECT profile_pic, email, firstname, middlename, lastname FROM tblusers WHERE id = ? AND role = 'teacher' LIMIT 1";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$teacher_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$first  = $teacher_row['firstname']  ?? '';
$middle = $teacher_row['middlename'] ?? '';
$last   = $teacher_row['lastname']   ?? '';
$teacher_name = trim("$first $middle $last") ?: ($teacher_row['email'] ?? 'Teacher');
$teacher_dept = $_SESSION['teacher_dept'] ?? 'Faculty';
$teacher_pic  = !empty($teacher_row['profile_pic'])
    ? '../uploads/profile_pic/teachers/' . $teacher_row['profile_pic']
    : null;
$words = array_filter(explode(' ', $teacher_name));
$teacher_initials = implode('', array_map(fn($w) => strtoupper($w[0]), $words));

$badge_subjects  = (int)$conn->query("SELECT COUNT(*) FROM tbl_teacher_subjects WHERE teacher_id = $teacher_id")->fetch_row()[0];
$badge_lessons   = (int)$conn->query("SELECT COUNT(*) FROM tbl_teacher_lessons l JOIN tbl_teacher_subjects s ON l.teacher_subject_id = s.teacher_subject_id WHERE s.teacher_id = $teacher_id")->fetch_row()[0];
$badge_quizzes   = (int)$conn->query("SELECT COUNT(*) FROM tblquizzes q JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id JOIN tbl_teacher_subjects s ON l.teacher_subject_id = s.teacher_subject_id WHERE s.teacher_id = $teacher_id")->fetch_row()[0];
$badge_students  = (int)$conn->query("SELECT COUNT(DISTINCT student_id) FROM tbl_teachers_students WHERE teacher_subject_id IN (SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = $teacher_id)")->fetch_row()[0];
$badge_virtualclass = (int)$conn->query("SELECT COUNT(*) FROM tblvirtualclasses WHERE teacher_id = $teacher_id AND status IN ('upcoming','live')")->fetch_row()[0];
$badge_gradebook = (int)$conn->query("SELECT COUNT(DISTINCT r.student_id) FROM tblquiz_results r JOIN tblquizzes q ON q.quiz_id = r.quizzes_id JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id JOIN tbl_teacher_subjects s ON s.teacher_subject_id = l.teacher_subject_id WHERE s.teacher_id = $teacher_id")->fetch_row()[0];

// Load or create teacher settings
$settings = [];
$stmtS = $conn->prepare("SELECT * FROM tblteacher_settings WHERE teacher_id = ? LIMIT 1");
$stmtS->bind_param("i", $teacher_id);
$stmtS->execute();
$resS = $stmtS->get_result()->fetch_assoc();
$stmtS->close();

if ($resS) {
    $settings = $resS;
} else {
    // Insert defaults for new teacher
    $conn->query("INSERT INTO tblteacher_settings (teacher_id) VALUES ($teacher_id)");
    $settings = [
        'language'     => 'en',
        'timezone'     => 'PHT',
        'theme'        => 'dark',
        'email_alerts' => 1,
        'email_digest' => 0,
        'sms_alerts'   => 0,
        'sms_reminder' => 1,
        'push_browser' => 1,
        'push_system'  => 1,
    ];
}

// Handle AJAX auto-save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_save'])) {
    $lang  = $_POST['language']     ?? 'en';
    $tz    = $_POST['timezone']     ?? 'PHT';
    $theme = $_POST['theme']        ?? 'dark';
    $ea    = (int)($_POST['email_alerts']  ?? 0);
    $ed    = (int)($_POST['email_digest']  ?? 0);
    $sa    = (int)($_POST['sms_alerts']    ?? 0);
    $sr    = (int)($_POST['sms_reminder']  ?? 0);
    $pb    = (int)($_POST['push_browser']  ?? 0);
    $ps    = (int)($_POST['push_system']   ?? 0);

    $stmtU = $conn->prepare("
        INSERT INTO tblteacher_settings 
            (teacher_id, language, timezone, theme, email_alerts, email_digest, sms_alerts, sms_reminder, push_browser, push_system)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            language=VALUES(language), timezone=VALUES(timezone), theme=VALUES(theme),
            email_alerts=VALUES(email_alerts), email_digest=VALUES(email_digest),
            sms_alerts=VALUES(sms_alerts), sms_reminder=VALUES(sms_reminder),
            push_browser=VALUES(push_browser), push_system=VALUES(push_system),
            updated_at=NOW()
    ");
    $stmtU->bind_param("isssiiiiii", $teacher_id, $lang, $tz, $theme, $ea, $ed, $sa, $sr, $pb, $ps);
    $stmtU->execute();
    $stmtU->close();
    echo json_encode(['status' => 'ok']);
    exit();
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
<link rel="stylesheet" href="../assets/css/teacher/settings.css">
<style>
    /* ===== MOBILE RESPONSIVE OVERRIDES ===== */
@media (max-width: 768px) {

  /* Page header */
  .page-title {
    font-size: 1.1rem;
  }
  .page-sub {
    font-size: 0.72rem;
  }

  /* Section title */
  .section-title {
    font-size: 0.75rem;
    margin-bottom: 6px;
  }

  /* Card padding */
  .card {
    padding: 12px 14px;
    border-radius: 10px;
  }

  /* Setting row */
  .setting-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 0;
  }

  .setting-label {
    font-size: 0.82rem;
  }
  .setting-desc {
    font-size: 0.70rem;
  }

  /* Select dropdowns */
  .setting-select {
    font-size: 0.78rem;
    padding: 6px 10px;
    width: 100%;
  }

  /* Setting control full width */
  .setting-control {
    width: 100%;
  }

  /* Radio pills */
  .radio-pill {
    font-size: 0.78rem;
    padding: 5px 12px;
  }

  /* Notification grid */
  .notif-grid {
    grid-template-columns: 1fr;
    gap: 0;
  }

  .notif-cell {
    padding: 10px 0;
  }

  .notif-cell-label {
    font-size: 0.80rem;
  }
  .notif-cell-desc {
    font-size: 0.68rem;
  }

  /* Toggle size */
  .toggle {
    transform: scale(0.88);
    transform-origin: right center;
  }

  /* Info box */
  .info-box {
    font-size: 0.70rem;
    padding: 8px 10px;
    gap: 6px;
  }

  /* Section gaps */
  .section-gap {
    margin-bottom: 16px;
  }

  /* Notification section headers */
  .notif-grid + div,
  [style*="margin-bottom:20px"] > div:first-child {
    margin-bottom: 8px !important;
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
<body class="<?= ($settings['theme'] ?? 'dark') === 'light' ? 'light' : '' ?>">
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
    <a class="nav-item active" href="settings.php">
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
    <div class="page-header fade-in">
      <div>
        <div class="page-title">Settings</div>
        <div class="page-sub">Customize your account preferences and system defaults.</div>
      </div>
    </div>

    <div>
      <!-- GENERAL -->
      <div class="section-gap fade-in fade-in-d1">
        <div class="section-title">General</div>
        <div class="card">
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">Language</div>
              <div class="setting-desc">Choose your preferred interface language.</div>
            </div>
            <div class="setting-control">
              <select class="setting-select" name="language" id="settingLang">
                <option value="en" <?= ($settings['language']??'en')==='en'?'selected':'' ?>>English</option>
                <option value="fil" <?= ($settings['language']??'')==='fil'?'selected':'' ?>>Filipino</option>
                <option value="tlh" <?= ($settings['language']??'')==='tlh'?'selected':'' ?>>Tagalog</option>
              </select>
            </div>
          </div>
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">Timezone</div>
              <div class="setting-desc">Used for scheduling and due dates.</div>
            </div>
            <div class="setting-control">
              <select class="setting-select" name="timezone" id="settingTimezone">
                <option value="PHT" <?= ($settings['timezone']??'PHT')==='PHT'?'selected':'' ?>>Asia/Manila (PHT, UTC+8)</option>
                <option value="GMT+8" <?= ($settings['timezone']??'')==='GMT+8'?'selected':'' ?>>GMT+8</option>
                <option value="UTC" <?= ($settings['timezone']??'')==='UTC'?'selected':'' ?>>UTC</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    
      <!-- APPEARANCE -->
      <div class="section-gap fade-in fade-in-d2">
        <div class="section-title">Appearance</div>
        <div class="card">
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">Theme</div>
              <div class="setting-desc">Switch between dark and light mode.</div>
            </div>
            <div class="setting-control">
              <input type="hidden" name="theme" id="themeInput" value="<?= htmlspecialchars($settings['theme']??'dark') ?>">
              <div class="radio-group" id="themeGroup">
                <button type="button" class="radio-pill <?= ($settings['theme']??'dark')==='dark'?'active':'' ?>" id="themeDarkPill" onclick="setThemeRadio('dark')">🌙 Dark</button>
                <button type="button" class="radio-pill <?= ($settings['theme']??'')==='light'?'active':'' ?>" id="themeLightPill" onclick="setThemeRadio('light')">☀️ Light</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    
      <!-- NOTIFICATION PREFERENCES -->
      <div class="section-gap fade-in fade-in-d3">
        <div class="section-title">Notification Preferences</div>
        <div class="card">
    
          <!-- EMAIL -->
          <div style="margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
              <span style="font-size:16px;">📧</span>
              <div style="font-weight:700;font-size:13px;color:var(--text);">Email Notifications</div>
            </div>
            <div class="notif-grid">
              <div class="notif-cell">
                <div class="notif-cell-info">
                  <div class="notif-cell-label">Email Alerts</div>
                  <div class="notif-cell-desc">Receive updates via email</div>
                </div>
                <label class="toggle"><input type="checkbox" name="email_alerts" <?= ($settings['email_alerts']??1)?'checked':'' ?>><span class="toggle-track"></span><span class="toggle-thumb"></span></label>
              </div>
              <div class="notif-cell">
                <div class="notif-cell-info">
                  <div class="notif-cell-label">Weekly Digest</div>
                  <div class="notif-cell-desc">Summary email every Monday</div>
                </div>
                <label class="toggle"><input type="checkbox" name="email_digest" <?= ($settings['email_digest']??0)?'checked':'' ?>><span class="toggle-track"></span><span class="toggle-thumb"></span></label>
              </div>
            </div>
          </div>
    
          <!-- SMS -->
          <div style="margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
              <span style="font-size:16px;">💬</span>
              <div style="font-weight:700;font-size:13px;color:var(--text);">SMS Notifications</div>
            </div>
            <div class="notif-grid">
              <div class="notif-cell">
                <div class="notif-cell-info">
                  <div class="notif-cell-label">SMS Alerts</div>
                  <div class="notif-cell-desc">Receive text message notifications</div>
                </div>
                <label class="toggle"><input type="checkbox" name="sms_alerts" <?= ($settings['sms_alerts']??0)?'checked':'' ?>><span class="toggle-track"></span><span class="toggle-thumb"></span></label>
              </div>
              <div class="notif-cell">
                <div class="notif-cell-info">
                  <div class="notif-cell-label">Virtual Class Reminder</div>
                  <div class="notif-cell-desc">SMS 15 min before a session</div>
                </div>
                <label class="toggle"><input type="checkbox" name="sms_reminder" <?= ($settings['sms_reminder']??1)?'checked':'' ?>><span class="toggle-track"></span><span class="toggle-thumb"></span></label>
              </div>
            </div>
          </div>
    
          <!-- PUSH -->
          <div style="margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
              <span style="font-size:16px;">🔔</span>
              <div style="font-weight:700;font-size:13px;color:var(--text);">Push Notifications</div>
            </div>
            <div class="notif-grid">
              <div class="notif-cell">
                <div class="notif-cell-info">
                  <div class="notif-cell-label">Browser Push</div>
                  <div class="notif-cell-desc">In-app and browser notifications</div>
                </div>
                <label class="toggle"><input type="checkbox" name="push_browser" <?= ($settings['push_browser']??1)?'checked':'' ?>><span class="toggle-track"></span><span class="toggle-thumb"></span></label>
              </div>
              <div class="notif-cell">
                <div class="notif-cell-info">
                  <div class="notif-cell-label">System Alerts</div>
                  <div class="notif-cell-desc">Maintenance and update alerts</div>
                </div>
                <label class="toggle"><input type="checkbox" name="push_system" <?= ($settings['push_system']??1)?'checked':'' ?>><span class="toggle-track"></span><span class="toggle-thumb"></span></label>
              </div>
            </div>
          </div>
    
          <div class="info-box" style="margin-top:4px">
            <span class="info-box-icon">📧</span>
            <span>Notifications are sent to your registered email and phone number. Update them in your <a href="teacher_profile.php" style="color:var(--accent);text-decoration:underline">Profile</a>.</span>
          </div>
    
        </div>
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
<!-- TOAST CONTAINER -->
<div class="toast-container" id="toast-container"></div>
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

/* ========== SIDEBAR ========== */
var sidebar=document.getElementById('sidebar');
var overlay=document.getElementById('sidebar-overlay');
var menuBtn=document.getElementById('menuBtn');
function openSidebar(){sidebar.classList.add('open');overlay.classList.add('open');document.body.style.overflow='hidden'}
function closeSidebar(){sidebar.classList.remove('open');overlay.classList.remove('open');document.body.style.overflow=''}
menuBtn.addEventListener('click',function(){sidebar.classList.contains('open')?closeSidebar():openSidebar()});
overlay.addEventListener('click',closeSidebar);
document.querySelectorAll('.nav-item').forEach(function(item){item.addEventListener('click',function(){if(window.innerWidth<=768)closeSidebar()})});

/* ========== AUTO-SAVE ========== */
function getSettings() {
  return {
    ajax_save:     '1',
    language:      document.getElementById('settingLang').value,
    timezone:      document.getElementById('settingTimezone').value,
    theme:         document.getElementById('themeInput').value,
    email_alerts:  document.querySelector('[name="email_alerts"]').checked  ? 1 : 0,
    email_digest:  document.querySelector('[name="email_digest"]').checked  ? 1 : 0,
    sms_alerts:    document.querySelector('[name="sms_alerts"]').checked    ? 1 : 0,
    sms_reminder:  document.querySelector('[name="sms_reminder"]').checked  ? 1 : 0,
    push_browser:  document.querySelector('[name="push_browser"]').checked  ? 1 : 0,
    push_system:   document.querySelector('[name="push_system"]').checked   ? 1 : 0,
  };
}

function autoSave() {
  var data = getSettings();
  var body = Object.keys(data).map(function(k){ return encodeURIComponent(k)+'='+encodeURIComponent(data[k]); }).join('&');
  fetch('settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body
  })
  .then(function(r){ return r.json(); })
  .then(function(res){
    if(res.status === 'ok') showToast('Settings saved!', 'success');
  })
  .catch(function(){ showToast('Save failed.', 'error'); });
}

/* ========== RADIO PILLS ========== */
function setRadio(el,groupId){
  var group=document.getElementById(groupId);
  group.querySelectorAll('.radio-pill').forEach(function(p){p.classList.remove('active')});
  el.classList.add('active');
}

/* ========== THEME RADIO ========== */
var themeIcon={textContent:''};
var themeText={textContent:''};

/* ========== THEME RADIO ========== */
function setThemeRadio(mode){
  setRadio(document.getElementById('theme'+mode.charAt(0).toUpperCase()+mode.slice(1)+'Pill'),'themeGroup');
  document.getElementById('themeInput').value = mode;
  if(mode==='dark'){document.body.classList.remove('light');themeIcon.textContent='🌙';themeText.textContent='Dark Mode';}
  else if(mode==='light'){document.body.classList.add('light');themeIcon.textContent='☀️';themeText.textContent='Light Mode';}
  localStorage.setItem('excelearn-theme',mode);
  autoSave();
}

(function(){
  var dbTheme = '<?= htmlspecialchars($settings['theme'] ?? 'dark') ?>';
  localStorage.setItem('excelearn-theme', dbTheme);
  var pillMap={dark:'themeDarkPill',light:'themeLightPill'};
  if(pillMap[dbTheme]){setRadio(document.getElementById(pillMap[dbTheme]),'themeGroup');}
  if(dbTheme==='light'){document.body.classList.add('light');themeIcon.textContent='☀️';themeText.textContent='Light Mode';}
})();


/* ========== LISTEN TO ALL CONTROLS ========== */
document.getElementById('settingLang').addEventListener('change', autoSave);
document.getElementById('settingTimezone').addEventListener('change', autoSave);
document.querySelectorAll('.toggle input[type="checkbox"]').forEach(function(cb){
  cb.addEventListener('change', autoSave);
});

/* ========== TOAST ========== */
function showToast(msg,type){
  type=type||'success';
  var icons={success:'✅',error:'❌',info:'ℹ️'};
  var container=document.getElementById('toast-container');
  var t=document.createElement('div');
  t.className='toast '+type;
  t.innerHTML='<span class="toast-icon">'+(icons[type]||'✅')+'</span><span>'+msg+'</span>';
  container.appendChild(t);
  setTimeout(function(){t.style.opacity='0';t.style.transform='translateX(60px)';t.style.transition='all .3s ease';setTimeout(function(){t.remove()},300)},3000);
}

/* ========== KEYBOARD ========== */
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeSidebar();});

/* ========== RESIZE ========== */
var resizeTimer;
window.addEventListener('resize',function(){clearTimeout(resizeTimer);resizeTimer=setTimeout(function(){if(window.innerWidth>768)closeSidebar()},150)});

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