<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: ../index.php");
    exit();
}

$student_id = (int)$_SESSION['student_id'];

$student = $conn->query("SELECT * FROM tblstudents WHERE id = $student_id")->fetch_assoc();

if (!$student) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$section_id = (int)$student['section_id'];

$section_row = $conn->query("SELECT * FROM tblsections WHERE section_id = $section_id")->fetch_assoc();

$strand_id = 0;
if ($section_row) {
    if (isset($section_row['strand_id']))        $strand_id = (int)$section_row['strand_id'];
    elseif (isset($section_row['strandid']))      $strand_id = (int)$section_row['strandid'];
    elseif (isset($section_row['strand']))        $strand_id = (int)$section_row['strand'];
    elseif (isset($section_row['tblstrands_id'])) $strand_id = (int)$section_row['tblstrands_id'];
}

$strand_label = 'N/A';
if ($strand_id) {
    $strand = $conn->query("SELECT strand FROM tblstrands WHERE strand_id = $strand_id")->fetch_assoc();
    if ($strand) $strand_label = htmlspecialchars($strand['strand']);
} elseif ($section_row && isset($section_row['section_name'])) {
    $strand_label = htmlspecialchars($section_row['section_name']);
} elseif ($section_row && isset($section_row['section'])) {
    $strand_label = htmlspecialchars($section_row['section']);
}

$quiz_count = (int)$conn->query("
    SELECT COUNT(DISTINCT q.quiz_id)
    FROM tblquizzes q
    JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = l.teacher_subject_id
    JOIN tblsubjects subj ON subj.subject_id = ts.subject_id
    JOIN tbl_teachers_students tts ON tts.teacher_id = ts.teacher_id
    WHERE tts.student_id = $student_id AND q.status = 'published'
")->fetch_row()[0];

$lesson_count = (int)$conn->query("
    SELECT COUNT(DISTINCT l.lesson_id)
    FROM tbl_teacher_lessons l
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = l.teacher_subject_id
    JOIN tbl_teachers_students tts ON tts.teacher_id = ts.teacher_id
    WHERE tts.student_id = $student_id AND l.status = 'published'
")->fetch_row()[0];

$subject_count = (int)$conn->query("
    SELECT COUNT(DISTINCT ts.teacher_subject_id)
    FROM tbl_teacher_subjects ts
    JOIN tbl_teachers_students tts ON tts.teacher_id = ts.teacher_id
    WHERE tts.student_id = $student_id
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
<link rel="stylesheet" href="../assets/css/student/settings.css">
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
      <div style="font-size:0.7rem;color:#9ca3af;margin-top:2px;" data-i18n="Learn Better. Anytime, Anywhere.">
        Learn Better. Anytime, Anywhere.
      </div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a class="nav-item" href="index.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      <span data-i18n="Dashboard">Dashboard</span>
    </a>
    <div class="nav-section-label" data-i18n="Learning">Learning</div>
    <a class="nav-item" href="student_courses.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      <span data-i18n="My Subjects">My Subjects</span>
      <span class="nav-badge"><?= $subject_count ?></span>
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
      <span data-i18n="Virtual Class">Virtual Class</span>
    </a>
    <div class="nav-section-label" style="margin-top:16px" data-i18n="Progress">Progress</div>
    <a class="nav-item" href="student_grades.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <span data-i18n="Grades">Grades</span>
    </a>
    <div class="nav-section-label" style="margin-top:16px" data-i18n="Account">Account</div>
    <a class="nav-item" href="student_profile.php">
    <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    <span data-i18n="My Profile">My Profile</span>
    </a>
    <a class="nav-item active" href="settings.php">
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
    <span data-i18n="Settings">Settings</span>
    </a>
    <a class="nav-item" href="about.php">
    <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="16" x2="12" y2="12"/>
        <line x1="12" y1="8" x2="12.01" y2="8"/>
    </svg>
    <span data-i18n="About">About</span>
    </a>
    <div style="margin-top:8px;border-top:1px solid rgba(255,255,255,0.08);padding-top:8px"></div>
    <a class="nav-item" href="#" onclick="confirmLogout(event)" style="color:var(--red)">
    <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
    </svg>
    <span data-i18n="Logout">Logout</span>
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--cyan),var(--accent));overflow:hidden;padding:0">
        <?php
        $pic = $student['profile_pic'] ?? '';
        $found = false;
        if (!empty($pic)) {
            foreach (['../uploads/profile_pic/students/'.$pic, '../uploads/profile_pic/'.$pic, '../'.$pic] as $p) {
                if (file_exists($p)) {
                    echo '<img src="'.htmlspecialchars($p).'" style="width:100%;height:100%;object-fit:cover;border-radius:inherit" alt="">';
                    $found = true;
                    break;
                }
            }
        }
        if (!$found) echo strtoupper(substr($student['firstname'],0,1).substr($student['lastname'],0,1));
        ?>
      </div>
      <div class="user-info">
        <p><?= htmlspecialchars($student['firstname'].' '.$student['middlename']. ' '.$student['lastname']) ?></p>
        <?php
        $grade_row = $conn->query("
            SELECT g.grade_level
            FROM tblsections sec
            JOIN tblgrade_levels g ON g.grade_id = sec.grade_level_id
            WHERE sec.section_id = $section_id
        ")->fetch_assoc();
        if ($grade_row): ?>
          <span><?= htmlspecialchars($grade_row['grade_level']) ?> — <?= $strand_label ?></span>
        <?php endif; ?>
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
      <div class="section-title" data-i18n="Settings">Settings</div>
      <div class="section-sub" data-i18n="Manage your account preferences and security options.">Manage your account preferences and security options.</div>
    </div>

    <form id="settingsForm">
      <div class="card">
        <div class="card-header">
        <div class="card-title" data-i18n="General Preferences">General Preferences</div>
        </div>
        <div class="settings-grid">
          <div class="form-group">
            <label data-i18n="Language">Language</label>
            <select id="setting-language" onchange="applyLanguage(this.value); saveAll()">
              <option>English (Philippines)</option>
              <option>English (US)</option>
              <option>Filipino</option>
            </select>
          </div>
          <div class="form-group">
            <label data-i18n="Timezone">Timezone</label>
            <select id="setting-timezone" onchange="saveAll()">
              <option>(GMT+08:00) Philippine Time</option>
              <option>(GMT+00:00) UTC</option>
            </select>
          </div>
        </div>
        <div class="setting-row">
          <div class="setting-row-content">
            <div class="setting-label" data-i18n="Dark Mode">Dark Mode</div>
            <div class="setting-desc" data-i18n="Switch between light and dark interface theme.">Switch between light and dark interface theme.</div>
          </div>
          <label class="switch">
            <input type="checkbox" id="darkModeToggle" onchange="toggleTheme()">
            <span class="slider"></span>
          </label>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title" data-i18n="Notifications">Notifications</div>
        </div>
        <div class="setting-row">
          <div class="setting-row-content">
            <div class="setting-label" data-i18n="Push Notifications">Push Notifications</div>
            <div class="setting-desc" data-i18n="Receive daily summaries and course updates via push notification.">Receive daily summaries and course updates via push notification.</div>
          </div>
          <label class="switch">
            <input type="checkbox" id="setting-notif_push" onchange="saveToggle('notif_push', this.checked)">
            <span class="slider"></span>
          </label>
        </div>
        <div class="setting-row">
          <div class="setting-row-content">
            <div class="setting-label" data-i18n="SMS Alerts">SMS Alerts</div>
            <div class="setting-desc" data-i18n="Receive urgent alerts (e.g., class cancellations) via SMS.">Receive urgent alerts (hal., pagkansela ng klase) sa SMS.</div>
          </div>
          <label class="switch">
            <input type="checkbox" id="setting-notif_sms" onchange="saveToggle('notif_sms', this.checked)">
            <span class="slider"></span>
          </label>
        </div>

      </div>
    </form>
  </div>
</main>
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
/* ========== LOGOUT ========== */
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
function applyTheme(theme) {
  var isLight = theme === 'light';
  document.body.classList.toggle('light', isLight);
  var cb = document.getElementById('darkModeToggle');
  if (cb) cb.checked = !isLight;

  var lang = document.getElementById('setting-language').value;
  var isFil = lang === 'Filipino';
  var label = document.querySelector('[data-i18n="Dark Mode"]');
  if (label) {
    label.textContent = isLight
      ? (isFil ? 'Maliwanag na Mode' : 'Light Mode')
      : (isFil ? 'Madilim na Mode'  : 'Dark Mode');
  }
}

function toggleTheme() {
  var isLight = document.body.classList.contains('light');
  var newTheme = isLight ? 'dark' : 'light';
  applyTheme(newTheme);
  saveAll();
}

/* ========== COLLECT PAYLOAD ========== */
function getPayload() {
  return {
    theme:      document.body.classList.contains('light') ? 'light' : 'dark',
    language:   document.getElementById('setting-language').value,
    timezone:   document.getElementById('setting-timezone').value,
    notif_push: document.getElementById('setting-notif_push').checked ? 1 : 0,
    notif_sms:  document.getElementById('setting-notif_sms').checked  ? 1 : 0
  };
}

/* ========== SAVE ALL ========== */
function saveAll(btn) {
  if (btn) {
    btn.textContent = 'Saving...';
    btn.disabled = true;
    btn.style.opacity = '0.7';
  }

  fetch('settings_handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(getPayload())
  })
  .then(function(r){ return r.json(); })
  .then(function(res) {
    if (btn) {
      btn.textContent = 'Save Changes';
      btn.disabled = false;
      btn.style.opacity = '1';
    }
    showToast(res.success ? 'Settings saved!' : (res.message || 'Failed to save.'), res.success ? 'success' : 'error');
  })
  .catch(function() {
    if (btn) {
      btn.textContent = 'Save Changes';
      btn.disabled = false;
      btn.style.opacity = '1';
    }
    showToast('Network error. Try again.', 'error');
  });
}

/* ========== INSTANT TOGGLE SAVE ========== */
function saveToggle(field, checked) {
  var payload = getPayload();
  payload[field] = checked ? 1 : 0;

  fetch('settings_handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(function(r){ return r.json(); })
  .then(function(res){
    showToast(res.success ? 'Setting updated!' : (res.message || 'Failed.'), res.success ? 'success' : 'error');
  })
  .catch(function(){
    showToast('Network error. Try again.', 'error');
  });
}

/* ========== TOAST ========== */
function showToast(msg, type) {
  type = type || 'success';
  var icons = { success: '✅', error: '❌', info: 'ℹ️' };
  var container = document.getElementById('toast-container');
  var t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = '<span class="toast-icon">' + (icons[type] || '✅') + '</span><span>' + msg + '</span>';
  container.appendChild(t);
  setTimeout(function () {
    t.style.opacity = '0';
    t.style.transform = 'translateX(60px)';
    t.style.transition = 'all .3s ease';
    setTimeout(function () { t.remove(); }, 300);
  }, 3000);
}

/* ========== LOAD SETTINGS ========== */
function setSelect(id, value) {
  var el = document.getElementById(id);
  if (!el) return;
  for (var i = 0; i < el.options.length; i++) {
    if (el.options[i].value === value || el.options[i].text === value) {
      el.selectedIndex = i;
      break;
    }
  }
}

function loadSettings() {
  fetch('settings_handler.php')
    .then(function(r){ return r.text(); })
    .then(function(text) {
      var res;
      try { res = JSON.parse(text); }
      catch(e) {
        console.error('Non-JSON from handler:', text);
        showToast('Could not load settings.', 'error');
        return;
      }
      if (!res.success) {
        console.error('Handler error:', res.message);
        showToast('Error: ' + res.message, 'error');
        return;
      }
      var d = res.data;
      applyTheme(d.theme || 'dark');
      setSelect('setting-language', d.language);
      setSelect('setting-timezone', d.timezone);
      applyLanguage(d.language || 'English (Philippines)');
      document.getElementById('setting-notif_push').checked = d.notif_push == 1;
      document.getElementById('setting-notif_sms').checked  = d.notif_sms  == 1;
    })
    .catch(function(e){
      console.error('Fetch error:', e);
      showToast('Could not load settings.', 'error');
    });
}

/* ========== TRANSLATIONS ========== */
var translations = {
  'Filipino': {
    // Page text
    'Settings':                        'Mga Setting',
    'Manage your account preferences and security options.': 'Pamahalaan ang iyong mga kagustuhan at opsyon sa seguridad.',
    'General Preferences':             'Mga Pangkalahatang Kagustuhan',
    'Language':                        'Wika',
    'Timezone':                        'Oras ng Lugar',
    'Dark Mode':                       'Madilim na Mode',
    'Light Mode':                      'Maliwanag na Mode',
    'Switch between light and dark interface theme.': 'Pumili sa maliwanag o madilim na tema.',
    'Notifications':                   'Mga Abiso',
    'Push Notifications':              'Mga Push na Abiso',
    'Receive daily summaries and course updates via push notification.': 'Tumanggap ng pang-araw-araw na buod at mga update sa kurso.',
    'SMS Alerts':                      'Mga SMS na Alerto',
    'Receive urgent alerts (e.g., class cancellations) via SMS.': 'Tumanggap ng mga agarang alerto (hal., pagkansela ng klase) sa SMS.',

    // Sidebar nav section labels
    'Learning':   'Pag-aaral',
    'Progress':   'Progreso',
    'Account':    'Account',

    // Sidebar nav items
    'Learn Better. Anytime, Anywhere.': 'Matuto nang Mas Mabuti. Kahit Kailan, Kahit Saan.',
    'Dashboard':    'Dashboard',
    'My Subjects':  'Aking mga Paksa',
    'Lessons':      'Mga Aralin',
    'My Quizzes':   'Aking mga Pagsusulit',
    'Virtual Class':'Virtual na Klase',
    'Grades':       'Mga Grado',
    'My Profile':   'Aking Profile',
    'About':        'Tungkol',
    'Logout':       'Mag-logout',
  },
  'English (Philippines)': {},
  'English (US)': {}
};

function applyLanguage(lang) {
  var dict = translations[lang] || {};
  document.querySelectorAll('[data-i18n]').forEach(function(el) {
    var key = el.getAttribute('data-i18n');
    if (key === 'Dark Mode') return; // skip — handled by applyTheme()
    el.textContent = dict[key] || key;
  });
  // After language is applied, re-sync the theme label
  applyTheme(document.body.classList.contains('light') ? 'light' : 'dark');
}
/* ========== INIT ========== */
loadSettings();

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