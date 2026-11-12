<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
$error = '';
$success = '';
$active_role = $_POST['role'] ?? 'student';

// If already logged in via session, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'student') {
        header("Location: students/index.php");
        exit();
    } elseif ($role === 'teacher') {
        header("Location: teachers/index.php");
        exit();
    } elseif ($role === 'admin') {
        header("Location: admin/index.php");
        exit();
    }
}

if (!isset($_SESSION['user_id']) && isset($_COOKIE['excelearn_rem_user'])) {
    $cookie_data = explode('|', $_COOKIE['excelearn_rem_user']);

    if (count($cookie_data) === 3) {
        $cookie_role  = $cookie_data[0];
        $cookie_id    = (int)$cookie_data[1];
        $cookie_token = $cookie_data[2];

        require_once 'connection.php';

        if ($cookie_role === 'student') {
            $stmt = $conn->prepare("SELECT id, firstname, lastname, student_id, section_id FROM tblstudents WHERE id = ? AND remember_token = ? AND status = 'active'");
            $stmt->bind_param("is", $cookie_id, $cookie_token);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if ($user) {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['student_id'] = $user['id'];
                $_SESSION['student_lrn'] = $user['student_id'];
                $_SESSION['section_id'] = $user['section_id'];
                $_SESSION['user_role']  = 'student';
                $_SESSION['user_name']  = $user['firstname'] . ' ' . $user['lastname'];
                header("Location: students/index.php");
                exit();
            }
        } else {
            $stmt = $conn->prepare("SELECT id, firstname, lastname, role FROM tblusers WHERE id = ? AND remember_token = ? AND status = 'active'");
            $stmt->bind_param("is", $cookie_id, $cookie_token);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];

                if ($user['role'] === 'teacher') {
                    $_SESSION['teacher_id'] = $user['id'];
                    header("Location: teachers/index.php");
                    exit();
                } else {
                    header("Location: admin/index.php");
                    exit();
                }
            }
        }

        // Token mismatch — clear bad cookie
        setcookie("excelearn_rem_user", "", time() - 3600, "/");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $active_role = trim($_POST['role'] ?? 'student');
    $password = trim($_POST['password'] ?? '');

    if ($active_role === 'student') {
        $student_id = trim($_POST['student_id'] ?? '');

        if (empty($student_id) || empty($password)) {
            $error = 'Please enter your Student ID and Password.';
        } else {
            if (file_exists('connection.php')) {
                require_once 'connection.php';

                $stmt = $conn->prepare("
                    SELECT * 
                    FROM tblstudents 
                    WHERE student_id = ? 
                    AND status = 'active'
                ");
                $stmt->bind_param("s", $student_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $student = $result->fetch_assoc();

                    if (password_verify($password, $student['password'])) {
                        $_SESSION['user_id']    = $student['id'];
                        $_SESSION['student_id'] = $student['id'];          // numeric PK — used by dashboard
                        $_SESSION['student_lrn'] = $student['student_id']; // keep the LRN separately
                        $_SESSION['user_name']  = $student['firstname'] . ' ' . $student['lastname'];
                        $_SESSION['user_role']  = 'student';
                        $_SESSION['section_id'] = $student['section_id'];

                        if (isset($_POST['remember'])) {
                            $token = bin2hex(random_bytes(32));
                            $stmtR = $conn->prepare("UPDATE tblstudents SET remember_token = ? WHERE id = ?");
                            $stmtR->bind_param("si", $token, $student['id']);
                            $stmtR->execute();
                            $stmtR->close();
                            setcookie("excelearn_rem_user", "student|{$student['id']}|{$token}", time() + (86400 * 30), "/", "", false, true);
                        } else {
                            $stmtR = $conn->prepare("UPDATE tblstudents SET remember_token = NULL WHERE id = ?");
                            $stmtR->bind_param("i", $student['id']);
                            $stmtR->execute();
                            $stmtR->close();
                            setcookie("excelearn_rem_user", "", time() - 3600, "/");
                        }

                        header("Location: students/index.php");
                        exit();
                    } else {
                        $error = 'Incorrect password. Please try again.';
                    }
                } else {
                    $error = 'Student ID not found.';
                }
            } else {
                $error = 'Student authentication not configured yet.';
            }
        }
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || empty($password)) {
            $error = 'Please enter your email and Password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {

            if (file_exists('connection.php')) {
                require_once 'connection.php';

                $stmt = $conn->prepare("
                    SELECT * 
                    FROM tblusers 
                    WHERE email = ? 
                    AND status = 'active'
                    LIMIT 1
                ");

                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();

                    if (password_verify($password, $user['password'])) {

                        if ($user['is_verified'] == 0) {

                            $_SESSION['verify_email'] = $user['email'];

                            $error = 'Please verify your email first. Redirecting to verification page...';

                            echo "
                            <script>
                                setTimeout(function(){
                                    window.location.href='verify.php';
                                }, 2000);
                            </script>
                            ";

                        } else {

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
                            $_SESSION['user_role'] = $user['role'];

                            if ($user['role'] === 'teacher') {

                                $_SESSION['teacher_id'] = $user['id'];

                                if (isset($_POST['remember'])) {
                                    $token = bin2hex(random_bytes(32));
                                    $stmtR = $conn->prepare("UPDATE tblusers SET remember_token = ? WHERE id = ?");
                                    $stmtR->bind_param("si", $token, $user['id']);
                                    $stmtR->execute();
                                    $stmtR->close();
                                    setcookie("excelearn_rem_user", "teacher|{$user['id']}|{$token}", time() + (86400 * 30), "/", "", false, true);
                                } else {
                                    $stmtR = $conn->prepare("UPDATE tblusers SET remember_token = NULL WHERE id = ?");
                                    $stmtR->bind_param("i", $user['id']);
                                    $stmtR->execute();
                                    $stmtR->close();
                                    setcookie("excelearn_rem_user", "", time() - 3600, "/");
                                }
                                header("Location: teachers/index.php");
                                exit();

                            } elseif ($user['role'] === 'admin') {

                                if (isset($_POST['remember'])) {
                                    $token = bin2hex(random_bytes(32));
                                    $stmtR = $conn->prepare("UPDATE tblusers SET remember_token = ? WHERE id = ?");
                                    $stmtR->bind_param("si", $token, $user['id']);
                                    $stmtR->execute();
                                    $stmtR->close();
                                    setcookie("excelearn_rem_user", "admin|{$user['id']}|{$token}", time() + (86400 * 30), "/", "", false, true);
                                } else {
                                    $stmtR = $conn->prepare("UPDATE tblusers SET remember_token = NULL WHERE id = ?");
                                    $stmtR->bind_param("i", $user['id']);
                                    $stmtR->execute();
                                    $stmtR->close();
                                    setcookie("excelearn_rem_user", "", time() - 3600, "/");
                                }
                                header("Location: admin/index.php");
                                exit();

                            } else {

                                $error = 'Invalid user role.';
                            }
                        }

                    } else {

                        $error = 'Incorrect password. Please try again.';
                    }

                } else {
                    $error = 'No account found with this email.';
                }

            } else {
                $error = 'System database not configured.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn — Login</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/styles.css">
<style>
  .remember-check {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #555;
    cursor: pointer;
  }
  .remember-check input[type="checkbox"] {
    appearance: auto;
    -webkit-appearance: checkbox;
    width: 16px;
    height: 16px;
    margin: 0;
    cursor: pointer;
    accent-color: #f59e0b;
  }
  body.light .remember-check {
    color: #333;
  }

  /* forgot-link hidden by default; shown only for teacher role */
  .forgot-link {
    display: none;
    font-size: 13.5px;
    color: var(--amber, #f59e0b);
    text-decoration: none;
    font-weight: 500;
    transition: opacity .2s;
  }
  .forgot-link:hover { opacity: .75; text-decoration: underline; }
  .forgot-link.visible { display: inline; }
</style>
</head>
<body>
<button class="theme-toggle-float" onclick="toggleTheme()" id="themeToggle">
  <span id="theme-icon">🌙</span>
  <span id="theme-text">Dark</span>
</button>
<div class="login-page">
  <div class="login-left">
    <div class="login-left-bg"></div>
    <div class="login-left-pattern"></div>
    <div class="login-left-shape1"></div>
    <div class="login-left-shape2"></div>
    <div class="login-left-shape3"></div>
    <div class="login-left-shape4"></div>
    <div class="login-left-content fade-in">
      <div class="login-left-logo">
        <img src="assets/img/Excelsior_Seal_Logo.png" alt="EPA Logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <div class="login-left-logo-fallback" style="display:none">EPA</div>
      </div>
      <div class="login-left-school">Excelsior Polytechnic Academy</div>
      <div class="login-left-tagline">A leading educational institution committed to producing innovative, competent, and resilient graduates for social development among Masbateños.</div>
      <ul class="login-left-features">
        <li>
          <span class="feat-icon">📚</span>
          <span>Access your courses, lessons, and learning materials anytime, anywhere</span>
        </li>
        <li>
          <span class="feat-icon">📝</span>
          <span>Take quizzes and track your academic progress in real-time</span>
        </li>
        <li>
          <span class="feat-icon">🎥</span>
          <span>Join virtual classes and interact with teachers live</span>
        </li>
        <li>
          <span class="feat-icon">🏆</span>
          <span>Earn certificates and celebrate your achievements</span>
        </li>
      </ul>
    </div>
  </div>
  <div class="login-right">
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
          <div class="login-brand-name">Exce<span>Learn</span></div>
          <div class="mobile-banner-tagline">Learn Better. Anytime, Anywhere.</div>
        </div>
      </div>
    </div>
    <div class="login-right-inner fade-in">
      <div class="login-brand">
        <img src="assets/img/Excelsior_Logo_Long.png" alt="ExceLearn" class="login-brand-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
        <div style="display:none">
          <div class="login-brand-name">Exce<span>Learn</span></div>
          <div class="login-brand-sub">Learn Better. Anytime, Anywhere.</div>
        </div>
      </div>
      <div class="login-heading">Welcome back</div>
      <div class="login-sub">Sign in to your ExceLearn account to continue.</div>

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

      <form class="login-form" method="POST" action="" id="loginForm" novalidate>
        <div class="role-toggle-wrap">
          <div class="role-toggle" id="roleToggle">
            <div class="role-toggle-slider <?php echo $active_role === 'teacher' ? 'teacher-slide' : 'student-slide'; ?>" id="roleSlider"></div>
            <button type="button" class="role-toggle-btn <?php echo $active_role === 'student' ? 'active' : ''; ?>" id="btnStudent" onclick="switchRole('student')">
              <span class="role-icon">🎓</span> Student
            </button>
            <button type="button" class="role-toggle-btn <?php echo $active_role === 'teacher' ? 'active' : ''; ?>" id="btnTeacher" onclick="switchRole('teacher')">
              <span class="role-icon">👩‍🏫</span> Teacher
            </button>
          </div>
          <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($active_role); ?>">
        </div>

        <div class="role-desc" id="roleDesc">
          <?php if ($active_role === 'student'): ?>
            <span class="role-desc-icon">🎓</span> Sign in with the Student ID and password provided by your teacher.
          <?php else: ?>
            <span class="role-desc-icon">👩‍🏫</span> Sign in with your school Gmail and your teacher account password.
          <?php endif; ?>
        </div>

        <div class="field-panel <?php echo $active_role === 'student' ? 'active' : ''; ?>" id="studentFields">
          <div class="form-group">
            <input type="text" name="student_id" id="student_id" placeholder="Student ID" required autocomplete="off" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" inputmode="text">
            <svg class="input-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/><rect x="2" y="2" width="6" height="6" rx="1" fill="none"/><text x="3.5" y="7" font-size="5" fill="currentColor" stroke="none" font-weight="700">ID</text></svg>
          </div>
        </div>

        <div class="field-panel <?php echo $active_role === 'teacher' ? 'active' : ''; ?>" id="teacherFields">
          <div class="form-group">
            <input type="email" name="email" id="email" placeholder="School Gmail address" required autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            <svg class="input-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
        </div>

        <div class="form-group">
          <div class="password-wrapper">
            <input type="password" name="password" id="password" placeholder="Password" required autocomplete="current-password">
            <svg class="input-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility" id="pwToggle">
              <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" id="pwEyeOpen"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" id="pwEyeClosed" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>

        <div class="form-row">
          <label class="remember-check">
            <input type="checkbox" name="remember" id="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
            Remember me
          </label>
          <!-- Forgot password: only visible for teacher role -->
          <a href="forgotpassword.php"
             class="forgot-link <?php echo $active_role === 'teacher' ? 'visible' : ''; ?>"
             id="forgotLink">
            Forgot password?
          </a>
        </div>

        <button type="submit" class="btn <?php echo $active_role === 'teacher' ? 'btn-primary' : 'btn-orange'; ?>" id="loginBtn">
          <?php echo $active_role === 'teacher' ? 'Sign In as Teacher' : 'Sign In as Student'; ?>
        </button>
      </form>

      <div class="login-divider">or</div>

      <a href="register.php"
         id="teacherRegisterBtn"
         class="btn btn-primary"
         style="margin-top:4px; <?php echo $active_role === 'student' ? 'display:none;' : ''; ?>"
         onclick="showToast('Redirecting to teacher registration...','info')">
        👩‍🏫 Teacher? Register Here
      </a>
      <div class="login-copyright">
        ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © JBH: 2025–2026
      </div>
    </div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>
var currentRole = '<?php echo $active_role; ?>';

function switchRole(role){
  if(role === currentRole) return;
  currentRole = role;

  var slider      = document.getElementById('roleSlider');
  var btnS        = document.getElementById('btnStudent');
  var btnT        = document.getElementById('btnTeacher');
  var input       = document.getElementById('roleInput');
  var desc        = document.getElementById('roleDesc');
  var sFields     = document.getElementById('studentFields');
  var tFields     = document.getElementById('teacherFields');
  var loginBtn    = document.getElementById('loginBtn');
  var registerBtn = document.getElementById('teacherRegisterBtn');
  var forgotLink  = document.getElementById('forgotLink');

  if(role === 'student'){
    slider.className = 'role-toggle-slider student-slide';
    btnS.classList.add('active'); btnT.classList.remove('active');
    sFields.classList.add('active'); tFields.classList.remove('active');
    loginBtn.className = 'btn btn-orange';
    loginBtn.textContent = 'Sign In as Student';
    desc.innerHTML = '<span class="role-desc-icon">🎓</span> Sign in with the Student ID and password provided by your teacher.';
    if(registerBtn) registerBtn.style.display = 'none';
    // Hide forgot password for students
    forgotLink.classList.remove('visible');
  } else {
    slider.className = 'role-toggle-slider teacher-slide';
    btnT.classList.add('active'); btnS.classList.remove('active');
    tFields.classList.add('active'); sFields.classList.remove('active');
    loginBtn.className = 'btn btn-primary';
    loginBtn.textContent = 'Sign In as Teacher';
    desc.innerHTML = '<span class="role-desc-icon">👩‍🏫</span> Sign in with your school Gmail and your teacher account password.';
    if(registerBtn) registerBtn.style.display = 'flex';
    // Show forgot password for teachers
    forgotLink.classList.add('visible');
  }

  input.value = role;
  clearErrors();
}

function clearErrors(){
  document.querySelectorAll('.alert').forEach(function(a){
    a.style.opacity = '0'; a.style.transform = 'translateY(-6px)';
    a.style.transition = 'all .2s ease';
    setTimeout(function(){ a.remove(); }, 200);
  });
}

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

function togglePassword(){
  var pw = document.getElementById('password');
  var open = document.getElementById('pwEyeOpen');
  var closed = document.getElementById('pwEyeClosed');
  if(pw.type === 'password'){ pw.type = 'text'; open.style.display = 'none'; closed.style.display = 'block'; }
  else { pw.type = 'password'; open.style.display = 'block'; closed.style.display = 'none'; }
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

document.getElementById('loginForm').addEventListener('submit', function(){
  var btn = document.getElementById('loginBtn');
  if(currentRole === 'student'){
    var sid = document.getElementById('student_id').value.trim();
    if(!sid){ showToast('Please enter your Student ID.','error'); return; }
  } else {
    var em = document.getElementById('email').value.trim();
    if(!em){ showToast('Please enter your email address.','error'); return; }
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)){ showToast('Please enter a valid email address.','error'); return; }
  }
  var pw = document.getElementById('password').value.trim();
  if(!pw){ showToast('Please enter your password.','error'); return; }
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