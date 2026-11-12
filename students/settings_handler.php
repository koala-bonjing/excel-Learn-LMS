<?php
session_start();
require_once '../connection.php';

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT `id`, `student_id`, `theme`, `language`, `timezone`, `notif_push`, `notif_sms`, `updated_at` FROM `tbl_students_settings` WHERE `student_id` = ? LIMIT 1");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        $ins = $conn->prepare("INSERT INTO tbl_students_settings (student_id, theme, language, timezone, notif_push, notif_sms) VALUES (?, 'dark', 'English (Philippines)', '(GMT+08:00) Philippine Time', 1, 0)");
        if ($ins) {
            $ins->bind_param("i", $student_id);
            $ins->execute();
            $ins->close();
        }
        $result = [
            'theme'      => 'dark',
            'language'   => 'English (Philippines)',
            'timezone'   => '(GMT+08:00) Philippine Time',
            'notif_push' => 1,
            'notif_sms'  => 0
        ];
    }

    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !is_array($data)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    $allowed_languages = ['English (Philippines)', 'English (US)', 'Filipino'];
    $allowed_timezones = ['(GMT+08:00) Philippine Time', '(GMT+00:00) UTC'];

    $theme      = in_array($data['theme'] ?? '', ['dark', 'light']) ? $data['theme'] : 'dark';
    $language   = in_array($data['language'] ?? '', $allowed_languages) ? $data['language'] : 'English (Philippines)';
    $timezone   = in_array($data['timezone'] ?? '', $allowed_timezones) ? $data['timezone'] : '(GMT+08:00) Philippine Time';
    $notif_push = isset($data['notif_push']) ? (int)$data['notif_push'] : 1;
    $notif_sms  = isset($data['notif_sms'])  ? (int)$data['notif_sms']  : 0;
    
    $check = $conn->prepare("SELECT id FROM `tbl_students_settings` WHERE `student_id` = ? LIMIT 1");
    $check->bind_param("i", $student_id);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    $check->close();

    if ($exists) {
        $stmt = $conn->prepare("UPDATE `tbl_students_settings` SET `theme`=?, `language`=?, `timezone`=?, `notif_push`=?, `notif_sms`=? WHERE `student_id`=?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("sssiii", $theme, $language, $timezone, $notif_push, $notif_sms, $student_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO `tbl_students_settings` (`student_id`, `theme`, `language`, `timezone`, `notif_push`, `notif_sms`) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("isssii", $student_id, $theme, $language, $timezone, $notif_push, $notif_sms);
    }

    // then keep:
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Settings saved']);
    } else {
        $err = $stmt->error;
        $stmt->close();
        echo json_encode(['success' => false, 'message' => $err]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>