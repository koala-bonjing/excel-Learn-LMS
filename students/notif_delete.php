<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['student_id'])) {
    http_response_code(401);
    exit();
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['student_id'];

$notif_id = intval($_POST['notification_id'] ?? 0);

if ($notif_id > 0) {
    $stmt = $conn->prepare("
        DELETE FROM tblnotifications 
        WHERE notification_id = ? AND user_id = ?
    ");
    $stmt->bind_param('ii', $notif_id, $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
}

$conn->close();
?>