<?php
date_default_timezone_set('Asia/Manila');

$logFile = __DIR__ . "/worker.log";
error_log("ExceLearn Worker path: " . __DIR__);
if (file_exists($logFile) && filesize($logFile) > (5 * 1024 * 1024)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -100);
    file_put_contents($logFile, implode("", $lastLines));
}

function logMessage($msg) {
    global $logFile;
    file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n", FILE_APPEND);
}

include __DIR__ . '/../connection.php';
$conn->set_charset("utf8mb4");

$envPath = __DIR__ . '/../.env';
$env = [];
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $env[$k] = trim($v, "\"'");
    }
}

$ONESIGNAL_APP_ID       = $env['ONESIGNAL_APP_ID'] ?? (getenv('ONESIGNAL_APP_ID') ?: '');
$ONESIGNAL_REST_API_KEY = $env['ONESIGNAL_REST_API_KEY'] ?? (getenv('ONESIGNAL_REST_API_KEY') ?: '');

function sendNotificationDirect($user_ids, $type, $body, $title) {
    global $conn, $ONESIGNAL_APP_ID, $ONESIGNAL_REST_API_KEY;

    if (!is_array($user_ids)) $user_ids = [$user_ids];

    $inserted_ids = [];

    foreach ($user_ids as $user_id) {
        // Check if this exact notification was already sent to this user
        $chk = $conn->prepare("
            SELECT COUNT(*) as cnt FROM tblnotifications
            WHERE user_id = ? AND type = ? AND message = ?
            LIMIT 1
        ");
        $chk->bind_param("iss", $user_id, $type, $body);
        $chk->execute();
        $cnt = (int)$chk->get_result()->fetch_assoc()['cnt'];
        $chk->close();

        if ($cnt > 0) {
            logMessage("Skipped duplicate announcement for user_id={$user_id}");
            continue;
        }

        $ins = $conn->prepare("
            INSERT INTO tblnotifications (user_id, title, message, type, status, created_at)
            VALUES (?, ?, ?, ?, 'unread', NOW())
        ");
        if (!$ins) {
            logMessage("Prepare failed INSERT: " . $conn->error);
            continue;
        }
        $ins->bind_param("isss", $user_id, $title, $body, $type);
        $ok = $ins->execute();
        logMessage("DB insert user_id={$user_id} result=" . ($ok ? 'OK' : $ins->error));
        $ins->close();

        if ($ok) $inserted_ids[] = $user_id;
    }

    // Only push OneSignal for newly inserted users
    if (empty($inserted_ids)) {
        logMessage("No new users to push for this announcement.");
        return;
    }

    $filters = [];
    foreach ($inserted_ids as $idx => $uid) {
        if ($idx > 0) $filters[] = ['operator' => 'OR'];
        $filters[] = ['field' => 'tag', 'key' => 'user_id', 'relation' => '=', 'value' => (string)$uid];
    }

    $payload = [
        'app_id'   => $ONESIGNAL_APP_ID,
        'headings' => ['en' => $title],
        'contents' => ['en' => $body],
        'data'     => ['type' => $type],
        'filters'  => $filters,
    ];

    $ch = curl_init('https://onesignal.com/api/v1/notifications');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => [
            'Authorization: Key ' . $ONESIGNAL_REST_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $os_response = curl_exec($ch);
    $curl_error  = curl_error($ch);
    curl_close($ch);
    logMessage("OneSignal push sent to " . count($inserted_ids) . " users. Response: " . ($os_response ?: 'empty') . ($curl_error ? " | curl_error: $curl_error" : ''));
}
function sendNotification($user_ids, $type, $extra = []) {
    global $conn, $ONESIGNAL_APP_ID, $ONESIGNAL_REST_API_KEY;

    $lesson_title  = $extra['lesson_title']  ?? null;
    $quiz_title    = $extra['quiz_title']     ?? null;
    $subject_name  = $extra['subject_name']  ?? null;
    $due_date      = $extra['due_date']       ?? null;
    $passing_score = $extra['passing_score'] ?? null;
    $announcement  = $extra['announcement']  ?? null;
    $expiry_date   = $extra['expiry_date']   ?? null;
    $score         = $extra['score']          ?? null;
    $student_name  = $extra['student_name']  ?? null;

    switch ($type) {
        case 'new_subject_assigned':
            $teacher_name  = $extra['teacher_name'] ?? 'Teacher';
            $strand_info   = $extra['strand'] ?? '';
            $track_info    = $extra['track'] ?? '';
            $grade_info    = $extra['grade_level'] ?? '';
            $strand_label  = trim("{$grade_info} — {$strand_info}" . ($track_info ? " ({$track_info})" : ''));
            $title = "New Subject Assigned";
            $body  = "Hi {$teacher_name}, you have been assigned a new subject: {$subject_name}" 
                   . ($strand_label ? " under {$strand_label}" : "") 
                   . ". Assigned by the Administrator. Please set up your lessons.";
            break;
        case 'new_lesson':
            $teacher_name  = $extra['teacher_name'] ?? 'Your Teacher';
            $subject_info  = $extra['subject_name'] ?? '';
            $strand_info   = $extra['strand'] ?? '';
            $track_info    = $extra['track'] ?? '';
            $grade_info    = $extra['grade_level'] ?? '';
            $strand_label  = trim("{$grade_info} — {$strand_info}" . ($track_info ? " ({$track_info})" : ''));
            $title = "New Lesson Available";
            $body  = "A new lesson \"{$lesson_title}\" has been added"
                   . ($subject_info ? " for {$subject_info}" : "")
                   . ($strand_label ? " ({$strand_label})" : "")
                   . " by {$teacher_name}. Check it out now!";
            break;
        case 'lesson_expiring':
            $title = "Lesson Expiring Soon";
            $body  = "The lesson \"{$lesson_title}\" will expire on {$expiry_date}. Complete it before it's gone!";
            break;
        case 'lesson_expired':
            $title = "Lesson Expired";
            $body  = "The lesson \"{$lesson_title}\" has already expired as of {$expiry_date}.";
            break;
        case 'new_quiz':
            $teacher_name  = $extra['teacher_name'] ?? 'Your Teacher';
            $subject_info  = $extra['subject_name'] ?? '';
            $strand_info   = $extra['strand'] ?? '';
            $track_info    = $extra['track'] ?? '';
            $grade_info    = $extra['grade_level'] ?? '';
            $strand_label  = trim("{$grade_info} — {$strand_info}" . ($track_info ? " ({$track_info})" : ''));
            $title = "New Quiz Available";
            $body  = "A new quiz \"{$quiz_title}\" has been posted"
                   . ($subject_info ? " for {$subject_info}" : "")
                   . ($strand_label ? " ({$strand_label})" : "")
                   . " by {$teacher_name}. Due: {$due_date}.";
            break;
        case 'quiz_due_soon':
            $title = "Quiz Due Tomorrow!";
            $body  = "Reminder: \"{$quiz_title}\" is due tomorrow ({$due_date}). Don't forget to take it!";
            break;
        case 'quiz_overdue':
            $title = "Quiz Overdue";
            $body  = "The quiz \"{$quiz_title}\" was due on {$due_date} and is now overdue.";
            break;
        case 'quiz_passed':
            $title = "Quiz Passed! 🎉";
            $body  = "Congratulations! You passed \"{$quiz_title}\" with a score of {$score}. Keep it up!";
            break;
        case 'quiz_failed':
            $title = "Quiz Not Passed";
            $body  = "You scored {$score} on \"{$quiz_title}\". Passing score is {$passing_score}. Try again!";
            break;
        case 'student_submitted_quiz':
            $title = "Student Quiz Submission";
            $body  = "{$student_name} has submitted the quiz \"{$quiz_title}\" with a score of {$score}.";
            break;
        case 'new_announcement':
            $title = "New Announcement";
            $body  = $announcement;
            break;
        default:
            $title = "ExceLearn Notification";
            $body  = "You have a new notification.";
    }

    $cooldowns = [
        'new_subject_assigned'   => 1 * 3600,
        'new_lesson'             => 1 * 3600,
        'lesson_expiring'        => 12 * 3600,
        'lesson_expired'         => 24 * 3600,
        'new_quiz'               => 1 * 3600,
        'quiz_due_soon'          => 12 * 3600,
        'quiz_overdue'           => 24 * 3600,
        'quiz_passed'            => 5 * 60,
        'quiz_failed'            => 5 * 60,
        'student_submitted_quiz' => 5 * 60,
        'new_announcement'       => 5 * 60,
        'default'                => 10 * 60,
    ];
    $cooldown = $cooldowns[$type] ?? $cooldowns['default'];

    if (!is_array($user_ids)) $user_ids = [$user_ids];

    foreach ($user_ids as $user_id) {
        $lastTime   = null;
        $stmtCheck  = $conn->prepare("
            SELECT created_at FROM tblnotifications
            WHERE user_id=? AND type=? AND message=?
            ORDER BY created_at DESC LIMIT 1
        ");
        if ($stmtCheck) {
            $stmtCheck->bind_param("iss", $user_id, $type, $body);
            $stmtCheck->execute();
            $row      = $stmtCheck->get_result()->fetch_assoc();
            $lastTime = $row['created_at'] ?? null;
            $stmtCheck->close();
        } else {
            logMessage("Prepare failed for cooldown check: " . $conn->error);
        }

        logMessage("Cooldown check user_id={$user_id} type={$type} lastTime=" . ($lastTime ?? 'none'));

        if ($lastTime && (time() - strtotime($lastTime)) < $cooldown) {
            logMessage("Skipped spam '{$type}' for user_id {$user_id}");
            continue;
        }

        $stmtIns = $conn->prepare("
            INSERT INTO tblnotifications (user_id, title, message, type, status, created_at)
            VALUES (?, ?, ?, ?, 'unread', NOW())
        ");
        if (!$stmtIns) {
            logMessage("Prepare failed for INSERT: " . $conn->error);
            continue;
        }
        $stmtIns->bind_param("isss", $user_id, $title, $body, $type);
        $exec_result = $stmtIns->execute();
        logMessage("DB insert user_id={$user_id} result=" . ($exec_result ? 'OK' : $stmtIns->error));
        if (!$exec_result) {
            logMessage("MYSQL ERROR: " . $conn->error . " | errno: " . $conn->errno);
        }
        $stmtIns->close();
    }

    // Send OneSignal push
    $filters = [];
    foreach ($user_ids as $idx => $uid) {
        if ($idx > 0) $filters[] = ['operator' => 'OR'];
        $filters[] = ['field' => 'tag', 'key' => 'user_id', 'relation' => '=', 'value' => (string)$uid];
    }

    $payload = [
        'app_id'   => $ONESIGNAL_APP_ID,
        'headings' => ['en' => $title],
        'contents' => ['en' => $body],
        'data'     => ['type' => $type],
        'filters'  => $filters,
    ];

    $ch = curl_init('https://onesignal.com/api/v1/notifications');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => [
            'Authorization: Key ' . $ONESIGNAL_REST_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $os_response = curl_exec($ch);
    $curl_error  = curl_error($ch);
    curl_close($ch);
    logMessage("OneSignal response: " . ($os_response ?: 'empty') . ($curl_error ? " | curl_error: $curl_error" : ''));
    logMessage("Notification '{$type}' sent to user_ids: " . implode(',', $user_ids));
    return true;
}

// ── Helper: fetch students for a given teacher_subject_id ──────────────────
function getStudentIds($conn, $teacher_subject_id) {
    $stmt = $conn->prepare("
        SELECT DISTINCT s.id
        FROM tbl_teacher_subjects tsubj
        INNER JOIN tbl_teachers_students tts ON tts.teacher_id = tsubj.teacher_id
        INNER JOIN tblstudents s ON s.id = tts.student_id
        WHERE tsubj.teacher_subject_id = ?
          AND s.status = 'active'
    ");
    if (!$stmt) return [];
    $stmt->bind_param("i", $teacher_subject_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return array_column($rows, 'id');
}

// ── Helper: fetch students who haven't taken a quiz yet ────────────────────
function getStudentIdsNotTaken($conn, $teacher_subject_id, $quiz_id) {
    $stmt = $conn->prepare("
        SELECT DISTINCT s.id
        FROM tbl_teacher_subjects tsubj
        INNER JOIN tbl_teachers_students tts ON tts.teacher_id = tsubj.teacher_id
        INNER JOIN tblstudents s ON s.id = tts.student_id
        WHERE tsubj.teacher_subject_id = ?
          AND tts.student_id NOT IN (
              SELECT qr.student_id FROM tblquiz_results qr WHERE qr.quizzes_id = ?
          )
          AND s.status = 'active'
    ");
    if (!$stmt) return [];
    $stmt->bind_param("ii", $teacher_subject_id, $quiz_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return array_column($rows, 'id');
}

// ── Helper: get teacher_subject_id from lesson_id ──────────────────────────
function getTeacherSubjectId($conn, $lesson_id) {
    $stmt = $conn->prepare("
        SELECT teacher_subject_id FROM tbl_teacher_lessons WHERE lesson_id=? LIMIT 1
    ");
    if (!$stmt) return null;
    $stmt->bind_param("i", $lesson_id);
    $stmt->execute();
    $stmt->bind_result($tsid);
    $stmt->fetch();
    $stmt->close();
    return $tsid;
}

logMessage("ExceLearn Worker started");

$task = $_GET['task'] ?? $_POST['task'] ?? 'all';
logMessage("Task: {$task}");

if ($task === 'all' || $task === 'subjects') {
// ── New subject assignments ────────────────────────────────────────────────
logMessage("Checking new subject assignments...");
$res = $conn->query("
    SELECT 
        ts.teacher_subject_id, 
        ts.teacher_id, 
        ts.subject_id, 
        sub.subject,
        st.strand,
        st.track,
        g.grade_level,
        CONCAT(u.firstname, ' ', u.lastname) AS teacher_name
    FROM tbl_teacher_subjects ts
    LEFT JOIN tblsubjects sub ON sub.subject_id = ts.subject_id
    LEFT JOIN tblstrands st ON st.strand_id = sub.strand_id
    LEFT JOIN tblgrade_levels g ON g.grade_id = st.grade_level_id
    LEFT JOIN tblusers u ON u.id = ts.teacher_id
    WHERE ts.created_at >= NOW() - INTERVAL 5 MINUTE
");
$newSubjects = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->free();

foreach ($newSubjects as $row) {
    sendNotification(
        [$row['teacher_id']],
        'new_subject_assigned',
        [
            'subject_name' => $row['subject'] ?? 'Unknown Subject',
            'teacher_name' => $row['teacher_name'] ?? 'Unknown Teacher',
            'strand'       => $row['strand'] ?? '',
            'track'        => $row['track'] ?? '',
            'grade_level'  => $row['grade_level'] ?? '',
        ]
    );
}
} // end subjects

if ($task === 'all' || $task === 'lessons') {
// ── New lessons ───────────────────────────────────────────────────────────
logMessage("Checking new lessons...");
$res = $conn->query("
    SELECT 
        l.lesson_id, 
        l.teacher_subject_id, 
        l.title, 
        l.expiration_date,
        sub.subject,
        st.strand,
        st.track,
        g.grade_level,
        CONCAT(u.firstname, ' ', u.lastname) AS teacher_name
    FROM tbl_teacher_lessons l
    LEFT JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = l.teacher_subject_id
    LEFT JOIN tblsubjects sub ON sub.subject_id = ts.subject_id
    LEFT JOIN tblstrands st ON st.strand_id = sub.strand_id
    LEFT JOIN tblgrade_levels g ON g.grade_id = st.grade_level_id
    LEFT JOIN tblusers u ON u.id = ts.teacher_id
    WHERE l.created_at >= NOW() - INTERVAL 5 MINUTE
      AND l.status = 'published'
");
if (!$res) {
    logMessage("Query error (new lessons): " . $conn->error);
} else {
    $newLessons = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
    logMessage("New lessons found: " . count($newLessons));
    foreach ($newLessons as $lesson) {
        logMessage("Processing lesson_id={$lesson['lesson_id']} tsid={$lesson['teacher_subject_id']}");
        $student_ids = getStudentIds($conn, $lesson['teacher_subject_id']);
        logMessage("Students found for lesson {$lesson['lesson_id']}: " . count($student_ids) . " — IDs: " . implode(',', $student_ids));
        if (!empty($student_ids)) {
            sendNotification($student_ids, 'new_lesson', [
                'lesson_title' => $lesson['title'],
                'teacher_name' => $lesson['teacher_name'] ?? 'Your Teacher',
                'subject_name' => $lesson['subject'] ?? '',
                'strand'       => $lesson['strand'] ?? '',
                'track'        => $lesson['track'] ?? '',
                'grade_level'  => $lesson['grade_level'] ?? '',
            ]);
        }
    }
}

// ── Expiring lessons ──────────────────────────────────────────────────────
logMessage("Checking expiring lessons...");
$res = $conn->query("
    SELECT l.lesson_id, l.teacher_subject_id, l.title, l.expiration_date
    FROM tbl_teacher_lessons l
    WHERE l.expiration_date IS NOT NULL
      AND DATE(l.expiration_date) = DATE(NOW() + INTERVAL 1 DAY)
      AND l.status = 'published'
");
$expiringLessons = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->free();

foreach ($expiringLessons as $lesson) {
    $student_ids = getStudentIds($conn, $lesson['teacher_subject_id']);
    if (!empty($student_ids)) {
        sendNotification($student_ids, 'lesson_expiring', [
            'lesson_title' => $lesson['title'],
            'expiry_date'  => date('M d, Y', strtotime($lesson['expiration_date'])),
        ]);
    }
}

// ── Expired lessons ───────────────────────────────────────────────────────
logMessage("Checking expired lessons...");
$res = $conn->query("
    SELECT l.lesson_id, l.teacher_subject_id, l.title, l.expiration_date
    FROM tbl_teacher_lessons l
    WHERE l.expiration_date IS NOT NULL
      AND l.expiration_date < NOW()
      AND l.status = 'published'
");
$expiredLessons = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->free();

foreach ($expiredLessons as $lesson) {
    $student_ids = getStudentIds($conn, $lesson['teacher_subject_id']);
    if (!empty($student_ids)) {
        sendNotification($student_ids, 'lesson_expired', [
            'lesson_title' => $lesson['title'],
            'expiry_date'  => date('M d, Y', strtotime($lesson['expiration_date'])),
        ]);
    }
}
} // end lessons

if ($task === 'all' || $task === 'quizzes') {
// ── New quizzes ───────────────────────────────────────────────────────────
logMessage("Checking new quizzes...");
$res = $conn->query("
    SELECT 
        q.quiz_id, 
        q.lesson_id, 
        q.description, 
        q.due_date, 
        q.passing_score,
        sub.subject,
        st.strand,
        st.track,
        g.grade_level,
        CONCAT(u.firstname, ' ', u.lastname) AS teacher_name
    FROM tblquizzes q
    LEFT JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    LEFT JOIN tbl_teacher_subjects ts ON ts.teacher_subject_id = l.teacher_subject_id
    LEFT JOIN tblsubjects sub ON sub.subject_id = ts.subject_id
    LEFT JOIN tblstrands st ON st.strand_id = sub.strand_id
    LEFT JOIN tblgrade_levels g ON g.grade_id = st.grade_level_id
    LEFT JOIN tblusers u ON u.id = ts.teacher_id
    WHERE q.created_at >= NOW() - INTERVAL 5 MINUTE
      AND q.status = 'published'
");
$newQuizzes = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->free();

foreach ($newQuizzes as $quiz) {
    $tsid        = getTeacherSubjectId($conn, $quiz['lesson_id']);
    $student_ids = $tsid ? getStudentIds($conn, $tsid) : [];
    if (!empty($student_ids)) {
        sendNotification($student_ids, 'new_quiz', [
            'quiz_title'   => $quiz['description'],
            'due_date'     => $quiz['due_date']
                ? date('M d, Y h:i A', strtotime($quiz['due_date']))
                : 'No due date',
            'teacher_name' => $quiz['teacher_name'] ?? 'Your Teacher',
            'subject_name' => $quiz['subject'] ?? '',
            'strand'       => $quiz['strand'] ?? '',
            'track'        => $quiz['track'] ?? '',
            'grade_level'  => $quiz['grade_level'] ?? '',
        ]);
    }
}

// ── Quizzes due tomorrow ──────────────────────────────────────────────────
logMessage("Checking quizzes due tomorrow...");
$res = $conn->query("
    SELECT q.quiz_id, q.lesson_id, q.description, q.due_date
    FROM tblquizzes q
    WHERE q.due_date IS NOT NULL
      AND DATE(q.due_date) = DATE(NOW() + INTERVAL 1 DAY)
      AND q.status = 'published'
");
$dueSoonQuizzes = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->free();

foreach ($dueSoonQuizzes as $quiz) {
    $tsid        = getTeacherSubjectId($conn, $quiz['lesson_id']);
    $student_ids = $tsid ? getStudentIdsNotTaken($conn, $tsid, $quiz['quiz_id']) : [];
    if (!empty($student_ids)) {
        sendNotification($student_ids, 'quiz_due_soon', [
            'quiz_title' => $quiz['description'],
            'due_date'   => date('M d, Y', strtotime($quiz['due_date'])),
        ]);
    }
}

// ── Overdue quizzes ───────────────────────────────────────────────────────
logMessage("Checking overdue quizzes...");
$res = $conn->query("
    SELECT q.quiz_id, q.lesson_id, q.description, q.due_date
    FROM tblquizzes q
    WHERE q.due_date IS NOT NULL
      AND q.due_date < NOW()
      AND q.status = 'published'
");
$overdueQuizzes = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->free();

foreach ($overdueQuizzes as $quiz) {
    $tsid        = getTeacherSubjectId($conn, $quiz['lesson_id']);
    $student_ids = $tsid ? getStudentIdsNotTaken($conn, $tsid, $quiz['quiz_id']) : [];
    if (!empty($student_ids)) {
        sendNotification($student_ids, 'quiz_overdue', [
            'quiz_title' => $quiz['description'],
            'due_date'   => date('M d, Y', strtotime($quiz['due_date'])),
        ]);
    }
}
} // end quizzes

if ($task === 'all' || $task === 'submissions') {
// ── Recent quiz submissions ───────────────────────────────────────────────
logMessage("Checking recent quiz submissions for auto-grading...");
$res = $conn->query("
    SELECT qr.id AS result_id, qr.quizzes_id AS quiz_id, qr.student_id, qr.score,
           q.description AS quiz_title, q.passing_score, q.lesson_id
    FROM tblquiz_results qr
    INNER JOIN tblquizzes q ON q.quiz_id = qr.quizzes_id
    WHERE qr.created_at >= NOW() - INTERVAL 5 MINUTE
");
$recentSubmissions = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->free();

foreach ($recentSubmissions as $sub) {
    $passed = $sub['score'] >= $sub['passing_score'];

    sendNotification(
        [$sub['student_id']],
        $passed ? 'quiz_passed' : 'quiz_failed',
        [
            'quiz_title'    => $sub['quiz_title'],
            'score'         => $sub['score'],
            'passing_score' => $sub['passing_score'],
        ]
    );

    $stmtT = $conn->prepare("
        SELECT tsubj.teacher_id
        FROM tbl_teacher_lessons l
        INNER JOIN tbl_teacher_subjects tsubj ON tsubj.teacher_subject_id = l.teacher_subject_id
        WHERE l.lesson_id = ? LIMIT 1
    ");
    $stmtT->bind_param("i", $sub['lesson_id']);
    $stmtT->execute();
    $stmtT->bind_result($teacher_id);
    $stmtT->fetch();
    $stmtT->close();

    $stmtN = $conn->prepare("SELECT CONCAT(firstname,' ',lastname) FROM tblstudents WHERE id=? LIMIT 1");
    $stmtN->bind_param("i", $sub['student_id']);
    $stmtN->execute();
    $stmtN->bind_result($student_name);
    $stmtN->fetch();
    $stmtN->close();

    if (!empty($teacher_id)) {
        sendNotification(
            [$teacher_id],
            'student_submitted_quiz',
            [
                'quiz_title'   => $sub['quiz_title'],
                'score'        => $sub['score'],
                'student_name' => $student_name ?? 'A student',
            ]
        );
    }
}
} // end submissions

if ($task === 'all' || $task === 'announcements') {
logMessage("Checking new announcements...");
$res = $conn->query("
    SELECT a.id, a.user_id, a.title, a.message, a.type, a.category, a.created_at,
           CONCAT(u.firstname, ' ', u.lastname) AS poster_name,
           u.role AS poster_role
    FROM tblannouncements a
    LEFT JOIN tblusers u ON u.id = a.user_id
    WHERE a.created_at >= NOW() - INTERVAL 10 MINUTE
    ORDER BY a.created_at DESC
");
$announcements = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->free();

foreach ($announcements as $ann) {
    $ann_id      = (int)$ann['id'];
    $ann_body    = "{$ann['title']}: {$ann['message']}";
    $category    = $ann['category'] ?? 'both';
    $poster_uid  = (int)$ann['user_id'];
    $poster_role = strtolower($ann['poster_role'] ?? 'admin');

    logMessage("Processing announcement id={$ann_id} category={$category} poster_uid={$poster_uid}");

    // Skip if already notified for this exact announcement body
    $alreadySentCheck = $conn->prepare("
        SELECT COUNT(*) as cnt
        FROM tblnotifications
        WHERE type = 'new_announcement'
          AND message = ?
        LIMIT 1
    ");
    $alreadySentCheck->bind_param("s", $ann_body);
    $alreadySentCheck->execute();
    $alreadySentRow = $alreadySentCheck->get_result()->fetch_assoc();
    $alreadySentCheck->close();

    if ((int)$alreadySentRow['cnt'] > 0) {
        logMessage("Skipped announcement id={$ann_id} — already notified.");
        continue;
    }

    $poster_name  = trim($ann['poster_name'] ?? 'Unknown');
    $poster_label = $poster_role === 'teacher' ? "Teacher {$poster_name}" : "Admin {$poster_name}";
    $notif_title  = "📢 New Announcement from {$poster_label}";

    if ($category === 'for_student' || $category === 'both') {
        // If posted by a teacher: only notify students under that teacher
        // If posted by admin: notify all active students
        if ($poster_role === 'teacher') {
            $stmtS = $conn->prepare("
                SELECT DISTINCT ts.student_id
                FROM tbl_teachers_students ts
                INNER JOIN tbl_teacher_subjects tsubj
                    ON tsubj.teacher_id = ts.teacher_id
                    AND tsubj.teacher_subject_id = ts.teacher_subject_id
                WHERE ts.teacher_id = ?
            ");
            $stmtS->bind_param("i", $poster_uid);
            $stmtS->execute();
            $rows = $stmtS->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtS->close();
            $sids = array_column($rows, 'student_id');
        } else {
            $r    = $conn->query("SELECT id FROM tblstudents WHERE status='active'");
            $sids = array_column($r->fetch_all(MYSQLI_ASSOC), 'id');
            $r->free();
        }

        logMessage("Sending announcement id={$ann_id} to " . count($sids) . " students");
        if (!empty($sids)) {
            sendNotificationDirect($sids, 'new_announcement', $ann_body, $notif_title);
        }
    }

    if ($category === 'for_teacher' || $category === 'both') {
        $r    = $conn->query("SELECT id FROM tblusers WHERE role='Teacher' AND status='Active'");
        $tids = array_column($r->fetch_all(MYSQLI_ASSOC), 'id');
        $r->free();
        logMessage("Sending announcement id={$ann_id} to " . count($tids) . " teachers");
        if (!empty($tids)) {
            sendNotificationDirect($tids, 'new_announcement', $ann_body, $notif_title);
        }
    }
}
} // end announcements

logMessage("ExceLearn Worker finished. Task: {$task}");
?>