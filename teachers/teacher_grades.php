<?php
session_start();
include '../connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Fetch teacher info
$stmt = $conn->prepare("SELECT id, profile_pic, firstname, middlename, lastname, role FROM tblusers WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { session_destroy(); header("Location: ../index.php"); exit(); }

$firstname    = htmlspecialchars($user['firstname'] ?? '');
$middlename   = htmlspecialchars($user['middlename'] ?? '');
$lastname     = htmlspecialchars($user['lastname'] ?? '');
$display_name = trim("$firstname " . ($middlename ? "$middlename " : '') . "$lastname");
$initials     = strtoupper(substr($user['firstname'] ?? '', 0, 1) . substr($user['lastname'] ?? '', 0, 1));
if (strlen(trim($initials)) < 2) $initials = 'U';
$role_display = htmlspecialchars(ucfirst($user['role'] ?? 'Teacher'));

$profile_pic_path = '';
$profile_pic_db   = $user['profile_pic'] ?? '';
if (!empty($profile_pic_db)) {
    foreach (['../uploads/profile_pic/teachers/', '../uploads/profile_pic/', '../'] as $dir) {
        $p = $dir . $profile_pic_db;
        if (file_exists($p)) { $profile_pic_path = $p; break; }
    }
}
$sidebar_avatar_html = !empty($profile_pic_path)
    ? '<img src="' . htmlspecialchars($profile_pic_path) . '" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">'
    : htmlspecialchars($initials);

// Badge counts
$total_lessons_badge = (int)$conn->query("
    SELECT COUNT(*) FROM tbl_teacher_lessons l
    JOIN tbl_teacher_subjects s ON l.teacher_subject_id = s.teacher_subject_id
    WHERE s.teacher_id = $teacher_id
")->fetch_row()[0];

$total_subjects_badge = (int)$conn->query("
    SELECT COUNT(*) FROM tbl_teacher_subjects WHERE teacher_id = $teacher_id
")->fetch_row()[0];

$total_quizzes_badge = (int)$conn->query("
    SELECT COUNT(*) FROM tblquizzes q
    JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id
    JOIN tbl_teacher_subjects s ON l.teacher_subject_id = s.teacher_subject_id
    WHERE s.teacher_id = $teacher_id
")->fetch_row()[0];

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

// Fetch all subjects for this teacher
$subjects_list = $conn->query("
    SELECT ts.teacher_subject_id, subj.subject AS subject_name
    FROM tbl_teacher_subjects ts
    JOIN tblsubjects subj ON subj.subject_id = ts.subject_id
    WHERE ts.teacher_id = $teacher_id
    ORDER BY subj.subject ASC
")->fetch_all(MYSQLI_ASSOC);

// Fetch quizzes grouped by subject
$quizzes_by_subject = [];
$all_quiz_ids = [];
foreach ($subjects_list as $subj) {
    $tsid = $subj['teacher_subject_id'];
    $qrows = $conn->query("
    SELECT q.quiz_id, q.description, q.question_count, q.questions,
           l.lesson_id, l.title AS lesson_title, l.order_index,
           l.teacher_subject_id
    FROM tblquizzes q
    JOIN tbl_teacher_lessons l ON q.lesson_id = l.lesson_id
    WHERE l.teacher_subject_id = $tsid
    ORDER BY l.created_at ASC, l.lesson_id ASC, q.created_at ASC, q.quiz_id ASC
")->fetch_all(MYSQLI_ASSOC);

// Group quizzes under their lesson — preserves the ASC (first-added-first) order from $qrows
$lessons_with_quizzes = []; 
foreach ($qrows as $qr) {
    $lid = $qr['lesson_id'];
    if (!isset($lessons_with_quizzes[$lid])) {
        $lessons_with_quizzes[$lid] = [
            'lesson_id'    => $lid,
            'lesson_title' => $qr['lesson_title'],
            'order_index'  => $qr['order_index'],
            'quizzes'      => [],
        ];
    }
    $lessons_with_quizzes[$lid]['quizzes'][] = $qr;
}


$quizzes_by_subject[$tsid] = $qrows; 
$lessons_by_subject[$tsid] = array_values($lessons_with_quizzes);
    foreach ($qrows as $qr) $all_quiz_ids[] = $qr['quiz_id'];
}

// Fetch students per subject
$students_by_subject = [];
foreach ($subjects_list as $subj) {
    $tsid = $subj['teacher_subject_id'];
    $rows = $conn->query("
        SELECT DISTINCT st.id, st.firstname, st.lastname, st.middlename
        FROM tblstudents st
        JOIN tbl_teachers_students tts ON tts.student_id = st.id
        WHERE tts.teacher_subject_id = $tsid
        ORDER BY st.lastname ASC, st.firstname ASC
    ")->fetch_all(MYSQLI_ASSOC);
    $students_by_subject[$tsid] = $rows;
}

// Fetch all quiz results
$results_map = []; 
$submission_count = 0;
if (!empty($all_quiz_ids)) {
    $in = implode(',', array_unique($all_quiz_ids));
    $results = $conn->query("
        SELECT qr.student_id, qr.quizzes_id, qr.score, qr.manual_score, q.question_count, q.questions
        FROM tblquiz_results qr
        JOIN tblquizzes q ON q.quiz_id = qr.quizzes_id
        WHERE qr.quizzes_id IN ($in)
        ORDER BY qr.created_at ASC
    ")->fetch_all(MYSQLI_ASSOC);

    foreach ($results as $r) {
        $sid  = $r['student_id'];
        $qid  = $r['quizzes_id'];
        $max  = max((float)$r['question_count'], 1);

        // Detect essay-type quiz (all questions are essay)
        $decodedQs = json_decode($r['questions'] ?? '[]', true);
        $is_essay  = false;
        if (!empty($decodedQs) && is_array($decodedQs)) {
            $is_essay = true;
            foreach ($decodedQs as $dq) {
                if (($dq['type'] ?? '') !== 'essay') { $is_essay = false; break; }
            }
        }

        if ($is_essay) {
            if ($r['manual_score'] === null) continue; 
            $raw = (float)$r['manual_score'];
        } else {
            $raw = (float)$r['score'];
        }
        $pct = round(($raw / $max) * 100, 1);
        $pct = min($pct, 100); 

        // Keep best attempt per student per quiz
        if (!isset($results_map[$sid][$qid]) || $pct > $results_map[$sid][$qid]['pct']) {
            $results_map[$sid][$qid] = [
                'raw'      => $raw,
                'pct'      => $pct,
                'max'      => $max,
                'taken'    => true,
                'is_essay' => $is_essay,
            ];
        }
    }

    // Count unique student+quiz submissions
    $all_student_ids = [];
    foreach ($students_by_subject as $rows) {
        foreach ($rows as $st) $all_student_ids[] = $st['id'];
    }
    $all_student_ids = array_unique($all_student_ids);

    foreach ($results_map as $sid => $quizzes) {
        if (!in_array($sid, $all_student_ids)) continue; 
        foreach ($quizzes as $qid => $sc) {
            if (in_array($qid, $all_quiz_ids)) $submission_count++;
        }
    }
}

// Build grades per subject
$grades_by_subject = [];
$all_avgs = [];

foreach ($subjects_list as $subj) {
    $tsid      = $subj['teacher_subject_id'];
    $quizzes   = $quizzes_by_subject[$tsid] ?? [];
    $students  = $students_by_subject[$tsid] ?? [];
    $rows      = [];

    foreach ($students as $st) {
        $sid      = $st['id'];
        $name     = trim($st['firstname'] . ' ' . ($st['middlename'] ? $st['middlename'] . ' ' : '') . $st['lastname']);
        $initials_s = strtoupper(substr($st['firstname'], 0, 1) . substr($st['lastname'], 0, 1));

        $scores    = [];
        $total_pct = 0;
        $taken     = 0;

        foreach ($quizzes as $qz) {
            $qid = $qz['quiz_id'];
            $max = max((int)$qz['question_count'], 1);

            if (isset($results_map[$sid][$qid])) {
                $best = $results_map[$sid][$qid];
                $scores[$qid] = [
                    'raw'      => $best['raw'],
                    'pct'      => $best['pct'],
                    'max'      => $best['max'],
                    'taken'    => true,
                    'is_essay' => $best['is_essay'] ?? false,
                ];
                $total_pct += $best['pct'];
                $taken++;
            } else {
                $scores[$qid] = ['raw' => null, 'pct' => null, 'max' => $max, 'taken' => false, 'is_essay' => false];
            }
        }

        // Average only over quizzes the student actually took (same as student_grades)
        $avg = $taken > 0 ? round($total_pct / $taken, 1) : 0;
        $all_avgs[] = $avg;

        $rows[] = [
            'id'       => $sid,
            'name'     => $name,
            'initials' => $initials_s,
            'avg'      => $avg,
            'taken'    => $taken,
            'total'    => count($quizzes),
            'scores'   => $scores,
        ];
    }

    // Sort by avg descending by default
    usort($rows, fn($a, $b) => $b['avg'] <=> $a['avg']);

    $grades_by_subject[] = [
        'subject_id'   => $tsid,
        'subject_name' => $subj['subject_name'],
        'quizzes'      => $quizzes,
        'lessons'      => $lessons_by_subject[$tsid] ?? [],
        'students'     => $rows,
    ];
}

// Stats (across all subjects)
$class_avg = count($all_avgs) > 0 ? round(array_sum($all_avgs) / count($all_avgs), 1) : 0;
$highest   = count($all_avgs) > 0 ? max($all_avgs) : 0;
$total_possible = 0;
foreach ($grades_by_subject as $grp) {
    $total_possible += count($grp['students']) * count($grp['quizzes']);
}
$sub_rate = $total_possible > 0 ? min(100, round(($submission_count / $total_possible) * 100)) : 0;

// Search & filter
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';

// Apply search/filter per subject
foreach ($grades_by_subject as &$grp) {
    if ($search) {
        $grp['students'] = array_values(array_filter($grp['students'], fn($s) => stripos($s['name'], $search) !== false));
    }
    if ($filter === 'passing') {
        $grp['students'] = array_values(array_filter($grp['students'], fn($s) => $s['avg'] >= 75));
    } elseif ($filter === 'failing') {
        $grp['students'] = array_values(array_filter($grp['students'], fn($s) => $s['avg'] < 75));
    }
}
unset($grp);

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
<link rel="stylesheet" href="../assets/css/teacher/teacher_grades.css">
<style>
.btn-danger{background:var(--red);color:#fff;border:none;}
.btn-danger:hover{opacity:0.88;}
.export-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--bg2);color:var(--text);transition:all .2s;}
.export-btn:hover{background:var(--accent);color:#fff;border-color:var(--accent);}
@media(max-width:480px){
  .class-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;}
  .stat-card{padding:8px 6px;border-radius:10px;}
  .stat-val{font-size:1.1rem;}
  .stat-lbl{font-size:9px;}

  .page-title{font-size:1rem;}
  .page-sub{font-size:11px;}

  .toolbar{gap:6px;}
  .toolbar .search-wrap input{height:32px;font-size:12px;padding:0 10px 0 32px;}
  .toolbar .search-wrap{min-height:unset;}
  .filter-tab{font-size:11px;padding:5px 10px;}

  .grade-card{padding:10px;}
  .g-name{font-size:12px;}
  .g-id{font-size:10px;}
  .g-avg-label{font-size:10px;}
  .g-avg-badge{font-size:11px;}
  .grade-body table{font-size:11px;}
  .grade-body td, .grade-body th{padding:4px 6px;}

  .export-btn{font-size:11px;padding:5px 10px;}
}
@media print{
  .sidebar,.topbar,.toolbar,.modal-overlay,.toast-container,.export-btn,.theme-toggle-wrapper,.nav-section-label{display:none!important;}
  .main{margin:0!important;padding:0!important;}
  .content{padding:0!important;}
  .grade-card{break-inside:avoid;page-break-inside:avoid;}
  body{background:#fff!important;color:#000!important;}
  .stat-card,.grade-card{border:1px solid #ddd!important;background:#fff!important;}
  .score-val.high{color:#16a34a!important;}
  .score-val.mid{color:#d97706!important;}
  .score-val.low{color:#dc2626!important;}
  .no-print{display:none!important;}
}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
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
      <?php if ($total_subjects_badge > 0): ?><span class="nav-badge" style="background:var(--accent);color:#fff"><?= $total_subjects_badge ?></span><?php endif; ?>
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
    <a class="nav-item active" href="teacher_grades.php">
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
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--accent),var(--purple));overflow:hidden;display:flex;align-items:center;justify-content:center;">
        <?= $sidebar_avatar_html ?>
      </div>
      <div class="user-info">
        <p><?= $display_name ?></p>
        <span>Faculty — <?php echo $role_display; ?></span>
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
  <div class="page-header">
    <div>
      <div class="page-title">Gradebook</div>
      <div class="page-sub"><?= count($subjects_list) ?> subject<?= count($subjects_list) != 1 ? 's' : '' ?> · <?= count($all_quiz_ids) ?> total quizzes</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;" class="no-print">
    <button class="export-btn" onclick="exportAllCSV()">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
        Export All CSV
    </button>
    </div>
  </div>

  <div class="class-stats">
    <div class="stat-card">
      <div class="stat-val"><?= $class_avg ?>%</div>
      <div class="stat-lbl">Class Average</div>
    </div>
    <div class="stat-card">
      <div class="stat-val"><?= $highest ?>%</div>
      <div class="stat-lbl">Highest Score</div>
    </div>
    <div class="stat-card">
      <div class="stat-val"><?= $sub_rate ?>%</div>
      <div class="stat-lbl">Submission Rate</div>
    </div>
  </div>

  <div class="toolbar">
    <form method="GET" class="search-wrap" style="display:flex;align-items:center;position:relative;">
      <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
      <span class="search-icon">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </span>
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search student name..." style="font-size:13px;">
    </form>
    <div class="filter-tabs">
      <a href="?filter=all<?= $search ? '&search='.urlencode($search) : '' ?>" class="filter-tab<?= $filter==='all' ? ' active' : '' ?>">All</a>
      <a href="?filter=passing<?= $search ? '&search='.urlencode($search) : '' ?>" class="filter-tab<?= $filter==='passing' ? ' active' : '' ?>">Passing</a>
      <a href="?filter=failing<?= $search ? '&search='.urlencode($search) : '' ?>" class="filter-tab<?= $filter==='failing' ? ' active' : '' ?>">At Risk</a>
    </div>
  </div>

  <?php foreach ($grades_by_subject as $grp): ?>
  <?php if (empty($grp['quizzes']) || empty($grp['students'])) continue; ?>
  <div id="subject-<?= $grp['subject_id'] ?>" style="margin-bottom:32px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
      <span style="font-size:18px;">📚</span>
      <div style="flex:1">
        <div style="font-size:15px;font-weight:700;color:var(--text)"><?= htmlspecialchars($grp['subject_name']) ?></div>
        <div style="font-size:12px;color:var(--text3)"><?= count($grp['quizzes']) ?> quiz<?= count($grp['quizzes']) != 1 ? 'zes' : '' ?> · <?= count($grp['students']) ?> student<?= count($grp['students']) != 1 ? 's' : '' ?></div>
      </div>
      <button class="export-btn no-print" onclick="exportSubjectPDF('subject-<?= $grp['subject_id'] ?>')">
        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
        Export PDF
      </button>
    </div>

    <div class="gradebook-list">
      <?php if (empty($grp['students'])): ?>
        <div class="empty-state" style="padding:20px">
          <div class="empty-icon">👥</div>
          <p>No students<?= $search ? ' matching "'.htmlspecialchars($search).'"' : '' ?></p>
        </div>
      <?php endif; ?>

    <?php foreach ($grp['students'] as $sg):
    $avg = $sg['avg'];
    $avgColor = $avg >= 90 ? 'var(--green)' : ($avg >= 75 ? 'var(--amber)' : 'var(--red)');
    $avgBg    = $avg >= 90 ? 'var(--green-bg)' : ($avg >= 75 ? 'var(--amber-bg)' : 'var(--red-bg)');
    $letter   = '';
    if($avg>=97)$letter='A+'; elseif($avg>=93)$letter='A'; elseif($avg>=90)$letter='A-';
    elseif($avg>=87)$letter='B+'; elseif($avg>=83)$letter='B'; elseif($avg>=80)$letter='B-';
    elseif($avg>=77)$letter='C+'; elseif($avg>=73)$letter='C'; elseif($avg>=70)$letter='C-';
    elseif($avg>=67)$letter='D+'; elseif($avg>=63)$letter='D'; elseif($avg>=60)$letter='D-';
    else $letter = 'F';
?>
<div class="grade-card">
  <div class="grade-header">
    <div class="g-avatar"><?= htmlspecialchars($sg['initials']) ?></div>
    <div class="g-info">
      <div class="g-name"><?= htmlspecialchars($sg['name']) ?></div>
      <div class="g-id"><?= $sg['taken'] ?> / <?= $sg['total'] ?> quizzes taken · Best scores averaged</div>
    </div>
    <!-- Subject grade badge — mirrors student_grades display -->
    <div style="text-align:right;flex-shrink:0">
      <div style="font-size:1.4rem;font-weight:800;color:<?= $avgColor ?>"><?= $avg ?>%</div>
      <div style="font-size:0.75rem;background:<?= $avgBg ?>;color:<?= $avgColor ?>;padding:2px 8px;border-radius:20px;font-weight:700;display:inline-block"><?= $letter ?></div>
    </div>
  </div>

  <!-- Progress bar -->
  <div style="margin:8px 0 12px;height:5px;background:var(--bg3);border-radius:99px;overflow:hidden">
    <div style="height:100%;width:<?= $avg ?>%;background:<?= $avgColor ?>;border-radius:99px;transition:width .4s ease"></div>
  </div>

<div class="grade-body">
    <?php foreach ($grp['lessons'] as $lesson): ?>
      <?php if (empty($lesson['quizzes'])) continue; ?>
      <div style="margin-bottom:12px;">
        <div style="font-size:11px;font-weight:700;color:var(--text2,#cbd5e1);margin-bottom:4px;">📖 <?= htmlspecialchars($lesson['lesson_title']) ?></div>
        <div style="overflow-x:auto;border:1px solid var(--border);border-radius:8px;padding:4px;background:var(--bg2);display:inline-block;<?= count($lesson['quizzes']) === 1 ? '' : 'width:100%;' ?>">
          <table style="<?= count($lesson['quizzes']) === 1 ? 'width:auto;min-width:160px;' : 'width:100%;' ?>border-collapse:collapse;font-size:12px;">
            <thead>
              <tr>
                <?php $qnum = 0; foreach ($lesson['quizzes'] as $qz): $qnum++; ?>
                  <th style="padding:6px 10px;background:var(--bg3);border:1px solid var(--border);font-weight:600;white-space:nowrap;text-align:center;">
                    <div style="font-size:11px;color:var(--text);font-weight:700;">Quiz <?= $qnum ?></div>
                    <div style="font-size:10px;color:var(--accent,#6366f1);font-weight:600;white-space:normal;max-width:140px;margin:2px auto 0;word-wrap:break-word;line-height:1.3;">📝 <?= htmlspecialchars($qz['description'] ?: 'Quiz') ?></div>
                  </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <tr>
                <?php foreach ($lesson['quizzes'] as $qz):
                  $sc = $sg['scores'][$qz['quiz_id']] ?? ['taken' => false];
                  if ($sc['taken']) {
                    $pct = $sc['pct'];
                    $color = $pct >= 90 ? 'var(--green)' : ($pct >= 75 ? 'var(--amber)' : 'var(--red)');
                    $display = ($sc['raw'] == floor($sc['raw'])) ? (int)$sc['raw'] : $sc['raw'];
                  }
                ?>
                  <td style="padding:6px 10px;border:1px solid var(--border);text-align:center;<?= $sc['taken'] ? 'color:'.$color.';font-weight:600;' : 'color:var(--text3);' ?>">
                    <?php if ($sc['taken']): ?>
                      <?php if (!empty($sc['is_essay'])): ?>
                        <?= $display ?> pt<?= ($display == 1 ? '' : 's') ?>
                      <?php else: ?>
                        <?= $display ?>/<?= (int)$sc['max'] ?>
                        <div style="font-size:10px;font-weight:400;"><?= $pct ?>%</div>
                      <?php endif; ?>
                    <?php else: ?>
                      —
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grade-footer" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:nowrap;gap:8px;">
    <div class="g-avg-wrap" style="flex-shrink:0;">
      <span class="g-avg-label">Subject Average</span>
      <span class="g-avg-badge" style="color:<?= $avgColor ?>;border-color:<?= $avgColor ?>"><?= $avg ?>%</span>
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:nowrap;flex-shrink:0;margin-left:auto;">
      <?php if ($sg['taken'] < $sg['total']): ?>
        <span style="font-size:11px;color:var(--amber)">⚠️ <?= $sg['total'] - $sg['taken'] ?> quiz<?= ($sg['total'] - $sg['taken']) > 1 ? 'zes' : '' ?> not yet taken</span>
      <?php endif; ?>
      <?php if ($sg['taken'] > 0): ?>
        <button class="btn-danger no-print" onclick="openDeleteModal(<?= $sg['id'] ?>, '<?= addslashes($sg['name']) ?>', <?= $grp['subject_id'] ?>)"
          style="font-size:11px;padding:4px 10px;border-radius:6px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
          🗑
        </button>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
    </div> <!-- closes gradebook-list -->
  </div>   <!-- closes subject-wrapper -->
<?php endforeach; // closes grades_by_subject ?>

  <?php if (empty($subjects_list)): ?>
    <div class="empty-state">
      <div class="empty-icon">📚</div>
      <p>No subjects assigned yet</p>
      <span>You need subjects with students and quizzes to see grades here.</span>
    </div>
  <?php endif; ?>
</div>
</main>
</div>

<!-- DELETE RESULT MODAL -->
<div class="modal-overlay" id="deleteResultModal">
  <div class="modal" style="max-width:480px;width:calc(100% - 32px);margin:auto;border-radius:16px;overflow:hidden;">
    <div class="modal-header" style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;">
      <div style="width:34px;height:34px;border-radius:8px;background:var(--red-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px;">🗑️</div>
      <div style="flex:1;min-width:0">
        <div class="modal-title" style="font-size:15px;font-weight:700;color:var(--text);line-height:1.2">Delete Quiz Results</div>
        <div id="deleteModalMsg" style="font-size:12px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;"></div>
      </div>
      <button class="modal-close" onclick="closeDeleteModal()" style="flex-shrink:0;width:28px;height:28px;border-radius:6px;border:none;background:var(--bg3);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--text2);">✕</button>
    </div>

    <div class="modal-body" style="padding:0">
      <div style="padding:12px 20px;background:var(--amber-bg,rgba(245,158,11,0.08));border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px;">
        <span style="font-size:13px;">⚠️</span>
        <span style="font-size:12px;color:var(--amber,#d97706);">Deleted results cannot be recovered. Select carefully.</span>
      </div>

      <div style="padding:14px 20px 4px;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:12px;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:0.04em;">Quiz results</span>
        <label style="font-size:12px;color:var(--text3);cursor:pointer;display:flex;align-items:center;gap:5px;user-select:none;">
          <input type="checkbox" id="selectAllQuizzes" onchange="toggleSelectAll(this)" style="width:14px;height:14px;cursor:pointer;accent-color:var(--accent);">
          Select all
        </label>
      </div>

      <div id="deleteQuizList" style="padding:8px 16px 16px;display:flex;flex-direction:column;gap:6px;max-height:300px;overflow-y:auto;"></div>
    </div>

    <div class="modal-footer" style="padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:8px;justify-content:flex-end;">
    <button class="btn btn-ghost" onclick="closeDeleteModal()" style="padding:9px 20px;border-radius:8px;font-size:13px;font-weight:600;">Cancel</button>
    <button class="btn btn-danger" onclick="confirmDeleteResult()" style="padding:9px 20px;border-radius:8px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;">
        <span>🗑️</span> Delete Selected
    </button>
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
<!-- EDIT GRADES MODAL -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Grades</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <div id="modalStudentInfo" style="margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border);font-weight:600;font-size:16px;"></div>
      <div id="gradeInputsContainer">
        <!-- Dynamic Inputs injected here -->
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" onclick="saveGrades()">Save Changes</button>
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
var sidebar=document.getElementById('sidebar');
var overlay=document.getElementById('sidebar-overlay');
var menuBtn=document.getElementById('menuBtn');
function openSidebar(){sidebar.classList.add('open');overlay.classList.add('open');document.body.style.overflow='hidden';}
function closeSidebar(){sidebar.classList.remove('open');overlay.classList.remove('open');document.body.style.overflow='';}
menuBtn.addEventListener('click',function(){sidebar.classList.contains('open')?closeSidebar():openSidebar();});
overlay.addEventListener('click',closeSidebar);
document.querySelectorAll('.nav-item').forEach(function(item){item.addEventListener('click',function(){if(window.innerWidth<=768)closeSidebar();});});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeSidebar();closeLogoutModal();closeDeleteModal();}});
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogoutModal();});

var themeToggle=document.getElementById('themeToggle');
var themeIcon=document.getElementById('theme-icon');
var themeText=document.getElementById('theme-text');
function applyTheme(isLight){if(isLight){document.body.classList.add('light');themeIcon.textContent='☀️';themeText.textContent='Light Mode';}else{document.body.classList.remove('light');themeIcon.textContent='🌙';themeText.textContent='Dark Mode';}}
if(localStorage.getItem('excelearn-theme')==='light')applyTheme(true);
themeToggle.addEventListener('click',function(){var isLight=document.body.classList.contains('light');applyTheme(!isLight);localStorage.setItem('excelearn-theme',!isLight?'light':'dark');});

function openLogoutModal(){document.getElementById('logoutModal').classList.add('open');document.body.style.overflow='hidden';}
function closeLogoutModal(){document.getElementById('logoutModal').classList.remove('open');document.body.style.overflow='';}
function confirmLogout(){window.location.href='../logout.php';}

var resizeTimer;
window.addEventListener('resize',function(){clearTimeout(resizeTimer);resizeTimer=setTimeout(function(){if(window.innerWidth>768)closeSidebar();},150);});

function exportSubjectPDF(subjectId) {
  var el = document.getElementById(subjectId);
  if (!el) return;
  var title = el.querySelector('[style*="font-weight:700"]')?.textContent?.trim() || 'Subject';
  var win = window.open('', '_blank');
  win.document.write('<html><head><title>' + title + ' — Gradebook</title>');
  win.document.write('<style>');
  win.document.write('body{font-family:sans-serif;font-size:13px;color:#111;margin:24px;}');
  win.document.write('h1{font-size:18px;margin-bottom:4px;}');
  win.document.write('.sub{font-size:12px;color:#555;margin-bottom:20px;}');
  win.document.write('table{width:100%;border-collapse:collapse;margin-bottom:20px;}');
  win.document.write('th{background:#f3f4f6;text-align:left;padding:7px 10px;font-size:11px;text-transform:uppercase;border:1px solid #e5e7eb;}');
  win.document.write('td{padding:7px 10px;border:1px solid #e5e7eb;vertical-align:top;}');
  win.document.write('.badge{display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;}');
  win.document.write('.high{color:#16a34a;}.mid{color:#d97706;}.low{color:#dc2626;}');
  win.document.write('.lesson-header{background:#f9fafb;font-weight:700;font-size:11px;text-transform:uppercase;color:#6b7280;padding:5px 10px;border:1px solid #e5e7eb;}');
  win.document.write('</style></head><body>');
  win.document.write('<h1>📚 ' + title + '</h1>');
  win.document.write('<div class="sub">Exported: ' + new Date().toLocaleString() + '</div>');

  /* Build table from PHP data via inline JSON */
  var data = <?= json_encode(array_map(function($grp) {
    return [
      'subject_id'   => $grp['subject_id'],
      'subject_name' => $grp['subject_name'],
      'lessons'      => $grp['lessons'],
      'students'     => array_map(function($s) {
        return ['name'=>$s['name'],'avg'=>$s['avg'],'taken'=>$s['taken'],'scores'=>$s['scores']];
      }, $grp['students']),
      'quizzes'      => array_map(function($q) {
        return ['quiz_id'=>$q['quiz_id'],'description'=>$q['description'],'lesson_title'=>$q['lesson_title'],'question_count'=>$q['question_count']];
      }, $grp['quizzes']),
    ];
  }, $grades_by_subject)) ?>;

  var grp = data.find(function(g){ return 'subject-' + g.subject_id === subjectId; });
  if (!grp) { win.document.write('<p>No data.</p></body></html>'); win.document.close(); win.print(); return; }

  writePDFTable(win, grp);
  win.document.write('</body></html>');
  win.document.close();
  win.focus();
  setTimeout(function(){ win.print(); }, 400);
}

function exportAllCSV() {
  var data = <?= json_encode(array_map(function($grp) {
    return [
      'subject_name' => $grp['subject_name'],
      'lessons'      => $grp['lessons'],
      'students'     => array_map(function($s) {
        return ['name'=>$s['name'],'avg'=>$s['avg'],'taken'=>$s['taken'],'total'=>$s['total'],'scores'=>$s['scores']];
      }, $grp['students']),
      'quizzes'      => array_map(function($q) {
        return ['quiz_id'=>$q['quiz_id'],'description'=>$q['description'],'lesson_title'=>$q['lesson_title'],'question_count'=>$q['question_count']];
      }, $grp['quizzes']),
    ];
  }, $grades_by_subject)) ?>;

  var rows = [];
  var now = new Date().toLocaleString();
  rows.push(['ExceLearn Gradebook Export', now]);
  rows.push([]);

  data.forEach(function(grp) {
    if (!grp.students || !grp.quizzes || grp.students.length === 0) return;

    // Subject header row
    rows.push(['Subject: ' + grp.subject_name]);
    rows.push([]);

    // ── One block per lesson (mirrors on-screen gradebook grouping) ──
    grp.lessons.forEach(function(lesson) {
      if (!lesson.quizzes || lesson.quizzes.length === 0) return;

      rows.push(['Lesson: ' + lesson.lesson_title]);

      var lessonHeader = ['Student'];
      lesson.quizzes.forEach(function(qz, idx) {
        lessonHeader.push('Quiz ' + (idx+1) + ' - ' + (qz.description || 'Quiz') + ' (/' + qz.question_count + ')');
      });
      rows.push(lessonHeader);

      grp.students.forEach(function(st) {
        var row = [st.name];
        lesson.quizzes.forEach(function(qz) {
          var sc = st.scores[qz.quiz_id];
          if (sc && sc.taken) {
            var raw = (sc.raw == Math.floor(sc.raw)) ? Math.floor(sc.raw) : sc.raw;
            row.push(raw + '/' + qz.question_count + ' (' + sc.pct + '%)');
          } else {
            row.push('—');
          }
        });
        rows.push(row);
      });

      rows.push([]); // blank line between lessons
    });

    // ── Summary block: subject average per student (same numbers shown on each grade-card) ──
    rows.push(['Subject Average Summary']);
    rows.push(['Student', 'Quizzes Taken', 'Average (%)']);
    grp.students.forEach(function(st) {
      rows.push([st.name, st.taken + '/' + st.total, st.avg + '%']);
    });

    rows.push([]); 
  });

  // Convert to CSV string
  var csv = rows.map(function(row) {
    return row.map(function(cell) {
      var val = String(cell ?? '');
      if (val.indexOf(',') !== -1 || val.indexOf('"') !== -1 || val.indexOf('\n') !== -1) {
        val = '"' + val.replace(/"/g, '""') + '"';
      }
      return val;
    }).join(',');
  }).join('\n');

  // Trigger download
  var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  var url  = URL.createObjectURL(blob);
  var a    = document.createElement('a');
  a.href     = url;
  a.download = 'gradebook_' + new Date().toISOString().slice(0,10) + '.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

function writePDFTable(win, grp) {
  if (!grp.students || grp.students.length === 0) {
    win.document.write('<p style="color:#888">No students in this subject.</p>'); return;
  }
  if (!grp.quizzes || grp.quizzes.length === 0) {
    win.document.write('<p style="color:#888">No quizzes yet.</p>'); return;
  }

  // ── One table per lesson (same grouping as the on-screen gradebook) ──
  grp.lessons.forEach(function(lesson) {
    if (!lesson.quizzes || lesson.quizzes.length === 0) return;

    win.document.write('<div class="lesson-header">📖 ' + lesson.lesson_title + '</div>');
    win.document.write('<table>');
    win.document.write('<thead><tr><th>Student</th>');
    lesson.quizzes.forEach(function(qz, idx){
      win.document.write('<th>Quiz ' + (idx+1) + '<br><small>' + (qz.description || 'Quiz') + '</small></th>');
    });
    win.document.write('</tr></thead><tbody>');

    grp.students.forEach(function(st){
      win.document.write('<tr><td><strong>' + st.name + '</strong></td>');
      lesson.quizzes.forEach(function(qz){
        var sc = st.scores[qz.quiz_id];
        if (sc && sc.taken) {
          var cls = sc.pct >= 90 ? 'high' : (sc.pct >= 75 ? 'mid' : 'low');
          var raw = (sc.raw == Math.floor(sc.raw)) ? Math.floor(sc.raw) : sc.raw;
          win.document.write('<td class="' + cls + '">' + raw + ' / ' + qz.question_count + '<br><small>' + sc.pct + '%</small></td>');
        } else {
          win.document.write('<td style="color:#aaa">—</td>');
        }
      });
      win.document.write('</tr>');
    });
    win.document.write('</tbody></table>');
  });

  // ── Summary table: subject average per student (mirrors the "Subject Average" badge on each grade-card) ──
  win.document.write('<div class="lesson-header">📊 Subject Average Summary</div>');
  win.document.write('<table><thead><tr><th>Student</th><th>Quizzes Taken</th><th>Average</th></tr></thead><tbody>');
  grp.students.forEach(function(st){
    var avgColor = st.avg >= 90 ? '#16a34a' : (st.avg >= 75 ? '#d97706' : '#dc2626');
    win.document.write('<tr><td><strong>' + st.name + '</strong></td><td>' + st.taken + '/' + st.total + '</td><td><strong style="color:' + avgColor + '">' + st.avg + '%</strong></td></tr>');
  });
  win.document.write('</tbody></table>');
}

var _deleteStudentId = null;
var _deleteSubjectId = null;
var _deleteStudentName = '';
var _deleteQuizData = []; 

function openDeleteModal(studentId, studentName, subjectId) {
  _deleteStudentId = studentId;
  _deleteSubjectId = subjectId;
  _deleteStudentName = studentName;

  // Find the subject group and student scores from PHP-inlined data
  var allData = <?= json_encode(array_map(function($grp) {
    return [
      'subject_id' => $grp['subject_id'],
      'lessons'    => $grp['lessons'],
      'quizzes'    => array_map(function($q) {
        return ['quiz_id'=>$q['quiz_id'],'description'=>$q['description'],'lesson_title'=>$q['lesson_title'],'question_count'=>$q['question_count']];
      }, $grp['quizzes']),
      'students'   => array_map(function($s) {
        return ['id'=>$s['id'],'scores'=>$s['scores']];
      }, $grp['students']),
    ];
  }, $grades_by_subject)) ?>;

  var grp = allData.find(function(g){ return g.subject_id == subjectId; });
  var st  = grp ? grp.students.find(function(s){ return s.id == studentId; }) : null;

  _deleteQuizData = [];
  var listHtml = '';

  if (grp && st) {
    grp.lessons.forEach(function(lesson) {
      lesson.quizzes.forEach(function(qz) {
        var sc = st.scores[qz.quiz_id];
        if (sc && sc.taken) {
          _deleteQuizData.push({ quiz_id: qz.quiz_id, label: lesson.lesson_title });
          var cls = sc.pct >= 90 ? 'var(--green)' : (sc.pct >= 75 ? 'var(--amber)' : 'var(--red)');
          var raw = (sc.raw == Math.floor(sc.raw)) ? Math.floor(sc.raw) : sc.raw;
          listHtml +=
            '<label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--border);border-radius:10px;cursor:pointer;background:var(--bg2);transition:background .15s;">' +
            '<input type="checkbox" class="quiz-delete-cb" value="' + qz.quiz_id + '" style="width:15px;height:15px;flex-shrink:0;cursor:pointer;accent-color:var(--accent);">' +
            '<div style="flex:1;min-width:0;">' +
                '<div style="font-size:13px;font-weight:600;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + (qz.description || 'Quiz') + '</div>' +
                '<div style="font-size:11px;color:var(--text3);margin-top:2px;">📖 ' + lesson.lesson_title + '</div>' +
            '</div>' +
            '<div style="text-align:right;flex-shrink:0;">' +
                '<div style="font-size:13px;font-weight:700;color:' + cls + '">' + raw + '/' + qz.question_count + '</div>' +
                '<div style="font-size:11px;color:' + cls + ';opacity:0.85">' + sc.pct + '%</div>' +
            '</div>' +
            '</label>';
        }
      });
    });
  }

  document.getElementById('deleteModalMsg').textContent = 'Results for ' + studentName;
  document.getElementById('deleteQuizList').innerHTML = listHtml || '<p style="color:var(--text3);font-size:12px;">No results found.</p>';
  document.getElementById('selectAllQuizzes').checked = false;
  document.getElementById('deleteResultModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function toggleSelectAll(cb) {
  document.querySelectorAll('.quiz-delete-cb').forEach(function(el){ el.checked = cb.checked; });
}

function closeDeleteModal() {
  document.getElementById('deleteResultModal').classList.remove('open');
  document.body.style.overflow = '';
}

function confirmDeleteResult() {
  var selected = Array.from(document.querySelectorAll('.quiz-delete-cb:checked')).map(function(el){ return parseInt(el.value); });
  if (!selected.length) { showToast('No quizzes selected', 'error'); return; }

  fetch('delete_quiz_results.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ student_id: _deleteStudentId, subject_id: _deleteSubjectId, quiz_ids: selected })
  })
  .then(r => r.json())
  .then(data => {
    closeDeleteModal();
    if (data.success) {
      showToast('Deleted ' + selected.length + ' result(s) for ' + _deleteStudentName, 'success');
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast(data.message || 'Delete failed', 'error');
    }
  })
  .catch(() => { closeDeleteModal(); showToast('Network error', 'error'); });
}

document.getElementById('deleteResultModal').addEventListener('click', function(e) {
  if (e.target === this) closeDeleteModal();
});

function showToast(msg, type) {
  var container = document.getElementById('toast-container');
  var toast = document.createElement('div');
  var bg = type === 'success' ? 'var(--green)' : type === 'error' ? 'var(--red)' : 'var(--accent)';
  toast.style.cssText = 'background:' + bg + ';color:#fff;padding:10px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-top:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);animation:fadeIn .2s ease;';
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(function(){ toast.remove(); }, 3000);
}

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