<?php
session_start();
require_once 'connection.php';

// Guard — must have gone through forgotpassword.php first
if (empty($_SESSION['fp_email']) || empty($_SESSION['fp_user_id'])) {
    header('Location: forgotpassword.php');
    exit;
}

$error   = '';
$success = '';
$email   = $_SESSION['fp_email'];
$user_id = (int) $_SESSION['fp_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect the 6 individual digit inputs and join them
    $digits = '';
    for ($i = 1; $i <= 6; $i++) {
        $digits .= trim($_POST['d' . $i] ?? '');
    }
    $otp_entered = $digits;

    if (strlen($otp_entered) !== 6 || !ctype_digit($otp_entered)) {
        $error = 'Please enter the complete 6-digit code.';
    } else {
        $chk = $conn->prepare("
            SELECT id, reset_token, reset_expires
            FROM tblusers
            WHERE id = ? AND status = 'active'
            LIMIT 1
        ");
        $chk->bind_param("i", $user_id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();

        if (!$row) {
            $error = 'Account not found. Please start over.';
        } elseif ($row['reset_token'] !== $otp_entered) {
            $error = 'Incorrect code. Please check your email and try again.';
        } elseif (strtotime($row['reset_expires']) < time()) {
            $error = 'This code has expired. Please request a new one.';
        } else {
            // Mark as verified — set a session flag, clear OTP
            $_SESSION['fp_verified'] = true;

            // Nullify OTP so it can't be reused; password reset will be done on next page
            $upd = $conn->prepare("
                UPDATE tblusers
                SET reset_token = 'VERIFIED', reset_expires = NULL
                WHERE id = ?
            ");
            $upd->bind_param("i", $user_id);
            $upd->execute();

            header('Location: newpassword.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn — Verify Code</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/forgotpassword.css">
<style>
/* ── OTP digit inputs ── */
.otp-row {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin: 24px 0 8px;
}
.otp-digit {
  width: 48px;
  height: 58px;
  text-align: center;
  font-size: 24px;
  font-weight: 700;
  font-family: 'DM Sans', sans-serif;
  border: 2px solid var(--border);
  border-radius: 12px;
  background: var(--input-bg);
  color: var(--text1);
  outline: none;
  transition: border-color .2s, box-shadow .2s;
  caret-color: var(--amber);
}
.otp-digit:focus {
  border-color: var(--amber);
  box-shadow: 0 0 0 3px rgba(245,158,11,.18);
}
.otp-digit.filled {
  border-color: var(--amber);
}
.otp-hint {
  font-size: 13px;
  color: var(--text3);
  text-align: center;
  margin-bottom: 20px;
}
.otp-hint b { color: var(--amber); }

.resend-row {
  text-align: center;
  margin-top: 8px;
  font-size: 13px;
  color: var(--text3);
}
.resend-row a {
  color: var(--amber);
  text-decoration: none;
  font-weight: 600;
}
.resend-row a:hover { text-decoration: underline; }

.otp-timer {
  color: var(--text3);
  font-size: 13px;
}
.otp-timer.urgent { color: var(--red); }
</style>
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
      <div class="fp-left-title">Verify Your Identity</div>
      <div class="fp-left-desc">A one-time 6-digit code was sent to your school Gmail. Enter it below to continue resetting your password.</div>
      <ul class="fp-left-steps">
        <li><span class="step-num" style="background:var(--green)">✓</span><span>Email address confirmed</span></li>
        <li><span class="step-num" style="background:var(--amber);color:#0f172a">2</span><span>Enter the 6-digit code from your inbox</span></li>
        <li><span class="step-num">3</span><span>Set a strong new password</span></li>
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
          <div class="mobile-banner-sub">Verification</div>
        </div>
      </div>
    </div>

    <div class="fp-right-inner fade-in">

      <div class="fp-brand">
        <img src="assets/img/Excelsior_Logo_long.png" alt="ExceLearn" class="fp-brand-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
        <div style="display:none"><div class="fp-brand-name">Exce<span>Learn</span></div></div>
      </div>

      <div class="fp-heading">Enter Your Code</div>
      <div class="fp-sub">We sent a 6-digit code to <b><?php echo htmlspecialchars(substr($email, 0, 3) . str_repeat('*', strpos($email, '@') - 3) . substr($email, strpos($email, '@'))); ?></b></div>

      <?php if ($error): ?>
      <div class="alert alert-error">
        <span class="alert-icon">⚠️</span>
        <span><?php echo htmlspecialchars($error); ?></span>
      </div>
      <?php endif; ?>

      <form class="fp-form" method="POST" action="" id="verifyForm" novalidate>
        <div class="otp-row">
          <?php for ($i = 1; $i <= 6; $i++): ?>
          <input type="text" name="d<?php echo $i; ?>" id="d<?php echo $i; ?>"
                 class="otp-digit" maxlength="1" inputmode="numeric" autocomplete="off"
                 pattern="[0-9]" required>
          <?php endfor; ?>
        </div>

        <div class="otp-hint">Code expires in <span id="countdownDisplay" class="otp-timer"><b>10:00</b></span></div>

        <button type="submit" class="btn btn-primary" id="verifyBtn">Verify Code</button>
      </form>

      <div class="resend-row">
        Didn't receive it? <a href="forgotpassword.php">Resend code</a>
      </div>

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

/* ── OTP digit inputs: auto-advance, paste, backspace ── */
(function(){
  var digits = Array.from(document.querySelectorAll('.otp-digit'));
  digits.forEach(function(input, idx){
    input.addEventListener('input', function(){
      // Keep only last character typed, must be digit
      var val = this.value.replace(/\D/g,'');
      this.value = val ? val.slice(-1) : '';
      this.classList.toggle('filled', this.value !== '');
      if(this.value && idx < digits.length - 1){
        digits[idx + 1].focus();
      }
    });
    input.addEventListener('keydown', function(e){
      if(e.key === 'Backspace' && !this.value && idx > 0){
        digits[idx - 1].focus();
        digits[idx - 1].value = '';
        digits[idx - 1].classList.remove('filled');
      }
    });
    // Handle paste on first box
    input.addEventListener('paste', function(e){
      e.preventDefault();
      var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
      pasted.split('').forEach(function(ch, i){
        if(digits[i]){ digits[i].value = ch; digits[i].classList.add('filled'); }
      });
      var next = Math.min(pasted.length, digits.length - 1);
      digits[next].focus();
    });
  });
})();

/* ── Countdown timer (10 min) ── */
(function(){
  var seconds = 600;
  var el = document.getElementById('countdownDisplay');
  function tick(){
    if(!el) return;
    var m = Math.floor(seconds / 60);
    var s = seconds % 60;
    var display = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    el.innerHTML = '<b>' + display + '</b>';
    el.className = 'otp-timer' + (seconds <= 60 ? ' urgent' : '');
    if(seconds > 0){ seconds--; setTimeout(tick, 1000); }
    else { el.innerHTML = '<b style="color:var(--red)">Expired</b>'; }
  }
  tick();
})();

/* ── Form submit ── */
var verifyForm = document.getElementById('verifyForm');
if(verifyForm){
  verifyForm.addEventListener('submit', function(){
    var combined = '';
    for(var i = 1; i <= 6; i++){
      var el = document.getElementById('d' + i);
      combined += el ? el.value : '';
    }
    if(combined.length < 6 || !/^\d{6}$/.test(combined)){
      showToast('Please enter the complete 6-digit code.','error');
      return false;
    }
    var btn = document.getElementById('verifyBtn');
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