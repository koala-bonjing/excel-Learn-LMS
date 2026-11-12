<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$highest_degree   = $_POST['highest_degree'] ?? '';
$university       = $_POST['university'] ?? '';
$bachelors_degree = $_POST['bachelors_degree'] ?? '';
$licensure        = $_POST['licensure'] ?? '';
$lpt_year         = $_POST['lpt_year'] ?? '';
$masters          = $_POST['masters'] ?? '';
$certifications   = $_POST['certifications'] ?? '';

try {

    // CHECK IF EXISTS
    $stmt = $conn->prepare("SELECT id FROM tblteacher_education WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {

        // UPDATE
        $stmt = $conn->prepare("
            UPDATE tblteacher_education 
            SET highest_degree=?, university=?, bachelors_degree=?, licensure=?, lpt_year=?, masters=?, certifications=?, updated_at=NOW()
            WHERE user_id=?
        ");

        $stmt->bind_param(
            "sssssssi",
            $highest_degree,
            $university,
            $bachelors_degree,
            $licensure,
            $lpt_year,
            $masters,
            $certifications,
            $user_id
        );

    } else {

        // INSERT
        $stmt = $conn->prepare("
            INSERT INTO tblteacher_education 
            (user_id, highest_degree, university, bachelors_degree, licensure, lpt_year, masters, certifications)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isssssss",
            $user_id,
            $highest_degree,
            $university,
            $bachelors_degree,
            $licensure,
            $lpt_year,
            $masters,
            $certifications
        );
    }

    if ($stmt->execute()) {
        $_SESSION['profile_success'] = "Education & credentials updated successfully!";
    } else {
        $_SESSION['profile_error'] = "Failed to update education: " . $stmt->error;
    }

    $stmt->close();

} catch (Exception $e) {
    $_SESSION['profile_error'] = "Something went wrong!";
}

header("Location: teacher_profile.php");
exit();
?>