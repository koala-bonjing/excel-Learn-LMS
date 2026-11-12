<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['student_id']) && isset($_COOKIE['excelearn_rem_user'])) {
    $cookie_data = explode('|', $_COOKIE['excelearn_rem_user']);
    if (count($cookie_data) === 3 && $cookie_data[0] === 'student') {
        $cookie_id    = (int)$cookie_data[1];
        $cookie_token = $cookie_data[2];
        $stmt = $conn->prepare("SELECT id, firstname, lastname, student_id, section_id FROM tblstudents WHERE id = ? AND remember_token = ? AND status = 'active'");
        $stmt->bind_param("is", $cookie_id, $cookie_token);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user) {
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['student_id']  = $user['id'];
            $_SESSION['student_lrn'] = $user['student_id'];
            $_SESSION['section_id']  = $user['section_id'];
            $_SESSION['user_role']   = 'student';
            $_SESSION['user_name']   = $user['firstname'] . ' ' . $user['lastname'];
        } else {
            setcookie("excelearn_rem_user", "", time() - 3600, "/");
        }
    }
}

if (!isset($_SESSION['student_id'])) {
    header("Location: ../index.php");
    exit();
}

$student_id = (int)$_SESSION['student_id'];

$student = $conn->query("SELECT * FROM tblstudents WHERE id = $student_id")->fetch_assoc();

if (!$student) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$section_id = (int)$student['section_id'];

$section_row = $conn->query("SELECT * FROM tblsections WHERE section_id = $section_id")->fetch_assoc();

$strand_id = 0;
if ($section_row) {
    if (isset($section_row['strand_id']))        $strand_id = (int)$section_row['strand_id'];
    elseif (isset($section_row['strandid']))      $strand_id = (int)$section_row['strandid'];
    elseif (isset($section_row['strand']))        $strand_id = (int)$section_row['strand'];
    elseif (isset($section_row['tblstrands_id'])) $strand_id = (int)$section_row['tblstrands_id'];
}

$strand_label = 'N/A';
if ($strand_id) {
    $strand = $conn->query("SELECT strand FROM tblstrands WHERE strand_id = $strand_id")->fetch_assoc();
    if ($strand) $strand_label = htmlspecialchars($strand['strand']);
} elseif ($section_row && isset($section_row['section_name'])) {
    $strand_label = htmlspecialchars($section_row['section_name']);
} elseif ($section_row && isset($section_row['section'])) {
    $strand_label = htmlspecialchars($section_row['section']);
}

$subjects = $conn->query("
    SELECT ts.teacher_subject_id, ts.teacher_id, ts.subject_id,
           subj.subject AS subject_title, subj.strand_id
    FROM tbl_teacher_subjects ts
    JOIN tblsubjects subj ON subj.subject_id = ts.subject_id
    " . ($strand_id ? "WHERE subj.strand_id = $strand_id" : "") . "
")->fetch_all(MYSQLI_ASSOC);

$lessons_data = [];
foreach ($subjects as $subj) {
    $tsid = (int)$subj['teacher_subject_id'];
    $total = $conn->query("
        SELECT COUNT(*) AS cnt FROM tbl_teacher_lessons
        WHERE teacher_subject_id = $tsid AND status = 'published'
    ")->fetch_assoc()['cnt'];
    $lessons_data[$tsid] = ['total' => (int)$total];
}

$deadlines = $conn->query("
    SELECT q.quiz_id, q.due_date, q.description, l.title AS lesson_title, l.teacher_subject_id
    FROM tblquizzes q
    JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    WHERE q.due_date >= NOW()
      AND q.status = 'published'
      AND q.quiz_id NOT IN (
          SELECT quizzes_id FROM tblquiz_results WHERE student_id = $student_id
      )
    ORDER BY q.due_date ASC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$grades = $conn->query("
    SELECT qr.score, q.question_count, q.description, l.title AS lesson_title,
           (
               SELECT COUNT(*) FROM tblquiz_results qr2
               WHERE qr2.quizzes_id = qr.quizzes_id
                 AND qr2.student_id = qr.student_id
                 AND qr2.created_at <= qr.created_at
           ) AS take_number
    FROM tblquiz_results qr
    JOIN tblquizzes q ON q.quiz_id = qr.quizzes_id
    JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    WHERE qr.student_id = $student_id
    ORDER BY qr.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$leaderboard = $conn->query("
    SELECT s.id, s.firstname, s.lastname,
           ROUND(
               SUM(
                   CASE 
                       WHEN q.question_count > 0 AND qr.score <= q.question_count
                           THEN (qr.score / q.question_count) * 100
                       WHEN q.question_count > 0 AND qr.score > q.question_count
                           THEN qr.score
                       ELSE 0
                   END
               ) / NULLIF(COUNT(qr.id), 0)
           , 1) AS avg_score
    FROM tblstudents s
    JOIN tblquiz_results qr ON qr.student_id = s.id
    JOIN tblquizzes q ON q.quiz_id = qr.quizzes_id
    WHERE s.section_id = $section_id
    GROUP BY s.id, s.firstname, s.lastname
    ORDER BY avg_score DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$ann_page      = max(1, (int)($_GET['ann_page'] ?? 1));
$ann_per_page  = 3;
$ann_offset    = ($ann_page - 1) * $ann_per_page;
$ann_search    = trim($_GET['ann_search'] ?? '');
$ann_search_param = '%' . $ann_search . '%';

$ann_where = "
    (
        (u.role = 'admin' AND a.category IN ('for_student', 'both'))
        OR
        (u.role = 'teacher' AND a.category = 'for_student' AND a.user_id IN (
            SELECT DISTINCT ts.teacher_id
            FROM tbl_teachers_students ts
            WHERE ts.student_id = $student_id
        ))
    )
";

$ann_search_clause = $ann_search
    ? " AND (a.title LIKE ? OR a.message LIKE ?)"
    : "";

// Count total
$count_sql = "SELECT COUNT(*) FROM tblannouncements a LEFT JOIN tblusers u ON u.id = a.user_id WHERE $ann_where $ann_search_clause";
$count_stmt = $conn->prepare($count_sql);
if ($ann_search) {
    $count_stmt->bind_param('ss', $ann_search_param, $ann_search_param);
}
$count_stmt->execute();
$ann_total  = (int)$count_stmt->get_result()->fetch_row()[0];
$ann_pages  = max(1, (int)ceil($ann_total / $ann_per_page));
$count_stmt->close();

// Fetch page
$fetch_sql = "
    SELECT a.id, a.title, a.message, a.type, a.category, a.created_at,
           u.firstname, u.lastname, u.role AS poster_role
    FROM tblannouncements a
    LEFT JOIN tblusers u ON u.id = a.user_id
    WHERE $ann_where $ann_search_clause
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
";
$fetch_stmt = $conn->prepare($fetch_sql);
if ($ann_search) {
    $fetch_stmt->bind_param('ssii', $ann_search_param, $ann_search_param, $ann_per_page, $ann_offset);
} else {
    $fetch_stmt->bind_param('ii', $ann_per_page, $ann_offset);
}
$fetch_stmt->execute();
$announcements = $fetch_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$fetch_stmt->close();

$activities = $conn->query("
    SELECT qr.created_at, qr.score, q.question_count, q.passing_score,
           l.title AS lesson_title, q.description AS quiz_title,
           (
               SELECT COUNT(*) FROM tblquiz_results qr2
               WHERE qr2.quizzes_id = qr.quizzes_id
                 AND qr2.student_id = qr.student_id
                 AND qr2.created_at <= qr.created_at
           ) AS take_number
    FROM tblquiz_results qr
    JOIN tblquizzes q ON q.quiz_id = qr.quizzes_id
    JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    WHERE qr.student_id = $student_id
    ORDER BY qr.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$enrolled_count  = count($subjects);
$completed_lessons = (int)$conn->query("
    SELECT COUNT(DISTINCT q.lesson_id) AS cnt
    FROM tblquiz_results qr
    JOIN tblquizzes q ON q.quiz_id = qr.quizzes_id
    WHERE qr.student_id = $student_id
")->fetch_assoc()['cnt'];


$avg_grade_row = $conn->query("
    SELECT 
        COUNT(qr.id) AS total_quizzes,
        ROUND(
            SUM(
                CASE 
                    WHEN q.question_count > 0 AND qr.score <= q.question_count 
                        THEN (qr.score / q.question_count) * 100
                    WHEN q.question_count > 0 AND qr.score > q.question_count 
                        THEN qr.score
                    ELSE 0
                END
            ) / NULLIF(COUNT(qr.id), 0)
        , 1) AS avg
    FROM tblquiz_results qr
    JOIN tblquizzes q ON q.quiz_id = qr.quizzes_id
    WHERE qr.student_id = $student_id
")->fetch_assoc();

$avg_grade    = (float)($avg_grade_row['avg'] ?? 0);
$total_quizzes = (int)($avg_grade_row['total_quizzes'] ?? 0);

$quiz_count = (int)$conn->query("
    SELECT COUNT(DISTINCT q.quiz_id)
    FROM tblquizzes q
    JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = l.teacher_subject_id
    JOIN tblsubjects subj ON subj.subject_id = ts.subject_id
    JOIN tbl_teachers_students tts ON tts.teacher_id = ts.teacher_id
    WHERE tts.student_id = $student_id AND q.status = 'published'
")->fetch_row()[0];

$lesson_count = (int)$conn->query("
    SELECT COUNT(DISTINCT l.lesson_id)
    FROM tbl_teacher_lessons l
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = l.teacher_subject_id
    JOIN tbl_teachers_students tts ON tts.teacher_id = ts.teacher_id
    WHERE tts.student_id = $student_id AND l.status = 'published'
")->fetch_row()[0];

$subject_count = (int)$conn->query("
    SELECT COUNT(DISTINCT ts.teacher_subject_id)
    FROM tbl_teacher_subjects ts
    JOIN tbl_teachers_students tts ON tts.teacher_id = ts.teacher_id
    WHERE tts.student_id = $student_id
")->fetch_row()[0];

// ── Notifications ──────────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT notification_id, title, message, type, status, created_at
    FROM tblnotifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 30
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unread_count = count(array_filter($notifications, fn($n) => $n['status'] === 'unread'));

function getNotifUrl(string $type): string {
    return match($type) {
        'new_lesson'             => 'student_lessons.php',
        'lesson_expiring',
        'lesson_expired'         => 'student_lessons.php',
        'new_quiz',
        'quiz_due_soon',
        'quiz_overdue'           => 'student_quizzes.php',
        'quiz_passed',
        'quiz_failed'            => 'student_grades.php',
        'new_announcement'       => 'index.php#announcements',
        'new_subject_assigned'   => 'student_courses.php',
        default                  => '#',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ExceLearn LMS — Student Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/student/index.css">
<?php if (!empty($student_id)): ?>
<script>
  function registerOneSignal() {
    if (typeof median !== 'undefined' && median.onesignal) {
      median.onesignal.login("<?php echo $student_id; ?>");
      median.onesignal.tags.setTags({
        tags: { user_id: "<?php echo $student_id; ?>" }
      });
      console.log("✅ OneSignal tags set: user_id=<?php echo $student_id; ?>");
    } else if (typeof OneSignal !== 'undefined') {
      // Fallback: web SDK
      OneSignal.push(function() {
        OneSignal.sendTag("user_id", "<?php echo $student_id; ?>", function(tagsSent) {
          console.log("✅ OneSignal web tag set:", tagsSent);
        });
      });
    } else {
      setTimeout(registerOneSignal, 300);
    }
  }

  // Also fire on median deviceready event as backup
  document.addEventListener("median_deviceready", function() {
    registerOneSignal();
  });

  registerOneSignal();
</script>
<?php endif; ?>
<style>
.stats-grid {
  grid-template-columns: repeat(4, 1fr);
}

.stat-card {
  aspect-ratio: 1 / 1;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(4, 2fr);
  }

  .stat-card {
    aspect-ratio: unset;
    padding: 14px;
  }

  .stat-value {
    font-size: 1.0rem;
  }

  .stat-change {
    font-size: 0.5rem;
    white-space: normal;
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
    <div class="nav-section-label">Overview</div>

    <a class="nav-item active" href="index.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>
    <div class="nav-section-label">Learning</div>
    <a class="nav-item" href="student_courses.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      My Subjects
      <span class="nav-badge"><?= $subject_count ?></span>
    </a>
    <a class="nav-item" href="student_performance_tasks.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M9 2H7a2 2 0 00-2 2v16a2 2 0 002 2h10a2 2 0 002-2V4a2 2 0 00-2-2h-2"/>
        <rect x="9" y="2" width="6" height="4" rx="1" ry="1"/>
        <path d="M9 14l2 2 4-4"/>
      </svg>
      Performance Tasks
    </a>

    <a class="nav-item" href="student_virtual_class.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
      </svg>
      Virtual Class
    </a>

    <div class="nav-section-label" style="margin-top:16px">Progress</div>

    <a class="nav-item" href="student_grades.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
        <polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
      Grades
    </a>
    <div class="nav-section-label" style="margin-top:16px">Account</div>

    <a class="nav-item" href="student_profile.php">
    <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    My Profile
    </a>
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
    <div style="margin-top:8px;border-top:1px solid rgba(255,255,255,0.08);padding-top:8px"></div>
    <a class="nav-item" href="#" onclick="confirmLogout(event)" style="color:var(--red)">
    <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
    </svg>
    Logout
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--cyan),var(--accent));overflow:hidden;padding:0">
        <?php
        $pic = $student['profile_pic'] ?? '';
        $found = false;
        if (!empty($pic)) {
            foreach (['../uploads/profile_pic/students/'.$pic, '../uploads/profile_pic/'.$pic, '../'.$pic] as $p) {
                if (file_exists($p)) {
                    echo '<img src="'.htmlspecialchars($p).'" style="width:100%;height:100%;object-fit:cover;border-radius:inherit" alt="">';
                    $found = true;
                    break;
                }
            }
        }
        if (!$found) echo strtoupper(substr($student['firstname'],0,1).substr($student['lastname'],0,1));
        ?>
      </div>
      <div class="user-info">
        <p><?= htmlspecialchars($student['firstname'].' '.$student['middlename']. ' '.$student['lastname']) ?></p>
        <?php
        $grade_row = $conn->query("
            SELECT g.grade_level
            FROM tblsections sec
            JOIN tblgrade_levels g ON g.grade_id = sec.grade_level_id
            WHERE sec.section_id = $section_id
        ")->fetch_assoc();
        if ($grade_row): ?>
          <span><?= htmlspecialchars($grade_row['grade_level']) ?> — <?= $strand_label ?></span>
        <?php endif; ?>
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
      <button class="btn-icon" onclick="openNotifModal()" aria-label="Notifications" style="position:relative;background:rgba(255,255,255,0.08);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.12);font-size:1.0rem;cursor:pointer;padding:6px 10px;border-radius:10px;color:var(--text,#f1f5f9);transition:background .2s;">
      🔔
      <?php if ($unread_count > 0): ?>
      <span id="notif-badge" style="position:absolute;top:4px;right:4px;background:var(--red,#ef4444);color:#fff;font-size:9px;font-weight:700;border-radius:999px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 3px;line-height:1;"><?= $unread_count ?></span>
      <?php else: ?>
      <span id="notif-badge" style="display:none"></span>
      <?php endif; ?>
    </button>
    </div>
  </div>

  <div class="content">
    <div class="section-header" style="margin-bottom:4px">
      <div>
        <div class="section-title">Welcome back, <?= htmlspecialchars($student['firstname']) ?>! 🌟</div>
        <div class="section-sub">Keep up the great work — you're on a 7-day streak!</div>
      </div>
    </div>
    <div class="stats-grid">
      <div class="stat-card blue">
        <div class="stat-icon">📖</div>
        <div class="stat-value"><?= $enrolled_count ?></div>
        <div class="stat-label">Enrolled Courses</div>
        <div class="stat-change up">↑ 1 new this week</div>
      </div>
      <div class="stat-card green">
        <div class="stat-icon">✅</div>
        <div class="stat-value"><?= $completed_lessons ?></div>
        <div class="stat-label">Lessons Completed</div>
        <div class="stat-change up">↑ 5 this week</div>
      </div>
      <div class="stat-card amber">
        <div class="stat-icon">🏆</div>
        <div class="stat-value"><?= $avg_grade ?>%</div>
        <div class="stat-label">Average Grade</div>
        <div class="stat-change up"><?= $avg_grade > 0 ? '↑ across all subjects' : 'No quizzes yet' ?></div>
      </div>
      <div class="stat-card purple">
        <div class="stat-icon">📝</div>
        <div class="stat-value"><?= $total_quizzes ?></div>
        <div class="stat-label">Quizzes Taken</div>
        <div class="stat-change up">
          <?= $total_quizzes > 0 ? '↑ ' . $total_quizzes . ' quiz' . ($total_quizzes != 1 ? 'zes' : '') . ' completed' : 'No quizzes yet' ?>
        </div>
      </div>
    </div>
    <div class="card" style="margin-bottom:24px">
      <div class="card-header">
        <div class="card-title">📢 Announcements</div>
      </div>
    
      <!-- Search bar -->
      <form method="GET" action="index.php" id="ann-search-form"
            style="padding:0 0 12px 0;display:flex;gap:8px;align-items:center;">
        <input type="text" name="ann_search" id="ann-search-input"
               value="<?= htmlspecialchars($ann_search) ?>"
               placeholder="🔍 Search announcements..."
               style="flex:1;padding:8px 12px;border-radius:8px;border:1px solid rgba(255,255,255,0.12);
                      background:rgba(255,255,255,0.06);color:var(--text,#f1f5f9);font-size:13px;outline:none;">
        <button type="submit"
                style="padding:7px 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.12);
                       background:rgba(255,255,255,0.08);color:var(--text,#f1f5f9);font-size:12px;cursor:pointer;">
          Search
        </button>
        <?php if ($ann_search): ?>
        <a href="index.php"
           style="padding:7px 12px;border-radius:8px;border:1px solid rgba(239,68,68,0.3);
                  background:rgba(239,68,68,0.08);color:#ef4444;font-size:12px;text-decoration:none;">
          ✕ Clear
        </a>
        <?php endif; ?>
      </form>
    
      <div id="announcements-list">
        <?php if (empty($announcements)): ?>
          <div style="text-align:center;padding:28px 16px;color:var(--text3,#64748b);font-size:13px;">
            <div style="font-size:32px;margin-bottom:6px;"><?= $ann_search ? '🔍' : '📭' ?></div>
            <?= $ann_search
              ? 'No announcements found for "' . htmlspecialchars($ann_search) . '".'
              : 'No announcements yet.' ?>
          </div>
        <?php else: ?>
          <?php foreach($announcements as $a):
              $created  = new DateTime($a['created_at'], new DateTimeZone('UTC'));
              $now_dt   = new DateTime('now', new DateTimeZone('UTC'));
              $diff     = $now_dt->diff($created);
              $totalHrs = (int)$diff->days * 24 + (int)$diff->h;
    
              if ($diff->days >= 365)       $time_label = $diff->y . ' year'  . ($diff->y  > 1 ? 's' : '') . ' ago';
              elseif ($diff->days >= 30)    $time_label = round($diff->days / 30) . ' month' . (round($diff->days/30) > 1 ? 's' : '') . ' ago';
              elseif ($diff->days >= 1)     $time_label = $diff->days . ' day'  . ($diff->days > 1 ? 's' : '') . ' ago';
              elseif ($totalHrs >= 1)       $time_label = $totalHrs . ' hr'   . ($totalHrs > 1 ? 's' : '') . ' ago';
              elseif ($diff->i >= 1)        $time_label = $diff->i . ' min ago';
              else                          $time_label = 'Just now';
    
              $tag         = in_array($a['type'], ['important','info','update']) ? $a['type'] : 'info';
              $poster_role = strtolower($a['poster_role'] ?? 'unknown');
              $poster_name = trim(($a['firstname'] ?? '') . ' ' . ($a['lastname'] ?? ''));
              $role_color  = $poster_role === 'admin' ? '#ef4444' : '#8b5cf6';
              $role_label  = $poster_role === 'admin' ? '🛡️ Admin' : '👨‍🏫 Teacher';
          ?>
            <div class="announce-item" style="display:flex;flex-direction:column;gap:4px;">
              <div class="announce-header">
                <span class="announce-tag <?= htmlspecialchars($tag) ?>"><?= htmlspecialchars($a['type']) ?></span>
                <span style="font-size:10px;padding:1px 8px;border-radius:20px;background:<?= $role_color ?>22;color:<?= $role_color ?>;font-weight:600;">
                  <?= $role_label ?>: <?= htmlspecialchars($poster_name ?: 'Unknown') ?>
                </span>
                <span class="announce-time"><?= $time_label ?></span>
              </div>
              <div style="font-size:14px;font-weight:700;color:var(--text,#f1f5f9);line-height:1.3;padding:2px 0;">
                <?= htmlspecialchars($a['title']) ?>
              </div>
              <div style="font-size:13px;color:var(--text2,#94a3b8);line-height:1.5;">
                <?= htmlspecialchars($a['message']) ?>
              </div>
            </div>
          <?php endforeach; ?>
    
          <!-- Pagination -->
          <?php if ($ann_pages > 1): ?>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.08);">
            <div style="font-size:12px;color:var(--text3,#64748b);">
              Page <?= $ann_page ?> of <?= $ann_pages ?>
              <span style="margin-left:4px;">(<?= $ann_total ?> total)</span>
            </div>
            <div style="display:flex;gap:6px;">
              <?php
              $base_url = 'index.php?ann_search=' . urlencode($ann_search) . '&ann_page=';
              $prev = max(1, $ann_page - 1);
              $next = min($ann_pages, $ann_page + 1);
              ?>
              <a href="<?= $base_url . $prev ?>"
                 style="padding:4px 10px;font-size:12px;border-radius:6px;border:1px solid rgba(255,255,255,0.12);
                        background:rgba(255,255,255,0.06);color:var(--text,#f1f5f9);text-decoration:none;
                        <?= $ann_page <= 1 ? 'opacity:.4;pointer-events:none;' : '' ?>">
                ← Prev
              </a>
              <?php for ($p = max(1, $ann_page - 2); $p <= min($ann_pages, $ann_page + 2); $p++): ?>
              <a href="<?= $base_url . $p ?>"
                 style="padding:4px 10px;font-size:12px;border-radius:6px;text-decoration:none;
                        <?= $p === $ann_page
                          ? 'background:var(--accent,#6366f1);color:#fff;border:none;'
                          : 'background:rgba(255,255,255,0.06);color:var(--text,#f1f5f9);border:1px solid rgba(255,255,255,0.12);' ?>">
                <?= $p ?>
              </a>
              <?php endfor; ?>
              <a href="<?= $base_url . $next ?>"
                 style="padding:4px 10px;font-size:12px;border-radius:6px;border:1px solid rgba(255,255,255,0.12);
                        background:rgba(255,255,255,0.06);color:var(--text,#f1f5f9);text-decoration:none;
                        <?= $ann_page >= $ann_pages ? 'opacity:.4;pointer-events:none;' : '' ?>">
                Next →
              </a>
            </div>
          </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:18px;margin-bottom:24px;min-width:0;width:100%" class="dash-grid">
      <div class="card" style="min-width:0;overflow:hidden">
        <div class="card-header">
          <div class="card-title">📊 My Subject Progress</div>
          <a href="student_courses.php" class="btn btn-ghost btn-sm">View all</a>
        </div>
        <div id="course-progress-list">
          <?php foreach($subjects as $idx => $subj):
              $tsid = $subj['teacher_subject_id'];
              $total = $lessons_data[$tsid]['total'] ?? 1;
              $done = $conn->query("
                  SELECT COUNT(DISTINCT q.lesson_id) as cnt
                  FROM tblquiz_results qr
                  JOIN tblquizzes q ON q.quiz_id = qr.quizzes_id
                  JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
                  WHERE qr.student_id = $student_id AND l.teacher_subject_id = $tsid
              ")->fetch_assoc()['cnt'] ?? 0;
              $pct = $total > 0 ? round(($done / $total) * 100) : 0;
              $fillClass = $pct >= 90 ? 'green-fill' : ($pct >= 60 ? '' : ($pct >= 40 ? 'amber-fill' : 'red-fill'));
              $color = $pct >= 90 ? 'var(--green)' : ($pct >= 60 ? 'var(--accent)' : ($pct >= 40 ? 'var(--amber)' : 'var(--red)'));
              $icons = ['🧬','📐','🌍','📖','🔬','📊'];
              $icon = $icons[$idx % count($icons)];
          ?>
            <a href="student_courses.php" style="text-decoration:none;color:inherit;display:block;margin-bottom:16px">
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px">
                <span style="font-size:20px"><?= $icon ?></span>
                <div style="flex:1;min-width:0">
                  <div style="font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?= htmlspecialchars($subj['subject_title']) ?>
                  </div>
                  <div style="font-size:12px;color:var(--text3)"><?= $done ?> of <?= $total ?> lessons</div>
                </div>
                <span style="font-size:14px;font-weight:700;flex-shrink:0;color:<?= $color ?>"><?= $pct ?>%</span>
              </div>
              <div class="progress-bar"><div class="progress-fill <?= $fillClass ?>" style="width:<?= $pct ?>%"></div></div>
            </a>
          <?php endforeach; ?>
          </div>
      </div>

      <div class="card" style="min-width:0;overflow:hidden">
        <div class="card-header">
          <div class="card-title">⏰ Upcoming Deadlines</div>
        </div>
        <div id="deadlines-list">
          <?php if(empty($deadlines)): ?>
            <div class="empty-state"><div class="empty-icon">🎉</div><p>All caught up!</p><span>No upcoming deadlines.</span></div>
          <?php else: foreach($deadlines as $d):
              $due = new DateTime($d['due_date']);
              $now = new DateTime();
              $diff = $now->diff($due)->days;
              $urgency = $diff == 0 ? 'urgent' : ($diff <= 3 ? 'soon' : 'later');
              $due_label = $diff == 0 ? 'Today' : ($diff == 1 ? 'Tomorrow' : $due->format('M j, g:i A'));
          ?>
            <div class="deadline-item">
              <div class="deadline-dot <?= $urgency ?>"></div>
              <div class="deadline-info">
                <div class="deadline-title"><?= htmlspecialchars($d['lesson_title'].' — Quiz') ?></div>
                <div class="deadline-meta"><?= htmlspecialchars($d['description']) ?></div>
              </div>
              <div class="deadline-due <?= $urgency ?>"><?= $due_label ?></div>
            </div>
          <?php endforeach; endif; ?>
          </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:24px" class="dash-grid">

      <div class="card">
        <div class="card-header">
          <div class="card-title">📋 Recent Quiz Scores</div>
          <a href="student_grades.php" class="btn btn-ghost btn-sm">All Scores</a>
        </div>
        <div id="grades-list">
          <?php foreach($grades as $g):
              $pct = $g['question_count'] > 0 ? round(($g['score'] / $g['question_count']) * 100) : 0;
              $color = $pct >= 90 ? 'var(--green)' : ($pct >= 80 ? 'var(--accent)' : ($pct >= 70 ? 'var(--amber)' : 'var(--red)'));
              $fillClass = $pct >= 90 ? 'green-fill' : ($pct >= 80 ? '' : ($pct >= 70 ? 'amber-fill' : 'red-fill'));
              $label = $pct >= 90 ? 'Excellent' : ($pct >= 80 ? 'Good' : ($pct >= 70 ? 'Fair' : 'Needs Work'));
          ?>
            <div class="grade-row">
              <div class="grade-course">
                  <?= htmlspecialchars($g['lesson_title']) ?>
                  <?php if ((int)($g['take_number'] ?? 1) >= 1): ?>
                      <span style="font-size:11px;color:var(--text3);font-weight:600">#<?= (int)$g['take_number'] ?></span>
                  <?php endif; ?>
              </div>
              <div class="grade-score" style="color:<?= $color ?>"><?= $g['score'] ?></div>
              <div class="grade-bar">
                <div class="progress-bar"><div class="progress-fill <?= $fillClass ?>" style="width:<?= $pct ?>%"></div></div>
              </div>
              <div class="grade-label"><?= $label ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title">🏅 Class Leaderboard</div>
          <span class="badge badge-blue">
            <?php
            $lb_grade = $conn->query("
                SELECT g.grade_level
                FROM tblsections sec
                JOIN tblgrade_levels g ON g.grade_id = sec.grade_level_id
                WHERE sec.section_id = $section_id
            ")->fetch_assoc();
            echo $lb_grade ? htmlspecialchars($lb_grade['grade_level']) : 'My Class';
            ?>
          </span>
        </div>
        <div id="leaderboard-list">
          <?php 
          $gradients = [
              "linear-gradient(135deg,#f5a623,#e08c0c)",
              "linear-gradient(135deg,var(--cyan),var(--accent))",
              "linear-gradient(135deg,var(--purple),#8b3fbf)",
              "linear-gradient(135deg,var(--green),#1aaa6e)",
              "linear-gradient(135deg,var(--red),#e04040)"
          ];
          foreach($leaderboard as $i => $s):
              $rankClass = $i == 0 ? 'gold' : ($i == 1 ? 'silver' : ($i == 2 ? 'bronze' : 'normal'));
              $isMe = $s['id'] == $student_id;
              $initials = strtoupper(substr($s['firstname'],0,1).substr($s['lastname'],0,1));
              $wrapper = $isMe ? 'lb-me' : '';
          ?>
            <div class="<?= $wrapper ?>">
              <div class="leaderboard-item" <?= $isMe ? 'style="border:none"' : '' ?>>
                <div class="lb-rank <?= $rankClass ?>"><?= $i+1 ?></div>
                <div class="lb-avatar" style="background:<?= $gradients[$i % 5] ?>;color:#fff"><?= $initials ?></div>
                <div class="lb-name">
                  <?= htmlspecialchars($s['firstname'].' '.$s['lastname']) ?>
                  <?= $isMe ? '<span style="font-size:11px;color:var(--accent)">(You)</span>' : '' ?>
                </div>
                <div class="lb-score"><?= $s['avg_score'] ?>%</div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:24px" class="dash-grid">
      <!-- Recent Activity -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">🕐 Recent Activity</div>
        </div>
        <div id="activity-list">
          <?php if (empty($activities)): ?>
            <div class="empty-state"><div class="empty-icon">📭</div><p>No activity yet.</p></div>
          <?php else: foreach ($activities as $act):
              $raw   = (float)$act['score'];
              $total = (float)$act['question_count'];

              if ($total > 0 && $raw <= $total) {
                  $pct = round(($raw / $total) * 100);
              } elseif ($total > 0 && $raw > $total) {
                  $pct = (int)$raw;
              } else {
                  $pct = 0;
              }

              $passed  = $pct >= (int)$act['passing_score'];
              $timeAgo  = (new DateTime($act['created_at']))->diff(new DateTime());
              $totalHrs = (int)$timeAgo->days * 24 + (int)$timeAgo->h;

              if ($timeAgo->days >= 1) {
                  $time_label = $timeAgo->days . ' day' . ($timeAgo->days > 1 ? 's' : '') . ' ago';
              } elseif ($totalHrs >= 1) {
                  $time_label = $totalHrs . ' hr' . ($totalHrs > 1 ? 's' : '') . ' ago';
              } elseif ($timeAgo->i >= 1) {
                  $time_label = $timeAgo->i . ' min ago';
              } else {
                  $time_label = 'Just now';
              }

              $take_num  = (int)($act['take_number'] ?? 1);
              $take_badge = $take_num >= 1 ? ' <span style="font-size:11px;color:var(--text3);font-weight:600">#' . $take_num . '</span>' : '';
              $quiz_name = (!empty($act['quiz_title']) 
                  ? htmlspecialchars($act['quiz_title']) 
                  : htmlspecialchars($act['lesson_title']) . ' Quiz') . $take_badge;
          ?>
            <div class="activity-item">
              <div class="activity-dot <?= $passed ? 'green-dot' : 'amber-dot' ?>">
                <?= $passed ? '✅' : '📝' ?>
              </div>
              <div>
                <div class="activity-text">
                  <strong>Scored <?= $pct ?>%</strong>
                  <?= $passed 
                      ? '<span style="font-size:11px;color:var(--green)"> ✓ Passed</span>' 
                      : '<span style="font-size:11px;color:var(--amber)"> ✗ Below passing</span>' ?>
                  on <?= $quiz_name ?>
                </div>
                <div class="activity-time"><?= $time_label ?></div>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>
<!-- NOTIFICATION MODAL -->
<div id="notifModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--bg2,#1e293b);border-radius:16px;width:90%;max-width:460px;max-height:80vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.5);">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.08);flex-shrink:0;">
      <div style="font-size:16px;font-weight:700;color:var(--text,#f1f5f9);">🔔 Notifications <br>
        <span id="notif-unread-count" style="font-size:11px;font-weight:500;color:var(--text3,#64748b);margin-left:6px;"><?= $unread_count > 0 ? "($unread_count unread)" : '' ?></span>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <?php if ($unread_count > 0): ?>
        <button onclick="markAllRead()" style="font-size:11px;padding:4px 10px;background:transparent;border:1.5px solid rgba(255,255,255,0.15);color:var(--text,#f1f5f9);border-radius:6px;cursor:pointer;">✓ Mark all read</button>
        <?php endif; ?>
        <button onclick="closeNotifModal()" style="background:none;border:none;color:var(--text3,#64748b);font-size:18px;cursor:pointer;padding:4px;line-height:1;">✕</button>
      </div>
    </div>
    <div style="overflow-y:auto;flex:1;padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
      <?php if (empty($notifications)): ?>
        <div style="text-align:center;padding:40px 16px;color:var(--text3,#64748b);">
          <div style="font-size:36px;margin-bottom:8px;">🔕</div>
          <div style="font-size:13px;">No notifications yet.</div>
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $notif):
          $n_ts   = strtotime($notif['created_at']);
          $n_diff = time() - $n_ts;
          if ($n_diff < 60)         $n_time = 'Just now';
          elseif ($n_diff < 3600)   $n_time = floor($n_diff/60).'m ago';
          elseif ($n_diff < 86400)  $n_time = floor($n_diff/3600).'h ago';
          elseif ($n_diff < 604800) $n_time = floor($n_diff/86400).'d ago';
          else                      $n_time = date('M j, Y', $n_ts);
          $is_unread = $notif['status'] === 'unread';
          $n_icon = match($notif['type']) {
            'new_lesson'             => '📖',
            'new_quiz'               => '📝',
            'quiz_passed'            => '🎉',
            'quiz_failed'            => '😞',
            'quiz_due_soon'          => '⏰',
            'quiz_overdue'           => '🚨',
            'new_subject_assigned'   => '📚',
            'new_announcement'       => '📢',
            'lesson_expiring'        => '⌛',
            'lesson_expired'         => '🔒',
            default                  => '🔔',
          };
        ?>
        <div class="notif-item" data-id="<?= $notif['notification_id'] ?>"
             data-url="<?= htmlspecialchars(getNotifUrl($notif['type'])) ?>"
             onclick="markOneRead(<?= $notif['notification_id'] ?>, this)"
             style="display:flex;gap:12px;align-items:flex-start;padding:10px 12px;border-radius:10px;cursor:pointer;transition:background .15s;
                    background:<?= $is_unread ? 'rgba(99,102,241,0.08)' : 'rgba(255,255,255,.03)' ?>;
                    border:1px solid <?= $is_unread ? 'rgba(99,102,241,0.2)' : 'rgba(255,255,255,.07)' ?>;">
          <div style="font-size:20px;flex-shrink:0;margin-top:1px;"><?= $n_icon ?></div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:<?= $is_unread ? '700' : '500' ?>;color:var(--text,#f1f5f9);margin-bottom:2px;line-height:1.3;">
              <?= htmlspecialchars($notif['title']) ?>
              <?php if ($is_unread): ?>
              <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#6366f1;margin-left:5px;vertical-align:middle;"></span>
              <?php endif; ?>
            </div>
            <div style="font-size:12px;color:var(--text3,#64748b);line-height:1.45;margin-bottom:4px;">
              <?= htmlspecialchars($notif['message']) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:2px;">
              <div style="font-size:10px;color:var(--text3,#64748b);opacity:.7;"><?= $n_time ?></div>
              <button onclick="deleteNotif(<?= $notif['notification_id'] ?>, this, event)"
                      style="font-size:10px;padding:2px 8px;border-radius:6px;border:none;background:rgba(239,68,68,0.12);color:#ef4444;cursor:pointer;line-height:1.5;">🗑 Delete</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<div class="modal-backdrop" id="logoutModal" onclick="cancelLogout(event)">
  <div class="modal" style="max-width:380px;padding:0;overflow:hidden">
    <div style="padding:28px 28px 20px;text-align:center">
      <div style="width:56px;height:56px;border-radius:50%;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px">🚪</div>
      <div style="font-size:1.15rem;font-weight:700;margin-bottom:8px">Log out?</div>
      <div style="font-size:0.875rem;color:var(--text2)">Are you sure you want to log out of your ExceLearn account?</div>
    </div>
    <div style="display:flex;gap:10px;padding:0 28px 24px">
      <button class="btn btn-ghost" style="flex:1" onclick="closeLogoutModal()">Cancel</button>
      <button class="btn" style="flex:1;background:var(--red);color:#fff;border:none" onclick="doLogout()">Yes, Log out</button>
    </div>
  </div>
</div>
<div class="toast-container" id="toast-container"></div>

<script>

  function confirmLogout(e) {
      e.preventDefault();
      document.getElementById('logoutModal').classList.add('open');
      document.body.style.overflow = 'hidden';
  }
  function closeLogoutModal() {
      document.getElementById('logoutModal').classList.remove('open');
      document.body.style.overflow = '';
  }
  function cancelLogout(e) {
      if (e.target === document.getElementById('logoutModal')) closeLogoutModal();
  }
  function doLogout() {
      window.location.href = '../logout.php';
  }

const streakDays = ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"];
const streakValues = [1,1,1,1,1,1,0];

function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('open');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}

function applyTheme(theme) {
  var isLight = theme === 'light';
  document.body.classList.toggle('light', isLight);
  document.getElementById('theme-icon').textContent = isLight ? '☀️' : '🌙';
  document.getElementById('theme-text').textContent = isLight ? 'Light Mode' : 'Dark Mode';
}

function toggleTheme() {
  var isLight = document.body.classList.contains('light');
  var newTheme = isLight ? 'dark' : 'light';
  applyTheme(newTheme);
  fetch('settings_handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ theme: newTheme })
  });
}

// Load theme from DB on page load
fetch('settings_handler.php')
  .then(function(r){ return r.json(); })
  .then(function(res){
    if (res.success && res.data) {
      applyTheme(res.data.theme || 'dark');
    }
  })
  .catch(function(){});

function showToast(msg, type){
  type = type || 'success';
  const icons = { success:'✅', error:'❌', info:'ℹ️' };
  const container = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = '<span class="toast-icon">' + (icons[type]||'✅') + '</span><span>' + msg + '</span>';
  container.appendChild(t);
  setTimeout(function(){
    t.style.opacity = '0';
    t.style.transform = 'translateX(60px)';
    t.style.transition = 'all .3s ease';
    setTimeout(function(){ t.remove(); }, 300);
  }, 3000);
}

// ── Notification Modal ────────────────────────────────────────────────────
function openNotifModal() {
  var m = document.getElementById('notifModal');
  m.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeNotifModal() {
  var m = document.getElementById('notifModal');
  m.style.display = 'none';
  document.body.style.overflow = '';
}
document.getElementById('notifModal').addEventListener('click', function(e) {
  if (e.target === this) closeNotifModal();
});

function markOneRead(id, el) {
  var url = el.dataset.url || '#';
  fetch('notif_mark_read.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'notification_id=' + id
  });
  if (!el.dataset.read) {
    el.style.background = 'rgba(255,255,255,.03)';
    el.style.border = '1px solid rgba(255,255,255,.07)';
    var dot = el.querySelector('span[style*="border-radius:50%"]');
    var title = el.querySelector('div[style*="font-weight"]');
    if (dot) dot.remove();
    if (title) title.style.fontWeight = '500';
    el.dataset.read = '1';
    updateBadgeCount(-1);
  }
  if (url && url !== '#') {
    setTimeout(function() { window.location.href = url; }, 150);
  }
}

function markAllRead() {
  fetch('notif_mark_read.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'mark_all=1'
  });
  document.querySelectorAll('.notif-item').forEach(function(el) {
    el.style.background = 'rgba(255,255,255,.03)';
    el.style.border = '1px solid rgba(255,255,255,.07)';
    var dot = el.querySelector('span[style*="border-radius:50%"]');
    var title = el.querySelector('div[style*="font-weight"]');
    if (dot) dot.remove();
    if (title) title.style.fontWeight = '500';
    el.dataset.read = '1';
  });
  updateBadgeCount(0);
  document.getElementById('notif-unread-count').textContent = '';
}

function updateBadgeCount(delta) {
  var badge = document.getElementById('notif-badge');
  if (!badge) return;
  var current = parseInt(badge.textContent) || 0;
  var next = delta === 0 ? 0 : Math.max(0, current + delta);
  if (next <= 0) { badge.style.display = 'none'; }
  else { badge.textContent = next; badge.style.display = 'flex'; }
}

function deleteNotif(id, btn, e) {
  e.stopPropagation();
  var item = btn.closest('.notif-item');
  fetch('notif_delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'notification_id=' + id
  });
  var wasUnread = !item.dataset.read && item.style.border.includes('99,102,241');
  item.style.transition = 'opacity .25s, transform .25s';
  item.style.opacity = '0';
  item.style.transform = 'translateX(30px)';
  setTimeout(function() {
    item.remove();
    var remaining = document.querySelectorAll('.notif-item');
    if (remaining.length === 0) {
      var body = document.querySelector('#notifModal > div > div:last-child');
      body.innerHTML = '<div style="text-align:center;padding:40px 16px;color:var(--text3,#64748b);"><div style="font-size:36px;margin-bottom:8px;">🔕</div><div style="font-size:13px;">No notifications yet.</div></div>';
    }
  }, 250);
  if (wasUnread) updateBadgeCount(-1);
}
var annSearchInput = document.getElementById('ann-search-input');
if (annSearchInput) {
  annSearchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') document.getElementById('ann-search-form').submit();
  });
}
</script>
<?php $conn->close(); ?>
</body>
</html>