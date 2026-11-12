<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
 $user_id = $_SESSION['user_id'];

$student_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, firstname, middlename, lastname, section_id, profile_pic FROM tblstudents WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("
    SELECT 
        s.id,
        s.student_id,
        s.firstname,
        s.middlename,
        s.lastname,
        s.suffix,
        s.age,
        s.gender,
        s.contact_number,
        s.address,
        s.birthdate,
        s.profile_pic,
        s.password,
        s.status,
        s.section_id,
        s.lrn,
        sec.section AS section_name,
        sec.grade_level_id,
        gl.grade_level
    FROM tblstudents s
    LEFT JOIN tblsections sec ON sec.section_id = s.section_id
    LEFT JOIN tblgrade_levels gl ON gl.grade_id = sec.grade_level_id
    WHERE s.id = ?
");
 $stmt->bind_param("i", $user_id);
 $stmt->execute();
 $user = $stmt->get_result()->fetch_assoc();
 $stmt->close();

if (!$user) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// ── Assign variables from DB ──
 $first_name      = htmlspecialchars($user['firstname'] ?? '');
 $last_name       = htmlspecialchars($user['lastname'] ?? '');
  $middle = $user['middlename'] ?? '';
  $suffix = $user['suffix'] ?? '';

  $fullname = trim($first_name . ' ' . $middle . ' ' . $last_name . ' ' . $suffix);

 $phone           = htmlspecialchars($user['contact_number'] ?? '');
 $gender          = htmlspecialchars($user['gender'] ?? '');
 $address         = htmlspecialchars($user['address'] ?? '');

// Academic Info from joins
// Academic Info from joins
$grade_level = htmlspecialchars($user['grade_level'] ?? '');
$section     = htmlspecialchars($user['section_name'] ?? '');
$strand      = '';

// Additional fields from DB
 $stud_id         = htmlspecialchars($user['student_id'] ?? ''); 
 $lrn             = htmlspecialchars($user['lrn'] ?? ''); 
 // Fetch enrollment date and adviser from tbl_teachers_students + tblusers
  $enrollment_date = '';
  $adviser = '';
  $ts_row = $conn->query("
      SELECT ts.created_at, u.firstname, u.lastname
      FROM tbl_teachers_students ts
      JOIN tblusers u ON u.id = ts.teacher_id
      WHERE ts.student_id = $user_id
      ORDER BY ts.created_at ASC
      LIMIT 1
  ")->fetch_assoc();
  if ($ts_row) {
      $enrollment_date = $ts_row['created_at'];
      $adviser = htmlspecialchars(trim($ts_row['firstname'] . ' ' . $ts_row['lastname']));
  }

// Initials logic
 $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
if (strlen(trim($initials)) < 2) $initials = '??';

// Date Formatting
 $birthdate_display = ''; 
 $birthdate_input   = "";
 if (!empty($user['birthdate'])) {
    $d = date_create($user['birthdate']);
    $birthdate_display = $d->format('F j, Y');
    $birthdate_input = $user['birthdate'];
 }

 $enrollment_display = '';
if (!empty($enrollment_date)) {
    $d = date_create($enrollment_date);
    if ($d) $enrollment_display = $d->format('F j, Y');
}

$guardian_display = !empty($guardian) ? $guardian : '—';
$guardian_display_no = !empty($guardian_contact) ? $guardian_contact : '—';

// Profile picture path
 $profile_pic_path = '';
 $profile_pic_db   = $user['profile_pic'] ?? '';
if (!empty($profile_pic_db)) {
    $possible_paths = [
        '../uploads/profile_pic/students/' . $profile_pic_db,
        '../' . $profile_pic_db,
        $profile_pic_db
    ];
    foreach ($possible_paths as $p) {
        if (file_exists($p)) {
            $profile_pic_path = $p;
            break;
        }
    }
}

// ── Flash messages ──
 $success_msg = $_SESSION['profile_success'] ?? '';
 $error_msg   = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);

// ════════════════════════════════════════
// HANDLE POST REQUESTS
// ════════════════════════════════════════

// ── Profile Picture Upload ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_pic'])) {
    if (isset($_FILES['profile_pic_file']) && $_FILES['profile_pic_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($finfo, $_FILES['profile_pic_file']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($detected_type, $allowed_types)) {
            $_SESSION['profile_error'] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
        } elseif ($_FILES['profile_pic_file']['size'] > 2 * 1024 * 1024) {
            $_SESSION['profile_error'] = "Image must be under 2MB.";
        } else {
            $upload_dir = '../uploads/profile_pic/students/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            $ext = $ext_map[$detected_type] ?? 'jpg';
            $new_name = 'profile_' . $user_id . '_' . time() . '.' . $ext;
            $target_path = $upload_dir . $new_name;

            if (move_uploaded_file($_FILES['profile_pic_file']['tmp_name'], $target_path)) {
                // Delete old picture
                if (!empty($profile_pic_db)) {
                    $old_paths = [
                        '../uploads/profile_pic/students/' . $profile_pic_db,
                        '../' . $profile_pic_db
                    ];
                    foreach ($old_paths as $op) {
                        if (file_exists($op)) unlink($op);
                    }
                }

                // Update tblstudents
                $stmt = $conn->prepare("UPDATE tblstudents SET profile_pic = ? WHERE id = ?");
                $stmt->bind_param("si", $new_name, $user_id);
                $stmt->execute();
                $stmt->close();

                $_SESSION['profile_success'] = "Profile picture updated!";
            } else {
                $_SESSION['profile_error'] = "Failed to save image. Check folder permissions.";
            }
        }
    } else {
        $_SESSION['profile_error'] = "Please select an image file.";
    }
    header("Location: student_profile.php");
    exit();
}

// ── Update Profile (UPDATED LOGIC FOR NEW COLUMNS) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fn   = trim($_POST['first_name'] ?? '');
    $mn   = trim($_POST['middle_name'] ?? '');
    $ln   = trim($_POST['last_name'] ?? '');
    $sfx  = trim($_POST['suffix'] ?? '');
    $sid  = trim($_POST['student_id'] ?? '');
    $ph   = trim($_POST['phone'] ?? '');
    $gd   = trim($_POST['gender'] ?? '');
    $addr = trim($_POST['address'] ?? '');
    $bday = trim($_POST['birthdate'] ?? '');

    if (empty($fn) || empty($ln)) {
        $_SESSION['profile_error'] = "First name and last name are required.";
    } else {
        $sql = "UPDATE tblstudents SET firstname=?, middlename=?, lastname=?, suffix=?, student_id=?, contact_number=?, gender=?, address=?, birthdate=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", $fn, $mn, $ln, $sfx, $sid, $ph, $gd, $addr, $bday, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['profile_success'] = "Profile updated successfully!";
    }
    header("Location: student_profile.php");
    exit();
}

// ── Update Academic Info (FIXED with FK validation) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_academic'])) {
    $lrn        = trim($_POST['lrn'] ?? '');
    $section_id = intval($_POST['section_id'] ?? 0);

    $stmt = $conn->prepare("UPDATE tblstudents SET lrn=?, section_id=? WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("sii", $lrn, $section_id, $user_id);
        if ($stmt->execute()) {
            $_SESSION['profile_success'] = "Academic info updated!";
        } else {
            $_SESSION['profile_error'] = "Update failed: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: student_profile.php");
    exit();
}

// ── Change Password ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass  = $_POST['current_password'] ?? '';
    $new_pass      = $_POST['new_password'] ?? '';
    $confirm_pass  = $_POST['confirm_password'] ?? '';

    if (empty($current_pass)) {
        $_SESSION['profile_error'] = "Please enter your current password.";
    } elseif (strlen($new_pass) < 8) {
        $_SESSION['profile_error'] = "New password must be at least 8 characters.";
    } elseif ($new_pass !== $confirm_pass) {
        $_SESSION['profile_error'] = "New passwords do not match.";
    } elseif (!password_verify($current_pass, $user['password'])) {
        $_SESSION['profile_error'] = "Current password is incorrect.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        // Update tblstudents
        $stmt = $conn->prepare("UPDATE tblstudents SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['profile_success'] = "Password changed successfully!";
    }
    header("Location: student_profile.php");
    exit();
}

// ── Deactivate Account ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_account'])) {
    // Update tblstudents status
    $stmt = $conn->prepare("UPDATE tblstudents SET status = 'inactive' WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// ── Stats ──
$stat_avg     = 0;
$stat_certs   = 0;
// Profile completion — based on Personal Info + Academic Info fields shown in the cards
$completion_fields = [
    // Personal Information card (8 fields)
    'First Name'     => $user['firstname'],
    'Middle Name'    => $user['middlename'],
    'Last Name'      => $user['lastname'],
    'Suffix'         => $user['suffix'],
    'Student ID'     => $user['student_id'],
    'Phone'          => $user['contact_number'],
    'Birthdate'      => $user['birthdate'],
    'Gender'         => $user['gender'],
    'Address'        => $user['address'],
    // Academic Information card (5 fields)
    'LRN'            => $user['lrn'],
    'Grade Level'    => $user['grade_level'],
    'Section'        => $user['section_name'],
    'Adviser'        => $adviser,
    'Enrollment Date'=> $enrollment_date,
    // Profile picture
    'Profile Picture'=> $user['profile_pic'],
];
$filled = count(array_filter($completion_fields, fn($v) => !empty(trim($v ?? ''))));
$stat_progress = round(($filled / count($completion_fields)) * 100);

// Count enrolled subjects via teacher_subjects linked to student's section
$stat_courses = 0;
$subj_result = $conn->query("
    SELECT COUNT(DISTINCT ts.subject_id) AS cnt
    FROM tbl_teacher_subjects ts
    JOIN tbl_teachers_students tts ON tts.teacher_id = ts.teacher_id
    WHERE tts.student_id = $user_id
");
if ($subj_result && $srow = $subj_result->fetch_assoc()) {
    $stat_courses = (int)$srow['cnt'];
}

// Average grade: (SUM of scores / SUM of question_count) * 100
$avg_result = $conn->query("
    SELECT 
        SUM(r.score) AS total_score,
        SUM(q.question_count) AS total_items
    FROM tblquiz_results r
    JOIN tblquizzes q ON q.quiz_id = r.quizzes_id
    WHERE r.student_id = $user_id
      AND q.question_count > 0
");
if ($avg_result && $arow = $avg_result->fetch_assoc()) {
    if ($arow['total_items'] > 0) {
        $stat_avg = round(($arow['total_score'] / $arow['total_items']) * 100);
    }
}

$cert_result = $conn->query("SELECT COUNT(*) AS cnt FROM tblquiz_results WHERE student_id = $user_id");
if ($cert_result && $crow = $cert_result->fetch_assoc()) {
    $stat_certs = (int)$crow['cnt'];
}

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
<link rel="stylesheet" href="../assets/css/student/student_profile.css">
<style>
.profile-stats-row {
  display: grid !important;
  grid-template-columns: repeat(4, 1fr) !important;
  gap: 12px !important;
}
@media (max-width: 768px) {
  .profile-stat-card {
    padding: 10px 8px !important;
    gap: 4px !important;
  }
  .profile-stat-icon {
    font-size: 1.1rem !important;
  }
  .profile-stat-val {
    font-size: 1rem !important;
    line-height: 1.2 !important;
  }
  .profile-stat-lbl {
    font-size: 0.6rem !important;
    line-height: 1.3 !important;
    white-space: normal !important;
    word-break: break-word !important;
  }
  .profile-stat-bar {
    height: 3px !important;
    margin-top: 4px !important;
  }
}

@media (max-width: 400px) {
  .profile-stat-card {
    padding: 8px 6px !important;
  }
  .profile-stat-val {
    font-size: 0.9rem !important;
  }
  .profile-stat-lbl {
    font-size: 0.55rem !important;
  }
}
@media (max-width: 768px) {
  .profile-grid {
    grid-template-columns: 1fr !important;
  }
  .info-grid {
    grid-template-columns: 1fr 1fr !important;
    gap: 10px 12px !important;
  }
  .info-item {
    display: flex !important;
    flex-direction: column !important;
    gap: 2px !important;
  }
  .info-item.full {
    grid-column: 1 / -1 !important;
  }
  .info-label {
    font-size: 0.65rem !important;
    color: var(--text3) !important;
    text-transform: uppercase !important;
    letter-spacing: 0.03em !important;
  }
  .info-value {
    font-size: 0.8rem !important;
    font-weight: 500 !important;
    word-break: break-word !important;
  }
}

@media (max-width: 400px) {
  .info-grid {
    grid-template-columns: 1fr 1fr !important;
    gap: 8px !important;
  }
  .info-label {
    font-size: 0.6rem !important;
  }
  .info-value {
    font-size: 0.75rem !important;
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
    <div class="nav-section-label">Learning</div>
    <a class="nav-item" href="student_courses.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      My Subjects
      <span class="nav-badge"><?php echo $stat_courses; ?></span>
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
      Virtual Class
    </a>
    <div class="nav-section-label" style="margin-top:16px">Progress</div>
    <a class="nav-item" href="student_grades.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Grades
    </a>
    <div class="nav-section-label" style="margin-top:16px">Account</div>
    <a class="nav-item active" href="student_profile.php">
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
    <div class="user-card">
      <?php if ($profile_pic_path): ?>
        <div class="user-avatar" style="background:linear-gradient(135deg,var(--cyan),var(--accent));overflow:hidden;padding:0">
          <img src="<?php echo $profile_pic_path; ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
        </div>
      <?php else: ?>
        <div class="user-avatar" style="background:linear-gradient(135deg,var(--cyan),var(--accent))"><?php echo $initials; ?></div>
      <?php endif; ?>
      <div class="user-info">
        <p><?php echo $fullname; ?></p>
        <span><?php echo $grade_level ? $grade_level . ' — ' . $section : 'Student'; ?></span>
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
    <!-- Profile Hero -->
    <div class="profile-hero">
      <div class="profile-banner">
        <div class="profile-banner-bg"></div>
        <div class="profile-banner-pattern"></div>
        <div class="profile-banner-shape1"></div>
        <div class="profile-banner-shape2"></div>
      </div>
      <div class="profile-hero-content">
        <!-- Profile picture: click to upload -->
        <form method="POST" enctype="multipart/form-data" id="picUploadForm" style="position:relative;flex-shrink:0;">
          <input type="file" name="profile_pic_file" id="picFileInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
          <input type="hidden" name="upload_pic" value="1">
          <div class="profile-avatar-wrap" onclick="document.getElementById('picFileInput').click()" style="cursor:pointer" title="Click to change photo">
            <?php if ($profile_pic_path): ?>
              <div class="profile-avatar" id="profileAvatarDisplay" style="padding:0;overflow:hidden;border-radius:50%">
                <img src="<?php echo $profile_pic_path; ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
              </div>
            <?php else: ?>
              <div class="profile-avatar" id="profileAvatarDisplay"><?php echo $initials; ?></div>
            <?php endif; ?>
            <div class="profile-avatar-badge" style="background:var(--accent);font-size:14px">📷</div>
          </div>
        </form>
        <div class="profile-hero-info">
          <div class="profile-hero-name" id="profileNameDisplay"><?php echo $fullname; ?></div>
          <div class="profile-hero-sub">
            <?php if ($grade_level): ?><span class="badge badge-cyan"><?php echo $grade_level; ?></span><span class="sep">·</span><span>Section <?php echo $section; ?></span><?php endif; ?>
            <?php if ($strand): ?><span class="sep">·</span><span><?php echo $strand; ?></span><?php endif; ?>
          </div>
        </div>
        <div class="profile-hero-actions">
          <button class="btn btn-primary btn-sm" onclick="openModal('editModal')">✏️ Edit Profile</button>
          <button class="btn btn-ghost btn-sm" onclick="openModal('passwordModal')">🔒 Change Password</button>
        </div>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="profile-stats-row">
      <div class="profile-stat-card">
        <div class="profile-stat-icon">📚</div>
        <div class="profile-stat-val" style="color:var(--accent)"><?php echo $stat_courses; ?></div>
        <div class="profile-stat-lbl">Enrolled Subjects</div>
        <div class="profile-stat-bar"><div class="profile-stat-bar-fill" style="width:<?php echo min(100, $stat_courses * 25); ?>%;background:var(--accent)"></div></div>
      </div>
      <div class="profile-stat-card">
        <div class="profile-stat-icon">📊</div>
        <div class="profile-stat-val" style="color:var(--green)"><?php echo $stat_avg; ?>%</div>
        <div class="profile-stat-lbl">Average Grade</div>
        <div class="profile-stat-bar"><div class="profile-stat-bar-fill" style="width:<?php echo min(100, $stat_avg); ?>%;background:linear-gradient(90deg,var(--green),#1aaa6e)"></div></div>
      </div>
      <div class="profile-stat-card">
        <div class="profile-stat-icon">📝</div>
        <div class="profile-stat-val" style="color:var(--amber)"><?php echo $stat_certs; ?></div>
        <div class="profile-stat-lbl">Total Quizzes Taken</div>
        <div class="profile-stat-bar"><div class="profile-stat-bar-fill" style="width:<?php echo min(100, $stat_certs * 10); ?>%;background:var(--amber)"></div></div>
      </div>
      <div class="profile-stat-card">
        <div class="profile-stat-icon">👤</div>
        <div class="profile-stat-val" style="color:var(--purple)"><?php echo $stat_progress; ?>%</div>
        <div class="profile-stat-lbl">Profile Completion</div>
        <div class="profile-stat-bar"><div class="profile-stat-bar-fill" style="width:<?php echo $stat_progress; ?>%;background:linear-gradient(90deg,var(--purple),#8b5cf6)"></div></div>
      </div>
    </div>

    <!-- Two Column Grid -->
    <div class="profile-grid">
      <!-- Personal Information -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">📋 Personal Information</div>
          <button class="btn btn-ghost btn-sm" onclick="openModal('editModal')">Edit</button>
        </div>
        <div class="info-grid">
          <div class="info-item"><div class="info-label">First Name</div><div class="info-value"><?php echo $first_name ?: 'N/A'; ?></div></div>
          <div class="info-item"><div class="info-label">Middle Name</div><div class="info-value"><?php echo htmlspecialchars($middle) ?: 'N/A'; ?></div></div>
          <div class="info-item"><div class="info-label">Last Name</div><div class="info-value"><?php echo $last_name ?: 'N/A'; ?></div></div>
          <div class="info-item"><div class="info-label">Suffix</div><div class="info-value"><?php echo htmlspecialchars($suffix) ?: 'N/A'; ?></div></div>
          <!-- REPLACED EMAIL WITH STUDENT ID AS REQUESTED -->
          <div class="info-item"><div class="info-label">Student ID</div><div class="info-value"><?php echo $stud_id ?: 'N/A'; ?></div></div>
          <div class="info-item"><div class="info-label">Phone</div><div class="info-value"><?php echo $phone ?: 'N/A'; ?></div></div>
          <div class="info-item"><div class="info-label">Birthdate</div><div class="info-value"><?php echo $birthdate_display ?: 'N/A'; ?></div></div>
          <div class="info-item"><div class="info-label">Gender</div><div class="info-value"><?php echo $gender ?: 'N/A'; ?></div></div>
          <div class="info-item full"><div class="info-label">Address</div><div class="info-value"><?php echo $address ?: 'N/A'; ?></div></div>
        </div>
      </div>

      <!-- Academic Information -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">🎓 Academic Information</div>
          <button class="btn btn-ghost btn-sm" onclick="openModal('academicModal')">Edit</button>
        </div>
        <div class="info-grid">
          <div class="info-item"><div class="info-label">Learner Reference No.</div><div class="info-value"><?php echo $lrn ?: '—'; ?></div></div>
          <div class="info-item"><div class="info-label">Grade Level</div><div class="info-value"><?php echo $grade_level ?: '—'; ?></div></div>
          <div class="info-item"><div class="info-label">Section</div><div class="info-value"><?php echo $section ?: '—'; ?></div></div>
          <div class="info-item"><div class="info-label">Adviser</div><div class="info-value"><?php echo $adviser ?: '—'; ?></div></div>
          <div class="info-item full"><div class="info-label">Enrollment Date</div><div class="info-value"><?php echo $enrollment_display ?: '—'; ?></div></div>
        </div>
      </div>

    <!-- Danger Zone -->
    <div class="card" style="margin-bottom:24px;border-color:var(--red)20">
      <div class="card-header">
        <div class="card-title" style="color:var(--red)">⚠️ Danger Zone</div>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:8px 0">
        <div>
          <div style="font-weight:600;font-size:14px;margin-bottom:2px">Deactivate Account</div>
          <div style="font-size:13px;color:var(--text3)">This will disable your account. Contact an administrator to reactivate.</div>
        </div>
        <form method="POST" onsubmit="return confirm('Are you sure you want to deactivate your account? This action cannot be easily undone.')">
          <input type="hidden" name="deactivate_account" value="1">
          <button type="submit" class="btn btn-danger btn-sm">Deactivate Account</button>
        </form>
      </div>
    </div>

    <div style="text-align:center;padding:20px 0 8px;font-size:12px;color:var(--text3)">
     ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © JBH: 2025–2026
    </div>
  </div>
</main>
</div>

<!-- Edit Profile Modal -->
<div class="modal-backdrop" id="editModal" onclick="closeModalOutside(event,'editModal')">
  <div class="modal" style="position:relative">
    <form method="POST" action="student_profile.php">
      <div class="modal-header-banner" style="background:linear-gradient(135deg,#1e3c72,#2a5298,#f7971e)">
        <div class="banner-overlay"></div>
        <!--<button type="button" class="modal-close" onclick="closeModal('editModal')">✕</button>-->
      </div>
      <div class="modal-body">
        <div class="modal-title">Edit Profile</div>
        <div class="modal-subtitle">Update your personal information below.</div>
        <input type="hidden" name="update_profile" value="1">
          <div class="modal-form-grid">
            <div>
              <label>First Name <span style="color:var(--red)">*</span></label>
              <input type="text" name="first_name" value="<?php echo $first_name; ?>" required>
            </div>
            <div>
              <label>Last Name <span style="color:var(--red)">*</span></label>
              <input type="text" name="last_name" value="<?php echo $last_name; ?>" required>
            </div>
            <div>
              <label>Middle Name</label>
              <input type="text" name="middle_name" value="<?php echo htmlspecialchars($middle); ?>">
            </div>
            <div>
              <label>Suffix</label>
              <input type="text" name="suffix" value="<?php echo htmlspecialchars($suffix); ?>" placeholder="e.g. Jr., Sr., III">
            </div>
            <div>
              <label>Student ID</label>
              <input type="text" name="student_id" value="<?php echo $stud_id; ?>">
            </div>
            <div>
              <label>Phone Number</label>
              <input type="tel" name="phone" value="<?php echo $phone; ?>">
            </div>
            <div>
              <label>Birthdate</label>
              <input type="date" name="birthdate" value="<?php echo $birthdate_input; ?>">
            </div>
            <div>
              <label>Gender</label>
              <select name="gender">
                <option value="">— Select —</option>
                <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Other" <?php echo $gender === 'Other' ? 'selected' : ''; ?>>Other</option>
                <option value="Prefer not to say" <?php echo $gender === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
              </select>
            </div>
            <div class="full">
              <label>Address</label>
              <input type="text" name="address" value="<?php echo $address; ?>">
            </div>
          </div>
        <div class="modal-actions-row">
          <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">💾 Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
<!-- Edit Academic Info Modal -->
<div class="modal-backdrop" id="academicModal" onclick="closeModalOutside(event,'academicModal')">
  <div class="modal" style="position:relative">
    <form method="POST" action="student_profile.php">
      
      <div class="modal-header-banner" style="background:linear-gradient(135deg,#0f2027,#203a43,#2c5364)">
        <div class="banner-overlay"></div>
        <!--<button type="button" class="modal-close" onclick="closeModal('academicModal')">✕</button>-->
      </div>

      <div class="modal-body">
        <div class="modal-title">Edit Academic Information</div>
        <div class="modal-subtitle">Update your academic details below.</div>

        <!-- IMPORTANT -->
        <input type="hidden" name="update_academic" value="1">

        <div class="modal-form-grid">

          <div>
            <label>Learner Reference No.</label>
            <input type="text" name="lrn" value="<?php echo $lrn; ?>">
          </div>

          <div>
            <label>Adviser</label>
            <input type="text" name="adviser" value="<?php echo $adviser; ?>">
          </div>

          <!-- OPTIONAL: Grade + Section + Strand -->
        <div>
          <label>Grade Level</label>
          <select name="grade_level_id" id="gradeSelect">
            <option value="">— Select Grade —</option>
            <?php
            $grades = $conn->query("SELECT grade_id, grade_level FROM tblgrade_levels ORDER BY grade_level");
            while ($g = $grades->fetch_assoc()): ?>
              <option value="<?php echo $g['grade_id']; ?>"
                <?php echo ($g['grade_id'] == ($user['grade_level_id'] ?? 0)) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($g['grade_level']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div>
          <label>Section</label>
          <select name="section_id" id="sectionSelect">
            <option value="">— Select Section —</option>
            <?php
            $sections = $conn->query("SELECT section_id, grade_level_id, section FROM tblsections ORDER BY section");
            while ($sec = $sections->fetch_assoc()): ?>
              <option value="<?php echo $sec['section_id']; ?>"
                data-grade="<?php echo $sec['grade_level_id']; ?>"
                <?php echo ($sec['section_id'] == ($user['section_id'] ?? 0)) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($sec['section']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        </div>
        <div class="modal-actions-row">
          <button type="button" class="btn btn-ghost" onclick="closeModal('academicModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">💾 Save Academic</button>
        </div>

      </div>
    </form>
  </div>
</div>
<!-- Change Password Modal -->
<div class="modal-backdrop" id="passwordModal" onclick="closeModalOutside(event,'passwordModal')">
  <div class="modal" style="position:relative;max-width:460px">
    <form method="POST" action="student_profile.php">
      <div class="modal-header-banner" style="background:linear-gradient(135deg,#2a5298,#1e3c72)">
        <div class="banner-overlay"></div>
        <button type="button" class="modal-close" onclick="closeModal('passwordModal')">✕</button>
      </div>
      <div class="modal-body">
        <div class="modal-title">Change Password</div>
        <div class="modal-subtitle">Ensure your account stays secure by using a strong password.</div>
        <input type="hidden" name="change_password" value="1">
        <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:20px">
          <div>
            <label>Current Password</label>
            <input type="password" name="current_password" placeholder="Enter current password" required>
          </div>
          <div>
            <label>New Password</label>
            <input type="password" name="new_password" id="newPass" placeholder="Enter new password (min. 8 characters)" required minlength="8" oninput="checkPasswordStrength(this.value)">
            <div id="passStrength" style="margin-top:6px;font-size:12px;color:var(--text3)"></div>
          </div>
          <div>
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Re-enter new password" required minlength="8">
          </div>
        </div>
        <div class="modal-actions-row">
          <button type="button" class="btn btn-ghost" onclick="closeModal('passwordModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">🔒 Update Password</button>
        </div>
      </div>
    </form>
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
/* Auto-submit profile pic on file select */
document.getElementById('picFileInput').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        document.getElementById('picUploadForm').submit();
    }
});

/* Modal open/close — UI only, no data handling */
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}
function closeModalOutside(e, id) {
    if (e.target === document.getElementById(id)) closeModal(id);
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        ['editModal', 'passwordModal'].forEach(function(id) {
            if (document.getElementById(id).classList.contains('open')) closeModal(id);
        });
    }
});

/* Password strength indicator — UI only */
function checkPasswordStrength(val) {
    var el = document.getElementById('passStrength');
    if (!val) { el.innerHTML = ''; return; }
    var score = 0;
    if (val.length >= 8) score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    var labels = ['Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
    var colors = ['var(--red)', 'var(--red)', 'var(--amber)', 'var(--green)', 'var(--green)'];
    if (score > 0) {
        el.innerHTML = '<span style="color:' + colors[score - 1] + ';font-weight:600">' + labels[score - 1] + '</span>';
    } else {
        el.innerHTML = '<span style="color:var(--red);font-weight:600">Too Short</span>';
    }
}

/* Sidebar toggle — UI only */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebar-overlay').classList.toggle('open');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebar-overlay').classList.remove('open');
}

/* Theme toggle — UI only */
function applyTheme(theme) {
  var isLight = theme === 'light';
  document.body.classList.toggle('light', isLight);
  var ti = document.getElementById('theme-icon');
  var tt = document.getElementById('theme-text');
  if(ti) ti.textContent = isLight ? '☀️' : '🌙';
  if(tt) tt.textContent = isLight ? 'Light Mode' : 'Dark Mode';
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

/* Toast notification — used for PHP flash messages */
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
    }, 4000);
}

/* Show PHP flash messages as toasts on page load */
<?php if ($success_msg): ?>
showToast(<?php echo json_encode($success_msg); ?>, 'success');
<?php endif; ?>
<?php if ($error_msg): ?>
showToast(<?php echo json_encode($error_msg); ?>, 'error');
<?php endif; ?>

document.getElementById('gradeSelect').addEventListener('change', function () {
    let gradeId = this.value;
    let sectionSelect = document.getElementById('sectionSelect');

    for (let option of sectionSelect.options) {
        let match = option.getAttribute('data-grade') == gradeId || option.value === "";
        option.style.display = match ? "" : "none";
    }

    sectionSelect.value = "";
});

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