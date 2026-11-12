<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['teacher_id'])) { http_response_code(403); exit; }
$teacher_id = (int)$_SESSION['teacher_id'];

if (!empty($_POST['mark_all'])) {
    $stmt = $conn->prepare("UPDATE tblnotifications SET status='read' WHERE user_id=?");
    $stmt->bind_param('i', $teacher_id);
    $stmt->execute();
    $stmt->close();
} elseif (!empty($_POST['notification_id'])) {
    $nid = (int)$_POST['notification_id'];
    $stmt = $conn->prepare("UPDATE tblnotifications SET status='read' WHERE notification_id=? AND user_id=?");
    $stmt->bind_param('ii', $nid, $teacher_id);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true]);