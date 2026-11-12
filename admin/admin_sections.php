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

    if (!empty($u['profile_pic'])) {
        $admin_pic = '../uploads/profile_pic/admin/' . $u['profile_pic'];
    }
}
 $stmtU->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_action'])) {

    $action = $_POST['api_action'];
    $response = ['success' => false, 'message' => 'Invalid request'];

    if ($action === 'create') {
        
        $grade_id   = intval($_POST['grade_level_id'] ?? 0);
        $section    = trim($_POST['section'] ?? '');

        if ($grade_id < 1 || empty($section)) {
            $response['message'] = "Grade Level and Section Name are required.";
        } else {
            $sql = "INSERT INTO tblsections (grade_level_id, section, created_at) VALUES (?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $grade_id, $section);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Section created successfully.";
            } else {
                $response['message'] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    elseif ($action === 'update') {

        $id         = intval($_POST['section_db_id'] ?? 0);
        $grade_id   = intval($_POST['grade_level_id'] ?? 0);
        $section    = trim($_POST['section'] ?? '');

        if ($id < 1 || $grade_id < 1 || empty($section)) {
            $response['message'] = "Invalid data.";
        } else {
            $sql = "UPDATE tblsections SET 
                    grade_level_id = ?, 
                    section = ?, 
                    updated_at = NOW() 
                    WHERE section_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isi", $grade_id, $section, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Section updated successfully.";
            } else {
                $response['message'] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    elseif ($action === 'delete') {

        $id = intval($_POST['section_db_id'] ?? 0);

        if ($id < 1) {
            $response['message'] = "Invalid ID.";
        } else {
            $stmt = $conn->prepare("DELETE FROM tblsections WHERE section_id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Section deleted successfully.";
            } else {
                $response['message'] = "Failed to delete: " . $stmt->error;
            }

            $stmt->close();
        }
    }

    echo json_encode($response);
    exit();
}

 $grade_levels_db = [];
 $gl_stmt = $conn->prepare("SELECT grade_id, grade_level FROM tblgrade_levels");
 $gl_stmt->execute();
 $gl_res = $gl_stmt->get_result();
while ($r = $gl_res->fetch_assoc()) {
    $grade_levels_db[] = [
        'grade_id' => (int)$r['grade_id'],
        'grade_level' => $r['grade_level']
    ];
}
 $gl_stmt->close();

 $sections_db = [];
 $sql = "SELECT s.section_id, s.grade_level_id, s.section, s.created_at, s.updated_at, g.grade_level 
        FROM tblsections s 
        JOIN tblgrade_levels g ON s.grade_level_id = g.grade_id 
        ORDER BY g.grade_level ASC, s.section ASC";

 $stmt = $conn->prepare($sql);
 $stmt->execute();
 $res = $stmt->get_result();

while ($r = $res->fetch_assoc()) {
    $sections_db[] = [
        'id'            => (int)$r['section_id'],
        'grade_id'      => (int)$r['grade_level_id'],
        'name'          => $r['section'],
        'grade_level'   => $r['grade_level'],
        'created_at'    => date('M j, Y', strtotime($r['created_at'])),
        'updated_at'    => date('M j, Y', strtotime($r['updated_at']))
    ];
}
 $stmt->close();

 $sections_json = json_encode($sections_db);
 $grade_levels_json = json_encode($grade_levels_db);

  // ================= COUNT: TEACHERS =================
  $teacherCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblusers WHERE role = 'teacher'");
  $teacherCountStmt->execute();
  $teacherCount = $teacherCountStmt->get_result()->fetch_assoc()['total'];
  $teacherCountStmt->close();

  // ================= COUNT: STUDENTS =================
  $studentCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblstudents");
  $studentCountStmt->execute();
  $studentCount = $studentCountStmt->get_result()->fetch_assoc()['total'];
  $studentCountStmt->close();

  // ================= COUNT: STRANDS =================
  $strandCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblstrands");
  $strandCountStmt->execute();
  $strandCount = $strandCountStmt->get_result()->fetch_assoc()['total'];
  $strandCountStmt->close();

  // ================= COUNT: SECTIONS =================
  $sectionCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblsections");
  $sectionCountStmt->execute();
  $sectionCount = $sectionCountStmt->get_result()->fetch_assoc()['total'];
  $sectionCountStmt->close();
  
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
<title>ExceLearn — Grade & Sections</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin/admin_sections.css">
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

.s-card {
    display: flex !important;
    align-items: flex-start !important;
    gap: 10px;
    padding: 14px;
    border-radius: 10px;
    background: var(--bg1);
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    margin-bottom: 10px;
  }
  .sc-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    border-bottom: 1px solid var(--border);
    font-size: 12px;
  }
  .sc-row:last-child {
    border-bottom: none;
  }
  .sc-label {
    color: var(--text3);
    font-weight: 500;
  }
  .sc-value {
    color: var(--text2);
    text-align: right;
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
    <a class="nav-item active" href="admin_sections.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      Grade & Sections
      <span class="nav-badge"><?php echo $sectionCount; ?></span>
    </a>
    <a class="nav-item" href="admin_strands.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Strands<span class="nav-badge"><?php echo $strandCount; ?></span>
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
      <div class="user-info"><p><?php echo htmlspecialchars($admin_name); ?></p><span>Administrator</span></div>
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
    <div class="section-header">
      <div>
        <div class="section-title">📋 Section Management</div>
        <div class="section-sub">Manage grade levels and their corresponding sections.</div>
      </div>
      <button class="btn btn-primary" onclick="openAddModal()">+ Add Section</button>
    </div>

    <div class="mini-stats" id="mini-stats"></div>

    <div class="filter-bar">
      <div class="search-wrap">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="searchInput" placeholder="Search by section name or grade level..." oninput="applyFilters()">
      </div>
      <div class="filter-chips" id="filter-chips"></div>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
      <div class="table-wrap">
        <table class="data-table" id="data-table">
          <thead>
            <tr>
              <th style="width:40px;text-align:center">#</th>
              <!-- MOVED: Grade Level Column is now first -->
              <th><span class="sort-btn" onclick="sortBy('grade')">Grade Level <span class="sort-arrow" id="sort-grade">↕</span></span></th>
              <!-- MOVED: Section Name Column is now second -->
              <th><span class="sort-btn" onclick="sortBy('name')">Section <span class="sort-arrow" id="sort-name">↕</span></span></th>
              <th>Date Added</th>
              <th style="width:80px">Actions</th>
            </tr>
          </thead>
          <tbody id="table-body"></tbody>
        </table>
      </div>

      <div class="mobile-list" id="mobile-list" style="padding:14px"></div>

      <div class="pagination" style="padding:14px 16px" id="pagination"></div>
    </div>
  </div>
</main>
</div>

<!-- ADD / EDIT MODAL -->
<div class="modal-overlay" id="section-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-title">Add New Section</div>
      <button class="modal-close" onclick="closeModal('section-modal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-id">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Grade Level *</label>
          <select id="f-year">
            <option value="">Select grade level</option>
            <!-- Populated via JS from PHP data -->
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Section Name *</label>
        <input type="text" id="f-section" placeholder="e.g. Section A, Block 1" style="text-transform:capitalize">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('section-modal')">Cancel</button>
      <button class="btn btn-primary" id="modal-save-btn" onclick="saveSection()">Create Section</button>
    </div>
  </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="delete-modal">
  <div class="modal" style="max-width:400px">
    <div class="confirm-body">
      <div class="confirm-icon">⚠️</div>
      <div class="confirm-text">Delete this section?</div>
      <div class="confirm-sub" id="delete-confirm-text">This action cannot be undone.</div>
      <div class="confirm-actions">
        <button class="btn btn-ghost" onclick="closeModal('delete-modal')">Cancel</button>
        <button class="btn btn-danger" onclick="confirmDelete()">Delete Section</button>
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
/* ========== DATA FROM PHP ========== */
var sections = <?php echo $sections_json; ?>;
var dbGradeLevels = <?php echo $grade_levels_json; ?>;

/* ========== STATE ========== */
var currentFilter = "All";
var currentPage = 1;
var perPage = 10;
var sortField = "name";
var sortDir = "asc";
var selectedIds = {};
var deleteTargetId = null;

/* ========== FILTER & SORT ========== */
function getFiltered() {
  var q = document.getElementById('searchInput').value.toLowerCase().trim();
  var list = sections.filter(function(s) {
    var matchGrade = currentFilter === "All" || s.grade_level === currentFilter;
    if (!matchGrade) return false;
    if (!q) return true;
    var full = (s.name + " " + s.grade_level).toLowerCase();
    return full.indexOf(q) !== -1;
  });

  list.sort(function(a, b) {
    var va, vb;
    if (sortField === "name") { va = a.name.toLowerCase(); vb = b.name.toLowerCase(); }
    else if (sortField === "grade") { va = a.grade_level; vb = b.grade_level; }
    else { va = a.name.toLowerCase(); vb = b.name.toLowerCase(); }
    if (va < vb) return sortDir === "asc" ? -1 : 1;
    if (va > vb) return sortDir === "asc" ? 1 : -1;
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
  ["name","grade"].forEach(function(f) {
    var el = document.getElementById("sort-" + f);
    if (!el) return;
    if (f === sortField) { el.textContent = sortDir === "asc" ? "↑" : "↓"; el.className = "sort-arrow " + sortDir; }
    else { el.textContent = "↕"; el.className = "sort-arrow"; }
  });
}

function applyFilters() { currentPage = 1; render(); }
function setFilter(filter) { currentFilter = filter; currentPage = 1; renderChips(); render(); }

/* ========== RENDER ========== */
function render() {
  var filtered = getFiltered();
  var totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  if (currentPage > totalPages) currentPage = totalPages;
  var start = (currentPage - 1) * perPage;
  var pageItems = filtered.slice(start, start + perPage);
  renderTable(pageItems);
  renderMobile(pageItems);
  renderPagination(filtered.length, totalPages);
  renderMiniStats();
}

function renderTable(items) {
  var tbody = document.getElementById("table-body");
  if (items.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text3);font-size:14px">No sections found</td></tr>';
    return;
  }
  var start = (currentPage - 1) * perPage;
  tbody.innerHTML = items.map(function(s, idx) {
    return '<tr>' +
      '<td style="text-align:center;color:var(--text3);font-weight:500">' + (start + idx + 1) + '</td>' +
      '<td><span class="badge badge-blue">' + s.grade_level + '</span></td>' +
      '<td>' +
        '<div class="tbl-sec-info">' +
          '<div class="tbl-sec-icon" style="background:var(--accent-glow)">' + s.name.charAt(0) + '</div>' +
          '<div>' +
            '<div class="tbl-sec-name">' + s.name + '</div>' +
          '</div>' +
        '</div>' +
      '</td>' +
      '<td style="color:var(--text2);font-size:12px">' + s.created_at + '</td>' +
      '<td>' +
        '<div class="tbl-actions">' +
          '<button class="btn btn-ghost btn-xs" onclick="openEditModal(' + s.id + ')" title="Edit">✏️</button>' +
          '<button class="btn btn-danger btn-xs" onclick="openDeleteModal(' + s.id + ')" title="Delete">🗑️</button>' +
        '</div>' +
      '</td>' +
    '</tr>';
  }).join('');
}

function renderPagination(total, totalPages) {
  var el = document.getElementById("pagination");
  var start = (currentPage - 1) * perPage + 1;
  var end = Math.min(currentPage * perPage, total);
  if (total === 0) start = 0;
  var btns = '';
  btns += '<button class="page-btn" onclick="goPage(' + (currentPage - 1) + ')"' + (currentPage <= 1 ? ' disabled' : '') + '>‹</button>';
  var pages = [];
  if (totalPages <= 5) { for (var i = 1; i <= totalPages; i++) pages.push(i); }
  else {
    pages.push(1);
    if (currentPage > 3) pages.push('...');
    for (var j = Math.max(2, currentPage - 1); j <= Math.min(totalPages - 1, currentPage + 1); j++) pages.push(j);
    if (currentPage < totalPages - 2) pages.push('...');
    pages.push(totalPages);
  }
  pages.forEach(function(p) {
    if (p === '...') btns += '<div class="page-dots">…</div>';
    else btns += '<button class="page-btn' + (p === currentPage ? ' active' : '') + '" onclick="goPage(' + p + ')">' + p + '</button>';
  });
  btns += '<button class="page-btn" onclick="goPage(' + (currentPage + 1) + ')"' + (currentPage >= totalPages ? ' disabled' : '') + '>›</button>';
  el.innerHTML = '<div class="pagination-info">Showing ' + start + '–' + end + ' of ' + total + ' sections</div><div class="pagination-btns">' + btns + '</div>';
}

function goPage(p) {
  var filtered = getFiltered();
  var totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  if (p < 1 || p > totalPages) return;
  currentPage = p;
  render();
  document.querySelector('.card').scrollIntoView({ behavior:'smooth', block:'start' });
}

function renderChips() {
  // Get unique grade levels
  var grades = [];
  dbGradeLevels.forEach(function(g){ if(grades.indexOf(g.grade_level)===-1) grades.push(g.grade_level); });
  var filters = ["All"].concat(grades);
  var el = document.getElementById("filter-chips");
  el.innerHTML = filters.map(function(f) {
    var count = f === "All" ? sections.length : sections.filter(function(s){return s.grade_level===f}).length;
    return '<div class="chip' + (currentFilter === f ? ' active' : '') + '" onclick="setFilter(\'' + f + '\')">' + f + ' <span style="opacity:.6;margin-left:2px">' + count + '</span></div>';
  }).join('');
}

function renderMiniStats() {
  var total = sections.length;
  var el = document.getElementById("mini-stats");
  el.innerHTML =
    '<div class="mini-stat"><div class="mini-stat-icon" style="background:var(--accent-glow)">📋</div><div><div class="mini-stat-val">' + total + '</div><div class="mini-stat-lbl">Total Sections</div></div></div>' +
    '<div class="mini-stat"><div class="mini-stat-icon" style="background:var(--cyan-bg)">🎓</div><div><div class="mini-stat-val" style="color:var(--cyan)">' + dbGradeLevels.length + '</div><div class="mini-stat-lbl">Grade Levels</div></div></div>';
}

function renderMobile(items) {
  var el = document.getElementById("mobile-list");
  if (sections.length === 0) {
    el.innerHTML = '<div style="text-align:center;padding:40px 20px;color:var(--text3)">No sections added yet.</div>';
    return;
  }
  if (items.length === 0) {
    el.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3);font-size:14px">No sections found</div>';
    return;
  }
  var start = (currentPage - 1) * perPage;
  el.innerHTML = items.map(function(s, idx) {
    return '<div class="s-card">' +
      '<div class="tbl-sec-icon" style="background:var(--accent-glow);flex-shrink:0">' + (start + idx + 1) + '</div>' +
      '<div style="flex:1;min-width:0">' +
        '<div class="sc-row"><span class="sc-label">Grade Level</span><span class="sc-value"><span class="badge badge-blue">' + s.grade_level + '</span></span></div>' +
        '<div class="sc-row"><span class="sc-label">Section</span><span class="sc-value">' + s.name + '</span></div>' +
        '<div class="sc-row"><span class="sc-label">Date Added</span><span class="sc-value">' + s.created_at + '</span></div>' +
      '</div>' +
      '<div class="sc-actions">' +
        '<button class="btn btn-ghost btn-xs" onclick="openEditModal(' + s.id + ')" title="Edit">✏️</button>' +
        '<button class="btn btn-danger btn-xs" onclick="openDeleteModal(' + s.id + ')" title="Delete">🗑️</button>' +
      '</div>' +
    '</div>';
  }).join('');
}

/* ========== MODALS ========== */
function openModal(id) { document.getElementById(id).classList.add("open"); document.body.style.overflow="hidden"; }
function closeModal(id) { document.getElementById(id).classList.remove("open"); document.body.style.overflow=""; }
function openLogoutModal() { openModal("logout-modal"); }

function populateDropdowns() {
  var yearSel = document.getElementById("f-year");
  yearSel.innerHTML = '<option value="">Select grade level</option>' +
    dbGradeLevels.map(function(g){ return '<option value="' + g.grade_id + '">' + g.grade_level + '</option>'; }).join('');
}

function openAddModal() {
  populateDropdowns();
  document.getElementById("modal-title").textContent = "Add New Section";
  document.getElementById("modal-save-btn").textContent = "Create Section";
  document.getElementById("edit-id").value = "";
  document.getElementById("f-year").value = "";
  document.getElementById("f-section").value = "";
  openModal("section-modal");
}

function openEditModal(id) {
  populateDropdowns();
  var s = sections.find(function(x){return x.id===id});
  if (!s) return;
  document.getElementById("modal-title").textContent = "Edit Section — " + s.name;
  document.getElementById("modal-save-btn").textContent = "Save Changes";
  document.getElementById("edit-id").value = s.id;
  document.getElementById("f-year").value = s.grade_id;
  document.getElementById("f-section").value = s.name;
  openModal("section-modal");
}

function saveSection() {
  var editId = document.getElementById("edit-id").value;
  var grade_id = document.getElementById("f-year").value;
  var name = document.getElementById("f-section").value.trim();

  if (!grade_id || !name) {
    showToast("Please fill in grade level and section name", "error");
    return;
  }

  var formData = new FormData();
  formData.append('api_action', editId ? 'update' : 'create');
  formData.append('section_db_id', editId);
  formData.append('grade_level_id', grade_id);
  formData.append('section', name);

  fetch('admin_sections.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      var msg = editId ? "Section updated" : "Section created";
      showToast(msg, "success");
      closeModal("section-modal");
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast(data.message, "error");
    }
  })
  .catch(e => showToast("Network error", "error"));
}

function openDeleteModal(id) {
  deleteTargetId = id;
  var s = sections.find(function(x){return x.id===id});
  if (!s) return;
  document.getElementById("delete-confirm-text").textContent = "Are you sure you want to delete " + s.name + " (" + s.grade_level + ")? This action cannot be undone.";
  openModal("delete-modal");
}

function confirmDelete() {
  if (deleteTargetId === null) return;
  
  var formData = new FormData();
  formData.append('api_action', 'delete');
  formData.append('section_db_id', deleteTargetId);

  fetch('admin_sections.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if(data.success) {
      showToast(data.message, "error");
      closeModal("delete-modal");
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast(data.message, "error");
    }
  })
  .catch(e => showToast("Network error", "error"));
}

/* ========== TOAST ========== */
function showToast(msg, type) {
  type = type || "success";
  var icons = { success:"✅", error:"❌", info:"ℹ️" };
  var container = document.getElementById("toast-container");
  var t = document.createElement("div");
  t.className = "toast " + type;
  t.innerHTML = '<span class="toast-icon">' + (icons[type]||"✅") + '</span><span>' + msg + '</span>';
  container.appendChild(t);
  setTimeout(function() {
    t.style.opacity = "0"; t.style.transform = "translateX(60px)"; t.style.transition = "all .3s ease";
    setTimeout(function(){ t.remove(); }, 300);
  }, 3000);
}

/* ========== SIDEBAR ========== */
var sidebar = document.getElementById("sidebar");
var overlay = document.getElementById("sidebar-overlay");
var menuBtn = document.getElementById("menuBtn");
function openSidebar() { sidebar.classList.add("open"); overlay.classList.add("open"); document.body.style.overflow="hidden"; }
function closeSidebar() { sidebar.classList.remove("open"); overlay.classList.remove("open"); document.body.style.overflow=""; }
menuBtn.addEventListener("click", function() { if (sidebar.classList.contains("open")) closeSidebar(); else openSidebar(); });
overlay.addEventListener("click", closeSidebar);
document.querySelectorAll(".nav-item").forEach(function(item) { item.addEventListener("click", function() { if (window.innerWidth<=768) closeSidebar(); }); });
document.addEventListener("keydown", function(e) { if (e.key==="Escape") { closeSidebar(); closeModal("section-modal"); closeModal("delete-modal"); closeModal("logout-modal"); } });
document.querySelectorAll(".modal-overlay").forEach(function(ov) { ov.addEventListener("click", function(e) { if (e.target === ov) closeModal(ov.id); }); });

/* ========== THEME ========== */
var savedTheme = '<?= htmlspecialchars($savedTheme) ?>';
localStorage.setItem('excelearn-theme', savedTheme);
if (savedTheme === 'light') document.body.classList.add('light');
/* ========== RESIZE ========== */
var resizeTimer;
window.addEventListener("resize", function(){ clearTimeout(resizeTimer); resizeTimer=setTimeout(function(){ if(window.innerWidth>768) closeSidebar(); },150); });

/* ========== INIT ========== */
renderChips();
updateSortArrows();
render();
</script>
</body>
</html>