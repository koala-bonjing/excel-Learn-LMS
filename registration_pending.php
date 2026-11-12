<?php
session_start();
$name  = $_SESSION['pending_name']  ?? 'Teacher';
$email = $_SESSION['pending_email'] ?? '';

// Clear so user can't revisit stale session data
unset($_SESSION['pending_name'], $_SESSION['pending_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ExceLearn — Registration Submitted</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: #09090b;
    color: #e4e4e7;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }

  .card {
    background: #18181b;
    border: 1px solid #27272a;
    border-radius: 16px;
    max-width: 480px;
    width: 100%;
    padding: 48px 40px;
    text-align: center;
    box-shadow: 0 24px 64px rgba(0,0,0,.5);
    animation: fadeUp .5s ease both;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .icon {
    width: 72px;
    height: 72px;
    background: linear-gradient(135deg, #f59e0b22, #f59e0b11);
    border: 2px solid #f59e0b44;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    font-size: 32px;
  }

  h1 {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 8px;
    color: #f4f4f5;
  }

  .sub {
    color: #a1a1aa;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 28px;
  }

  .info-box {
    background: #27272a;
    border-left: 3px solid #f59e0b;
    border-radius: 8px;
    padding: 14px 18px;
    text-align: left;
    font-size: 13.5px;
    color: #d4d4d8;
    line-height: 1.7;
    margin-bottom: 32px;
  }

  .info-box b { color: #f59e0b; }

  .email-badge {
    display: inline-block;
    background: #3f3f46;
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 13px;
    color: #e4e4e7;
    font-weight: 500;
    margin-top: 6px;
    word-break: break-all;
  }

  .btn-back {
    display: inline-block;
    text-decoration: none;
    background: transparent;
    border: 1px solid #3f3f46;
    color: #a1a1aa;
    padding: 10px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    transition: all .2s;
  }

  .btn-back:hover {
    background: #27272a;
    color: #e4e4e7;
    border-color: #52525b;
  }

  .footer {
    margin-top: 32px;
    color: #52525b;
    font-size: 12px;
  }
</style>
</head>
<body>

<div class="card">
  <div class="icon">⏳</div>

  <h1>Registration Submitted!</h1>

  <p class="sub">
    Your teacher account for <b>ExceLearn</b> has been received and is now
    waiting for administrator approval.
  </p>

  <div class="info-box">
    👋 &nbsp;Hi <b><?php echo htmlspecialchars($name); ?></b>, here's what to expect:<br><br>

    ✉️ &nbsp;A confirmation email has been sent to:<br>
    <?php if ($email): ?>
      <span class="email-badge"><?php echo htmlspecialchars($email); ?></span><br><br>
    <?php endif; ?>

    ✅ &nbsp;Once the <b>admin approves</b> your account, you'll receive
    another email and can then log in to ExceLearn.
  </div>

  <a href="index.php" class="btn-back">← Back to Login</a>

  <div class="footer">
    ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © 2025–2026
  </div>
</div>

</body>
</html>