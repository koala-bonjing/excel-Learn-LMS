<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduFlow LMS — About</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin/index.css">
<link rel="stylesheet" href="../assets/css/admin/admin_about.css">
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
.about-hero-name {
  margin-top: 50px;
}

/* Mobile fix */
@media (max-width: 576px) {
  .about-hero-name {
    margin-top: -10px;
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
    <div class="nav-section-label" style="margin-top:14px">Account</div>
    <a class="nav-item" href="admin_profile.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      My Profile
    </a>
    <a class="nav-item" href="settings.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33h0A1.65 1.65 0 009 3.09V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51h0a1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82v0a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
      Settings
    </a>
    <a class="nav-item active" href="about.php">
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

<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>
<main class="main">
  <div class="topbar">
  <button class="menu-btn" onclick="toggleSidebar()"><div class="hamburger"><span></span><span></span><span></span></div></button>
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

    <!-- ===== HERO ===== -->
    <div class="about-hero fade-in">
      <div class="about-banner">
        
        <div class="about-banner-bg">
          <img src="../assets/img/Excelsior_Seal_Logo_cover.jpg" 
              alt="EPA Logo"
              onerror="this.style.display='none';this.parentElement.classList.add('no-image')">
        </div>

        <div class="about-banner-pattern"></div>
        <div class="about-banner-shape1"></div>
        <div class="about-banner-shape2"></div>
        <div class="about-banner-shape3"></div>

      </div>
      <div class="about-hero-content">
        <div class="about-logo-wrap">
          <div class="about-logo">
            <img src="../assets/img/Excelsior_Seal_Logo.png" alt="EPA Logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div class="about-logo-fallback" style="display:none">EPA</div>
          </div>
        </div>
        <div class="about-hero-info">
          <div class="about-hero-name">Excelsior Polytechnic Academy</div>
          <div class="about-hero-tagline">Excelsior Polytechnic Academy, Inc. is a private educational institution offering Senior High School, Preschool, Kindergarten, and Grade 1, with campuses in Cabitan, Mandaon, Masbate and in Crossing, Uson, Masbate. Visit us!</div>
          <div class="about-hero-tagline">« in avanti, in alto, sempre »</div>
          <div class="about-hero-badges">
            <span class="badge badge-blue">📍 Cabitan, Mandaon</span>
            <span class="badge badge-green">🎓 DepEd Recognized</span>
            <span class="badge badge-amber">💻 Blended Learning</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== VISION & MISSION ===== -->
    <div class="vm-grid fade-in">
      <div class="vm-card">
        <div class="vm-card-glow" style="background:var(--school-blue)"></div>
        <div class="vm-card-icon" style="background:rgba(30,60,114,.15);color:var(--school-blue-light)">🔭</div>
        <div class="vm-card-label" style="color:var(--school-blue-light)">Our Vision</div>
        <div class="vm-card-text">Excelsior Polytechnic Academy is a leading educational institution that produces innovative, competent and resilient graduates for social development among Masbateños by <strong style="color:var(--text)">2030</strong>.</div>
      </div>
      <div class="vm-card">
        <div class="vm-card-glow" style="background:var(--school-orange)"></div>
        <div class="vm-card-icon" style="background:rgba(247,151,30,.12);color:var(--school-orange)">🎯</div>
        <div class="vm-card-label" style="color:var(--school-orange)">Our Mission</div>
        <div class="vm-card-text">Excelsior Polytechnic Academy commits in providing dynamic and relevant educational experiences in a <strong style="color:var(--text)">learner-friendly, gender-sensitive, safe and motivating environment</strong> for optimum development of life-long learners through a competency-based blended learning approach.</div>
      </div>
    </div>

    <!-- ===== CORE VALUES ===== -->
    <div class="core-values-section fade-in">
      <div class="core-values-header">
        <div><div class="section-title" style="margin-bottom:2px">Core Values</div><div class="core-values-sub">The foundation of everything we do at Excelsior Polytechnic Academy</div></div>
        <div class="core-values-badge">e-CORE</div>
      </div>
      <div class="core-values-grid">
        <div class="core-value-card">
          <div class="core-value-card-glow" style="background:var(--school-blue)"></div>
          <div class="core-value-letter" style="background:linear-gradient(135deg,var(--school-blue),var(--school-blue-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">E</div>
          <div class="core-value-word">Excellence</div>
          <div class="core-value-desc">Striving for the highest standards in every endeavor — academically, personally, and as a community.</div>
        </div>
        <div class="core-value-card">
          <div class="core-value-card-glow" style="background:var(--school-blue-light)"></div>
          <div class="core-value-letter" style="background:linear-gradient(135deg,var(--school-blue-light),#4a7fd4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">C</div>
          <div class="core-value-word">Competence</div>
          <div class="core-value-desc">Equipping learners with the knowledge, skills, and confidence to thrive in a rapidly evolving world.</div>
        </div>
        <div class="core-value-card">
          <div class="core-value-card-glow" style="background:var(--school-orange)"></div>
          <div class="core-value-letter" style="background:linear-gradient(135deg,var(--school-orange),var(--school-orange-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">R</div>
          <div class="core-value-word">Resilience</div>
          <div class="core-value-desc">Building the strength and determination to overcome challenges and emerge stronger every time.</div>
        </div>
      </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <div class="about-footer-card fade-in">
      <div class="about-footer-card-bg"></div>
      <div class="about-footer-card-content">
        <div class="about-footer-logo">Excelsior Polytechnic Academy</div>
        <div class="about-footer-tagline">"Learn Better. Anytime, Anywhere."</div>
        <div class="about-footer-text">Dedicated to shaping the future of Masbateño youth through innovative education, blended learning technology, and unwavering commitment to the e-CORE values of Excellence, Competence, and Resilience.</div>
      </div>
    </div>

    <div style="text-align:center;padding:20px 0 8px;font-size:12px;color:var(--text3)">
      ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © JBH: 2025–2026
    </div>
  </div>
</main>
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
function openModal(id) { document.getElementById(id).classList.add("open"); document.body.style.overflow="hidden"; }
function closeModal(id) { document.getElementById(id).classList.remove("open"); document.body.style.overflow=""; }
function openLogoutModal() { openModal("logout-modal"); }

function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebar-overlay').classList.toggle('open')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebar-overlay').classList.remove('open')}
/* ========== THEME ========== */
var savedTheme = '<?= htmlspecialchars($savedTheme) ?>';
localStorage.setItem('excelearn-theme', savedTheme);
if (savedTheme === 'light') document.body.classList.add('light');function showToast(msg,type){type=type||'success';var icons={success:'✅',error:'❌',info:'ℹ️'};var container=document.getElementById('toast-container');var t=document.createElement('div');t.className='toast '+type;t.innerHTML='<span class="toast-icon">'+(icons[type]||'✅')+'</span><span>'+msg+'</span>';container.appendChild(t);setTimeout(function(){t.style.opacity='0';t.style.transform='translateX(60px)';t.style.transition='all .3s ease';setTimeout(function(){t.remove()},300)},3000)}
(function(){var observer=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add('visible');observer.unobserve(entry.target)}})},{threshold:0.06,rootMargin:'0px 0px -30px 0px'});document.querySelectorAll('.fade-in').forEach(function(el){observer.observe(el)})})();
</script>
</body>
</html>