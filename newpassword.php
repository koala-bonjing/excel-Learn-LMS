<?php
session_start();
require_once 'connection.php';

// Guard — must have been verified on verifyforgotpassword.php
if (empty($_SESSION['fp_verified']) || empty($_SESSION['fp_user_id'])) {
    header('Location: forgotpassword.php');
    exit;
}

$user_id = (int) $_SESSION['fp_user_id'];

// Confirm the user still has the VERIFIED marker (prevents direct URL access after done)
$chk = $conn->prepare("
    SELECT id FROM tblusers
    WHERE id = ? AND reset_token = 'VERIFIED' AND status = 'active'
    LIMIT 1
");
$chk->bind_param("i", $user_id);
$chk->execute();
$valid = $chk->get_result()->fetch_assoc();

if (!$valid) {
    // Already used or session tampered
    session_unset();
    header('Location: forgotpassword.php');
    exit;
}

$error   = '';
$success = '';
$step    = 'form';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password     = $_POST['password']         ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in both password fields.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $upd = $conn->prepare("
            UPDATE tblusers
            SET password = ?, reset_token = NULL, reset_expires = NULL
            WHERE id = ? AND reset_token = 'VERIFIED'
        ");
        $upd->bind_param("si", $hashed, $user_id);

        if ($upd->execute() && $upd->affected_rows > 0) {
            // Clear all reset-related session data
            unset($_SESSION['fp_email'], $_SESSION['fp_user_id'], $_SESSION['fp_verified']);
            $success = 'Password changed successfully! Redirecting to login…';
            $step    = 'done';
            header("refresh:3;url=index.php");
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn — Set New Password</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/forgotpassword.css">
</head>
<body>

<button class="theme-toggle-float" onclick="toggleTheme()" id="themeToggle">
  <span id="theme-icon">🌙</span>
  <span id="theme-text">Dark</span>
</button>

<div class="fp-page">

  <div class="fp-left">
    <div class="fp-left-bg"></div>
    <div class="fp-left-pattern"></div>
    <div class="fp-left-shape1"></div>
    <div class="fp-left-shape2"></div>
    <div class="fp-left-shape3"></div>
    <div class="fp-left-shape4"></div>
    <div class="fp-left-content fade-in">
      <div class="fp-left-logo">
        <img src="assets/img/Excelsior_Seal_Logo.png" alt="EPA Logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <div class="fp-left-logo-fallback" style="display:none">EPA</div>
      </div>
      <div class="fp-left-title">Almost There!</div>
      <div class="fp-left-desc">Your identity has been verified. Choose a strong new password to secure your teacher account.</div>
      <ul class="fp-left-steps">
        <li><span class="step-num" style="background:var(--green)">✓</span><span>Email address confirmed</span></li>
        <li><span class="step-num" style="background:var(--green)">✓</span><span>Verification code accepted</span></li>
        <li><span class="step-num" style="background:var(--amber);color:#0f172a">3</span><span>Set a strong new password</span></li>
        <li><span class="step-num">4</span><span>Sign in with your new credentials</span></li>
      </ul>
    </div>
  </div>

  <div class="fp-right">

    <div class="mobile-banner">
      <div class="mobile-banner-bg"></div>
      <div class="mobile-banner-pattern"></div>
      <div class="mobile-banner-shape"></div>
      <div class="mobile-banner-content">
        <div class="mobile-banner-logo">
          <img src="assets/img/Excelsior_Seal_Logo.png" alt="EPA Logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="mobile-banner-logo-fallback" style="display:none">EPA</div>
        </div>
        <div class="mobile-banner-text">
          <div class="fp-brand-name">Exce<span>Learn</span></div>
          <div class="mobile-banner-sub">New Password</div>
        </div>
      </div>
    </div>

    <div class="fp-right-inner fade-in">

      <div class="fp-brand">
        <img src="assets/img/Excelsior_Logo_long.png" alt="ExceLearn" class="fp-brand-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
        <div style="display:none"><div class="fp-brand-name">Exce<span>Learn</span></div></div>
      </div>

      <?php if ($step === 'done'): ?>

        <div class="fp-sent-box">
          <div class="fp-sent-icon">✅</div>
          <div class="fp-sent-title">Password Changed!</div>
          <div class="fp-sent-desc"><?php echo htmlspecialchars($success); ?></div>
        </div>
        <a href="index.php" class="btn btn-primary">← Go to Login</a>

      <?php else: ?>

        <div class="fp-heading">Set New Password</div>
        <div class="fp-sub">Choose a strong password for your teacher account.</div>

        <?php if ($error): ?>
        <div class="alert alert-error">
          <span class="alert-icon">⚠️</span>
          <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <form class="fp-form" method="POST" action="" id="resetForm" novalidate>

          <div class="form-group">
            <div class="password-wrapper">
              <input type="password" name="password" id="password" placeholder="New password" required autocomplete="new-password">
              <svg class="input-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
              <button type="button" class="password-toggle" onclick="togglePassword('password','pwEyeOpen','pwEyeClosed')" aria-label="Toggle password">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" id="pwEyeOpen"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" id="pwEyeClosed" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
            <div class="pw-strength">
              <div class="pw-bar" id="pwBar1"></div>
              <div class="pw-bar" id="pwBar2"></div>
              <div class="pw-bar" id="pwBar3"></div>
              <div class="pw-bar" id="pwBar4"></div>
            </div>
            <div class="pw-strength-text" id="pwStrengthText"></div>
          </div>

          <div class="form-group">
            <div class="password-wrapper">
              <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required autocomplete="new-password">
              <svg class="input-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              <button type="button" class="password-toggle" onclick="togglePassword('confirm_password','cpwEyeOpen','cpwEyeClosed')" aria-label="Toggle password">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" id="cpwEyeOpen"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" id="cpwEyeClosed" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
            <div class="pw-strength-text" id="cpwMatchText" style="min-height:13px"></div>
          </div>

          <button type="submit" class="btn btn-primary" id="resetBtn">Save New Password</button>
        </form>

      <?php endif; ?>

      <div class="fp-divider">or</div>
      <a href="index.php" class="btn btn-ghost">← Back to Login</a>

      <div class="fp-footer">Only for authorized teachers of Excelsior Polytechnic Academy.</div>
      <div class="fp-copyright">ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © JBH: 2025–2026</div>
    </div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>
/* ── Theme ── */
function toggleTheme(){
  document.body.classList.toggle('light');
  var isLight = document.body.classList.contains('light');
  document.getElementById('theme-icon').textContent = isLight ? '☀️' : '🌙';
  document.getElementById('theme-text').textContent = isLight ? 'Light' : 'Dark';
  localStorage.setItem('excelearn-theme', isLight ? 'light' : 'dark');
}
(function(){
  var saved = localStorage.getItem('excelearn-theme');
  if(saved === 'light'){
    document.body.classList.add('light');
    document.getElementById('theme-icon').textContent = '☀️';
    document.getElementById('theme-text').textContent = 'Light';
  }
})();

/* ── Toggle password visibility ── */
function togglePassword(inputId, openId, closedId){
  var pw = document.getElementById(inputId);
  var open = document.getElementById(openId);
  var closed = document.getElementById(closedId);
  if(!pw) return;
  if(pw.type === 'password'){ pw.type = 'text'; open.style.display = 'none'; closed.style.display = 'block'; }
  else { pw.type = 'password'; open.style.display = 'block'; closed.style.display = 'none'; }
}

/* ── Password strength ── */
var pwInput = document.getElementById('password');
if(pwInput){
  pwInput.addEventListener('input', function(){
    var pw = this.value;
    var bars = [
      document.getElementById('pwBar1'),
      document.getElementById('pwBar2'),
      document.getElementById('pwBar3'),
      document.getElementById('pwBar4')
    ];
    var text = document.getElementById('pwStrengthText');
    bars.forEach(function(b){ if(b) b.className = 'pw-bar'; });
    if(text){ text.textContent = ''; text.style.color = 'var(--text3)'; }
    if(!pw) return;
    var score = 0;
    if(pw.length >= 8) score++;
    if(/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
    if(/\d/.test(pw)) score++;
    if(/[^a-zA-Z0-9]/.test(pw)) score++;
    var levels = [
      {cls:'weak',  label:'Weak — add uppercase, numbers, or symbols', color:'var(--red)'},
      {cls:'fair',  label:'Fair — try adding more variety',             color:'var(--amber)'},
      {cls:'good',  label:'Good — almost there',                        color:'var(--cyan)'},
      {cls:'strong',label:'Strong — excellent password',                color:'var(--green)'}
    ];
    var lvl = levels[Math.max(0, score - 1)];
    for(var i = 0; i < score; i++){ if(bars[i]) bars[i].className = 'pw-bar ' + lvl.cls; }
    if(text){ text.textContent = lvl.label; text.style.color = lvl.color; }
  });
}

/* ── Confirm match ── */
var cpwInput = document.getElementById('confirm_password');
if(cpwInput){
  cpwInput.addEventListener('input', function(){
    var pw  = pwInput ? pwInput.value : '';
    var cpw = this.value;
    var text = document.getElementById('cpwMatchText');
    if(!text) return;
    if(cpw.length === 0){ text.textContent = ''; return; }
    if(pw === cpw){ text.textContent = '✓ Passwords match'; text.style.color = 'var(--green)'; }
    else           { text.textContent = '✗ Passwords do not match'; text.style.color = 'var(--red)'; }
  });
}

/* ── Form submit ── */
var rstForm = document.getElementById('resetForm');
if(rstForm){
  rstForm.addEventListener('submit', function(){
    var pw  = document.getElementById('password').value;
    var cpw = document.getElementById('confirm_password').value;
    var btn = document.getElementById('resetBtn');
    if(pw.length < 8){ showToast('Password must be at least 8 characters.','error'); return false; }
    if(pw !== cpw){ showToast('Passwords do not match.','error'); return false; }
    btn.classList.add('btn-loading'); btn.textContent = '';
  });
}

/* ── Toast ── */
function showToast(msg, type){
  type = type || 'success';
  var icons = {success:'✅',error:'❌',info:'ℹ️'};
  var container = document.getElementById('toast-container');
  var t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = '<span class="toast-icon">' + (icons[type]||'✅') + '</span><span>' + msg + '</span>';
  container.appendChild(t);
  setTimeout(function(){
    t.style.opacity = '0'; t.style.transform = 'translateY(20px)';
    t.style.transition = 'all .25s ease';
    setTimeout(function(){ t.remove(); }, 250);
  }, 3000);
}

/* ── Fade-in ── */
(function(){
  var observer = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if(entry.isIntersecting){ entry.target.classList.add('visible'); observer.unobserve(entry.target); }
    });
  }, {threshold: 0.05});
  document.querySelectorAll('.fade-in').forEach(function(el){ observer.observe(el); });
})();

document.querySelectorAll('button, a').forEach(function(el){
  el.addEventListener('touchend', function(e){ e.preventDefault(); el.click(); }, {passive:false});
});
</script>
</body>
</html>