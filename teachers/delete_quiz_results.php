<?php
session_start();
include '../connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$teacher_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$student_id = (int)($data['student_id'] ?? 0);
$subject_id = (int)($data['subject_id'] ?? 0);

if (!$student_id || !$subject_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Verify the subject belongs to this teacher
$check = $conn->prepare("SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_subject_id = ? AND teacher_id = ?");
$check->bind_param("ii", $subject_id, $teacher_id);
$check->execute();
if (!$check->get_result()->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
$check->close();

$quiz_ids = array_map('intval', $data['quiz_ids'] ?? []);
if (empty($quiz_ids)) {
    echo json_encode(['success' => false, 'message' => 'No quizzes selected']);
    exit();
}
$placeholders = implode(',', array_fill(0, count($quiz_ids), '?'));
$types = str_repeat('i', count($quiz_ids));

$stmt = $conn->prepare("
    DELETE qr FROM tblquiz_results qr
    JOIN tblquizzes q ON q.quiz_id = qr.quizzes_id
    JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    WHERE l.teacher_subject_id = ? AND qr.student_id = ? AND qr.quizzes_id IN ($placeholders)
");
$params = array_merge([$subject_id, $student_id], $quiz_ids);
$stmt->bind_param('ii' . $types, ...$params);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

echo json_encode(['success' => true, 'deleted' => $affected]);