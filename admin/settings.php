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
$admin_id = $_SESSION['user_id'];

// ── Admin user info ────────────────────────────────────────────────────────
$stmtU = $conn->prepare("SELECT profile_pic, firstname, lastname FROM tblusers WHERE id = ?");
$stmtU->bind_param("i", $admin_id);
$stmtU->execute();
$resU = $stmtU->get_result();
$admin_pic = null;
$admin_name = 'Admin';
$initials = 'A';
if ($resU && $resU->num_rows > 0) {
    $u = $resU->fetch_assoc();
    $admin_name = trim($u['firstname'] . ' ' . $u['lastname']);
    $initials = strtoupper(substr($u['firstname'], 0, 1) . substr($u['lastname'], 0, 1));
    if (!empty($u['profile_pic'])) {
        $admin_pic = '../uploads/profile_pic/admin/' . $u['profile_pic'];
    }
}
$stmtU->close();

// ── Nav badge counts ───────────────────────────────────────────────────────
$stats_teachers = (int)$conn->query("SELECT COUNT(*) FROM tblusers WHERE role = 'Teacher'")->fetch_row()[0];
$stats_students = (int)$conn->query("SELECT COUNT(*) FROM tblstudents")->fetch_row()[0];
$stats_strands  = (int)$conn->query("SELECT COUNT(*) FROM tblstrands")->fetch_row()[0];
$stats_sections = (int)$conn->query("SELECT COUNT(*) FROM tblsections")->fetch_row()[0];
$stats_subjects = (int)$conn->query("SELECT COUNT(*) FROM tblsubjects")->fetch_row()[0];

// ── Load or create admin settings ─────────────────────────────────────────
$settings = [];
$stmtS = $conn->prepare("SELECT * FROM tblteacher_settings WHERE teacher_id = ? LIMIT 1");
$stmtS->bind_param("i", $admin_id);
$stmtS->execute();
$resS = $stmtS->get_result()->fetch_assoc();
$stmtS->close();

if ($resS) {
    $settings = $resS;
} else {
    $conn->query("INSERT INTO tblteacher_settings (teacher_id) VALUES ($admin_id)");
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

// ── Handle AJAX auto-save ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_save'])) {
    $lang  = $_POST['language']    ?? 'en';
    $tz    = $_POST['timezone']    ?? 'PHT';
    $theme = $_POST['theme']       ?? 'dark';
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
    $stmtU->bind_param("isssiiiiii", $admin_id, $lang, $tz, $theme, $ea, $ed, $sa, $sr, $pb, $ps);
    $stmtU->execute();
    $stmtU->close();
    echo json_encode(['status' => 'ok']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn LMS — Admin Settings</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin/index.css">
<link rel="stylesheet" href="../assets/css/admin/settings.css">
<style>
/* ===== MOBILE RESPONSIVE OVERRIDES ===== */
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
  .page-title {
    font-size: 1.1rem;
  }
  .page-sub {
    font-size: 0.72rem;
  }
  .section-title {
    font-size: 0.75rem;
    margin-bottom: 6px;
  }
  .card {
    padding: 12px 14px;
    border-radius: 10px;
  }
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
  .setting-select {
    font-size: 0.78rem;
    padding: 6px 10px;
    width: 100%;
  }
  .setting-control {
    width: 100%;
  }
  .radio-pill {
    font-size: 0.78rem;
    padding: 5px 12px;
  }
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
  .toggle {
    transform: scale(0.88);
    transform-origin: right center;
  }
  .info-box {
    font-size: 0.70rem;
    padding: 8px 10px;
    gap: 6px;
  }
  .section-gap {
    margin-bottom: 16px;
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

<!-- SIDEBAR (Admin) -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo" style="display:flex;align-items:center;gap:10px;flex-wrap:nowrap;overflow:hidden;">
    <img src="../assets/img/Excelsior_Seal_Logo.png" alt="ExceLearn Logo"
         style="height:38px;width:auto;object-fit:contain;flex-shrink:0;">
    <div style="min-width:0">
      <div class="logo-name">Exce<span style="color:#22c55e">Learn</span></div>
      <div class="logo-sub">Learn Better. Anytime, Anywhere.</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a class="nav-item" href="index.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>

    <div class="nav-section-label" style="margin-top:14px">User Management</div>
    <a class="nav-item" href="admin_teachers.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Teachers
      <span class="nav-badge"><?php echo $stats_teachers; ?></span>
    </a>
    <a class="nav-item" href="admin_students.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Students
      <span class="nav-badge"><?php echo $stats_students; ?></span>
    </a>

    <div class="nav-section-label" style="margin-top:14px">Academic</div>
    <a class="nav-item" href="admin_sections.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      Grade & Sections
      <span class="nav-badge"><?php echo $stats_sections; ?></span>
    </a>
    <a class="nav-item" href="admin_strands.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Strands
      <span class="nav-badge"><?php echo $stats_strands; ?></span>
    </a>
    <a class="nav-item" href="admin_subjects.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5V4.5A1.5 1.5 0 0 1 5.5 3h13A1.5 1.5 0 0 1 20 4.5v15a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 19.5z"/>
        <path d="M8 7h8M8 11h8M8 15h5"/>
      </svg>
      Subjects
      <span class="nav-badge"><?php echo $stats_subjects; ?></span>
    </a>

    <div class="nav-section-label" style="margin-top:14px">Reports and Analytics</div>
    <a class="nav-item" href="admin_reports.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/>
        <line x1="10" y1="9" x2="8" y2="9"/>
      </svg>
      Reports
    </a>
    <a class="nav-item" href="admin_analytics.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="3" x2="3" y2="21"/>
        <line x1="3" y1="21" x2="21" y2="21"/>
        <polyline points="7 16 11 12 15 14 19 8"/>
      </svg>
      Analytics
    </a>

    <div class="nav-section-label" style="margin-top:14px">Account</div>
    <a class="nav-item" href="admin_profile.php">
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
    <a class="nav-item" href="#" onclick="openModal('logout-modal');return false;">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <?php if (!empty($admin_pic)): ?>
        <div class="user-avatar" style="background-image:url('<?php echo $admin_pic; ?>'); background-size:cover;"></div>
      <?php else: ?>
        <div class="user-avatar" style="background:linear-gradient(135deg,var(--rose),var(--amber))"><?php echo $initials; ?></div>
      <?php endif; ?>
      <div class="user-info">
        <p><?php echo htmlspecialchars($admin_name); ?></p>
        <span>Administrator</span>
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
                <option value="en"  <?= ($settings['language'] ?? 'en') === 'en'  ? 'selected' : '' ?>>English</option>
                <option value="fil" <?= ($settings['language'] ?? '') === 'fil' ? 'selected' : '' ?>>Filipino</option>
                <option value="tlh" <?= ($settings['language'] ?? '') === 'tlh' ? 'selected' : '' ?>>Tagalog</option>
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
                <option value="PHT"   <?= ($settings['timezone'] ?? 'PHT') === 'PHT'   ? 'selected' : '' ?>>Asia/Manila (PHT, UTC+8)</option>
                <option value="GMT+8" <?= ($settings['timezone'] ?? '') === 'GMT+8' ? 'selected' : '' ?>>GMT+8</option>
                <option value="UTC"   <?= ($settings['timezone'] ?? '') === 'UTC'   ? 'selected' : '' ?>>UTC</option>
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
              <input type="hidden" name="theme" id="themeInput" value="<?= htmlspecialchars($settings['theme'] ?? 'dark') ?>">
              <div class="radio-group" id="themeGroup">
                <button type="button" class="radio-pill <?= ($settings['theme'] ?? 'dark') === 'dark'  ? 'active' : '' ?>" id="themeDarkPill"  onclick="setThemeRadio('dark')">🌙 Dark</button>
                <button type="button" class="radio-pill <?= ($settings['theme'] ?? '') === 'light' ? 'active' : '' ?>" id="themeLightPill" onclick="setThemeRadio('light')">☀️ Light</button>
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
                <label class="toggle"><input type="checkbox" name="email_alerts" <?= ($settings['email_alerts'] ?? 1) ? 'checked' : '' ?>><span class="toggle-track"></span><span class="toggle-thumb"></span></label>
              </div>
              <div class="notif-cell">
                <div class="notif-cell-info">
                  <div class="notif-cell-label">Weekly Digest</div>
                  <div class="notif-cell-desc">Summary email every Monday</div>
                </div>
                <label class="toggle"><input type="checkbox" name="email_digest" <?= ($settings['email_digest'] ?? 0) ? 'checked' : '' ?>><span class="toggle-track"></span><span class="toggle-thumb"></span></label>
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
                <label class="toggle"><input type="checkbox" name="sms_alerts" <?= ($settings['sms_alerts'] ?? 0) ? 'checked' : '' ?>><span class="toggle-track"></span><span class="toggle-thumb"></span></label>
              </div>
              <div class="notif-cell">
                <div class="notif-cell-info">
                  <div class="notif-cell-label">System Reminder</div>
                  <div class="notif-cell-desc">SMS for important system events</div>
                </div>
                <label class="toggle"><input type="checkbox" name="sms_reminder" <?= ($settings['sms_reminder'] ?? 1) ? 'checked' : '' ?>><span class="toggle-track"></span><span class="toggle-thumb"></span></label>
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
                <label class="toggle"><input type="checkbox" name="push_browser" <?= ($settings['push_browser'] ?? 1) ? 'checked' : '' ?>><span class="toggle-track"></span><span class="toggle-thumb"></span></label>
              </div>
              <div class="notif-cell">
                <div class="notif-cell-info">
                  <div class="notif-cell-label">System Alerts</div>
                  <div class="notif-cell-desc">Maintenance and update alerts</div>
                </div>
                <label class="toggle"><input type="checkbox" name="push_system" <?= ($settings['push_system'] ?? 1) ? 'checked' : '' ?>><span class="toggle-track"></span><span class="toggle-thumb"></span></label>
              </div>
            </div>
          </div>

          <div class="info-box" style="margin-top:4px">
            <span class="info-box-icon">📧</span>
            <span>Notifications are sent to your registered email and phone number. Update them in your <a href="admin_profile.php" style="color:var(--accent);text-decoration:underline">Profile</a>.</span>
          </div>

        </div>
      </div>
    </div>

  </div>
</main>
</div>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toast-container"></div>

<!-- LOGOUT MODAL -->
<div class="modal-overlay" id="logout-modal">
  <div class="modal" style="max-width:400px">
    <div class="confirm-body">
      <div class="confirm-icon">🔒</div>
      <div class="confirm-text">Logout from ExceLearn?</div>
      <div class="confirm-sub">You will be redirected to the login page.</div>
      <div class="confirm-actions">
        <button class="btn btn-ghost" onclick="closeModal('logout-modal')">Cancel</button>
        <button class="btn btn-danger" onclick="window.location.href='../logout.php'">Logout</button>
      </div>
    </div>
  </div>
</div>

<script>
/* ========== MODAL ========== */
function openModal(id) {
  var el = document.getElementById(id);
  if (el) { el.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  var el = document.getElementById(id);
  if (el) { el.style.display = 'none'; document.body.style.overflow = ''; }
}

/* ========== SIDEBAR ========== */
var sidebar = document.getElementById('sidebar');
var overlay = document.getElementById('sidebar-overlay');
var menuBtn = document.getElementById('menuBtn');

function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow = ''; }

menuBtn.addEventListener('click', function() {
  sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
});
overlay.addEventListener('click', closeSidebar);
document.querySelectorAll('.nav-item').forEach(function(item) {
  item.addEventListener('click', function() { if (window.innerWidth <= 768) closeSidebar(); });
});
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeSidebar(); });

/* ========== THEME TOGGLE (sidebar button) ========== */
var themeToggle = document.getElementById('themeToggle');
var themeIcon   = document.getElementById('theme-icon');
var themeText   = document.getElementById('theme-text');

function applyTheme(isLight) {
  if (isLight) {
    document.body.classList.add('light');
    if (themeIcon) themeIcon.textContent = '☀️';
    if (themeText) themeText.textContent = 'Light Mode';
  } else {
    document.body.classList.remove('light');
    if (themeIcon) themeIcon.textContent = '🌙';
    if (themeText) themeText.textContent = 'Dark Mode';
  }
}

(function () {
  var saved = localStorage.getItem('excelearn-theme');
  if (saved === 'light') applyTheme(true);
})();

if (themeToggle) {
  themeToggle.addEventListener('click', function() {
    var isLight = document.body.classList.contains('light');
    applyTheme(!isLight);
    localStorage.setItem('excelearn-theme', !isLight ? 'light' : 'dark');
  });
}

/* ========== AUTO-SAVE ========== */
function getSettings() {
  return {
    ajax_save:    '1',
    language:     document.getElementById('settingLang').value,
    timezone:     document.getElementById('settingTimezone').value,
    theme:        document.getElementById('themeInput').value,
    email_alerts: document.querySelector('[name="email_alerts"]').checked ? 1 : 0,
    email_digest: document.querySelector('[name="email_digest"]').checked ? 1 : 0,
    sms_alerts:   document.querySelector('[name="sms_alerts"]').checked   ? 1 : 0,
    sms_reminder: document.querySelector('[name="sms_reminder"]').checked ? 1 : 0,
    push_browser: document.querySelector('[name="push_browser"]').checked ? 1 : 0,
    push_system:  document.querySelector('[name="push_system"]').checked  ? 1 : 0,
  };
}

function autoSave() {
  var data = getSettings();
  var body = Object.keys(data).map(function(k) {
    return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
  }).join('&');

  fetch('settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body
  })
  .then(function(r) { return r.json(); })
  .then(function(res) {
    if (res.status === 'ok') showToast('Settings saved!', 'success');
  })
  .catch(function() { showToast('Save failed.', 'error'); });
}

/* ========== RADIO PILLS ========== */
function setRadio(el, groupId) {
  document.getElementById(groupId).querySelectorAll('.radio-pill').forEach(function(p) {
    p.classList.remove('active');
  });
  el.classList.add('active');
}

/* ========== THEME RADIO (settings card) ========== */
function setThemeRadio(mode) {
  var pilId = 'theme' + mode.charAt(0).toUpperCase() + mode.slice(1) + 'Pill';
  setRadio(document.getElementById(pilId), 'themeGroup');
  document.getElementById('themeInput').value = mode;
  applyTheme(mode === 'light');
  localStorage.setItem('excelearn-theme', mode);
  autoSave();
}

// Sync radio pills to DB theme on load
(function () {
  var dbTheme = '<?= htmlspecialchars($settings['theme'] ?? 'dark') ?>';
  localStorage.setItem('excelearn-theme', dbTheme);
  var pillMap = { dark: 'themeDarkPill', light: 'themeLightPill' };
  if (pillMap[dbTheme]) setRadio(document.getElementById(pillMap[dbTheme]), 'themeGroup');
  applyTheme(dbTheme === 'light');
})();

/* ========== LISTEN TO ALL CONTROLS ========== */
document.getElementById('settingLang').addEventListener('change', autoSave);
document.getElementById('settingTimezone').addEventListener('change', autoSave);
document.querySelectorAll('.toggle input[type="checkbox"]').forEach(function(cb) {
  cb.addEventListener('change', autoSave);
});

/* ========== TOAST ========== */
function showToast(msg, type) {
  type = type || 'success';
  var icons = { success: '✅', error: '❌', info: 'ℹ️' };
  var container = document.getElementById('toast-container');
  var t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = '<span class="toast-icon">' + (icons[type] || '✅') + '</span><span>' + msg + '</span>';
  container.appendChild(t);
  setTimeout(function() {
    t.style.opacity = '0';
    t.style.transform = 'translateX(60px)';
    t.style.transition = 'all .3s ease';
    setTimeout(function() { t.remove(); }, 300);
  }, 3000);
}

/* ========== RESIZE ========== */
var resizeTimer;
window.addEventListener('resize', function() {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(function() {
    if (window.innerWidth > 768) closeSidebar();
  }, 150);
});
</script>
</body>
</html>