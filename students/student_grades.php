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

// Get section → strand
$section_row = $conn->query("SELECT * FROM tblsections WHERE section_id = $section_id")->fetch_assoc();
$strand_id = 0;
if ($section_row) {
    if (isset($section_row['strand_id']))        $strand_id = (int)$section_row['strand_id'];
    elseif (isset($section_row['strandid']))      $strand_id = (int)$section_row['strandid'];
}
$strand_label = 'N/A';
if ($strand_id) {
    $strand_row = $conn->query("SELECT strand FROM tblstrands WHERE strand_id = $strand_id")->fetch_assoc();
    if ($strand_row) $strand_label = htmlspecialchars($strand_row['strand']);
} elseif ($section_row) {
    $strand_label = htmlspecialchars($section_row['section_name'] ?? $section_row['section'] ?? 'N/A');
}

$student_grade   = '—';
$student_section = '—';
if ($section_row) {
    $student_section = $section_row['section'] ?? $section_row['section_name'] ?? '—';
    $grade_id = (int)($section_row['grade_level_id'] ?? $section_row['grade_id'] ?? 0);
    if ($grade_id) {
        $grade_row = $conn->query("SELECT grade_level FROM tblgrade_levels WHERE grade_id = $grade_id")->fetch_assoc();
        if ($grade_row) $student_grade = $grade_row['grade_level'];
    }
}
$student_pic      = $student['profile_pic'] ?? '';
$student_fullname = trim(
    ($student['firstname']  ?? '') . ' ' .
    ($student['middlename'] ?? '') . ' ' .
    ($student['lastname']   ?? '')
);
$student_initials = strtoupper(
    substr($student['firstname'] ?? 'S', 0, 1) .
    substr($student['lastname']  ?? 'T', 0, 1)
);

// Get all quiz results grouped by subject
$results_raw = $conn->query("
    SELECT 
        qr.id,
        qr.score,
        qr.created_at AS date_taken,
        q.quiz_id,
        q.question_count,
        q.passing_score,
        l.lesson_id,
        l.title AS lesson_title,
        l.teacher_subject_id,
        subj.subject_id,
        subj.subject AS subject_title
    FROM tblquiz_results qr
    JOIN tblquizzes q          ON q.quiz_id    = qr.quizzes_id
    JOIN tbl_teacher_lessons l ON l.lesson_id  = q.lesson_id
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = l.teacher_subject_id
    JOIN tblsubjects subj      ON subj.subject_id = ts.subject_id
    WHERE qr.student_id = $student_id
    ORDER BY subj.subject_id ASC, qr.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

// Group by subject — keep only BEST score per quiz, then average across quizzes
$subjectMap     = [];
$subjectQuizzes = []; // [subject_id][quiz_id] = best_score_row

foreach ($results_raw as $r) {
    $sid = (int)$r['subject_id'];
    $qid = (int)$r['quiz_id'];
    $subjectMap[$sid] = $r['subject_title'];

    $raw   = (float)$r['score'];
    $total = (float)$r['question_count'];
    $pct   = ($total > 0) ? round(($raw / $total) * 100, 1) : 0;

    // Keep best attempt per quiz
    if (!isset($subjectQuizzes[$sid][$qid]) || $pct > $subjectQuizzes[$sid][$qid]['pct']) {
        $subjectQuizzes[$sid][$qid] = [
            'quiz_id'       => $qid,
            'lesson_title'  => $r['lesson_title'],
            'score'         => $raw,
            'max'           => $total,
            'pct'           => $pct,
            'passing_score' => (int)$r['passing_score'],
            'date_sort'     => $r['date_taken'],
        ];
    }
}

// Build allGrades — one row per subject
$allGrades = [];
$unique_id = 1;

foreach ($subjectQuizzes as $sid => $quizzes) {
    $quiz_list   = array_values($quizzes);
    $quiz_count_subj = count($quiz_list);
    $total_pct   = array_sum(array_column($quiz_list, 'pct'));
    $avg_pct     = $quiz_count_subj > 0 ? round($total_pct / $quiz_count_subj, 1) : 0;

    // Passing: based on subject avg vs average passing score across quizzes
    $avg_passing = $quiz_count_subj > 0
        ? round(array_sum(array_column($quiz_list, 'passing_score')) / $quiz_count_subj)
        : 75;

    $total_score = array_sum(array_column($quiz_list, 'score'));
    $total_max   = array_sum(array_column($quiz_list, 'max'));

    // Latest quiz date for sorting
    $dates    = array_column($quiz_list, 'date_sort');
    rsort($dates);
    $last_date = $dates[0] ?? '';

    $allGrades[] = [
        'id'            => $unique_id++,
        'subject_id'    => $sid,
        'subject_title' => $subjectMap[$sid],
        'quiz_count'    => $quiz_count_subj,
        'total_score'   => $total_score,
        'total_max'     => $total_max,
        'pct'           => $avg_pct,
        'passing_score' => $avg_passing,
        'passed'        => $avg_pct >= $avg_passing,
        'date'          => date('M j, Y', strtotime($last_date)),
        'date_sort'     => $last_date,
        'quizzes'       => $quiz_list, // for modal detail
    ];
}

usort($allGrades, fn($a, $b) => strcmp($b['date_sort'], $a['date_sort']));

$total_count   = count($allGrades);
$avg_grade     = $total_count ? round(array_sum(array_column($allGrades, 'pct')) / $total_count, 1) : 0;
$highest       = $total_count ? max(array_column($allGrades, 'pct')) : 0;
$above90       = count(array_filter($allGrades, fn($g) => $g['pct'] >= 90));
$passing_count = count(array_filter($allGrades, fn($g) => $g['passed']));
$pass_rate     = $total_count ? round($passing_count / $total_count * 100) : 0;

$lowest_grade  = $total_count ? array_reduce($allGrades, fn($carry, $g) => ($carry === null || $g['pct'] < $carry['pct']) ? $g : $carry, null) : null;
$lowest        = $lowest_grade ? $lowest_grade['pct'] : 0;
$lowest_label  = $lowest_grade ? htmlspecialchars($lowest_grade['subject_title']) : '';
$highest = $total_count ? max(array_column($allGrades, 'pct')) : 0;
$above90      = count(array_filter($allGrades, fn($g) => $g['pct'] >= 90));
$passing_count = count(array_filter($allGrades, fn($g) => $g['passed']));
$pass_rate    = $total_count ? round($passing_count / $total_count * 100) : 0;


function getLetter($pct) {
    if($pct>=97) return 'A+'; if($pct>=93) return 'A'; if($pct>=90) return 'A-';
    if($pct>=87) return 'B+'; if($pct>=83) return 'B'; if($pct>=80) return 'B-';
    if($pct>=77) return 'C+'; if($pct>=73) return 'C'; if($pct>=70) return 'C-';
    if($pct>=67) return 'D+'; if($pct>=63) return 'D'; if($pct>=60) return 'D-';
    return 'F';
}
function getLetterColor($pct) {
    if($pct>=90) return 'var(--green)';
    if($pct>=80) return 'var(--accent)';
    if($pct>=70) return 'var(--amber)';
    return 'var(--red)';
}
function getLetterBg($pct) {
    if($pct>=90) return 'var(--green-bg)';
    if($pct>=80) return 'var(--accent-glow)';
    if($pct>=70) return 'var(--amber-bg)';
    return 'var(--red-bg)';
}
function getBarClass($pct) {
    if($pct>=90) return 'green-fill';
    if($pct>=80) return '';
    if($pct>=70) return 'amber-fill';
    return 'red-fill';
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
<title>ExceLearn LMS — Grades</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/student/student_grades.css">
<style>
/* Add to existing <style> block */
.grade-bar { width:100%; height:6px; background:var(--bg3,#2a2a3a); border-radius:99px; overflow:hidden; }
.grade-bar-fill { height:100%; border-radius:99px; background:var(--accent,#6366f1); transition:width .4s ease; }
.grade-bar-fill.green-fill { background:var(--green,#22c55e); }
.grade-bar-fill.amber-fill { background:var(--amber,#f59e0b); }
.grade-bar-fill.red-fill   { background:var(--red,#ef4444); }

/* ADD THESE: */
* { box-sizing: border-box; }
html, body { max-width: 100%; overflow-x: hidden; }
.main { min-width: 0; max-width: 100%; overflow-x: hidden; }
.content { min-width: 0; overflow-x: hidden; }
.summary-row { flex-wrap: wrap; }
.filter-bar { flex-wrap: wrap; }
.status-tabs { flex-wrap: wrap; }

/* REPLACE this: */
@media (max-width: 768px) {
  .grade-table-wrap { display: none; }
  .grade-card-list  { display: block; }
  .summary-row { gap: 8px; }
  .summary-card { min-width: calc(50% - 8px); flex: 1 1 calc(50% - 8px); }
  .filter-bar { gap: 8px; }
  .filter-bar .search-box,
  .filter-bar .filter-select { width: 100%; }
}

/* WITH this: */
@media (max-width: 768px) {
  .grade-table-wrap { display: none; }
  .grade-card-list  { display: block; }

  .summary-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
  }
  .summary-card {
    min-width: 0;
    flex: none;
    padding: 8px 10px;
    gap: 6px;
  }
  .summary-icon {
    font-size: 16px;
    width: 28px;
    height: 28px;
    min-width: 28px;
  }
  .summary-value { font-size: 0.95rem; }
  .summary-label { font-size: 0.6rem; }

  .filter-bar {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
  }
  .filter-bar .search-box {
    grid-column: 1 / -1;  /* search spans full width */
    width: 100%;
  }
  .filter-bar .filter-select {
    width: 100%;
    font-size: 0.75rem;
    padding: 6px 8px;
  }

  .status-tabs { gap: 4px; }
  .status-tab  { font-size: 0.7rem; padding: 5px 8px; }
}

@media (max-width: 400px) {
  .summary-row { grid-template-columns: repeat(2, 1fr); }
  .filter-bar  { grid-template-columns: 1fr; }
  .filter-bar .search-box { grid-column: 1; }
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
      Virtual Class
    </a>
    <div class="nav-section-label" style="margin-top:16px">Progress</div>
    <a class="nav-item active" href="student_grades.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Grades
    </a>
    <div class="nav-section-label" style="margin-top:16px">Account</div>

    <a class="nav-item" href="student_profile.php">
    <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    My Profile
    </a>

    <!-- NEW BUTTON -->
    <a class="nav-item" href="settings.php">
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
      <?php if ($student_pic): ?>
        <img src="../uploads/profile_pic/students/<?= htmlspecialchars($student_pic) ?>"
             alt="Profile"
             style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid rgba(255,255,255,0.15); background:linear-gradient(135deg,var(--cyan),var(--accent));overflow:hidden;padding:0"
             onerror="this.style.display='none';document.getElementById('grades-initials-fallback').style.display='flex';">
        <div id="grades-initials-fallback"
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
  <div style="margin-bottom:5px">
    <div class="section-title">Grades</div>
    <div class="section-sub">View all your assessment scores, track your performance, and review detailed breakdowns.</div>
  </div>

  <!-- SUMMARY CARDS — pure PHP -->
  <div class="summary-row">
    <div class="summary-card">
      <div class="summary-icon" style="background:var(--accent-glow)">📊</div>
      <div><div class="summary-value"><?= $avg_grade ?>%</div><div class="summary-label">Average Grade</div></div>
    </div>
    <div class="summary-card">
      <div class="summary-icon" style="background:var(--green-bg)">🏆</div>
      <div><div class="summary-value"><?= $highest ?>%</div><div class="summary-label">Highest Score</div></div>
    </div>
    <div class="summary-card">
      <div class="summary-icon" style="background:var(--purple-bg)">🌟</div>
      <div><div class="summary-value"><?= $above90 ?></div><div class="summary-label">90% & Above (<?= $total_count > 0 ? round($above90/$total_count*100) : 0 ?>%)</div></div>
    </div>
    <div class="summary-card">
      <div class="summary-icon" style="background:var(--cyan-bg)">✅</div>
      <div><div class="summary-value"><?= $pass_rate ?>%</div><div class="summary-label">Pass Rate (<?= $passing_count ?>/<?= $total_count ?>)</div></div>
    </div>
    <div class="summary-card">
      <div class="summary-icon" style="background:var(--amber-bg)">⚠️</div>
      <div>
        <div class="summary-value"><?= $lowest ?>%</div>
        <div class="summary-label">Lowest Grade</div>
        <?php if ($lowest_label): ?>
          <div style="font-size:10px;color:var(--text3);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:120px" title="<?= $lowest_label ?>"><?= $lowest_label ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- FILTER BAR -->
  <div class="filter-bar">
    <div class="search-box">
      <span class="search-icon">🔍</span>
      <input type="text" id="searchInput" placeholder="Search assessments..." oninput="filterGrades()">
    </div>
    <select class="filter-select" id="courseFilter" onchange="filterGrades()">
      <option value="all">All Subjects</option>
      <?php foreach ($subjectMap as $sid => $sname): ?>
        <option value="<?= $sid ?>"><?= htmlspecialchars($sname) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="filter-select" id="sortSelect" onchange="filterGrades()">
      <option value="default">Sort: Default (Newest)</option>
      <option value="course">Group by Subject</option>
      <option value="score-desc">Score: High → Low</option>
      <option value="score-asc">Score: Low → High</option>
      <option value="name-asc">Name: A → Z</option>
    </select>
  </div>

  <!-- STATUS TABS -->
  <?php
    $tab_counts = [
      'all'     => $total_count,
      '90plus'  => $above90,
      '80plus'  => count(array_filter($allGrades, fn($g) => $g['pct'] >= 80 && $g['pct'] < 90)),
      'below80' => count(array_filter($allGrades, fn($g) => $g['pct'] < 80)),
    ];
    $tab_labels = [
      'all'     => 'All Grades',
      '90plus'  => '🌟 90% & Above',
      '80plus'  => '👍 80–89%',
      'below80' => '⚠️ Below 80%',
    ];
  ?>
  <div class="status-tabs" id="statusTabs">
    <?php foreach ($tab_labels as $key => $label): ?>
      <button class="status-tab <?= $key === 'all' ? 'active' : '' ?>"
              data-status="<?= $key ?>"
              onclick="setStatus('<?= $key ?>')">
        <?= $label ?> (<?= $tab_counts[$key] ?>)
      </button>
    <?php endforeach; ?>
  </div>

  <!-- DESKTOP TABLE -->
  <div class="grade-table-wrap">
    <div class="grade-table-header">
      <div class="card-title">All Grades</div>
      <div id="tableCount" style="font-size:12px;color:var(--text3)"><?= $total_count ?> assessments · Avg: <?= $avg_grade ?>%</div>
    </div>
    <div style="overflow-x:auto;max-width:100%">
      <table class="grade-table" id="gradeTable">
        <thead>
          <tr><th>Subject</th><th>Quizzes Taken</th><th>Total Score</th><th>Average Grade</th><th>Status</th><th>Last Activity</th></tr>
        </thead>
        <tbody id="gradeTableBody">
          <?php foreach ($allGrades as $g):
    $pct      = $g['pct'];
    $letter   = getLetter($pct);
    $lcolor   = getLetterColor($pct);
    $lbg      = getLetterBg($pct);
    $barclass = getBarClass($pct);
    $icon     = $subject_icon_map[$g['subject_id']] ?? '📚';
?>
<tr class="grade-row-item"
    data-course="<?= $g['subject_id'] ?>"
    data-score="<?= $pct ?>"
    data-name="<?= htmlspecialchars(strtolower($g['subject_title'])) ?>"
    data-subject="<?= htmlspecialchars(strtolower($g['subject_title'])) ?>"
    data-date="<?= $g['date_sort'] ?>"
    onclick="openModal(<?= $g['id'] ?>)">
  <td>
    <div class="grade-assess-name"><?= $icon ?> <?= htmlspecialchars($g['subject_title']) ?></div>
    <div class="grade-course-name"><?= $g['quiz_count'] ?> quiz<?= $g['quiz_count'] > 1 ? 'zes' : '' ?> averaged</div>
  </td>
  <td style="text-align:center;font-size:13px;color:var(--text2)"><?= $g['quiz_count'] ?></td>
  <td style="font-size:13px;color:var(--text2)"><?= (int)$g['total_score'] ?>/<?= (int)$g['total_max'] ?></td>
  <td>
    <div class="grade-score-cell">
      <span class="grade-score-num" style="color:<?= $lcolor ?>"><?= $pct ?>%</span>
      <div class="grade-bar-wrap" style="min-width:60px;width:100%">
        <div class="grade-bar"><div class="grade-bar-fill <?= $barclass ?>" style="width:<?= $pct ?>%"></div></div>
      </div>
    </div>
  </td>
  <td style="text-align:center">
    <span class="grade-letter" style="background:<?= $lbg ?>;color:<?= $lcolor ?>"><?= $letter ?></span>
  </td>
  <td class="grade-date"><?= $g['date'] ?></td>
</tr>
<?php endforeach; ?>
          <?php if (empty($allGrades)): ?>
          <tr><td colspan="6" style="text-align:center;padding:48px 20px;color:var(--text3)">
            <div style="font-size:40px;margin-bottom:10px">📊</div>No grades yet.
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- MOBILE CARDS -->
  <div class="grade-card-list" id="cardList">
    <?php foreach ($allGrades as $g):
    $pct      = $g['pct'];
    $letter   = getLetter($pct);
    $lcolor   = getLetterColor($pct);
    $lbg      = getLetterBg($pct);
    $barclass = getBarClass($pct);
    $icon     = $subject_icon_map[$g['subject_id']] ?? '📚';
?>
<div class="grade-card grade-card-item"
     data-course="<?= $g['subject_id'] ?>"
     data-score="<?= $pct ?>"
     data-name="<?= htmlspecialchars(strtolower($g['subject_title'])) ?>"
     data-subject="<?= htmlspecialchars(strtolower($g['subject_title'])) ?>"
     data-date="<?= $g['date_sort'] ?>"
     onclick="openModal(<?= $g['id'] ?>)">
  <div class="grade-card-top">
    <span class="grade-card-name"><?= $icon ?> <?= htmlspecialchars($g['subject_title']) ?></span>
    <span class="grade-card-letter" style="background:<?= $lbg ?>;color:<?= $lcolor ?>"><?= $letter ?></span>
  </div>
  <div class="grade-card-meta">
    <span><?= $g['quiz_count'] ?> quiz<?= $g['quiz_count'] > 1 ? 'zes' : '' ?></span>
    <span><?= $g['date'] ?></span>
  </div>
  <div class="grade-card-bar"><div class="grade-bar"><div class="grade-bar-fill <?= $barclass ?>" style="width:<?= $pct ?>%"></div></div></div>
  <div class="grade-card-bottom">
    <span class="grade-card-score" style="color:<?= $lcolor ?>"><?= $pct ?>% avg</span>
    <span class="grade-card-raw"><?= (int)$g['total_score'] ?> / <?= (int)$g['total_max'] ?> pts</span>
  </div>
</div>
<?php endforeach; ?>
    <?php if (empty($allGrades)): ?>
    <div class="empty-state"><div class="empty-icon">📊</div><p>No grades found</p></div>
    <?php endif; ?>
  </div>

</div>
</main>
</div>

<div class="modal-backdrop" id="modalBackdrop" onclick="closeModalOutside(event)">
  <div class="modal" id="gradeModal">
    <div class="modal-header-banner" id="modalBanner">
      <span class="modal-banner-icon" id="modalBannerIcon"></span>
      <div class="banner-overlay"></div>
      <!--<button class="modal-close" onclick="closeModal()">✕</button>-->
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
<!-- ADD before </body> -->
<div class="modal-backdrop" id="logoutModal" onclick="if(event.target===this){closeLogoutModal()}">
  <div class="modal" style="max-width:380px;padding:0;overflow:hidden">
    <div style="padding:28px 28px 20px;text-align:center">
      <div style="width:56px;height:56px;border-radius:50%;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px">🚪</div>
      <div style="font-size:1.15rem;font-weight:700;margin-bottom:8px">Log out?</div>
      <div style="font-size:0.875rem;color:var(--text2)">Are you sure you want to log out?</div>
    </div>
    <div style="display:flex;gap:10px;padding:0 28px 24px">
      <button class="btn btn-ghost" style="flex:1" onclick="closeLogoutModal()">Cancel</button>
      <button class="btn" style="flex:1;background:var(--red);color:#fff;border:none" onclick="window.location.href='../logout.php'">Yes, Log out</button>
    </div>
  </div>
</div>

<script>
function confirmLogout(e){ e.preventDefault(); document.getElementById('logoutModal').classList.add('open'); document.body.style.overflow='hidden'; }
function closeLogoutModal(){ document.getElementById('logoutModal').classList.remove('open'); document.body.style.overflow=''; }

// Grade data for modal (PHP-generated)
var gradeData = <?php
    $modal_data = [];
    foreach ($allGrades as $g) {
        $modal_data[$g['id']] = [
            'id'           => $g['id'],
            'subject'      => $g['subject_title'],
            'quiz_count'   => $g['quiz_count'],
            'total_score'  => $g['total_score'],
            'total_max'    => $g['total_max'],
            'pct'          => $g['pct'],
            'passingScore' => $g['passing_score'],
            'passed'       => $g['passed'],
            'date'         => $g['date'],
            'quizzes'      => $g['quizzes'],
        ];
    }
    echo json_encode($modal_data);
?>;

function openModal(id) {
    var g = gradeData[id];
    if (!g) return;
    var pct    = g.pct;
    var letter = getLetter(pct);
    var lColor = getColor(pct);
    var lBg    = getBg(pct);
    var barCls = getBar(pct);

    document.getElementById('modalBanner').className = 'modal-header-banner banner-science';
    document.getElementById('modalBannerIcon').textContent = '📚';

    // Build per-quiz breakdown rows
    var breakdownRows = '';
    if (g.quizzes && g.quizzes.length) {
        breakdownRows = '<div style="margin:16px 0 8px;font-size:0.8rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em">Quiz Breakdown</div>';
        g.quizzes.forEach(function(q, i) {
            var qColor = getColor(q.pct);
            var qBar   = getBar(q.pct);
            breakdownRows +=
                '<div style="margin-bottom:10px;padding:10px 12px;background:var(--bg2);border-radius:8px;border:1px solid var(--border)">' +
                    '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">' +
                        '<span style="font-size:0.82rem;font-weight:600;color:var(--text1)">' + (i+1) + '. ' + q.lesson_title + '</span>' +
                        '<span style="font-size:0.82rem;font-weight:700;color:' + qColor + '">' + q.pct + '%</span>' +
                    '</div>' +
                    '<div class="grade-bar" style="height:5px"><div class="grade-bar-fill ' + qBar + '" style="width:' + q.pct + '%"></div></div>' +
                    '<div style="font-size:0.75rem;color:var(--text3);margin-top:4px">' + Math.floor(q.score) + '/' + Math.floor(q.max) + ' pts · Best attempt</div>' +
                '</div>';
        });
    }

    document.getElementById('modalBody').innerHTML =
        '<div class="modal-grade-title">' + g.subject + '</div>' +
        '<div class="modal-grade-meta">' +
            '<span class="modal-grade-meta-item">📝 ' + g.quiz_count + ' quiz' + (g.quiz_count > 1 ? 'zes' : '') + ' averaged</span>' +
            '<span class="modal-grade-meta-item">📅 Last: ' + g.date + '</span>' +
            '<span class="modal-grade-meta-item">🎯 Passing: ' + g.passingScore + '%</span>' +
        '</div>' +
        '<div class="modal-score-hero">' +
            '<div class="modal-score-big" style="color:' + lColor + '">' + pct + '%</div>' +
            '<div class="modal-score-letter" style="color:' + lColor + '">Subject Grade: ' + letter + '</div>' +
            '<div class="modal-score-sub">' + Math.floor(g.total_score) + '/' + Math.floor(g.total_max) + ' total pts · ' +
                (g.passed
                    ? '<span style="color:var(--green)">✅ Passing</span>'
                    : '<span style="color:var(--red)">❌ Below passing</span>') +
            '</div>' +
        '</div>' +
        '<div style="margin-bottom:14px"><div class="grade-bar" style="height:10px"><div class="grade-bar-fill ' + barCls + '" style="width:' + pct + '%"></div></div></div>' +
        breakdownRows +
        '<div class="modal-actions-row"><button class="btn btn-ghost" onclick="closeModal()">Close</button></div>';

    document.getElementById('modalBackdrop').classList.add('open');
    document.body.style.overflow = 'hidden';
}

var subjectIconMap = <?= json_encode($subject_icon_map ?? []) ?>;

/* ========== FILTERING ========== */
var activeStatus = 'all';

function setStatus(key) {
    activeStatus = key;
    document.querySelectorAll('.status-tab').forEach(function(btn) {
        btn.classList.toggle('active', btn.dataset.status === key);
    });
    filterGrades();
}

function filterGrades() {
    var query     = document.getElementById('searchInput').value.toLowerCase();
    var courseVal = document.getElementById('courseFilter').value;
    var sort      = document.getElementById('sortSelect').value;

    var rows  = Array.from(document.querySelectorAll('.grade-row-item'));
    var cards = Array.from(document.querySelectorAll('.grade-card-item'));

    function matches(el) {
        var course  = el.dataset.course;
        var score   = parseFloat(el.dataset.score);
        var name    = el.dataset.name;
        var subject = el.dataset.subject;

        var matchCourse = courseVal === 'all' || course === courseVal;
        var matchSearch = name.indexOf(query) !== -1 || subject.indexOf(query) !== -1;
        var matchStatus = activeStatus === 'all' ||
            (activeStatus === '90plus'  && score >= 90) ||
            (activeStatus === '80plus'  && score >= 80 && score < 90) ||
            (activeStatus === 'below80' && score < 80);

        return matchCourse && matchSearch && matchStatus;
    }

    function sortEls(els) {
        return els.slice().sort(function(a, b) {
            var sa = parseFloat(a.dataset.score), sb = parseFloat(b.dataset.score);
            var da = a.dataset.date, db = b.dataset.date;
            var na = a.dataset.name, nb = b.dataset.name;
            if (sort === 'score-desc') return sb - sa;
            if (sort === 'score-asc')  return sa - sb;
            if (sort === 'name-asc')   return na.localeCompare(nb);
            if (sort === 'course')     return parseInt(a.dataset.course) - parseInt(b.dataset.course) || db.localeCompare(da);
            // default: newest
            return db.localeCompare(da);
        });
    }

    // Apply to table rows
    var tbody = document.getElementById('gradeTableBody');
    var visibleRows = sortEls(rows.filter(matches));
    var hiddenRows  = rows.filter(function(r){ return !matches(r); });
    hiddenRows.forEach(function(r){ r.style.display = 'none'; });
    visibleRows.forEach(function(r){ r.style.display = ''; tbody.appendChild(r); });

    // Apply to cards
    var cardList = document.getElementById('cardList');
    var visibleCards = sortEls(cards.filter(matches));
    var hiddenCards  = cards.filter(function(c){ return !matches(c); });
    hiddenCards.forEach(function(c){ c.style.display = 'none'; });
    visibleCards.forEach(function(c){ c.style.display = ''; cardList.appendChild(c); });

    // Update count
    var avg = visibleRows.length
        ? Math.round(visibleRows.reduce(function(s,r){ return s + parseFloat(r.dataset.score); }, 0) / visibleRows.length)
        : 0;
    document.getElementById('tableCount').textContent = visibleRows.length + ' assessments · Avg: ' + avg + '%';
}

/* ========== MODAL ========== */
function getLetter(pct){
    if(pct>=97)return'A+';if(pct>=93)return'A';if(pct>=90)return'A-';
    if(pct>=87)return'B+';if(pct>=83)return'B';if(pct>=80)return'B-';
    if(pct>=77)return'C+';if(pct>=73)return'C';if(pct>=70)return'C-';
    if(pct>=67)return'D+';if(pct>=63)return'D';if(pct>=60)return'D-';
    return'F';
}
function getColor(pct){ return pct>=90?'var(--green)':pct>=80?'var(--accent)':pct>=70?'var(--amber)':'var(--red)'; }
function getBg(pct)   { return pct>=90?'var(--green-bg)':pct>=80?'var(--accent-glow)':pct>=70?'var(--amber-bg)':'var(--red-bg)'; }
function getBar(pct)  { return pct>=90?'green-fill':pct>=80?'':pct>=70?'amber-fill':'red-fill'; }

// function openModal(id) {
//     var g = gradeData[id];
//     if (!g) return;
//     var pct    = g.pct;
//     var letter = getLetter(pct);
//     var lColor = getColor(pct);
//     var lBg    = getBg(pct);
//     var barCls = getBar(pct);
//     var icon   = subjectIconMap[g.subject] || '📚';

//     document.getElementById('modalBanner').className = 'modal-header-banner banner-science';
//     document.getElementById('modalBannerIcon').textContent = icon;

//     document.getElementById('modalBody').innerHTML =
//         '<div class="modal-grade-title">' + g.name + '</div>' +
//         '<div class="modal-grade-meta">' +
//             '<span class="modal-grade-meta-item">📚 ' + g.subject + '</span>' +
//             '<span class="modal-grade-meta-item">📖 ' + g.lesson + '</span>' +
//             '<span class="modal-grade-meta-item">📅 ' + g.date + '</span>' +
//             '<span class="modal-grade-meta-item">🎯 Passing: ' + g.passingScore + '%</span>' +
//             '<span class="modal-grade-meta-item">🔁 Take #' + g.take + '</span>' +
//         '</div>' +
//         '<div class="modal-score-hero">' +
//             '<div class="modal-score-big" style="color:' + lColor + '">' + pct + '%</div>' +
//             '<div class="modal-score-letter" style="color:' + lColor + '">Grade: ' + letter + '</div>' +
//             '<div class="modal-score-sub">' + Math.floor(g.score) + '/' + Math.floor(g.max) + ' points · ' +
//                 (g.passed
//                     ? '<span style="color:var(--green)">✅ Passed</span>'
//                     : '<span style="color:var(--red)">❌ Below passing</span>') +
//             '</div>' +
//         '</div>' +
//         '<div class="modal-stats-grid">' +
//           '<div class="modal-stat-card"><div class="modal-stat-val" style="color:' + lColor + '">' + Math.floor(g.score) + '/' + Math.floor(g.max) + '</div><div class="modal-stat-lbl">Score</div></div>' +
//             '<div class="modal-stat-card"><div class="modal-stat-val" style="color:' + lColor + '">' + pct + '%</div><div class="modal-stat-lbl">Quiz Grade</div></div>' +
//             '<div class="modal-stat-card"><div class="modal-stat-val">' + letter + '</div><div class="modal-stat-lbl">Letter Grade</div></div>' +
//         '</div>' +
//         '<div style="margin-bottom:14px"><div class="grade-bar" style="height:10px"><div class="grade-bar-fill ' + barCls + '" style="width:' + pct + '%"></div></div></div>' +
//         '<div class="modal-actions-row">' +
//             '<button class="btn btn-ghost" onclick="closeModal()">Close</button>' +
//         '</div>';

//     document.getElementById('modalBackdrop').classList.add('open');
//     document.body.style.overflow = 'hidden';
// }

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}
function closeModalOutside(e){ if(e.target===document.getElementById('modalBackdrop')) closeModal(); }
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeModal(); });

/* ========== SIDEBAR ========== */
function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebar-overlay').classList.toggle('open'); }
function closeSidebar()  { document.getElementById('sidebar').classList.remove('open');  document.getElementById('sidebar-overlay').classList.remove('open'); }

/* ========== LOGOUT ========== */
function confirmLogout(e){ e.preventDefault(); document.getElementById('logoutModal').classList.add('open'); document.body.style.overflow='hidden'; }
function closeLogoutModal(){ document.getElementById('logoutModal').classList.remove('open'); document.body.style.overflow=''; }

function applyTheme(theme) {
  var isLight = theme === 'light';
  document.body.classList.toggle('light', isLight);
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
function showToast(msg,type){
    type=type||'success';
    var icons={success:'✅',error:'❌',info:'ℹ️'};
    var container=document.getElementById('toast-container');
    var t=document.createElement('div');
    t.className='toast '+type;
    t.innerHTML='<span class="toast-icon">'+(icons[type]||'✅')+'</span><span>'+msg+'</span>';
    container.appendChild(t);
    setTimeout(function(){ t.style.opacity='0'; t.style.transform='translateX(60px)'; t.style.transition='all .3s ease'; setTimeout(function(){ t.remove(); },300); },3000);
}

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