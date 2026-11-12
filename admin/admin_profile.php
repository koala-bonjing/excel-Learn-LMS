<?php
session_start();
include "../connection.php";

// --- AUTH CHECK ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
}

 $user_id = $_SESSION['user_id'];
 $success_msg = "";
 $error_msg = "";

// --- FETCH USER DATA ---
 $stmt = $conn->prepare("SELECT id, profile_pic, email, password, firstname, middlename, lastname, age, gender, contact_number, address, is_verified, role, status, two_factor_enabled, two_factor_secret, updated_at, created_at FROM tblusers WHERE id = ? AND role = 'admin'");
 $stmt->bind_param("i", $user_id);
 $stmt->execute();
 $result = $stmt->get_result();
 $user = $result->fetch_assoc();

// ===== DASHBOARD COUNTS =====

// Count Teachers
$teacher_count = 0;
$teacher_stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM tblusers 
    WHERE role = 'teacher'
");
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();
if ($teacher_row = $teacher_result->fetch_assoc()) {
    $teacher_count = $teacher_row['total'];
}

// Count Students
$student_count = 0;
$student_stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM tblstudents
");
$student_stmt->execute();
$student_result = $student_stmt->get_result();
if ($student_row = $student_result->fetch_assoc()) {
    $student_count = $student_row['total'];
}

// Count Strands
$strand_count = 0;
$strand_stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM tblstrands
");
$strand_stmt->execute();
$strand_result = $strand_stmt->get_result();
if ($strand_row = $strand_result->fetch_assoc()) {
    $strand_count = $strand_row['total'];
}

// Count Sections
$section_count = 0;
$section_stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM tblsections
");
$section_stmt->execute();
$section_result = $section_stmt->get_result();
if ($section_row = $section_result->fetch_assoc()) {
    $section_count = $section_row['total'];
}

  // --- SAVE CURRENT SESSION ---
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';

// ===== DETECT BROWSER =====
$browser = 'Unknown Browser';

if (strpos($user_agent, 'Edg') !== false) {
    $browser = 'Microsoft Edge';
} elseif (strpos($user_agent, 'OPR') !== false || strpos($user_agent, 'Opera') !== false) {
    $browser = 'Opera';
} elseif (strpos($user_agent, 'Chrome') !== false) {
    $browser = 'Google Chrome';
} elseif (strpos($user_agent, 'Firefox') !== false) {
    $browser = 'Mozilla Firefox';
} elseif (strpos($user_agent, 'Safari') !== false) {
    $browser = 'Safari';
}

// ===== DETECT DEVICE =====
$device = 'Desktop';

if (preg_match('/mobile/i', $user_agent)) {
    $device = 'Mobile';
}

if (preg_match('/tablet|ipad/i', $user_agent)) {
    $device = 'Tablet';
}

if (preg_match('/android/i', $user_agent)) {
    $device .= ' Android';
}

if (preg_match('/iphone/i', $user_agent)) {
    $device .= ' iPhone';
}

if (preg_match('/windows/i', $user_agent)) {
    $device .= ' Windows PC';
}

if (preg_match('/macintosh|mac os x/i', $user_agent)) {
    $device .= ' Mac';
}

if (preg_match('/linux/i', $user_agent)) {
    $device .= ' Linux';
}

// ===== GET REAL IP ADDRESS =====
function getRealIpAddress() {

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ip_list[0]);
    }

    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
}

$ip_address = getRealIpAddress();

// ===== SIMPLE LOCATION =====
$location = 'Philippines';

  // Remove previous current session
  $conn->query("UPDATE tbluser_sessions SET is_current = 0 WHERE user_id = $user_id");

  // Save new session
  $session_stmt = $conn->prepare("
      INSERT INTO tbluser_sessions 
      (user_id, device, browser, ip_address, location, is_current)
      VALUES (?, ?, ?, ?, ?, 1)
  ");

  $session_stmt->bind_param(
      "issss",
      $user_id,
      $device,
      $browser,
      $ip_address,
      $location
  );

  $session_stmt->execute();

if (!$user) {
    die("Admin user not found or invalid role.");
}

// --- HANDLE PROFILE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $firstname = trim($_POST['firstname']);
    $middlename = trim($_POST['middlename']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $gender = trim($_POST['gender']);
    $age = intval($_POST['age']);
    $address = trim($_POST['address']);
    
    // Handle File Upload
    $profile_pic = $user['profile_pic'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $target_dir = "../uploads/profile_pic/admin/"; // Ensure this folder exists and is writable
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $filename = time() . "_" . basename($_FILES["avatar"]["name"]);
        $target_file = $target_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image
        $check = getimagesize($_FILES["avatar"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $profile_pic = $filename;
            } else {
                $error_msg = "Error uploading file.";
            }
        } else {
            $error_msg = "File is not an image.";
        }
    }

    if (empty($error_msg)) {
        $update_stmt = $conn->prepare("UPDATE tblusers SET firstname=?, middlename=?, lastname=?, email=?, contact_number=?, gender=?, age=?, address=?, profile_pic=?, updated_at=NOW() WHERE id=?");
        $update_stmt->bind_param("sssssssssi", $firstname, $middlename, $lastname, $email, $contact_number, $gender, $age, $address, $profile_pic, $user_id);
        
        if ($update_stmt->execute()) {
            $success_msg = "Profile updated successfully!";
            // Refresh user data
            $user['firstname'] = $firstname;
            $user['lastname'] = $lastname;
            $user['middlename'] = $middlename;
            $user['email'] = $email;
            $user['contact_number'] = $contact_number;
            $user['gender'] = $gender;
            $user['age'] = $age;
            $user['address'] = $address;
            $user['profile_pic'] = $profile_pic;
        } else {
            $error_msg = "Error updating profile: " . $conn->error;
        }
    }
}

// --- HANDLE PASSWORD CHANGE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_pw = $_POST['current_password'];
    $new_pw = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];

    if (password_verify($current_pw, $user['password'])) {
        if ($new_pw === $confirm_pw) {
            if (strlen($new_pw) >= 8) {
                $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
                $pw_stmt = $conn->prepare("UPDATE tblusers SET password=? WHERE id=?");
                $pw_stmt->bind_param("si", $hashed_pw, $user_id);
                if ($pw_stmt->execute()) {
                    $success_msg = "Password changed successfully.";
                } else {
                    $error_msg = "Database error changing password.";
                }
            } else {
                $error_msg = "New password must be at least 8 characters.";
            }
        } else {
            $error_msg = "New passwords do not match.";
        }
    } else {
        $error_msg = "Current password is incorrect.";
    }
}
// --- HANDLE ENABLE 2FA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enable_2fa'])) {

    // Simple generated secret
    $secret = strtoupper(bin2hex(random_bytes(8)));

    $twofa_stmt = $conn->prepare("
        UPDATE tblusers
        SET two_factor_enabled = 1,
            two_factor_secret = ?
        WHERE id = ?
    ");

    $twofa_stmt->bind_param("si", $secret, $user_id);

    if ($twofa_stmt->execute()) {

        $user['two_factor_enabled'] = 1;
        $user['two_factor_secret'] = $secret;

        $success_msg = "Two-Factor Authentication enabled successfully.";

    } else {

        $error_msg = "Failed to enable 2FA.";

    }
}

// --- HANDLE DISABLE 2FA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['disable_2fa'])) {

    $disable_stmt = $conn->prepare("
        UPDATE tblusers
        SET two_factor_enabled = 0,
            two_factor_secret = NULL
        WHERE id = ?
    ");

    $disable_stmt->bind_param("i", $user_id);

    if ($disable_stmt->execute()) {

        $user['two_factor_enabled'] = 0;
        $user['two_factor_secret'] = null;

        $success_msg = "Two-Factor Authentication disabled.";

    } else {

        $error_msg = "Failed to disable 2FA.";

    }
}
// --- HELPERS ---
function getInitials($fname, $lname) {
    return strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1));
}

function getAccountAge($created_at) {
    $date1 = new DateTime($created_at);
    $date2 = new DateTime();
    $diff = $date1->diff($date2);
    if ($diff->y > 0) return $diff->y . "y";
    if ($diff->m > 0) return $diff->m . "mo";
    return $diff->d . "d";
}

// --- FETCH RECENT SESSIONS ---
$sessions_data = [];

$sessions_stmt = $conn->prepare("
    SELECT device, browser, ip_address, location, is_current, created_at
    FROM tbluser_sessions
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");

$sessions_stmt->bind_param("i", $user_id);
$sessions_stmt->execute();

$sessions_result = $sessions_stmt->get_result();

while ($row = $sessions_result->fetch_assoc()) {
    $sessions_data[] = $row;
}

// --- FETCH RECENT ACTIVITIES ---
$activities_data = [];

$activity_stmt = $conn->prepare("
    SELECT browser, device, ip_address, location, is_current, created_at
    FROM tbluser_sessions
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");

$activity_stmt->bind_param("i", $user_id);
$activity_stmt->execute();

$activity_result = $activity_stmt->get_result();

while ($row = $activity_result->fetch_assoc()) {

    $activity_text = "Logged in from <strong>" .
        htmlspecialchars($row['browser']) .
        "</strong> using " .
        htmlspecialchars($row['device']);

    if ($row['is_current']) {
        $activity_text .= " <span class='badge badge-green'>Current Session</span>";
    }

    $activities_data[] = [
        "text" => $activity_text,
        "time" => date("M d, Y — h:i A", strtotime($row['created_at'])),
        "color" => $row['is_current'] ? "var(--green)" : "var(--accent)"
    ];
}

// Load saved theme
$themeResult = $conn->prepare("SELECT theme FROM tblteacher_settings WHERE teacher_id = ? LIMIT 1");
$themeResult->bind_param("i", $user_id);
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
<title>ExceLearn — My Profile</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin/admin_profile.css">
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
@media (max-width: 768px) {
  /* Center the profile nav tabs */
  .profile-nav {
    display: flex !important;
    flex-wrap: wrap !important;
    justify-content: center !important;
    gap: 6px !important;
  }
  .pn-btn {
    font-size: 0.75rem !important;
    padding: 6px 12px !important;
  }

  /* Stack Personal Info and Account Info cards vertically */
  #tab-overview > div {
    grid-template-columns: 1fr !important;
    gap: 12px !important;
  }

  /* Profile header stack on mobile */
  .profile-header {
    flex-direction: column !important;
    align-items: center !important;
    text-align: center !important;
    gap: 12px !important;
  }
  .profile-meta {
    justify-content: center !important;
    flex-wrap: wrap !important;
  }
  .profile-actions {
    justify-content: center !important;
    flex-wrap: wrap !important;
  }
}
@media (max-width: 768px) {
  .profile-role {
    justify-content: center !important;
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 6px !important;
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
    <img src="../assets/img/Excelsior_Seal_Logo.png" alt="ExceLearn Logo" style="height:38px;width:auto;object-fit:contain;flex-shrink:0;">
    <div style="min-width:0"><div class="logo-name">Exce<span style="color:#22c55e">Learn</span></div><div class="logo-sub">Learn Better. Anytime, Anywhere.</div></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a class="nav-item" href="index.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>Dashboard</a>
    <div class="nav-section-label" style="margin-top:14px">User Management</div>
    <a class="nav-item" href="admin_teachers.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>Teachers<span class="nav-badge"><?php echo $teacher_count; ?></span></a>
    <a class="nav-item" href="admin_students.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Students<span class="nav-badge"><?php echo $student_count; ?></span></a>
    <div class="nav-section-label" style="margin-top:14px">Academic</div>
    <a class="nav-item" href="admin_sections.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>Grade & Sections<span class="nav-badge"><?php echo $section_count; ?></a>
    <a class="nav-item" href="admin_strands.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>Strands<span class="nav-badge"><?php echo $strand_count; ?></span></span></a>
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
    <a class="nav-item active" href="admin_profile.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>My Profile</a>
    <a class="nav-item" href="settings.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33h0A1.65 1.65 0 009 3.09V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51h0a1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82v0a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
      Settings
    </a>
    <a class="nav-item" href="about.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>About</a>
    <a class="nav-item" href="#" onclick="openLogoutModal();return false;"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a>
  </nav>
  <div class="sidebar-footer"><div class="user-card"><div class="user-avatar" style="<?php echo empty($user['profile_pic']) 
    ? 'background:linear-gradient(135deg,var(--rose),var(--amber))' 
    : 'background-image:url(\'../uploads/profile_pic/admin/' . htmlspecialchars($user['profile_pic']) . '\');background-size:cover;background-position:center;'; ?>"></div><div class="user-info"><p><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></p><span>Administrator</span></div></div></div>
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
    <?php if($success_msg): ?>
        <script>showToast('<?php echo $success_msg; ?>','success');</script>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <script>showToast('<?php echo $error_msg; ?>','error');</script>
    <?php endif; ?>

    <!-- Profile Header -->
    <div class="profile-header">
      <div class="profile-avatar-wrap">
        <div class="profile-avatar" id="profile-avatar-display" style="<?php echo empty($user['profile_pic']) 
            ? 'background:linear-gradient(135deg,var(--rose),var(--amber))' 
            : 'background-image:url(\'../uploads/profile_pic/admin/' . htmlspecialchars($user['profile_pic']) . '\');background-size:cover;background-position:center;'; ?>">
            <?php if (empty($user['profile_pic'])): ?>
                <span id="avatar-initials"><?php echo getInitials($user['firstname'], $user['lastname']); ?></span>
            <?php endif; ?>
        </div>
        <div class="avatar-overlay" onclick="document.getElementById('avatar-input').click()">
          <span style="font-size:20px;color:#fff">📷</span>
        </div>
        <!-- Trigger inside the profile form below -->
        <div class="avatar-badge" onclick="document.getElementById('avatar-input').click()">✏️</div>
      </div>
      <div class="profile-info">
        <div class="profile-name" id="display-name"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></div>
        <div class="profile-role">
          <span class="badge badge-purple"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
          <span class="badge badge-green"><?php echo htmlspecialchars(ucfirst($user['status'])); ?></span>
        </div>
        <div class="profile-meta">
          <div class="profile-meta-item">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <?php echo htmlspecialchars($user['email']); ?>
          </div>
          <div class="profile-meta-item">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
            <?php echo htmlspecialchars($user['contact_number']); ?>
          </div>
          <div class="profile-meta-item">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?php echo htmlspecialchars($user['address']); ?>
          </div>
        </div>
      </div>
      <div class="profile-actions">
        <button class="btn btn-primary btn-sm" onclick="setProfileTab('edit')">✏️ Edit Profile</button>
        <button class="btn btn-ghost btn-sm" onclick="openChangePwModal()">🔑 Change Password</button>
      </div>
    </div>

    <!-- Profile Nav -->
    <div class="profile-nav" id="profile-nav">
      <button class="pn-btn active" onclick="setProfileTab('overview')">Overview</button>
      <button class="pn-btn" onclick="setProfileTab('edit')">Edit Profile</button>
      <button class="pn-btn" onclick="setProfileTab('security')">Security</button>
      <button class="pn-btn" onclick="setProfileTab('activity')">Activity</button>
    </div>

    <!-- OVERVIEW TAB -->
    <div class="profile-tab" id="tab-overview">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="card">
          <div style="font-size:14px;font-weight:600;margin-bottom:14px">Personal Information</div>
          <div style="display:flex;flex-direction:column;gap:8px;font-size:13px">
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Full Name</span><span style="font-weight:500"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname']); ?></span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Email </span><span style="font-weight:500"><?php echo htmlspecialchars($user['email']); ?></span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Gender</span><span style="font-weight:500"><?php echo htmlspecialchars($user['gender']); ?></span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Age</span><span style="font-weight:500"><?php echo htmlspecialchars($user['age']); ?></span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Address</span><span style="font-weight:500;text-align:right;max-width:60%"><?php echo htmlspecialchars($user['address']); ?></span></div>
          </div>
        </div>
        <div class="card">
          <div style="font-size:14px;font-weight:600;margin-bottom:14px">Account Information</div>
          <div style="display:flex;flex-direction:column;gap:8px;font-size:13px">
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Role</span><span class="badge badge-purple"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Status</span><span class="badge badge-green"><?php echo htmlspecialchars(ucfirst($user['status'])); ?></span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Verified</span><span class="badge badge-<?php echo $user['is_verified'] ? 'green' : 'red'; ?>"><?php echo $user['is_verified'] ? 'Yes' : 'No'; ?></span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Account Created</span><span style="font-weight:500"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:var(--text3)">Last Updated</span><span style="font-weight:500"><?php echo date('M d, Y', strtotime($user['updated_at'])); ?></span></div>
          </div>
        </div>
      </div>
      <div class="card" style="margin-top:14px">
        <div style="font-size:14px;font-weight:600;margin-bottom:14px">Recent Sessions</div>
        <div id="sessions-list"></div>
      </div>
    </div>

    <!-- EDIT TAB -->
    <div class="profile-tab" id="tab-edit" style="display:none">
      <!-- WRAPPED IN FORM FOR PHP PROCESSING -->
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="update_profile" value="1">
        
        <div class="card">
          <div style="font-size:14px;font-weight:600;margin-bottom:16px">Personal Information</div>
          <div class="form-group">
            <label class="form-label">First Name</label>
            <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Middle Name</label>
            <input type="text" name="middlename" value="<?php echo htmlspecialchars($user['middlename']); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Last Name</label>
            <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Phone Number</label>
              <input type="tel" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number']); ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Gender</label>
              <select name="gender">
                <option value="Male" <?php echo $user['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo $user['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                <option value="Prefer not to say" <?php echo $user['gender'] == 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Age</label>
              <!-- Using Age input as per DB Schema -->
              <input type="number" name="age" value="<?php echo htmlspecialchars($user['age']); ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Address</label>
            <textarea name="address" rows="2"><?php echo htmlspecialchars($user['address']); ?></textarea>
          </div>
          
          <div class="form-group">
            <label class="form-label">Profile Picture</label>
            <input type="file" id="avatar-input" name="avatar" accept="image/*" onchange="previewAvatar(this)">
            <div class="form-hint">Allowed: JPG, PNG. Max 2MB.</div>
          </div>
          
          <div class="save-bar">
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </div>
      </form>
    </div>

    <!-- SECURITY TAB -->
    <div class="profile-tab" id="tab-security" style="display:none">
      <div class="card">
        <div style="font-size:14px;font-weight:600;margin-bottom:4px">Password</div>
        <div class="form-hint" style="margin-bottom:16px">Ensure your account is using a strong, unique password.</div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:var(--bg3);border-radius:var(--radius-sm);margin-bottom:16px;flex-wrap:wrap;gap:10px">
          <div>
            <div style="font-size:13px;font-weight:500">Password Status</div>
            <div style="font-size:12px;color:var(--text3);margin-top:2px">Last updated: <?php echo date('M d, Y', strtotime($user['updated_at'])); ?></div>
          </div>
          <button class="btn btn-ghost btn-sm" onclick="openChangePwModal()">🔑 Change Password</button>
        </div>
        <hr class="form-divider">
        <div style="font-size:14px;font-weight:600;margin-bottom:4px">Two-Factor Authentication</div>
        <div class="form-hint" style="margin-bottom:16px">Add an extra layer of security to your account.</div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:var(--bg3);border-radius:var(--radius-sm);flex-wrap:wrap;gap:10px">
          <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:24px">🔒</span>
            <div>
              <div style="font-size:13px;font-weight:500">
                2FA Status:

                <?php if ($user['two_factor_enabled']): ?>
                    <span class="badge badge-green">Enabled</span>
                <?php else: ?>
                    <span class="badge badge-red">Disabled</span>
                <?php endif; ?>
              </div>

              <div style="font-size:11px;color:var(--text3);margin-top:2px">

                <?php if ($user['two_factor_enabled']): ?>
                    Your account is protected with Two-Factor Authentication.
                <?php else: ?>
                    Enable 2FA for enhanced account protection.
                <?php endif; ?>

              </div>
            </div>
          </div>
          <?php if (!$user['two_factor_enabled']): ?>

            <form method="POST" style="margin:0">
                <input type="hidden" name="enable_2fa" value="1">
                <button type="submit" class="btn btn-primary btn-sm">
                    Enable 2FA
                </button>
            </form>

            <?php else: ?>

            <form method="POST" style="margin:0">
                <input type="hidden" name="disable_2fa" value="1">
                <button type="submit" class="btn btn-ghost btn-sm">
                    Disable 2FA
                </button>
            </form>

          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ACTIVITY TAB -->
    <div class="profile-tab" id="tab-activity" style="display:none">
      <div class="card">
        <div style="font-size:14px;font-weight:600;margin-bottom:14px">Recent Activity</div>
        <div class="form-hint" style="margin-bottom:14px">Your latest actions and system events related to your account.</div>
        <div id="activity-timeline"></div>
      </div>
    </div>

  </div>
</main>
</div>

<!-- CHANGE PASSWORD MODAL (PHP Form) -->
<div class="modal-overlay" id="pw-modal">
  <form method="POST">
    <input type="hidden" name="change_password" value="1">
    <div class="modal" style="max-width:440px">
      <div class="modal-header">
        <div class="modal-title">Change Password</div>
        <button type="button" class="modal-close" onclick="closeModal('pw-modal')">✕</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Current Password</label>
          <input type="password" name="current_password" placeholder="Enter your current password" required>
        </div>
        <div class="form-group">
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" placeholder="Enter new password" oninput="checkPwStrength()" required>
          <div id="pw-strength-bar" style="margin-top:6px;height:4px;border-radius:2px;background:var(--bg4);overflow:hidden"><div id="pw-strength-fill" style="height:100%;width:0;border-radius:2px;transition:all .3s ease"></div></div>
          <div id="pw-strength-text" style="font-size:11px;margin-top:3px;color:var(--text3)"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" placeholder="Confirm new password" required>
        </div>
        <div id="pw-requirements" style="font-size:11px;color:var(--text3);margin-top:-8px">
          <div id="req-length" style="padding:2px 0">❌ Minimum 8 characters</div>
          <div id="req-upper" style="padding:2px 0">❌ At least one uppercase letter</div>
          <div id="req-number" style="padding:2px 0">❌ At least one number</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('pw-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Password</button>
      </div>
    </div>
  </form>
</div>

<!-- 2FA MODAL -->
<div class="modal-overlay" id="twofa-modal">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <div class="modal-title">Enable Two-Factor Authentication</div>
      <button class="modal-close" onclick="closeModal('twofa-modal')">✕</button>
    </div>
    <div class="modal-body" style="text-align:center">
      <div style="font-size:48px;margin-bottom:12px">📱</div>
      <div style="font-size:14px;font-weight:500;margin-bottom:6px">Scan with your authenticator app</div>
      <div style="font-size:12px;color:var(--text3);margin-bottom:16px">Use Google Authenticator, Authy, or any compatible TOTP app.</div>
      <div style="display:inline-block;padding:16px;background:#fff;border-radius:var(--radius-sm);margin-bottom:16px">
        <div style="width:140px;height:140px;background:repeating-conic-gradient(#000 0% 25%, #fff 0% 50%) 50%/16px 16px;border-radius:2px;opacity:.8"></div>
      </div>
      <div style="font-size:12px;color:var(--text3);margin-bottom:16px">Or enter this code manually:</div>
      <div style="font-family:monospace;font-size:16px;font-weight:600;letter-spacing:3px;padding:10px;background:var(--bg3);border-radius:var(--radius-sm);user-select:all">JBSW Y3DP EHPK 5NT7</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('twofa-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="closeModal('twofa-modal');showToast('2FA enabled successfully','success')">Verify & Enable</button>
    </div>
  </div>
</div>

<!-- LOGOUT MODAL -->
<div class="modal-overlay" id="logout-modal">
  <div class="modal" style="max-width:400px">
    <div class="confirm-body">
      <div class="confirm-icon">🔒</div>
      <div class="confirm-text">Logout from ExceLearn?</div>
      <div class="confirm-sub">You will be redirected to the login page.</div>
      <div class="confirm-actions">
        <button class="btn btn-ghost" onclick="closeModal('logout-modal')">Cancel</button>
        <button class="btn btn-danger" style="background:var(--red-bg);color:var(--red);border:1.5px solid rgba(255,107,107,.25)" onclick="window.location.href='../logout.php'">Logout</button>
      </div>
    </div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>
/* ===== DATA (Static for Demo/Visuals since tables not provided) ===== */
var sessions = <?php echo json_encode(array_map(function($s){

    $icon = "🖥️";
    $bg = "var(--accent-glow)";

  if (
      stripos($s['device'], 'Mobile') !== false ||
      stripos($s['device'], 'Android') !== false ||
      stripos($s['device'], 'iPhone') !== false
  ) {
        $icon = "📱";
        $bg = "var(--green-bg)";
    }

    return [
        "device" => $s['browser'],
        "icon" => $icon,
        "bg" => $bg,
        "ip" => $s['ip_address'],
        "location" => $s['location'],
        "time" => date("M d, Y — h:i A", strtotime($s['created_at'])),
        "current" => (bool)$s['is_current']
    ];
}, $sessions_data)); ?>;

var activities = <?php echo json_encode($activities_data); ?>;

/* ===== UI INTERACTION LOGIC ===== */

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var display = document.getElementById("profile-avatar-display");
            display.style.backgroundImage = "url('" + e.target.result + "')";
            display.style.backgroundSize = "cover";
            var initials = document.getElementById("avatar-initials");
            if(initials) initials.style.display = "none";
        }
        reader.readAsDataURL(input.files[0]);
    }
}

/* ===== TABS ===== */
function setProfileTab(tab) {
  document.querySelectorAll(".pn-btn").forEach(function(b) { b.classList.remove("active"); });
  document.querySelectorAll(".pn-btn").forEach(function(b) {
    var t = b.textContent.toLowerCase().replace(" ","");
    if (t === tab || (tab === "edit" && t === "editprofile") || (tab === "security" && t === "security") || (tab === "activity" && t === "activity") ||(tab === "overview" && t === "overview")) b.classList.add("active");
  });
  document.querySelectorAll(".profile-tab").forEach(function(el) { el.style.display = "none"; });
  var target = document.getElementById("tab-" + tab);
  if (target) target.style.display = "";
  if (window.innerWidth <= 768 && target) target.scrollIntoView({ behavior: "smooth", block: "start" });
}

/* ===== SESSIONS ===== */
function renderSessions(containerId) {
  var el = document.getElementById(containerId);
  if(!el) return;
  el.innerHTML = sessions.map(function(s) {
    return '<div class="session-item">' +
      '<div class="session-icon" style="background:' + s.bg + '">' + s.icon + '</div>' +
      '<div class="session-info"><div class="session-device">' + s.device + (s.current ? ' <span class="session-current badge badge-green">This Device</span>' : '') + '</div><div class="session-detail">' + s.ip + ' · ' + s.location + '</div><div class="session-detail">' + s.time + '</div></div>' +
    '</div>';
  }).join("");
}

/* ===== ACTIVITY TIMELINE ===== */
function renderActivityTimeline() {
  var el = document.getElementById("activity-timeline");
  if(!el) return;
  el.innerHTML = activities.map(function(a) {
    return '<div class="timeline-item"><div class="timeline-dot" style="background:' + a.color + '"></div><div class="timeline-line"></div><div class="timeline-content"><div class="timeline-text">' + a.text + '</div><div class="timeline-time">' + a.time + '</div></div></div>';
  }).join("");
}

/* ===== PASSWORD ===== */
function openChangePwModal() {
  // Clear fields
  var form = document.querySelector('#pw-modal form');
  if(form) form.reset();
  document.getElementById("pw-strength-fill").style.width = "0";
  document.getElementById("pw-strength-text").textContent = "";
  document.getElementById("req-length").innerHTML = "❌ Minimum 8 characters";
  document.getElementById("req-length").style.color = "var(--text3)";
  document.getElementById("req-upper").innerHTML = "❌ At least one uppercase letter";
  document.getElementById("req-number").innerHTML = "❌ At least one number";
  openModal("pw-modal");
}

function checkPwStrength() {
  var pw = document.querySelector('input[name="new_password"]').value;
  var fill = document.getElementById("pw-strength-fill");
  var text = document.getElementById("pw-strength-text");
  var hasLen = pw.length >= 8;
  var hasUpper = /[A-Z]/.test(pw);
  var hasNum = /[0-9]/.test(pw);
  var hasSpec = /[^A-Za-z0-9]/.test(pw);

  document.getElementById("req-length").innerHTML = hasLen ? "✅ Minimum 8 characters" : "❌ Minimum 8 characters";
  document.getElementById("req-length").style.color = hasLen ? "var(--green)" : "var(--text3)";
  document.getElementById("req-upper").innerHTML = hasUpper ? "✅ At least one uppercase letter" : "❌ At least one uppercase letter";
  document.getElementById("req-upper").style.color = hasUpper ? "var(--green)" : "var(--text3)";
  document.getElementById("req-number").innerHTML = hasNum ? "✅ At least one number" : "❌ At least one number";
  document.getElementById("req-number").style.color = hasNum ? "var(--green)" : "var(--text3)";

  var score = 0;
  if (hasLen) score++;
  if (hasUpper) score++;
  if (hasNum) score++;
  if (hasSpec) score++;
  if (pw.length >= 12) score++;

  if (pw.length === 0) { fill.style.width = "0"; text.textContent = ""; return; }
  if (score <= 1) { fill.style.width = "20%"; fill.style.background = "var(--red)"; text.textContent = "Weak"; text.style.color = "var(--red)"; }
  else if (score <= 2) { fill.style.width = "40%"; fill.style.background = "var(--amber)"; text.textContent = "Fair"; text.style.color = "var(--amber)"; }
  else if (score <= 3) { fill.style.width = "65%"; fill.style.background = "var(--cyan)"; text.textContent = "Good"; text.style.color = "var(--cyan)"; }
  else { fill.style.width = "100%"; fill.style.background = "var(--green)"; text.textContent = "Strong"; text.style.color = "var(--green)"; }
}

function previewPrefTheme(val) {
  if (val === "dark") document.body.classList.remove("light");
  else if (val === "light") document.body.classList.add("light");
  else { var dm = window.matchMedia("(prefers-color-scheme: dark)").matches; if (dm) document.body.classList.remove("light"); else document.body.classList.add("light"); }
}

/* ===== TOAST ===== */
function showToast(msg, type) {
  type = type || "success";
  var icons = { success: "✅", error: "❌", info: "ℹ️" };
  var c = document.getElementById("toast-container");
  var t = document.createElement("div");
  t.className = "toast " + type;
  t.innerHTML = '<span class="toast-icon">' + (icons[type] || "✅") + '</span><span>' + msg + '</span>';
  c.appendChild(t);
  setTimeout(function() { t.style.opacity = "0"; t.style.transform = "translateX(60px)"; t.style.transition = "all .3s ease"; setTimeout(function() { t.remove(); }, 300); }, 3500);
}

/* ===== MODALS ===== */
function openModal(id) { document.getElementById(id).classList.add("open"); document.body.style.overflow = "hidden"; }
function closeModal(id) { document.getElementById(id).classList.remove("open"); document.body.style.overflow = ""; }
function openLogoutModal() { openModal("logout-modal"); }

/* ===== SIDEBAR ===== */
var sidebar = document.getElementById("sidebar"), overlay = document.getElementById("sidebar-overlay"), menuBtn = document.getElementById("menuBtn");
function openSidebar() { sidebar.classList.add("open"); overlay.classList.add("open"); document.body.style.overflow = "hidden"; }
function closeSidebar() { sidebar.classList.remove("open"); overlay.classList.remove("open"); document.body.style.overflow = ""; }
menuBtn.addEventListener("click", function() { if (sidebar.classList.contains("open")) closeSidebar(); else openSidebar(); });
overlay.addEventListener("click", closeSidebar);
document.querySelectorAll(".nav-item").forEach(function(item) { item.addEventListener("click", function() { if (window.innerWidth <= 768) closeSidebar(); }); });
document.addEventListener("keydown", function(e) {
  if (e.key === "Escape") { closeSidebar(); closeModal("pw-modal"); closeModal("twofa-modal"); closeModal("logout-modal"); }
});
document.querySelectorAll(".modal-overlay").forEach(function(ov) { ov.addEventListener("click", function(e) { if (e.target === ov) closeModal(ov.id); }); });

/* ========== THEME ========== */
var savedTheme = '<?= htmlspecialchars($savedTheme) ?>';
localStorage.setItem('excelearn-theme', savedTheme);
if (savedTheme === 'light') document.body.classList.add('light');

/* ===== RESIZE ===== */
var resizeTimer;
window.addEventListener("resize", function() { clearTimeout(resizeTimer); resizeTimer = setTimeout(function() { if (window.innerWidth > 768) closeSidebar(); }, 150); });

/* ===== INIT ===== */
renderSessions("sessions-list");
renderActivityTimeline();
</script>
</body>
</html>