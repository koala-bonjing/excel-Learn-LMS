<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['student_id'])) {
    http_response_code(401);
    exit();
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['student_id'];

// Mark all as read
if (!empty($_POST['mark_all'])) {
    $stmt = $conn->prepare("
        UPDATE tblnotifications 
        SET status = 'read' 
        WHERE user_id = ? AND status = 'unread'
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);

// Mark one as read
} elseif (!empty($_POST['notification_id'])) {
    $notif_id = intval($_POST['notification_id']);
    $stmt = $conn->prepare("
        UPDATE tblnotifications 
        SET status = 'read' 
        WHERE notification_id = ? AND user_id = ?
    ");
    $stmt->bind_param('ii', $notif_id, $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);

} else {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
}

$conn->close();
?>