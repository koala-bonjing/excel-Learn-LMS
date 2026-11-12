<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn LMS — Teacher Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{
    --bg:#0e0f14;--bg2:#16181f;--bg3:#1e2029;--bg4:#252733;
    --border:#2e3140;--border2:#3a3d52;
    --text:#e8eaf0;--text2:#9da3b8;--text3:#636880;
    --accent:#6c8fff;--accent2:#4d6fff;--accent-glow:rgba(108,143,255,.18);
    --green:#3dd68c;--green-bg:rgba(61,214,140,.12);
    --amber:#f5a623;--amber-bg:rgba(245,166,35,.12);
    --red:#ff6b6b;--red-bg:rgba(255,107,107,.12);
    --purple:#b57bee;--purple-bg:rgba(181,123,238,.12);
    --cyan:#22d3ee;--cyan-bg:rgba(34,211,238,.12);
    --teal:#14b8a6;--teal-bg:rgba(20,184,166,.12);
    --radius:12px;--radius-sm:8px;--radius-lg:18px;
    --shadow:0 4px 24px rgba(0,0,0,.4);
    --transition:all .2s cubic-bezier(.4,0,.2,1);
    --sidebar-w:264px;
    --sidebar-active-grad:linear-gradient(135deg,#1e3c72,#2a5298,#f7971e);
    --sidebar-active-text:#fff;
    --sidebar-active-border:#f7971e;
    --sidebar-active-badge-bg:#fff;
    --sidebar-active-badge-text:#2a5298;
  }
  body.light{
    --bg:#f5f7fb;--bg2:#ffffff;--bg3:#f1f3f9;--bg4:#e7eaf3;
    --border:#d6d9e5;--border2:#c7cbe0;
    --text:#1e2230;--text2:#5a6175;--text3:#8b90a5;
    --accent:#4d6fff;--accent2:#3b5bdb;--accent-glow:rgba(77,111,255,.15);
    --green:#22c55e;--green-bg:rgba(34,197,94,.12);
    --amber:#f59e0b;--amber-bg:rgba(245,158,11,.12);
    --red:#ef4444;--red-bg:rgba(239,68,68,.12);
    --purple:#8b5cf6;--purple-bg:rgba(139,92,246,.12);
    --cyan:#06b6d4;--cyan-bg:rgba(6,182,212,.12);
    --teal:#14b8a6;--teal-bg:rgba(20,184,166,.12);
    --sidebar-active-grad:linear-gradient(135deg,#f5f7fa,#c3cfe2,#6dd5ed);
    --sidebar-active-text:#1e2230;
    --sidebar-active-border:#4d6fff;
    --sidebar-active-badge-bg:#4d6fff;
    --sidebar-active-badge-text:#fff;
  }
  html{font-size:16px;-webkit-text-size-adjust:100%;width:100%;overflow-x:hidden}
  body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;width:100%;max-width:100%}
  a{color:inherit;text-decoration:none}
  button{cursor:pointer;border:none;background:none;font-family:inherit;color:inherit}
  input,textarea,select{font-family:inherit;color:var(--text);background:var(--bg3);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:10px 14px;width:100%;font-size:14px;outline:none;transition:var(--transition)}
  input:focus,textarea:focus,select:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-glow)}
  select option{background:var(--bg3)}

  .layout{display:flex;min-height:100vh;width:100%;max-width:100%;overflow:hidden}
  .sidebar{width:var(--sidebar-w);background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;transition:transform .3s cubic-bezier(.4,0,.2,1);overflow:hidden}
  .main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;transition:margin .3s ease;width:100%;position:relative}

  .sidebar-logo{padding:20px 16px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-shrink:0}
  .logo-name{font-weight:800;font-size:1.15rem;letter-spacing:-.05em;line-height:1;white-space:nowrap}
  .logo-name span{color:#22c55e}
  .logo-sub{font-size:.7rem;color:#9ca3af;white-space:nowrap;margin-top:2px}

  .sidebar-nav{flex:1;padding:14px 10px;overflow-y:auto;overflow-x:hidden;-webkit-overflow-scrolling:touch}
  .nav-section-label{font-size:10px;font-weight:600;letter-spacing:1.2px;color:var(--text3);text-transform:uppercase;padding:14px 10px 6px}
  .sidebar-nav .nav-item.active{background:var(--sidebar-active-grad);color:var(--sidebar-active-text);border-radius:8px;border-left:4px solid var(--sidebar-active-border)}
  .nav-item{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;color:var(--text2);cursor:pointer;transition:var(--transition);margin-bottom:2px;user-select:none;text-decoration:none;white-space:nowrap}
  .nav-item .nav-icon{width:18px;height:18px;flex-shrink:0;opacity:.8;transition:var(--transition)}
  .nav-item.active,.nav-item:hover{background:var(--sidebar-active-grad);color:var(--sidebar-active-text)}
  .nav-item.active .nav-icon,.nav-item:hover .nav-icon{stroke:var(--sidebar-active-text);opacity:1}
  
  .sidebar-footer{padding:14px 10px;border-top:1px solid var(--border);flex-shrink:0}
  .user-card{display:flex;align-items:center;gap:10px;padding:10px;border-radius:var(--radius-sm);background:var(--bg3)}
  .user-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0;color:#fff}
  .user-info p{font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .user-info span{font-size:11px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}

  .theme-toggle-wrapper{padding-top:12px;border-top:1px solid var(--border);margin-top:8px}
  .theme-toggle-btn{width:100%;padding:9px 14px;border-radius:var(--radius-sm);background:var(--bg3);border:1px solid var(--border);color:var(--text2);font-size:13px;font-weight:500;display:flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;transition:var(--transition)}
  .theme-toggle-btn:hover{border-color:var(--border2);color:var(--text)}

  .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:90;backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px)}
  .sidebar-overlay.open{display:block}

  .topbar{background:var(--bg2);border-bottom:1px solid var(--border);padding:12px 16px;display:flex;align-items:center;gap:10px;position:sticky;top:0;z-index:50;width:100%;box-sizing:border-box}
  .menu-btn{display:none;padding:8px;border-radius:var(--radius-sm);background:var(--bg3);border:1px solid var(--border);align-items:center;justify-content:center;flex-shrink:0}
  .hamburger{width:20px;height:14px;display:flex;flex-direction:column;justify-content:space-between}
  .hamburger span{display:block;height:2px;background:var(--text2);border-radius:2px;transition:var(--transition)}
  .topbar-title{font-family:'DM Serif Display',serif;font-size:18px;letter-spacing:-.3px;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .topbar-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}

  .btn{display:inline-flex;align-items:center;gap:7px;padding:8px 14px;border-radius:var(--radius-sm);font-size:13px;font-weight:500;transition:var(--transition);white-space:nowrap;cursor:pointer;border:none;font-family:inherit}
  .btn-primary{background:var(--accent);color:#fff}
  .btn-primary:hover{background:var(--accent2);box-shadow:0 0 20px var(--accent-glow)}
  .btn-ghost{background:transparent;color:var(--text2);border:1.5px solid var(--border)}
  .btn-ghost:hover{border-color:var(--border2);color:var(--text);background:var(--bg3)}
  .btn-sm{padding:5px 10px;font-size:12px}
  .btn-icon{padding:8px;border-radius:var(--radius-sm);background:var(--bg3);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;position:relative}
  .btn-icon:hover{border-color:var(--border2);background:var(--bg4)}

  .content{padding:20px 16px;flex:1;width:100%;max-width:100%;box-sizing:border-box}

  /* PAGE HEADER */
  .page-header{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:20px;flex-wrap:wrap}
  .page-title{font-family:'DM Serif Display',serif;font-size:22px;letter-spacing:-.3px;line-height:1.3}
  .page-sub{color:var(--text2);font-size:13px;margin-top:4px}

  /* DASHBOARD GRID */
  .dashboard-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;margin-bottom:20px}
  .stat-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center}
  .stat-icon-wrap{width:48px;height:48px;border-radius:50%;background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:10px}
  .stat-val{font-size:28px;font-weight:700;color:var(--text);font-family:'DM Serif Display',serif}
  .stat-lbl{font-size:12px;color:var(--text3);text-transform:uppercase;letter-spacing:0.5px;margin-top:4px}

  /* REPORTS GRID */
  .reports-section{display:grid;grid-template-columns:1fr;gap:16px}
  .section-title{font-size:16px;font-weight:600;color:var(--text);margin-bottom:10px;display:flex;align-items:center;gap:8px}
  .section-title::before{content:'';width:4px;height:16px;background:var(--accent);border-radius:2px;display:block}

  .report-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;display:flex;flex-direction:column;gap:14px;transition:var(--transition)}
  .report-card:hover{border-color:var(--border2);transform:translateY(-2px);box-shadow:var(--shadow)}
  
  .report-header{display:flex;justify-content:space-between;align-items:flex-start}
  .report-title{font-size:15px;font-weight:600;color:var(--text)}
  .report-date{font-size:11px;color:var(--text3);margin-top:2px}
  
  .report-body{min-height:100px;display:flex;align-items:flex-end;justify-content:space-between;gap:8px;padding:10px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
  
  /* CSS BAR CHART */
  .bar-col{display:flex;flex-direction:column;align-items:center;gap:6px;flex:1;max-width:60px}
  .bar-track{width:100%;height:100px;background:var(--bg3);border-radius:4px;overflow:hidden;position:relative;display:flex;align-items:flex-end}
  .bar-fill{width:100%;background:var(--accent);border-radius:4px;transition:height 1s ease}
  .bar-fill.green{background:var(--green)}
  .bar-fill.amber{background:var(--amber)}
  .bar-label{font-size:10px;color:var(--text3);text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%}

  .report-footer{display:flex;justify-content:space-between;align-items:center}
  .report-meta{font-size:12px;color:var(--text2)}
  .report-actions{display:flex;gap:8px}

  /* LIST STYLE REPORT */
  .report-list-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border3)}
  .report-list-row:last-child{border-bottom:none}
  .list-name{font-size:13px;color:var(--text);font-weight:500}
  .list-val{font-size:13px;color:var(--accent);font-weight:600}

  /* MODAL */
  .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:20px}
  .modal-overlay.open{display:flex}
  .modal{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);width:100%;max-width:520px;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.5);animation:modalIn .25s ease}
  @keyframes modalIn{from{transform:scale(.95) translateY(10px);opacity:0}to{transform:scale(1) translateY(0);opacity:1}}
  .modal-header{padding:20px 20px 0;display:flex;align-items:center;justify-content:space-between;gap:12px}
  .modal-title{font-family:'DM Serif Display',serif;font-size:20px}
  .modal-close{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:var(--bg3);border:1px solid var(--border);transition:var(--transition);cursor:pointer;font-size:16px}
  .modal-close:hover{border-color:var(--red);background:var(--red-bg);color:var(--red)}
  .modal-body{padding:20px}
  .modal-footer{padding:0 20px 20px;display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}

  /* TOAST */
  .toast-container{position:fixed;bottom:20px;right:20px;z-index:500;display:flex;flex-direction:column;gap:10px;pointer-events:none;max-width:90vw}
  .toast{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:13px 18px;font-size:14px;display:flex;align-items:center;gap:10px;box-shadow:var(--shadow);animation:toastIn .3s ease;max-width:340px;pointer-events:auto}
  .toast.success{border-color:rgba(61,214,140,.3)}.toast.success .toast-icon{color:var(--green)}
  .toast.error{border-color:rgba(255,107,107,.3)}.toast.error .toast-icon{color:var(--red)}
  .toast.info{border-color:rgba(108,143,255,.3)}.toast.info .toast-icon{color:var(--accent)}
  @keyframes toastIn{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}
  .notif-dot{position:absolute;top:5px;right:5px;width:7px;height:7px;background:var(--red);border-radius:50%;border:2px solid var(--bg2)}

  /* ========== RESPONSIVE ========== */
  @media(max-width:768px){
    .sidebar{transform:translateX(-100%);box-shadow:4px 0 15px rgba(0,0,0,.5)}
    .sidebar.open{transform:translateX(0)}
    .sidebar-overlay.open{display:block}
    .main{margin-left:0}
    .menu-btn{display:flex}
    .content{padding:16px 12px}
    .topbar{padding:10px 12px;gap:8px}
    .topbar-logo{display:none}
    .topbar-title{font-size:16px}
    .page-header{margin-bottom:16px}
    .page-title{font-size:18px}
    .dashboard-grid{grid-template-columns:1fr 1fr;gap:8px}
    .stat-val{font-size:22px}
    .report-body{overflow-x:auto;justify-content:flex-start}
    .bar-col{min-width:40px}
  }
  @media(max-width:480px){
    .content{padding:12px 8px}
    .page-title{font-size:16px}
    .dashboard-grid{grid-template-columns:1fr}
    .modal{max-height:90vh;border-radius:var(--radius)}
    .modal-header{padding:16px 16px 0}
    .modal-body{padding:16px}
    .modal-footer{padding:0 16px 16px}
    .modal-title{font-size:17px}
    .report-actions .btn span{display:none} /* Hide text on small screens, keep icons */
    .report-actions .btn svg{margin:0}
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
    </a>
    <a class="nav-item" href="teacher_lessons.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Lessons
    </a>
    <a class="nav-item" href="teacher_quizzes.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      Quizzes
    </a>
    <a class="nav-item" href="teacher_virtual_class.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
      Virtual Class
    </a>
    <div class="nav-section-label" style="margin-top:14px">Analytics</div>
    <a class="nav-item" href="teacher_students.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Students
    </a>
    <a class="nav-item" href="teacher_grades.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Gradebook
    </a>
    <a class="nav-item active" href="teacher_reports.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Reports
    </a>
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
    <div class="theme-toggle-wrapper">
      <button class="theme-toggle-btn" id="themeToggle">
        <span id="theme-icon">🌙</span>
        <span id="theme-text">Dark Mode</span>
      </button>
    </div>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--accent),var(--purple))">MR</div>
      <div class="user-info">
        <p>Ma. Rosario Santos</p>
        <span>Science Dept. — Teacher</span>
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
    <img src="../assets/img/Excelsior_Logo_long.png" alt="Logo" style="height:38px;width:auto;">
    <div class="topbar-title"></div>
    <div class="topbar-actions">
      <button class="btn-icon" onclick="showToast('No new notifications','info')" aria-label="Notifications">
        🔔<span class="notif-dot"></span>
      </button>
    </div>
  </div>

  <div class="content">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div>
        <div class="page-title">Reports & Analytics</div>
        <div class="page-sub">Track class performance and engagement.</div>
      </div>
      <div class="page-actions">
        <button class="btn btn-primary btn-sm" onclick="showToast('Generating PDF for current term...','success')">📄 Download All</button>
      </div>
    </div>

    <!-- STATS ROW -->
    <div class="dashboard-grid">
      <div class="stat-card">
        <div class="stat-icon-wrap" style="color:var(--accent)">📈</div>
        <div class="stat-val">88%</div>
        <div class="stat-lbl">Class Avg</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrap" style="color:var(--green)">📝</div>
        <div class="stat-val">45</div>
        <div class="stat-lbl">Assignments Graded</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrap" style="color:var(--amber)">⚠️</div>
        <div class="stat-val">3</div>
        <div class="stat-lbl">At Risk</div>
      </div>
    </div>

    <!-- REPORTS SECTION -->
    <div class="reports-section">
      
      <!-- Report 1: Performance by Course -->
      <div class="section-title">Course Performance</div>
      <div class="report-card">
        <div class="report-header">
          <div>
            <div class="report-title">Average Score per Course</div>
            <div class="report-date">Academic Year 2024</div>
          </div>
          <button class="btn btn-ghost btn-sm" onclick="openViewModal('Course Performance Report')">View Details</button>
        </div>
        <div class="report-body">
          <!-- CSS Bar Chart -->
          <div class="bar-col">
            <div class="bar-track"><div class="bar-fill" style="height:92%"></div></div>
            <div class="bar-label">Biology</div>
          </div>
          <div class="bar-col">
            <div class="bar-track"><div class="bar-fill amber" style="height:75%"></div></div>
            <div class="bar-label">Chem</div>
          </div>
          <div class="bar-col">
            <div class="bar-track"><div class="bar-fill green" style="height:88%"></div></div>
            <div class="bar-label">Physics</div>
          </div>
          <div class="bar-col">
            <div class="bar-track"><div class="bar-fill" style="height:65%"></div></div>
            <div class="bar-label">Earth Sci</div>
          </div>
          <div class="bar-col">
            <div class="bar-track"><div class="bar-fill green" style="height:95%"></div></div>
            <div class="bar-label">History</div>
          </div>
        </div>
        <div class="report-footer">
          <div class="report-meta">Top performing: <b>History (95%)</b></div>
          <div class="report-actions">
            <button class="btn btn-ghost btn-sm" onclick="showToast('Downloading CSV...','info')">CSV</button>
          </div>
        </div>
      </div>

      <!-- Report 2: Student Engagement -->
      <div class="section-title">Student Engagement</div>
      <div class="report-card">
        <div class="report-header">
          <div>
            <div class="report-title">Submission & Login Trends</div>
            <div class="report-date">Last 30 Days</div>
          </div>
          <button class="btn btn-ghost btn-sm" onclick="openViewModal('Engagement Report')">View Details</button>
        </div>
        <div class="report-body" style="flex-direction:column;justify-content:center;gap:0">
           <div class="report-list-row">
             <div class="list-name">Average Login Frequency</div>
             <div class="list-val">4.2 days/week</div>
           </div>
           <div class="report-list-row">
             <div class="list-name">On-time Assignment Rate</div>
             <div class="list-val">92%</div>
           </div>
           <div class="report-list-row">
             <div class="list-name">Forum Participation</div>
             <div class="list-val">78%</div>
           </div>
        </div>
        <div class="report-footer">
          <div class="report-meta">Most active day: <b>Monday</b></div>
          <div class="report-actions">
             <button class="btn btn-primary btn-sm" onclick="openViewModal('Engagement Report')">Full Report</button>
          </div>
        </div>
      </div>

      <!-- Report 3: Grading Efficiency -->
      <div class="section-title">Grading Workflow</div>
      <div class="report-card">
        <div class="report-header">
          <div>
            <div class="report-title">Turnaround Time</div>
            <div class="report-date">Current Semester</div>
          </div>
          <button class="btn btn-ghost btn-sm" onclick="openViewModal('Grading Efficiency')">View Details</button>
        </div>
        <div class="report-body">
          <!-- CSS Bar Chart -->
          <div class="bar-col">
            <div class="bar-track"><div class="bar-fill green" style="height:40%"></div></div>
            <div class="bar-label">Quiz</div>
          </div>
          <div class="bar-col">
            <div class="bar-track"><div class="bar-fill amber" style="height:70%"></div></div>
            <div class="bar-label">Lab</div>
          </div>
          <div class="bar-col">
            <div class="bar-track"><div class="bar-fill" style="height:85%"></div></div>
            <div class="bar-label">Essay</div>
          </div>
          <div class="bar-col">
            <div class="bar-track"><div class="bar-fill" style="height:60%"></div></div>
            <div class="bar-label">Exam</div>
          </div>
        </div>
        <div class="report-footer">
          <div class="report-meta">Avg time to grade: <b>2.5 days</b></div>
          <div class="report-actions">
             <button class="btn btn-ghost btn-sm" onclick="showToast('Exporting Workflow...','info')">Export</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>
</div>

<!-- VIEW REPORT MODAL -->
<div class="modal-overlay" id="viewModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Report Details</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text2);margin-bottom:16px">This is a detailed view of the selected report. In a production environment, this would contain extensive tables, charts, and data exports.</p>
      
      <div style="background:var(--bg3);padding:12px;border-radius:8px;margin-bottom:12px;">
        <div style="font-size:12px;color:var(--text3);margin-bottom:4px">Data Range</div>
        <div style="font-size:14px;color:var(--text);font-weight:500">August 2024 - October 2024</div>
      </div>
      
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div style="background:var(--bg3);padding:12px;border-radius:8px">
          <div style="font-size:12px;color:var(--text3);margin-bottom:4px">Total Records</div>
          <div style="font-size:18px;color:var(--accent);font-weight:700">1,245</div>
        </div>
        <div style="background:var(--bg3);padding:12px;border-radius:8px">
          <div style="font-size:12px;color:var(--text3);margin-bottom:4px">Change</div>
          <div style="font-size:18px;color:var(--green);font-weight:700">+12%</div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">Close</button>
      <button class="btn btn-primary" onclick="showToast('Downloading Report PDF...','success')">Download PDF</button>
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
/* ========== SIDEBAR ========== */
var sidebar = document.getElementById('sidebar');
var overlay = document.getElementById('sidebar-overlay');
var menuBtn = document.getElementById('menuBtn');
function openSidebar() {
  sidebar.classList.add('open');
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeSidebar() {
  sidebar.classList.remove('open');
  overlay.classList.remove('open');
  document.body.style.overflow = '';
}
menuBtn.addEventListener('click', function() {
  sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
});
overlay.addEventListener('click', closeSidebar);
document.querySelectorAll('.nav-item').forEach(function(item) {
  item.addEventListener('click', function() {
    if (window.innerWidth <= 768) closeSidebar();
  });
});
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogoutModal();});
/* ========== MODAL ========== */
function openViewModal(title) {
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('viewModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  document.getElementById('viewModal').classList.remove('open');
  document.body.style.overflow = '';
}
/* Close modal on overlay click */
document.getElementById('viewModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
/* Close modal on ESC */
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeModal();
    closeSidebar();
  }
});

/* ========== THEME ========== */
var themeToggle = document.getElementById('themeToggle');
var themeIcon = document.getElementById('theme-icon');
var themeText = document.getElementById('theme-text');
function applyTheme(isLight) {
  if (isLight) {
    document.body.classList.add('light');
    themeIcon.textContent = '☀️';
    themeText.textContent = 'Light Mode';
  } else {
    document.body.classList.remove('light');
    themeIcon.textContent = '🌙';
    themeText.textContent = 'Dark Mode';
  }
}
(function() {
  if (localStorage.getItem('excelearn-theme') === 'light') applyTheme(true);
})();
themeToggle.addEventListener('click', function() {
  var isLight = document.body.classList.contains('light');
  applyTheme(!isLight);
  localStorage.setItem('excelearn-theme', !isLight ? 'light' : 'dark');
});

/* ========== TOAST ========== */
function showToast(msg, type) {
  type = type || 'success';
  var icons = {success:'✅',error:'❌',info:'ℹ️'};
  var container = document.getElementById('toast-container');
  var t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = '<span class="toast-icon">' + (icons[type]||'✅') + '</span><span>' + msg + '</span>';
  container.appendChild(t);
  setTimeout(function() {
    t.style.opacity = '0';
    t.style.transform = 'translateX(60px)';
    t.style.transition = 'all .3s ease';
    setTimeout(function(){t.remove()},300);
  }, 3000);
}

/* ========== RESIZE ========== */
var resizeTimer;
window.addEventListener('resize', function() {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(function() {
    if (window.innerWidth > 768) closeSidebar();
  }, 150);
});

/* ========== INIT ========== */
// Animate bars on load
setTimeout(function(){
  var bars = document.querySelectorAll('.bar-fill');
  bars.forEach(function(b){
    var h = b.style.height;
    b.style.height = '0%';
    setTimeout(function(){ b.style.height = h; }, 100);
  });
}, 500);

function openLogoutModal(){document.getElementById('logoutModal').classList.add('open');document.body.style.overflow='hidden';}
function closeLogoutModal(){document.getElementById('logoutModal').classList.remove('open');document.body.style.overflow='';}
function confirmLogout(){window.location.href='../logout.php';}

</script>
</body>
</html>