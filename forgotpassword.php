<?php
session_start();

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'connection.php';

$error   = '';
$success = '';
$step    = 'request';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("
            SELECT id, firstname, lastname, email
            FROM tblusers
            WHERE email = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $found = $stmt->get_result()->fetch_assoc();

        if ($found) {
            // Generate 6-digit OTP
            $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes

            // Store OTP in reset_token, expiry in reset_expires
            $upd = $conn->prepare("
                UPDATE tblusers
                SET reset_token = ?, reset_expires = ?
                WHERE id = ?
            ");
            $upd->bind_param("ssi", $otp, $expires, $found['id']);
            $upd->execute();

            // Store email in session so next pages know who we're resetting
            $_SESSION['fp_email']   = $found['email'];
            $_SESSION['fp_user_id'] = $found['id'];

            // Fetch SMTP settings
            $emailer = $conn->query("SELECT smtp_email, smtp_password, smtp_from, smtp_name FROM tbl_emailer LIMIT 1");
            $smtp    = $emailer ? $emailer->fetch_assoc() : null;

            if ($smtp) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.hostinger.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $smtp['smtp_email'];
                    $mail->Password   = $smtp['smtp_password'];
                    $mail->SMTPSecure = 'ssl';
                    $mail->Port       = 465;

                    $mail->setFrom($smtp['smtp_from'], $smtp['smtp_name']);
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'ExceLearn — Password Reset Code';

                    $fullname = $found['firstname'] . ' ' . $found['lastname'];

                    $mail->Body = "
                    <div style='font-family:DM Sans,Arial,sans-serif;max-width:520px;margin:auto;background:#0f172a;padding:40px;border-radius:16px;color:#f8fafc;'>
                        <h2 style='margin:0 0 8px;color:#f8fafc;'>Password Reset Code</h2>
                        <p>Hello <b>{$fullname}</b>,</p>
                        <p>Use the 6-digit code below to reset your password. It expires in <b>10 minutes</b>.</p>
                        <div style='font-size:40px;font-weight:700;letter-spacing:12px;background:#1e293b;padding:20px;border-radius:12px;text-align:center;color:#f59e0b;margin:24px 0;'>
                            {$otp}
                        </div>
                        <p style='font-size:13px;color:#94a3b8;'>If you did not request this, you can safely ignore this email.</p>
                    </div>";

                    $mail->send();
                } catch (Exception $e) {
                    // silent — don't leak SMTP errors
                }
            }
        }

        // Always show success (don't reveal whether email exists)
        $success = 'If that email is registered, a 6-digit verification code has been sent. Check your inbox (and spam folder).';
        $step    = 'sent';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn — Forgot Password</title>
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
      <div class="fp-left-title">Reset Your Password</div>
      <div class="fp-left-desc">Only teacher accounts can use self-service password reset. Follow the steps to regain access to your account.</div>
      <ul class="fp-left-steps">
        <li><span class="step-num">1</span><span>Enter your registered school Gmail address</span></li>
        <li><span class="step-num">2</span><span>Check your inbox for a 6-digit verification code (also check Spam)</span></li>
        <li><span class="step-num">3</span><span>Enter the code on the next page to verify your identity</span></li>
        <li><span class="step-num">4</span><span>Set a strong new password and sign in</span></li>
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
          <div class="mobile-banner-sub">Password Reset</div>
        </div>
      </div>
    </div>

    <div class="fp-right-inner fade-in">

      <div class="fp-brand">
        <img src="assets/img/Excelsior_Logo_long.png" alt="ExceLearn" class="fp-brand-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
        <div style="display:none"><div class="fp-brand-name">Exce<span>Learn</span></div></div>
      </div>

      <?php if ($step === 'sent'): ?>

        <div class="fp-sent-box">
          <div class="fp-sent-icon">📬</div>
          <div class="fp-sent-title">Check Your Email</div>
          <div class="fp-sent-desc"><?php echo htmlspecialchars($success); ?></div>
        </div>
        <a href="verifyforgotpassword.php" class="btn btn-primary" style="margin-bottom:12px;">Enter Verification Code →</a>
        <a href="index.php" class="btn btn-ghost">← Back to Login</a>

      <?php else: ?>

        <div class="fp-heading">Forgot Password?</div>
        <div class="fp-sub">Enter your school Gmail and we'll send you a 6-digit verification code.</div>

        <?php if ($error): ?>
        <div class="alert alert-error">
          <span class="alert-icon">⚠️</span>
          <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <form class="fp-form" method="POST" action="" id="requestForm" novalidate>
          <div class="form-group">
            <input type="email" name="email" id="email" placeholder="School Gmail address"
                   required autocomplete="email"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            <svg class="input-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>
          <button type="submit" class="btn btn-primary" id="requestBtn">Send Verification Code</button>
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

var reqForm = document.getElementById('requestForm');
if(reqForm){
  reqForm.addEventListener('submit', function(){
    var email = document.getElementById('email').value.trim();
    var btn   = document.getElementById('requestBtn');
    if(!email){ showToast('Please enter your email address.','error'); return false; }
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){ showToast('Please enter a valid email address.','error'); return false; }
    btn.classList.add('btn-loading'); btn.textContent = '';
  });
}

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