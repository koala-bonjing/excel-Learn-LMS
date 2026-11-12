<?php
session_start();
include '../connection.php';

// ── AUTH CHECK ──
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = 'Admin';
$admin_pic = '';
$initials = 'AD';

// Fetch Admin Info
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_action'])) {
    $action = $_POST['api_action'];
    $response = ['success' => false, 'message' => 'Invalid request'];

    if ($action === 'create') {
        $subject = trim($_POST['subject'] ?? '');
        $strand_id = intval($_POST['strand_id'] ?? 0);

        if (empty($subject) || $strand_id < 1) {
            $response['message'] = "All fields are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO tblsubjects (subject, strand_id, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("si", $subject, $strand_id);
            if ($stmt->execute()) {
                $response['success']    = true;
                $response['message']    = "Subject created successfully.";
                $response['subject_id'] = $conn->insert_id;
            } else {
                $response['message'] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['subject_id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $strand_id = intval($_POST['strand_id'] ?? 0);

        if ($id < 1 || empty($subject) || $strand_id < 1) {
            $response['message'] = "Invalid data.";
        } else {
            $stmt = $conn->prepare("UPDATE tblsubjects SET subject=?, strand_id=?, updated_at=NOW() WHERE subject_id=?");
            $stmt->bind_param("sii", $subject, $strand_id, $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Subject updated successfully.";
            } else {
                $response['message'] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['subject_id'] ?? 0);
        if ($id < 1) {
            $response['message'] = "Invalid ID.";
        } else {
            $stmt = $conn->prepare("DELETE FROM tblsubjects WHERE subject_id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Subject deleted successfully.";
            } else {
                $response['message'] = "Failed to delete: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'assign_teachers') {
        $subject_id  = intval($_POST['subject_id'] ?? 0);
        $teacher_ids = isset($_POST['teacher_ids']) ? array_map('intval', (array)$_POST['teacher_ids']) : [];

        if ($subject_id < 1) {
            $response['message'] = "Invalid subject ID.";
        } else {
            // Remove old assignments
            $del = $conn->prepare("DELETE FROM tbl_teacher_subjects WHERE subject_id = ?");
            $del->bind_param("i", $subject_id);
            $del->execute();
            $del->close();

            // Insert new ones
            if (!empty($teacher_ids)) {
                $ins = $conn->prepare("INSERT INTO tbl_teacher_subjects (teacher_id, subject_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                foreach ($teacher_ids as $tid) {
                    $ins->bind_param("ii", $tid, $subject_id);
                    $ins->execute();
                }
                $ins->close();
            }

            $response['success'] = true;
            $response['message'] = "Teachers assigned successfully.";
        }
    }

    echo json_encode($response);
    exit();
}

$strands_list = [];
$st_stmt = $conn->prepare("
    SELECT s.strand_id, s.strand, s.track, g.grade_level 
    FROM tblstrands s
    LEFT JOIN tblgrade_levels g ON s.grade_level_id = g.grade_id
    ORDER BY g.grade_level ASC, s.strand ASC
");
$st_stmt->execute();
$st_res = $st_stmt->get_result();
while ($r = $st_res->fetch_assoc()) {
    $strands_list[] = [
        'id'          => (int)$r['strand_id'],
        'name'        => $r['strand'],
        'track'       => $r['track'],
        'grade_level' => $r['grade_level'] ?? ''
    ];
}
$st_stmt->close();

// ── FETCH TEACHERS ──
$teachers_list = [];
$t_stmt = $conn->prepare("SELECT id, firstname, lastname FROM tblusers WHERE role = 'teacher' ORDER BY lastname ASC");
$t_stmt->execute();
$t_res = $t_stmt->get_result();
while ($r = $t_res->fetch_assoc()) {
    $teachers_list[] = [
        'id'   => (int)$r['id'],
        'name' => trim($r['firstname'] . ' ' . $r['lastname'])
    ];
}
$t_stmt->close();

// ── FETCH EXISTING TEACHER-SUBJECT ASSIGNMENTS ──
$ts_map = []; // subject_id => [teacher_ids]
$ts_stmt = $conn->prepare("SELECT teacher_id, subject_id FROM tbl_teacher_subjects");
$ts_stmt->execute();
$ts_res = $ts_stmt->get_result();
while ($r = $ts_res->fetch_assoc()) {
    $ts_map[(int)$r['subject_id']][] = (int)$r['teacher_id'];
}
$ts_stmt->close();

// ── FETCH SUBJECTS ──
$subjects_db = [];
$s_stmt = $conn->prepare("
    SELECT 
        sub.subject_id,
        sub.subject,
        sub.strand_id,
        sub.created_at,
        st.strand,
        st.track,
        g.grade_level
    FROM tblsubjects sub
    LEFT JOIN tblstrands st ON sub.strand_id = st.strand_id
    LEFT JOIN tblgrade_levels g ON st.grade_level_id = g.grade_id
    ORDER BY sub.subject ASC
");
$s_stmt->execute();
$s_res = $s_stmt->get_result();

$colorPalette = [
    "linear-gradient(135deg,#06b6d4,#3b82f6)",
    "linear-gradient(135deg,#8b5cf6,#06b6d4)",
    "linear-gradient(135deg,#22c55e,#06b6d4)",
    "linear-gradient(135deg,#f59e0b,#ef4444)",
    "linear-gradient(135deg,#ef4444,#6366f1)",
    "linear-gradient(135deg,#ec4899,#f59e0b)",
    "linear-gradient(135deg,#6366f1,#8b5cf6)",
    "linear-gradient(135deg,#22c55e,#84cc16)"
];
$iconPalette = ["📚","📖","✏️","🔬","🧮","🌍","🎨","🏫","💡","🧠","📝","🔭","🎓","📐","🧪"];

$count = 0;
while ($r = $s_res->fetch_assoc()) {
    $cIdx = $count % count($colorPalette);
    $iIdx = $count % count($iconPalette);

    $sid = (int)$r['subject_id'];
    $subjects_db[] = [
        'id'          => $sid,
        'name'        => $r['subject'],
        'strand_id'   => (int)$r['strand_id'],
        'strand'      => $r['strand'] ?? 'Unknown',
        'track'       => $r['track'] ?? '',
        'grade_level' => $r['grade_level'] ?? 'Unknown',
        'icon'        => $iconPalette[$iIdx],
        'color'       => $colorPalette[$cIdx],
        'created_at'  => $r['created_at'],
        'teacher_ids' => $ts_map[$sid] ?? []
    ];
    $count++;
}
$s_stmt->close();

// Encode for JS
$subjects_json     = json_encode($subjects_db);
$strands_list_json = json_encode($strands_list);
$teachers_list_json = json_encode($teachers_list);
$ts_map_json        = json_encode($ts_map);

// ── SIDEBAR COUNTS ──
$teacherCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblusers WHERE role = 'teacher'");
$teacherCountStmt->execute();
$teacherCount = $teacherCountStmt->get_result()->fetch_assoc()['total'];
$teacherCountStmt->close();

$studentCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblstudents");
$studentCountStmt->execute();
$studentCount = $studentCountStmt->get_result()->fetch_assoc()['total'];
$studentCountStmt->close();

$strandCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblstrands");
$strandCountStmt->execute();
$strandCount = $strandCountStmt->get_result()->fetch_assoc()['total'];
$strandCountStmt->close();

$sectionCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblsections");
$sectionCountStmt->execute();
$sectionCount = $sectionCountStmt->get_result()->fetch_assoc()['total'];
$sectionCountStmt->close();

$subjectCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblsubjects");
$subjectCountStmt->execute();
$subjectCount = $subjectCountStmt->get_result()->fetch_assoc()['total'];
$subjectCountStmt->close();

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
<title>ExceLearn — Subjects</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin/admin_strands.css">
<style>
@media (max-width: 768px) {
  .t-card {
    display: flex !important;
    align-items: flex-start !important;
    gap: 10px;
    position: relative;
  }
  .tc-actions {
    display: flex !important;
    flex-direction: column !important;
    gap: 4px;
    flex-shrink: 0;
    align-items: flex-end;
    margin-left: auto;
  }
    .section-header {
    flex-direction: row !important;
    align-items: center !important;
    flex-wrap: nowrap !important;
  }
  .section-header > div {
    flex: 1;
    min-width: 0;
  }
  .section-header .btn-primary {
    flex-shrink: 0;
    white-space: nowrap;
  }
    .s-card {
    display: flex !important;
    align-items: flex-start !important;
    gap: 10px;
  }
  .sc-actions {
    display: flex !important;
    flex-direction: column !important;
    gap: 4px;
    flex-shrink: 0;
    align-items: flex-end;
    margin-left: auto;
  }
  .sc-bottom {
    display: none !important;
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

<!-- ===== SIDEBAR ===== -->
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
      <span class="nav-badge"><?php echo $teacherCount; ?></span>
    </a>
    <a class="nav-item" href="admin_students.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Students
      <span class="nav-badge"><?php echo $studentCount; ?></span>
    </a>

    <div class="nav-section-label" style="margin-top:14px">Academic</div>
    <a class="nav-item" href="admin_sections.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      Grade & Sections
      <span class="nav-badge"><?php echo $sectionCount; ?></span>
    </a>
    <a class="nav-item" href="admin_strands.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Strands<span class="nav-badge"><?php echo $strandCount; ?></span>
    </a>
    <a class="nav-item active" href="admin_subjects.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Subjects
      <span class="nav-badge"><?php echo $subjectCount; ?></span>
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
    <a class="nav-item" href="#" onclick="openLogoutModal(); return false;">
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
      <div class="user-info">
        <p><?php echo htmlspecialchars($admin_name); ?></p>
        <span>Administrator</span>
      </div>
    </div>
  </div>
</aside>

<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- ===== MAIN ===== -->
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
    <!-- Header -->
    <div class="section-header">
      <div>
        <div class="section-title">📚 Subject Management</div>
        <div class="section-sub">Create, edit, and organize subjects across all strands.</div>
      </div>
      <button class="btn btn-primary" onclick="openAddModal()">+ Add Subject</button>
    </div>

    <!-- Mini Stats -->
    <div class="mini-stats" id="mini-stats"></div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-wrap">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="searchInput" placeholder="Search by subject name or strand..." oninput="applyFilters()">
      </div>
      <div class="filter-chips" id="filter-chips"></div>
    </div>

    <!-- Desktop Table -->
    <div class="card" style="padding:0;overflow:hidden">
      <div class="table-wrap">
        <table class="data-table" id="data-table">
          <thead>
            <tr>
              <th style="width:40px;text-align:center">#</th>
              <th><span class="sort-btn" onclick="sortBy('name')">Subject <span class="sort-arrow" id="sort-name">↕</span></span></th>
              <th><span class="sort-btn" onclick="sortBy('strand')">Strand <span class="sort-arrow" id="sort-strand">↕</span></span></th>
              <th><span class="sort-btn" onclick="sortBy('grade')">Grade Level <span class="sort-arrow" id="sort-grade">↕</span></span></th>
              <th>Track</th>
              <th>Teacher</th>
              <th>Date Added</th>
              <th style="width:100px">Actions</th>
            </tr>
          </thead>
          <tbody id="table-body"></tbody>
        </table>
      </div>

      <!-- Mobile Card List -->
      <div class="mobile-list" id="mobile-list" style="padding:14px"></div>

      <!-- Pagination -->
      <div class="pagination" style="padding:14px 16px" id="pagination"></div>
    </div>
  </div>
</main>
</div>

<!-- ADD / EDIT MODAL -->
<div class="modal-overlay" id="subject-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-title">Add New Subject</div>
      <button class="modal-close" onclick="closeModal('subject-modal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-id">
      <div class="form-group">
        <label class="form-label">Subject Name *</label>
        <input type="text" id="f-name" placeholder="e.g. Mathematics">
      </div>
      <div class="form-group">
        <label class="form-label">Strand *</label>
        <select id="f-strand">
          <option value="">Select Strand</option>
          <?php 
          $grouped = [];
          foreach($strands_list as $st) {
              $grouped[$st['grade_level']][] = $st;
          }
          foreach($grouped as $grade => $items): ?>
            <optgroup label="<?php echo htmlspecialchars($grade); ?>">
              <?php foreach($items as $st): ?>
                <option value="<?php echo $st['id']; ?>">
                  <?php echo htmlspecialchars($st['name']); ?> — <?php echo htmlspecialchars($st['track']); ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Assign Teacher</label>
        <select id="f-teachers" style="width:100%;background:var(--surface2);color:var(--text1);border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;box-sizing:border-box">
          <option value="">— No teacher assigned —</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Icon / Color</label>
        <div class="color-options" id="color-options"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('subject-modal')">Cancel</button>
      <button class="btn btn-primary" id="modal-save-btn" onclick="saveSubject()">Create Subject</button>
    </div>
  </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="delete-modal">
  <div class="modal" style="max-width:400px">
    <div class="confirm-body">
      <div class="confirm-icon">⚠️</div>
      <div class="confirm-text">Delete this subject?</div>
      <div class="confirm-sub" id="delete-confirm-text">This action cannot be undone.</div>
      <div class="confirm-actions">
        <button class="btn btn-ghost" onclick="closeModal('delete-modal')">Cancel</button>
        <button class="btn btn-danger" onclick="confirmDelete()">Delete Subject</button>
      </div>
    </div>
  </div>
</div>

<!-- LOGOUT MODAL -->
<div class="modal-overlay" id="logout-modal">
  <div class="modal" style="max-width:400px">
    <div class="confirm-body">
      <div class="confirm-icon">🔒</div>
      <div class="confirm-text">Logout from ExceLearn?</div>
      <div class="confirm-sub">You will be redirected to login page.</div>
      <div class="confirm-actions">
        <button class="btn btn-ghost" onclick="closeModal('logout-modal')">Cancel</button>
        <button class="btn btn-danger" onclick="window.location.href='../logout.php'">Logout</button>
      </div>
    </div>
  </div>
</div>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toast-container"></div>

<script>
/* ========== DATA FROM PHP ========== */
var subjects      = <?php echo $subjects_json; ?>;
var strandsList   = <?php echo $strands_list_json; ?>;
var teachersList  = <?php echo $teachers_list_json; ?>;

var teacherMap = {};
teachersList.forEach(function(t){ teacherMap[t.id] = t.name; });

function getTeacherNames(s) {
  if (!s.teacher_ids || s.teacher_ids.length === 0) return null;
  var names = s.teacher_ids.map(function(tid){ return teacherMap[tid] || 'Unknown'; });
  return names.join(', ');
}

/* ========== STATE ========== */
var currentFilter  = "All";
var currentPage    = 1;
var perPage        = 8;
var sortField      = "name";
var sortDir        = "asc";
var selectedIds    = {};
var deleteTargetId = null;
var selectedColor  = "linear-gradient(135deg,#06b6d4,#3b82f6)";

/* ── Badge colors per strand ── */
var strandBadgeColors = {};
strandsList.forEach(function(st, idx) {
    var palette = ["badge-cyan","badge-purple","badge-amber","badge-green","badge-blue","badge-rose","badge-orange"];
    strandBadgeColors[st.name] = palette[idx % palette.length];
});

var colorPalette = [
  { bg:"linear-gradient(135deg,#06b6d4,#3b82f6)", icon:"🔵" },
  { bg:"linear-gradient(135deg,#8b5cf6,#06b6d4)", icon:"🟣" },
  { bg:"linear-gradient(135deg,#22c55e,#06b6d4)", icon:"🟢" },
  { bg:"linear-gradient(135deg,#f59e0b,#ef4444)", icon:"🟠" },
  { bg:"linear-gradient(135deg,#ef4444,#6366f1)", icon:"🔴" },
  { bg:"linear-gradient(135deg,#ec4899,#f59e0b)", icon:"🩷" },
  { bg:"linear-gradient(135deg,#6366f1,#8b5cf6)", icon:"💜" },
  { bg:"linear-gradient(135deg,#22c55e,#84cc16)", icon:"🍃" },
  { bg:"linear-gradient(135deg,#14b8a6,#22c55e)", icon:"🩵" },
  { bg:"linear-gradient(135deg,#f59e0b,#22c55e)", icon:"🟡" },
  { bg:"linear-gradient(135deg,#64748b,#94a3b8)", icon:"⚪" },
  { bg:"linear-gradient(135deg,#92400e,#f59e0b)", icon:"🟤" }
];

/* ── Helper: format date ── */
function fmtDate(d, long) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('en-US', long
    ? { year:'numeric', month:'long',  day:'numeric' }
    : { year:'numeric', month:'short', day:'numeric' }
  );
}

/* ========== FILTER & SORT ========== */
function getFiltered() {
  var q = document.getElementById('searchInput').value.toLowerCase().trim();
  var list = subjects.filter(function(s) {
    if (currentFilter !== "All" && s.strand !== currentFilter) return false;
    if (!q) return true;
    return (s.name + " " + s.strand + " " + s.track + " " + s.grade_level).toLowerCase().indexOf(q) !== -1;
  });
  list.sort(function(a, b) {
    var va, vb;
    if      (sortField === "name")   { va = a.name.toLowerCase();         vb = b.name.toLowerCase(); }
    else if (sortField === "strand") { va = a.strand.toLowerCase();       vb = b.strand.toLowerCase(); }
    else if (sortField === "grade")  { va = a.grade_level.toLowerCase();  vb = b.grade_level.toLowerCase(); }
    else { va = ""; vb = ""; }
    if (va < vb) return sortDir === "asc" ? -1 : 1;
    if (va > vb) return sortDir === "asc" ?  1 : -1;
    return 0;
  });
  return list;
}

function sortBy(field) {
  if (sortField === field) { sortDir = sortDir === "asc" ? "desc" : "asc"; }
  else { sortField = field; sortDir = "asc"; }
  updateSortArrows();
  currentPage = 1;
  render();
}

function updateSortArrows() {
  ["name","strand","grade"].forEach(function(f) {
    var el = document.getElementById("sort-" + f);
    if (!el) return;
    if (f === sortField) { el.textContent = sortDir === "asc" ? "↑" : "↓"; el.className = "sort-arrow " + sortDir; }
    else { el.textContent = "↕"; el.className = "sort-arrow"; }
  });
}

function applyFilters() { currentPage = 1; render(); }
function setFilter(f)   { currentFilter = f; currentPage = 1; renderChips(); render(); }

/* ========== RENDER ========== */
function render() {
  var filtered   = getFiltered();
  var totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  if (currentPage > totalPages) currentPage = totalPages;
  var start     = (currentPage - 1) * perPage;
  var pageItems = filtered.slice(start, start + perPage);

  renderTable(pageItems);
  renderMobile(pageItems);
  renderPagination(filtered.length, totalPages);
  renderMiniStats();
}

function renderTable(items) {
  var tbody = document.getElementById("table-body");
  if (items.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><div class="empty-icon">📚</div><div class="empty-text">No subjects found</div><div class="empty-sub">Try adjusting your search or filter criteria.</div></div></td></tr>';
    return;
  }
  var start = (currentPage - 1) * perPage;
  tbody.innerHTML = items.map(function(s, idx) {
    var strandBadge = strandBadgeColors[s.strand] || "badge-blue";
    var teacherName = getTeacherNames(s);

    return '<tr>' +
      '<td style="text-align:center;color:var(--text3);font-weight:500">' + (start + idx + 1) + '</td>' +
      '<td>' +
        '<div class="tbl-strand-info">' +
          '<div class="tbl-strand-icon" style="background:' + s.color + '">' + s.icon + '</div>' +
          '<div><div class="tbl-strand-name expand-trigger" onclick="toggleExpand(' + s.id + ')">' + s.name + '</div></div>' +
        '</div>' +
      '</td>' +
      '<td><span class="badge ' + strandBadge + '">' + s.strand + '</span></td>' +
      '<td><span style="font-size:12px;color:var(--text2)">' + (s.grade_level || '—') + '</span></td>' +
      '<td><span style="font-family:monospace;font-size:12px;color:var(--text2)">' + (s.track || '—') + '</span></td>' +
      '<td>' + (teacherName
          ? '<span style="font-size:12px;color:var(--text1);display:flex;align-items:center;gap:5px">👤 ' + teacherName + '</span>'
          : '<span style="font-size:12px;color:var(--text3)">— Unassigned —</span>'
        ) + '</td>' +
      '<td><span style="color:var(--text3);font-size:12px">📅 ' + fmtDate(s.created_at) + '</span></td>' +
      '<td>' +
        '<div class="tbl-actions">' +
          '<button class="btn btn-ghost btn-xs" onclick="openEditModal(' + s.id + ')" title="Edit">✏️</button>' +
          '<button class="btn btn-danger btn-xs" onclick="openDeleteModal(' + s.id + ')" title="Delete">🗑️</button>' +
        '</div>' +
      '</td>' +
    '</tr>' +
    '<tr id="expand-' + s.id + '" style="display:none"><td colspan="8" style="padding:0">' +
      '<div class="user-expand" id="expand-inner-' + s.id + '">' + buildDetailHTML(s) + '</div>' +
    '</td></tr>';
  }).join('');
}

function buildDetailHTML(s) {
  var teacherName = getTeacherNames(s);
  return '<div class="detail-row"><span class="dl">Subject Name:</span><span class="dv">' + s.name + '</span></div>' +
    '<div class="detail-row"><span class="dl">Strand:</span><span class="dv">' + s.strand + '</span></div>' +
    '<div class="detail-row"><span class="dl">Grade Level:</span><span class="dv">' + (s.grade_level || '—') + '</span></div>' +
    '<div class="detail-row"><span class="dl">Track:</span><span class="dv" style="font-family:monospace">' + (s.track || '—') + '</span></div>' +
    '<div class="detail-row"><span class="dl">Assigned Teacher:</span><span class="dv">' + (teacherName || '— Unassigned —') + '</span></div>' +
    '<div class="detail-row"><span class="dl">Date Added:</span><span class="dv">' + fmtDate(s.created_at, true) + '</span></div>';
}

function toggleExpand(id) {
  var row   = document.getElementById("expand-" + id);
  var inner = document.getElementById("expand-inner-" + id);
  if (!row) return;
  if (row.style.display === "none") { row.style.display = ""; inner.classList.add("open"); }
  else { row.style.display = "none"; inner.classList.remove("open"); }
}

function renderMobile(items) {
  var el = document.getElementById("mobile-list");
  if (items.length === 0) {
    el.innerHTML = '<div class="empty-state"><div class="empty-icon">📚</div><div class="empty-text">No subjects found</div><div class="empty-sub">Try adjusting your search or filter.</div></div>';
    return;
  }
  var start = (currentPage - 1) * perPage;
  el.innerHTML = items.map(function(s, idx) {
    var strandBadge = strandBadgeColors[s.strand] || "badge-blue";
    var teacherName = getTeacherNames(s);
    return '<div class="mobile-card">' +
      '<div class="mc-top">' +
        '<div class="mc-icon" style="background:' + s.color + '">' + s.icon + '</div>' +
        '<div class="mc-info">' +
          '<div class="mc-name">' + (start + idx + 1) + '. ' + s.name + '</div>' +
          '<div class="mc-code" style="color:var(--text3);font-size:11px">' + (s.grade_level || '—') + ' · ' + (s.track || '—') + '</div>' +
        '</div>' +
      '</div>' +
      '<div class="mc-badges">' +
        '<span class="badge badge-cyan" style="font-size:10px">' + (s.grade_level || '—') + '</span>' +
        '<span class="badge ' + strandBadge + '">' + s.strand + '</span>' +
      '</div>' +
      '<div style="margin-top:8px;font-size:12px;color:' + (teacherName ? 'var(--text1)' : 'var(--text3)') + ';display:flex;align-items:center;gap:5px">' +
        '👤 ' + (teacherName || '— Unassigned —') +
      '</div>' +
      '<div class="mc-bottom">' +
        '<div style="font-size:11px;color:var(--text3)">📅 ' + fmtDate(s.created_at) + '</div>' +
        '<div class="mc-actions">' +
          '<button class="btn btn-ghost btn-xs" onclick="openEditModal(' + s.id + ')">✏️</button>' +
          '<button class="btn btn-danger btn-xs" onclick="openDeleteModal(' + s.id + ')">🗑️</button>' +
        '</div>' +
      '</div>' +
    '</div>';
  }).join('');
}

function renderPagination(total, totalPages) {
  var el    = document.getElementById("pagination");
  var start = (currentPage - 1) * perPage + 1;
  var end   = Math.min(currentPage * perPage, total);
  if (total === 0) start = 0;

  var btns = '';
  btns += '<button class="page-btn" onclick="goPage(' + (currentPage-1) + ')"' + (currentPage<=1?' disabled':'') + '>‹</button>';

  var pages = [];
  if (totalPages <= 5) { for (var i=1;i<=totalPages;i++) pages.push(i); }
  else {
    pages.push(1);
    if (currentPage > 3) pages.push('...');
    for (var j=Math.max(2,currentPage-1);j<=Math.min(totalPages-1,currentPage+1);j++) pages.push(j);
    if (currentPage < totalPages-2) pages.push('...');
    pages.push(totalPages);
  }
  pages.forEach(function(p) {
    if (p==='...') btns += '<div class="page-dots">…</div>';
    else btns += '<button class="page-btn'+(p===currentPage?' active':'')+'" onclick="goPage('+p+')">'+p+'</button>';
  });
  btns += '<button class="page-btn" onclick="goPage('+(currentPage+1)+')"'+(currentPage>=totalPages?' disabled':'')+'>›</button>';

  el.innerHTML = '<div class="pagination-info">Showing '+start+'–'+end+' of '+total+' subjects</div>' +
    '<div class="pagination-btns">'+btns+'</div>';
}

function goPage(p) {
  var filtered   = getFiltered();
  var totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  if (p < 1 || p > totalPages) return;
  currentPage = p;
  render();
  document.querySelector('.card').scrollIntoView({ behavior:'smooth', block:'start' });
}

function renderChips() {
  var strandList = [];
  subjects.forEach(function(s) { if (strandList.indexOf(s.strand) === -1) strandList.push(s.strand); });
  var filters = ["All"].concat(strandList);
  var el = document.getElementById("filter-chips");
  el.innerHTML = filters.map(function(f) {
    var count = f === "All" ? subjects.length : subjects.filter(function(s){ return s.strand === f; }).length;
    return '<div class="chip'+(currentFilter===f?' active':'')+'" onclick="setFilter(\''+f.replace(/'/g,"\\'")+'\')">'+f+' <span style="opacity:.6;margin-left:2px">'+count+'</span></div>';
  }).join('');
}

function renderMiniStats() {
  var total       = subjects.length;
  var strandCount = strandsList.length;
  var el = document.getElementById("mini-stats");
  el.innerHTML =
    '<div class="mini-stat"><div class="mini-stat-icon" style="background:var(--accent-glow)">📚</div><div><div class="mini-stat-val">'+total+'</div><div class="mini-stat-lbl">Total Subjects</div></div></div>' +
    '<div class="mini-stat"><div class="mini-stat-icon" style="background:var(--green-bg)">📐</div><div><div class="mini-stat-val" style="color:var(--green)">'+strandCount+'</div><div class="mini-stat-lbl">Strands</div></div></div>';
}

/* ========== COLOR PICKER ========== */
function renderColorPicker() {
  var el = document.getElementById("color-options");
  el.innerHTML = colorPalette.map(function(c) {
    var sel = c.bg===selectedColor?" selected":"";
    return '<div class="color-opt'+sel+'" style="background:'+c.bg+'" onclick="pickColor(\''+c.bg+'\',this)">'+c.icon+'</div>';
  }).join('');
}
function pickColor(color, el) {
  selectedColor = color;
  document.querySelectorAll('.color-opt').forEach(function(e){ e.classList.remove('selected'); });
  el.classList.add('selected');
}

/* ========== MODALS ========== */
function openModal(id)    { document.getElementById(id).classList.add("open");    document.body.style.overflow="hidden"; }
function closeModal(id)   { document.getElementById(id).classList.remove("open"); document.body.style.overflow=""; }
function openLogoutModal(){ openModal("logout-modal"); }

function populateTeacherSelect(preselected) {
  preselected = preselected || [];
  var currentId = preselected.length > 0 ? preselected[0] : '';
  var sel = document.getElementById("f-teachers");
  sel.innerHTML = '<option value="">— No teacher assigned —</option>' +
    teachersList.map(function(t) {
      var s = t.id === currentId ? ' selected' : '';
      return '<option value="' + t.id + '"' + s + '>' + t.name + '</option>';
    }).join('');
}

function syncTeacherSelect() {
  var checkboxes = document.querySelectorAll('#teacher-checkbox-list input[type=checkbox]');
  var sel = document.getElementById("f-teachers");
  checkboxes.forEach(function(chk) {
    var opt = sel.querySelector('option[value="' + chk.value + '"]');
    if (opt) opt.selected = chk.checked;
  });
  updateTeacherLabel();
}

function updateTeacherLabel() {
  var checkboxes = document.querySelectorAll('#teacher-checkbox-list input[type=checkbox]:checked');
  var lbl = document.getElementById("teacher-dropdown-label");
  if (checkboxes.length === 0) {
    lbl.textContent = "Select teachers...";
    lbl.style.color = "var(--text3)";
  } else {
    var names = [];
    checkboxes.forEach(function(chk) {
      var t = teachersList.find(function(x){ return x.id === parseInt(chk.value); });
      if (t) names.push(t.name);
    });
    lbl.textContent = names.join(", ");
    lbl.style.color = "var(--text1)";
  }
}

function toggleTeacherDropdown() {
  var list = document.getElementById("teacher-dropdown-list");
  list.style.display = list.style.display === "none" ? "block" : "none";
}

// Close dropdown when clicking outside
document.addEventListener("click", function(e) {
  var btn  = document.getElementById("teacher-dropdown-btn");
  var list = document.getElementById("teacher-dropdown-list");
  if (btn && list && !btn.contains(e.target) && !list.contains(e.target)) {
    list.style.display = "none";
  }
});

function openAddModal() {
  document.getElementById("modal-title").textContent    = "Add New Subject";
  document.getElementById("modal-save-btn").textContent = "Create Subject";
  document.getElementById("edit-id").value   = "";
  document.getElementById("f-name").value   = "";
  document.getElementById("f-strand").value = "";
  populateTeacherSelect([]);
  selectedColor = colorPalette[0].bg;
  renderColorPicker();
  openModal("subject-modal");
}

function openEditModal(id) {
  var s = subjects.find(function(x){ return x.id===id; });
  if (!s) return;
  document.getElementById("modal-title").textContent    = "Edit Subject — " + s.name;
  document.getElementById("modal-save-btn").textContent = "Save Changes";
  document.getElementById("edit-id").value   = s.id;
  document.getElementById("f-name").value   = s.name;
  document.getElementById("f-strand").value = s.strand_id;
  populateTeacherSelect(s.teacher_ids || []);
  selectedColor = s.color;
  renderColorPicker();
  openModal("subject-modal");
}

/* ========== CRUD ========== */
function getSelectedTeacherIds() {
  var sel = document.getElementById("f-teachers");
  var val = parseInt(sel.value);
  return isNaN(val) ? [] : [val];
}

function saveSubject() {
  var editId     = document.getElementById("edit-id").value;
  var name       = document.getElementById("f-name").value.trim();
  var strandId   = document.getElementById("f-strand").value;
  var teacherIds = getSelectedTeacherIds();

  if (!name || !strandId) {
    showToast("Please fill in all required fields", "error");
    return;
  }

  var fd = new FormData();
  fd.append('api_action', editId ? 'update' : 'create');
  fd.append('subject_id', editId);
  fd.append('subject',    name);
  fd.append('strand_id',  strandId);

  fetch('admin_subjects.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast(data.message, "error"); return; }

      // Now assign teachers
      var subjectId = editId || null;
      if (!subjectId && data.insert_id) subjectId = data.insert_id;

      // If creating, we need the new ID — update PHP create response to return it
      var fd2 = new FormData();
      fd2.append('api_action', 'assign_teachers');
      fd2.append('subject_id', data.subject_id || editId);
      teacherIds.forEach(function(tid){ fd2.append('teacher_ids[]', tid); });

      return fetch('admin_subjects.php', { method:'POST', body:fd2 })
        .then(r => r.json())
        .then(function(res2) {
          showToast(data.message, "success");
          closeModal("subject-modal");
          setTimeout(() => location.reload(), 1000);
        });
    })
    .catch(() => showToast("Network error", "error"));
}

function openDeleteModal(id) {
  deleteTargetId = id;
  var s = subjects.find(function(x){ return x.id===id; });
  if (!s) return;
  document.getElementById("delete-confirm-text").textContent =
    "Are you sure you want to delete \"" + s.name + "\"? This action cannot be undone.";
  openModal("delete-modal");
}

function confirmDelete() {
  if (deleteTargetId === null) return;
  var fd = new FormData();
  fd.append('api_action', 'delete');
  fd.append('subject_id', deleteTargetId);

  fetch('admin_subjects.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast(data.message, "success");
        closeModal("delete-modal");
        setTimeout(() => location.reload(), 1000);
      } else {
        showToast(data.message, "error");
      }
    })
    .catch(() => showToast("Network error", "error"));
}

/* ========== TOAST ========== */
function showToast(msg, type) {
  type = type || "success";
  var icons = { success:"✅", error:"❌", info:"ℹ️" };
  var container = document.getElementById("toast-container");
  var t = document.createElement("div");
  t.className = "toast " + type;
  t.innerHTML = '<span class="toast-icon">'+(icons[type]||"✅")+'</span><span>'+msg+'</span>';
  container.appendChild(t);
  setTimeout(function(){
    t.style.opacity="0"; t.style.transform="translateX(60px)"; t.style.transition="all .3s ease";
    setTimeout(function(){ t.remove(); }, 300);
  }, 3000);
}

/* ========== SIDEBAR ========== */
var sidebar = document.getElementById("sidebar");
var overlay = document.getElementById("sidebar-overlay");
var menuBtn = document.getElementById("menuBtn");
function openSidebar()  { sidebar.classList.add("open");    overlay.classList.add("open");    document.body.style.overflow="hidden"; }
function closeSidebar() { sidebar.classList.remove("open"); overlay.classList.remove("open"); document.body.style.overflow=""; }
menuBtn.addEventListener("click", function(){ if(sidebar.classList.contains("open")) closeSidebar(); else openSidebar(); });
overlay.addEventListener("click", closeSidebar);
document.querySelectorAll(".nav-item").forEach(function(item){
  item.addEventListener("click", function(){ if(window.innerWidth<=768) closeSidebar(); });
});
document.addEventListener("keydown", function(e){
  if(e.key==="Escape"){ closeSidebar(); closeModal("subject-modal"); closeModal("delete-modal"); closeModal("logout-modal"); }
});
document.querySelectorAll(".modal-overlay").forEach(function(ov){
  ov.addEventListener("click", function(e){ if(e.target===ov) closeModal(ov.id); });
});

/* ========== THEME ========== */
var savedTheme = '<?= htmlspecialchars($savedTheme) ?>';
localStorage.setItem('excelearn-theme', savedTheme);
if (savedTheme === 'light') document.body.classList.add('light');

/* ========== RESIZE ========== */
var resizeTimer;
window.addEventListener("resize", function(){
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(function(){ if(window.innerWidth>768) closeSidebar(); }, 150);
});

/* ========== INIT ========== */
renderChips();
updateSortArrows();
render();
</script>
</body>
</html>