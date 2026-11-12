<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
$admin_id = $_SESSION['user_id'];

$stmtAdmin = $conn->prepare("
    SELECT firstname, lastname, role, profile_pic 
    FROM tblusers 
    WHERE id = ?
    LIMIT 1
");
$stmtAdmin->bind_param("i", $admin_id);
$stmtAdmin->execute();
$admin = $stmtAdmin->get_result()->fetch_assoc();
 $msg = "";
 $msgType = "";

 $stmtU = $conn->prepare("SELECT profile_pic, firstname, lastname FROM tblusers WHERE id = ?");
 $stmtU->bind_param("i", $admin_id);
 $stmtU->execute();
 $resU = $stmtU->get_result();
if ($resU && $resU->num_rows > 0) {
    $u = $resU->fetch_assoc();
    $admin_name = trim($u['firstname'] . ' ' . $u['lastname']);
    $initials = strtoupper(substr($u['firstname'], 0, 1) . substr($u['lastname'], 0, 1));
    if (!empty($u['profile_pic'])) $admin_pic = '../uploads/profile_pic/admin/' . $u['profile_pic'];
}
 $stmtU->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $id = intval($_POST['user_id']);
        $action = $_POST['action'];
        
        if ($action == 'approve') {
            $stmt = $conn->prepare("UPDATE tblusers SET is_verified = 1, status = 'Active' WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $msg = "User approved successfully!";
                $msgType = "success";
            } else {
                $msg = "Error approving user.";
                $msgType = "error";
            }
        } elseif ($action == 'reject') {
            $stmt = $conn->prepare("UPDATE tblusers SET status = 'Rejected' WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $msg = "User rejected.";
                $msgType = "error";
            }
        }
    }

    if (isset($_POST['add_announcement'])) {
        $title    = $_POST['title'];
        $message  = $_POST['message'];
        $type     = $_POST['type'];
        $category = $_POST['category'] ?? 'both';
    
        $stmt = $conn->prepare("INSERT INTO tblannouncements (user_id, title, message, type, category) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $admin_id, $title, $message, $type, $category);
    
        if ($stmt->execute()) {
            $msg     = "Announcement posted successfully!";
            $msgType = "success";
    
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
            $msg     = "Failed to post announcement.";
            $msgType = "error";
        }
    }
  if (isset($_POST['delete_announcement'])) {
      $id = intval($_POST['id']);

      $stmt = $conn->prepare("DELETE FROM tblannouncements WHERE id = ? AND user_id = ?");
      $stmt->bind_param("ii", $id, $admin_id);

      if ($stmt->execute()) {
          $msg = "Announcement deleted successfully!";
          $msgType = "success";
      }
  }
    if (isset($_POST['update_announcement'])) {
        $id       = intval($_POST['id']);
        $title    = $_POST['title'];
        $message  = $_POST['message'];
        $type     = $_POST['type'];
        $category = $_POST['category'] ?? 'both';
    
        $stmt = $conn->prepare("
            UPDATE tblannouncements 
            SET title = ?, message = ?, type = ?, category = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ssssii", $title, $message, $type, $category, $id, $admin_id);
    
        if ($stmt->execute()) {
            $msg     = "Announcement updated successfully!";
            $msgType = "success";
        }
    }
}

$total_teachers = $conn->query("SELECT COUNT(*) as count FROM tblusers WHERE role = 'Teacher'")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM tblstudents")->fetch_assoc()['count'];

$stats_total_users = $total_teachers + $total_students;
 $stats_teachers = $conn->query("SELECT COUNT(*) as count FROM tblusers WHERE role = 'Teacher'")->fetch_assoc()['count'];

 $stats_students = $conn->query("SELECT COUNT(*) as count FROM tblstudents")->fetch_assoc()['count'];

 $stats_strands = $conn->query("SELECT COUNT(*) as count FROM tblstrands")->fetch_assoc()['count'];

 $stats_sections = $conn->query("SELECT COUNT(*) as count FROM tblsections")->fetch_assoc()['count'];

$recent_users_query = "
  SELECT id, firstname, lastname, role, created_at, is_verified, profile_pic
  FROM tblusers 
  WHERE LOWER(role) = 'teacher'

  UNION ALL

  SELECT id, firstname, lastname, 'Student' as role, created_at, 1 as is_verified, profile_pic
  FROM tblstudents

  ORDER BY created_at DESC 
  LIMIT 5
";
 $recent_users_result = $conn->query($recent_users_query);

function getRoleBadge($role) {
    $role = ucfirst(strtolower($role));
    switch ($role) {
        case 'Student': return ['badge' => 'badge-cyan', 'color' => 'var(--cyan)'];
        case 'Teacher': return ['badge' => 'badge-purple', 'color' => 'var(--purple)'];
        case 'Admin':   return ['badge' => 'badge-rose', 'color' => 'var(--rose)'];
        default:        return ['badge' => 'badge-gray', 'color' => 'var(--slate)'];
    }
}

function getGradient($role) {
    $role = ucfirst(strtolower($role));
    switch ($role) {
        case 'Student': return 'linear-gradient(135deg,var(--cyan),var(--accent))';
        case 'Teacher': return 'linear-gradient(135deg,var(--purple),#8b3fbf)';
        case 'Admin':   return 'linear-gradient(135deg,var(--rose),var(--amber))';
        default:        return 'linear-gradient(135deg,var(--slate),#64748b)';
    }
}

function getInitials($fname, $lname) {
    return strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1));
}

 $pending_query = "SELECT * FROM tblusers WHERE is_verified = 0 LIMIT 5";
 $pending_result = $conn->query($pending_query);


 $strands_query = "SELECT * FROM tblstrands";
 $strands_result = $conn->query($strands_query);

$total_records =
    $conn->query("SELECT COUNT(*) as count FROM tblusers")->fetch_assoc()['count'] +
    $conn->query("SELECT COUNT(*) as count FROM tblstudents")->fetch_assoc()['count'] +
    $conn->query("SELECT COUNT(*) as count FROM tblstrands")->fetch_assoc()['count'] +
    $conn->query("SELECT COUNT(*) as count FROM tblsections")->fetch_assoc()['count'];


$total_limit_gb = 100; 

$storage_query = $conn->query("SELECT SUM(file_size) as total_size FROM tbl_storage_files");
$total_used_bytes = $storage_query->fetch_assoc()['total_size'] ?? 0;

$total_used_gb = $total_used_bytes / (1024 * 1024 * 1024);
$total_used_gb = round($total_used_gb, 2);

$remaining_gb = $total_limit_gb - $total_used_gb;

function getStorageByType($conn, $type) {
    $stmt = $conn->prepare("SELECT SUM(file_size) as size FROM tbl_storage_files WHERE file_type = ?");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc()['size'] ?? 0;

    return round($res / (1024 * 1024 * 1024), 2);
}

$course_materials = getStorageByType($conn, 'course');
$user_uploads = getStorageByType($conn, 'video');
$pdfs = getStorageByType($conn, 'pdf');
$pptx = getStorageByType($conn, 'pptx');
$backups = getStorageByType($conn, 'backup');
$logs = getStorageByType($conn, 'log');

$announcements_result = $conn->query("
    SELECT a.*, u.firstname, u.lastname, u.role
    FROM tblannouncements a
    LEFT JOIN tblusers u ON u.id = a.user_id
    ORDER BY a.created_at DESC
");

// Load saved theme
$themeResult = $conn->prepare("SELECT theme FROM tblteacher_settings WHERE teacher_id = ? LIMIT 1");
$themeResult->bind_param("i", $admin_id);
$themeResult->execute();
$themeRow = $themeResult->get_result()->fetch_assoc();
$themeResult->close();
$savedTheme = $themeRow['theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn — Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin/index.css">
<style>
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
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr) !important;
  gap: 16px;
}

.stat-card {
  padding: 16px !important;
  min-width: 0 !important;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 6px !important;
  }

  .stat-card {
    padding: 10px 8px !important;
    min-width: 0 !important;
    overflow: hidden !important;
  }

  .stat-icon {
    font-size: 1.2rem !important;
    margin-bottom: 4px !important;
  }

  .stat-value {
    font-size: 1.1rem !important;
    line-height: 1.2 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
  }

  .stat-label {
    font-size: 0.62rem !important;
    line-height: 1.2 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
  }

  .stat-change {
    font-size: 0.58rem !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
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
<body class="<?= $savedTheme === 'light' ? 'light' : '' ?>">
<div class="layout">
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
    <a class="nav-item active" href="index.php">
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
      <?php
        $stats_subjects = $conn->query("SELECT COUNT(*) as count FROM tblsubjects")->fetch_assoc()['count'];
        ?>
        Subjects <span class="nav-badge"><?php echo $stats_subjects; ?></span>
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
    <a class="nav-item" href="settings.php">
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
      <?php if(!empty($admin_pic)): ?>
        <div class="user-avatar" style="background-image:url('<?php echo $admin_pic; ?>'); background-size:cover;"></div>
      <?php else: ?>
        <div class="user-avatar" style="background:linear-gradient(135deg,var(--rose),var(--amber))"><?php echo $initials; ?></div>
      <?php endif; ?>
      <div class="user-info"><p><?php echo htmlspecialchars($admin_name); ?></p><span>Administrator</span></div>
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
    </div>

  <div class="content">
    <div class="section-header">
      <div>
        <div class="section-title" id="greeting-text">Good morning, Admin! 🛡️</div>
        <div class="section-sub">System is running smoothly.</div>
      </div>
    </div>

    <div class="stats-grid" id="stats-grid">
      <div class="stat-card blue">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?php echo $stats_total_users; ?></div>
        <div class="stat-label">Total Users</div>
        <div class="stat-change up">↑ Total accounts</div>
      </div>
      <div class="stat-card green">
        <div class="stat-icon">👨‍🏫</div>
        <div class="stat-value"><?php echo $stats_teachers; ?></div>
        <div class="stat-label">Active Teachers</div>
        <div class="stat-change up">Verified Staff</div>
      </div>
      <div class="stat-card cyan">
        <div class="stat-icon">👨‍🎓</div>
        <div class="stat-value"><?php echo $stats_students; ?></div>
        <div class="stat-label">Active Students</div>
        <div class="stat-change up">Enrolled</div>
      </div>
      <div class="stat-card purple">
        <div class="stat-icon">📚</div>
        <div class="stat-value"><?php echo $stats_strands; ?></div>
        <div class="stat-label">Total Strands</div>
        <div class="stat-change up">Academic Tracks</div>
      </div>
      <div class="stat-card teal">
        <div class="stat-icon">🏛️</div>
        <div class="stat-value"><?php echo $stats_sections; ?></div>
        <div class="stat-label">Sections</div>
        <div class="stat-change neutral">Class Blocks</div>
      </div>
      <div class="stat-card amber">
        <div class="stat-icon">📊</div>
        <div class="stat-value"><?php echo $total_records; ?></div>
        <div class="stat-label">Total Records</div>
        <div class="stat-change up">All System Data</div>
      </div>
    </div>
    <div class="dash-grid dash-grid-1-2">
      <div class="card">
          <div class="card-header">
            <div class="card-title">📢 System Announcements</div>
            <button class="btn btn-ghost btn-sm" onclick="openAnnouncementModal()">+ Post</button>
          </div>
        
          <!-- Search bar -->
          <div style="padding:0 0 12px 0;">
            <input type="text" id="ann-search" placeholder="🔍 Search announcements..."
              oninput="filterAnnouncements()"
              style="width:100%;padding:8px 12px;border:1.5px solid var(--border,rgba(255,255,255,0.1));border-radius:8px;background:var(--bg3,rgba(255,255,255,0.05));color:var(--text,#f1f5f9);font-size:0.85rem;box-sizing:border-box;outline:none;">
          </div>
        
          <div id="announcements-list">
            <?php
            $ann_items = [];
            if ($announcements_result->num_rows > 0):
                while($a = $announcements_result->fetch_assoc()):
                    $poster_name     = trim(($a['firstname'] ?? '') . ' ' . ($a['lastname'] ?? ''));
                    $poster_role     = strtolower($a['role'] ?? 'unknown');
                    $is_mine         = ((int)$a['user_id'] === (int)$admin_id);
                    $role_badge_color = $poster_role === 'admin' ? '#ef4444' : '#8b5cf6';
                    $role_label      = $poster_role === 'admin' ? '🛡️ Admin' : '👨‍🏫 Teacher';
                    $cat             = $a['category'] ?? 'both';
                    $cat_label       = match($cat) {
                        'for_teacher' => '👨‍🏫 Teachers Only',
                        'for_student' => '👨‍🎓 Students Only',
                        'both'        => '👥 Everyone',
                        default       => '👥 Everyone',
                    };
                    $cat_color = match($cat) {
                        'for_teacher' => '#8b5cf6',
                        'for_student' => '#06b6d4',
                        'both'        => '#22c55e',
                        default       => '#22c55e',
                    };
                    $ann_items[] = $a;
                    ?>
                    <div class="announce-item" data-title="<?php echo strtolower(htmlspecialchars($a['title'])); ?>" data-message="<?php echo strtolower(htmlspecialchars($a['message'])); ?>" data-poster="<?php echo strtolower(htmlspecialchars($poster_name)); ?>">
                      <div class="announce-header" style="flex-wrap:wrap;gap:4px;">
                        <span class="announce-tag <?php echo $a['type']; ?>"><?php echo $a['type']; ?></span>
                        <span style="font-size:11px;padding:2px 8px;border-radius:20px;background:<?php echo $role_badge_color; ?>22;color:<?php echo $role_badge_color; ?>;font-weight:600;">
                            <?php echo $role_label; ?>: <?php echo htmlspecialchars($poster_name ?: 'Unknown'); ?>
                        </span>
                        <span style="font-size:11px;padding:2px 8px;border-radius:20px;background:<?php echo $cat_color; ?>22;color:<?php echo $cat_color; ?>;font-weight:600;">
                            <?php echo $cat_label; ?>
                        </span>
                        <span class="announce-time"><?php echo date('M d, h:i A', strtotime($a['created_at'])); ?></span>
                      </div>
                      <div class="announce-text">
                        <strong><?php echo htmlspecialchars($a['title']); ?></strong><br>
                        <?php echo nl2br(htmlspecialchars($a['message'])); ?>
                      </div>
                      <?php if ($is_mine): ?>
                      <div style="margin-top:10px;display:flex;gap:8px;">
                        <button type="button" class="edit-btn"
                          onclick='editAnnouncement(<?php echo json_encode([
                              "id"       => $a["id"],
                              "title"    => $a["title"],
                              "message"  => $a["message"],
                              "type"     => $a["type"],
                              "category" => $a["category"] ?? "both"
                          ]); ?>)'>✏️ Edit</button>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this announcement?')" style="display:inline-block;">
                          <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                          <button type="submit" name="delete_announcement" class="delete-btn">🗑 Delete</button>
                        </form>
                      </div>
                      <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div id="ann-empty" style="padding:15px;color:var(--muted);text-align:center;">No announcements yet.</div>
            <?php endif; ?>
          </div>
        
          <!-- Pagination controls -->
          <div id="ann-pagination" style="display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid rgba(255,255,255,0.07);margin-top:8px;gap:8px;flex-wrap:wrap;">
            <span id="ann-page-info" style="font-size:12px;color:var(--muted,#64748b);"></span>
            <div style="display:flex;gap:6px;">
              <button id="ann-prev" onclick="annChangePage(-1)"
                style="padding:5px 12px;border-radius:7px;border:1.5px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.06);color:var(--text,#f1f5f9);font-size:12px;cursor:pointer;">
                ← Prev
              </button>
              <button id="ann-next" onclick="annChangePage(1)"
                style="padding:5px 12px;border-radius:7px;border:1.5px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.06);color:var(--text,#f1f5f9);font-size:12px;cursor:pointer;">
                Next →
              </button>
            </div>
          </div>
        </div>
    </div>
    <div class="dash-grid dash-grid-2-1">
        <div class="card">
        <div class="card-header">
          <div class="card-title">⚡ Quick Actions</div>
        </div>

        <div class="quick-actions" id="quick-actions">

          <div class="quick-action" onclick="window.location.href='admin_teachers.php'">
            <span class="qa-icon">👨‍🏫</span>
            <span class="qa-label">Add Teacher</span>
          </div>

          <div class="quick-action" onclick="window.location.href='admin_students.php'">
            <span class="qa-icon">👨‍🎓</span>
            <span class="qa-label">Add Student</span>
          </div>

          <div class="quick-action" onclick="window.location.href='admin_strands.php'">
            <span class="qa-icon">📘</span>
            <span class="qa-label">Create Course</span>
          </div>

          <div class="quick-action" onclick="window.location.href='admin_sections.php'">
            <span class="qa-icon">🏫</span>
            <span class="qa-label">Create Section</span>
          </div>

          <div class="quick-action" onclick="window.location.href='admin_reports.php'">
            <span class="qa-icon">📊</span>
            <span class="qa-label">Generate Report</span>
          </div>

        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <div class="card-title">👤 Recent Registrations</div>
        </div>
        <div id="users-list">
            <?php if ($recent_users_result->num_rows > 0): ?>
                <?php while($u = $recent_users_result->fetch_assoc()): 
                    $roleData = getRoleBadge($u['role']);
                    $initials = getInitials($u['firstname'], $u['lastname']);
                    $bgGradient = getGradient($u['role']);
                    $detailDate = date('M d', strtotime($u['created_at']));
                ?>
                <div class="user-row">
                  <div class="user-row-avatar" style="background:<?php echo $bgGradient; ?>"><?php echo $initials; ?></div>
                  <div class="user-row-info">
                    <div class="user-row-name"><?php echo $u['firstname'] . ' ' . $u['lastname']; ?></div>
                    <div class="user-row-detail"><?php echo ucfirst($u['role']); ?> — Reg <?php echo $detailDate; ?></div>
                  </div>
                  <div class="user-row-role"><span class="badge <?php echo $roleData['badge']; ?>"><?php echo ucfirst($u['role']); ?></span></div>
                  <div class="user-row-status">
                    <span class="badge <?php echo ($u['is_verified'] == 1) ? 'badge-green' : 'badge-amber'; ?>">
                        <?php echo ($u['is_verified'] == 1) ? 'Active' : 'Pending'; ?>
                    </span>
                  </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding:20px; text-align:center; color:var(--muted)">No recent users found.</div>
            <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="dash-grid dash-grid-1-1">
      <div class="card">
        <div class="card-header">
          <div class="card-title">🏛️ Available Strands</div>
          <span class="badge badge-cyan"><?php echo $stats_strands; ?> tracks</span>
        </div>
        <div id="dept-list">
            <?php if ($strands_result->num_rows > 0): ?>
                <?php while($d = $strands_result->fetch_assoc()): ?>
                    <div class="dept-bar-wrap">
                      <div class="dept-label"><?php echo $d['strand']; ?> <small style="color:var(--muted)">(<?php echo $d['track']; ?>)</small></div>
                      <div class="dept-bar"><div class="dept-fill blue-fill" style="width:100%"></div></div>
                      <div class="dept-count" style="color:var(--accent)">Active</div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding:20px;">No strands found.</div>
            <?php endif; ?>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <div class="card-title">💾 Storage Usage</div>
          <span class="badge badge-purple">
            <?php echo $total_used_gb; ?> GB / <?php echo $total_limit_gb; ?> GB
          </span>
        </div>
        <div id="storage-list">
            <div class="storage-wrap" style="margin-bottom:18px">
                <div class="storage-header">
                  <span class="storage-label">Total Used</span>
                  <span class="storage-pct" style="color:var(--accent)">
                    <?php echo $total_used_gb; ?> GB / <?php echo $total_limit_gb; ?> GB
                  </span>
                </div>
                <div class="storage-bar">
                  <div class="storage-seg" style="width:<?php echo ($course_materials / $total_limit_gb) * 100; ?>%;background:var(--accent)" title="Course Materials: <?php echo $course_materials; ?> GB"></div>

                  <div class="storage-seg" style="width:<?php echo ($videos / $total_limit_gb) * 100; ?>%;background:var(--green)" title="Videos: <?php echo $videos; ?> GB"></div>

                  <div class="storage-seg" style="width:<?php echo ($pdfs / $total_limit_gb) * 100; ?>%;background:var(--purple)" title="PDF: <?php echo $pdfs; ?> GB"></div>

                  <div class="storage-seg" style="width:<?php echo ($pptx / $total_limit_gb) * 100; ?>%;background:var(--amber)" title="PPTX: <?php echo $pptx; ?> GB"></div>

                  <div class="storage-seg" style="width:<?php echo ($backups / $total_limit_gb) * 100; ?>%;background:var(--rose)" title="Backups: <?php echo $backups; ?> GB"></div>
                </div>
            </div>
            <div class="dept-bar-wrap">
              <div class="dept-label" style="min-width:120px">Course Materials</div>

              <div class="dept-bar">
                <div class="progress-fill"
                    style="width:<?php echo ($course_materials / $total_limit_gb) * 100; ?>%;
                            background:var(--accent);
                            border-radius:4px;
                            height:100%">
                </div>
              </div>

              <div class="dept-count" style="color:var(--accent)">
                <?php echo $course_materials; ?> GB
              </div>
            </div>
              <div class="dept-bar-wrap">
                <div class="dept-label" style="min-width:120px">User Uploads</div>

                <div class="dept-bar">
                  <div class="progress-fill"
                      style="width:<?php echo ($user_uploads / $total_limit_gb) * 100; ?>%;
                              background:var(--green);
                              border-radius:4px;
                              height:100%">
                  </div>
                </div>

                <div class="dept-count" style="color:var(--green)">
                  <?php echo $user_uploads; ?> GB
                </div>
              </div>
        </div>
      </div>
    </div>
  </div>
</main>
<div id="announcementModal" class="modal-overlay" style="display:none;">
  <div class="modal-box">
    <div class="modal-header">
      <h3>📢 Post Announcement</h3>
      <button onclick="closeAnnouncementModal()">✖</button>
    </div>
    <form method="POST">
      <input type="hidden" name="id" id="ann_id">
      <div class="modal-body">
        <label>Title</label>
        <input type="text" name="title" id="ann_title" required>

        <label>Type</label>
        <select name="type" id="ann_type">
          <option value="system">System</option>
          <option value="important">Important</option>
          <option value="event">Event</option>
        </select>

        <label>Send To</label>
        <select name="category" id="ann_category">
          <option value="both">Everyone (Teachers & Students)</option>
          <option value="for_teacher">Teachers Only</option>
          <option value="for_student">Students Only</option>
        </select>

        <label>Message</label>
        <textarea name="message" id="ann_message" rows="4" required></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeAnnouncementModal()">Cancel</button>
        <button type="submit" name="update_announcement" id="updateBtn" style="display:none;">Update</button>
        <button type="submit" name="add_announcement" id="postBtn">Post</button>
      </div>
    </form>
  </div>
</div>
</div>
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

<div class="toast-container" id="toast-container"></div>
<script>

function openModal(id) {
  var el = document.getElementById(id);
  if (el) el.style.display = 'flex';
}

function closeModal(id) {
  var el = document.getElementById(id);
  if (el) el.style.display = 'none';
}
function openAnnouncementModal() {
  document.getElementById('announcementModal').style.display = 'flex';

  document.getElementById('ann_id').value = '';
  document.getElementById('ann_title').value = '';
  document.getElementById('ann_message').value = '';
  document.getElementById('ann_type').value = 'system';

  document.getElementById('postBtn').style.display = 'inline-block';
  document.getElementById('updateBtn').style.display = 'none';
}

function closeAnnouncementModal() {
  document.getElementById('announcementModal').style.display = 'none';
}

function editAnnouncement(data) {
  openAnnouncementModal();
  document.getElementById('ann_id').value       = data.id;
  document.getElementById('ann_title').value    = data.title;
  document.getElementById('ann_message').value  = data.message;
  document.getElementById('ann_type').value     = data.type;
  document.getElementById('ann_category').value = data.category || 'both';
  document.getElementById('postBtn').style.display   = 'none';
  document.getElementById('updateBtn').style.display = 'inline-block';
}

function setGreeting() {
  var h = new Date().getHours();
  var greet = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
  var emoji = h < 12 ? '🛡️' : h < 18 ? '🖥️' : '🌙';
  var el = document.getElementById('greeting-text');
  if (el) el.textContent = greet + ', Admin! ' + emoji;
}

setGreeting();

<?php if ($msg): ?>
showToast('<?php echo $msg; ?>', '<?php echo $msgType; ?>');
<?php endif; ?>

var sidebar = document.getElementById('sidebar');
var overlay = document.getElementById('sidebar-overlay');
var menuBtn = document.getElementById('menuBtn');

function openSidebar() {
  sidebar.classList.add('open');
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeSidebar() {
  sidebar.classList.remove('open');
  overlay.classList.remove('open');
  document.body.style.overflow = '';
}

menuBtn.addEventListener('click', function() {
  if (sidebar.classList.contains('open')) closeSidebar();
  else openSidebar();
});
overlay.addEventListener('click', closeSidebar);

var navItems = document.querySelectorAll('.nav-item');
navItems.forEach(function(item) {
  item.addEventListener('click', function() {
    if (window.innerWidth <= 768) closeSidebar();
  });
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeSidebar();
});

var themeToggle = document.getElementById('themeToggle');
var themeIcon = document.getElementById('theme-icon');
var themeText = document.getElementById('theme-text');

/* ========== THEME ========== */
var savedTheme = '<?= htmlspecialchars($savedTheme) ?>';
localStorage.setItem('excelearn-theme', savedTheme);
if (savedTheme === 'light') document.body.classList.add('light');

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

if ('IntersectionObserver' in window) {
  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        var fills = entry.target.querySelectorAll('.progress-fill, .dept-fill, .storage-seg');
        fills.forEach(function(fill) {
          var w = fill.style.width;
          fill.style.width = '0%';
          requestAnimationFrame(function() {
            requestAnimationFrame(function() {
              fill.style.width = w;
            });
          });
        });
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });

  document.querySelectorAll('.card').forEach(function(card) {
    observer.observe(card);
  });
}

var resizeTimer;
window.addEventListener('resize', function() {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(function() {
    if (window.innerWidth > 768) closeSidebar();
  }, 150);
});

// ── Announcement pagination + search ──────────────────────────────────────
var annPage     = 1;
var annPerPage  = 3;
var annFiltered = [];

function getAllAnnItems() {
  return Array.from(document.querySelectorAll('.announce-item'));
}

function filterAnnouncements() {
  annPage = 1;
  renderAnnPage();
}

function renderAnnPage() {
  var query = (document.getElementById('ann-search')?.value || '').toLowerCase().trim();
  var all   = getAllAnnItems();

  annFiltered = all.filter(function(el) {
    if (!query) return true;
    var title   = el.dataset.title   || '';
    var message = el.dataset.message || '';
    var poster  = el.dataset.poster  || '';
    return title.includes(query) || message.includes(query) || poster.includes(query);
  });

  var total     = annFiltered.length;
  var totalPages = Math.max(1, Math.ceil(total / annPerPage));
  if (annPage > totalPages) annPage = totalPages;

  var start = (annPage - 1) * annPerPage;
  var end   = start + annPerPage;

  // Hide all, then show only current page slice
  all.forEach(function(el) { el.style.display = 'none'; });
  annFiltered.forEach(function(el, idx) {
    el.style.display = (idx >= start && idx < end) ? '' : 'none';
  });

  // Empty state
  var emptyEl = document.getElementById('ann-empty');
  if (emptyEl) emptyEl.style.display = total === 0 ? '' : 'none';

  // Pagination info
  var info = document.getElementById('ann-page-info');
  if (info) {
    info.textContent = total === 0
      ? 'No results found'
      : 'Showing ' + (Math.min(start + 1, total)) + '–' + Math.min(end, total) + ' of ' + total;
  }

  // Button states
  var prev = document.getElementById('ann-prev');
  var next = document.getElementById('ann-next');
  if (prev) prev.style.opacity = annPage <= 1 ? '0.4' : '1';
  if (next) next.style.opacity = annPage >= totalPages ? '0.4' : '1';
  if (prev) prev.disabled = annPage <= 1;
  if (next) next.disabled = annPage >= totalPages;

  // Hide pagination if only 1 page and no search
  var pag = document.getElementById('ann-pagination');
  if (pag) pag.style.display = (total <= annPerPage && !query) ? 'none' : 'flex';
}

function annChangePage(dir) {
  var total      = annFiltered.length;
  var totalPages = Math.max(1, Math.ceil(total / annPerPage));
  annPage = Math.max(1, Math.min(annPage + dir, totalPages));
  renderAnnPage();
}

// Init on load
renderAnnPage();
</script>
</body>
</html>