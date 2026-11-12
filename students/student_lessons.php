<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$student_id = $_SESSION['user_id'];

/* ── Student info ── */
$stmt = $conn->prepare("
    SELECT s.id, s.firstname, s.middlename, s.lastname, s.profile_pic,
           s.section_id, sec.section, g.grade_level
    FROM tblstudents s
    LEFT JOIN tblsections sec ON s.section_id = sec.section_id
    LEFT JOIN tblgrade_levels g ON sec.grade_level_id = g.grade_id
    WHERE s.id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$student_name    = trim($student['firstname'] . ' ' . ($student['middlename'] ? $student['middlename'] . ' ' : '') . $student['lastname']);
$student_grade   = htmlspecialchars($student['grade_level'] ?? '');
$student_section = htmlspecialchars($student['section'] ?? '');

/* Avatar */
$fn = $student['firstname'] ?? '';
$ln = $student['lastname']  ?? '';
$initials = strtoupper(substr($fn,0,1).substr($ln,0,1));
if (strlen(trim($initials)) < 2) $initials = 'S';
$pic = $student['profile_pic'] ?? '';
$avatar_html = $initials;
if (!empty($pic)) {
    foreach (['../uploads/profile_pic/students/'.$pic,'../uploads/profile_pic/'.$pic,'../'.$pic] as $p) {
        if (file_exists($p)) {
            $avatar_html = '<img src="'.htmlspecialchars($p).'" style="width:100%;height:100%;object-fit:cover;border-radius:inherit" alt="'.htmlspecialchars($initials).'">';
            break;
        }
    }
}

/* ── Find teacher assigned to this student ── */
$stmt = $conn->prepare("SELECT DISTINCT teacher_id FROM tbl_teachers_students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$teacherRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$teacher_id = $teacherRow['teacher_id'] ?? null;

/* ── Subject filter from URL ── */
$filter_subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

$coursesMeta = [];
{
    $sql = "
        SELECT ts.teacher_subject_id, ts.subject_id, sub.subject,
               str.strand, str.track
        FROM tbl_teachers_students tts
        JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = tts.teacher_subject_id
        LEFT JOIN tblsubjects sub ON sub.subject_id = ts.subject_id
        LEFT JOIN tblstrands str ON str.strand_id = sub.strand_id
        WHERE tts.student_id = ?
        ORDER BY ts.created_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $subj = $row['subject'] ?? 'Subject';
        $s    = strtolower($subj);
        if      (strpos($s,'bio')!==false)                              $icon = '🧬';
        elseif  (strpos($s,'math')!==false||strpos($s,'algebra')!==false||strpos($s,'calc')!==false) $icon = '📐';
        elseif  (strpos($s,'history')!==false)                         $icon = '🌍';
        elseif  (strpos($s,'eng')!==false||strpos($s,'lit')!==false)   $icon = '📖';
        elseif  (strpos($s,'phys')!==false)                            $icon = '⚛️';
        elseif  (strpos($s,'chem')!==false)                            $icon = '⚗️';
        elseif  (strpos($s,'comp')!==false||strpos($s,'prog')!==false) $icon = '💻';
        else                                                             $icon = '📚';

        $banners  = ['banner-science','banner-math','banner-history','banner-english','banner-art'];
        $banner   = $banners[abs(crc32($subj)) % count($banners)];

        $coursesMeta[(int)$row['teacher_subject_id']] = [
            'title'       => htmlspecialchars($subj),
            'icon'        => $icon,
            'category'    => htmlspecialchars($row['strand'] ?? $row['track'] ?? 'Subject'),
            'bannerClass' => $banner,
            'strand'      => htmlspecialchars($row['strand'] ?? ''),
            'track'       => htmlspecialchars($row['track']  ?? ''),
        ];
    }
    $stmt->close();
}

/* ── Load lessons ── */
$allLessons = [];
if (!empty($coursesMeta)) {
    $ids     = implode(',', array_keys($coursesMeta));
    $where   = $filter_subject_id > 0
              ? "WHERE l.status = 'published' AND l.teacher_subject_id = $filter_subject_id AND l.teacher_subject_id IN ($ids)"
              : "WHERE l.status = 'published' AND l.teacher_subject_id IN ($ids)";

    $sql = "
        SELECT l.lesson_id, l.teacher_subject_id, l.title, l.description,
               l.resource_type, l.video_path, l.video_filename,
               l.status, l.order_index, l.expiration_date, l.created_at
        FROM tbl_teacher_lessons l
        $where
        ORDER BY l.created_at DESC, l.order_index ASC
    ";
    $res = $conn->query($sql);
    $num_by_subject = [];
    while ($row = $res->fetch_assoc()) {
        $tid = (int)$row['teacher_subject_id'];
        $num_by_subject[$tid] = ($num_by_subject[$tid] ?? 0) + 1;

        $rt = $row['resource_type'] ?? 'lesson';
        $typeIcon = match($rt) {
            'video'  => '🎬',
            'pdf'    => '📄',
            'image'  => '🖼️',
            'link'   => '🔗',
            default  => '📝',
        };

        $allLessons[] = [
            'id'           => (int)$row['lesson_id'],
            'courseId'     => $tid,
            'num'          => $num_by_subject[$tid],
            'name'         => htmlspecialchars($row['title']),
            'description'  => htmlspecialchars($row['description'] ?? ''),
            'resource_type'=> $rt,
            'typeIcon'     => $typeIcon,
            'video_path'    => htmlspecialchars($row['video_path'] ?? ''),
            'video_filename'=> htmlspecialchars($row['video_filename'] ?? ''),
            'file_url' => (function() use ($rt, $row) {
                if (empty($row['video_path'])) return '';
                $filename = basename($row['video_path']);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $subfolder = match(true) {
                    $rt === 'pdf'   || $ext === 'pdf'                        => 'pdfs/',
                    in_array($rt, ['ppt','pptx']) || in_array($ext, ['ppt','pptx']) => 'ppts/',
                    $rt === 'image' || in_array($ext, ['jpg','jpeg','png','gif','webp']) => 'images/',
                    $rt === 'link'                                            => '',
                    default                                                   => 'videos/',
                };
                if ($rt === 'link') return htmlspecialchars($row['video_path']);
                return htmlspecialchars('../uploads/lessons/' . $subfolder . $filename);
            })(),
            'status'       => (function() use ($row) {
                if (empty($row['expiration_date'])) return 'current';
                $tz  = new DateTimeZone('Asia/Manila');
                $now = new DateTime('now', $tz);
                $exp = new DateTime($row['expiration_date'], $tz);
                return ($exp < $now) ? 'expired' : 'current';
            })(),
            'score'        => null,
            'lastAccessed' => null,
            'topics'          => [],
            'expiration_date' => $row['expiration_date'] ? date('M d, Y', strtotime($row['expiration_date'])) : null,
        ];
    }
}

/* ── Load quizzes linked to lessons ── */
$lessonQuizMap = [];
if (!empty($allLessons)) {
    $lesson_ids = array_map(function($l){ return $l['id']; }, $allLessons);
    $lid_list   = implode(',', array_map('intval', $lesson_ids));
    $qres = $conn->query("
        SELECT quiz_id, lesson_id
        FROM tblquizzes
        WHERE lesson_id IN ($lid_list) AND status = 'published'
    ");
    if ($qres) {
        while ($qrow = $qres->fetch_assoc()) {
            $lessonQuizMap[(int)$qrow['lesson_id']] = (int)$qrow['quiz_id'];
        }
    }
}

/* Attach quiz_id to each lesson */
foreach ($allLessons as &$lesson) {
    $lesson['quiz_id'] = $lessonQuizMap[$lesson['id']] ?? null;
}
unset($lesson);

$coursesMetaJson = json_encode($coursesMeta);
$allLessonsJson  = json_encode($allLessons);

/* ── Nav badge counts ── */
$total_subjects = count($coursesMeta);

$quiz_count = (int)$conn->query("
    SELECT COUNT(DISTINCT q.quiz_id)
    FROM tblquizzes q
    JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = l.teacher_subject_id
    JOIN tblsubjects subj ON subj.subject_id = ts.subject_id
    JOIN tbl_teachers_students tts ON tts.teacher_id = ts.teacher_id
    WHERE tts.student_id = $student_id AND q.status = 'published'
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
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/student/student_lessons.css">
<style>
body, .layout {
  overflow-x: hidden;
  max-width: 100vw;
}

.main {
  overflow-x: hidden;
  min-width: 0;
}

.content {
  overflow-x: hidden;
  min-width: 0;
}
.lesson-name {
  word-break: break-word;
  overflow-wrap: break-word;
  white-space: normal;
}

.lesson-title-row {
  flex-wrap: wrap;
  gap: 4px;
}

.lesson-info {
  min-width: 0;
  flex: 1;
}
.lesson-list,
.lesson-item,
.filter-bar,
.summary-card,
#summaryRow {
  max-width: 100%;
  box-sizing: border-box;
}
@media (max-width: 768px) {
  .filter-bar {
    flex-wrap: wrap;
    gap: 6px;
  }

  .search-box {
    flex: 1 1 100%;
    min-width: 0;
  }

  .search-box input {
    width: 100%;
    font-size: 12px;
    padding: 6px 8px 6px 28px;
  }

  .filter-select {
    flex: 1 1 calc(50% - 6px);
    min-width: 0;
    font-size: 11px;
    padding: 6px 4px;
  }

  #logoutModal {
    align-items: center;
  }
}
</style>
</head>
<body>
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
    <div class="nav-section-label">Learning</div>
    <a class="nav-item" href="index.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <a class="nav-item" href="student_courses.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      My Subjects
      <span class="nav-badge"><?php echo $total_subjects; ?></span>
    </a>
    <a class="nav-item active" href="student_lessons.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Lessons
      <span class="nav-badge"><?php echo count($allLessons); ?></span>
    </a>
    <a class="nav-item" href="student_quizzes.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      My Quizzes
      <span class="nav-badge" style="background:var(--amber);color:#000"><?= $quiz_count ?></span>
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
    <div class="nav-section-label" style="margin-top:16px">Account</div>
    <a class="nav-item" href="student_profile.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      My Profile
    </a>
    <a class="nav-item" href="settings.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0A1.65 1.65 0 0 0 9 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      Settings
    </a>
    <a class="nav-item" href="about.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
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
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--cyan),var(--accent))"><?php echo $avatar_html; ?></div>
      <div class="user-info">
        <p><?php echo htmlspecialchars($student_name); ?></p>
        <span><?php echo $student_grade; ?> — <?php echo $student_section; ?></span>
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
    <div style="margin-bottom:4px">
      <div class="section-title">My Lessons</div>
      <div class="section-sub">Browse all lessons across your enrolled subjects.</div>
    </div>

    <!-- Summary Stats -->
    <div id="summaryRow"></div>

    <!-- Search & Filters -->
    <div class="filter-bar">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInput" placeholder="Search lessons..." oninput="filterLessons()">
      </div>
      <select class="filter-select" id="courseFilter" onchange="filterLessons()">
        <option value="all">All Subjects</option>
      </select>
      <select class="filter-select" id="typeFilter" onchange="filterLessons()">
        <option value="all">All Types</option>
        <option value="video">🎬 Video</option>
        <option value="pdf">📄 PDF</option>
        <option value="image">🖼️ Image</option>
        <option value="link">🔗 Link</option>
        <option value="lesson">📝 Lesson</option>
      </select>
      <select class="filter-select" id="sortSelect" onchange="filterLessons()">
        <option value="default">Sort: Default</option>
        <option value="course">Group by Subject</option>
        <option value="name-asc">Name: A → Z</option>
        <option value="name-desc">Name: Z → A</option>
      </select>
    </div>

    <!-- Status Tabs -->
    <div class="status-tabs" id="statusTabs"></div>

    <!-- Lessons List -->
    <div id="lessonsContainer"></div>
  </div>
</main>
</div>

<!-- Lesson Detail Modal -->
<div class="modal-backdrop" id="modalBackdrop" onclick="closeModalOutside(event)">
  <div class="modal" id="lessonModal">
    <div class="modal-header-banner" id="modalBanner">
      <div class="banner-overlay"></div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body" id="modalBody"></div>
  </div>
</div>

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
<!-- Logout Modal -->
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

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<script>
/* ── PHP → JS ── */
var coursesMeta = <?php echo $coursesMetaJson; ?>;
var allLessons  = <?php echo $allLessonsJson; ?>;
var preFilterSubject = <?php echo $filter_subject_id > 0 ? $filter_subject_id : 'null'; ?>;

var activeStatus = "all";
var statusOptions = [
  {key:"all",     label:"All Lessons"},
  {key:"current", label:"📖 Available"},
  {key:"done",    label:"✅ Completed"},
  {key:"locked",  label:"🔒 Not Available"},
  {key:"expired", label:"⏰ Expired"}
];

/* ── Summary ── */
function computeStats(){
  var total   = allLessons.length;
  var done    = allLessons.filter(function(l){return l.status==='done'}).length;
  var current = allLessons.filter(function(l){return l.status==='current'}).length;
  var locked  = allLessons.filter(function(l){return l.status==='locked'}).length;
  var expired = allLessons.filter(function(l){return l.status==='expired'}).length;
  return {total:total, done:done, current:current, locked:locked, expired:expired};
}

function renderSummary(){
  var s = computeStats();
  var el = document.getElementById('summaryRow');
  el.innerHTML =
    '<div class="summary-card">'+
      '<div class="summary-icon" style="background:var(--accent-glow)">📚</div>'+
      '<div><div class="summary-value">'+s.total+'</div><div class="summary-label">Total Lessons</div></div>'+
    '</div>'+
    '<div class="summary-card">'+
      '<div class="summary-icon" style="background:var(--green-bg)">📖</div>'+
      '<div><div class="summary-value">'+s.current+'</div><div class="summary-label">Available</div></div>'+
    '</div>'+
    '<div class="summary-card">'+
      '<div class="summary-icon" style="background:var(--purple-bg)">🗂️</div>'+
      '<div><div class="summary-value">'+Object.keys(coursesMeta).length+'</div><div class="summary-label">Subjects</div></div>'+
    '</div>';
}

/* ── Course filter dropdown ── */
function renderCourseFilter(){
  var sel = document.getElementById('courseFilter');
  sel.innerHTML = '<option value="all">All Subjects</option>';
  Object.keys(coursesMeta).forEach(function(id){
    var c = coursesMeta[id];
    var opt = document.createElement('option');
    opt.value = id;
    opt.textContent = c.icon + ' ' + c.title;
    if (preFilterSubject && preFilterSubject == id) opt.selected = true;
    sel.appendChild(opt);
  });
}

/* ── Status tabs ── */
function renderStatusTabs(){
  var s = computeStats();
  var el = document.getElementById('statusTabs');
  el.innerHTML = statusOptions.map(function(o){
    var count = o.key==='all' ? s.total : (s[o.key] || 0);
    return '<button class="status-tab'+(activeStatus===o.key?' active':'')+'" onclick="setStatus(\''+o.key+'\')">'+o.label+' ('+count+')</button>';
  }).join('');
}

function setStatus(st){
  activeStatus = st;
  renderStatusTabs();
  filterLessons();
}

/* ── Filter & render ── */
function filterLessons(){
  var query     = document.getElementById('searchInput').value.toLowerCase();
  var courseVal = document.getElementById('courseFilter').value;
  var typeVal   = document.getElementById('typeFilter').value;
  var sort      = document.getElementById('sortSelect').value;

  var filtered = allLessons.filter(function(l){
    var matchStatus = activeStatus === 'all' || l.status === activeStatus;
    var matchCourse = courseVal === 'all' || l.courseId == courseVal;
    var matchType   = typeVal === 'all' || l.resource_type === typeVal;
    var cTitle      = coursesMeta[l.courseId] ? coursesMeta[l.courseId].title.toLowerCase() : '';
    var matchSearch = l.name.toLowerCase().indexOf(query) !== -1 || cTitle.indexOf(query) !== -1;
    return matchStatus && matchCourse && matchType && matchSearch;
  });

  switch(sort){
    case 'name-asc':  filtered.sort(function(a,b){return a.name.localeCompare(b.name)}); break;
    case 'name-desc': filtered.sort(function(a,b){return b.name.localeCompare(a.name)}); break;
    case 'course':    filtered.sort(function(a,b){return a.courseId-b.courseId || a.num-b.num}); break;
  }

  renderLessons(filtered, sort === 'course');
}

function renderLessons(list, groupByCourse){
  var el = document.getElementById('lessonsContainer');
  if(!list.length){
    el.innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><p>No lessons found</p><span>Try adjusting your filters or search query.</span></div>';
    return;
  }

  if(groupByCourse){
    var html = '';
    var grouped = {};
    list.forEach(function(l){
      if(!grouped[l.courseId]) grouped[l.courseId] = [];
      grouped[l.courseId].push(l);
    });
    Object.keys(grouped).forEach(function(cid){
      var c = coursesMeta[cid];
      if(!c) return;
      var lessons = grouped[cid];
      html += '<div class="course-divider">'+
        '<span class="course-divider-label"><span class="course-divider-icon">'+c.icon+'</span>'+c.title+'</span>'+
        '<span style="font-size:12px;color:var(--text3);font-weight:500;white-space:nowrap">'+lessons.length+' lessons</span>'+
        '<div class="course-divider-line"></div>'+
      '</div>';
      html += '<div class="lesson-list">';
      lessons.forEach(function(l){ html += buildLessonItem(l); });
      html += '</div>';
    });
    el.innerHTML = html;
  } else {
    el.innerHTML = '<div class="lesson-list">' + list.map(function(l){ return buildLessonItem(l); }).join('') + '</div>';
  }
}

function buildLessonItem(l){
  var c = coursesMeta[l.courseId] || {icon:'📚', title:'Subject', category:'Subject', bannerClass:''};
  var numClass   = l.status === 'done' ? 'done' : l.status === 'current' ? 'current' : 'locked';
  var numContent = l.status === 'done' ? '✓' : l.num;
  var isCurrent  = l.status === 'current';
  var itemClass  = isCurrent ? 'lesson-item current-lesson' : 'lesson-item';
  var isExpired  = l.status === 'expired';

  var actionBtn = '';
  if(l.status === 'done'){
    actionBtn = '<button class="lesson-action-btn" onclick="event.stopPropagation();openModal('+l.id+')" title="Review lesson">🔄</button>';
  } else if(l.status === 'current'){
    actionBtn = '<button class="lesson-action-btn play-btn" onclick="event.stopPropagation();openModal('+l.id+')" title="View lesson">▶</button>';
  } else if(isExpired){
    actionBtn = '<button class="lesson-action-btn locked-btn" title="Lesson Expired" disabled>⏰</button>';
  } else {
    actionBtn = '<button class="lesson-action-btn locked-btn" title="Not Available">🔒</button>';
  }

  var currentTag = isCurrent ? '<span class="lesson-current-tag">Available</span>' : '';
  var expiredTag = isExpired ? '<span class="lesson-current-tag" style="background:rgba(239,68,68,0.15);color:#ef4444;border:1px solid rgba(239,68,68,0.3);">Expired</span>' : '';
  var metaOpacity = (l.status === 'locked' || isExpired) ? 'opacity:.5' : '';
  var typeLabel = l.typeIcon + ' ' + l.resource_type;

  // Description preview (truncate to 80 chars)
  var descHTML = '';
  if (l.description && l.description.trim()) {
    var shortDesc = l.description.length > 80 ? l.description.substring(0, 80) + '…' : l.description;
    descHTML = '<div style="font-size:11px;color:var(--text3);margin-top:3px;line-height:1.4;'+metaOpacity+'">' + shortDesc + '</div>';
  }

  // Expiration / due date badge
  var dateHTML = '';
  if (l.expiration_date) {
    var expLabel = isExpired ? '⏰ Expired: ' : '📅 Due: ';
    var expColor = isExpired ? '#ef4444' : 'var(--amber)';
    dateHTML = '<span class="lesson-meta-item" style="color:'+expColor+';font-weight:500;">'+expLabel+l.expiration_date+'</span>';
  }

  return '<div class="'+itemClass+'"'+((l.status==='locked'||isExpired) ? '' : ' onclick="openModal('+l.id+')"')+'>'+
    '<div class="lesson-number '+numClass+'">'+numContent+'</div>'+
    '<div class="lesson-info">'+
      '<div class="lesson-title-row">'+
        '<span class="lesson-name" style="'+(l.status==='locked'||isExpired?'color:var(--text3)':'')+'">'+l.name+'</span>'+
        currentTag+expiredTag+
      '</div>'+
      descHTML+
      '<div class="lesson-meta-row" style="'+metaOpacity+';flex-wrap:wrap;gap:4px;">'+
        '<span class="lesson-meta-item"><span class="lesson-course-tag">'+c.icon+' '+c.title+'</span></span>'+
        '<span class="lesson-meta-item">'+typeLabel+'</span>'+
        dateHTML+
      '</div>'+
    '</div>'+
    '<div class="lesson-right">'+
      '<span class="lesson-duration-badge" style="'+metaOpacity+'">L'+l.num+'</span>'+
      actionBtn+
    '</div>'+
  '</div>';
}

/* ── Build media HTML for modal ── */
function buildMediaHTML(l) {
  var fileUrl = l.file_url || '';
  var rt = l.resource_type;

  if (rt === 'video' && fileUrl) {
    return '<div style="margin:16px 0;border-radius:10px;overflow:hidden;background:#000">'+
      '<video controls style="width:100%;max-height:300px;display:block" src="'+fileUrl+'">'+
        'Your browser does not support video.'+
      '</video>'+
    '</div>';
  }

if (rt === 'pdf' && fileUrl) {
    var filename = fileUrl.split('/').pop().split('?')[0];
    var proxyUrl = 'pdf_viewer.php?file=' + encodeURIComponent(filename);
    var isAndroid = /Android/.test(navigator.userAgent);
    var isMobile = window.innerWidth <= 768;
    var iframeHeight = isMobile ? Math.floor(window.innerHeight * 0.62) + 'px' : '540px';

    // Android Chrome blocks inline PDF even with correct headers — use Google Docs viewer
    var absProxyUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/') + proxyUrl;
    var viewerSrc = isAndroid
      ? 'https://docs.google.com/gviewer?embedded=true&url=' + encodeURIComponent(absProxyUrl)
      : proxyUrl;

    return '<div style="margin:16px 0;border-radius:10px;overflow:hidden;border:1px solid var(--border)">'+
      '<iframe src="'+viewerSrc+'" '+
        'style="width:100%;height:'+iframeHeight+';border:none;display:block" '+
        'title="'+filename+'" allow="fullscreen">'+
      '</iframe>'+
      '<div style="padding:10px 14px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:8px;">'+
        '<span style="font-size:11px;color:var(--text3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;">📄 '+filename+'</span>'+
        '<a href="'+proxyUrl+'" target="_blank" style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:500;flex-shrink:0;">📥 Open PDF</a>'+
      '</div>'+
    '</div>';
  }

  if (rt === 'image' && fileUrl) {
    return '<div style="margin:16px 0;border-radius:10px;overflow:hidden;text-align:center;background:var(--bg3)">'+
      '<img src="'+fileUrl+'" style="max-width:100%;max-height:400px;object-fit:contain;border-radius:10px" alt="'+l.name+'">'+
    '</div>';
  }

  if ((rt === 'ppt' || rt === 'pptx') && fileUrl) {
    var absUrl = window.location.origin + '/' + fileUrl.replace(/^(\.\.\/)+/, '');
    return '<div style="margin:16px 0;border-radius:10px;overflow:hidden;border:1px solid var(--border)">'+
      '<iframe src="https://docs.google.com/gviewer?embedded=true&url='+encodeURIComponent(absUrl)+'" '+
        'style="width:100%;height:480px;border:none;display:block" title="'+l.name+'"></iframe>'+
    '</div>';
  }

  if (rt === 'link' && l.video_path) {
    var youtubeMatch = l.video_path.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{11})/);
    if (youtubeMatch) {
      return '<div style="margin:16px 0;border-radius:10px;overflow:hidden;border:1px solid var(--border);position:relative;padding-bottom:56.25%;height:0">'+
        '<iframe src="https://www.youtube.com/embed/'+youtubeMatch[1]+'" '+
          'style="position:absolute;top:0;left:0;width:100%;height:100%;border:none" '+
          'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen title="'+l.name+'"></iframe>'+
      '</div>'+
      '<div style="margin-top:8px">'+
        '<a href="'+l.video_path+'" target="_blank" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px">🔗 Open on YouTube</a>'+
      '</div>';
    }
    return '<div style="margin:16px 0;border-radius:10px;overflow:hidden;border:1px solid var(--border)">'+
      '<iframe src="'+l.video_path+'" style="width:100%;height:420px;border:none;display:block" title="'+l.name+'" sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>'+
    '</div>'+
    '<div style="margin-top:8px">'+
      '<a href="'+l.video_path+'" target="_blank" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px">🔗 Open Link</a>'+
    '</div>';
  }

  if (fileUrl) {
    var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0];
    if (['doc','docx','xls','xlsx','ppt','pptx'].indexOf(ext) !== -1) {
      var absFileUrl = window.location.origin + '/' + fileUrl.replace(/^(\.\.\/)+/, '');
      return '<div style="margin:16px 0;border-radius:10px;overflow:hidden;border:1px solid var(--border)">'+
        '<iframe src="https://docs.google.com/gviewer?embedded=true&url='+encodeURIComponent(absFileUrl)+'" '+
          'style="width:100%;height:480px;border:none;display:block" title="'+l.name+'"></iframe>'+
      '</div>';
    }
    if (['txt','html','htm','md','csv'].indexOf(ext) !== -1) {
      return '<div style="margin:16px 0;border-radius:10px;overflow:hidden;border:1px solid var(--border)">'+
        '<iframe src="'+fileUrl+'" style="width:100%;height:400px;border:none;display:block;background:#fff" title="'+l.name+'"></iframe>'+
      '</div>';
    }
    if (['mp3','wav','ogg','aac','m4a'].indexOf(ext) !== -1) {
      return '<div style="margin:16px 0;padding:16px;border-radius:10px;border:1px solid var(--border);background:var(--bg3)">'+
        '<audio controls style="width:100%" src="'+fileUrl+'">Your browser does not support audio.</audio>'+
      '</div>';
    }
    return '<div style="margin:16px 0;border-radius:10px;overflow:hidden;border:1px solid var(--border)">'+
      '<iframe src="'+fileUrl+'" style="width:100%;height:420px;border:none;display:block" title="'+l.name+'"></iframe>'+
    '</div>';
  }

  return '';
}

/* ── Modal ── */
function openModal(id){
  var l = allLessons.find(function(x){return x.id===id});
  if(!l || l.status === 'locked' || l.status === 'expired') return;
  var c = coursesMeta[l.courseId] || {icon:'📚', title:'Subject', bannerClass:''};

  var banner = document.getElementById('modalBanner');
  banner.className = 'modal-header-banner ' + c.bannerClass;

  var statusBadge = l.status === 'done'
    ? '<span class="badge badge-green">✅ Completed</span>'
    : l.status === 'locked'
    ? '<span class="badge badge-amber">🔒 Not Available</span>'
    : '<span class="badge badge-blue">📖 Available</span>';

  var typeIcon = l.typeIcon || '📝';

  var mediaHTML = buildMediaHTML(l);

  var sameCourseLessons = allLessons.filter(function(x){return x.courseId===l.courseId});
  var idx        = sameCourseLessons.findIndex(function(x){return x.id===l.id});
  var prevLesson = idx > 0 ? sameCourseLessons[idx-1] : null;
  var nextLesson = idx < sameCourseLessons.length-1 ? sameCourseLessons[idx+1] : null;
  var prevDisabled = !prevLesson ? ' disabled' : '';
  var nextDisabled = !nextLesson ? ' disabled' : '';

  var quizBtnHTML = '';
  if (l.quiz_id) {
    quizBtnHTML =
      '<div style="margin:16px 0 4px;padding:14px 16px;border-radius:10px;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);display:flex;align-items:center;justify-content:space-between;gap:12px;">'+
        '<div>'+
          '<div style="font-weight:600;font-size:0.9rem;color:var(--green)">📝 Quiz Available</div>'+
          '<div style="font-size:0.78rem;color:var(--text3);margin-top:2px;">Test your understanding of this lesson.</div>'+
        '</div>'+
        '<a href="student_quizzes.php" style="text-decoration:none;">'+
          '<button class="btn btn-primary btn-sm" style="white-space:nowrap;">Take Quiz →</button>'+
        '</a>'+
      '</div>';
  }

  document.getElementById('modalBody').innerHTML =
    '<div class="modal-lesson-title">'+l.name+'</div>'+
    '<div class="modal-lesson-meta">'+
      '<span class="modal-lesson-meta-item">'+c.icon+' '+c.title+'</span>'+
      '<span class="modal-lesson-meta-item">📚 Lesson '+l.num+'</span>'+
      '<span class="modal-lesson-meta-item">'+typeIcon+' '+l.resource_type+'</span>'+
      '<span style="margin-left:auto">'+statusBadge+'</span>'+
    '</div>'+
    (l.description ? '<div class="modal-desc">'+l.description+'</div>' : '')+
    mediaHTML+
    quizBtnHTML+
    '<div class="modal-nav-row">'+
      '<button class="modal-nav-btn" onclick="openModal('+(prevLesson?prevLesson.id:0)+')"'+prevDisabled+'>'+
        '← '+(prevLesson ? prevLesson.name : 'No previous')+
      '</button>'+
      '<button class="modal-nav-btn" onclick="closeModal()">Close</button>'+
      '<button class="modal-nav-btn" onclick="openModal('+(nextLesson?nextLesson.id:0)+')"'+nextDisabled+'>'+
        (nextLesson ? nextLesson.name : 'No next')+' →'+
      '</button>'+
    '</div>';

  document.getElementById('modalBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal(){
  document.getElementById('modalBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}
function closeModalOutside(e){
  if(e.target === document.getElementById('modalBackdrop')) closeModal();
}
document.addEventListener('keydown',function(e){if(e.key==='Escape') closeModal()});

/* ── Logout ── */
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

/* ── Sidebar ── */
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('open');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}

/* ── Theme ── */
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

/* ── Toast ── */
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

/* ── Init ── */
renderCourseFilter();
renderStatusTabs();
filterLessons();

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
</script>
</body>
</html>