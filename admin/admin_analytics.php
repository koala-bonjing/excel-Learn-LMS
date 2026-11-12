<?php
session_start();
include('../connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = 'Admin';
$admin_pic  = null;
$initials   = 'AD';

$stmtU = $conn->prepare("SELECT firstname, lastname, profile_pic FROM tblusers WHERE id = ? LIMIT 1");
$stmtU->bind_param("i", $admin_id);
$stmtU->execute();
$uRow = $stmtU->get_result()->fetch_assoc();
$stmtU->close();
if ($uRow) {
    $admin_name = trim($uRow['firstname'] . ' ' . $uRow['lastname']);
    $initials   = strtoupper(substr($uRow['firstname'], 0, 1) . substr($uRow['lastname'], 0, 1));
    if (!empty($uRow['profile_pic']))
        $admin_pic = '../uploads/profile_pic/admin/' . $uRow['profile_pic'];
}

// ── Theme ─────────────────────────────────────────────────────────────────
$themeStmt = $conn->prepare("SELECT theme FROM tblteacher_settings WHERE teacher_id = ? LIMIT 1");
$themeStmt->bind_param("i", $admin_id);
$themeStmt->execute();
$themeRow  = $themeStmt->get_result()->fetch_assoc();
$themeStmt->close();
$savedTheme = $themeRow['theme'] ?? 'dark';

// ── Nav badge counts ───────────────────────────────────────────────────────
$teacherCount  = (int)$conn->query("SELECT COUNT(*) FROM tblusers WHERE role='teacher'")->fetch_row()[0];
$studentCount  = (int)$conn->query("SELECT COUNT(*) FROM tblstudents")->fetch_row()[0];
$strandCount   = (int)$conn->query("SELECT COUNT(*) FROM tblstrands")->fetch_row()[0];
$sectionCount  = (int)$conn->query("SELECT COUNT(*) FROM tblsections")->fetch_row()[0];
$subjectCount  = (int)$conn->query("SELECT COUNT(*) FROM tblsubjects")->fetch_row()[0];

// ══════════════════════════════════════════════════════════════════════════
//  ANALYTICS DATA FROM DB
// ══════════════════════════════════════════════════════════════════════════

// 1. Mini stats
$totalQuizAttempts = (int)$conn->query("SELECT COUNT(*) FROM tblquiz_results")->fetch_row()[0];
$totalQuizzes      = (int)$conn->query("SELECT COUNT(*) FROM tblquizzes")->fetch_row()[0];
$totalLessons      = (int)$conn->query("SELECT COUNT(*) FROM tbl_teacher_lessons")->fetch_row()[0];
$overallAvg        = (float)($conn->query("SELECT ROUND(AVG(score),2) FROM tblquiz_results")->fetch_row()[0] ?? 0);

$passCount = (int)$conn->query("
    SELECT COUNT(*) FROM tblquiz_results qr
    JOIN tblquizzes q ON q.quiz_id = qr.quizzes_id
    WHERE qr.score >= q.passing_score
")->fetch_row()[0];
$failCount = $totalQuizAttempts - $passCount;
$passRate  = $totalQuizAttempts > 0 ? round($passCount / $totalQuizAttempts * 100, 1) : 0;

// 2. Enrollment stats
$enrollStats = $conn->query("
    SELECT COUNT(*) AS total,
           SUM(CASE WHEN gender='Male'   THEN 1 ELSE 0 END) AS male,
           SUM(CASE WHEN gender='Female' THEN 1 ELSE 0 END) AS female,
           SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END) AS active
    FROM tblstudents
")->fetch_assoc();

// 3. Grade / score distribution buckets (0–59, 60–74, 75–84, 85–94, 95–100)
$distBuckets = ['0–59' => 0, '60–74' => 0, '75–84' => 0, '85–94' => 0, '95–100' => 0];
$distRes = $conn->query("SELECT score FROM tblquiz_results");
while ($dr = $distRes->fetch_assoc()) {
    $s = (float)$dr['score'];
    if      ($s < 60)  $distBuckets['0–59']++;
    elseif  ($s < 75)  $distBuckets['60–74']++;
    elseif  ($s < 85)  $distBuckets['75–84']++;
    elseif  ($s < 95)  $distBuckets['85–94']++;
    else               $distBuckets['95–100']++;
}
$distLabels = array_keys($distBuckets);
$distValues = array_values($distBuckets);
$distColors = ['#ff6b6b','#f5a623','#22d3ee','#22c55e','#3dd68c'];

// 4. Avg score per strand (for bar chart)
$strandChart = ['labels' => [], 'values' => [], 'students' => []];
$res = $conn->query("
    SELECT st.strand,
           COUNT(DISTINCT ts2.student_id) AS students,
           ROUND(AVG(qr.score), 2)        AS avg_score
    FROM tblstrands st
    LEFT JOIN tblsubjects sub ON sub.strand_id = st.strand_id
    LEFT JOIN tbl_teacher_subjects ts ON ts.subject_id = sub.subject_id
    LEFT JOIN tbl_teacher_lessons tl ON tl.teacher_subject_id = ts.teacher_subject_id
    LEFT JOIN tblquizzes q ON q.lesson_id = tl.lesson_id
    LEFT JOIN tblquiz_results qr ON qr.quizzes_id = q.quiz_id
    LEFT JOIN tbl_teachers_students ts2 ON ts2.teacher_subject_id = ts.teacher_subject_id
    GROUP BY st.strand_id
    ORDER BY students DESC
    LIMIT 10
");
while ($r = $res->fetch_assoc()) {
    $strandChart['labels'][]   = $r['strand'];
    $strandChart['values'][]   = (float)($r['avg_score'] ?? 0);
    $strandChart['students'][] = (int)$r['students'];
}

// 5. Avg score per subject (for horizontal bar)
$subjectChart = ['labels' => [], 'values' => [], 'pass_rates' => []];
$res = $conn->query("
    SELECT sub.subject,
           ROUND(AVG(qr.score), 2)  AS avg_score,
           ROUND(100 * SUM(CASE WHEN qr.score >= q.passing_score THEN 1 ELSE 0 END) / NULLIF(COUNT(qr.id),0), 1) AS pass_rate
    FROM tblsubjects sub
    JOIN tbl_teacher_subjects ts ON ts.subject_id = sub.subject_id
    JOIN tbl_teacher_lessons tl ON tl.teacher_subject_id = ts.teacher_subject_id
    JOIN tblquizzes q ON q.lesson_id = tl.lesson_id
    JOIN tblquiz_results qr ON qr.quizzes_id = q.quiz_id
    GROUP BY sub.subject_id
    ORDER BY avg_score DESC
    LIMIT 10
");
while ($r = $res->fetch_assoc()) {
    $subjectChart['labels'][]     = $r['subject'];
    $subjectChart['values'][]     = (float)$r['avg_score'];
    $subjectChart['pass_rates'][] = (float)$r['pass_rate'];
}

// 6. Subject fail rate (difficulty) — bottom performers
$difficultyChart = ['labels' => [], 'values' => []];
$res = $conn->query("
    SELECT sub.subject,
           ROUND(100 * SUM(CASE WHEN qr.score < q.passing_score THEN 1 ELSE 0 END) / NULLIF(COUNT(qr.id),0), 1) AS fail_rate
    FROM tblsubjects sub
    JOIN tbl_teacher_subjects ts ON ts.subject_id = sub.subject_id
    JOIN tbl_teacher_lessons tl ON tl.teacher_subject_id = ts.teacher_subject_id
    JOIN tblquizzes q ON q.lesson_id = tl.lesson_id
    JOIN tblquiz_results qr ON qr.quizzes_id = q.quiz_id
    GROUP BY sub.subject_id
    HAVING COUNT(qr.id) > 0
    ORDER BY fail_rate DESC
    LIMIT 8
");
while ($r = $res->fetch_assoc()) {
    $difficultyChart['labels'][] = $r['subject'];
    $difficultyChart['values'][] = (float)$r['fail_rate'];
}

// 7. Top students
$topStudents = [];
$res = $conn->query("
    SELECT s.id, s.firstname, s.lastname, s.gender,
           ROUND(AVG(qr.score), 2) AS avg_score,
           COUNT(qr.id)            AS quizzes_taken,
           MAX(qr.score)           AS highest_score,
           st.strand,
           sec.section
    FROM tblstudents s
    JOIN tblquiz_results qr ON qr.student_id = s.id
    JOIN tblquizzes q       ON q.quiz_id = qr.quizzes_id
    JOIN tbl_teacher_lessons tl ON tl.lesson_id = q.lesson_id
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = tl.teacher_subject_id
    JOIN tblsubjects sub ON sub.subject_id = ts.subject_id
    JOIN tblstrands st   ON st.strand_id = sub.strand_id
    LEFT JOIN tblsections sec ON sec.section_id = s.section_id
    GROUP BY s.id
    ORDER BY avg_score DESC
    LIMIT 20
");
$rank = 1;
while ($r = $res->fetch_assoc()) {
    $r['rank'] = $rank++;
    $topStudents[] = $r;
}

// 8. Quiz attempts over time (last 8 months)
$enrollmentTrend = ['labels' => [], 'values' => []];
$res = $conn->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS month_label,
           DATE_FORMAT(created_at, '%Y-%m') AS month_key,
           COUNT(*) AS attempts
    FROM tblquiz_results
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 8 MONTH)
    GROUP BY month_key
    ORDER BY month_key ASC
");
while ($r = $res->fetch_assoc()) {
    $enrollmentTrend['labels'][] = $r['month_label'];
    $enrollmentTrend['values'][] = (int)$r['attempts'];
}
// fallback if no date data
if (empty($enrollmentTrend['labels'])) {
    $enrollmentTrend = ['labels' => ['N/A'], 'values' => [$totalQuizAttempts]];
}

// 9. Section enrollment for stacked/comparison chart
$sectionData = ['labels' => [], 'values' => []];
$res = $conn->query("
    SELECT sec.section, COUNT(s.id) AS enrolled
    FROM tblsections sec
    LEFT JOIN tblstudents s ON s.section_id = sec.section_id
    GROUP BY sec.section_id
    ORDER BY enrolled DESC
    LIMIT 8
");
while ($r = $res->fetch_assoc()) {
    $sectionData['labels'][] = $r['section'];
    $sectionData['values'][] = (int)$r['enrolled'];
}

// 10. Insights — dynamically generated from real data
$insights = [];

// Pass rate insight
if ($passRate >= 80) {
    $insights[] = ['icon' => '✅', 'text' => "<strong>Strong Pass Rate:</strong> The overall quiz pass rate is <strong>{$passRate}%</strong>, reflecting solid student performance across subjects."];
} else {
    $insights[] = ['icon' => '⚠️', 'text' => "<strong>Pass Rate Alert:</strong> The overall quiz pass rate stands at <strong>{$passRate}%</strong>. Consider reviewing subjects with high fail rates and offering additional support."];
}

// Average score insight
$insights[] = ['icon' => '📊', 'text' => "<strong>Average Score:</strong> Students average <strong>{$overallAvg}%</strong> across all quizzes. " . ($overallAvg >= 75 ? "This is above the standard passing threshold — great performance!" : "This is below the 75% passing threshold. Curriculum reinforcement may be needed.")];

// Top strand insight
if (!empty($strandChart['labels'])) {
    $topStrand = $strandChart['labels'][0];
    $topStudentsStrand = $strandChart['students'][0];
    $insights[] = ['icon' => '🎓', 'text' => "<strong>Most Enrolled Strand:</strong> <strong>{$topStrand}</strong> leads enrollment with <strong>{$topStudentsStrand}</strong> students currently assigned."];
}

// Difficult subject insight
if (!empty($difficultyChart['labels'])) {
    $hardestSubject = $difficultyChart['labels'][0];
    $highestFail    = $difficultyChart['values'][0];
    $insights[] = ['icon' => '📉', 'text' => "<strong>Highest Fail Rate:</strong> <strong>{$hardestSubject}</strong> has the highest fail rate at <strong>{$highestFail}%</strong>. Targeted tutoring or remedial sessions are recommended."];
}

// Top student insight
if (!empty($topStudents)) {
    $ts = $topStudents[0];
    $tsName  = htmlspecialchars($ts['firstname'] . ' ' . $ts['lastname']);
    $tsScore = $ts['avg_score'];
    $insights[] = ['icon' => '🏅', 'text' => "<strong>Top Performer:</strong> <strong>{$tsName}</strong> leads all students with an average quiz score of <strong>{$tsScore}%</strong>."];
}

// Quiz activity insight
$insights[] = ['icon' => '📝', 'text' => "<strong>Quiz Activity:</strong> A total of <strong>" . number_format($totalQuizAttempts) . " quiz attempts</strong> have been recorded across <strong>{$totalQuizzes}</strong> quizzes and <strong>{$totalLessons}</strong> lessons."];

// Gender ratio insight
$male   = (int)($enrollStats['male'] ?? 0);
$female = (int)($enrollStats['female'] ?? 0);
$total  = (int)($enrollStats['total'] ?? 0);
if ($total > 0) {
    $malePct   = round($male / $total * 100, 1);
    $femalePct = round($female / $total * 100, 1);
    $insights[] = ['icon' => '👥', 'text' => "<strong>Gender Distribution:</strong> Of <strong>{$total}</strong> enrolled students, <strong>{$malePct}%</strong> are male and <strong>{$femalePct}%</strong> are female."];
}

// Pass/Fail count insight
$insights[] = ['icon' => '🎯', 'text' => "<strong>Quiz Outcomes:</strong> Out of <strong>" . number_format($totalQuizAttempts) . "</strong> total attempts, <strong>" . number_format($passCount) . "</strong> passed and <strong>" . number_format($failCount) . "</strong> failed."];

// JSON encode all chart data for JS
$jsStrandLabels   = json_encode($strandChart['labels']);
$jsStrandValues   = json_encode($strandChart['values']);
$jsStrandStudents = json_encode($strandChart['students']);
$jsSubjectLabels  = json_encode($subjectChart['labels']);
$jsSubjectValues  = json_encode($subjectChart['values']);
$jsSubjectPass    = json_encode($subjectChart['pass_rates']);
$jsDiffLabels     = json_encode($difficultyChart['labels']);
$jsDiffValues     = json_encode($difficultyChart['values']);
$jsDistLabels     = json_encode($distLabels);
$jsDistValues     = json_encode($distValues);
$jsDistColors     = json_encode($distColors);
$jsTrendLabels    = json_encode($enrollmentTrend['labels']);
$jsTrendValues    = json_encode($enrollmentTrend['values']);
$jsSectionLabels  = json_encode($sectionData['labels']);
$jsSectionValues  = json_encode($sectionData['values']);
$jsPassFail       = json_encode(['pass' => $passCount, 'fail' => $failCount]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn — Analytics</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin/analytics.css">
<style>
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
    <a class="nav-item" href="admin_teachers.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>Teachers<span class="nav-badge"><?= $teacherCount ?></span></a>
    <a class="nav-item" href="admin_students.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Students<span class="nav-badge"><?= $studentCount ?></span></a>
    <div class="nav-section-label" style="margin-top:14px">Academic</div>
    <a class="nav-item" href="admin_sections.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>Grade & Sections <span class="nav-badge"><?= $sectionCount ?></span></a>
    <a class="nav-item" href="admin_strands.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>Strands<span class="nav-badge"><?= $strandCount ?></span></a>
    <a class="nav-item" href="admin_subjects.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 19.5V4.5A1.5 1.5 0 0 1 5.5 3h13A1.5 1.5 0 0 1 20 4.5v15a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 19.5z"/><path d="M8 7h8M8 11h8M8 15h5"/></svg>Subjects<span class="nav-badge"><?= $subjectCount ?></span></a>
    <div class="nav-section-label" style="margin-top:14px">Analytics & Reports</div>
    <a class="nav-item" href="admin_reports.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Reports</a>
    <a class="nav-item active" href="admin_analytics.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Analytics</a>
    <div class="nav-section-label" style="margin-top:14px">Account</div>
    <a class="nav-item" href="admin_profile.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>My Profile</a>
    <a class="nav-item" href="about.php"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>About</a>
    <a class="nav-item" href="#" onclick="openLogoutModal();return false;"><svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <?php if ($admin_pic): ?>
        <div class="user-avatar" style="background-image:url('<?= $admin_pic ?>');background-size:cover;"></div>
      <?php else: ?>
        <div class="user-avatar" style="background:linear-gradient(135deg,var(--rose),var(--amber))"><?= $initials ?></div>
      <?php endif; ?>
      <div class="user-info"><p><?= htmlspecialchars($admin_name) ?></p><span>Administrator</span></div>
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
        <div class="section-title">📈 Academic Analytics</div>
        <div class="section-sub">Live insights from the database — quiz performance, enrollment, pass rates, and subject difficulty.</div>
      </div>
      <a href="admin_analytics.php" class="btn btn-ghost btn-sm">🔄 Refresh</a>
    </div>

    <!-- ── Mini Stats ── -->
    <div class="mini-stats" id="mini-stats">
      <div class="mini-stat">
        <div class="mini-stat-icon" style="background:var(--accent-glow)">👥</div>
        <div>
          <div class="mini-stat-val"><?= number_format($total) ?></div>
          <div class="mini-stat-lbl">Total Students</div>
        </div>
      </div>
      <div class="mini-stat">
        <div class="mini-stat-icon" style="background:var(--green-bg)">📊</div>
        <div>
          <div class="mini-stat-val" style="color:var(--green)"><?= $overallAvg ?>%</div>
          <div class="mini-stat-lbl">Avg Quiz Score</div>
        </div>
      </div>
      <div class="mini-stat">
        <div class="mini-stat-icon" style="background:var(--cyan-bg)">✅</div>
        <div>
          <div class="mini-stat-val" style="color:var(--cyan)"><?= $passRate ?>%</div>
          <div class="mini-stat-lbl">Pass Rate</div>
        </div>
      </div>
      <div class="mini-stat">
        <div class="mini-stat-icon" style="background:var(--purple-bg)">🎯</div>
        <div>
          <div class="mini-stat-val" style="color:var(--purple)"><?= number_format($totalQuizAttempts) ?></div>
          <div class="mini-stat-lbl">Quiz Attempts</div>
        </div>
      </div>
      <div class="mini-stat">
        <div class="mini-stat-icon" style="background:var(--amber-bg)">🏫</div>
        <div>
          <div class="mini-stat-val" style="color:var(--amber)"><?= $strandCount ?></div>
          <div class="mini-stat-lbl">Active Strands</div>
        </div>
      </div>
    </div>

    <!-- ── Tabs ── -->
    <div class="tab-bar" id="tab-bar">
      <button class="tab-btn active" onclick="setTab('overview')">Overview</button>
      <button class="tab-btn" onclick="setTab('grades')">Score Analysis</button>
      <button class="tab-btn" onclick="setTab('students')">Student Rankings</button>
      <button class="tab-btn" onclick="setTab('insights')">AI Insights</button>
    </div>

    <!-- ── Tab: Overview ── -->
    <div id="tab-overview">
      <div class="charts-grid">
        <div class="chart-card chart-card-full">
          <div class="chart-header">
            <div><div class="chart-title">Quiz Attempt Trend</div><div class="chart-subtitle">Monthly quiz submissions over the last 8 months</div></div>
          </div>
          <div class="chart-wrap"><canvas id="chart-enrollment" height="180"></canvas></div>
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <div><div class="chart-title">Score Distribution</div><div class="chart-subtitle">Number of quiz attempts per score range</div></div>
          </div>
          <div class="chart-wrap"><canvas id="chart-grades" height="200"></canvas></div>
          <div class="chart-legend" id="grade-legend"></div>
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <div><div class="chart-title">Pass / Fail Ratio</div><div class="chart-subtitle">Overall quiz attempt outcomes</div></div>
          </div>
          <div class="donut-wrap">
            <div class="donut-canvas"><canvas id="chart-donut" width="160" height="160"></canvas></div>
            <div class="donut-legend" id="donut-legend"></div>
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <div><div class="chart-title">Avg Score by Strand</div><div class="chart-subtitle">Average quiz score per strand</div></div>
          </div>
          <div class="chart-wrap"><canvas id="chart-year" height="200"></canvas></div>
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <div><div class="chart-title">Enrollment by Section</div><div class="chart-subtitle">Top sections by student count</div></div>
          </div>
          <div class="chart-wrap"><canvas id="chart-strands" height="200"></canvas></div>
        </div>
      </div>
    </div>

    <!-- ── Tab: Score Analysis ── -->
    <div id="tab-grades" style="display:none">
      <div class="charts-grid">
        <div class="chart-card chart-card-full">
          <div class="chart-header">
            <div><div class="chart-title">Subject Average Scores</div><div class="chart-subtitle">Average quiz score per subject (top 10)</div></div>
          </div>
          <div class="chart-wrap"><canvas id="chart-dept" height="220"></canvas></div>
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <div><div class="chart-title">Subject Pass Rates</div><div class="chart-subtitle">Pass rate % per subject</div></div>
          </div>
          <div class="chart-wrap"><canvas id="chart-stacked" height="200"></canvas></div>
          <div class="chart-legend" id="stacked-legend"></div>
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <div><div class="chart-title">Subject Difficulty Index</div><div class="chart-subtitle">Subjects with highest fail rates</div></div>
          </div>
          <div class="chart-wrap"><canvas id="chart-difficulty" height="200"></canvas></div>
        </div>
      </div>
    </div>

    <!-- ── Tab: Student Rankings ── -->
    <div id="tab-students" style="display:none">
      <div class="card" style="padding:0;overflow:hidden">
        <div class="table-wrap">
          <table class="data-table" id="top-table">
            <thead>
              <tr>
                <th style="width:50px">Rank</th>
                <th>Student</th>
                <th>Strand</th>
                <th>Section</th>
                <th>Avg Score</th>
                <th style="width:180px">Performance</th>
              </tr>
            </thead>
            <tbody id="top-table-body"></tbody>
          </table>
        </div>
        <div class="mobile-list" id="top-mobile-list" style="padding:14px"></div>
        <div class="pagination" style="padding:14px 16px" id="top-pagination"></div>
      </div>
    </div>

    <!-- ── Tab: AI Insights ── -->
    <div id="tab-insights" style="display:none">
      <div style="display:flex;flex-direction:column;gap:12px" id="insights-container">
        <?php foreach ($insights as $ins): ?>
        <div class="insight-card">
          <div class="insight-icon"><?= $ins['icon'] ?></div>
          <div class="insight-text"><?= $ins['text'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div><!-- /content -->
</main>
</div><!-- /layout -->

<!-- STUDENT DETAIL MODAL -->
<div class="modal-overlay" id="student-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="student-modal-title">Student Details</div>
      <button class="modal-close" onclick="closeModal('student-modal')">✕</button>
    </div>
    <div class="modal-body" id="student-modal-body"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('student-modal')">Close</button>
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
/* ===== REAL DATA FROM PHP ===== */
var DB = {
  trendLabels:    <?= $jsTrendLabels ?>,
  trendValues:    <?= $jsTrendValues ?>,
  distLabels:     <?= $jsDistLabels ?>,
  distValues:     <?= $jsDistValues ?>,
  distColors:     <?= $jsDistColors ?>,
  passFail:       <?= $jsPassFail ?>,
  strandLabels:   <?= $jsStrandLabels ?>,
  strandValues:   <?= $jsStrandValues ?>,
  strandStudents: <?= $jsStrandStudents ?>,
  subjectLabels:  <?= $jsSubjectLabels ?>,
  subjectValues:  <?= $jsSubjectValues ?>,
  subjectPass:    <?= $jsSubjectPass ?>,
  diffLabels:     <?= $jsDiffLabels ?>,
  diffValues:     <?= $jsDiffValues ?>,
  sectionLabels:  <?= $jsSectionLabels ?>,
  sectionValues:  <?= $jsSectionValues ?>

};

var topStudents = <?= json_encode(array_map(function($s) {
    return [
        'rank'         => $s['rank'],
        'name'         => $s['firstname'] . ' ' . $s['lastname'],
        'strand'       => $s['strand'],
        'section'      => $s['section'] ?? '—',
        'gwa'          => (float)$s['avg_score'],
        'quizzes'      => (int)$s['quizzes_taken'],
        'highest'      => (float)$s['highest_score'],
    ];
}, $topStudents)) ?>;

var currentTab = "overview";
var studentPage = 1;
var studentsPerPage = 8;

/* ===== THEME COLORS ===== */
function getThemeColors() {
  var isLight = document.body.classList.contains("light");
  return {
    text: isLight ? "#5a6175" : "#9da3b8",
    grid: isLight ? "rgba(139,144,165,.12)" : "rgba(99,104,128,.15)",
    bg:   isLight ? "#ffffff" : "#16181f",
    tooltipBg: isLight ? "#e7eaf3" : "#252733"
  };
}

/* ===== CANVAS HELPERS ===== */
function setupCanvas(id, w, h) {
  var c = document.getElementById(id);
  if (!c) return null;
  var dpr = window.devicePixelRatio || 1;
  c.width = w * dpr; c.height = h * dpr;
  c.style.width = w + "px"; c.style.height = h + "px";
  var ctx = c.getContext("2d");
  ctx.scale(dpr, dpr);
  return { ctx: ctx, w: w, h: h };
}
function getCanvasSize(id) {
  var c = document.getElementById(id);
  if (!c) return { w: 400, h: 200 };
  var rect = c.parentElement.getBoundingClientRect();
  return { w: Math.max(200, Math.floor(rect.width)), h: parseInt(c.getAttribute("height")) || 200 };
}

/* ===== BAR CHART (VERTICAL) ===== */
function drawBarChart(id, labels, values, colors, opts) {
  opts = opts || {};
  var size = getCanvasSize(id);
  var setup = setupCanvas(id, size.w, size.h);
  if (!setup) return;
  var ctx = setup.ctx, W = setup.w, H = setup.h, tc = getThemeColors();
  var pad = { top:10, right:16, bottom:opts.bottomPad||40, left:44 };
  var chartW = W - pad.left - pad.right, chartH = H - pad.top - pad.bottom;
  var maxVal = opts.maxVal || Math.max.apply(null, values) * 1.15 || 1;
  var barW = Math.min(36, (chartW / labels.length) * 0.6);
  var gap  = (chartW - barW * labels.length) / (labels.length + 1);

  ctx.clearRect(0, 0, W, H);
  ctx.font = "10px 'DM Sans'"; ctx.fillStyle = tc.text; ctx.textAlign = "right";
  ctx.strokeStyle = tc.grid; ctx.lineWidth = 1;
  for (var i = 0; i <= 5; i++) {
    var y   = pad.top + chartH - (chartH / 5) * i;
    var val = (maxVal / 5) * i;
    ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke();
    ctx.fillText(opts.formatVal ? opts.formatVal(val) : val.toFixed(opts.decimals||0), pad.left - 6, y + 3);
  }
  ctx.textAlign = "center";
  labels.forEach(function(label, idx) {
    var x = pad.left + gap + (barW + gap) * idx;
    var bH = Math.max(0, (values[idx] / maxVal) * chartH);
    var y  = pad.top + chartH - bH;
    var col = typeof colors === "string" ? colors : (colors[idx] || colors[0]);
    ctx.fillStyle = col;
    ctx.beginPath();
    var r = Math.min(4, barW / 4);
    if (bH > r*2) { ctx.moveTo(x,y+r); ctx.arcTo(x,y,x+r,y,r); ctx.arcTo(x+barW,y,x+barW,y+r,r); ctx.lineTo(x+barW,pad.top+chartH); ctx.lineTo(x,pad.top+chartH); }
    else { ctx.rect(x, y, barW, bH); }
    ctx.fill();
    if (bH > 12) {
      ctx.fillStyle = tc.text; ctx.font = "bold 10px 'DM Sans'";
      ctx.fillText(opts.formatVal ? opts.formatVal(values[idx]) : (opts.decimals ? values[idx].toFixed(opts.decimals) : values[idx]), x + barW/2, y - 5);
    }
    ctx.fillStyle = tc.text; ctx.font = "10px 'DM Sans'";
    var dl = label.length > 8 ? label.substring(0,7)+"…" : label;
    ctx.fillText(dl, x + barW/2, H - pad.bottom + 14);
  });
}

/* ===== HORIZONTAL BAR CHART ===== */
function drawHBarChart(id, labels, values, colors, opts) {
  opts = opts || {};
  var size = getCanvasSize(id);
  var setup = setupCanvas(id, size.w, size.h);
  if (!setup) return;
  var ctx = setup.ctx, W = setup.w, H = setup.h, tc = getThemeColors();
  var pad = { top:6, right:opts.rightPad||50, bottom:6, left:opts.leftPad||80 };
  var chartW = W - pad.left - pad.right, chartH = H - pad.top - pad.bottom;
  var barH = Math.min(20, (chartH / Math.max(labels.length,1)) * 0.65);
  var gap  = (chartH - barH * labels.length) / (labels.length + 1);
  var maxVal = opts.maxVal || Math.max.apply(null, values) * 1.1 || 1;

  ctx.clearRect(0, 0, W, H);
  labels.forEach(function(label, idx) {
    var y  = pad.top + gap + (barH + gap) * idx;
    var bw = Math.max(0, (values[idx] / maxVal) * chartW);
    var col = typeof colors === "string" ? colors : (colors[idx] || colors[0]);
    ctx.fillStyle = col;
    ctx.beginPath();
    var r = Math.min(4, barH/4);
    if (bw > r*2) { ctx.moveTo(pad.left,y+r); ctx.arcTo(pad.left,y,pad.left+r,y,r); ctx.arcTo(pad.left+bw,y,pad.left+bw,y+r,r); ctx.lineTo(pad.left+bw,y+barH); ctx.lineTo(pad.left,y+barH); }
    else { ctx.rect(pad.left,y,bw,barH); }
    ctx.fill();
    ctx.fillStyle = tc.text; ctx.font = "10px 'DM Sans'"; ctx.textAlign = "right";
    var dl = label.length > 12 ? label.substring(0,11)+"…" : label;
    ctx.fillText(dl, pad.left - 6, y + barH/2 + 3);
    ctx.textAlign = "left"; ctx.font = "bold 10px 'DM Sans'";
    ctx.fillText(opts.formatVal ? opts.formatVal(values[idx]) : (opts.decimals ? values[idx].toFixed(opts.decimals) : values[idx]), pad.left + bw + 6, y + barH/2 + 3);
  });
}

/* ===== LINE CHART ===== */
function drawLineChart(id, labels, datasets) {
  var size = getCanvasSize(id);
  var setup = setupCanvas(id, size.w, size.h);
  if (!setup) return;
  var ctx = setup.ctx, W = setup.w, H = setup.h, tc = getThemeColors();
  var pad = { top:16, right:50, bottom:36, left:44 };
  var chartW = W - pad.left - pad.right, chartH = H - pad.top - pad.bottom;
  var allVals = []; datasets.forEach(function(ds){ ds.values.forEach(function(v){ allVals.push(v); }); });
  var maxVal = Math.max.apply(null, allVals) * 1.15 || 1;

  ctx.clearRect(0, 0, W, H);
  ctx.font = "10px 'DM Sans'"; ctx.fillStyle = tc.text; ctx.textAlign = "right";
  ctx.strokeStyle = tc.grid; ctx.lineWidth = 1;
  for (var i = 0; i <= 5; i++) {
    var y   = pad.top + chartH - (chartH/5)*i;
    var val = (maxVal/5)*i;
    ctx.beginPath(); ctx.moveTo(pad.left,y); ctx.lineTo(W-pad.right,y); ctx.stroke();
    ctx.fillText(Math.round(val).toLocaleString(), pad.left-6, y+3);
  }
  ctx.textAlign = "center";
  labels.forEach(function(label, idx){
    var x = pad.left + (labels.length > 1 ? (chartW/(labels.length-1))*idx : chartW/2);
    ctx.fillText(label, x, H - pad.bottom + 14);
  });

  datasets.forEach(function(ds){
    var pts = ds.values.map(function(v,idx){
      return { x: pad.left + (ds.values.length > 1 ? (chartW/(ds.values.length-1))*idx : chartW/2),
               y: pad.top + chartH - (v/maxVal)*chartH };
    });
    ctx.beginPath(); ctx.moveTo(pts[0].x, pad.top+chartH);
    pts.forEach(function(p){ ctx.lineTo(p.x,p.y); });
    ctx.lineTo(pts[pts.length-1].x, pad.top+chartH); ctx.closePath();
    var grad = ctx.createLinearGradient(0,pad.top,0,pad.top+chartH);
    grad.addColorStop(0, ds.color.replace(")",",.2)").replace("rgb","rgba"));
    grad.addColorStop(1, ds.color.replace(")",",.01)").replace("rgb","rgba"));
    ctx.fillStyle = grad; ctx.fill();
    ctx.beginPath(); ctx.strokeStyle = ds.color; ctx.lineWidth = 2.5;
    ctx.lineJoin = "round"; ctx.lineCap = "round";
    pts.forEach(function(p,i){ if(i===0) ctx.moveTo(p.x,p.y); else ctx.lineTo(p.x,p.y); });
    ctx.stroke();
    pts.forEach(function(p){ ctx.beginPath(); ctx.arc(p.x,p.y,4,0,Math.PI*2); ctx.fillStyle=ds.color; ctx.fill(); ctx.strokeStyle=tc.bg; ctx.lineWidth=2; ctx.stroke(); });
    var last = pts[pts.length-1];
    ctx.fillStyle=ds.color; ctx.font="bold 11px 'DM Sans'"; ctx.textAlign="left";
    ctx.fillText(ds.values[ds.values.length-1].toLocaleString(), last.x+8, last.y+3);
  });
}

/* ===== DONUT CHART ===== */
function drawDonut(id, segments, legendId) {
  var c = document.getElementById(id);
  var dpr = window.devicePixelRatio||1, size=160;
  c.width=size*dpr; c.height=size*dpr; c.style.width=size+"px"; c.style.height=size+"px";
  var ctx=c.getContext("2d"); ctx.scale(dpr,dpr);
  var cx=size/2, cy=size/2, radius=62, thickness=22;
  var total=0; segments.forEach(function(s){ total+=s.value; });
  ctx.clearRect(0,0,size,size);
  var startAngle=-Math.PI/2;
  segments.forEach(function(seg){
    var slice=(seg.value/total)*Math.PI*2;
    ctx.beginPath(); ctx.arc(cx,cy,radius,startAngle,startAngle+slice); ctx.arc(cx,cy,radius-thickness,startAngle+slice,startAngle,true); ctx.closePath(); ctx.fillStyle=seg.color; ctx.fill();
    startAngle+=slice;
  });
  var tc=getThemeColors();
  ctx.fillStyle=tc.text; ctx.font="bold 22px 'DM Sans'"; ctx.textAlign="center"; ctx.fillText(total.toLocaleString(),cx,cy-2);
  ctx.font="10px 'DM Sans'"; ctx.fillText("Total",cx,cy+14);
  var legendEl=document.getElementById(legendId);
  if (legendEl) {
    legendEl.innerHTML=segments.map(function(seg){
      var pct=((seg.value/total)*100).toFixed(1);
      return '<div class="donut-legend-item"><div class="donut-legend-dot" style="background:'+seg.color+'"></div><span style="color:var(--text2)">'+seg.label+'</span><span class="donut-legend-val">'+seg.value.toLocaleString()+'</span><span class="donut-legend-pct">'+pct+'%</span></div>';
    }).join('');
  }
}

/* ===== OVERVIEW CHARTS ===== */
function drawOverviewCharts() {
  drawLineChart("chart-enrollment", DB.trendLabels, [{ values: DB.trendValues, color: "rgb(108,143,255)" }]);

  drawBarChart("chart-grades", DB.distLabels, DB.distValues, DB.distColors, { bottomPad:50, decimals:0 });
  var gl = document.getElementById("grade-legend");
  if (gl) {
    gl.innerHTML = DB.distLabels.map(function(l,i){
      return '<div class="legend-item"><div class="legend-dot" style="background:'+DB.distColors[i]+'"></div>'+l+' ('+DB.distValues[i]+')</div>';
    }).join('');
  }

  drawDonut("chart-donut", [
    { label:"Passed", value:DB.passFail.pass, color:"#22c55e" },
    { label:"Failed", value:DB.passFail.fail, color:"#ff6b6b" }
  ], "donut-legend");

  var strandColors = DB.strandValues.map(function(v){ return v >= 80 ? "#22c55e" : v >= 60 ? "#6c8fff" : v >= 40 ? "#f5a623" : "#ff6b6b"; });
  drawBarChart("chart-year", DB.strandLabels, DB.strandValues, strandColors, { decimals:1, formatVal:function(v){ return v.toFixed(1)+"%"; } });

  drawHBarChart("chart-strands", DB.sectionLabels, DB.sectionValues, "rgb(34,211,238)", { leftPad:90, rightPad:40 });
}

/* ===== GRADES TAB CHARTS ===== */
function drawGradesCharts() {
  var subColors = ["#6c8fff","#22d3ee","#22c55e","#f5a623","#fb7185","#b57bee","#ff6b6b","#3dd68c","#fcd34d","#a78bfa"];
  drawHBarChart("chart-dept", DB.subjectLabels, DB.subjectValues, subColors, {
    leftPad:130, rightPad:55, decimals:1, formatVal:function(v){ return v.toFixed(1)+"%"; }, maxVal:105, labelWidth:120
  });

  // Pass rate per subject as bar chart
  var passColors = DB.subjectPass.map(function(v){ return v>=75?"#22c55e":v>=50?"#f5a623":"#ff6b6b"; });
  drawBarChart("chart-stacked", DB.subjectLabels, DB.subjectPass, passColors, {
    bottomPad:55, decimals:1, formatVal:function(v){ return v.toFixed(1)+"%"; }, maxVal:105
  });
  var sl = document.getElementById("stacked-legend");
  if (sl) sl.innerHTML = '<div class="legend-item"><div class="legend-dot" style="background:#22c55e"></div>≥75% Pass Rate</div><div class="legend-item"><div class="legend-dot" style="background:#f5a623"></div>50–74%</div><div class="legend-item"><div class="legend-dot" style="background:#ff6b6b"></div>&lt;50%</div>';

  var diffColors = DB.diffValues.map(function(v){ return v>=25?"#ff6b6b":v>=15?"#fb7185":v>=10?"#f5a623":"#22c55e"; });
  drawHBarChart("chart-difficulty", DB.diffLabels, DB.diffValues, diffColors, {
    leftPad:110, rightPad:50, decimals:1, formatVal:function(v){ return v.toFixed(1)+"%"; }, maxVal:100, labelWidth:100
  });
}

/* ===== STUDENT RANKINGS ===== */
function renderStudentTable() {
  var totalPages = Math.max(1, Math.ceil(topStudents.length / studentsPerPage));
  if (studentPage > totalPages) studentPage = totalPages;
  var start = (studentPage-1)*studentsPerPage;
  var page  = topStudents.slice(start, start+studentsPerPage);

  var tbody = document.getElementById("top-table-body");
  tbody.innerHTML = page.map(function(s){
    var rankClass = s.rank<=3 ? "rank-"+s.rank : "rank-other";
    var gradeColor = s.gwa>=85?"#22c55e":s.gwa>=75?"#6c8fff":s.gwa>=60?"#f5a623":"#ff6b6b";
    var barPct = Math.min(100, s.gwa);
    return '<tr style="cursor:pointer" onclick="openStudentModal('+s.rank+')">'+
      '<td><span class="rank-num '+rankClass+'">'+s.rank+'</span></td>'+
      '<td style="font-weight:600">'+s.name+'</td>'+
      '<td><span class="badge badge-blue">'+s.strand+'</span></td>'+
      '<td class="tbl-meta">'+s.section+'</td>'+
      '<td style="font-weight:700;color:'+gradeColor+'">'+s.gwa.toFixed(1)+'%</td>'+
      '<td><div class="grade-bar-wrap"><div class="grade-bar"><div class="grade-bar-fill" style="width:'+barPct+'%;background:'+gradeColor+'"></div></div></div></td></tr>';
  }).join('');

  var ml = document.getElementById("top-mobile-list");
  ml.innerHTML = page.map(function(s){
    var rankClass = s.rank<=3 ? "rank-"+s.rank : "rank-other";
    var gradeColor = s.gwa>=85?"#22c55e":s.gwa>=75?"#6c8fff":s.gwa>=60?"#f5a623":"#ff6b6b";
    var barPct = Math.min(100, s.gwa);
    return '<div class="mobile-card" onclick="openStudentModal('+s.rank+')" style="cursor:pointer">'+
      '<div class="mc-top"><div class="mc-rank '+rankClass+'">'+s.rank+'</div><div class="mc-info"><div class="mc-name">'+s.name+'</div><div class="mc-sub">'+s.strand+' · '+s.section+'</div></div><div class="mc-grade" style="color:'+gradeColor+'">'+s.gwa.toFixed(1)+'%</div></div>'+
      '<div class="mc-bar"><div class="mc-bar-fill" style="width:'+barPct+'%;background:'+gradeColor+'"></div></div></div>';
  }).join('');

  var pe = document.getElementById("top-pagination");
  var s2 = start+1, e2 = Math.min(start+studentsPerPage, topStudents.length);
  if (topStudents.length===0) s2=0;
  var btns = '<button class="page-btn" onclick="goStudentPage('+(studentPage-1)+')"'+(studentPage<=1?' disabled':'')+'>‹</button>';
  for (var p=1;p<=totalPages;p++) btns+='<button class="page-btn'+(p===studentPage?' active':'')+'" onclick="goStudentPage('+p+')">'+p+'</button>';
  btns += '<button class="page-btn" onclick="goStudentPage('+(studentPage+1)+')"'+(studentPage>=totalPages?' disabled':'')+'>›</button>';
  pe.innerHTML = '<div class="pagination-info">Showing '+s2+'–'+e2+' of '+topStudents.length+' students</div><div class="pagination-btns">'+btns+'</div>';
}

function goStudentPage(p) {
  var tp = Math.max(1,Math.ceil(topStudents.length/studentsPerPage));
  if (p<1||p>tp) return;
  studentPage = p;
  renderStudentTable();
  document.getElementById("tab-students").scrollIntoView({behavior:"smooth",block:"start"});
}

/* ===== STUDENT MODAL ===== */
function openStudentModal(rank) {
  var s = topStudents.find(function(x){ return x.rank===rank; });
  if (!s) return;
  document.getElementById("student-modal-title").textContent = s.name;
  var gc = s.gwa>=85?"var(--green)":s.gwa>=75?"var(--accent)":s.gwa>=60?"var(--amber)":"var(--rose)";
  var bp = Math.min(100,s.gwa);
  var html = '<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">'+
    '<span class="badge badge-blue">'+s.strand+'</span>'+
    '<span class="badge badge-purple">'+s.section+'</span>'+
    '<span class="badge badge-green">Rank #'+s.rank+'</span></div>'+
    '<div style="display:flex;align-items:center;gap:16px;margin-bottom:18px;padding:14px;background:var(--bg3);border-radius:var(--radius-sm)">'+
    '<div style="text-align:center"><div style="font-size:28px;font-weight:800;color:'+gc+'">'+s.gwa.toFixed(1)+'%</div><div style="font-size:10px;color:var(--text3);margin-top:2px">Avg Score</div></div>'+
    '<div style="flex:1"><div style="font-size:11px;color:var(--text3);margin-bottom:4px">Performance Index</div>'+
    '<div style="height:10px;border-radius:5px;background:var(--bg4);overflow:hidden"><div style="height:100%;width:'+bp+'%;background:'+gc+';border-radius:5px;transition:width .4s ease"></div></div></div></div>'+
    '<div style="display:flex;gap:16px;flex-wrap:wrap">'+
    '<div style="flex:1;min-width:100px;background:var(--bg3);border-radius:8px;padding:12px;text-align:center">'+
    '<div style="font-size:20px;font-weight:700;color:var(--green)">'+s.highest.toFixed(1)+'%</div><div style="font-size:11px;color:var(--text3)">Highest Score</div></div>'+
    '<div style="flex:1;min-width:100px;background:var(--bg3);border-radius:8px;padding:12px;text-align:center">'+
    '<div style="font-size:20px;font-weight:700;color:var(--accent)">'+s.quizzes+'</div><div style="font-size:11px;color:var(--text3)">Quizzes Taken</div></div></div>';
  document.getElementById("student-modal-body").innerHTML = html;
  openModal("student-modal");
}

/* ===== TABS ===== */
function setTab(tab) {
  currentTab = tab;
  document.querySelectorAll(".tab-btn").forEach(function(b){ b.classList.remove("active"); });
  var tabMap = { overview:"Overview", grades:"Score Analysis", students:"Student Rankings", insights:"AI Insights" };
  document.querySelectorAll(".tab-btn").forEach(function(b){ if(b.textContent===tabMap[tab]) b.classList.add("active"); });
  ["overview","grades","students","insights"].forEach(function(t){
    document.getElementById("tab-"+t).style.display = t===tab ? "" : "none";
  });
  if (tab==="overview")  setTimeout(drawOverviewCharts, 50);
  if (tab==="grades")    setTimeout(drawGradesCharts, 50);
  if (tab==="students")  renderStudentTable();
}

/* ===== MODALS ===== */
function openModal(id)  { document.getElementById(id).classList.add("open");    document.body.style.overflow="hidden"; }
function closeModal(id) { document.getElementById(id).classList.remove("open"); document.body.style.overflow=""; }
function openLogoutModal() { openModal("logout-modal"); }

/* ===== SIDEBAR ===== */
var sidebar=document.getElementById("sidebar"), overlay=document.getElementById("sidebar-overlay"), menuBtn=document.getElementById("menuBtn");
function openSidebar()  { sidebar.classList.add("open");    overlay.classList.add("open");    document.body.style.overflow="hidden"; }
function closeSidebar() { sidebar.classList.remove("open"); overlay.classList.remove("open"); document.body.style.overflow=""; }
menuBtn.addEventListener("click", function(){ sidebar.classList.contains("open")?closeSidebar():openSidebar(); });
overlay.addEventListener("click", closeSidebar);
document.querySelectorAll(".nav-item").forEach(function(i){ i.addEventListener("click",function(){ if(window.innerWidth<=768) closeSidebar(); }); });
document.addEventListener("keydown", function(e){ if(e.key==="Escape"){ closeSidebar(); closeModal("student-modal"); closeModal("logout-modal"); } });
document.querySelectorAll(".modal-overlay").forEach(function(ov){ ov.addEventListener("click",function(e){ if(e.target===ov) closeModal(ov.id); }); });
var rTimer; window.addEventListener("resize", function(){ clearTimeout(rTimer); rTimer=setTimeout(function(){ if(window.innerWidth>768) closeSidebar(); if(currentTab==="overview") drawOverviewCharts(); if(currentTab==="grades") drawGradesCharts(); },200); });

/* ===== INIT ===== */
setTimeout(function(){ setTab("overview"); }, 100);
</script>
</body>
</html>