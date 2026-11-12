// PALITAN NG:
<?php
session_start();
include '../connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'send_signal') {
    $class_id  = (int)$_POST['class_id'];
    $from_role = $_POST['from_role'];
    $to_role   = $_POST['to_role'];
    $signal    = $_POST['signal'];
    $from_id   = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("
        INSERT INTO tblvirtualclass_signals (class_id, from_id, from_role, to_role, `signal`, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iisss", $class_id, $from_id, $from_role, $to_role, $signal);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => 'ok']);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'get_signals') {
    $class_id = (int)$_POST['class_id'];
    $for_role = $_POST['for_role'];
    $last_id  = (int)($_POST['last_id'] ?? 0);

    $stmt = $conn->prepare("
        SELECT id, from_id, from_role, `signal`
        FROM tblvirtualclass_signals
        WHERE class_id = ? AND to_role = ? AND id > ?
        ORDER BY id ASC LIMIT 50
    ");
    $stmt->bind_param("isi", $class_id, $for_role, $last_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) $out[] = $row;
    $stmt->close();
    echo json_encode($out);
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'teacher_status') {
    $class_id = (int)($_POST['class_id'] ?? 0);
    if (!$class_id) {
        echo json_encode(['status' => 'unknown', 'camera_on' => 0]);
        exit;
    }
    $stmt = $conn->prepare("SELECT status, camera_on FROM tblvirtualclasses WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo json_encode([
        'status'    => $row['status']    ?? 'unknown',
        'camera_on' => (int)($row['camera_on'] ?? 0)
    ]);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'leave_class') {
    $student_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $class_id   = (int)($_POST['class_id'] ?? 0);
    if ($student_id && $class_id) {
        $stmt = $conn->prepare("UPDATE tblvirtualclass_participants SET `join` = 'NO' WHERE class_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $class_id, $student_id);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'update_media_status') {
    $student_id    = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $class_id      = (int)($_POST['class_id'] ?? 0);
    $camera_status = (int)($_POST['camera_status'] ?? 0);
    $mic_status    = (int)($_POST['mic_status'] ?? 0);
    if ($student_id && $class_id) {
        $stmt = $conn->prepare("UPDATE tblvirtualclass_participants SET camera_status = ?, mic_status = ? WHERE class_id = ? AND student_id = ?");
        $stmt->bind_param("iiii", $camera_status, $mic_status, $class_id, $student_id);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

$student_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$class_id   = isset($_POST['class_id'])   ? (int)$_POST['class_id']   : 0;

if (!$student_id || !$class_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

// ── 1. Fetch the virtual class ────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT id, teacher_id, teacher_subject_id, title, status, date, time, duration
    FROM tblvirtualclasses
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$class) {
    echo json_encode(['success' => false, 'message' => 'Virtual class not found.']);
    exit;
}

// ✅ FIX 2: trust the teacher's status column — don't second-guess with time math
if ($class['status'] === 'cancelled') {
    echo json_encode(['success' => false, 'message' => 'This class has been cancelled.']);
    exit;
}

if ($class['status'] === 'ended') {
    echo json_encode(['success' => false, 'message' => 'This class has already ended.']);
    exit;
}

if ($class['status'] !== 'live') {
    echo json_encode(['success' => false, 'message' => 'This class has not started yet.']);
    exit;
}

// ── 3. Verify the student is enrolled in this subject ────────────────────────
$stmt = $conn->prepare("
    SELECT ts.id
    FROM tbl_teachers_students ts
    WHERE ts.student_id = ?
      AND ts.teacher_subject_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $student_id, $class['teacher_subject_id']);
$stmt->execute();
$enrollment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$enrollment) {
    echo json_encode(['success' => false, 'message' => 'You are not enrolled in this subject.']);
    exit;
}

// ── 4. Check if student already joined (avoid duplicate rows) ─────────────────
$stmt = $conn->prepare("
    SELECT id
    FROM tblvirtualclass_participants
    WHERE class_id = ?
      AND student_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $class_id, $student_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $joined_at = (new DateTime())->format('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE tblvirtualclass_participants SET `join` = 'YES', joined_at = ? WHERE class_id = ? AND student_id = ?");
    $stmt->bind_param("sii", $joined_at, $class_id, $student_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode([
        'success'  => true,
        'class_id' => $class_id,
        'message'  => 'Welcome back to the class!'
    ]);
    exit;
}

// ── 5. Insert participant record ──────────────────────────────────────────────
$camera_status = 'off';
$mic_status    = 'off';
$joined_at     = (new DateTime())->format('Y-m-d H:i:s');

$join_status = 'YES';
$stmt = $conn->prepare("
    INSERT INTO tblvirtualclass_participants
        (class_id, teacher_subject_id, student_id, camera_status, mic_status, `join`, joined_at)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "iiissss",
    $class_id,
    $class['teacher_subject_id'],
    $student_id,
    $camera_status,
    $mic_status,
    $join_status,
    $joined_at
);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode([
        'success'  => true,
        'class_id' => $class_id,
        'message'  => 'Joined successfully!'
    ]);
} else {
    $err = $stmt->error;
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $err]);
}