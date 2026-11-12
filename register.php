<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (
        empty($firstname) ||
        empty($lastname) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {
        $error = 'Please fill in all required fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } elseif (strlen($firstname) < 2) {
        $error = 'First name must be at least 2 characters.';

    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';

    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';

    } else {

        require_once 'connection.php';

        $check = $conn->prepare("SELECT id FROM tblusers WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'Email already exists. Please login instead.';

        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $role   = 'teacher';
            $status = 'pending';   // Needs admin approval
            $is_verified = 0;

            $stmt = $conn->prepare("
                INSERT INTO tblusers
                (
                    email,
                    password,
                    firstname,
                    middlename,
                    lastname,
                    role,
                    status,
                    is_verified,
                    created_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->bind_param(
                "ssssssis",
                $email,
                $hashed_password,
                $firstname,
                $middlename,
                $lastname,
                $role,
                $status,
                $is_verified
            );

            if ($stmt->execute()) {

                $emailer = mysqli_query($conn, "SELECT * FROM tbl_emailer LIMIT 1");
                $smtp    = mysqli_fetch_assoc($emailer);

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

                    $mail->Subject = 'ExceLearn — Registration Received';

                    $fullname = trim("$firstname $lastname");

                    $mail->Body = "
                        <div style='font-family:sans-serif;max-width:520px;margin:auto;padding:32px;background:#18181b;color:#e4e4e7;border-radius:12px'>

                            <h2 style='color:#f59e0b;margin-bottom:4px'>ExceLearn LMS</h2>
                            <p style='color:#a1a1aa;margin-top:0;font-size:13px'>Excelsior Polytechnic Academy</p>

                            <hr style='border:none;border-top:1px solid #3f3f46;margin:20px 0'>

                            <p>Hello <b>$fullname</b>,</p>

                            <p>
                                Thank you for registering as a teacher on <b>ExceLearn</b>.
                                Your account has been successfully created and is currently
                                <b style='color:#f59e0b'>pending review</b>.
                            </p>

                            <div style='
                                background:#27272a;
                                border-left:4px solid #f59e0b;
                                border-radius:8px;
                                padding:16px 20px;
                                margin:24px 0;
                            '>
                                ⏳ &nbsp;<b>What happens next?</b><br><br>
                                The administrator will review your registration and activate
                                your account. You will receive another email once your account
                                has been <b>approved</b> and is ready to use.
                            </div>

                            <p style='color:#a1a1aa;font-size:13px'>
                                If you did not make this request or believe this is an error,
                                please ignore this email or contact your school administrator.
                            </p>

                            <hr style='border:none;border-top:1px solid #3f3f46;margin:20px 0'>

                            <p style='color:#71717a;font-size:12px;margin:0'>
                                ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © 2025–2026
                            </p>
                        </div>
                    ";

                    $mail->send();

                    $_SESSION['pending_name']  = $firstname;
                    $_SESSION['pending_email'] = $email;

                    header("Location: registration_pending.php");
                    exit();

                } catch (Exception $e) {
                    $error = "Mailer Error: " . $mail->ErrorInfo;
                }

            } else {
                $error = 'Registration failed: ' . $stmt->error;
            }

            $stmt->close();
        }

        $check->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn — Teacher Registration</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/register.css">
</head>
<body>

<button class="theme-toggle-float" onclick="toggleTheme()" id="themeToggle">
  <span id="theme-icon">🌙</span>
  <span id="theme-text">Dark</span>
</button>

<div class="register-page">
  <div class="register-left">
    <div class="register-left-bg"></div>
    <div class="register-left-pattern"></div>
    <div class="register-left-shape1"></div>
    <div class="register-left-shape2"></div>
    <div class="register-left-shape3"></div>
    <div class="register-left-shape4"></div>
    <div class="register-left-content fade-in">
      <div class="register-left-logo">
        <img src="assets/img/Excelsior_Seal_Logo.png" alt="EPA Logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <div class="register-left-logo-fallback" style="display:none">EPA</div>
      </div>
      <div class="register-left-title">Join as a Teacher</div>
      <div class="register-left-desc">Create your ExceLearn teacher account to manage classes, create content, and track student progress.</div>
      <ul class="register-left-steps">
        <li>
          <span class="step-num">1</span>
          <span>Fill out the registration form with your school credentials</span>
        </li>
        <li>
          <span class="step-num">2</span>
          <span>Your account will be reviewed and approved by the administrator</span>
        </li>
        <li>
          <span class="step-num">3</span>
          <span>Once approved, log in and start managing your classes</span>
        </li>
        <li>
          <span class="step-num">4</span>
          <span>Create courses, quizzes, lessons, and monitor student performance</span>
        </li>
      </ul>
    </div>
  </div>
  <div class="register-right">
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
          <div class="register-brand-name">Exce<span>Learn</span></div>
          <div class="mobile-banner-sub">Teacher Registration</div>
        </div>
      </div>
    </div>
    <div class="register-right-inner fade-in">
      <div class="register-brand">
        <img src="assets/img/Excelsior_Logo_long.png" alt="ExceLearn" class="register-brand-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
        <div style="display:none">
          <div class="register-brand-name">Exce<span>Learn</span></div>
        </div>
      </div>

      <div class="register-heading">Create Teacher Account</div>
      <div class="register-sub">Register with your school Gmail to get started.</div>
      <?php if ($error): ?>
      <div class="alert alert-error">
        <span class="alert-icon">⚠️</span>
        <span><?php echo htmlspecialchars($error); ?></span>
      </div>
      <?php endif; ?>
      <?php if ($success): ?>
      <div class="alert alert-success">
        <span class="alert-icon">✅</span>
        <span><?php echo htmlspecialchars($success); ?></span>
      </div>
      <?php endif; ?>
      <form class="register-form" method="POST" action="" id="registerForm" novalidate>
        <div class="form-group">
          <input type="text" 
                name="firstname" 
                id="firstname" 
                placeholder="First Name" 
                required 
                autocomplete="given-name"
                value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>">
          <svg class="input-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <div class="form-group">
          <input type="text" 
                name="middlename" 
                id="middlename" 
                placeholder="Middle Name" 
                autocomplete="additional-name"
                value="<?php echo htmlspecialchars($_POST['middlename'] ?? ''); ?>">
          <svg class="input-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <div class="form-group">
          <input type="text" 
                name="lastname" 
                id="lastname" 
                placeholder="Last Name" 
                required 
                autocomplete="family-name"
                value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>">

          <svg class="input-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <div class="form-group">
          <input type="email" name="email" id="email" placeholder="School Gmail address" required autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
          <svg class="input-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div class="form-group">
          <div class="password-wrapper">
            <input type="password" name="password" id="password" placeholder="Create password" required autocomplete="new-password">
            <svg class="input-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            <button type="button" class="password-toggle" onclick="togglePassword('password','pwEyeOpen','pwEyeClosed')" aria-label="Toggle password visibility">
              <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" id="pwEyeOpen"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" id="pwEyeClosed" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <div class="pw-strength" id="pwStrength">
            <div class="pw-bar" id="pwBar1"></div>
            <div class="pw-bar" id="pwBar2"></div>
            <div class="pw-bar" id="pwBar3"></div>
            <div class="pw-bar" id="pwBar4"></div>
          </div>
          <div class="pw-strength-text" id="pwStrengthText"></div>
        </div>
        <div class="form-group">
          <div class="password-wrapper">
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required autocomplete="new-password">
            <svg class="input-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password','cpwEyeOpen','cpwEyeClosed')" aria-label="Toggle password visibility">
              <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" id="cpwEyeOpen"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" id="cpwEyeClosed" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <div class="pw-strength-text" id="cpwMatchText" style="min-height:13px"></div>
        </div>
        <button type="submit" class="btn btn-primary" id="registerBtn">Create Account</button>
      </form>

      <div class="register-divider">or</div>

      <a href="index.php" class="btn btn-ghost" style="margin-top:4px">← Already have an account? Sign In</a>

      <div class="register-footer">
        Only for authorized teachers of Excelsior Polytechnic Academy.<br>
      </div>

      <div class="register-copyright">
        ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © JBH: 2025–2026
      </div>
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

function togglePassword(inputId, openId, closedId){
  var pw = document.getElementById(inputId);
  var open = document.getElementById(openId);
  var closed = document.getElementById(closedId);
  if(pw.type === 'password'){ pw.type = 'text'; open.style.display = 'none'; closed.style.display = 'block'; }
  else { pw.type = 'password'; open.style.display = 'block'; closed.style.display = 'none'; }
}

document.getElementById('password').addEventListener('input', function(){
  var pw = this.value;
  var bar1 = document.getElementById('pwBar1');
  var bar2 = document.getElementById('pwBar2');
  var bar3 = document.getElementById('pwBar3');
  var bar4 = document.getElementById('pwBar4');
  var text = document.getElementById('pwStrengthText');

  bar1.className = 'pw-bar';
  bar2.className = 'pw-bar';
  bar3.className = 'pw-bar';
  bar4.className = 'pw-bar';
  text.textContent = '';
  text.style.color = 'var(--text3)';

  if(pw.length === 0) return;

  var score = 0;
  if(pw.length >= 8) score++;
  if(/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
  if(/\d/.test(pw)) score++;
  if(/[^a-zA-Z0-9]/.test(pw)) score++;

  if(score <= 1){
    bar1.className = 'pw-bar weak';
    text.textContent = 'Weak — add uppercase, numbers, or symbols';
    text.style.color = 'var(--red)';
  } else if(score === 2){
    bar1.className = 'pw-bar fair';
    bar2.className = 'pw-bar fair';
    text.textContent = 'Fair — try adding more variety';
    text.style.color = 'var(--amber)';
  } else if(score === 3){
    bar1.className = 'pw-bar good';
    bar2.className = 'pw-bar good';
    bar3.className = 'pw-bar good';
    text.textContent = 'Good — almost there';
    text.style.color = 'var(--cyan)';
  } else {
    bar1.className = 'pw-bar strong';
    bar2.className = 'pw-bar strong';
    bar3.className = 'pw-bar strong';
    bar4.className = 'pw-bar strong';
    text.textContent = 'Strong — excellent password';
    text.style.color = 'var(--green)';
  }
});

document.getElementById('confirm_password').addEventListener('input', function(){
  var pw = document.getElementById('password').value;
  var cpw = this.value;
  var text = document.getElementById('cpwMatchText');

  if(cpw.length === 0){
    text.textContent = '';
    return;
  }

  if(pw === cpw){
    text.textContent = '✓ Passwords match';
    text.style.color = 'var(--green)';
  } else {
    text.textContent = '✗ Passwords do not match';
    text.style.color = 'var(--red)';
  }
});

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

document.getElementById('registerForm').addEventListener('submit', function(){
  var firstname = document.getElementById('firstname').value.trim();
  var lastname = document.getElementById('lastname').value.trim();
  var email = document.getElementById('email').value.trim();
  var pw = document.getElementById('password').value;
  var cpw = document.getElementById('confirm_password').value;
  var btn = document.getElementById('registerBtn');

  if(!firstname || !lastname){ showToast('Please enter your full name.','error'); return; }
  if(firstname.length < 2){ showToast('Full name must be at least 2 characters.','error'); return; }
  if(!email){ showToast('Please enter your email address.','error'); return; }
  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){ showToast('Please enter a valid email address.','error'); return; }
  if(!pw){ showToast('Please create a password.','error'); return; }
  if(pw.length < 8){ showToast('Password must be at least 8 characters.','error'); return; }
  if(pw !== cpw){ showToast('Passwords do not match.','error'); return; }

  btn.classList.add('btn-loading');
  btn.textContent = '';
});

document.querySelectorAll('button, a').forEach(function(el){
  el.addEventListener('touchend', function(e){
    e.preventDefault();
    el.click();
  }, {passive: false});
});

(function(){
  var observer = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if(entry.isIntersecting){ entry.target.classList.add('visible'); observer.unobserve(entry.target); }
    });
  }, {threshold: 0.05});
  document.querySelectorAll('.fade-in').forEach(function(el){ observer.observe(el); });
})();
</script>
</body>
</html>