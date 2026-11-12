<?php
session_start();
include '../connection.php';

$teacher_id = (int)$_SESSION['teacher_id'];
$notif_id   = (int)($_POST['notification_id'] ?? 0);

if ($notif_id && $teacher_id) {
    $stmt = $conn->prepare("DELETE FROM tblnotifications WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $notif_id, $teacher_id);
    $stmt->execute();
    $stmt->close();
}
echo 'ok';