<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

 $admin_id = $_SESSION['user_id'];
 $admin_name = 'Admin';
 $admin_pic = '';
 $initials = 'AD';

 $stmtU = $conn->prepare("SELECT profile_pic, firstname, lastname FROM tblusers WHERE id = ?");
 $stmtU->bind_param("i", $admin_id);
 $stmtU->execute();
 $resU = $stmtU->get_result();

if ($resU && $resU->num_rows > 0) {
    $u = $resU->fetch_assoc();
    $admin_name = trim($u['firstname'] . ' ' . $u['lastname']);
    $initials = strtoupper(substr($u['firstname'], 0, 1) . substr($u['lastname'], 0, 1));

    if (!empty($u['profile_pic'])) {
        $admin_pic = '../uploads/profile_pic/admin/' . $u['profile_pic'];
    }
}
 $stmtU->close();

// Load saved theme
$themeResult = $conn->prepare("SELECT theme FROM tblteacher_settings WHERE teacher_id = ? LIMIT 1");
$themeResult->bind_param("i", $admin_id);
$themeResult->execute();
$themeRow = $themeResult->get_result()->fetch_assoc();
$themeResult->close();
$savedTheme = $themeRow['theme'] ?? 'dark';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['api_action'];
    $response = ['success' => false, 'message' => 'Invalid request'];

    if ($action === 'create') {

        $first      = trim($_POST['firstname'] ?? '');
        $middle     = trim($_POST['middlename'] ?? '');
        $last       = trim($_POST['lastname'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $password   = trim($_POST['password'] ?? '');
        
        $selected_subjects = $_POST['subjects'] ?? [];

        $role     = 'teacher'; 
        $verified = 1;
        $status   = 'Active'; 

        if (
            empty($first) ||
            empty($last) ||
            empty($email) ||
            empty($password)
        ) {
            $response['message'] = "All required fields must be filled.";
        } else {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = "Invalid email format.";
                echo json_encode($response);
                exit();
            }
            
            if (!empty($password) && strlen($password) < 8) {
                $response['message'] = "Password must be at least 8 characters.";
                echo json_encode($response);
                exit();
            }
            $checkEmail = $conn->prepare("SELECT id FROM tblusers WHERE email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            if($checkEmail->get_result()->num_rows > 0){
                $response['message'] = "Email already exists.";
                $checkEmail->close();
                echo json_encode($response); exit();
            }
            $checkEmail->close();

            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

            $age     = intval($_POST['age'] ?? 0);
            $gender  = trim($_POST['gender'] ?? '');
            $contact = trim($_POST['contact_number'] ?? '');
            $address = trim($_POST['address'] ?? '');

            $stmt = $conn->prepare("
                INSERT INTO tblusers 
                (
                    firstname, middlename, lastname, email, password,
                    age, gender, contact_number, address,
                    role, is_verified, status, created_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->bind_param(
                "sssssissssss",
                $first, $middle, $last, $email, $hashed_pass,
                $age, $gender, $contact, $address,
                $role, $verified, $status
            );

            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;

                if (!empty($selected_subjects)) {
                    $subjStmt = $conn->prepare("INSERT INTO tbl_teacher_subjects (teacher_id, subject_id, created_at) VALUES (?, ?, NOW())");
                    foreach ($selected_subjects as $sub_id) {
                        if (!is_numeric($sub_id)) continue;
                        $clean_sub_id = intval($sub_id);
                        if ($clean_sub_id > 0) {
                            $subjStmt->bind_param("ii", $new_id, $clean_sub_id);
                            $subjStmt->execute();
                        }
                    }
                    $subjStmt->close();
                }

                $response['success'] = true;
                $response['message'] = "Teacher added successfully.";

                // Send welcome email to newly added teacher
                $emailer = mysqli_query($conn, "SELECT * FROM tbl_emailer LIMIT 1");
                $smtp    = mysqli_fetch_assoc($emailer);
                if ($smtp) {
                    require_once '../vendor/autoload.php';
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.hostinger.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = $smtp['smtp_email'];
                        $mail->Password   = $smtp['smtp_password'];
                        $mail->SMTPSecure = 'ssl';
                        $mail->Port       = 465;
                        $mail->setFrom($smtp['smtp_from'], $smtp['smtp_name']);
                        $mail->addAddress($email);
                        $mail->isHTML(true);
                        $mail->Subject = 'ExceLearn — Your Teacher Account Has Been Created';
                        $loginUrl = 'https://excelsiorpolytechnicacademy.com/excelearn/index.php';
                        $mail->Body = "
                            <div style='font-family:sans-serif;max-width:520px;margin:auto;padding:32px;background:#18181b;color:#e4e4e7;border-radius:12px'>
                                <div style='display:flex;align-items:center;gap:12px;margin-bottom:4px'>
                                    <img src='https://excelsiorpolytechnicacademy.com/excelearn/assets/img/Excelsior_Seal_Logo.png' alt='Excelsior Logo' style='height:48px;width:auto;object-fit:contain;'>
                                    <div>
                                        <div style='font-size:20px;font-weight:700;color:#22c55e;line-height:1.2'>ExceLearn LMS</div>
                                        <div style='font-size:12px;color:#a1a1aa;margin-top:2px'>Excelsior Polytechnic Academy</div>
                                    </div>
                                </div>
                                <hr style='border:none;border-top:1px solid #3f3f46;margin:20px 0'>
                                <p>Hello <b>{$first}</b>,</p>
                                <p>Welcome to <b>ExceLearn LMS</b>! Your teacher account has been created by the administrator.</p>
                                <div style='background:#27272a;border-left:4px solid #22c55e;border-radius:8px;padding:16px 20px;margin:24px 0;font-size:13px;line-height:1.8'>
                                    <div>📧 <b>Email:</b> {$email}</div>
                                    <div>🔑 <b>Password:</b> {$password}</div>
                                </div>
                                <p style='font-size:13px;color:#a1a1aa'>Please log in and change your password as soon as possible.</p>
                                <div style='text-align:center;margin:28px 0'>
                                    <a href='{$loginUrl}' style='display:inline-block;background:#22c55e;color:#fff;font-weight:700;font-size:15px;padding:13px 32px;border-radius:8px;text-decoration:none;letter-spacing:.3px'>🔐 Log In to ExceLearn</a>
                                </div>
                                <p style='font-size:12px;color:#a1a1aa;text-align:center'>Or copy this link: <a href='{$loginUrl}' style='color:#22c55e'>{$loginUrl}</a></p>
                                <hr style='border:none;border-top:1px solid #3f3f46;margin:20px 0'>
                                <p style='color:#71717a;font-size:12px;margin:0'>ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © 2025–2026</p>
                            </div>
                        ";
                        $mail->send();
                    } catch (Exception $e) { /* silent fail */ }
                }

                $workerUrl = 'https://excelsiorpolytechnicacademy.com/excelearn/background_worker/background_worker.php';
                $ch = curl_init($workerUrl);
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>1,CURLOPT_CONNECTTIMEOUT=>1,CURLOPT_NOBODY=>false,CURLOPT_SSL_VERIFYPEER=>false]);
                @curl_exec($ch);
                curl_close($ch);
                
            } else {
                $response['message'] = "Database error: " . $stmt->error;
            }

            $stmt->close();
        }
    }

    elseif ($action === 'update') {

        $id         = intval($_POST['teacher_db_id'] ?? 0);
        $first      = trim($_POST['firstname'] ?? '');
        $middle     = trim($_POST['middlename'] ?? '');
        $last       = trim($_POST['lastname'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $password   = trim($_POST['password'] ?? '');
        $selected_subjects = $_POST['subjects'] ?? [];

        if (
            $id < 1 ||
            empty($first) ||
            empty($last) ||
            empty($email)
        ) {
            $response['message'] = "Invalid data.";
        } else {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = "Invalid email format.";
                echo json_encode($response);
                exit();
            }
            $checkEmail = $conn->prepare("SELECT id FROM tblusers WHERE email = ? AND id != ?");
            $checkEmail->bind_param("si", $email, $id);
            $checkEmail->execute();

            if ($checkEmail->get_result()->num_rows > 0) {
                $response['message'] = "Email already exists.";
                $checkEmail->close();

                echo json_encode($response);
                exit();
            }

            $checkEmail->close();
            
            $age     = intval($_POST['age'] ?? 0);
            $gender  = trim($_POST['gender'] ?? '');
            $contact = trim($_POST['contact_number'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (!empty($password)) {
                $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE tblusers 
                        SET firstname=?, middlename=?, lastname=?, email=?, password=?,
                            age=?, gender=?, contact_number=?, address=?, updated_at=NOW()
                        WHERE id=?";
                $types  = "sssssissssi";
                $params = [$first, $middle, $last, $email, $hashed_pass, $age, $gender, $contact, $address, $id];
            } else {
                $sql = "UPDATE tblusers 
                        SET firstname=?, middlename=?, lastname=?, email=?,
                            age=?, gender=?, contact_number=?, address=?, updated_at=NOW()
                        WHERE id=?";
                $types  = "ssssisssi";
                $params = [$first, $middle, $last, $email, $age, $gender, $contact, $address, $id];
            }

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                
                    // Get existing teacher_subject_ids for this teacher
                    $getOldTS = $conn->prepare("SELECT teacher_subject_id, subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?");
                    $getOldTS->bind_param("i", $id);
                    $getOldTS->execute();
                    $oldTSResult = $getOldTS->get_result();
                    $oldSubjectMap = []; // subject_id => teacher_subject_id
                    while ($row = $oldTSResult->fetch_assoc()) {
                        $oldSubjectMap[(int)$row['subject_id']] = (int)$row['teacher_subject_id'];
                    }
                    $getOldTS->close();
                    
                    $newSubjectIds = array_map('intval', $selected_subjects);
                    
                    // Find subjects to remove (in old but not in new)
                    $toRemove = array_diff(array_keys($oldSubjectMap), $newSubjectIds);
                    
                    // Remove only the unselected subjects, but first clean up tbl_teachers_students
                    foreach ($toRemove as $removeSubId) {
                        $oldTsId = $oldSubjectMap[$removeSubId];
                    
                        // Remove from tbl_teachers_students first to avoid FK constraint
                        $delTS = $conn->prepare("DELETE FROM tbl_teachers_students WHERE teacher_subject_id = ?");
                        $delTS->bind_param("i", $oldTsId);
                        $delTS->execute();
                        $delTS->close();
                    
                        // Now safe to remove from tbl_teacher_subjects
                        $delSubj = $conn->prepare("DELETE FROM tbl_teacher_subjects WHERE teacher_subject_id = ?");
                        $delSubj->bind_param("i", $oldTsId);
                        $delSubj->execute();
                        $delSubj->close();
                    }
                    
                    // Add only newly selected subjects (not already existing)
                    $toAdd = array_diff($newSubjectIds, array_keys($oldSubjectMap));
                    if (!empty($toAdd)) {
                        $insSubj = $conn->prepare("INSERT INTO tbl_teacher_subjects (teacher_id, subject_id, created_at) VALUES (?, ?, NOW())");
                        foreach ($toAdd as $sub_id) {
                            if ($sub_id > 0) {
                                $insSubj->bind_param("ii", $id, $sub_id);
                                $insSubj->execute();
                            }
                        }
                        $insSubj->close();
                    }

                $response['success'] = true;
                $response['message'] = "Teacher updated successfully.";
                $workerUrl = 'https://excelsiorpolytechnicacademy.com/excelearn/background_worker/background_worker.php';
                $ch = curl_init($workerUrl);
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>1,CURLOPT_CONNECTTIMEOUT=>1,CURLOPT_NOBODY=>false,CURLOPT_SSL_VERIFYPEER=>false]);
                @curl_exec($ch);
                curl_close($ch);
            } else {
                $response['message'] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    elseif ($action === 'update_status') {
        
        $id = intval($_POST['teacher_db_id'] ?? 0);
        $status = trim($_POST['status'] ?? ''); 

        if ($id > 0 && in_array($status, ['Active', 'Inactive'])) {

            $stmt = $conn->prepare("UPDATE tblusers SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $status, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Teacher status updated.";
            } else {
                $response['message'] = "Failed to update status.";
            }
            $stmt->close();
        } else {
            $response['message'] = "Invalid status data.";
        }
    }
      elseif ($action === 'delete') {

        $id = intval($_POST['teacher_db_id'] ?? 0);

        if ($id < 1) {
            $response['message'] = "Invalid ID.";
        } else {

            // 1. Get all teacher_subject_ids for this teacher
            $getTS = $conn->prepare("SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?");
            $getTS->bind_param("i", $id);
            $getTS->execute();
            $resTS = $getTS->get_result();

            $teacherSubjectIds = [];
            while ($row = $resTS->fetch_assoc()) {
                $teacherSubjectIds[] = (int)$row['teacher_subject_id'];
            }
            $getTS->close();

            if (!empty($teacherSubjectIds)) {
                $placeholders = implode(',', array_fill(0, count($teacherSubjectIds), '?'));
                $types = str_repeat('i', count($teacherSubjectIds));

                // 2. Get lesson_ids under those teacher_subject_ids
                $lessonIds = [];
                $getL = $conn->prepare("SELECT lesson_id FROM tbl_teacher_lessons WHERE teacher_subject_id IN ($placeholders)");
                $getL->bind_param($types, ...$teacherSubjectIds);
                $getL->execute();
                $resL = $getL->get_result();
                while ($row = $resL->fetch_assoc()) {
                    $lessonIds[] = (int)$row['lesson_id'];
                }
                $getL->close();

                // 3. Delete quiz results and quizzes under those lessons
                if (!empty($lessonIds)) {
                    $lPlaceholders = implode(',', array_fill(0, count($lessonIds), '?'));
                    $lTypes = str_repeat('i', count($lessonIds));

                    // Get quiz_ids under those lessons
                    $quizIds = [];
                    $getQ = $conn->prepare("SELECT quiz_id FROM tblquizzes WHERE lesson_id IN ($lPlaceholders)");
                    $getQ->bind_param($lTypes, ...$lessonIds);
                    $getQ->execute();
                    $resQ = $getQ->get_result();
                    while ($row = $resQ->fetch_assoc()) {
                        $quizIds[] = (int)$row['quiz_id'];
                    }
                    $getQ->close();

                    // Delete quiz results
                    if (!empty($quizIds)) {
                        $qPlaceholders = implode(',', array_fill(0, count($quizIds), '?'));
                        $qTypes = str_repeat('i', count($quizIds));
                        $stmt = $conn->prepare("DELETE FROM tblquiz_results WHERE quizzes_id IN ($qPlaceholders)");
                        $stmt->bind_param($qTypes, ...$quizIds);
                        $stmt->execute();
                        $stmt->close();

                        // Delete quizzes
                        $stmt = $conn->prepare("DELETE FROM tblquizzes WHERE quiz_id IN ($qPlaceholders)");
                        $stmt->bind_param($qTypes, ...$quizIds);
                        $stmt->execute();
                        $stmt->close();
                    }

                    // Delete lessons
                    $stmt = $conn->prepare("DELETE FROM tbl_teacher_lessons WHERE lesson_id IN ($lPlaceholders)");
                    $stmt->bind_param($lTypes, ...$lessonIds);
                    $stmt->execute();
                    $stmt->close();
                }

                // 4. Get virtual class IDs under those teacher_subject_ids
                $vcIds = [];
                $getVC = $conn->prepare("SELECT id FROM tblvirtualclasses WHERE teacher_subject_id IN ($placeholders)");
                $getVC->bind_param($types, ...$teacherSubjectIds);
                $getVC->execute();
                $resVC = $getVC->get_result();
                while ($row = $resVC->fetch_assoc()) {
                    $vcIds[] = (int)$row['id'];
                }
                $getVC->close();

                // 5. Delete virtual class participants then virtual classes
                if (!empty($vcIds)) {
                    $vcPlaceholders = implode(',', array_fill(0, count($vcIds), '?'));
                    $vcTypes = str_repeat('i', count($vcIds));

                    $stmt = $conn->prepare("DELETE FROM tblvirtualclass_participants WHERE class_id IN ($vcPlaceholders)");
                    $stmt->bind_param($vcTypes, ...$vcIds);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $conn->prepare("DELETE FROM tblvirtualclasses WHERE id IN ($vcPlaceholders)");
                    $stmt->bind_param($vcTypes, ...$vcIds);
                    $stmt->execute();
                    $stmt->close();
                }

                // 6. Delete tbl_teachers_students rows under those teacher_subject_ids
                $stmt = $conn->prepare("DELETE FROM tbl_teachers_students WHERE teacher_subject_id IN ($placeholders)");
                $stmt->bind_param($types, ...$teacherSubjectIds);
                $stmt->execute();
                $stmt->close();

                // 7. Delete tbl_teacher_subjects
                $stmt = $conn->prepare("DELETE FROM tbl_teacher_subjects WHERE teacher_subject_id IN ($placeholders)");
                $stmt->bind_param($types, ...$teacherSubjectIds);
                $stmt->execute();
                $stmt->close();
            }

            // 8. Delete announcements (user_id = teacher's tblusers id)
            $stmt = $conn->prepare("DELETE FROM tblannouncements WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // 9. Delete teacher settings
            $stmt = $conn->prepare("DELETE FROM tblteacher_settings WHERE teacher_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // 10. Delete user sessions
            $stmt = $conn->prepare("DELETE FROM tbluser_sessions WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // 11. Delete teacher education
            $stmt = $conn->prepare("DELETE FROM tblteacher_education WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // 12. Finally delete the teacher user record
            $stmt = $conn->prepare("DELETE FROM tblusers WHERE id = ? AND role = 'teacher'");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Teacher and ALL related records deleted successfully.";
            } else {
                $response['message'] = "Failed to delete: " . $stmt->error;
            }

            $stmt->close();
        }
    }
    elseif ($action === 'approve') {
    $id = intval($_POST['teacher_db_id'] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE tblusers SET status = 'Active', is_verified = 1, updated_at = NOW() WHERE id = ? AND role = 'teacher'");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {

            // Send approval email
            $emailer = mysqli_query($conn, "SELECT * FROM tbl_emailer LIMIT 1");
            $smtp    = mysqli_fetch_assoc($emailer);

            $userStmt = $conn->prepare("SELECT firstname, email FROM tblusers WHERE id = ?");
            $userStmt->bind_param("i", $id);
            $userStmt->execute();
            $user = $userStmt->get_result()->fetch_assoc();
            $userStmt->close();

            if ($user) {
                require_once '../vendor/autoload.php';
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.hostinger.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $smtp['smtp_email'];
                    $mail->Password   = $smtp['smtp_password'];
                    $mail->SMTPSecure = 'ssl';
                    $mail->Port       = 465;
                    $mail->setFrom($smtp['smtp_from'], $smtp['smtp_name']);
                    $mail->addAddress($user['email']);
                    $mail->isHTML(true);
                    $mail->Subject = 'ExceLearn — Your Account Has Been Approved!';
                    $loginUrl = 'https://excelsiorpolytechnicacademy.com/excelearn/index.php';
                    $mail->Body = "
                        <div style='font-family:sans-serif;max-width:520px;margin:auto;padding:32px;background:#18181b;color:#e4e4e7;border-radius:12px'>
                            <div style='display:flex;align-items:center;gap:12px;margin-bottom:4px'>
                                <img src='https://excelsiorpolytechnicacademy.com/excelearn/assets/img/Excelsior_Seal_Logo.png' alt='Excelsior Logo' style='height:48px;width:auto;object-fit:contain;'>
                                <div>
                                    <div style='font-size:20px;font-weight:700;color:#22c55e;line-height:1.2'>ExceLearn LMS</div>
                                    <div style='font-size:12px;color:#a1a1aa;margin-top:2px'>Excelsior Polytechnic Academy</div>
                                </div>
                            </div>
                            <hr style='border:none;border-top:1px solid #3f3f46;margin:20px 0'>
                            <p>Hello <b>{$user['firstname']}</b>,</p>
                            <p>Great news! Your account on <b>ExceLearn</b> has been <b style='color:#22c55e'>approved</b> by the administrator.</p>
                            <div style='background:#27272a;border-left:4px solid #22c55e;border-radius:8px;padding:16px 20px;margin:24px 0'>
                                ✅ &nbsp;You can now <b>log in</b> to ExceLearn and start managing your classes.
                            </div>
                            <div style='text-align:center;margin:28px 0'>
                                <a href='{$loginUrl}' style='display:inline-block;background:#22c55e;color:#fff;font-weight:700;font-size:15px;padding:13px 32px;border-radius:8px;text-decoration:none;letter-spacing:.3px'>🔐 Log In to ExceLearn</a>
                            </div>
                            <p style='font-size:12px;color:#a1a1aa;text-align:center'>Or copy this link: <a href='{$loginUrl}' style='color:#22c55e'>{$loginUrl}</a></p>
                            <hr style='border:none;border-top:1px solid #3f3f46;margin:20px 0'>
                            <p style='color:#71717a;font-size:12px;margin:0'>ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © 2025–2026</p>
                        </div>
                    ";
                    $mail->send();
                } catch (Exception $e) { /* silent fail */ }
            }

            $response['success'] = true;
            $response['message'] = "Teacher approved and notified via email.";
        } else {
            $response['message'] = "Failed to approve teacher.";
        }
        $stmt->close();
    } else {
        $response['message'] = "Invalid ID.";
    }
}
elseif ($action === 'disapprove') {

        $id = intval($_POST['teacher_db_id'] ?? 0);

        if ($id < 1) {
            $response['message'] = "Invalid ID.";
        } else {

            // Get teacher info first (for email) before deleting
            $userStmt = $conn->prepare("SELECT firstname, email FROM tblusers WHERE id = ? AND role = 'teacher'");
            $userStmt->bind_param("i", $id);
            $userStmt->execute();
            $user = $userStmt->get_result()->fetch_assoc();
            $userStmt->close();

            if (!$user) {
                $response['message'] = "Teacher not found.";
                echo json_encode($response);
                exit();
            }

            // Clean up minimal related records (unverified accounts shouldn't have much)
            $stmt = $conn->prepare("DELETE FROM tbl_teacher_subjects WHERE teacher_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM tblteacher_settings WHERE teacher_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM tbluser_sessions WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM tblteacher_education WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM tblusers WHERE id = ? AND role = 'teacher'");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {

                // Send disapproval email
                $emailer = mysqli_query($conn, "SELECT * FROM tbl_emailer LIMIT 1");
                $smtp    = mysqli_fetch_assoc($emailer);

                require_once '../vendor/autoload.php';
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.hostinger.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $smtp['smtp_email'];
                    $mail->Password   = $smtp['smtp_password'];
                    $mail->SMTPSecure = 'ssl';
                    $mail->Port       = 465;
                    $mail->setFrom($smtp['smtp_from'], $smtp['smtp_name']);
                    $mail->addAddress($user['email']);
                    $mail->isHTML(true);
                    $mail->Subject = 'ExceLearn — Account Application Update';
                    $mail->Body = "
                        <div style='font-family:sans-serif;max-width:520px;margin:auto;padding:32px;background:#18181b;color:#e4e4e7;border-radius:12px'>
                            <div style='display:flex;align-items:center;gap:12px;margin-bottom:4px'>
                                <img src='https://excelsiorpolytechnicacademy.com/excelearn/assets/img/Excelsior_Seal_Logo.png' alt='Excelsior Logo' style='height:48px;width:auto;object-fit:contain;'>
                                <div>
                                    <div style='font-size:20px;font-weight:700;color:#ef4444;line-height:1.2'>ExceLearn LMS</div>
                                    <div style='font-size:12px;color:#a1a1aa;margin-top:2px'>Excelsior Polytechnic Academy</div>
                                </div>
                            </div>
                            <hr style='border:none;border-top:1px solid #3f3f46;margin:20px 0'>
                            <p>Hello <b>{$user['firstname']}</b>,</p>
                            <p>We regret to inform you that your account application on <b>ExceLearn</b> has been <b style='color:#ef4444'>declined</b> by the administrator.</p>
                            <div style='background:#27272a;border-left:4px solid #ef4444;border-radius:8px;padding:16px 20px;margin:24px 0'>
                                ❌ &nbsp;Based on our records, you are not currently recognized as a faculty member of Excelsior Polytechnic Academy. If you believe this is a mistake, please contact the school administration directly.
                            </div>
                            <hr style='border:none;border-top:1px solid #3f3f46;margin:20px 0'>
                            <p style='color:#71717a;font-size:12px;margin:0'>ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © 2025–2026</p>
                        </div>
                    ";
                    $mail->send();
                } catch (Exception $e) { /* silent fail */ }

                $response['success'] = true;
                $response['message'] = "Teacher account disapproved, removed, and notified via email.";
            } else {
                $response['message'] = "Failed to disapprove: " . $stmt->error;
            }

            $stmt->close();
        }
    }
    elseif ($action === 'csv_import') {

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = "No file uploaded or upload error.";
            echo json_encode($response);
            exit();
        }

        $file   = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');

        if (!$handle) {
            $response['message'] = "Cannot open uploaded file.";
            echo json_encode($response);
            exit();
        }

        $rawHeader = fgetcsv($handle);
        if (!$rawHeader) {
            $response['message'] = "CSV file is empty or has no header row.";
            echo json_encode($response);
            exit();
        }

        $header = array_map(function($h) {
            return strtolower(trim(preg_replace('/\s+/', '_', $h)));
        }, $rawHeader);

        $required = ['firstname', 'lastname', 'email', 'password'];
        $missing  = array_diff($required, $header);
        if (!empty($missing)) {
            $response['message'] = "CSV is missing required columns: " . implode(', ', $missing);
            echo json_encode($response);
            exit();
        }

        $inserted = 0;
        $skipped  = 0;
        $errors   = [];
        $rowNum   = 1;

        $insertStmt = $conn->prepare("
            INSERT INTO tblusers (
                firstname, middlename, lastname,
                email, password, age, gender, contact_number, address,
                role, is_verified, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'teacher', 1, 'Active', NOW())
        ");

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count(array_filter($row, 'strlen')) === 0) continue;

            $data = [];
            foreach ($header as $i => $col) {
                $data[$col] = isset($row[$i]) ? trim($row[$i]) : '';
            }

            $firstname      = $data['firstname']      ?? '';
            $middlename     = $data['middlename']     ?? '';
            $lastname       = $data['lastname']       ?? '';
            $email          = $data['email']          ?? '';
            $password_raw   = $data['password']       ?? '';
            $age_csv        = $data['age']            ?? '';
            $gender_csv     = $data['gender']         ?? '';
            $contact_csv    = $data['contact_number'] ?? '';
            $address_csv    = $data['address']        ?? '';

            if (empty($firstname) || empty($lastname) || empty($email) || empty($password_raw)) {
                $errors[] = "Row $rowNum: Missing required field(s) — skipped.";
                $skipped++;
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row $rowNum: Invalid email '$email' — skipped.";
                $skipped++;
                continue;
            }

            $chkEmail = $conn->prepare("SELECT id FROM tblusers WHERE email = ?");
            $chkEmail->bind_param("s", $email);
            $chkEmail->execute();
            if ($chkEmail->get_result()->num_rows > 0) {
                $errors[] = "Row $rowNum: Email '$email' already exists — skipped.";
                $chkEmail->close();
                $skipped++;
                continue;
            }
            $chkEmail->close();

            $hashed  = password_hash($password_raw, PASSWORD_DEFAULT);
            $age_val = intval($age_csv);
            $insertStmt->bind_param(
                "sssssisss",
                $firstname,
                $middlename,
                $lastname,
                $email,
                $hashed,
                $age_val,
                $gender_csv,
                $contact_csv,
                $address_csv
            );

            if ($insertStmt->execute()) {
                $new_teacher_id = $insertStmt->insert_id;
                $inserted++;

                // Send welcome email to imported teacher
                if (!isset($smtp_csv)) {
                    $emailer_csv = mysqli_query($conn, "SELECT * FROM tbl_emailer LIMIT 1");
                    $smtp_csv    = mysqli_fetch_assoc($emailer_csv);
                    if ($smtp_csv) require_once '../vendor/autoload.php';
                }
                if (!empty($smtp_csv)) {
                    $mail_csv = new PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        $mail_csv->isSMTP();
                        $mail_csv->Host       = 'smtp.hostinger.com';
                        $mail_csv->SMTPAuth   = true;
                        $mail_csv->Username   = $smtp_csv['smtp_email'];
                        $mail_csv->Password   = $smtp_csv['smtp_password'];
                        $mail_csv->SMTPSecure = 'ssl';
                        $mail_csv->Port       = 465;
                        $mail_csv->setFrom($smtp_csv['smtp_from'], $smtp_csv['smtp_name']);
                        $mail_csv->addAddress($email);
                        $mail_csv->isHTML(true);
                        $mail_csv->Subject = 'ExceLearn — Your Teacher Account Has Been Created';
                        $loginUrl = 'https://excelsiorpolytechnicacademy.com/excelearn/index.php';
                        $mail_csv->Body = "
                            <div style='font-family:sans-serif;max-width:520px;margin:auto;padding:32px;background:#18181b;color:#e4e4e7;border-radius:12px'>
                                <div style='display:flex;align-items:center;gap:12px;margin-bottom:4px'>
                                    <img src='https://excelsiorpolytechnicacademy.com/excelearn/assets/img/Excelsior_Seal_Logo.png' alt='Excelsior Logo' style='height:48px;width:auto;object-fit:contain;'>
                                    <div>
                                        <div style='font-size:20px;font-weight:700;color:#22c55e;line-height:1.2'>ExceLearn LMS</div>
                                        <div style='font-size:12px;color:#a1a1aa;margin-top:2px'>Excelsior Polytechnic Academy</div>
                                    </div>
                                </div>
                                <hr style='border:none;border-top:1px solid #3f3f46;margin:20px 0'>
                                <p>Hello <b>{$firstname}</b>,</p>
                                <p>Welcome to <b>ExceLearn LMS</b>! Your teacher account has been created by the administrator.</p>
                                <div style='background:#27272a;border-left:4px solid #22c55e;border-radius:8px;padding:16px 20px;margin:24px 0;font-size:13px;line-height:1.8'>
                                    <div>📧 <b>Email:</b> {$email}</div>
                                    <div>🔑 <b>Password:</b> {$password_raw}</div>
                                </div>
                                <p style='font-size:13px;color:#a1a1aa'>Please log in and change your password as soon as possible.</p>
                                <div style='text-align:center;margin:28px 0'>
                                    <a href='{$loginUrl}' style='display:inline-block;background:#22c55e;color:#fff;font-weight:700;font-size:15px;padding:13px 32px;border-radius:8px;text-decoration:none;letter-spacing:.3px'>🔐 Log In to ExceLearn</a>
                                </div>
                                <p style='font-size:12px;color:#a1a1aa;text-align:center'>Or copy this link: <a href='{$loginUrl}' style='color:#22c55e'>{$loginUrl}</a></p>
                                <hr style='border:none;border-top:1px solid #3f3f46;margin:20px 0'>
                                <p style='color:#71717a;font-size:12px;margin:0'>ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © 2025–2026</p>
                            </div>
                        ";
                        $mail_csv->send();
                    } catch (Exception $e) { /* silent fail */ }
                }

            } else {
                $errors[] = "Row $rowNum: Database error — " . $insertStmt->error;
                $skipped++;
            }
        }

        fclose($handle);
        $insertStmt->close();

        $response['success']  = true;
        $response['inserted'] = $inserted;
        $response['skipped']  = $skipped;
        $response['errors']   = $errors;
        $response['message']  = "$inserted teacher(s) imported successfully." . ($skipped > 0 ? " $skipped row(s) skipped." : "");
    }

    echo json_encode($response);
    exit();
}

$subjects_db = [];
$sub_stmt = $conn->prepare("
    SELECT 
        sub.subject_id, 
        sub.subject,
        st.strand,
        st.track,
        g.grade_level
    FROM tblsubjects sub
    LEFT JOIN tblstrands st ON sub.strand_id = st.strand_id
    LEFT JOIN tblgrade_levels g ON st.grade_level_id = g.grade_id
    ORDER BY g.grade_level ASC, st.strand ASC, sub.subject ASC
");
$sub_stmt->execute();
$sub_res = $sub_stmt->get_result();
while ($r = $sub_res->fetch_assoc()) {
    $subjects_db[] = [
        'subject_id'  => (int)$r['subject_id'],
        'subject'     => $r['subject'],
        'strand'      => $r['strand'] ?? 'Unknown',
        'track'       => $r['track'] ?? '',
        'grade_level' => $r['grade_level'] ?? 'Unknown'
    ];
}
$sub_stmt->close();

 $subject_map = [];
    foreach($subjects_db as $s) {
        $subject_map[$s['subject_id']] = [
        'subject' => $s['subject'],
        'strand' => $s['strand'],
        'grade_level' => $s['grade_level']
    ];
}

 $teachers_db = [];
$stmt = $conn->prepare("
    SELECT
        u.id,
        u.firstname,
        u.middlename,
        u.lastname,
        u.email,
        u.age,
        u.gender,
        u.contact_number,
        u.address,
        u.status,
        u.is_verified,
        u.created_at,
        u.profile_pic
    FROM tblusers u
    WHERE u.role = 'teacher'
    ORDER BY u.created_at DESC
");
 $stmt->execute();
 $res = $stmt->get_result();

 $colorPalette = [
    "linear-gradient(135deg,var(--purple),#8b3fbf)",
    "linear-gradient(135deg,var(--rose),#e11d48)",
    "linear-gradient(135deg,#8b5cf6,var(--rose))",
    "linear-gradient(135deg,var(--cyan),var(--teal))",
    "linear-gradient(135deg,var(--rose),var(--purple))",
    "linear-gradient(135deg,var(--amber),var(--green))",
    "linear-gradient(135deg,var(--green),var(--cyan))",
    "linear-gradient(135deg,var(--teal),var(--accent))",
    "linear-gradient(135deg,var(--amber),var(--rose))",
    "linear-gradient(135deg,var(--accent),var(--purple))"
];

 $idx = 0;

 $teacher_subjects_map = []; 
 $ts_map_stmt = $conn->prepare("SELECT teacher_id, subject_id FROM tbl_teacher_subjects");
 $ts_map_stmt->execute();
 $ts_map_res = $ts_map_stmt->get_result();
while($r = $ts_map_res->fetch_assoc()){
    $tid = (int)$r['teacher_id'];
    $subid = (int)$r['subject_id'];
    if(!isset($teacher_subjects_map[$tid])) $teacher_subjects_map[$tid] = [];
    $teacher_subjects_map[$tid][] = $subid;
}
 $ts_map_stmt->close();

$subject_assigned_map = [];
foreach ($teacher_subjects_map as $tid => $subs) {
    foreach ($subs as $sid) {
        if (!isset($subject_assigned_map[$sid])) $subject_assigned_map[$sid] = [];
        $subject_assigned_map[$sid][] = $tid;
    }
}
$subject_assigned_map_json = json_encode($subject_assigned_map);

while ($r = $res->fetch_assoc()) {

    $displayStatus = ucfirst(strtolower(trim($r['status'])));

    $current_teacher_sub_ids = $teacher_subjects_map[(int)$r['id']] ?? [];
    $current_teacher_sub_names = [];
    $teacher_grade_strands = [];
    
    foreach($current_teacher_sub_ids as $sid) {
        if(isset($subject_map[$sid])) {
    
            $sub = $subject_map[$sid];
    
            // SUBJECT NAME
            $current_teacher_sub_names[] = $sub['subject'];
    
            // GRADE + STRAND COMBO
            $key = $sub['grade_level'] . ' - ' . $sub['strand'];
            $teacher_grade_strands[$key] = true;
        }
    }
    
    // convert unique combinations
    $teacher_grade_strands = array_keys($teacher_grade_strands);
    $avatarColor = $colorPalette[$idx % count($colorPalette)];

    $teachers_db[] = [
        'id'             => (int)$r['id'],
        'first'          => $r['firstname'],
        'middle'         => $r['middlename'],
        'last'           => $r['lastname'],
        'email'          => $r['email'],
        'age'            => $r['age'] ?? '',
        'gender'         => $r['gender'] ?? '',
        'contact_number' => $r['contact_number'] ?? '',
        'address'        => $r['address'] ?? '',
        'status'         => $displayStatus,
        'is_verified'    => (int)$r['is_verified'],
        'joined'         => date('M j, Y', strtotime($r['created_at'])),
        'color'          => $avatarColor,
        'subjects'       => $current_teacher_sub_names,
        'grade_strands'  => $teacher_grade_strands,
        'rank'           => '',
        'dept'           => 'General',
        'load'           => 0,
        'maxLoad'        => 24,
        'profile_pic'    => !empty($r['profile_pic'])
            ? '../uploads/profile_pic/teachers/' . $r['profile_pic']
            : ''
    ];
    $idx++;
}
 $stmt->close();

 $teachers_json = json_encode($teachers_db);
 $subjects_json = json_encode($subjects_db);
 $teacher_subjects_map_json = json_encode($teacher_subjects_map);

  $teacherCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblusers WHERE role = 'teacher'");
  $teacherCountStmt->execute();
  $teacherCount = $teacherCountStmt->get_result()->fetch_assoc()['total'];
  $teacherCountStmt->close();

  $studentCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblstudents");
  $studentCountStmt->execute();
  $studentCount = $studentCountStmt->get_result()->fetch_assoc()['total'];
  $studentCountStmt->close();

  $strandCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblstrands");
  $strandCountStmt->execute();
  $strandCount = $strandCountStmt->get_result()->fetch_assoc()['total'];
  $strandCountStmt->close();

  $sectionCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM tblsections");
  $sectionCountStmt->execute();
  $sectionCount = $sectionCountStmt->get_result()->fetch_assoc()['total'];
  $sectionCountStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn — Teachers</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin/admin_teachers.css">
<style>
.subjects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 10px;
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid var(--border);
  padding: 10px;
  border-radius: 6px;
  background: var(--bg2);
  align-content: start; 
}
.check-item {
  display: flex;
  align-items: center;
  padding: 4px 0;
  font-size: 13px;
  cursor: pointer;
  user-select: none;
  height: 24px;
}
.check-item input {
  margin-right: 8px;
  accent-color: var(--green);
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

/* Subject badges in table */
.subj-list {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}
.badge-sub {
  font-size: 10px;
  padding: 2px 6px;
  background: var(--bg3);
  color: var(--text2);
  border-radius: 4px;
  border: 1px solid var(--border);
  white-space: nowrap;
}
@media (max-width: 768px) {

     .t-card {
        display: block !important;
        padding: 12px;
        border-radius: 10px;
        background: var(--bg1);
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
      }
    
      .tc-actions {
        display: flex !important;
        flex-direction: column;
        gap: 5px;
        margin-left: auto;
      }

    .section-header {
    flex-direction: row !important;
    align-items: center !important;
    flex-wrap: nowrap !important;
  }
  .section-header > div {
    flex: 1;
    min-width: 0;
  }
  .section-header .btn-primary {
    flex-shrink: 0;
    white-space: nowrap;
  }

/* NEW: button group on the right, stacked vertically on mobile */
  .section-header > div[style*="display:flex"] {
    flex: 0 0 auto !important;
    margin-left: auto;
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 6px !important;
  }
  .section-header > div[style*="display:flex"] .btn {
    padding: 6px 10px !important;
    font-size: 12px !important;
    white-space: nowrap;
    width: 100%;
  }
  .section-header > div[style*="display:flex"] .btn-ghost {
    gap: 4px !important;
    justify-content: center;
  }
  .section-header > div[style*="display:flex"] .btn-ghost svg {
    width: 12px;
    height: 12px;
  }
}
@media (max-width: 768px) {
  .topbar {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 100 !important;
    width: 100% !important;
  }
  .main {
    padding-top: 60px !important;
  }
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
@media (max-width: 768px) {
  .topbar-logo-desktop {
    display: none !important;
  }
  .topbar-logo-mobile {
    display: flex !important;
  }
}

</style>
</head>
<body class="<?= $savedTheme === 'light' ? 'light' : '' ?>">
<div class="layout">
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo" style="display:flex;align-items:center;gap:10px;flex-wrap:nowrap;overflow:hidden;">
    <img src="../assets/img/Excelsior_Seal_Logo.png" alt="ExceLearn Logo" style="height:38px;width:auto;object-fit:contain;flex-shrink:0;">
    <div style="min-width:0">
      <div class="logo-name">Exce<span style="color:#22c55e">Learn</span></div>
      <div class="logo-sub">Learn Better. Anytime, Anywhere.</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a class="nav-item" href="index.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <div class="nav-section-label" style="margin-top:14px">User Management</div>
    <a class="nav-item active" href="admin_teachers.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Teachers
      <span class="nav-badge"><?php echo $teacherCount; ?></span>
    </a>
    <a class="nav-item" href="admin_students.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Students
      <span class="nav-badge"><?php echo $studentCount; ?></span>
    </a>
    <div class="nav-section-label" style="margin-top:14px">Academic</div>
    <a class="nav-item" href="admin_sections.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      Grade & Sections
      <span class="nav-badge"><?php echo $sectionCount; ?></span>
    </a>
    <a class="nav-item" href="admin_strands.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Strands<span class="nav-badge"><?php echo $strandCount; ?></span>
    </a>
    <a class="nav-item" href="admin_subjects.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5V4.5A1.5 1.5 0 0 1 5.5 3h13A1.5 1.5 0 0 1 20 4.5v15a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 19.5z"/>
        <path d="M8 7h8M8 11h8M8 15h5"/>
      </svg>
        <?php
        $stats_subjects = $conn->query("SELECT COUNT(*) as count FROM tblsubjects")->fetch_assoc()['count'];
        ?>
        Subjects <span class="nav-badge"><?php echo $stats_subjects; ?></span>
    </a>
    <div class="nav-section-label" style="margin-top:14px">Reports and Analytics</div>
    <a class="nav-item" href="admin_reports.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/>
        <line x1="10" y1="9" x2="8" y2="9"/>
      </svg>
      Reports
    </a>
    <a class="nav-item" href="admin_analytics.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="3" x2="3" y2="21"/>
        <line x1="3" y1="21" x2="21" y2="21"/>
        <polyline points="7 16 11 12 15 14 19 8"/>
      </svg>
      Analytics
    </a>
    <div class="nav-section-label" style="margin-top:14px">Account</div>
    <a class="nav-item" href="admin_profile.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      My Profile
    </a>
    <a class="nav-item" href="settings.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33h0A1.65 1.65 0 009 3.09V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51h0a1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82v0a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
      Settings
    </a>
    <a class="nav-item" href="about.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      About
    </a>
    <a class="nav-item" href="#" onclick="openLogoutModal();return false;">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <?php if(!empty($admin_pic)): ?>
        <div class="user-avatar" style="background-image:url('<?php echo $admin_pic; ?>'); background-size:cover;"></div>
      <?php else: ?>
        <div class="user-avatar" style="background:linear-gradient(135deg,var(--rose),var(--amber))"><?php echo $initials; ?></div>
      <?php endif; ?>
      <div class="user-info"><p><?php echo htmlspecialchars($admin_name); ?></p><span>Administrator</span></div>
    </div>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<main class="main">
<div class="topbar">
  <button class="menu-btn" id="menuBtn" aria-label="Open menu">
    <div class="hamburger"><span></span><span></span><span></span></div>
  </button>
  <img class="topbar-logo-desktop" src="../assets/img/Excelsior_Logo_long.png" alt="Logo" style="height:38px;width:auto;">
  <div class="topbar-logo-mobile" style="display:none;align-items:center;gap:10px;flex-wrap:nowrap;overflow:hidden;">
    <img src="../assets/img/Excelsior_Seal_Logo.png" alt="ExceLearn Logo"
         style="height:38px;width:auto;object-fit:contain;flex-shrink:0;">
    <div style="min-width:0">
      <div class="logo-name">Exce<span style="color:#22c55e">Learn</span></div>
      <div class="logo-sub">Learn Better. Anytime, Anywhere.</div>
    </div>
  </div>
</div>

  <div class="content">
    <div class="section-header">
      <div>
        <div class="section-title">👨‍🏫 Teachers Management</div>
        <div class="section-sub">Manage teachers by maintaining and updating teacher profiles and essential information.</div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <button class="btn btn-primary" onclick="openAddModal()">+ Add Teacher</button>
        <button class="btn btn-ghost" onclick="openCSVModal()" style="display:flex;align-items:center;gap:6px">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Import CSV
        </button>
      </div>
    </div>
    <div class="mini-stats" id="mini-stats"></div>
    <div class="filter-bar">
      <div class="search-wrap">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="searchInput" placeholder="Search by name, email, or specialization..." oninput="applyFilters()">
      </div>
      <div class="filter-chips" id="filter-chips"></div>
    </div>
    <div class="bulk-bar" id="bulk-bar">
      <span><span class="bulk-count" id="bulk-count">0</span> teacher(s) selected</span>
      <div class="bulk-sep"></div>
      <button class="btn btn-success btn-xs" onclick="bulkAction('activate')">✅ Activate</button>
      <button class="btn btn-danger btn-xs" onclick="bulkAction('deactivate')">🚫 Deactivate</button>
      <button class="btn btn-ghost btn-xs" onclick="bulkAction('export')">📤 Export</button>
      <button class="btn btn-ghost btn-xs" onclick="clearSelection()" style="margin-left:auto">✕ Clear</button>
    </div>
    <div class="card" style="padding:0;overflow:hidden">
      <div class="table-wrap">
        <table class="t-table" id="t-table">
          <thead>
            <tr>
              <th style="width:40px"><div class="custom-check" id="check-all" onclick="toggleAll()"></div></th>
              <th><span class="sort-btn" onclick="sortBy('name')">Teacher <span class="sort-arrow" id="sort-name">↕</span></span></th>
              <!-- Department Column Removed -->
              <th>Teacher Info</th>
              <!-- Load Column Removed -->
              <th><span class="sort-btn" onclick="sortBy('status')">Status <span class="sort-arrow" id="sort-status">↕</span></span></th>
              <th style="width:120px">Actions</th>
            </tr>
          </thead>
          <tbody id="table-body"></tbody>
        </table>
      </div>
      <div class="mobile-list" id="mobile-list" style="padding:14px"></div>
      <div class="pagination" style="padding:14px 16px" id="pagination"></div>
    </div>
  </div>
</main>
</div>

<div class="modal-overlay" id="teacher-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-title">Add New Teacher</div>
      <button class="modal-close" onclick="closeModal('teacher-modal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-id">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">First Name *</label>
          <input type="text" id="f-first" placeholder="e.g. Angela">
        </div>
        <div class="form-group">
          <label class="form-label">Middle Name</label>
          <input type="text" id="f-middle" placeholder="e.g. Santos">
        </div>
        <div class="form-group">
          <label class="form-label">Last Name *</label>
          <input type="text" id="f-last" placeholder="e.g. Reyes">
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" id="f-email" placeholder="teacher@excelsior.edu.ph">
        </div>
        <div class="form-group">
          <label class="form-label">Age</label>
          <input type="number" id="f-age" placeholder="e.g. 30" min="18" max="80">
        </div>
        <div class="form-group">
          <label class="form-label">Gender</label>
          <select id="f-gender" style="padding:8px 10px;background:var(--bg2);border:1px solid var(--border);border-radius:6px;color:var(--text1);width:100%">
            <option value="">— Select —</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Contact Number</label>
          <input type="text" id="f-contact" placeholder="e.g. 09171234567">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Address</label>
          <textarea id="f-address" class="form-control" placeholder="e.g. Naga City, Camarines Sur" rows="3"></textarea>
        </div>
      </div>
      <div class="form-group" id="password-group">
        <label id="lbl-password" class="form-label">Temporary Password *</label>
        <input type="text" id="f-password" placeholder="Min 8 characters">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('teacher-modal')">Cancel</button>
      <button class="btn btn-primary" id="modal-save-btn" onclick="saveTeacher()">Create Teacher</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="delete-modal">
  <div class="modal" style="max-width:400px">
    <div class="confirm-body">
      <div class="confirm-icon">⚠️</div>
      <div class="confirm-text">Remove this teacher?</div>
      <div class="confirm-sub" id="delete-confirm-text">Their assigned subjects and sections will need to be reassigned.</div>
      <div class="confirm-actions">
        <button class="btn btn-ghost" onclick="closeModal('delete-modal')">Cancel</button>
        <button class="btn btn-danger" onclick="confirmDelete()">Remove Teacher</button>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="logout-modal">
  <div class="modal" style="max-width:400px">
    <div class="confirm-body">
      <div class="confirm-icon">🔒</div>
      <div class="confirm-text">Logout from ExceLearn?</div>
      <div class="confirm-sub">You will be redirected to login page.</div>
      <div class="confirm-actions">
        <button class="btn btn-ghost" onclick="closeModal('logout-modal')">Cancel</button>
        <button class="btn btn-danger" onclick="window.location.href='../logout.php'">Logout</button>
      </div>
    </div>
  </div>
</div>
<!-- CSV IMPORT MODAL -->
<div class="modal-overlay" id="csv-modal">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <div class="modal-title">📤 Import Teachers via CSV</div>
      <button class="modal-close" onclick="closeModal('csv-modal')">✕</button>
    </div>
    <div class="modal-body">
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:14px 16px;margin-bottom:16px;font-size:13px;color:var(--text2);line-height:1.8">
        <strong style="color:var(--text1)">📋 CSV Column Guide</strong><br>
        <strong>Required:</strong>
        <code style="background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:1px 6px;font-size:11px;color:var(--cyan)">firstname</code>
        <code style="background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:1px 6px;font-size:11px;color:var(--cyan)">middlename</code>
        <code style="background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:1px 6px;font-size:11px;color:var(--cyan)">lastname</code>
        <code style="background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:1px 6px;font-size:11px;color:var(--cyan)">email</code>
        <code style="background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:1px 6px;font-size:11px;color:var(--cyan)">password</code>
        <code style="background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:1px 6px;font-size:11px;color:var(--cyan)">age</code>
        <code style="background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:1px 6px;font-size:11px;color:var(--cyan)">gender</code>
        <code style="background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:1px 6px;font-size:11px;color:var(--cyan)">contact_number</code>
        <code style="background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:1px 6px;font-size:11px;color:var(--cyan)">address</code><br>
        <ul style="margin:6px 0 0 18px;padding:0">
          <li><strong>firstname, lastname, email, password</strong> are required; rest are optional</li>
          <li>Gender accepted values: <em>Male</em> or <em>Female</em></li>
          <li>Rows with duplicate email are automatically skipped</li>
          <li>All imported teachers are set to <strong>Active</strong> by default</li>
        </ul>
      </div>
      <div style="margin-bottom:14px">
        <button class="btn btn-ghost btn-xs" onclick="downloadTeacherCSVTemplate()" style="font-size:12px;display:inline-flex;align-items:center;gap:5px">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Download CSV Template
        </button>
      </div>
      <div class="form-group">
        <label class="form-label">Select CSV File *</label>
        <input type="file" id="teacher-csv-input" accept=".csv"
          style="padding:8px;background:var(--bg2);border:1px solid var(--border);border-radius:6px;color:var(--text1);width:100%;box-sizing:border-box;cursor:pointer">
      </div>
      <div id="teacher-csv-preview-wrap" style="display:none;margin-top:4px">
        <label class="form-label">Preview (first 5 data rows)</label>
        <div id="teacher-csv-preview-table"
          style="overflow-x:auto;font-size:11.5px;border:1px solid var(--border);border-radius:6px;max-height:180px;overflow-y:auto"></div>
      </div>
      <div style="height:4px;background:var(--bg3);border-radius:4px;overflow:hidden;margin-top:8px;display:none" id="teacher-csv-progress">
        <div style="height:100%;background:var(--green,#22c55e);border-radius:4px;width:0%;transition:width .4s ease" id="teacher-csv-progress-bar"></div>
      </div>
      <div id="teacher-csv-result" style="display:none;margin-top:14px;padding:12px 14px;border-radius:8px;font-size:13px;line-height:1.7"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('csv-modal')">Cancel</button>
      <button class="btn btn-primary" id="teacher-csv-import-btn" onclick="submitTeacherCSV()">Import Teachers</button>
    </div>
  </div>
</div>
<div class="toast-container" id="toast-container"></div>

<script>
var teachers = <?php echo $teachers_json; ?>;
var dbSubjects = <?php echo $subjects_json; ?>;
var teacherSubjectsMap = <?php echo $teacher_subjects_map_json; ?>;
var subjectAssignedMap = <?php echo $subject_assigned_map_json; ?>;

var nextId = teachers.length > 0 ? Math.max(...teachers.map(t=>t.id)) + 1 : 1;

var currentFilter = "All";
var currentPage = 1;
var perPage = 8;
var sortField = "name";
var sortDir = "asc";
var selectedIds = {};
var deleteTargetId = null;

var deptColors = {
  "General":"var(--accent)", "Computer Studies":"var(--accent)","Information Technology":"var(--cyan)",
  "Business Administration":"var(--amber)","Science":"var(--green)",
  "Mathematics":"var(--purple)","English & Humanities":"var(--rose)"
};
var statusColors = { "Active":"badge-green","Inactive":"badge-red" };

function getFiltered() {
  var q = document.getElementById("searchInput").value.toLowerCase().trim();
  var list = teachers.filter(function(t) {
  // FILTER LOGIC
  var matchDept = true;
  var matchStatus = true;

  // Department Filter
  if (
      currentFilter !== "All" &&
      currentFilter !== "Active" &&
      currentFilter !== "Inactive"
  ) {
      matchDept = (t.dept === currentFilter);
  }

  // Status Filter
  if (currentFilter === "Active" || currentFilter === "Inactive") {
      matchStatus = ((t.status || '').trim() === currentFilter);
  }

  if (!matchDept || !matchStatus) return false;
    if (!q) return true;
    var full = (t.rank + " " + t.first + " " + (t.middle ? t.middle + " " : "") + t.last + " " + t.email + " " + t.dept + " " + t.subjects.join(" ")).toLowerCase();
    return full.indexOf(q) !== -1;
  });
  list.sort(function(a, b) {
    // ✅ PENDING (is_verified === 0) always on top
    if (a.is_verified === 0 && b.is_verified !== 0) return -1;
    if (a.is_verified !== 0 && b.is_verified === 0) return 1;

    var va, vb;
    if (sortField === "name") {
        va = (a.first + " " + (a.middle || "") + " " + a.last).toLowerCase();
        vb = (b.first + " " + (b.middle || "") + " " + b.last).toLowerCase();
    }
    else if (sortField === "load") { va = a.load; vb = b.load; }
    else if (sortField === "status") { va = a.status; vb = b.status; }
    else { va = a.first; vb = b.first; }
    if (va < vb) return sortDir === "asc" ? -1 : 1;
    if (va > vb) return sortDir === "asc" ? 1 : -1;
    return 0;
  });
  return list;
}

function sortBy(field) {
  if (sortField === field) sortDir = sortDir === "asc" ? "desc" : "asc";
  else { sortField = field; sortDir = "asc"; }
  updateSortArrows(); currentPage = 1; render();
}
function updateSortArrows() {
  ["name","status"].forEach(function(f) { // Removed "load" from arrows list
    var el = document.getElementById("sort-" + f);
    if (!el) return;
    if (f === sortField) { el.textContent = sortDir === "asc" ? "↑" : "↓"; el.className = "sort-arrow " + sortDir; }
    else { el.textContent = "↕"; el.className = "sort-arrow"; }
  });
}
function applyFilters() { currentPage = 1; render(); }
function setFilter(f) { currentFilter = f; currentPage = 1; renderChips(); render(); }

function getLoadColor(load, max) {
  var pct = load / max;
  if (pct >= 1) return "var(--red)";
  if (pct >= 0.85) return "var(--amber)";
  return "var(--green)";
}

function renderTable(items) {
  var tbody = document.getElementById("table-body");
  if (teachers.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:60px 20px"><div style="font-size:40px;margin-bottom:12px">👨‍🏫</div><div style="font-size:15px;font-weight:600;color:var(--text1);margin-bottom:6px">No Teachers Added Yet</div><div style="font-size:13px;color:var(--text3)">Click <strong>+ Add Teacher</strong> or <strong>Import CSV</strong> to get started.</div></td></tr>';
    return;
  }
  if (items.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text3);font-size:14px">No teachers found.</td></tr>';
    return;
  }
  tbody.innerHTML = items.map(function(t) {
    
    var avatarHtml = t.profile_pic
      ? '<img src="' + t.profile_pic + '" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">'
      : (t.first.charAt(0) + t.last.charAt(0)).toUpperCase();

    var avatarWrapper = t.profile_pic
      ? avatarHtml
      : '<div class="tbl-avatar" style="background:' + t.color + '">' + avatarHtml + '</div>';

    var checked = selectedIds[t.id] ? " checked" : "";
    var displayName =
      (t.rank ? t.rank + " " : "") +
      t.first + " " +
      (t.middle ? t.middle + " " : "") +
      t.last;
    
    // Subject Badges
    var gradeStrandStr = "";
    if(t.grade_strands && t.grade_strands.length > 0){
        gradeStrandStr =
            '<div style="margin-top:4px;font-size:11px;color:var(--text3)">' +
            t.grade_strands.join('<br>') +
            '</div>';
    }
    if(t.subjects && t.subjects.length > 0) {
        subjectStr = '<div class="subj-list">' + t.subjects.map(function(s){ 
            return '<span class="badge-sub">'+s+'</span>'; 
        }).join('') + '</div>';
    } else {
        subjectStr = '<span style="color:var(--text3);font-size:12px">None</span>';
    }

    var statusClass = (statusColors[t.status]||"badge-blue");

    var actionsHtml;
    if (t.is_verified === 0) {
      actionsHtml =
        '<button class="btn btn-success btn-xs" onclick="approveTeacher(' + t.id + ')" title="Approve">✅ Approve</button>' +
        '<button class="btn btn-danger btn-xs" onclick="rejectTeacher(' + t.id + ')" title="Disapprove">🗑️ Disapprove</button>';
    } else {
      actionsHtml =
        '<button class="btn btn-ghost btn-xs" onclick="openEditModal(' + t.id + ')" title="Edit">✏️</button>' +
        '<button class="btn btn-ghost btn-xs" onclick="toggleStatus(' + t.id + ')" title="Toggle">' + (((t.status || "").trim() === "Active") ? "🚫" : "✅") + '</button>' +
        '<button class="btn btn-danger btn-xs" onclick="openDeleteModal(' + t.id + ')" title="Remove">🗑️</button>';
    }
    
    return '<tr>' +
      '<td><div class="custom-check' + checked + '" onclick="toggleCheck(' + t.id + ')"></div></td>' +
      
        '<td><div class="tbl-user">' + avatarWrapper + 
        '<div><div class="tbl-name expand-trigger" onclick="toggleExpand(' + t.id + ')">' + displayName + '</div>' +
        '<div class="tbl-email">' + t.email + '</div></div></div></td>'
         +
      '<td style="font-size:12px;color:var(--text2);max-width:220px;line-height:1.8">' +
        (t.gender ? '<div>⚧ <b>Gender:</b> ' + t.gender + '</div>' : '') +
        (t.age    ? '<div>🎂 <b>Age:</b> ' + t.age + '</div>' : '') +
        (t.contact_number ? '<div>📞 <b>Contact:</b> ' + t.contact_number + '</div>' : '') +
        (t.address ? '<div style="white-space:normal">📍 <b>Address:</b> ' + t.address + '</div>' : '') +
        (!t.gender && !t.age && !t.contact_number && !t.address ? '<span style="color:var(--text3)">No info available</span>' : '') +
      '</td>' +
      // LOAD COLUMN REMOVED
      '<td><span class="badge ' + statusClass + '">' + (t.status||'N/A') + '</span></td>' +
      '<td><div class="tbl-actions">' + actionsHtml + '</div></td>' +
    '</tr>' +
    '<tr id="expand-' + t.id + '" style="display:none"><td colspan="5" style="padding:0"><div class="user-expand" id="expand-inner-' + t.id + '">' + buildDetail(t) + '</div></td></tr>'; // Updated colspan to 5
  }).join('');
}

function buildDetail(t) {
  var displayName =
    (t.rank ? t.rank + " " : "") +
    t.first + " " +
    (t.middle ? t.middle + " " : "") +
    t.last;
    var avatarHtml = t.profile_pic
    ? '<img src="' + t.profile_pic + '" style="width:56px;height:56px;border-radius:50%;object-fit:cover;margin-bottom:10px;">'
      : '<div style="width:56px;height:56px;border-radius:50%;background:' + t.color + ';display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#fff;margin-bottom:10px;">' + (t.first.charAt(0) + t.last.charAt(0)).toUpperCase() + '</div>';
    
    var html = '<div style="display:flex;flex-direction:column;align-items:center;margin-bottom:12px;">' + avatarHtml + '</div>' +
    '<div class="detail-row"><span class="dl">Full Name:</span><span class="dv">' + displayName + '</span></div>' +
    '<div class="detail-row"><span class="dl">Email:</span><span class="dv">' + t.email + '</span></div>' +
    (t.age    ? '<div class="detail-row"><span class="dl">Age:</span><span class="dv">' + t.age + '</span></div>' : '') +
    (t.gender ? '<div class="detail-row"><span class="dl">Gender:</span><span class="dv">' + t.gender + '</span></div>' : '') +
    (t.contact_number ? '<div class="detail-row"><span class="dl">Contact:</span><span class="dv">' + t.contact_number + '</span></div>' : '') +
    (t.address ? '<div class="detail-row"><span class="dl">Address:</span><span class="dv">' + t.address + '</span></div>' : '') +
    '<div class="detail-row"><span class="dl">Status:</span><span class="dv">' + (t.status||'Unknown') + '</span></div>' +
    '<div class="detail-row"><span class="dl">Joined:</span><span class="dv">' + t.joined + '</span></div>' +
    '<div style="margin-top:8px"><span class="dl" style="font-size:12px;color:var(--text3);font-weight:500">Assigned Subjects:</span><div class="subjects-tags" style="margin-top:4px">' + t.subjects.map(function(s){return '<span class="subject-tag">' + s + '</span>';}).join("") + '</div></div>';
  return html;
}

function toggleExpand(id) {
  var row = document.getElementById("expand-" + id);
  var inner = document.getElementById("expand-inner-" + id);
  if (!row) return;
  if (row.style.display === "none") { row.style.display = ""; inner.classList.add("open"); }
  else { row.style.display = "none"; inner.classList.remove("open"); }
}

function renderMobile(items) {
  var el = document.getElementById("mobile-list");
  if (teachers.length === 0) { el.innerHTML = '<div style="text-align:center;padding:60px 20px"><div style="font-size:40px;margin-bottom:12px">👨‍🏫</div><div style="font-size:15px;font-weight:600;color:var(--text1);margin-bottom:6px">No Teachers Added Yet</div><div style="font-size:13px;color:var(--text3)">Click <strong>+ Add Teacher</strong> or <strong>Import CSV</strong> to get started.</div></div>'; return; }
  if (items.length === 0) { el.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3);font-size:14px">No teachers found.</div>'; return; }
  el.innerHTML = items.map(function(t) {
    var avatarHtml = t.profile_pic
      ? '<img src="' + t.profile_pic + '" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">'
      : (t.first.charAt(0) + t.last.charAt(0)).toUpperCase();

    var displayName = (t.rank ? t.rank + " " : "") + t.first + " " + (t.middle ? t.middle + " " : "") + t.last;
    var statusBadge = statusColors[t.status] || "badge-blue";
    var grouped = {};
    
    // ✅ REAL GROUPING USING dbSubjects
    if (t.subjects && t.subjects.length > 0) {
    
      t.subjects.forEach(function(subName) {
    
        var match = dbSubjects.find(function(s){
          return s.subject === subName;
        });
    
        if (!match) return;
    
        var key = match.grade_level + ' - ' + match.strand;
    
        if (!grouped[key]) grouped[key] = [];
    
        grouped[key].push(subName);
      });
    }

    // BUILD HTML
    var subjectStr = '';
    
    if (Object.keys(grouped).length > 0) {
    
      Object.keys(grouped).forEach(function(key){
    
        subjectStr +=
          '<div style="margin-top:8px;">' +
    
            '<div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:4px;">' +
            '📚 ' + key +
            '</div>' +
    
            '<div style="font-size:12px;margin-left:10px;">' +
            grouped[key].join(' &nbsp;&nbsp; ') +
            '</div>' +
    
          '</div>';
      });
    
    } else {
      subjectStr = '<span style="color:var(--text3);font-size:11px">No subjects assigned</span>';
    }
    var avatarBlock = t.profile_pic
    ? avatarHtml
    : '<div class="tc-avatar" style="background:' + t.color + '">' + avatarHtml + '</div>';

    return '<div class="t-card">' +

      '<div style="display:flex; gap:10px; align-items:flex-start;">' +
    
        avatarBlock +
    
        '<div class="tc-info" style="flex:1;min-width:0;">' +
    
          '<div class="tc-name">' + displayName + '</div>' +
    
          '<div class="tc-badges">' +
            '<span class="badge ' + statusBadge + '">' + (t.status || 'N/A') + '</span>' +
          '</div>' +
    
          '<div class="tc-meta"><span>📧 ' + t.email + '</span></div>' +
            '<div style="margin-top:6px;font-size:12px;color:var(--text2);line-height:1.8">' +
              (t.gender ? '<div>⚧ <b>Gender:</b> ' + t.gender + '</div>' : '') +
              (t.age    ? '<div>🎂 <b>Age:</b> ' + t.age + '</div>' : '') +
              (t.contact_number ? '<div>📞 <b>Contact:</b> ' + t.contact_number + '</div>' : '') +
              (t.address ? '<div style="white-space:normal">📍 <b>Address:</b> ' + t.address + '</div>' : '') +
              (!t.gender && !t.age && !t.contact_number && !t.address ? '<span style="color:var(--text3)">No info available</span>' : '') +
            '</div>' +
        '</div>' +
    
        // ✅ ACTION BUTTONS INSIDE CARD (RIGHT SIDE)
        '<div class="tc-actions">' + (function(){
          if (t.is_verified === 0) {
            return '<button class="btn btn-success btn-xs w-100 mb-1" onclick="approveTeacher(' + t.id + ')">✅ Approve</button>' +
            '<button class="btn btn-danger btn-xs w-100" onclick="rejectTeacher(' + t.id + ')">🗑️ Disapprove</button>';
          }
          return '<button class="btn btn-ghost btn-xs" onclick="openEditModal(' + t.id + ')">✏️</button>' +
            '<button class="btn btn-ghost btn-xs" onclick="toggleStatus(' + t.id + ')">' + ((t.status || '').trim() === 'Active' ? '🚫' : '✅') + '</button>' +
            '<button class="btn btn-danger btn-xs" onclick="openDeleteModal(' + t.id + ')">🗑️</button>';
        })() + '</div>' +
    
      '</div>' +
    
    '</div>';
  }).join('');
}

function renderPagination(total, totalPages) {
  var el = document.getElementById("pagination");
  var start = (currentPage - 1) * perPage + 1;
  var end = Math.min(currentPage * perPage, total);
  if (total === 0) start = 0;
  var btns = '<button class="page-btn" onclick="goPage(' + (currentPage-1) + ')"' + (currentPage<=1?' disabled':'') + '>‹</button>';
  var pages = [];
  if (totalPages <= 5) { for (var i=1;i<=totalPages;i++) pages.push(i); }
  else { pages.push(1); if (currentPage>3) pages.push('...'); for (var j=Math.max(2,currentPage-1);j<=Math.min(totalPages-1,currentPage+1);j++) pages.push(j); if (currentPage<totalPages-2) pages.push('...'); pages.push(totalPages); }
  pages.forEach(function(p) { if (p==='...') btns+='<div class="page-dots">…</div>'; else btns+='<button class="page-btn'+(p===currentPage?' active':'')+'" onclick="goPage('+p+')">'+p+'</button>'; });
  btns += '<button class="page-btn" onclick="goPage(' + (currentPage+1) + ')"' + (currentPage>=totalPages?' disabled':'') + '>›</button>';
  el.innerHTML = '<div class="pagination-info">Showing ' + start + '–' + end + ' of ' + total + ' teachers</div><div class="pagination-btns">' + btns + '</div>';
}

function goPage(p) {
  var filtered = getFiltered();
  var totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  if (p < 1 || p > totalPages) return;
  currentPage = p; render();
  document.querySelector('.card').scrollIntoView({behavior:'smooth',block:'start'});
}

function renderChips() {
  var filters = ["All", "Active", "Inactive", "Pending"];
  var el = document.getElementById("filter-chips");
  el.innerHTML = filters.map(function(f) {
    var count;
    if (f === "All") {
      count = teachers.length;
    } else if (f === 'Pending') {
      count = teachers.filter(function(t){
        return t.is_verified === 0;
      }).length;
    } else {
      count = teachers.filter(function(t){
        return ((t.status || '').trim() === f && t.is_verified === 1);
      }).length;
    }
    return '<div class="chip' + (currentFilter === f ? ' active' : '') + '" onclick="setFilter(\'' + f + '\')">' + f + ' <span style="opacity:.6;margin-left:2px">' + count + '</span></div>';
  }).join('');
}

function renderMiniStats() {
   var total = teachers.filter(function(t){ return t.is_verified === 1; }).length;
      var active = teachers.filter(function(t){
          return t.is_verified === 1 && (t.status || '').trim().toLowerCase() === "active";
      }).length;
      var inactive = teachers.filter(function(t){
          return t.is_verified === 1 && (t.status || '').trim().toLowerCase() === "inactive";
      }).length;
  
  var el = document.getElementById("mini-stats");

    var pending = teachers.filter(function(t){
        return t.is_verified === 0;
    }).length;

  el.innerHTML = 
    '<div class="mini-stat"><div class="mini-stat-icon" style="background:rgba(245,158,11,0.12)">⏳</div><div><div class="mini-stat-val" style="color:var(--amber)">' + pending + '</div><div class="mini-stat-lbl">Pending</div></div></div>'+
    '<div class="mini-stat"><div class="mini-stat-icon" style="background:var(--green-bg)">✅</div><div><div class="mini-stat-val" style="color:var(--green)">' + active + '</div><div class="mini-stat-lbl">Active</div></div></div>' +
    '<div class="mini-stat"><div class="mini-stat-icon" style="background:var(--red-bg)">🚫</div><div><div class="mini-stat-val" style="color:var(--red)">' + inactive + '</div><div class="mini-stat-lbl">Inactive</div></div></div>' +
    '<div class="mini-stat"><div class="mini-stat-icon" style="background:var(--purple-bg)">👨‍🏫</div><div><div class="mini-stat-val">' + total + '</div><div class="mini-stat-lbl">Total Teachers</div></div></div>';
}

/* ========== SELECTION ========== */
function toggleCheck(id) { selectedIds[id]=!selectedIds[id]; if(!selectedIds[id]) delete selectedIds[id]; updateBulkBar(); renderCheckAll(); render(); }
function toggleAll() {
  var filtered=getFiltered(); var start=(currentPage-1)*perPage; var pageItems=filtered.slice(start,start+perPage);
  var allChecked=pageItems.every(function(u){return selectedIds[u.id]});
  pageItems.forEach(function(u){ if(allChecked) delete selectedIds[u.id]; else selectedIds[u.id]=true; });
  updateBulkBar(); renderCheckAll(); render();
}
function renderCheckAll() {
  var filtered=getFiltered(); var start=(currentPage-1)*perPage; var pageItems=filtered.slice(start,start+perPage);
  var allChecked=pageItems.length>0&&pageItems.every(function(u){return selectedIds[u.id]});
  var el=document.getElementById("check-all"); if(allChecked) el.classList.add("checked"); else el.classList.remove("checked");
}
function updateBulkBar() {
  var count=Object.keys(selectedIds).length; var bar=document.getElementById("bulk-bar");
  document.getElementById("bulk-count").textContent=count;
  if(count>0) bar.classList.add("visible"); else bar.classList.remove("visible");
}
function clearSelection() { selectedIds={}; updateBulkBar(); renderCheckAll(); render(); }
function bulkAction(action) {
  var ids=Object.keys(selectedIds).map(Number);
  if(!ids.length) return;
  if(action==="activate") { ids.forEach(function(id){var t=teachers.find(function(x){return x.id===id});if(t)t.status="Active";}); showToast(ids.length+" teacher(s) activated","success"); }
  else if(action==="deactivate") { ids.forEach(function(id){var t=teachers.find(function(x){return x.id===id});if(t)t.status="Inactive";}); showToast(ids.length+" teacher(s) deactivated","info"); }
  else if(action==="export") { showToast("Exported "+ids.length+" teacher(s) to CSV","success"); }
  clearSelection(); renderChips(); render();
}

/* ========== TOGGLE STATUS ========== */
function toggleStatus(id) {

  var t = teachers.find(function(x){ return x.id === id; });
  if(!t) return;

  // DATABASE-BASED STATUS
  var currentStatus = (t.status || '').trim().toLowerCase();

  var newStatus = currentStatus === "active"
      ? "Inactive"
      : "Active";

  var formData = new FormData();
  formData.append('api_action', 'update_status');
  formData.append('teacher_db_id', id);
  formData.append('status', newStatus);

  fetch('admin_teachers.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {

    if(data.success) {

      // UPDATE LOCAL STATE
      t.status = newStatus;

      showToast(
        t.first + " " + t.last + " is now " + newStatus,
        newStatus === "Active" ? "success" : "info"
      );

      renderChips();
      render();

    } else {
      showToast(data.message || "Error updating status", "error");
    }

  })
  .catch(() => {
    showToast("Network error", "error");
  });
}

function approveTeacher(id) {
  var t = teachers.find(function(x){ return x.id === id; });
  if (!t) return;

  var btn = document.querySelector('button[onclick="approveTeacher(' + id + ')"]');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:5px"><svg style="animation:spin .7s linear infinite" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Approving…</span>';
  }

  var formData = new FormData();
  formData.append('api_action', 'approve');
  formData.append('teacher_db_id', id);

  fetch('admin_teachers.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        t.is_verified = 1;
        t.status = 'Active';
        showToast(data.message, 'success');
        renderChips();
        render();
      } else {
        if (btn) { btn.disabled = false; btn.innerHTML = '✅ Approve'; }
        showToast(data.message || 'Failed to approve teacher', 'error');
      }
    })
    .catch(() => {
      if (btn) { btn.disabled = false; btn.innerHTML = '✅ Approve'; }
      showToast('Network error', 'error');
    });
}
function rejectTeacher(id) {
  var t = teachers.find(function(x){ return x.id === id; });
  if (!t) return;

  if (!confirm("Disapprove and remove " + t.first + " " + t.last + "'s account? They will be notified via email and this cannot be undone.")) {
    return;
  }

  var btn = document.querySelector('button[onclick="rejectTeacher(' + id + ')"]');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:5px"><svg style="animation:spin .7s linear infinite" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Removing…</span>';
  }

  var formData = new FormData();
  formData.append('api_action', 'disapprove');
  formData.append('teacher_db_id', id);

  fetch('admin_teachers.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 1000);
      } else {
        if (btn) { btn.disabled = false; btn.innerHTML = '🗑️ Disapprove'; }
        showToast(data.message || 'Failed to disapprove teacher', 'error');
      }
    })
    .catch(() => {
      if (btn) { btn.disabled = false; btn.innerHTML = '🗑️ Disapprove'; }
      showToast('Network error', 'error');
    });
}
/* ========== MODALS ========== */
function openModal(id) { document.getElementById(id).classList.add("open"); document.body.style.overflow="hidden"; }
function closeModal(id) { document.getElementById(id).classList.remove("open"); document.body.style.overflow=""; }
function openLogoutModal() { openModal("logout-modal"); }

function openAddModal() {
  document.getElementById("modal-title").textContent="Add New Teacher";
  document.getElementById("modal-save-btn").textContent="Create Teacher";
  document.getElementById("edit-id").value="";
    ["f-first","f-middle","f-last","f-email","f-age","f-contact","f-address","f-password"].forEach(function(id){document.getElementById(id).value="";});
      document.getElementById("f-gender").value = "";
  
  // Password Logic
  document.getElementById("password-group").style.display="";
  document.getElementById("lbl-password").textContent="Temporary Password *";
  document.getElementById("f-password").placeholder="Min 8 characters";
  document.getElementById("f-password").required = true;
  
  openModal("teacher-modal");
}

function openEditModal(id) {
  var t=teachers.find(function(x){return x.id===id}); if(!t) return;
  document.getElementById("modal-title").textContent="Edit Teacher — "+t.first+" "+t.last;
  document.getElementById("modal-save-btn").textContent="Save Changes";
  document.getElementById("edit-id").value=t.id;
  document.getElementById("f-first").value = t.first;
  document.getElementById("f-middle").value = t.middle || "";
  document.getElementById("f-last").value = t.last;
  document.getElementById("f-email").value = t.email;
  document.getElementById("f-age").value = t.age || "";
  document.getElementById("f-gender").value = t.gender || "";
  document.getElementById("f-contact").value = t.contact_number || "";
  document.getElementById("f-address").value = t.address || "";
  
  // Password Logic (Optional)
  document.getElementById("password-group").style.display=""; // Visible
  document.getElementById("lbl-password").textContent="New Password (Optional)"; // Label change
  document.getElementById("f-password").placeholder="Leave blank to keep current"; // Hint
  document.getElementById("f-password").value=""; // Clear value
  document.getElementById("f-password").required = false; // Not required

  openModal("teacher-modal");
}

function loadSubjectsCheckboxes(selectedIds) {
    var container = document.getElementById("subjects-container");
    container.innerHTML = "";
    var editingId = parseInt(document.getElementById("edit-id").value) || 0;

    if (dbSubjects.length === 0) {
        container.innerHTML = '<span style="color:var(--text3);font-size:13px">No subjects available.</span>';
        updateSubjCounter();
        return;
    }

    var grouped = {};
    dbSubjects.forEach(function(sub) {
        // Check if this subject is assigned to another teacher
        var assignedTo = subjectAssignedMap[sub.subject_id] || [];
        var takenByOther = assignedTo.some(function(tid) { return tid !== editingId; });
        if (takenByOther) return; // Skip — already assigned to someone else

        var gradeKey = sub.grade_level || 'Unknown';
        var strandKey = sub.strand || 'Unknown';
        if (!grouped[gradeKey]) grouped[gradeKey] = {};
        if (!grouped[gradeKey][strandKey]) grouped[gradeKey][strandKey] = [];
        grouped[gradeKey][strandKey].push(sub);
    });

    var html = '';
    var hasAny = false;

    Object.keys(grouped).sort().forEach(function(grade) {
        html += '<div style="grid-column:1/-1;margin-top:8px;margin-bottom:2px;">' +
            '<span style="font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.5px">📚 ' + grade + '</span>' +
        '</div>';

        Object.keys(grouped[grade]).sort().forEach(function(strand) {
            html += '<div style="grid-column:1/-1;margin-top:4px;margin-bottom:2px;">' +
                '<span style="font-size:11px;color:var(--text2);font-weight:500">▸ ' + strand + ' (' + grouped[grade][strand][0].track + ')</span>' +
            '</div>';

            grouped[grade][strand].forEach(function(sub) {
                hasAny = true;
                var isChecked = selectedIds.indexOf(sub.subject_id) !== -1 ? 'checked' : '';
                html += '<label class="check-item">' +
                    '<input type="checkbox" name="subjects[]" value="' + sub.subject_id + '" ' + isChecked + ' onchange="updateSubjCounter()">' +
                    '<span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="' + sub.subject + '">' + sub.subject + '</span>' +
                '</label>';
            });
        });
    });

    if (!hasAny) {
        html = '<span style="color:var(--text3);font-size:13px">All subjects are already assigned to other teachers.</span>';
    }

    container.innerHTML = html;
    updateSubjCounter();
}

function updateSubjCounter() {
    var checked = document.querySelectorAll('input[name="subjects[]"]:checked').length;
    var el = document.getElementById("subj-counter");
    if (el) el.textContent = "(" + checked + " selected)";
}

function saveTeacher() {
  var editId=document.getElementById("edit-id").value;
  var first=document.getElementById("f-first").value.trim();
  var middle=document.getElementById("f-middle").value.trim();
  var last=document.getElementById("f-last").value.trim();
  var email=document.getElementById("f-email").value.trim();
  var password=document.getElementById("f-password").value;

  if(!first||!last||!email) { showToast("Please fill in all required fields","error"); return; }
  if(!editId && password.length<8) { showToast("Password must be at least 8 characters","error"); return; }

  var saveBtn = document.getElementById("modal-save-btn");
  var originalBtnText = saveBtn.textContent;
  saveBtn.disabled = true;
  saveBtn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:6px"><svg style="animation:spin .7s linear infinite" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Saving…</span>';

  var age     = document.getElementById("f-age").value.trim();
  var gender  = document.getElementById("f-gender").value;
  var contact = document.getElementById("f-contact").value.trim();
  var address = document.getElementById("f-address").value.trim();

  var formData = new FormData();
  formData.append('api_action', editId ? 'update' : 'create');
  formData.append('teacher_db_id', editId);
  formData.append('firstname', first);
  formData.append('middlename', middle);
  formData.append('lastname', last);
  formData.append('email', email);
  formData.append('age', age);
  formData.append('gender', gender);
  formData.append('contact_number', contact);
  formData.append('address', address);

  if(password.length > 0){
    formData.append('password', password);
  }

  fetch('admin_teachers.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if(data.success) {
      var msg = editId ? "Teacher updated" : "Teacher added";
      showToast(msg, "success");
      closeModal("teacher-modal");
      setTimeout(() => location.reload(), 1000);
    } else {
      saveBtn.disabled = false;
      saveBtn.innerHTML = originalBtnText;
      showToast(data.message, "error");
    }
  })
  .catch(e => {
    saveBtn.disabled = false;
    saveBtn.innerHTML = originalBtnText;
    showToast("Network error", "error");
  });
}

function openDeleteModal(id) {
  deleteTargetId=id; var t=teachers.find(function(x){return x.id===id}); if(!t) return;
  document.getElementById("delete-confirm-text").textContent="Are you sure you want to remove "+(t.rank?t.rank+" ":"")+
  t.first+" "+
  (t.middle ? t.middle + " " : "")+
  t.last+"? Their "+t.subjects.length+" subject(s) will need reassignment.";
  openModal("delete-modal");
}
function confirmDelete() {
  if(deleteTargetId===null) return;
  
  var formData = new FormData();
  formData.append('api_action', 'delete');
  formData.append('teacher_db_id', deleteTargetId);

  fetch('admin_teachers.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if(data.success) {
      showToast(data.message, "error");
      closeModal("delete-modal");
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast(data.message, "error");
    }
  })
  .catch(e => showToast("Network error", "error"));
}

/* ========== TOAST ========== */
function showToast(msg,type) {
  type=type||"success"; var icons={success:"✅",error:"❌",info:"ℹ️"};
  var container=document.getElementById("toast-container"); var t=document.createElement("div");
  t.className="toast "+type; t.innerHTML='<span class="toast-icon">'+(icons[type]||"✅")+'</span><span>'+msg+'</span>';
  container.appendChild(t);
  setTimeout(function(){t.style.opacity="0";t.style.transform="translateX(60px)";t.style.transition="all .3s ease";setTimeout(function(){t.remove()},300)},3000);
}

/* ========== SIDEBAR ========== */
var sidebar=document.getElementById("sidebar"),overlay=document.getElementById("sidebar-overlay"),menuBtn=document.getElementById("menuBtn");
function openSidebar(){sidebar.classList.add("open");overlay.classList.add("open");document.body.style.overflow="hidden"}
function closeSidebar(){sidebar.classList.remove("open");overlay.classList.remove("open");document.body.style.overflow=""}
menuBtn.addEventListener("click",function(){if(sidebar.classList.contains("open"))closeSidebar();else openSidebar()});
overlay.addEventListener("click",closeSidebar);
document.querySelectorAll(".nav-item").forEach(function(item){item.addEventListener("click",function(){if(window.innerWidth<=768)closeSidebar()})});
document.addEventListener("keydown",function(e){if(e.key==="Escape"){closeSidebar();closeModal("teacher-modal");closeModal("delete-modal");closeModal("logout-modal");closeModal("csv-modal")}});
document.querySelectorAll(".modal-overlay").forEach(function(ov){ov.addEventListener("click",function(e){if(e.target===ov)closeModal(ov.id)})});

/* ========== THEME ========== */
var savedTheme = '<?= htmlspecialchars($savedTheme) ?>';
localStorage.setItem('excelearn-theme', savedTheme);
if (savedTheme === 'light') document.body.classList.add('light');
/* ========== RESIZE ========== */
var resizeTimer; window.addEventListener("resize",function(){clearTimeout(resizeTimer);resizeTimer=setTimeout(function(){if(window.innerWidth>768)closeSidebar()},150)});

/* ========== CSV IMPORT ========== */
function openCSVModal() {
  document.getElementById('teacher-csv-input').value = '';
  document.getElementById('teacher-csv-preview-wrap').style.display = 'none';
  document.getElementById('teacher-csv-preview-table').innerHTML = '';
  document.getElementById('teacher-csv-result').style.display = 'none';
  document.getElementById('teacher-csv-progress').style.display = 'none';
  document.getElementById('teacher-csv-progress-bar').style.width = '0%';
  var btn = document.getElementById('teacher-csv-import-btn');
  btn.disabled = false;
  btn.textContent = 'Import Teachers';
  openModal('csv-modal');
}

document.getElementById('teacher-csv-input').addEventListener('change', function() {
  var file = this.files[0];
  if (!file) return;
  if (file.name.split('.').pop().toLowerCase() !== 'csv') {
    showToast('Please select a valid .csv file.', 'error');
    this.value = '';
    return;
  }
  var reader = new FileReader();
  reader.onload = function(e) {
    var lines = e.target.result.split(/\r?\n/).filter(l => l.trim() !== '');
    if (lines.length < 2) {
      document.getElementById('teacher-csv-preview-wrap').style.display = 'none';
      showToast('CSV appears to have no data rows.', 'error');
      return;
    }
    function parseCSVLine(line) {
      var result = [], cur = '', inQuote = false;
      for (var i = 0; i < line.length; i++) {
        var ch = line[i];
        if (ch === '"') { inQuote = !inQuote; }
        else if (ch === ',' && !inQuote) { result.push(cur.trim()); cur = ''; }
        else { cur += ch; }
      }
      result.push(cur.trim());
      return result;
    }
    var preview = lines.slice(0, 6);
    var table = '<table style="border-collapse:collapse;width:100%;white-space:nowrap">';
    preview.forEach(function(line, idx) {
      var cells = parseCSVLine(line);
      table += '<tr style="background:' + (idx === 0 ? 'var(--bg3)' : (idx % 2 === 0 ? 'var(--bg2)' : 'var(--bg1,var(--bg))')) + '">';
      cells.forEach(function(cell) {
        var tag = idx === 0 ? 'th' : 'td';
        table += '<' + tag + ' style="padding:5px 10px;border:1px solid var(--border);font-weight:' + (idx===0?'600':'400') + ';color:var(--text' + (idx===0?'1':'2') + ')">' + (cell || '&nbsp;') + '</' + tag + '>';
      });
      table += '</tr>';
    });
    table += '</table>';
    document.getElementById('teacher-csv-preview-table').innerHTML = table;
    document.getElementById('teacher-csv-preview-wrap').style.display = 'block';
    document.getElementById('teacher-csv-result').style.display = 'none';
  };
  reader.readAsText(file);
});

function submitTeacherCSV() {
  var fileInput = document.getElementById('teacher-csv-input');
  if (!fileInput.files.length) { showToast('Please select a CSV file first.', 'error'); return; }
  var btn = document.getElementById('teacher-csv-import-btn');
  btn.disabled = true;
  btn.textContent = 'Importing…';
  var progressWrap = document.getElementById('teacher-csv-progress');
  var progressBar  = document.getElementById('teacher-csv-progress-bar');
  progressWrap.style.display = 'block';
  var prog = 0;
  var progInterval = setInterval(function() { prog = Math.min(prog + 2, 85); progressBar.style.width = prog + '%'; }, 80);
  var formData = new FormData();
  formData.append('api_action', 'csv_import');
  formData.append('csv_file', fileInput.files[0]);
  fetch('admin_teachers.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      clearInterval(progInterval);
      progressBar.style.width = '100%';
      setTimeout(() => { progressWrap.style.display = 'none'; progressBar.style.width = '0%'; }, 600);
      btn.disabled = false;
      btn.textContent = 'Import Teachers';
      var resultEl = document.getElementById('teacher-csv-result');
      resultEl.style.display = 'block';
      if (data.success) {
        var html = '<div style="color:var(--green,#22c55e);font-weight:600;font-size:14px;margin-bottom:8px">✅ ' + data.message + '</div>';
        html += '<div style="color:var(--text2);font-size:13px">Inserted: <strong style="color:var(--text1)">' + data.inserted + '</strong> &nbsp;|&nbsp; Skipped: <strong style="color:var(--text1)">' + data.skipped + '</strong></div>';
        if (data.errors && data.errors.length > 0) {
          html += '<div style="margin-top:10px;font-size:12px;color:var(--amber,#f59e0b);font-weight:500">⚠️ Warnings / Skipped rows:</div>';
          html += '<div style="margin-top:4px;max-height:120px;overflow-y:auto;font-size:11.5px;color:var(--text3);line-height:1.6">';
          data.errors.forEach(function(e) { html += '<div style="padding:1px 0">• ' + e + '</div>'; });
          html += '</div>';
        }
        resultEl.style.background = 'rgba(34,197,94,0.08)';
        resultEl.style.border = '1px solid var(--green,#22c55e)';
        resultEl.innerHTML = html;
        if (data.inserted > 0) setTimeout(() => location.reload(), 2200);
      } else {
        resultEl.style.background = 'rgba(239,68,68,0.08)';
        resultEl.style.border = '1px solid var(--rose,#ef4444)';
        resultEl.innerHTML = '<div style="color:var(--rose,#ef4444);font-weight:600">❌ ' + (data.message || 'Import failed.') + '</div>';
      }
    })
    .catch(() => {
      clearInterval(progInterval);
      progressWrap.style.display = 'none';
      btn.disabled = false;
      btn.textContent = 'Import Teachers';
      showToast('Network error during import.', 'error');
    });
}

function downloadTeacherCSVTemplate() {
  var header = 'firstname,middlename,lastname,email,password,age,gender,contact_number,address';
  var rows = [
    'Angela,Santos,Reyes,angela.reyes@excelsior.edu.ph,password123,28,Female,09171234567,Naga City Camarines Sur',
    'Juan,Dela,Cruz,juan.cruz@excelsior.edu.ph,password456,35,Male,,'
  ];
  var csv = header + '\n' + rows.join('\n');
  var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  var a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'teachers_import_template.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  showToast('Template downloaded!', 'success');
}

function render() {
  var filtered = getFiltered();
  var totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  if (currentPage > totalPages) currentPage = totalPages;
  var start = (currentPage - 1) * perPage;
  var pageItems = filtered.slice(start, start + perPage);
  renderTable(pageItems);
  renderMobile(pageItems);
  renderPagination(filtered.length, totalPages);
  renderMiniStats();
  updateBulkBar();
}
renderChips();
renderMiniStats();
render();
</script>
</body>
</html>