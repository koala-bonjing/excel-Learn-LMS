<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

 $teacher_id = $_SESSION['user_id'];

/* ── Helper: "X ago" (defined early so it's available in HTML) ── */
function formatTimeAgo($datetime_str) {
    if (empty($datetime_str)) return 'unknown';
    $now = new DateTime();
    $then = new DateTime($datetime_str);
    $diff = $now->diff($then);
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/* Helper for empty fields */
function displayField($val) {
    return !empty($val) ? $val : '—';
}

/* ── FETCH LOGGED-IN TEACHER INFO ── */
 $stmt = $conn->prepare("SELECT id, profile_pic, email, password, firstname, middlename, lastname, age, gender, contact_number, address, is_verified, role, status, updated_at, created_at FROM tblusers WHERE id = ?");
 $stmt->bind_param("i", $teacher_id);
 $stmt->execute();
 $user = $stmt->get_result()->fetch_assoc();
 $stmt->close();

if (!$user) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

/* ── Build display values ── */
 $firstname      = htmlspecialchars($user['firstname'] ?? '');
 $middlename     = htmlspecialchars($user['middlename'] ?? '');
 $lastname       = htmlspecialchars($user['lastname'] ?? '');
 $display_name   = trim("$firstname " . ($middlename ? "$middlename " : '') . "$lastname");
 $initials       = strtoupper(substr($user['firstname'] ?? '', 0, 1) . substr($user['lastname'] ?? '', 0, 1));
if (strlen(trim($initials)) < 2) $initials = 'U';

 $age            = htmlspecialchars($user['age'] ?? '');
 $email          = htmlspecialchars($user['email'] ?? '');
 $gender         = htmlspecialchars($user['gender'] ?? '');
 $contact_number = htmlspecialchars($user['contact_number'] ?? '');
 $address        = htmlspecialchars($user['address'] ?? '');
 $role           = htmlspecialchars(ucfirst($user['role'] ?? 'Teacher'));
 $status         = $user['status'] ?? 'active';
 $is_verified    = (int)($user['is_verified'] ?? 0);
 $created_at     = $user['created_at'] ?? '';
 $updated_at     = $user['updated_at'] ?? '';

/* ── Profile Completion Rate (based on filled fields in tblusers) ── */
 $completion_fields = [
    'firstname'       => $user['firstname'] ?? '',
    'lastname'        => $user['lastname'] ?? '',
    'email'           => $user['email'] ?? '',
    'middlename'      => $user['middlename'] ?? '',
    'age'             => $user['age'] ?? '',
    'gender'          => $user['gender'] ?? '',
    'contact_number'  => $user['contact_number'] ?? '',
    'address'         => $user['address'] ?? '',
    'profile_pic'     => $user['profile_pic'] ?? '',
];
 $filled_count = 0;
 $total_fields = count($completion_fields);
foreach ($completion_fields as $val) {
    if (!empty($val)) $filled_count++;
}
 $completion_rate = $total_fields > 0 ? round(($filled_count / $total_fields) * 100) : 0;

/* Profile pic path */
 $profile_pic_path = '';
 $profile_pic_db   = $user['profile_pic'] ?? '';
if (!empty($profile_pic_db)) {
    $possible = [
        '../uploads/profile_pic/teachers/' . $profile_pic_db,
        '../uploads/profile_pic/' . $profile_pic_db,
        '../' . $profile_pic_db
    ];
    foreach ($possible as $p) {
        if (file_exists($p)) { $profile_pic_path = $p; break; }
    }
}

/* Avatar HTML for display */
 $avatar_html = '';
if (!empty($profile_pic_path)) {
    $avatar_html = '<img src="' . $profile_pic_path . '" alt="Profile picture" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">';
} else {
    $avatar_html = htmlspecialchars($initials);
}

 $sidebar_avatar_html = '';
if (!empty($profile_pic_path)) {
    $sidebar_avatar_html = '<img src="' . $profile_pic_path . '" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">';
} else {
    $sidebar_avatar_html = htmlspecialchars($initials);
}

 $date_joined = '';
if (!empty($created_at)) {
    $date_joined = date_create($created_at)->format('F j, Y');
}

 $success_msg = $_SESSION['profile_success'] ?? '';
 $error_msg   = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fn   = trim($_POST['edit_firstname'] ?? '');
    $mn   = trim($_POST['edit_middlename'] ?? '');
    $ln   = trim($_POST['edit_lastname'] ?? '');
    $em   = trim($_POST['edit_email'] ?? '');
    $gd   = trim($_POST['edit_gender'] ?? '');
    $ph   = trim($_POST['edit_contact'] ?? '');
    $addr = trim($_POST['edit_address'] ?? '');
    $ag   = trim($_POST['edit_age'] ?? '');

    if (!empty($ag) && (!is_numeric($ag) || $ag < 1 || $ag > 120)) {
        $_SESSION['profile_error'] = "Please enter a valid age.";
    } elseif (empty($fn) || empty($ln)) {
        $_SESSION['profile_error'] = "First name and last name are required.";
    } elseif (empty($em) || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['profile_error'] = "Please enter a valid email address.";
    } else {
        /* Handle optional profile pic upload */
        if (isset($_FILES['profile_pic_file']) && $_FILES['profile_pic_file']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected = finfo_file($finfo, $_FILES['profile_pic_file']['tmp_name']);
            finfo_close($finfo);

            if (in_array($detected, $allowed) && $_FILES['profile_pic_file']['size'] <= 2 * 1024 * 1024) {
                $upload_dir = '../uploads/profile_pic/teachers/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $ext_map = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
                $ext = $ext_map[$detected] ?? 'jpg';
                $new_name = 'teacher_' . $teacher_id . '_' . time() . '.' . $ext;
                $target = $upload_dir . $new_name;

                if (move_uploaded_file($_FILES['profile_pic_file']['tmp_name'], $target)) {
                    if (!empty($profile_pic_db)) {
                        foreach (['../uploads/profile_pic/teachers/','../uploads/profile_pic/','../'] as $dir) {
                            $old = $dir . $profile_pic_db;
                            if (file_exists($old)) unlink($old);
                        }
                    }
                    $stmt2 = $conn->prepare("UPDATE tblusers SET profile_pic = ? WHERE id = ?");
                    $stmt2->bind_param("si", $new_name, $teacher_id);
                    $stmt2->execute();
                    $stmt2->close();
                }
            }
        }

        $stmt = $conn->prepare("UPDATE tblusers SET firstname=?, middlename=?, lastname=?, email=?, gender=?, contact_number=?, address=?, age=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("sssssssii", $fn, $mn, $ln, $em, $gd, $ph, $addr, $ag, $teacher_id);
        if ($stmt->execute()) {
            $_SESSION['profile_success'] = "Profile updated successfully!";
        } else {
            $_SESSION['profile_error'] = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: teacher_profile.php");
    exit();
}

/* ── Change Password ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new_pwd = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current)) {
        $_SESSION['profile_error'] = "Please enter your current password.";
    } elseif (strlen($new_pwd) < 8) {
        $_SESSION['profile_error'] = "New password must be at least 8 characters.";
    } elseif ($new_pwd !== $confirm) {
        $_SESSION['profile_error'] = "New passwords do not match.";
    } elseif (!password_verify($current, $user['password'])) {
        $_SESSION['profile_error'] = "Current password is incorrect.";
    } else {
        $hashed = password_hash($new_pwd, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE tblusers SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $hashed, $teacher_id);
        if ($stmt->execute()) {
            $_SESSION['profile_success'] = "Password changed successfully!";
        } else {
            $_SESSION['profile_error'] = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: teacher_profile.php");
    exit();
}

/* ── Toggle Email Verification (is_verified) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_verified'])) {
    $new_val = ($is_verified === 1) ? 0 : 1;
    $stmt = $conn->prepare("UPDATE tblusers SET is_verified = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $new_val, $teacher_id);
    if ($stmt->execute()) {
        $_SESSION['profile_success'] = $new_val === 1
            ? "Email has been marked as verified."
            : "Email verification has been revoked.";
    } else {
        $_SESSION['profile_error'] = "Error: " . $stmt->error;
    }
    $stmt->close();
    header("Location: teacher_profile.php");
    exit();
}

/* ── Regenerate Session (Revoke Other Sessions) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate_session'])) {
    session_regenerate_id(true);
    $_SESSION['profile_success'] = "All other sessions have been revoked. You are now on a new session.";
    header("Location: teacher_profile.php");
    exit();
}

/* ── Quick Stats from DB ── */
 $total_students = 0;
 $active_courses = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT student_id) AS cnt FROM tbl_teachers_students WHERE teacher_subject_id IN (SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?)");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $total_students = (int)($r['cnt'] ?? 0);
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tbl_teacher_subjects WHERE teacher_id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $active_courses = (int)($r['cnt'] ?? 0);
    $stmt->close();
    
    $badge_lessons = (int)$conn->query("
        SELECT COUNT(*) FROM tbl_teacher_lessons l
        JOIN tbl_teacher_subjects s ON l.teacher_subject_id = s.teacher_subject_id
        WHERE s.teacher_id = $teacher_id
    ")->fetch_row()[0];
    
    $badge_quizzes = (int)$conn->query("
        SELECT COUNT(*) FROM tblquizzes q
        JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id
        JOIN tbl_teacher_subjects s ON l.teacher_subject_id = s.teacher_subject_id
        WHERE s.teacher_id = $teacher_id
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
} catch (Exception $e) {
    /* Silently fail if table/column doesn't exist yet */
}

$assigned_courses = [];
try {
    $stmt = $conn->prepare("
        SELECT ts.teacher_subject_id, subj.subject AS subject_name
        FROM tbl_teacher_subjects ts
        JOIN tblsubjects subj ON subj.subject_id = ts.subject_id
        WHERE ts.teacher_id = ?
        ORDER BY subj.subject ASC
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assigned_courses[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $assigned_courses = [];
}

 $education = [];
try {
    $stmt = $conn->prepare("
        SELECT *
        FROM tblteacher_education
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $education = $res->fetch_assoc() ?? [];
    $stmt->close();
} catch (Exception $e) {
    $education = [];
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
<link rel="stylesheet" href="../assets/css/teacher/teacher_profile.css">
<style>
/* ── Mobile responsiveness fixes ── */
@media (max-width: 480px) {

  /* Stats row — stay 4 cols but shrink aggressively */
  .stats-mini {
    gap: 5px !important;
  }
  .stat-mini-card {
    padding: 6px 4px !important;
  }
  .stat-mini-val {
    font-size: 0.95rem !important;
  }
  .stat-mini-lbl {
    font-size: 9px !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Profile banner — stack vertically */
  .banner-body {
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 10px;
  }
  .profile-avatar-large {
    width: 70px !important;
    height: 70px !important;
    font-size: 1.4rem !important;
  }
  .profile-name {
    font-size: 1rem !important;
  }
  .profile-meta-row {
    flex-direction: column;
    align-items: center;
    gap: 4px;
  }
  .profile-meta-item span {
    font-size: 11px !important;
    word-break: break-all;
  }
  .banner-actions {
    width: 100%;
    display: flex;
    gap: 8px;
    justify-content: center;
  }
  .banner-actions .btn {
    flex: 1;
    font-size: 12px;
    padding: 7px 10px;
  }

  /* Info grid — 2 columns on mobile, smaller text */
  .info-grid {
    grid-template-columns: 1fr 1fr !important;
    gap: 8px !important;
  }
  .info-item[style*="grid-column"] {
    grid-column: 1 / -1 !important;
  }
  .info-item label {
    font-size: 10px !important;
  }
  .info-value {
    font-size: 12px !important;
    word-break: break-word;
  }

  /* Security rows — stack label + button */
  .security-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  .security-row .btn {
    align-self: flex-start;
    font-size: 11px;
  }

  /* Page header */
  .page-title {
    font-size: 1rem !important;
  }
  .page-sub {
    font-size: 11px !important;
  }

  /* Cards */
  .card {
    padding: 12px !important;
  }

  /* Course chips */
  .course-chip {
    font-size: 11px;
    padding: 4px 10px;
  }

  /* Section titles */
  .section-title {
    font-size: 13px !important;
  }

  /* Topbar logo */
  .topbar img {
    height: 28px !important;
  }

  /* Content padding */
  .content {
    padding: 12px !important;
  }
  /* Modal form grid — also 2 cols on mobile */
  .modal-body .info-grid {
    grid-template-columns: 1fr 1fr !important;
    gap: 8px !important;
  }
  .modal-body input,
  .modal-body select,
  .modal-body textarea {
    font-size: 13px !important;
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
    padding-bottom: 15px !important;
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
      <?php if ($active_courses > 0): ?><span class="nav-badge" style="background:var(--accent);color:#fff"><?= $active_courses ?></span><?php endif; ?>
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
      <?php if ($total_students > 0): ?><span class="nav-badge" style="background:var(--green);color:#fff"><?= $total_students ?></span><?php endif; ?>
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
    <a class="nav-item active" href="teacher_profile.php">
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
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--accent),var(--purple))"><?php echo $sidebar_avatar_html; ?></div>
      <div class="user-info">
        <p><?php echo $display_name; ?></p>
        <span><?php echo $role; ?></span>
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
    <div class="page-header fade-in">
      <div>
        <div class="page-title">My Profile</div>
        <div class="page-sub">Manage your personal information and account settings.</div>
      </div>
    </div>

    <!-- PROFILE BANNER -->
    <div class="profile-banner fade-in fade-in-d1">
      <div class="banner-cover"></div>
      <div class="banner-body">
        <div class="profile-avatar-large" id="profileAvatarDisplay"><?php echo $avatar_html; ?></div>
        <div class="profile-info-main">
          <div class="profile-name" id="profileNameDisplay"><?php echo $display_name; ?></div>
          <div class="profile-role"><?php echo $role; ?></div>
          <div class="profile-meta-row">
            <div class="profile-meta-item">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <span><?php echo !empty($email) ? $email : 'No email set'; ?></span>
            </div>
            <?php if (!empty($contact_number)): ?>
            <div class="profile-meta-item">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
              <span><?php echo $contact_number; ?></span>
            </div>
            <?php endif; ?>
            <div class="profile-meta-item">
              <span class="badge <?php echo $status === 'active' ? 'badge-green' : 'badge-red'; ?>"><?php echo ucfirst($status); ?></span>
            </div>
          </div>
        </div>
        <div class="banner-actions">
          <button class="btn btn-primary" onclick="openEditModal()">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <span class="btn-label">Edit Profile</span>
          </button>
          <button class="btn btn-ghost" onclick="openPasswordModal()">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            <span class="btn-label">Change Password</span>
          </button>
        </div>
      </div>
    </div>

    <!-- QUICK STATS -->
    <div class="stats-mini fade-in fade-in-d2 section-gap" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
    <div class="stat-mini-card" style="padding:8px 10px;text-align:center;">
        <div class="stat-mini-val" style="color:var(--accent);font-size:1.1rem;"><?php echo $active_courses; ?></div>
        <div class="stat-mini-lbl" style="font-size:10px;white-space:nowrap;">Active Subjects</div>
    </div>
    <div class="stat-mini-card" style="padding:8px 10px;text-align:center;">
        <div class="stat-mini-val" style="color:var(--green);font-size:1.1rem;"><?php echo $total_students; ?></div>
        <div class="stat-mini-lbl" style="font-size:10px;white-space:nowrap;">Students</div>
    </div>
    <div class="stat-mini-card" style="padding:8px 10px;text-align:center;">
        <div class="stat-mini-val" style="color:<?php echo $completion_rate >= 80 ? 'var(--green)' : ($completion_rate >= 50 ? 'var(--amber)' : 'var(--red)'); ?>;font-size:1.1rem;"><?php echo $completion_rate; ?>%</div>
        <div class="stat-mini-lbl" style="font-size:10px;white-space:nowrap;">Completion</div>
    </div>
    <div class="stat-mini-card" style="padding:8px 10px;text-align:center;">
        <div class="stat-mini-val" style="color:var(--purple);font-size:1.1rem;"><?php echo $filled_count; ?>/<?php echo $total_fields; ?></div>
        <div class="stat-mini-lbl" style="font-size:10px;white-space:nowrap;">Fields Filled</div>
    </div>
    </div>

    <!-- PERSONAL INFORMATION -->
    <div class="section-gap fade-in fade-in-d3">
      <div class="section-title">Personal Information</div>
      <div class="card">
        <div class="info-grid">
          <div class="info-item">
            <label>First Name</label>
            <div class="info-value" id="infoFirstName"><?php echo displayField($firstname); ?></div>
          </div>
          <div class="info-item">
            <label>Last Name</label>
            <div class="info-value" id="infoLastName"><?php echo displayField($lastname); ?></div>
          </div>
          <div class="info-item">
            <label>Middle Name</label>
            <div class="info-value" id="infoMiddleName"><?php echo displayField($middlename); ?></div>
          </div>
          <div class="info-item">
            <label>Role</label>
            <div class="info-value"><?php echo $role; ?></div>
          </div>
          <div class="info-item">
            <label>Date Joined</label>
            <div class="info-value muted"><?php echo !empty($date_joined) ? $date_joined : '—'; ?></div>
          </div>
          <div class="info-item">
            <label>Gender</label>
            <div class="info-value"><?php echo displayField($gender); ?></div>
          </div>
          <div class="info-item">
            <label>Age</label>
            <div class="info-value"><?php echo !empty($user['age']) ? (int)$user['age'] : '—'; ?></div>
          </div>
          <div class="info-item">
            <label>Account Status</label>
            <div class="info-value">
              <span class="badge <?php echo $status === 'active' ? 'badge-green' : 'badge-red'; ?>"><?php echo ucfirst($status); ?></span>
            </div>
          </div>
        </div>
        <div class="divider"></div>
        <div class="info-grid">
          <div class="info-item">
            <label>Email Address</label>
            <div class="info-value" id="infoEmail"><?php echo !empty($email) ? $email : '—'; ?></div>
          </div>
          <div class="info-item">
            <label>Phone Number</label>
            <div class="info-value" id="infoPhone"><?php echo displayField($contact_number); ?></div>
          </div>
          <div class="info-item" style="grid-column:1/-1">
            <label>Home Address</label>
            <div class="info-value" id="infoAddress"><?php echo displayField($address); ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- EDUCATION & CREDENTIALS -->
    <div class="section-gap fade-in fade-in-d4">
        <div class="section-title" style="display:flex;justify-content:space-between;align-items:center;">
        Education & Credentials
        <button type="button" class="edit-course-btn" onclick="openEduModal()">
            ✏️ Edit
        </button>
        </div>
        <div class="card">
            <div class="info-grid">
            <div class="info-item">
                <label>Highest Degree</label>
                <div class="info-value">
                <?php echo htmlspecialchars($education['highest_degree'] ?? '—'); ?>
                </div>
            </div>
            <div class="info-item">
                <label>University</label>
                <div class="info-value">
                <?php echo htmlspecialchars($education['university'] ?? '—'); ?>
                </div>
            </div>
            <div class="info-item">
                <label>Bachelor's Degree</label>
                <div class="info-value">
                <?php echo htmlspecialchars($education['bachelors_degree'] ?? '—'); ?>
                </div>
            </div>
            <div class="info-item">
                <label>Licensure</label>
                <div class="info-value">
                <?php echo htmlspecialchars($education['licensure'] ?? '—'); ?>
                </div>
            </div>
            </div>
            <div class="divider"></div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
            <span class="badge badge-accent">
                LPT — <?php echo htmlspecialchars($education['lpt_year'] ?? '—'); ?>
            </span>
            <span class="badge badge-purple">
                <?php echo htmlspecialchars($education['masters'] ?? '—'); ?>
            </span>
            <?php if (!empty($education['certifications'])): ?>
                <?php foreach (explode(',', $education['certifications']) as $cert): ?>
                <span class="badge badge-cyan">
                    <?php echo htmlspecialchars(trim($cert)); ?>
                </span>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- ASSIGNED COURSES -->
    <div class="section-gap fade-in fade-in-d4">
        <div class="section-title">Assigned Courses</div>
        <div class="card">
            <div class="course-chips">
            <?php if (!empty($assigned_courses)): ?>
                <?php foreach ($assigned_courses as $course): ?>
                <div class="course-chip">
                    <span class="chip-dot" style="background:var(--accent)"></span>
                    <?php echo htmlspecialchars($course['subject_name']); ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="course-chip" style="opacity:0.6">
                No assigned courses yet
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- SECURITY & ACCOUNT -->
    <div class="section-gap fade-in fade-in-d5">
      <div class="section-title">Security & Account</div>
      <div class="card">

        <!-- Password -->
        <div class="security-row">
          <div class="security-info">
            <div class="security-label">Password</div>
            <div class="security-desc">Last changed <?php echo !empty($updated_at) ? formatTimeAgo($updated_at) : 'unknown'; ?>. We recommend changing it every 90 days.</div>
          </div>
          <button class="btn btn-ghost btn-sm" onclick="openPasswordModal()">Change</button>
        </div>

        <!-- Email Verification (is_verified) -->
        <div class="security-row">
          <div class="security-info">
            <div class="security-label">Email Verification</div>
            <div class="security-desc">
              <?php if ($is_verified === 1): ?>
                Your email <strong><?php echo !empty($email) ? $email : ''; ?></strong> is verified.
              <?php else: ?>
                Your email is not yet verified. Verify to confirm your identity.
              <?php endif; ?>
            </div>
          </div>
          <?php if ($is_verified === 1): ?>
            <span class="badge badge-green" style="margin-right:8px">Verified</span>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('verifyForm').submit()">Revoke</button>
          <?php else: ?>
            <span class="badge badge-red" style="margin-right:8px">Not Verified</span>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('verifyForm').submit()">Verify Email</button>
          <?php endif; ?>
        </div>

        <!-- Active Sessions -->
        <div class="security-row">
          <div class="security-info">
            <div class="security-label">Active Sessions</div>
            <div class="security-desc">You are currently logged in on this device. Revoking will invalidate all other sessions.</div>
          </div>
          <button class="btn btn-ghost btn-sm" onclick="document.getElementById('sessionForm').submit()">Revoke Others</button>
        </div>

        <!-- Email Notifications (UI-only, no DB column) -->
        <div class="security-row">
          <div class="security-info">
            <div class="security-label">Email Notifications</div>
            <div class="security-desc">Receive alerts for submissions, messages, and system updates.</div>
          </div>
          <button class="btn btn-ghost btn-sm" id="notifToggleBtn" onclick="toggleNotifications()">Enabled</button>
        </div>

      </div>
    </div>

    <!-- DANGER ZONE -->
    <div class="section-gap fade-in fade-in-d5" style="margin-bottom:40px">
      <div class="card danger-zone">
        <div class="section-title" style="color:var(--red)">Danger Zone</div>
        <div class="security-row" style="border-color:rgba(255,107,107,.1)">
          <div class="security-info">
            <div class="security-label" style="color:var(--red)">Deactivate Account</div>
            <div class="security-desc">Temporarily disable your account. You can reactivate it later by contacting the admin.</div>
          </div>
          <button class="btn btn-danger btn-sm" onclick="showToast('Please contact the administrator to deactivate your account.','error')">Deactivate</button>
        </div>
      </div>
    </div>

  </div>
</main>
</div>

<!-- ═══ HIDDEN FORM: TOGGLE EMAIL VERIFICATION ═══ -->
<form method="POST" action="teacher_profile.php" id="verifyForm" style="display:none">
  <input type="hidden" name="toggle_verified" value="1">
</form>

<!-- ═══ HIDDEN FORM: REVOKE OTHER SESSIONS ═══ -->
<form method="POST" action="teacher_profile.php" id="sessionForm" style="display:none">
  <input type="hidden" name="regenerate_session" value="1">
</form>

<!-- ═══ EDIT PROFILE MODAL (REAL FORM) ═══ -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Profile</div>
      <button class="modal-close" onclick="closeEditModal()">✕</button>
    </div>
    <form method="POST" action="teacher_profile.php" enctype="multipart/form-data">
      <input type="hidden" name="update_profile" value="1">
      <div class="modal-body">
        <div class="avatar-upload-area">
          <div class="avatar-upload-preview" id="avatarPreview"><?php echo $avatar_html; ?></div>
          <label class="avatar-upload-btn" for="avatarInput">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Change Photo
          </label>
          <input type="file" name="profile_pic_file" id="avatarInput" class="avatar-upload-input" accept="image/*" onchange="previewAvatar(event)">
        </div>
        <div class="info-grid">
          <div>
            <label for="editFirstName">First Name *</label>
            <input type="text" name="edit_firstname" id="editFirstName" value="<?php echo $firstname; ?>" required>
          </div>
          <div>
            <label for="editLastName">Last Name *</label>
            <input type="text" name="edit_lastname" id="editLastName" value="<?php echo $lastname; ?>" required>
          </div>
        </div>
        <div>
          <label for="editMiddleName">Middle Name</label>
          <input type="text" name="edit_middlename" id="editMiddleName" value="<?php echo $middlename; ?>">
        </div>
        <div>
          <label for="editEmail">Email Address *</label>
          <input type="email" name="edit_email" id="editEmail" value="<?php echo $email; ?>" required>
        </div>
        <div>
          <label for="editPhone">Phone Number</label>
          <input type="tel" name="edit_contact" id="editPhone" value="<?php echo $contact_number; ?>">
        </div>
        <div>
          <label for="editAddress">Home Address</label>
          <textarea name="edit_address" id="editAddress" rows="2"><?php echo $address; ?></textarea>
        </div>
        <div class="info-grid">
          <div>
            <label for="editGender">Gender</label>
            <select name="edit_gender" id="editGender">
              <option value="">Select gender</option>
              <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
              <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
              <option value="Other" <?php echo $gender === 'Other' ? 'selected' : ''; ?>>Prefer not to say</option>
            </select>
          </div>
          <div>
            <label for="editAge">Age</label>
            <input type="number" name="edit_age" id="editAge" value="<?php echo $age; ?>" min="1" max="120">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ CHANGE PASSWORD MODAL (REAL FORM) ═══ -->
<div class="modal-overlay" id="passwordModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Change Password</div>
      <button class="modal-close" onclick="closePasswordModal()">✕</button>
    </div>
    <form method="POST" action="teacher_profile.php">
      <input type="hidden" name="change_password" value="1">
      <div class="modal-body">
        <div>
          <label for="currentPwd">Current Password</label>
          <input type="password" name="current_password" id="currentPwd" placeholder="Enter your current password" required>
        </div>
        <div>
          <label for="newPwd">New Password</label>
          <input type="password" name="new_password" id="newPwd" placeholder="Enter new password" oninput="checkPasswordStrength()" required>
          <div id="pwdStrength" style="margin-top:6px;display:flex;gap:4px;align-items:center">
            <div id="pwdBar1" style="flex:1;height:3px;border-radius:2px;background:var(--border);transition:var(--transition)"></div>
            <div id="pwdBar2" style="flex:1;height:3px;border-radius:2px;background:var(--border);transition:var(--transition)"></div>
            <div id="pwdBar3" style="flex:1;height:3px;border-radius:2px;background:var(--border);transition:var(--transition)"></div>
            <div id="pwdBar4" style="flex:1;height:3px;border-radius:2px;background:var(--border);transition:var(--transition)"></div>
            <span id="pwdLabel" style="font-size:11px;color:var(--text3);margin-left:6px;white-space:nowrap"></span>
          </div>
        </div>
        <div>
          <label for="confirmPwd">Confirm New Password</label>
          <input type="password" name="confirm_password" id="confirmPwd" placeholder="Re-enter new password" required>
        </div>
        <div style="background:var(--bg3);padding:12px;border-radius:var(--radius-sm);display:flex;align-items:flex-start;gap:8px">
          <span style="font-size:14px;flex-shrink:0;margin-top:1px">💡</span>
          <div style="font-size:12px;color:var(--text3);line-height:1.6">
            Password must be at least <strong style="color:var(--text2)">8 characters</strong> and include uppercase, lowercase, number, and special character.
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closePasswordModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Password</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ EDUCATION MODAL ═══ -->
<div class="modal-overlay" id="eduModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Education & Credentials</div>
      <button class="modal-close" onclick="closeEduModal()">✕</button>
    </div>
    <form method="POST" action="update_education.php">
      <div class="modal-body">
        <label>Highest Degree</label>
        <input type="text" name="highest_degree" value="<?php echo htmlspecialchars($education['highest_degree'] ?? ''); ?>">
        <label>University</label>
        <input type="text" name="university" value="<?php echo htmlspecialchars($education['university'] ?? ''); ?>">
        <label>Bachelor's Degree</label>
        <input type="text" name="bachelors_degree" value="<?php echo htmlspecialchars($education['bachelors_degree'] ?? ''); ?>">
        <label>Licensure</label>
        <input type="text" name="licensure" value="<?php echo htmlspecialchars($education['licensure'] ?? ''); ?>">
        <label>LPT Year</label>
        <input type="text" name="lpt_year" value="<?php echo htmlspecialchars($education['lpt_year'] ?? ''); ?>">
        <label>Masters</label>
        <input type="text" name="masters" value="<?php echo htmlspecialchars($education['masters'] ?? ''); ?>">
        <label>Certifications (comma separated)</label>
        <textarea name="certifications" rows="3"><?php echo htmlspecialchars($education['certifications'] ?? ''); ?></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeEduModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
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
<!-- ═══ LOGOUT CONFIRMATION MODAL ═══ -->
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

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toast-container"></div>

<script>
function openEduModal(){
    document.getElementById('eduModal').style.display = 'flex';
}

function closeEduModal(){
    document.getElementById('eduModal').style.display = 'none';
}

/* ========== LOGOUT MODAL ========== */
function openLogoutModal(){
    document.getElementById('logoutModal').classList.add('open');
    document.body.style.overflow='hidden';
}
function closeLogoutModal(){
    document.getElementById('logoutModal').classList.remove('open');
    document.body.style.overflow='';
}
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogoutModal()});
function confirmLogout(){
    window.location.href = '../logout.php';
}

/* ========== SIDEBAR ========== */
var sidebar = document.getElementById('sidebar');
var overlay = document.getElementById('sidebar-overlay');
var menuBtn = document.getElementById('menuBtn');
function openSidebar(){sidebar.classList.add('open');overlay.classList.add('open');document.body.style.overflow='hidden'}
function closeSidebar(){sidebar.classList.remove('open');overlay.classList.remove('open');document.body.style.overflow=''}
menuBtn.addEventListener('click',function(){sidebar.classList.contains('open')?closeSidebar():openSidebar()});
overlay.addEventListener('click',closeSidebar);
document.querySelectorAll('.nav-item').forEach(function(item){item.addEventListener('click',function(){if(window.innerWidth<=768)closeSidebar()})});

/* ========== EDIT PROFILE MODAL ========== */
function openEditModal(){
  document.getElementById('editModal').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeEditModal(){
  document.getElementById('editModal').classList.remove('open');
  document.body.style.overflow='';
}
document.getElementById('editModal').addEventListener('click',function(e){if(e.target===this)closeEditModal()});

function previewAvatar(e){
  var file = e.target.files[0];
  if(!file) return;
  var reader = new FileReader();
  reader.onload = function(ev){
    var preview = document.getElementById('avatarPreview');
    preview.innerHTML = '<img src="'+ev.target.result+'" alt="Avatar preview" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">';
  };
  reader.readAsDataURL(file);
}

/* ========== PASSWORD MODAL ========== */
function openPasswordModal(){
  document.getElementById('passwordModal').classList.add('open');
  document.body.style.overflow='hidden';
  document.getElementById('currentPwd').value = '';
  document.getElementById('newPwd').value = '';
  document.getElementById('confirmPwd').value = '';
  resetPwdStrength();
}
function closePasswordModal(){
  document.getElementById('passwordModal').classList.remove('open');
  document.body.style.overflow='';
}
document.getElementById('passwordModal').addEventListener('click',function(e){if(e.target===this)closePasswordModal()});

function resetPwdStrength(){
  for(var i=1;i<=4;i++) document.getElementById('pwdBar'+i).style.background='var(--border)';
  document.getElementById('pwdLabel').textContent='';
}
function checkPasswordStrength(){
  var pwd = document.getElementById('newPwd').value;
  var score = 0;
  if(pwd.length >= 8) score++;
  if(/[A-Z]/.test(pwd)) score++;
  if(/[0-9]/.test(pwd)) score++;
  if(/[^A-Za-z0-9]/.test(pwd)) score++;
  var colors = ['var(--border)','var(--red)','var(--amber)','var(--cyan)','var(--green)'];
  var labels = ['','Weak','Fair','Good','Strong'];
  for(var i=1;i<=4;i++){
    document.getElementById('pwdBar'+i).style.background = i <= score ? colors[score] : 'var(--border)';
  }
  document.getElementById('pwdLabel').textContent = pwd.length > 0 ? labels[score] : '';
  document.getElementById('pwdLabel').style.color = colors[score];
}

/* ========== NOTIFICATION TOGGLE (UI only) ========== */
var notifEnabled = true;
function toggleNotifications(){
  notifEnabled = !notifEnabled;
  var btn = document.getElementById('notifToggleBtn');
  if(notifEnabled){
    btn.textContent = 'Enabled';
    btn.style.borderColor = '';
    btn.style.color = '';
    showToast('Email notifications enabled.','success');
  } else {
    btn.textContent = 'Disabled';
    btn.style.borderColor = 'var(--red)';
    btn.style.color = 'var(--red)';
    showToast('Email notifications disabled.','info');
  }
}

/* ========== THEME ========== */
var themeToggle = document.getElementById('themeToggle');
var themeIcon = document.getElementById('theme-icon');
var themeText = document.getElementById('theme-text');
function applyTheme(isLight){
  if(isLight){document.body.classList.add('light');themeIcon.textContent='☀️';themeText.textContent='Light Mode'}
  else{document.body.classList.remove('light');themeIcon.textContent='🌙';themeText.textContent='Dark Mode'}
}
(function(){if(localStorage.getItem('excelearn-theme')==='light')applyTheme(true)})();
themeToggle.addEventListener('click',function(){
  var isLight=document.body.classList.contains('light');
  applyTheme(!isLight);
  localStorage.setItem('excelearn-theme',!isLight?'light':'dark');
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
  setTimeout(function(){
    t.style.opacity='0';t.style.transform='translateX(60px)';t.style.transition='all .3s ease';
    setTimeout(function(){t.remove()},300);
  },3000);
}

/* ========== KEYBOARD ========== */
document.addEventListener('keydown',function(e){
  if(e.key==='Escape'){closeEditModal();closePasswordModal();closeEduModal();closeLogoutModal();closeSidebar()}
});

var resizeTimer;
window.addEventListener('resize',function(){
  clearTimeout(resizeTimer);
  resizeTimer=setTimeout(function(){if(window.innerWidth>768)closeSidebar()},150);
});
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

<?php if (!empty($success_msg)): ?>
showToast('<?php echo addslashes($success_msg); ?>', 'success');
<?php endif; ?>
<?php if (!empty($error_msg)): ?>
showToast('<?php echo addslashes($error_msg); ?>', 'error');
<?php endif; ?>

</script>
</body>
</html>