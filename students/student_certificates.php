<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduFlow LMS — Certificates</title>
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
  --gold:#fbbf24;--gold-bg:rgba(251,191,36,.15);
  --radius:12px;--radius-sm:8px;--radius-lg:18px;
  --shadow:0 4px 24px rgba(0,0,0,.4);
  --transition:all .2s cubic-bezier(.4,0,.2,1);
  --sidebar-w:260px;
  --sidebar-active-grad:linear-gradient(135deg,#1e3c72,#2a5298,#f7971e);
  --sidebar-active-text:#fff;
  --sidebar-active-border:#f7971e;
  --sidebar-active-badge-bg:#fff;
  --sidebar-active-badge-text:#2a5298;
  --btn-grad:linear-gradient(135deg,#1e3c72,#2a5298,#f7971e);
  --btn-text:#fff;
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
  --gold:#d97706;--gold-bg:rgba(217,119,6,.1);
  --sidebar-active-grad:linear-gradient(135deg,#f5f7fa,#c3cfe2,#6dd5ed);
  --sidebar-active-text:#1e2230;
  --sidebar-active-border:#4d6fff;
  --sidebar-active-badge-bg:#4d6fff;
  --sidebar-active-badge-text:#ffffff;
  --btn-grad:linear-gradient(135deg,#4d6fff,#c3cfe2,#6dd5ed);
  --btn-text:#1e2230;
}
html{font-size:16px}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden}
a{color:inherit;text-decoration:none}
button{cursor:pointer;border:none;background:none;font-family:inherit;color:inherit}
input,select{font-family:inherit;color:var(--text);background:var(--bg3);border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:10px 14px;width:100%;font-size:14px;outline:none;transition:var(--transition)}
input:focus,select:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-glow)}
select option{background:var(--bg3)}

.layout{display:flex;min-height:100vh}
.sidebar{width:var(--sidebar-w);background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;transition:transform .3s ease}
.sidebar-nav .nav-item.active{background:var(--sidebar-active-grad);color:var(--sidebar-active-text);border-radius:8px;border-left:4px solid var(--sidebar-active-border)}
.sidebar-nav .nav-item:hover{background:var(--sidebar-active-grad);color:var(--sidebar-active-text)}
.sidebar-nav .nav-item.active .nav-icon,.sidebar-nav .nav-item:hover .nav-icon{stroke:var(--sidebar-active-text)}
.sidebar-nav .nav-item.active .nav-badge{background:var(--sidebar-active-badge-bg);color:var(--sidebar-active-badge-text)}
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;transition:margin .3s ease}

.sidebar-logo{padding:20px 16px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.sidebar-nav{flex:1;padding:16px 12px;overflow-y:auto}
.nav-section-label{font-size:10px;font-weight:600;letter-spacing:1.2px;color:var(--text3);text-transform:uppercase;padding:12px 10px 6px}
.nav-item{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;color:var(--text2);cursor:pointer;transition:var(--transition);margin-bottom:2px;user-select:none;text-decoration:none}
.nav-item .nav-icon{width:18px;height:18px;flex-shrink:0;opacity:.8}
.nav-badge{margin-left:auto;background:var(--accent);color:#fff;font-size:11px;font-weight:600;padding:2px 7px;border-radius:20px}
.sidebar-footer{padding:16px;border-top:1px solid var(--border)}
.user-card{display:flex;align-items:center;gap:10px;padding:10px;border-radius:var(--radius-sm);background:var(--bg3)}
.user-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0}
.user-info p{font-size:13px;font-weight:500}
.user-info span{font-size:11px;color:var(--text3)}

.topbar{background:var(--bg2);border-bottom:1px solid var(--border);padding:14px 28px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50}
.menu-btn{display:none;padding:6px;border-radius:6px;background:var(--bg3);border:1px solid var(--border)}
.topbar-title{font-family:'DM Serif Display',serif;font-size:20px;letter-spacing:-.3px;flex:1}
.topbar-actions{display:flex;align-items:center;gap:10px}

.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;transition:var(--transition);white-space:nowrap;cursor:pointer;border:none;font-family:inherit}
.btn-primary{background:var(--accent);color:#fff}
.btn-primary:hover{background:var(--accent2);box-shadow:0 0 20px var(--accent-glow)}
.btn-ghost{background:transparent;color:var(--text2);border:1.5px solid var(--border)}
.btn-ghost:hover{border-color:var(--border2);color:var(--text);background:var(--bg3)}
.btn-sm{padding:6px 12px;font-size:13px}
.btn-block{width:100%;justify-content:center}

.content{padding:28px;flex:1}

.card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:12px;flex-wrap:wrap}
.card-title{font-size:15px;font-weight:600}

.section-title{font-family:'DM Serif Display',serif;font-size:22px;letter-spacing:-.3px;margin-bottom:6px}
.section-sub{color:var(--text2);font-size:14px;margin-bottom:22px}

.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600}
.badge-blue{background:var(--accent-glow);color:var(--accent)}
.badge-green{background:var(--green-bg);color:var(--green)}
.badge-amber{background:var(--amber-bg);color:var(--amber)}
.badge-purple{background:var(--purple-bg);color:var(--purple)}
.badge-red{background:var(--red-bg);color:var(--red)}
.badge-gold{background:var(--gold-bg);color:var(--gold)}

.toast-container{position:fixed;bottom:24px;right:24px;z-index:500;display:flex;flex-direction:column;gap:10px}
.toast{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:13px 18px;font-size:14px;display:flex;align-items:center;gap:10px;box-shadow:var(--shadow);animation:slideIn .3s ease;max-width:340px}
.toast.success{border-color:rgba(61,214,140,.3)}.toast.success .toast-icon{color:var(--green)}
.toast.error{border-color:rgba(255,107,107,.3)}.toast.error .toast-icon{color:var(--red)}
@keyframes slideIn{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}

.hamburger{width:20px;height:14px;display:flex;flex-direction:column;justify-content:space-between;cursor:pointer}
.hamburger span{display:block;height:2px;background:var(--text2);border-radius:2px;transition:var(--transition)}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:90}

.theme-toggle-wrapper{padding-top:12px;border-top:1px solid var(--border)}
.theme-toggle-btn{width:100%;padding:9px 14px;border-radius:var(--radius-sm);background:var(--bg3);border:1px solid var(--border);color:var(--text2);font-size:13px;font-weight:500;display:flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;transition:var(--transition)}
.theme-toggle-btn:hover{border-color:var(--border2);color:var(--text)}

/* ===== CERTIFICATES PAGE ===== */
.filter-bar{display:flex;align-items:center;gap:12px;margin-bottom:22px;flex-wrap:wrap}
.search-box{position:relative;flex:1;min-width:200px;max-width:360px}
.search-box input{padding-left:38px}
.search-box .search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:16px;pointer-events:none;opacity:.5}
.filter-select{width:auto;min-width:150px}

.status-tabs{display:flex;gap:6px;margin-bottom:22px;flex-wrap:wrap}
.status-tab{padding:7px 16px;border-radius:20px;font-size:13px;font-weight:500;color:var(--text2);background:var(--bg3);border:1.5px solid var(--border);cursor:pointer;transition:var(--transition);white-space:nowrap}
.status-tab:hover{border-color:var(--border2);color:var(--text)}
.status-tab.active{background:var(--accent);color:#fff;border-color:var(--accent)}

.summary-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
.summary-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:16px;display:flex;align-items:center;gap:14px;transition:var(--transition)}
.summary-card:hover{border-color:var(--border2);transform:translateY(-1px)}
.summary-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.summary-value{font-size:22px;font-weight:700;letter-spacing:-.3px;line-height:1.1}
.summary-label{font-size:11px;color:var(--text3);font-weight:500;margin-top:2px}

/* Cert List (Table Wrapper) */
.cert-table-wrap{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.cert-table-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);gap:12px;flex-wrap:wrap}

.cert-table{width:100%;border-collapse:collapse}
.cert-table thead th{padding:12px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);text-align:left;border-bottom:1px solid var(--border);background:var(--bg3);white-space:nowrap}
.cert-table thead th:first-child{padding-left:20px}
.cert-table thead th:last-child{padding-right:20px}
.cert-table tbody tr{transition:var(--transition);border-bottom:1px solid var(--border)}
.cert-table tbody tr:last-child{border-bottom:none}
.cert-table tbody tr:hover{background:var(--bg3)}
.cert-table tbody td{padding:14px 16px;font-size:13px;vertical-align:middle}
.cert-table tbody td:first-child{padding-left:20px}
.cert-table tbody td:last-child{padding-right:20px}

.cert-course-name{font-weight:600;font-size:14px;color:var(--text);display:block}
.cert-id{font-size:12px;color:var(--text3);font-family:'DM Sans',monospace;margin-top:2px;display:block}
.cert-instructor{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2)}
.cert-instructor-avatar{width:24px;height:24px;border-radius:50%;background:var(--bg4);display:flex;align-items:center;justify-content:center;font-size:11px}

/* Mobile Card Fallback */
.cert-card-list{display:none;flex-direction:column;gap:10px}
.cert-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:16px;transition:var(--transition);cursor:pointer}
.cert-card:hover{border-color:var(--border2);background:var(--bg3)}
.cert-card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.cert-card-title{font-weight:600;font-size:14px}
.cert-card-date{font-size:12px;color:var(--text3)}
.cert-card-meta{display:flex;align-items:center;gap:12px;font-size:12px;color:var(--text2);margin-bottom:12px}
.cert-card-actions{display:flex;gap:8px}

/* Certificate Modal */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;display:none;align-items:center;justify-content:center;padding:24px;backdrop-filter:blur(4px);overflow-y:auto}
.modal-backdrop.open{display:flex}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);width:100%;max-width:800px;box-shadow:0 24px 64px rgba(0,0,0,.5);animation:modalIn .25s ease;position:relative}
@keyframes modalIn{from{transform:translateY(20px) scale(.97);opacity:0}to{transform:translateY(0) scale(1);opacity:1}}
.modal-close{position:absolute;top:14px;right:14px;width:32px;height:32px;border-radius:50%;background:rgba(0,0,0,.5);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;z-index:10;cursor:pointer;border:none;transition:var(--transition);backdrop-filter:blur(4px)}
.modal-close:hover{background:rgba(0,0,0,.7);transform:scale(1.1)}
.modal-body{padding:24px}

/* The Visual Certificate Paper */
.cert-paper-wrapper{background:#fff;padding:24px;border-radius:4px;box-shadow:0 10px 30px rgba(0,0,0,.2);text-align:center;position:relative;overflow:hidden;color:#1a1a1a;margin-bottom:20px;border:1px solid #e5e5e5}
.cert-paper-wrapper::before{content:'';position:absolute;inset:8px;border:2px solid #d4af37;border-radius:2px;pointer-events:none}
.cert-paper-wrapper::after{content:'';position:absolute;inset:12px;border:1px solid #d4af37;opacity:.5;border-radius:2px;pointer-events:none}

.cert-header{margin-bottom:24px}
.cert-logo{font-family:'DM Serif Display',serif;font-size:28px;font-weight:700;color:#1e3c72;display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:8px}
.cert-logo span{color:#f7971e}
.cert-subtitle{text-transform:uppercase;letter-spacing:3px;font-size:12px;font-weight:600;color:#666}

.cert-title{font-family:'DM Serif Display',serif;font-size:32px;color:#333;margin-bottom:24px;position:relative;display:inline-block}
.cert-title::after{content:'';display:block;width:60px;height:2px;background:#d4af37;margin:8px auto 0}

.cert-body{margin-bottom:30px}
.cert-presented{font-size:14px;color:#555;margin-bottom:8px}
.cert-student-name{font-family:'DM Serif Display',serif;font-size:26px;color:#000;margin-bottom:24px;letter-spacing:.5px;border-bottom:1px solid #eee;display:inline-block;padding-bottom:4px;min-width:200px}

.cert-desc{font-size:15px;line-height:1.6;color:#444;max-width:80%;margin:0 auto 24px}
.cert-course-title{font-weight:700;color:#1e3c72}

.cert-footer{display:flex;justify-content:space-between;align-items:flex-end;padding:0 20px}
.cert-signature{text-align:left}
.cert-sign-line{width:150px;height:1px;background:#333;margin-bottom:8px}
.cert-sign-label{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#666}
.cert-seal{width:80px;height:80px;background:#d4af37;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;box-shadow:0 4px 10px rgba(212,175,55,.4);border:4px solid rgba(212,175,55,.2)}

/* Modal Metadata */
.modal-meta-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:20px}
.modal-meta-item{display:flex;justify-content:space-between;padding:10px;background:var(--bg3);border-radius:var(--radius-sm);font-size:13px}
.modal-mm-label{color:var(--text3)}
.modal-mm-val{font-weight:600;color:var(--text)}

.modal-actions{display:flex;gap:10px;padding-top:16px;border-top:1px solid var(--border)}
.modal-actions .btn{flex:1;justify-content:center}
.modal-actions .btn:disabled{opacity:.5;cursor:not-allowed;filter:grayscale(1)}

/* Responsive */
@media(max-width:1100px){.summary-row{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)} .sidebar.open{transform:translateX(0)}
  .sidebar-overlay.open{display:block} .main{margin-left:0}
  .menu-btn{display:flex} .content{padding:20px 16px}
  .filter-bar{flex-direction:column;align-items:stretch} .search-box{max-width:100%}
  .cert-table-wrap{display:none} .cert-card-list{display:flex}
  .cert-paper-wrapper{padding:16px}
  .cert-logo{font-size:22px}
  .cert-title{font-size:24px}
  .cert-student-name{font-size:20px}
  .cert-desc{font-size:13px}
  .cert-footer{flex-direction:column;align-items:center;gap:20px;text-align:center}
  .cert-signature{text-align:center}
  .cert-sign-line{margin:0 auto 8px}
  .modal-actions{flex-direction:column}
}
@media(max-width:480px){
  .summary-row{grid-template-columns:1fr;gap:10px}
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
    <div class="nav-section-label">Learning</div>
    <a class="nav-item" href="index.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <a class="nav-item" href="student_courses.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      My Courses
      <span class="nav-badge">4</span>
    </a>
    <a class="nav-item" href="student_lessons.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Lessons
    </a>
    <a class="nav-item" href="student_quizzes.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      My Quizzes
      <span class="nav-badge" style="background:var(--amber);color:#000">2</span>
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
    <a class="nav-item active" href="student_certificates.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      Certificates
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
    <div class="theme-toggle-wrapper" style="margin-top:16px">
      <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeToggle">
        <span id="theme-icon">🌙</span>
        <span id="theme-text">Dark Mode</span>
      </button>
    </div>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--cyan),var(--accent))">AC</div>
      <div class="user-info">
        <p>Anna Claire Reyes</p>
        <span>Grade 10 — Einstein</span>
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
      <button class="btn btn-ghost btn-sm" onclick="showToast('No new notifications','info')" style="position:relative">
        🔔
        <span style="position:absolute;top:4px;right:4px;width:7px;height:7px;background:var(--red);border-radius:50%;border:2px solid var(--bg2)"></span>
      </button>
    </div>
  </div>

  <div class="content">
    <div style="margin-bottom:4px">
      <div class="section-title">My Certificates</div>
      <div class="section-sub">Official credentials for your completed courses and achievements.</div>
    </div>

    <div class="summary-row" id="summaryRow"></div>

    <div class="filter-bar">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInput" placeholder="Search by course or ID..." oninput="filterCerts()">
      </div>
      <select class="filter-select" id="statusFilter" onchange="filterCerts()">
        <option value="all">All Status</option>
        <option value="issued">Issued</option>
        <option value="processing">Processing</option>
      </select>
    </div>

    <div class="status-tabs" id="statusTabs"></div>

    <!-- Desktop Table -->
    <div class="cert-table-wrap" id="tableWrap">
      <div class="cert-table-header">
        <div class="card-title" id="tableTitle">All Certificates</div>
        <div id="tableCount" style="font-size:12px;color:var(--text3)"></div>
      </div>
      <div style="overflow-x:auto">
        <table class="cert-table" id="certTable"></table>
      </div>
    </div>

    <!-- Mobile Cards -->
    <div class="cert-card-list" id="cardList"></div>
  </div>
</main>
</div>

<div class="modal-backdrop" id="modalBackdrop" onclick="closeModalOutside(event)">
  <div class="modal" id="certModal">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <div class="modal-body" id="modalBody"></div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>
var coursesMeta = {
  1:{title:"Introduction to Biology",icon:"🧬",category:"Science"},
  2:{title:"Algebra & Trigonometry",icon:"📐",category:"Mathematics"},
  3:{title:"World History: Modern Era",icon:"🌍",category:"History"},
  4:{title:"English Literature",icon:"📖",category:"English"}
};

var allCertificates = [
  {id:1,courseId:1,status:"issued",date:"Dec 15, 2024",credentialId:"CERT-BIO-2024-089",instructor:"Dr. A. Smith",score:98},
  {id:2,courseId:2,status:"issued",date:"Dec 10, 2024",credentialId:"CERT-MATH-2024-042",instructor:"Prof. J. Doe",score:92},
  {id:3,courseId:3,status:"issued",date:"Nov 28, 2024",credentialId:"CERT-HIST-2024-156",instructor:"Ms. S. Connor",score:95},
  {id:4,courseId:4,status:"processing",date:"Dec 16, 2024",credentialId:"PENDING-ENG-2024-003",instructor:"Mrs. L. Garner",score:88}
];

var activeStatus = "all";
var statusOptions = [
  {key:"all",label:"All Certificates"},
  {key:"issued",label:"✅ Issued"},
  {key:"processing",label:"⏳ Processing"}
];

function computeStats(){
  var total = allCertificates.length;
  var issued = allCertificates.filter(function(c){return c.status==='issued'}).length;
  var processing = total - issued;
  var avg = total ? Math.round(allCertificates.reduce(function(s,c){return s+c.score},0)/total) : 0;
  return {total:total,issued:issued,processing:processing,avg:avg};
}

function renderSummary(){
  var s = computeStats();
  document.getElementById('summaryRow').innerHTML =
    '<div class="summary-card"><div class="summary-icon" style="background:var(--gold-bg)">📜</div><div><div class="summary-value">'+s.issued+'</div><div class="summary-label">Total Earned</div></div></div>'+
    '<div class="summary-card"><div class="summary-icon" style="background:var(--accent-glow)">⏳</div><div><div class="summary-value">'+s.processing+'</div><div class="summary-label">Processing</div></div></div>'+
    '<div class="summary-card"><div class="summary-icon" style="background:var(--green-bg)">📊</div><div><div class="summary-value">'+s.avg+'%</div><div class="summary-label">Avg. Score</div></div></div>'+
    '<div class="summary-card"><div class="summary-icon" style="background:var(--purple-bg)">🏆</div><div><div class="summary-value">4</div><div class="summary-label">Courses Active</div></div></div>';
}

function renderStatusTabs(){
  var s = computeStats();
  document.getElementById('statusTabs').innerHTML = statusOptions.map(function(o){
    var count;
    if(o.key==='all') count = s.total;
    else if(o.key==='issued') count = s.issued;
    else count = s.processing;
    return '<button class="status-tab'+(activeStatus===o.key?' active':'')+'" onclick="setStatus(\''+o.key+'\')">'+o.label+' ('+count+')</button>';
  }).join('');
}

function setStatus(st){
  activeStatus = st;
  renderStatusTabs();
  filterCerts();
}

function filterCerts(){
  var query = document.getElementById('searchInput').value.toLowerCase();
  var statusVal = document.getElementById('statusFilter').value;

  var filtered = allCertificates.filter(function(c){
    var course = coursesMeta[c.courseId];
    var matchSearch = c.credentialId.toLowerCase().indexOf(query)!==-1 || 
                      course.title.toLowerCase().indexOf(query)!==-1;
    var matchStatus = statusVal==='all' || c.status===statusVal;
    var matchTab = activeStatus==='all' || c.status===activeStatus;
    return matchSearch && matchStatus && matchTab;
  });

  document.getElementById('tableTitle').textContent = statusVal==='all'?'All Certificates':statusVal.charAt(0).toUpperCase()+statusVal.slice(1)+' Certificates';
  document.getElementById('tableCount').textContent = filtered.length+' found';

  renderTable(filtered);
  renderCards(filtered);
}

function renderTable(list){
  var el = document.getElementById('certTable');
  if(!list.length){
    el.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:48px 20px;color:var(--text3)"><div style="font-size:40px;margin-bottom:10px">📜</div>No certificates found.</td></tr>';
    return;
  }
  el.innerHTML = '<thead><tr><th>Course</th><th>Instructor</th><th>ID</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody>'+
  list.map(function(c){
    var course = coursesMeta[c.courseId];
    var statusBadge = c.status==='issued' 
      ? '<span class="badge badge-green">Issued</span>' 
      : '<span class="badge badge-amber">Processing</span>';
    
    return '<tr>'+
      '<td>'+
        '<span class="cert-course-name">'+course.icon+' '+course.title+'</span>'+
      '</td>'+
      '<td>'+
        '<div class="cert-instructor">'+
          '<div class="cert-instructor-avatar">'+c.instructor.charAt(0)+'</div>'+
          '<span>'+c.instructor+'</span>'+
        '</div>'+
      '</td>'+
      '<td><span class="cert-id">'+c.credentialId+'</span></td>'+
      '<td style="color:var(--text2)">'+c.date+'</td>'+
      '<td>'+statusBadge+'</td>'+
      '<td>'+
        '<button class="btn btn-ghost btn-sm" onclick="openModal('+c.id+')">View</button>'+
      '</td>'+
    '</tr>';
  }).join('')+'</tbody>';
}

function renderCards(list){
  var el = document.getElementById('cardList');
  if(!list.length){
    el.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)">No certificates found.</div>';
    return;
  }
  el.innerHTML = list.map(function(c){
    var course = coursesMeta[c.courseId];
    var statusBadge = c.status==='issued' 
      ? '<span class="badge badge-green">Issued</span>' 
      : '<span class="badge badge-amber">Processing</span>';
    return '<div class="cert-card" onclick="openModal('+c.id+')">'+
      '<div class="cert-card-header">'+
        '<span class="cert-card-title">'+course.title+'</span>'+
        '<span class="cert-card-date">'+c.date+'</span>'+
      '</div>'+
      '<div class="cert-card-meta">'+
        '<span>ID: '+c.credentialId+'</span>'+
        statusBadge+
      '</div>'+
      '<div class="cert-card-actions">'+
        '<button class="btn btn-primary btn-sm btn-block">View Details</button>'+
      '</div>'+
    '</div>';
  }).join('');
}

/* ========== MODAL ========== */
function openModal(id){
  var c = allCertificates.find(function(x){return x.id===id});
  if(!c) return;
  var course = coursesMeta[c.courseId];
  var isIssued = c.status === 'issued';

  document.getElementById('modalBody').innerHTML =
    '<div class="cert-paper-wrapper">'+
      '<div class="cert-header">'+
        '<div class="cert-logo"><span>Exce</span>Learn</div>'+
        '<div class="cert-subtitle">Certificate of Completion</div>'+
      '</div>'+
      '<div class="cert-title">This certifies that</div>'+
      '<div class="cert-body">'+
        '<div class="cert-presented">has successfully completed the course</div>'+
        '<div class="cert-student-name">Anna Claire Reyes</div>'+
        '<div class="cert-desc">in <span class="cert-course-title">'+course.title+'</span> with a final score of <strong>'+c.score+'%</strong>.</div>'+
      '</div>'+
      '<div class="cert-footer">'+
        '<div class="cert-signature">'+
          '<div class="cert-sign-line"></div>'+
          '<div class="cert-sign-label">'+c.instructor+'</div>'+
        '</div>'+
        '<div class="cert-seal">🏆</div>'+
        '<div class="cert-signature" style="text-align:right">'+
          '<div class="cert-sign-line"></div>'+
          '<div class="cert-sign-label">Date Issued</div>'+
          '<div style="font-size:13px;margin-top:4px;color:#444">'+c.date+'</div>'+
        '</div>'+
      '</div>'+
    '</div>'+
    '<div class="modal-meta-grid">'+
      '<div class="modal-meta-item"><span class="modal-mm-label">Credential ID</span><span class="modal-mm-val">'+c.credentialId+'</span></div>'+
      '<div class="modal-meta-item"><span class="modal-mm-label">Status</span><span class="modal-mm-val" style="color:'+(isIssued?'var(--green)':'var(--amber)')+'">'+c.status.toUpperCase()+'</span></div>'+
    '</div>'+
    '<div class="modal-actions">'+
      '<button class="btn btn-ghost" onclick="closeModal()">Close</button>'+
      '<button class="btn btn-primary" '+(isIssued?'':'disabled')+' onclick="downloadCert(\''+c.credentialId+'\')">⬇ Download PDF</button>'+
    '</div>';

  document.getElementById('modalBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal(){
  document.getElementById('modalBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}
function closeModalOutside(e){if(e.target===document.getElementById('modalBackdrop'))closeModal()}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeModal()});

function downloadCert(id){
  showToast('Downloading '+id+'...', 'success');
  setTimeout(function(){showToast('Download complete!', 'success');}, 1500);
}

/* ========== SIDEBAR ========== */
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('open');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}

/* ========== THEME ========== */
function toggleTheme(){
  document.body.classList.toggle('light');
  var isLight = document.body.classList.contains('light');
  document.getElementById('theme-icon').textContent = isLight ? '☀️' : '🌙';
  document.getElementById('theme-text').textContent = isLight ? 'Light Mode' : 'Dark Mode';
  localStorage.setItem('excelearn-theme', isLight ? 'light' : 'dark');
}
(function(){
  var saved = localStorage.getItem('excelearn-theme');
  if(saved==='light'){
    document.body.classList.add('light');
    document.getElementById('theme-icon').textContent='☀️';
    document.getElementById('theme-text').textContent='Light Mode';
  }
})();

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

/* ========== INIT ========== */
renderSummary();
renderStatusTabs();
filterCerts();
</script>
</body>
</html>