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

$gradeSummaryByStrand = [];
$res = $conn->query("
    SELECT st.strand, st.track,
           COUNT(DISTINCT ts2.student_id) AS students,
           ROUND(AVG(qr.score), 2)        AS avg_score,
           ROUND(
               100 * SUM(CASE WHEN qr.score >= q.passing_score THEN 1 ELSE 0 END)
               / NULLIF(COUNT(qr.id), 0), 1
           ) AS pass_rate
    FROM tblstrands st
    LEFT JOIN tblsubjects sub ON sub.strand_id = st.strand_id
    LEFT JOIN tbl_teacher_subjects ts ON ts.subject_id = sub.subject_id
    LEFT JOIN tbl_teacher_lessons tl ON tl.teacher_subject_id = ts.teacher_subject_id
    LEFT JOIN tblquizzes q ON q.lesson_id = tl.lesson_id
    LEFT JOIN tblquiz_results qr ON qr.quizzes_id = q.quiz_id
    LEFT JOIN tbl_teachers_students ts2 ON ts2.teacher_subject_id = ts.teacher_subject_id
    GROUP BY st.strand_id
    ORDER BY avg_score DESC
");
while ($r = $res->fetch_assoc()) $gradeSummaryByStrand[] = $r;

// 2. Top Performers (students with highest average quiz score)
$topPerformers = [];
$res = $conn->query("
    SELECT s.firstname, s.lastname, s.gender,
           ROUND(AVG(qr.score), 2) AS avg_score,
           COUNT(qr.id)            AS quizzes_taken,
           MAX(qr.score)           AS highest_score,
           st.strand
    FROM tblstudents s
    JOIN tblquiz_results qr ON qr.student_id = s.id
    JOIN tblquizzes q       ON q.quiz_id = qr.quizzes_id
    JOIN tbl_teacher_lessons tl ON tl.lesson_id = q.lesson_id
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = tl.teacher_subject_id
    JOIN tblsubjects sub ON sub.subject_id = ts.subject_id
    JOIN tblstrands st   ON st.strand_id = sub.strand_id
    GROUP BY s.id
    ORDER BY avg_score DESC
    LIMIT 10
");
while ($r = $res->fetch_assoc()) $topPerformers[] = $r;

// 3. Failing Students (average score below passing_score)
$failingStudents = [];
$res = $conn->query("
    SELECT s.firstname, s.lastname,
           ROUND(AVG(qr.score), 2)  AS avg_score,
           COUNT(qr.id)             AS quizzes_taken,
           sub.subject,
           st.strand
    FROM tblstudents s
    JOIN tblquiz_results qr ON qr.student_id = s.id
    JOIN tblquizzes q       ON q.quiz_id = qr.quizzes_id
    JOIN tbl_teacher_lessons tl ON tl.lesson_id = q.lesson_id
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = tl.teacher_subject_id
    JOIN tblsubjects sub ON sub.subject_id = ts.subject_id
    JOIN tblstrands st   ON st.strand_id = sub.strand_id
    WHERE qr.score < q.passing_score
    GROUP BY s.id
    ORDER BY avg_score ASC
    LIMIT 20
");
while ($r = $res->fetch_assoc()) $failingStudents[] = $r;

// 4. Enrollment Statistics
$enrollStats = [];
$res = $conn->query("
    SELECT
        COUNT(*)                                              AS total,
        SUM(CASE WHEN gender='Male'   THEN 1 ELSE 0 END)    AS male,
        SUM(CASE WHEN gender='Female' THEN 1 ELSE 0 END)    AS female,
        SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END)    AS active
    FROM tblstudents
");
$enrollStats = $res->fetch_assoc();

// 5. Subject Grade Distribution
$subjectDist = [];
$res = $conn->query("
    SELECT sub.subject,
           COUNT(qr.id)  AS quiz_attempts,
           ROUND(AVG(qr.score), 2)  AS avg_score,
           SUM(CASE WHEN qr.score >= q.passing_score THEN 1 ELSE 0 END) AS passed,
           SUM(CASE WHEN qr.score <  q.passing_score THEN 1 ELSE 0 END) AS failed
    FROM tblsubjects sub
    JOIN tbl_teacher_subjects ts ON ts.subject_id = sub.subject_id
    JOIN tbl_teacher_lessons tl ON tl.teacher_subject_id = ts.teacher_subject_id
    JOIN tblquizzes q  ON q.lesson_id = tl.lesson_id
    JOIN tblquiz_results qr ON qr.quizzes_id = q.quiz_id
    GROUP BY sub.subject_id
    ORDER BY avg_score DESC
");
while ($r = $res->fetch_assoc()) $subjectDist[] = $r;

// 6. Teacher Load Summary
$teacherLoad = [];
$res = $conn->query("
    SELECT u.firstname, u.lastname, u.status,
           COUNT(DISTINCT ts.subject_id)                     AS subject_count,
           COUNT(DISTINCT tl.lesson_id)                      AS lesson_count,
           COUNT(DISTINCT ts2.student_id)                    AS student_count
    FROM tblusers u
    LEFT JOIN tbl_teacher_subjects ts  ON ts.teacher_id = u.id
    LEFT JOIN tbl_teacher_lessons tl   ON tl.teacher_subject_id = ts.teacher_subject_id
    LEFT JOIN tbl_teachers_students ts2 ON ts2.teacher_subject_id = ts.teacher_subject_id
    WHERE u.role = 'teacher'
    GROUP BY u.id
    ORDER BY subject_count DESC
");
while ($r = $res->fetch_assoc()) $teacherLoad[] = $r;

// 7. Section Capacity Report
$sectionCapacity = [];
$res = $conn->query("
    SELECT sec.section,
           gl.grade_level,
           COUNT(s.id) AS enrolled
    FROM tblsections sec
    LEFT JOIN tblgrade_levels gl ON gl.grade_id = sec.grade_level_id
    LEFT JOIN tblstudents s ON s.section_id = sec.section_id
    GROUP BY sec.section_id
    ORDER BY enrolled DESC
");
while ($r = $res->fetch_assoc()) $sectionCapacity[] = $r;

// 8. Quiz Results Summary
$quizSummary = [];
$res = $conn->query("
    SELECT q.description, q.passing_score, q.due_date, q.status,
           COUNT(qr.id)                       AS attempts,
           ROUND(AVG(qr.score), 2)            AS avg_score,
           MAX(qr.score)                      AS highest,
           MIN(qr.score)                      AS lowest,
           SUM(CASE WHEN qr.score >= q.passing_score THEN 1 ELSE 0 END) AS passed,
           sub.subject, st.strand
    FROM tblquizzes q
    JOIN tbl_teacher_lessons tl ON tl.lesson_id = q.lesson_id
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = tl.teacher_subject_id
    JOIN tblsubjects sub ON sub.subject_id = ts.subject_id
    JOIN tblstrands st ON st.strand_id = sub.strand_id
    LEFT JOIN tblquiz_results qr ON qr.quizzes_id = q.quiz_id
    GROUP BY q.quiz_id
    ORDER BY attempts DESC
    LIMIT 20
");
while ($r = $res->fetch_assoc()) $quizSummary[] = $r;

// ── Aggregate mini-stat numbers ───────────────────────────────────────────
$totalQuizAttempts  = (int)$conn->query("SELECT COUNT(*) FROM tblquiz_results")->fetch_row()[0];
$totalQuizzes       = (int)$conn->query("SELECT COUNT(*) FROM tblquizzes")->fetch_row()[0];
$totalLessons       = (int)$conn->query("SELECT COUNT(*) FROM tbl_teacher_lessons")->fetch_row()[0];
$overallAvg = $conn->query("SELECT ROUND(AVG(score),2) FROM tblquiz_results")->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn — Reports</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin/index.css">
<style>
@media (max-width: 768px) {
  .topbar { position:fixed!important;top:0!important;left:0!important;right:0!important;z-index:100!important;width:100%!important; }
  .main   { padding-top:60px!important; }
}

/* ── Report section tabs ── */
.report-tabs { display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px; }
.report-tab  { padding:7px 16px;border-radius:20px;font-size:13px;font-weight:500;cursor:pointer;
               background:var(--bg3);border:1.5px solid var(--border);color:var(--text2);transition:.2s; }
.report-tab:hover  { border-color:var(--border2);color:var(--text); }
.report-tab.active { background:var(--accent-glow);border-color:var(--accent);color:var(--accent); }

/* ── Tables inside cards ── */
.rpt-table { width:100%;border-collapse:collapse;font-size:13px; }
.rpt-table thead th { font-size:11px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;
                      color:var(--text3);padding:8px 10px;text-align:left;
                      border-bottom:2px solid var(--border);background:var(--bg2); }
.rpt-table tbody tr { border-bottom:1px solid var(--border);transition:.15s; }
.rpt-table tbody tr:hover { background:var(--bg3); }
.rpt-table tbody tr:last-child { border-bottom:none; }
.rpt-table td { padding:10px;vertical-align:middle; }

/* ── Progress bar ── */
.bar-wrap { display:flex;align-items:center;gap:8px; }
.bar-bg   { flex:1;height:7px;border-radius:4px;background:var(--bg4);overflow:hidden; }
.bar-fill { height:100%;border-radius:4px;transition:width .4s; }

/* ── Stat cards ── */
.rpt-stat-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px; }
@media(max-width:900px){ .rpt-stat-grid{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:480px){ .rpt-stat-grid{ grid-template-columns:1fr 1fr; } }

.rpt-stat { background:var(--bg2);border:1px solid var(--border);border-radius:12px;
            padding:14px 16px;display:flex;align-items:center;gap:12px; }
.rpt-stat-icon { width:36px;height:36px;border-radius:10px;display:flex;align-items:center;
                 justify-content:center;font-size:17px;flex-shrink:0; }
.rpt-stat-val  { font-size:22px;font-weight:700;letter-spacing:-.5px;line-height:1; }
.rpt-stat-lbl  { font-size:11px;color:var(--text3);margin-top:2px; }

/* ── Report panel ── */
.rpt-panel { display:none; }
.rpt-panel.active { display:block; }

/* ── Scrollable table wrapper ── */
.rpt-scroll { overflow-x:auto;-webkit-overflow-scrolling:touch; }
.rpt-scroll .rpt-table { min-width:600px; }

/* ── Rank badge ── */
.rank-badge { display:inline-flex;align-items:center;justify-content:center;
              width:24px;height:24px;border-radius:50%;font-size:11px;font-weight:700; }

@media(max-width:768px){
  .rpt-table thead { display:none; }
  .rpt-table tbody tr { display:block;border-bottom:none;margin-bottom:10px;
                        border:1px solid var(--border);border-radius:10px;padding:10px; }
  .rpt-table td { display:flex;justify-content:space-between;align-items:center;
                  padding:5px 4px;font-size:12px;border-bottom:none; }
  .rpt-table td[data-label]::before { content:attr(data-label);
                                      font-size:10px;color:var(--text3);font-weight:600;
                                      text-transform:uppercase;flex-shrink:0;margin-right:8px; }
  .rpt-scroll .rpt-table { min-width:unset; }
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

<!-- ══ SIDEBAR ══════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo" style="display:flex;align-items:center;gap:10px;flex-wrap:nowrap;overflow:hidden;">
    <img src="../assets/img/Excelsior_Seal_Logo.png" alt="ExceLearn Logo" style="height:38px;width:auto;object-fit:contain;flex-shrink:0;">
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
      Teachers <span class="nav-badge"><?= $teacherCount ?></span>
    </a>
    <a class="nav-item" href="admin_students.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Students <span class="nav-badge"><?= $studentCount ?></span>
    </a>
    <div class="nav-section-label" style="margin-top:14px">Academic</div>
    <a class="nav-item" href="admin_sections.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      Grade & Sections <span class="nav-badge"><?= $sectionCount ?></span>
    </a>
    <a class="nav-item" href="admin_strands.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Strands <span class="nav-badge"><?= $strandCount ?></span>
    </a>
    <a class="nav-item" href="admin_subjects.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5V4.5A1.5 1.5 0 0 1 5.5 3h13A1.5 1.5 0 0 1 20 4.5v15a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 19.5z"/>
        <path d="M8 7h8M8 11h8M8 15h5"/>
      </svg>
      Subjects <span class="nav-badge"><?= $subjectCount ?></span>
    </a>
    <div class="nav-section-label" style="margin-top:14px">Reports and Analytics</div>
    <a class="nav-item active" href="admin_reports.php">
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

<!-- ══ MAIN ══════════════════════════════════════════════════════════════ -->
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
    <div class="section-header" style="margin-bottom:6px;">
      <div>
        <div class="section-title">📄 Reports Center</div>
        <div class="section-sub">Live data pulled from the database — academic performance, enrollment, and teacher load.</div>
      </div>
    </div>

    <!-- Mini stats -->
    <div class="rpt-stat-grid">
      <div class="rpt-stat">
        <div class="rpt-stat-icon" style="background:var(--accent-glow)">👨‍🎓</div>
        <div>
          <div class="rpt-stat-val"><?= number_format($studentCount) ?></div>
          <div class="rpt-stat-lbl">Total Students</div>
        </div>
      </div>
      <div class="rpt-stat">
        <div class="rpt-stat-icon" style="background:var(--green-bg)">📝</div>
        <div>
          <div class="rpt-stat-val" style="color:var(--green)"><?= number_format($totalQuizAttempts) ?></div>
          <div class="rpt-stat-lbl">Quiz Attempts</div>
        </div>
      </div>
      <div class="rpt-stat">
        <div class="rpt-stat-icon" style="background:var(--purple-bg)">📊</div>
        <div>
          <div class="rpt-stat-val" style="color:var(--purple)"><?= $overallAvg ?>%</div>
          <div class="rpt-stat-lbl">Overall Avg Score</div>
        </div>
      </div>
      <div class="rpt-stat">
        <div class="rpt-stat-icon" style="background:var(--amber-bg)">📖</div>
        <div>
          <div class="rpt-stat-val" style="color:var(--amber)"><?= number_format($totalLessons) ?></div>
          <div class="rpt-stat-lbl">Total Lessons</div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="report-tabs" id="report-tabs">
      <div class="report-tab active" onclick="switchTab('strand')">📚 Grade by Strand</div>
      <div class="report-tab" onclick="switchTab('top')">🏅 Top Performers</div>
      <div class="report-tab" onclick="switchTab('failing')">⚠️ Failing Students</div>
      <div class="report-tab" onclick="switchTab('enrollment')">🎓 Enrollment</div>
      <div class="report-tab" onclick="switchTab('subjects')">📖 Subject Distribution</div>
      <div class="report-tab" onclick="switchTab('teachers')">👨‍🏫 Teacher Load</div>
      <div class="report-tab" onclick="switchTab('sections')">🏛️ Section Capacity</div>
      <div class="report-tab" onclick="switchTab('quizzes')">📝 Quiz Summary</div>
    </div>

    <!-- ── PANEL 1: Grade by Strand ── -->
    <div class="rpt-panel active card" id="panel-strand">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <div style="font-weight:700;font-size:15px;">📚 Grade Summary by Strand</div>
        <span class="badge badge-blue"><?= count($gradeSummaryByStrand) ?> strands</span>
      </div>
      <?php if (empty($gradeSummaryByStrand)): ?>
        <div style="text-align:center;padding:40px;color:var(--text3);">No quiz data available yet.</div>
      <?php else: ?>
      <div class="rpt-scroll">
        <table class="rpt-table">
          <thead>
            <tr>
              <th>Strand</th>
              <th>Track</th>
              <th>Students</th>
              <th>Avg Score</th>
              <th>Pass Rate</th>
              <th>Progress</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($gradeSummaryByStrand as $row):
              $avg = (float)($row['avg_score'] ?? 0);
              $pr  = (float)($row['pass_rate'] ?? 0);
              $col = $avg >= 85 ? 'var(--green)' : ($avg >= 70 ? 'var(--amber)' : 'var(--red)');
            ?>
            <tr>
              <td data-label="Strand"><strong><?= htmlspecialchars($row['strand']) ?></strong></td>
              <td data-label="Track"><span class="badge badge-cyan"><?= htmlspecialchars($row['track']) ?></span></td>
              <td data-label="Students"><?= number_format($row['students']) ?></td>
              <td data-label="Avg Score"><span style="font-weight:700;color:<?= $col ?>"><?= $avg > 0 ? $avg.'%' : '—' ?></span></td>
              <td data-label="Pass Rate"><?= $pr > 0 ? $pr.'%' : '—' ?></td>
              <td data-label="Progress" style="min-width:120px;">
                <div class="bar-wrap">
                  <div class="bar-bg"><div class="bar-fill" style="width:<?= min(100,$avg) ?>%;background:<?= $col ?>"></div></div>
                  <span style="font-size:11px;color:var(--text3);width:36px;text-align:right;"><?= $avg > 0 ? $avg.'%' : '—' ?></span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── PANEL 2: Top Performers ── -->
    <div class="rpt-panel card" id="panel-top">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <div style="font-weight:700;font-size:15px;">🏅 Top 10 Performers</div>
        <span class="badge badge-green">By Average Quiz Score</span>
      </div>
      <?php if (empty($topPerformers)): ?>
        <div style="text-align:center;padding:40px;color:var(--text3);">No quiz results yet.</div>
      <?php else: ?>
      <div class="rpt-scroll">
        <table class="rpt-table">
          <thead>
            <tr><th>#</th><th>Student</th><th>Strand</th><th>Avg Score</th><th>Highest</th><th>Quizzes</th></tr>
          </thead>
          <tbody>
            <?php foreach ($topPerformers as $i => $row):
              $rankColors = ['#f5a623','#c0c0c0','#cd7f32'];
              $rankBg = $rankColors[$i] ?? 'var(--bg4)';
            ?>
            <tr>
              <td data-label="#">
                <div class="rank-badge" style="background:<?= $rankBg ?>;color:<?= $i < 3 ? '#fff' : 'var(--text2)' ?>;"><?= $i+1 ?></div>
              </td>
              <td data-label="Student">
                <strong><?= htmlspecialchars($row['firstname'].' '.$row['lastname']) ?></strong>
                <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars($row['gender'] ?? '') ?></div>
              </td>
              <td data-label="Strand"><span class="badge badge-purple"><?= htmlspecialchars($row['strand']) ?></span></td>
              <td data-label="Avg Score"><span style="font-weight:700;color:var(--green)"><?= $row['avg_score'] ?>%</span></td>
              <td data-label="Highest"><?= $row['highest_score'] ?>%</td>
              <td data-label="Quizzes"><?= $row['quizzes_taken'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── PANEL 3: Failing Students ── -->
    <div class="rpt-panel card" id="panel-failing">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <div style="font-weight:700;font-size:15px;">⚠️ Students Below Passing Score</div>
        <span class="badge badge-red"><?= count($failingStudents) ?> students</span>
      </div>
      <?php if (empty($failingStudents)): ?>
        <div style="text-align:center;padding:40px;color:var(--text3);">No failing records found.</div>
      <?php else: ?>
      <div class="rpt-scroll">
        <table class="rpt-table">
          <thead>
            <tr><th>Student</th><th>Subject</th><th>Strand</th><th>Avg Score</th><th>Quizzes</th></tr>
          </thead>
          <tbody>
            <?php foreach ($failingStudents as $row): ?>
            <tr>
              <td data-label="Student"><strong><?= htmlspecialchars($row['firstname'].' '.$row['lastname']) ?></strong></td>
              <td data-label="Subject"><?= htmlspecialchars($row['subject']) ?></td>
              <td data-label="Strand"><span class="badge badge-rose"><?= htmlspecialchars($row['strand']) ?></span></td>
              <td data-label="Avg Score"><span style="font-weight:700;color:var(--red)"><?= $row['avg_score'] ?>%</span></td>
              <td data-label="Quizzes"><?= $row['quizzes_taken'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── PANEL 4: Enrollment ── -->
    <div class="rpt-panel card" id="panel-enrollment">
      <div style="font-weight:700;font-size:15px;margin-bottom:18px;">🎓 Enrollment Statistics</div>
      <?php $e = $enrollStats; ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px;">
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center;">
          <div style="font-size:28px;font-weight:800;color:var(--accent)"><?= number_format($e['total']) ?></div>
          <div style="font-size:12px;color:var(--text3)">Total Students</div>
        </div>
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center;">
          <div style="font-size:28px;font-weight:800;color:var(--cyan)"><?= number_format($e['male']) ?></div>
          <div style="font-size:12px;color:var(--text3)">Male</div>
        </div>
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center;">
          <div style="font-size:28px;font-weight:800;color:var(--rose)"><?= number_format($e['female']) ?></div>
          <div style="font-size:12px;color:var(--text3)">Female</div>
        </div>
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center;">
          <div style="font-size:28px;font-weight:800;color:var(--green)"><?= number_format($e['active']) ?></div>
          <div style="font-size:12px;color:var(--text3)">Active</div>
        </div>
      </div>
      <?php
        $male   = (int)$e['male'];
        $female = (int)$e['female'];
        $total  = (int)$e['total'];
        $malePct   = $total > 0 ? round($male   / $total * 100, 1) : 0;
        $femalePct = $total > 0 ? round($female / $total * 100, 1) : 0;
      ?>
      <div style="font-size:13px;font-weight:600;margin-bottom:10px;color:var(--text2)">Gender Ratio</div>
      <div class="bar-wrap" style="margin-bottom:6px;">
        <span style="font-size:12px;width:60px;color:var(--cyan)">Male</span>
        <div class="bar-bg" style="height:12px;border-radius:6px;">
          <div class="bar-fill" style="width:<?= $malePct ?>%;background:var(--cyan);border-radius:6px;height:100%;"></div>
        </div>
        <span style="font-size:12px;width:44px;text-align:right;color:var(--cyan)"><?= $malePct ?>%</span>
      </div>
      <div class="bar-wrap">
        <span style="font-size:12px;width:60px;color:var(--rose)">Female</span>
        <div class="bar-bg" style="height:12px;border-radius:6px;">
          <div class="bar-fill" style="width:<?= $femalePct ?>%;background:var(--rose);border-radius:6px;height:100%;"></div>
        </div>
        <span style="font-size:12px;width:44px;text-align:right;color:var(--rose)"><?= $femalePct ?>%</span>
      </div>
    </div>

    <!-- ── PANEL 5: Subject Distribution ── -->
    <div class="rpt-panel card" id="panel-subjects">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <div style="font-weight:700;font-size:15px;">📖 Subject Grade Distribution</div>
        <span class="badge badge-amber"><?= count($subjectDist) ?> subjects</span>
      </div>
      <?php if (empty($subjectDist)): ?>
        <div style="text-align:center;padding:40px;color:var(--text3);">No data yet.</div>
      <?php else: ?>
      <div class="rpt-scroll">
        <table class="rpt-table">
          <thead>
            <tr><th>Subject</th><th>Attempts</th><th>Avg Score</th><th>Passed</th><th>Failed</th><th>Pass Rate</th></tr>
          </thead>
          <tbody>
            <?php foreach ($subjectDist as $row):
              $att = (int)$row['quiz_attempts'];
              $pr  = $att > 0 ? round($row['passed'] / $att * 100, 1) : 0;
              $col = $pr >= 75 ? 'var(--green)' : ($pr >= 50 ? 'var(--amber)' : 'var(--red)');
            ?>
            <tr>
              <td data-label="Subject"><strong><?= htmlspecialchars($row['subject']) ?></strong></td>
              <td data-label="Attempts"><?= number_format($att) ?></td>
              <td data-label="Avg Score"><span style="color:<?= $col ?>;font-weight:700"><?= $row['avg_score'] ?>%</span></td>
              <td data-label="Passed"><span style="color:var(--green)"><?= number_format($row['passed']) ?></span></td>
              <td data-label="Failed"><span style="color:var(--red)"><?= number_format($row['failed']) ?></span></td>
              <td data-label="Pass Rate">
                <div class="bar-wrap">
                  <div class="bar-bg"><div class="bar-fill" style="width:<?= $pr ?>%;background:<?= $col ?>"></div></div>
                  <span style="font-size:11px;color:var(--text3);width:36px;text-align:right"><?= $pr ?>%</span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── PANEL 6: Teacher Load ── -->
    <div class="rpt-panel card" id="panel-teachers">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <div style="font-weight:700;font-size:15px;">👨‍🏫 Teacher Load Summary</div>
        <span class="badge badge-purple"><?= count($teacherLoad) ?> teachers</span>
      </div>
      <?php if (empty($teacherLoad)): ?>
        <div style="text-align:center;padding:40px;color:var(--text3);">No teachers found.</div>
      <?php else: ?>
      <div class="rpt-scroll">
        <table class="rpt-table">
          <thead>
            <tr><th>Teacher</th><th>Status</th><th>Subjects</th><th>Lessons</th><th>Students</th></tr>
          </thead>
          <tbody>
            <?php foreach ($teacherLoad as $row): ?>
            <tr>
              <td data-label="Teacher"><strong><?= htmlspecialchars($row['firstname'].' '.$row['lastname']) ?></strong></td>
              <td data-label="Status">
                <span class="badge <?= strtolower($row['status']) === 'active' ? 'badge-green' : 'badge-red' ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>
              <td data-label="Subjects"><?= (int)$row['subject_count'] ?></td>
              <td data-label="Lessons"><?= (int)$row['lesson_count'] ?></td>
              <td data-label="Students"><?= (int)$row['student_count'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── PANEL 7: Section Capacity ── -->
    <div class="rpt-panel card" id="panel-sections">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <div style="font-weight:700;font-size:15px;">🏛️ Section Enrollment</div>
        <span class="badge badge-teal"><?= count($sectionCapacity) ?> sections</span>
      </div>
      <?php if (empty($sectionCapacity)): ?>
        <div style="text-align:center;padding:40px;color:var(--text3);">No section data.</div>
      <?php else: ?>
      <div class="rpt-scroll">
        <table class="rpt-table">
          <thead>
            <tr><th>Section</th><th>Grade Level</th><th>Enrolled</th><th>Enrollment Bar</th></tr>
          </thead>
          <tbody>
            <?php
            $maxEnrolled = max(array_column($sectionCapacity, 'enrolled') ?: [1]);
            foreach ($sectionCapacity as $row):
              $pct = $maxEnrolled > 0 ? round($row['enrolled'] / $maxEnrolled * 100) : 0;
              $col = $pct >= 90 ? 'var(--red)' : ($pct >= 70 ? 'var(--amber)' : 'var(--green)');
            ?>
            <tr>
              <td data-label="Section"><strong><?= htmlspecialchars($row['section']) ?></strong></td>
              <td data-label="Grade Level"><?= htmlspecialchars($row['grade_level'] ?? '—') ?></td>
              <td data-label="Enrolled"><span style="font-weight:700;color:<?= $col ?>"><?= (int)$row['enrolled'] ?></span></td>
              <td data-label="Bar" style="min-width:140px">
                <div class="bar-wrap">
                  <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>"></div></div>
                  <span style="font-size:11px;color:var(--text3);width:36px;text-align:right"><?= (int)$row['enrolled'] ?></span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── PANEL 8: Quiz Summary ── -->
    <div class="rpt-panel card" id="panel-quizzes">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
        <div style="font-weight:700;font-size:15px;">📝 Quiz Results Summary</div>
        <span class="badge badge-amber"><?= count($quizSummary) ?> quizzes</span>
      </div>
      <?php if (empty($quizSummary)): ?>
        <div style="text-align:center;padding:40px;color:var(--text3);">No quiz data yet.</div>
      <?php else: ?>
      <div class="rpt-scroll">
        <table class="rpt-table">
          <thead>
            <tr><th>Quiz</th><th>Subject</th><th>Attempts</th><th>Avg</th><th>High</th><th>Low</th><th>Passed</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($quizSummary as $row):
              $col = (float)$row['avg_score'] >= 75 ? 'var(--green)' : ((float)$row['avg_score'] >= 50 ? 'var(--amber)' : 'var(--red)');
              $statusBadge = $row['status'] === 'published' ? 'badge-green' : ($row['status'] === 'draft' ? 'badge-amber' : 'badge-red');
            ?>
            <tr>
              <td data-label="Quiz"><strong><?= htmlspecialchars($row['description'] ?: 'Quiz') ?></strong>
                <?php if ($row['due_date']): ?>
                  <div style="font-size:10px;color:var(--text3)">Due: <?= date('M d, Y', strtotime($row['due_date'])) ?></div>
                <?php endif; ?>
              </td>
              <td data-label="Subject"><?= htmlspecialchars($row['subject']) ?></td>
              <td data-label="Attempts"><?= (int)$row['attempts'] ?></td>
              <td data-label="Avg"><span style="font-weight:700;color:<?= $col ?>"><?= $row['avg_score'] ?>%</span></td>
              <td data-label="High"><span style="color:var(--green)"><?= $row['highest'] ?>%</span></td>
              <td data-label="Low"><span style="color:var(--red)"><?= $row['lowest'] ?>%</span></td>
              <td data-label="Passed"><span style="color:var(--green)"><?= (int)$row['passed'] ?></span></td>
              <td data-label="Status"><span class="badge <?= $statusBadge ?>"><?= ucfirst($row['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /content -->
</main>
</div><!-- /layout -->

<!-- LOGOUT MODAL -->
<div class="modal-overlay" id="logout-modal" style="display:none;">
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
/* ── Theme ── */
var savedTheme = '<?= htmlspecialchars($savedTheme) ?>';
localStorage.setItem('excelearn-theme', savedTheme);
if (savedTheme === 'light') document.body.classList.add('light');

/* ── Modal ── */
function openModal(id)  { var el=document.getElementById(id); if(el){el.style.display='flex'; document.body.style.overflow='hidden';} }
function closeModal(id) { var el=document.getElementById(id); if(el){el.style.display='none'; document.body.style.overflow='';} }
document.querySelectorAll('.modal-overlay').forEach(function(ov){
  ov.addEventListener('click', function(e){ if(e.target===ov) closeModal(ov.id); });
});

/* ── Sidebar ── */
var sidebar=document.getElementById('sidebar'), overlay=document.getElementById('sidebar-overlay'), menuBtn=document.getElementById('menuBtn');
function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('open');    document.body.style.overflow='hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow=''; }
menuBtn.addEventListener('click', function(){ sidebar.classList.contains('open') ? closeSidebar() : openSidebar(); });
overlay.addEventListener('click', closeSidebar);
document.querySelectorAll('.nav-item').forEach(function(item){
  item.addEventListener('click', function(){ if(window.innerWidth<=768) closeSidebar(); });
});
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeSidebar(); });
var rTimer; window.addEventListener('resize', function(){ clearTimeout(rTimer); rTimer=setTimeout(function(){ if(window.innerWidth>768) closeSidebar(); },150); });

/* ── Report Tabs ── */
var panels = ['strand','top','failing','enrollment','subjects','teachers','sections','quizzes'];
function switchTab(name) {
  // tabs
  document.querySelectorAll('.report-tab').forEach(function(t){ t.classList.remove('active'); });
  event.currentTarget.classList.add('active');
  // panels
  panels.forEach(function(p){
    var el = document.getElementById('panel-'+p);
    if(el) el.classList.toggle('active', p === name);
  });
}

/* ── Toast ── */
function showToast(msg, type) {
  type = type || 'success';
  var icons = {success:'✅',error:'❌',info:'ℹ️'};
  var c = document.getElementById('toast-container');
  var t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = '<span class="toast-icon">'+(icons[type]||'✅')+'</span><span>'+msg+'</span>';
  c.appendChild(t);
  setTimeout(function(){ t.style.opacity='0'; t.style.transform='translateX(60px)'; t.style.transition='all .3s ease'; setTimeout(function(){ t.remove(); },300); }, 3000);
}
</script>
</body>
</html>