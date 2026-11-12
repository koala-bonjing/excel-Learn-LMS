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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_action'])) {

    $action = $_POST['api_action'];
    $response = ['success' => false, 'message' => 'Invalid request'];

    if ($action === 'create') {

        $first          = trim($_POST['firstname'] ?? '');
        $middle         = trim($_POST['middlename'] ?? '');
        $last           = trim($_POST['lastname'] ?? '');
        $suffix         = trim($_POST['suffix'] ?? '');
        $lrn            = trim($_POST['lrn'] ?? '');
        $birthdate      = trim($_POST['birthdate'] ?? '');
        $age            = intval($_POST['age'] ?? 0);
        $gender         = trim($_POST['gender'] ?? '');
        $contact        = trim($_POST['contact_number'] ?? '');
        $address        = trim($_POST['address'] ?? '');
        $section_id     = intval($_POST['section_id'] ?? 0);
        $strand_id      = intval($_POST['strand_id'] ?? 0);
        $password       = trim($_POST['password'] ?? '');

        $stud_id_input  = trim($_POST['student_id_code'] ?? '');

        $selected_subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];

        $status = 'Active';

        if (
            empty($first) ||
            empty($last) ||
            empty($lrn) ||
            empty($birthdate) ||
            empty($gender) ||
            empty($contact) ||
            empty($address) ||
            empty($section_id) ||
            empty($stud_id_input) ||
            empty($password)
        ) {

            $response['message'] = "All required fields are required.";

        } else {

            $checkSec = $conn->prepare("SELECT section_id FROM tblsections WHERE section_id = ?");
            $checkSec->bind_param("i", $section_id);
            $checkSec->execute();

            $secRes = $checkSec->get_result();

            if ($secRes->num_rows == 0) {

                $response['message'] = "Invalid section selected.";

            } else {

                $checkLRN = $conn->prepare("SELECT id FROM tblstudents WHERE lrn = ?");
                $checkLRN->bind_param("s", $lrn);
                $checkLRN->execute();

                if ($checkLRN->get_result()->num_rows > 0) {

                    $response['message'] = "LRN already exists.";
                    echo json_encode($response);
                    exit();
                }

                $checkLRN->close();

                $stud_id = $stud_id_input;

                $checkID = $conn->prepare("SELECT id FROM tblstudents WHERE student_id = ?");
                $checkID->bind_param("s", $stud_id);
                $checkID->execute();

                if ($checkID->get_result()->num_rows > 0) {
                    $response['message'] = "Student ID already exists.";
                    echo json_encode($response);
                    exit();
                }

                $checkID->close();
                $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                    INSERT INTO tblstudents (
                        student_id,
                        section_id,
                        password,
                        firstname,
                        middlename,
                        lastname,
                        suffix,
                        lrn,
                        birthdate,
                        age,
                        gender,
                        contact_number,
                        address,
                        status,
                        created_at
                    )
                    VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                    )
                ");
                
                $stmt->bind_param(
                    "sissssssissss",
                    $stud_id,
                    $section_id,
                    $hashed_pass,
                    $first,
                    $middle,
                    $last,
                    $suffix,
                    $lrn,
                    $birthdate,
                    $age,
                    $gender,
                    $contact,
                    $address,
                    $status
                );

                if ($stmt->execute()) {

                    $new_id = $stmt->insert_id;

                    $response['success'] = true;
                    $response['message'] = "Student added successfully.";

                } else {

                    $response['message'] = "Database error: " . $stmt->error;
                }

                $stmt->close();
            }

            $checkSec->close();
        }
    }

    elseif ($action === 'update') {

        $id             = intval($_POST['student_db_id'] ?? 0);
        $strand_id      = intval($_POST['strand_id'] ?? 0);
        $first          = trim($_POST['firstname'] ?? '');
        $middle         = trim($_POST['middlename'] ?? '');
        $last           = trim($_POST['lastname'] ?? '');
        $suffix         = trim($_POST['suffix'] ?? '');

        $lrn            = trim($_POST['lrn'] ?? '');
        $birthdate      = trim($_POST['birthdate'] ?? '');
        $age            = intval($_POST['age'] ?? 0);

        $gender         = trim($_POST['gender'] ?? '');
        $contact        = trim($_POST['contact_number'] ?? '');
        $address        = trim($_POST['address'] ?? '');

        $section_id     = intval($_POST['section_id'] ?? 0);

        $password       = trim($_POST['password'] ?? '');

        $stud_id_input  = trim($_POST['student_id_code'] ?? '');

        $selected_subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];

        if (
            $id < 1 ||
            empty($first) ||
            empty($last) ||
            empty($lrn) ||
            empty($birthdate) ||
            empty($gender) ||
            empty($contact) ||
            empty($address) ||
            empty($section_id)
        ) {

            $response['message'] = "Invalid data.";

        } else {

            $checkLRN = $conn->prepare("
                SELECT id FROM tblstudents
                WHERE lrn = ? AND id != ?
            ");

            $checkLRN->bind_param("si", $lrn, $id);
            $checkLRN->execute();

            if ($checkLRN->get_result()->num_rows > 0) {

                $response['message'] = "LRN already exists.";
                echo json_encode($response);
                exit();
            }

            $checkLRN->close();

            if (!empty($password)) {

                $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

                $sql = "
                    UPDATE tblstudents SET
                        firstname = ?,
                        middlename = ?,
                        lastname = ?,
                        suffix = ?,
                        lrn = ?,
                        birthdate = ?,
                        age = ?,
                        gender = ?,
                        contact_number = ?,
                        address = ?,
                        section_id = ?,
                        password = ?,
                        student_id = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ";
                
                $stmt = $conn->prepare($sql);
                
                $stmt->bind_param(
                    "ssssssississi",
                    $first,
                    $middle,
                    $last,
                    $suffix,
                    $lrn,
                    $birthdate,
                    $age,
                    $gender,
                    $contact,
                    $address,
                    $section_id,
                    $hashed_pass,
                    $stud_id_input,
                    $id
                );

            } else {

                $sql = "
                    UPDATE tblstudents SET
                        firstname = ?,
                        middlename = ?,
                        lastname = ?,
                        suffix = ?,
                        lrn = ?,
                        birthdate = ?,
                        age = ?,
                        gender = ?,
                        contact_number = ?,
                        address = ?,
                        section_id = ?,
                        student_id = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ";
                
                $stmt = $conn->prepare($sql);
                
                $stmt->bind_param(
                    "ssssssissisi",
                    $first,
                    $middle,
                    $last,
                    $suffix,
                    $lrn,
                    $birthdate,
                    $age,
                    $gender,
                    $contact,
                    $address,
                    $section_id,
                    $stud_id_input,
                    $id
                );
            }

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Student updated successfully.";
            } else {
                $response['message'] = "Database error: " . $stmt->error;
            }

            $stmt->close();
        }
    }

    elseif ($action === 'update_status') {
        
        $id = intval($_POST['student_db_id'] ?? 0);
        $status = trim($_POST['status'] ?? ''); 
        $strand_id = intval($_POST['strand_id'] ?? 0);
        if ($id > 0 && in_array($status, ['Active', 'Inactive'])) {

            $stmt = $conn->prepare("UPDATE tblstudents SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $status, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Student status updated.";
            } else {
                $response['message'] = "Failed to update status.";
            }
            $stmt->close();
        } else {
            $response['message'] = "Invalid status data.";
        }
    }

    elseif ($action === 'delete') {

        $id = intval($_POST['student_db_id'] ?? 0);

        if ($id < 1) {
            $response['message'] = "Invalid ID.";
        } else {

            $children = [
                "DELETE FROM tbl_students_settings WHERE student_id = ?",
                "DELETE FROM tblquiz_results WHERE student_id = ?",
                "DELETE FROM tblvirtualclass_participants WHERE student_id = ?",
                "DELETE FROM tbl_teachers_students WHERE student_id = ?",
            ];

            $ok = true;
            foreach ($children as $sql) {
                $c = $conn->prepare($sql);
                $c->bind_param("i", $id);
                if (!$c->execute()) {
                    $response['message'] = "Failed to delete related records: " . $c->error;
                    $ok = false;
                    $c->close();
                    break;
                }
                $c->close();
            }

            if ($ok) {
                $stmt = $conn->prepare("DELETE FROM tblstudents WHERE id = ?");
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Student deleted successfully.";
                } else {
                    $response['message'] = "Failed to delete: " . $stmt->error;
                }

                $stmt->close();
            }
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

        // Read and normalize header row
        $rawHeader = fgetcsv($handle);
        if (!$rawHeader) {
            $response['message'] = "CSV file is empty or has no header row.";
            echo json_encode($response);
            exit();
        }

        $header = array_map(function($h) {
            return strtolower(trim(preg_replace('/\s+/', '_', $h)));
        }, $rawHeader);

        $required = ['firstname', 'lastname', 'lrn', 'birthdate', 'gender', 'contact_number', 'address','guardian_name', 'guardian_email', 'password'];
        $missing  = array_diff($required, $header);
        if (!empty($missing)) {
            $response['message'] = "CSV is missing required columns: " . implode(', ', $missing);
            echo json_encode($response);
            exit();
        }

        $gradeLevelMap = [];
        $gl_r = $conn->query("SELECT grade_id, grade_level FROM tblgrade_levels");
        while ($row = $gl_r->fetch_assoc()) {
            $gradeLevelMap[strtolower(trim($row['grade_level']))] = (int)$row['grade_id'];
        }

        $strandMap = [];
        $str_r = $conn->query("SELECT strand_id, strand FROM tblstrands");
        while ($row = $str_r->fetch_assoc()) {
            $strandMap[strtolower(trim($row['strand']))] = (int)$row['strand_id'];
        }

        $sectionMap = [];
        $sec_r = $conn->query("SELECT section_id, section, grade_level_id FROM tblsections");
        while ($row = $sec_r->fetch_assoc()) {
            $keyFull  = strtolower(trim($row['section'])) . '|' . $row['grade_level_id'];
            $keyPlain = strtolower(trim($row['section']));
            $sectionMap[$keyFull]  = (int)$row['section_id'];
            $sectionMap[$keyPlain] = (int)$row['section_id'];
        }

        $inserted = 0;
        $skipped  = 0;
        $errors   = [];
        $rowNum   = 1; 

        $insertStmt = $conn->prepare("
            INSERT INTO tblstudents (
                student_id, section_id, password,
                firstname, middlename, lastname, suffix,
                lrn, birthdate, age, gender,
                contact_number, address, guardian_name, guardian_email, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', NOW())
        ");

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count(array_filter($row, 'strlen')) === 0) {
                continue;
            }

            $data = [];
            foreach ($header as $i => $col) {
                $data[$col] = isset($row[$i]) ? trim($row[$i]) : '';
            }

            $firstname      = $data['firstname']      ?? '';
            $middlename     = $data['middlename']     ?? '';
            $lastname       = $data['lastname']       ?? '';
            $suffix         = $data['suffix']         ?? '';
            $lrn            = $data['lrn']            ?? '';
            $birthdate      = $data['birthdate']      ?? '';
            $gender         = $data['gender']         ?? '';
            $contact_number = $data['contact_number'] ?? '';
            $address        = $data['address']        ?? '';
            $guardian_name  = $data['guardian_name']  ?? '';
            $guardian_email = $data['guardian_email'] ?? '';
            $password_raw   = $data['password']       ?? '';
            $grade_level    = $data['grade_level']    ?? '';
            $strand_name    = $data['strand']         ?? '';
            $section_name   = $data['section']        ?? '';
            $student_id_csv = $data['student_id']     ?? '';
            $age_csv        = $data['age']            ?? '';

            if (
                empty($firstname) || empty($lastname) || empty($lrn) ||
                empty($birthdate) || empty($gender)   || empty($contact_number) ||
                empty($address)   || empty($password_raw) ||
                empty($guardian_name) || empty($guardian_email)
            ) {
                $errors[] = "Row $rowNum: Missing required field(s) — skipped.";
                $skipped++;
                continue;
            }

            $chkLRN = $conn->prepare("SELECT id FROM tblstudents WHERE lrn = ?");
            $chkLRN->bind_param("s", $lrn);
            $chkLRN->execute();
            if ($chkLRN->get_result()->num_rows > 0) {
                $errors[] = "Row $rowNum: LRN '$lrn' already exists — skipped.";
                $chkLRN->close();
                $skipped++;
                continue;
            }
            $chkLRN->close();

            $grade_id = 0;
            if (!empty($grade_level)) {
                $grade_id = $gradeLevelMap[strtolower(trim($grade_level))] ?? 0;
            }

            $strand_id = 0;
            if (!empty($strand_name)) {
                $strand_id = $strandMap[strtolower(trim($strand_name))] ?? 0;
            }

            $section_id = 0;
            if (!empty($section_name)) {
                if ($grade_id > 0) {
                    $secKey = strtolower(trim($section_name)) . '|' . $grade_id;
                    $section_id = $sectionMap[$secKey] ?? ($sectionMap[strtolower(trim($section_name))] ?? 0);
                } else {
                    $section_id = $sectionMap[strtolower(trim($section_name))] ?? 0;
                }
            }

            if ($section_id === 0) {
                $errors[] = "Row $rowNum: Section '$section_name' not found in database — skipped.";
                $skipped++;
                continue;
            }

            if (empty($age_csv) && !empty($birthdate)) {
                try {
                    $birth   = new DateTime($birthdate);
                    $today   = new DateTime();
                    $age_csv = (int)$birth->diff($today)->y;
                } catch (Exception $e) {
                    $age_csv = 0;
                }
            }
            $age_val = (int)$age_csv;

            if (!empty($student_id_csv)) {
                $stud_id = $student_id_csv;
                $chkID = $conn->prepare("SELECT id FROM tblstudents WHERE student_id = ?");
                $chkID->bind_param("s", $stud_id);
                $chkID->execute();
                if ($chkID->get_result()->num_rows > 0) {
                    $errors[] = "Row $rowNum: Student ID '$stud_id' already exists — skipped.";
                    $chkID->close();
                    $skipped++;
                    continue;
                }
                $chkID->close();
            } else {
                do {
                    $stud_id = date("Y") . "-" . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    $chkGen = $conn->prepare("SELECT id FROM tblstudents WHERE student_id = ?");
                    $chkGen->bind_param("s", $stud_id);
                    $chkGen->execute();
                    $exists = $chkGen->get_result()->num_rows > 0;
                    $chkGen->close();
                } while ($exists);
            }

            $hashed = password_hash($password_raw, PASSWORD_DEFAULT);

            $insertStmt->bind_param(
                "sisssssssisssss",
                $stud_id,
                $section_id,
                $hashed,
                $firstname,
                $middlename,
                $lastname,
                $suffix,
                $lrn,
                $birthdate,
                $age_val,
                $gender,
                $contact_number,
                $address,
                $guardian_name,
                $guardian_email
            );

            if ($insertStmt->execute()) {
                $inserted++;
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
        $response['message']  = "$inserted student(s) imported successfully." . ($skipped > 0 ? " $skipped row(s) skipped." : "");
    }

    echo json_encode($response);
    exit();
}

 $grade_levels_db = [];
 $gl_stmt = $conn->prepare("SELECT grade_id, grade_level, updated_at, created_at FROM tblgrade_levels WHERE 1");
 $gl_stmt->execute();
 $gl_res = $gl_stmt->get_result();
while($r = $gl_res->fetch_assoc()){
    $grade_levels_db[] = [
        'id' => (int)$r['grade_id'],
        'name' => $r['grade_level']
    ];
}
 $gl_stmt->close();

 $strands_db = [];
 $s_stmt = $conn->prepare("SELECT strand_id, grade_level_id, strand, track, updated_at, created_at FROM tblstrands WHERE 1");
 $s_stmt->execute();
 $s_res = $s_stmt->get_result();
while($r = $s_res->fetch_assoc()){
    $strands_db[] = [
        'id' => (int)$r['strand_id'],
        'name' => $r['strand'],
        'track' => $r['track'],
        'grade_id' => (int)$r['grade_level_id']
    ];
}
 $s_stmt->close();

 $sections_db = [];
 $sec_stmt = $conn->prepare("SELECT section_id, section, grade_level_id FROM tblsections ORDER BY section ASC");
 $sec_stmt->execute();
 $sec_res = $sec_stmt->get_result();
while ($r = $sec_res->fetch_assoc()) {
    $sections_db[] = [
        'section_id'     => (int)$r['section_id'],
        'grade_level_id' => (int)$r['grade_level_id'],
        'section'        => $r['section']
    ];
}
 $sec_stmt->close();

 $subjects_db = [];
 $sub_stmt = $conn->prepare("SELECT subject_id, strand_id, subject, updated_at, created_at FROM tblsubjects WHERE 1");
 $sub_stmt->execute();
 $sub_res = $sub_stmt->get_result();
while ($r = $sub_res->fetch_assoc()) {
    $subjects_db[] = [
        'subject_id' => (int)$r['subject_id'],
        'subject'    => $r['subject'],
        'strand_id'  => (int)$r['strand_id']
    ];
}
 $sub_stmt->close();

 $subject_map = [];
foreach($subjects_db as $s) {
    $subject_map[$s['subject_id']] = $s['subject'];
}

 $students_db = [];
$stmt = $conn->prepare("
    SELECT
        s.id,
        s.student_id,
        s.section_id,
        s.firstname,
        s.middlename,
        s.lastname,
        s.suffix,
        s.lrn,
        s.birthdate,
        s.age,
        s.gender,
        s.contact_number,
        s.address,
        s.status,
        s.created_at,
        sec.section AS section_name,
        gl.grade_level
    FROM tblstudents s
    LEFT JOIN tblsections sec ON s.section_id = sec.section_id
    LEFT JOIN tblgrade_levels gl ON sec.grade_level_id = gl.grade_id
    ORDER BY s.created_at DESC
");
 $stmt->execute();
 $res = $stmt->get_result();

 $colorPalette = [
    "linear-gradient(135deg,var(--cyan),var(--accent))",
    "linear-gradient(135deg,var(--green),#1aaa6e)",
    "linear-gradient(135deg,var(--amber),#e08c0c)",
    "linear-gradient(135deg,var(--teal),#0d9488)",
    "linear-gradient(135deg,var(--accent),var(--cyan))",
    "linear-gradient(135deg,var(--green),var(--teal))",
    "linear-gradient(135deg,var(--purple),var(--accent))",
    "linear-gradient(135deg,var(--teal),var(--green))",
    "linear-gradient(135deg,var(--rose),var(--purple))",
    "linear-gradient(135deg,var(--cyan),var(--green))"
];

 $idx = 0;

while ($r = $res->fetch_assoc()) {
    $displayStatus = strtolower($r['status']);

    if ($displayStatus === 'enrolled' || $displayStatus === 'active') {
        $displayStatus = 'Active';
    } else {
        $displayStatus = 'Inactive';
    }

  $students_db[] = [
          'id'        => (int)$r['id'],
          'first'     => $r['firstname'],
          'middle'    => $r['middlename'],
          'last'      => $r['lastname'],
          'suffix'    => $r['suffix'],
          'lrn'       => $r['lrn'],
          'birthdate' => $r['birthdate'],
          'age'       => $r['age'],
          'gender'    => $r['gender'],
          'contact'   => $r['contact_number'],
          'address'   => $r['address'],
          'email'     => '',
          'dept'      => '',
          'year'      => $r['grade_level'] ?? 'Unassigned',
          'section'   => $r['section_name'] ?? 'Unassigned',
          'strand'    => '',
          'studId'    => $r['student_id'],
          'gpa'       => 0,
          'phone'     => '',
          'status'    => $displayStatus,
          'joined'    => date('M j, Y', strtotime($r['created_at'])),
          'color'     => $colorPalette[$idx % count($colorPalette)],
      ];
    $idx++;
}
 $stmt->close();

 $students_json = json_encode($students_db);
 $sections_json = json_encode($sections_db);
 $strands_json = json_encode($strands_db); 
 $grade_levels_json = json_encode($grade_levels_db);
 $subjects_json = json_encode($subjects_db);

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
  
    $themeResult = $conn->prepare("SELECT theme FROM tblteacher_settings WHERE teacher_id = ? LIMIT 1");
    $themeResult->bind_param("i", $admin_id);
    $themeResult->execute();
    $themeRow = $themeResult->get_result()->fetch_assoc();
    $themeResult->close();
    $savedTheme = $themeRow['theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn — Students</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin/admin_students.css">
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

.csv-col-tag {
  display: inline-block;
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: 4px;
  padding: 1px 6px;
  font-size: 11px;
  font-family: monospace;
  color: var(--cyan, #06b6d4);
  margin: 1px 2px;
}
.csv-info-box {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 14px 16px;
  margin-bottom: 16px;
  font-size: 13px;
  color: var(--text2);
  line-height: 1.8;
}
.csv-info-box strong { color: var(--text1); }
.csv-info-box ul {
  margin: 6px 0 0 18px;
  padding: 0;
}
.csv-info-box li { margin-bottom: 2px; }
.csv-progress {
  height: 4px;
  background: var(--bg3);
  border-radius: 4px;
  overflow: hidden;
  margin-top: 8px;
  display: none;
}
.csv-progress-bar {
  height: 100%;
  background: var(--green, #22c55e);
  border-radius: 4px;
  width: 0%;
  transition: width .4s ease;
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
    <a class="nav-item" href="admin_teachers.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Teachers
      <span class="nav-badge"><?php echo $teacherCount; ?></span>
    </a>
    <a class="nav-item active" href="admin_students.php">
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
        <div class="section-title">👨‍🎓 Student Management</div>
        <div class="section-sub">Manage students by maintaining and updating student profiles, enrollment information, strands, sections, and grade level details.</div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <button class="btn btn-primary" onclick="openAddModal()">+ Add Student</button>
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
        <input type="text" id="searchInput" placeholder="Search by name, student ID, or section..." oninput="applyFilters()">
      </div>
    </div>

    <div class="bulk-bar" id="bulk-bar">
      <span><span class="bulk-count" id="bulk-count">0</span> student(s) selected</span>
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
              <th><span class="sort-btn" onclick="sortBy('name')">Student <span class="sort-arrow" id="sort-name">↕</span></span></th>
              <th><span class="sort-btn" onclick="sortBy('year')">Grade <span class="sort-arrow" id="sort-year">↕</span></span></th>
              <th>Section</th>
              <th>Status</th>
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

<div class="modal-overlay" id="student-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-title">Add New Student</div>
      <button class="modal-close" onclick="closeModal('student-modal')">✕</button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="edit-id">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">First Name *</label>
          <input type="text" id="f-first" placeholder="e.g. John Paolo">
        </div>
        <div class="form-group">
          <label class="form-label">Middle Name</label>
          <input type="text" id="f-middle" placeholder="e.g. Santos">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Last Name *</label>
          <input type="text" id="f-last" placeholder="e.g. Garcia">
        </div>
        <div class="form-group">
          <label class="form-label">Suffix</label>
          <input type="text" id="f-suffix" placeholder="e.g. Jr., Sr., III">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">LRN *</label>
          <input type="text" id="f-lrn" placeholder="Enter Learner Reference Number">
        </div>
        <div class="form-group">
          <label class="form-label">Student ID</label>
          <input type="text" id="f-stud-id" placeholder="e.g. 2024-000001">
          <div class="form-hint">Required. e.g. 2026-000001</div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Birthdate *</label>
          <input type="date" id="f-birthdate" onchange="calculateAge()">
        </div>
        <div class="form-group">
          <label class="form-label">Age</label>
          <input type="number" id="f-age" placeholder="Age" readonly>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Gender *</label>
          <select id="f-gender">
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Contact Number *</label>
          <input type="text" id="f-contact" placeholder="e.g. 09123456789">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Address *</label>
        <textarea id="f-address" rows="3" placeholder="Complete Address"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Grade Level *</label>
          <select id="f-grade" onchange="updateSections()">
            <option value="">Select Grade</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Section / Block *</label>
        <select id="f-section">
          <option value="">Select Section</option>
        </select>
        <div class="form-hint">Selecting a Grade Level loads the available Strands and Sections.</div>
      </div>

      <div class="form-group" id="password-group">
        <label class="form-label">Password *</label>
        <input type="text" id="f-password" placeholder="Min 8 characters">
        <div class="form-hint" id="pass-hint">Required for new students. Leave blank to keep current password when editing.</div>
      </div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('student-modal')">Cancel</button>
      <button class="btn btn-primary" id="modal-save-btn" onclick="saveStudent()">Add Student</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="delete-modal">
  <div class="modal" style="max-width:400px">
    <div class="confirm-body">
      <div class="confirm-icon">⚠️</div>
      <div class="confirm-text">Remove this student?</div>
      <div class="confirm-sub" id="delete-confirm-text">All academic records and enrollment data will be archived.</div>
      <div class="confirm-actions">
        <button class="btn btn-ghost" onclick="closeModal('delete-modal')">Cancel</button>
        <button class="btn btn-danger" onclick="confirmDelete()">Remove Student</button>
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

<div class="modal-overlay" id="csv-modal">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <div class="modal-title">📤 Import Students via CSV</div>
      <button class="modal-close" onclick="closeModal('csv-modal')">✕</button>
    </div>
    <div class="modal-body">

      <div class="csv-info-box">
        <strong>📋 CSV Column Guide</strong><br>
        <strong>Required:</strong>
        <span class="csv-col-tag">firstname</span>
        <span class="csv-col-tag">middlename</span>
        <span class="csv-col-tag">lastname</span>
        <span class="csv-col-tag">lrn</span>
        <span class="csv-col-tag">birthdate</span>
        <span class="csv-col-tag">gender</span>
        <span class="csv-col-tag">contact_number</span>
        <span class="csv-col-tag">address</span>
        <span class="csv-col-tag">guardian_name</span>
        <span class="csv-col-tag">guardian_email</span>
        <span class="csv-col-tag">password</span>
        <span class="csv-col-tag">section</span>
        <span class="csv-col-tag">student_id</span>
        <span class="csv-col-tag">age</span>
        <span class="csv-col-tag">grade_level</span>
        <strong>Optional:</strong>
        <span class="csv-col-tag">suffix</span>
        <ul>
          <li><code>grade_level</code> — must match exactly, e.g. <em>Grade 11</em></li>
          <li><code>section</code> — must match an existing section name</li>
          <li><code>birthdate</code> — format: <em>DD-MM-YYYY</em> (e.g. 15-05-2007)</li>
          <li><code>gender</code> — <em>Male</em> or <em>Female</em></li>
          <li>Rows with duplicate LRN are automatically skipped</li>
        </ul>
      </div>

      <div style="margin-bottom:14px">
        <button class="btn btn-ghost btn-xs" onclick="downloadCSVTemplate()" style="font-size:12px;display:inline-flex;align-items:center;gap:5px">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Download CSV Template
        </button>
      </div>

      <div class="form-group">
        <label class="form-label">Select CSV File *</label>
        <input type="file" id="csv-file-input" accept=".csv"
          style="padding:8px;background:var(--bg2);border:1px solid var(--border);border-radius:6px;color:var(--text1);width:100%;box-sizing:border-box;cursor:pointer">
      </div>

      <div id="csv-preview-wrap" style="display:none;margin-top:4px">
        <label class="form-label">Preview (first 5 data rows)</label>
        <div id="csv-preview-table"
          style="overflow-x:auto;font-size:11.5px;border:1px solid var(--border);border-radius:6px;max-height:180px;overflow-y:auto">
        </div>
      </div>

      <div class="csv-progress" id="csv-progress">
        <div class="csv-progress-bar" id="csv-progress-bar"></div>
      </div>

      <div id="csv-result" style="display:none;margin-top:14px;padding:12px 14px;border-radius:8px;font-size:13px;line-height:1.7"></div>

    </div>

    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('csv-modal')">Cancel</button>
      <button class="btn btn-primary" id="csv-import-btn" onclick="submitCSV()">
        Import Students
      </button>
    </div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>
var students      = <?php echo $students_json; ?>;
var dbSections    = <?php echo $sections_json; ?>;
var dbStrands     = <?php echo $strands_json; ?>;
var dbGradeLevels = <?php echo $grade_levels_json; ?>;
var dbSubjects    = <?php echo $subjects_json; ?>;

var nextId = students.length > 0 ? Math.max(...students.map(s=>s.id)) + 1 : 1;

var currentFilter="All",currentPage=1,perPage=8,sortField="name",sortDir="asc",selectedIds={},deleteTargetId=null;

var statusColors={"Active":"badge-green","Inactive":"badge-red"};
var yearColors={"Grade 11":"var(--cyan)","Grade 12":"var(--green)"};
var yearBg={"Grade 11":"var(--cyan-bg)","Grade 12":"var(--green-bg)"};

function getFiltered(){
  var q=document.getElementById("searchInput").value.toLowerCase().trim();
  var list=students.filter(function(s){
    if(!q) return true;
    var full=(s.first+" "+s.last+" "+s.email+" "+s.section+" "+s.year+" "+s.studId).toLowerCase();
    return full.indexOf(q)!==-1;
  });
  list.sort(function(a,b){
    var va,vb;
    if(sortField==="name"){va=(a.first+" "+a.last).toLowerCase();vb=(b.first+" "+b.last).toLowerCase()}
    else if(sortField==="year"){va=a.year;vb=b.year}
    else if(sortField==="status"){va=a.status;vb=b.status}
    else{va=a.first;vb=b.first}
    if(va<vb)return sortDir==="asc"?-1:1;if(va>vb)return sortDir==="asc"?1:-1;return 0;
  });
  return list;
}
function sortBy(f){if(sortField===f)sortDir=sortDir==="asc"?"desc":"asc";else{sortField=f;sortDir="asc"}updateSortArrows();currentPage=1;render()}
function updateSortArrows(){["name","year","status"].forEach(function(f){var el=document.getElementById("sort-"+f);if(!el)return;if(f===sortField){el.textContent=sortDir==="asc"?"↑":"↓";el.className="sort-arrow "+sortDir}else{el.textContent="↕";el.className="sort-arrow"}})}
function applyFilters(){currentPage=1;render()}

function render(){
  var filtered=getFiltered();var totalPages=Math.max(1,Math.ceil(filtered.length/perPage));
  if(currentPage>totalPages)currentPage=totalPages;
  var start=(currentPage-1)*perPage;var pageItems=filtered.slice(start,start+perPage);
  renderTable(pageItems);renderMobile(pageItems);renderPagination(filtered.length,totalPages);renderMiniStats();updateBulkBar();
}

function renderTable(items){
  var tbody=document.getElementById("table-body");
  if(!items.length){tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text3);font-size:14px">No students found.</td></tr>';return}
  tbody.innerHTML=items.map(function(s){
    var init=(s.first.charAt(0)+s.last.charAt(0)).toUpperCase();
    var ck=selectedIds[s.id]?" checked":"";
    var yc=yearColors[s.year]||"var(--text3)";
    var statusBtn=(s.status==="Active")?"🚫":"✅";
    return '<tr>'+
      '<td><div class="custom-check'+ck+'" onclick="toggleCheck('+s.id+')"></div></td>'+
      '<td><div class="tbl-user"><div class="tbl-avatar" style="background:'+s.color+'">'+init+'</div><div><div class="tbl-name expand-trigger" onclick="toggleExpand('+s.id+')">'+s.first+' '+s.last+'</div><div class="tbl-email">'+s.email+'</div></div></div></td>'+
      '<td><span class="badge" style="background:'+(yearBg[s.year]||"var(--bg4)")+';color:'+yc+';border:1px solid '+yc+'33">'+s.year+'</span></td>'+
      '<td style="font-size:13px;font-weight:500;color:var(--text2)">'+s.section+'</td>'+
      '<td><span class="badge '+(statusColors[s.status]||"badge-blue")+'">'+s.status+'</span></td>'+
      '<td><div class="tbl-actions"><button class="btn btn-ghost btn-xs" onclick="openEditModal('+s.id+')" title="Edit">✏️</button><button class="btn btn-ghost btn-xs" onclick="toggleStatus('+s.id+')" title="Toggle Status">'+statusBtn+'</button><button class="btn btn-danger btn-xs" onclick="openDeleteModal('+s.id+')" title="Remove">🗑️</button></div></td>'+
    '</tr>'+
    '<tr id="expand-'+s.id+'" style="display:none"><td colspan="7" style="padding:0"><div class="user-expand" id="expand-inner-'+s.id+'">'+buildDetail(s)+'</div></td></tr>';
  }).join("");
}

function buildDetail(s){
  return '<div class="detail-row"><span class="dl">Full Name:</span><span class="dv">'+s.first+' '+s.last+'</span></div>'+
    '<div class="detail-row"><span class="dl">Student ID:</span><span class="dv">'+s.studId+'</span></div>'+
    (s.email?'<div class="detail-row"><span class="dl">Email:</span><span class="dv">'+s.email+'</span></div>':'')+
    '<div class="detail-row"><span class="dl">Grade Level:</span><span class="dv">'+s.year+'</span></div>'+
    '<div class="detail-row"><span class="dl">Section:</span><span class="dv">'+s.section+'</span></div>'+
    '<div class="detail-row"><span class="dl">Status:</span><span class="dv">'+s.status+'</span></div>'+
    '<div class="detail-row"><span class="dl">Enrolled:</span><span class="dv">'+s.joined+'</span></div>';
}

function toggleExpand(id){var r=document.getElementById("expand-"+id);var i=document.getElementById("expand-inner-"+id);if(!r)return;if(r.style.display==="none"){r.style.display="";i.classList.add("open")}else{r.style.display="none";i.classList.remove("open")}}

function renderMobile(items){
  var el=document.getElementById("mobile-list");
  if(!items.length){el.innerHTML='<div style="text-align:center;padding:40px;color:var(--text3);font-size:14px">No students found.</div>';return}
  el.innerHTML=items.map(function(s){
    var init=(s.first.charAt(0)+s.last.charAt(0)).toUpperCase();
    var yc=yearColors[s.year]||"var(--text3)";
    return '<div class="s-card" style="display:flex;align-items:flex-start;gap:10px;">'+
      '<div class="sc-avatar" style="background:'+s.color+';flex-shrink:0;">'+init+'</div>'+
      '<div class="sc-info" style="flex:1;min-width:0;">'+
        '<div class="sc-name">'+s.first+' '+s.last+'</div>'+
        '<div class="sc-badges">'+
          '<span class="badge" style="background:'+(yearBg[s.year]||"var(--bg4)")+';color:'+yc+'">'+s.year+'</span>'+
          '<span class="badge '+(statusColors[s.status]||"badge-blue")+'">'+s.status+'</span>'+
        '</div>'+
        '<div class="sc-meta"><span>📋 '+s.studId+'</span></div>'+
        '<div class="sc-meta"><span>🏫 '+s.section+'</span></div>'+
      '</div>'+
      '<div class="sc-actions" style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;align-items:flex-end;">'+
        '<button class="btn btn-ghost btn-xs" onclick="openEditModal('+s.id+')">✏️</button>'+
        '<button class="btn btn-ghost btn-xs" onclick="toggleStatus('+s.id+')">'+(s.status==='Active'?'🚫':'✅')+'</button>'+
        '<button class="btn btn-danger btn-xs" onclick="openDeleteModal('+s.id+')">🗑️</button>'+
      '</div>'+
    '</div>';
  }).join("");
}

function renderPagination(total,totalPages){
  var el=document.getElementById("pagination");var start=(currentPage-1)*perPage+1;var end=Math.min(currentPage*perPage,total);if(!total)start=0;
  var btns='<button class="page-btn" onclick="goPage('+(currentPage-1)+')"'+(currentPage<=1?' disabled':'')+'>‹</button>';
  var pages=[];if(totalPages<=5){for(var i=1;i<=totalPages;i++)pages.push(i)}else{pages.push(1);if(currentPage>3)pages.push('...');for(var j=Math.max(2,currentPage-1);j<=Math.min(totalPages-1,currentPage+1);j++)pages.push(j);if(currentPage<totalPages-2)pages.push('...');pages.push(totalPages)}
  pages.forEach(function(p){if(p==='...')btns+='<div class="page-dots">…</div>';else btns+='<button class="page-btn'+(p===currentPage?' active':'')+'" onclick="goPage('+p+')">'+p+'</button>'});
  btns+='<button class="page-btn" onclick="goPage('+(currentPage+1)+')"'+(currentPage>=totalPages?' disabled':'')+'>›</button>';
  el.innerHTML='<div class="pagination-info">Showing '+start+'–'+end+' of '+total+' students</div><div class="pagination-btns">'+btns+'</div>';
}
function goPage(p){var f=getFiltered();var tp=Math.max(1,Math.ceil(f.length/perPage));if(p<1||p>tp)return;currentPage=p;render();document.querySelector('.card').scrollIntoView({behavior:'smooth',block:'start'})}

function renderMiniStats(){
  var total=students.length;
  var el=document.getElementById("mini-stats");
  el.innerHTML=
    '<div class="mini-stat"><div class="mini-stat-icon" style="background:var(--cyan-bg)">👨‍🎓</div><div><div class="mini-stat-val">'+total+'</div><div class="mini-stat-lbl">Total Students</div></div></div>';
}

function toggleCheck(id){selectedIds[id]=!selectedIds[id];if(!selectedIds[id])delete selectedIds[id];updateBulkBar();renderCheckAll();render()}
function toggleAll(){var f=getFiltered();var st=(currentPage-1)*perPage;var pi=f.slice(st,st+perPage);var ac=pi.every(function(u){return selectedIds[u.id]});pi.forEach(function(u){if(ac)delete selectedIds[u.id];else selectedIds[u.id]=true});updateBulkBar();renderCheckAll();render()}
function renderCheckAll(){var f=getFiltered();var st=(currentPage-1)*perPage;var pi=f.slice(st,st+perPage);var ac=pi.length>0&&pi.every(function(u){return selectedIds[u.id]});var el=document.getElementById("check-all");if(ac)el.classList.add("checked");else el.classList.remove("checked")}
function updateBulkBar(){var c=Object.keys(selectedIds).length;var b=document.getElementById("bulk-bar");document.getElementById("bulk-count").textContent=c;if(c>0)b.classList.add("visible");else b.classList.remove("visible")}
function clearSelection(){selectedIds={};updateBulkBar();renderCheckAll();render()}
function bulkAction(a){var ids=Object.keys(selectedIds).map(Number);if(!ids.length)return;if(a==="activate"){ids.forEach(function(id){var s=students.find(function(x){return x.id===id});if(s)s.status="Active"});showToast(ids.length+" student(s) activated","success")}else if(a==="deactivate"){ids.forEach(function(id){var s=students.find(function(x){return x.id===id});if(s)s.status="Inactive"});showToast(ids.length+" student(s) deactivated","info")}else if(a==="export"){showToast("Exported "+ids.length+" student(s) to CSV","success")}clearSelection();render()}

function toggleStatus(id){
  var s=students.find(x=>x.id===id);if(!s)return;
  var newStatus=(s.status==="Active")?"Inactive":"Active";
  var formData=new FormData();
  formData.append('api_action','update_status');
  formData.append('student_db_id',id);
  formData.append('status',newStatus);
  fetch('admin_students.php',{method:'POST',body:formData})
    .then(r=>r.json())
    .then(data=>{
      if(data.success){
        showToast(s.first+" "+s.last+" is now "+newStatus,newStatus==="Active"?"success":"info");
        setTimeout(()=>location.reload(),1200);
      } else {
        showToast("Error updating status","error");
      }
    })
    .catch(()=>showToast("Network error","error"));
}

function openModal(id){document.getElementById(id).classList.add("open");document.body.style.overflow="hidden"}
function closeModal(id){document.getElementById(id).classList.remove("open");document.body.style.overflow=""}
function openLogoutModal(){openModal("logout-modal")}

var gradeSelect=document.getElementById("f-grade");
gradeSelect.innerHTML='<option value="">Select Grade</option>';
dbGradeLevels.forEach(function(gl){
  gradeSelect.innerHTML+='<option value="'+gl.id+'">'+gl.name+'</option>';
});

function updateStrands(){
  var gradeId=parseInt(gradeSelect.value);
  var strandSelect=document.getElementById("f-strand");
  var sectionSelect=document.getElementById("f-section");
  strandSelect.innerHTML='<option value="">Select Strand</option>';
  sectionSelect.innerHTML='<option value="">Select Section</option>';
  if(!gradeId)return;
  dbStrands.filter(s=>s.grade_id===gradeId).forEach(function(st){
    strandSelect.innerHTML+='<option value="'+st.id+'">'+st.name+'</option>';
  });
  updateSections();
}

function updateSections(){
  var gradeId=parseInt(gradeSelect.value);
  var sectionSelect=document.getElementById("f-section");
  sectionSelect.innerHTML='<option value="">Select Section</option>';
  if(!gradeId)return;
  dbSections.filter(s=>s.grade_level_id===gradeId).forEach(function(sec){
    sectionSelect.innerHTML+='<option value="'+sec.section_id+'">'+sec.section+'</option>';
  });
}

function loadSubjects(){}

function calculateAge(){
  var birthdate=document.getElementById("f-birthdate").value;
  if(!birthdate){document.getElementById("f-age").value="";return}
  var birth=new Date(birthdate),today=new Date();
  var age=today.getFullYear()-birth.getFullYear();
  var md=today.getMonth()-birth.getMonth();
  if(md<0||(md===0&&today.getDate()<birth.getDate()))age--;
  document.getElementById("f-age").value=age;
}

function openAddModal(){
  document.getElementById("modal-title").textContent="Add New Student";
  document.getElementById("modal-save-btn").textContent="Add Student";
  document.getElementById("edit-id").value="";
  ["f-first","f-middle","f-last","f-suffix","f-lrn","f-birthdate","f-age","f-gender","f-contact","f-address","f-grade","f-section","f-password","f-stud-id"].forEach(function(id){var el=document.getElementById(id);if(el)el.value=""});
  updateSections();
  document.getElementById("pass-hint").textContent="Required for new students.";
  openModal("student-modal");
}

function openEditModal(id){
  var s=students.find(x=>x.id===id);if(!s)return;
  document.getElementById("modal-title").textContent="Edit Student — "+s.first+" "+s.last;
  document.getElementById("modal-save-btn").textContent="Save Changes";
  document.getElementById("edit-id").value=s.id;
  document.getElementById("f-first").value=s.first;
  document.getElementById("f-middle").value=s.middle||"";
  document.getElementById("f-suffix").value=s.suffix||"";
  document.getElementById("f-lrn").value=s.lrn||"";
  document.getElementById("f-birthdate").value=s.birthdate||"";
  document.getElementById("f-age").value=s.age||"";
  document.getElementById("f-gender").value=s.gender||"";
  document.getElementById("f-contact").value=s.contact||"";
  document.getElementById("f-address").value=s.address||"";
  document.getElementById("f-last").value=s.last;
  document.getElementById("f-stud-id").value=s.studId;
  var matchedGrade=dbGradeLevels.find(gl=>gl.name===s.year);
  if(matchedGrade){document.getElementById("f-grade").value=matchedGrade.id;}
  updateSections();
  var secSelect=document.getElementById("f-section");
  if(s.section&&secSelect.options.length>0){
    for(var i=0;i<secSelect.options.length;i++){
      if(secSelect.options[i].text===s.section){secSelect.value=secSelect.options[i].value;break}
    }
  }
  document.getElementById("f-password").value="";
  document.getElementById("pass-hint").textContent="Leave blank to keep current password.";
  openModal("student-modal");
}

function saveStudent(){
  var editId=document.getElementById("edit-id").value;
  var first=document.getElementById("f-first").value.trim();
  var middle=document.getElementById("f-middle").value.trim();
  var suffix=document.getElementById("f-suffix").value.trim();
  var lrn=document.getElementById("f-lrn").value.trim();
  var birthdate=document.getElementById("f-birthdate").value;
  var age=document.getElementById("f-age").value;
  var gender=document.getElementById("f-gender").value;
  var contact=document.getElementById("f-contact").value.trim();
  var address=document.getElementById("f-address").value.trim();
  var last=document.getElementById("f-last").value.trim();
  var studId=document.getElementById("f-stud-id").value.trim();
  var secSelect=document.getElementById("f-section");
  var sectionId=secSelect.value;
  var password=document.getElementById("f-password").value;
  if(!first||!last||!lrn||!birthdate||!gender||!contact||!address||!sectionId||!studId){showToast("Please fill in all required fields","error");return}
  if(!editId&&password.length<8){showToast("Password must be at least 8 characters","error");return}
  var formData=new FormData();
  formData.append('api_action',editId?'update':'create');
  formData.append('student_db_id',editId);
  formData.append('firstname',first);
  formData.append('middlename',middle);
  formData.append('suffix',suffix);
  formData.append('lrn',lrn);
  formData.append('birthdate',birthdate);
  formData.append('age',age);
  formData.append('gender',gender);
  formData.append('contact_number',contact);
  formData.append('address',address);
  formData.append('lastname',last);
  formData.append('student_id_code',studId);
  formData.append('section',secSelect.options[secSelect.selectedIndex].text);
  formData.append('section_id',sectionId);
  if(password.length>0)formData.append('password',password);
  fetch('admin_students.php',{method:'POST',body:formData})
    .then(r=>r.json())
    .then(data=>{
      if(data.success){
        showToast(data.message||"Saved successfully","success");
        closeModal("student-modal");
        setTimeout(()=>location.reload(),800);
      } else {
        showToast(data.message||"Error saving student","error");
      }
    })
    .catch(()=>showToast("Network error","error"));
}

function openDeleteModal(id){
  deleteTargetId=id;
  var s=students.find(x=>x.id===id);if(!s)return;
  document.getElementById("delete-confirm-text").textContent="Are you sure you want to remove "+s.first+" "+s.last+" ("+s.studId+")? Academic records will be archived.";
  openModal("delete-modal");
}
function confirmDelete(){
  if(deleteTargetId===null)return;
  var formData=new FormData();
  formData.append('api_action','delete');
  formData.append('student_db_id',deleteTargetId);
  fetch('admin_students.php',{method:'POST',body:formData})
    .then(r=>r.json())
    .then(data=>{
      if(data.success){showToast(data.message,"error");closeModal("delete-modal");setTimeout(()=>location.reload(),1000)}
      else showToast(data.message,"error");
    })
    .catch(()=>showToast("Network error","error"));
}

function showToast(msg,type){
  type=type||"success";
  var icons={success:"✅",error:"❌",info:"ℹ️"};
  var c=document.getElementById("toast-container");
  var t=document.createElement("div");
  t.className="toast "+type;
  t.innerHTML='<span class="toast-icon">'+(icons[type]||"✅")+'</span><span>'+msg+'</span>';
  c.appendChild(t);
  setTimeout(function(){t.style.opacity="0";t.style.transform="translateX(60px)";t.style.transition="all .3s ease";setTimeout(function(){t.remove()},300)},3000);
}

var sidebar=document.getElementById("sidebar"),overlay=document.getElementById("sidebar-overlay"),menuBtn=document.getElementById("menuBtn");
function openSidebar(){sidebar.classList.add("open");overlay.classList.add("open");document.body.style.overflow="hidden"}
function closeSidebar(){sidebar.classList.remove("open");overlay.classList.remove("open");document.body.style.overflow=""}
menuBtn.addEventListener("click",function(){if(sidebar.classList.contains("open"))closeSidebar();else openSidebar()});
overlay.addEventListener("click",closeSidebar);
document.querySelectorAll(".nav-item").forEach(function(item){item.addEventListener("click",function(){if(window.innerWidth<=768)closeSidebar()})});
document.addEventListener("keydown",function(e){if(e.key==="Escape"){closeSidebar();["student-modal","delete-modal","logout-modal","csv-modal"].forEach(closeModal)}});
document.querySelectorAll(".modal-overlay").forEach(function(ov){ov.addEventListener("click",function(e){if(e.target===ov)closeModal(ov.id)})});

var savedTheme='<?= htmlspecialchars($savedTheme) ?>';
localStorage.setItem('excelearn-theme',savedTheme);
if(savedTheme==='light')document.body.classList.add('light');

var resizeTimer;window.addEventListener("resize",function(){clearTimeout(resizeTimer);resizeTimer=setTimeout(function(){if(window.innerWidth>768)closeSidebar()},150)});

function openCSVModal() {
  document.getElementById('csv-file-input').value = '';
  document.getElementById('csv-preview-wrap').style.display = 'none';
  document.getElementById('csv-preview-table').innerHTML = '';
  document.getElementById('csv-result').style.display = 'none';
  document.getElementById('csv-progress').style.display = 'none';
  document.getElementById('csv-progress-bar').style.width = '0%';
  var btn = document.getElementById('csv-import-btn');
  btn.disabled = false;
  btn.textContent = 'Import Students';
  openModal('csv-modal');
}

document.getElementById('csv-file-input').addEventListener('change', function() {
  var file = this.files[0];
  if (!file) return;

  var ext = file.name.split('.').pop().toLowerCase();
  if (ext !== 'csv') {
    showToast('Please select a valid .csv file.', 'error');
    this.value = '';
    return;
  }

  var reader = new FileReader();
  reader.onload = function(e) {
    var text = e.target.result;
    var lines = text.split(/\r?\n/).filter(l => l.trim() !== '');

    if (lines.length < 2) {
      document.getElementById('csv-preview-wrap').style.display = 'none';
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

    var preview = lines.slice(0, 6); // header + up to 5 rows
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

    document.getElementById('csv-preview-table').innerHTML = table;
    document.getElementById('csv-preview-wrap').style.display = 'block';
    document.getElementById('csv-result').style.display = 'none';
  };
  reader.readAsText(file);
});

function submitCSV() {
  var fileInput = document.getElementById('csv-file-input');
  if (!fileInput.files.length) {
    showToast('Please select a CSV file first.', 'error');
    return;
  }

  var btn = document.getElementById('csv-import-btn');
  btn.disabled = true;
  btn.textContent = 'Importing…';

  var progressWrap = document.getElementById('csv-progress');
  var progressBar  = document.getElementById('csv-progress-bar');
  progressWrap.style.display = 'block';
  var prog = 0;
  var progInterval = setInterval(function() {
    prog = Math.min(prog + 2, 85); 
    progressBar.style.width = prog + '%';
  }, 80);

  var formData = new FormData();
  formData.append('api_action', 'csv_import');
  formData.append('csv_file', fileInput.files[0]);

  fetch('admin_students.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      clearInterval(progInterval);
      progressBar.style.width = '100%';
      setTimeout(() => { progressWrap.style.display = 'none'; progressBar.style.width = '0%'; }, 600);

      btn.disabled = false;
      btn.textContent = 'Import Students';

      var resultEl = document.getElementById('csv-result');
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

        if (data.inserted > 0) {
          setTimeout(() => location.reload(), 2200);
        }

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
      btn.textContent = 'Import Students';
      showToast('Network error during import.', 'error');
    });
}

function downloadCSVTemplate() {
  var header = 'firstname,middlename,lastname,suffix,student_id,lrn,birthdate,age,gender,contact_number,address,guardian_name,guardian_email,grade_level,section,password';

  var refRows = [];
  dbSections.forEach(function(sec) {
    var gradeObj = dbGradeLevels.find(function(g) { return g.id === sec.grade_level_id; });
    var gradeName = gradeObj ? gradeObj.name : '';
    var strandsForGrade = dbStrands.filter(function(st) { return st.grade_id === sec.grade_level_id; });
    if (strandsForGrade.length === 0) {
      refRows.push({ grade: gradeName, strand: '', section: sec.section });
    } else {
      strandsForGrade.forEach(function(st) {
        refRows.push({ grade: gradeName, strand: st.name, section: sec.section });
      });
    }
  });

  var rows = [];
  var r0 = refRows[0] || { grade: 'Grade 11', strand: '', section: '' };
  var r1 = refRows[1] || { grade: 'Grade 12', strand: '', section: '' };

rows.push('Juan,Dela,Cruz,,2026-000001,123456789012,2007-05-15,17,Male,09123456789,"123 Rizal St. Naga City",Pedro Cruz,pedro@email.com,' + r0.grade + ',"' + r0.section + '",password123');
rows.push('Maria,,Santos,,2026-000002,098765432101,2008-03-22,16,Female,09987654321,"456 Luna Ave. Naga City",Rosa Santos,rosa@email.com,' + r1.grade + ',"' + r1.section + '",password456');

  var refBlock = '\n\n--- VALID COMBINATIONS REFERENCE (delete this block before importing) ---\ngrade_level,section\n';
  refRows.forEach(function(r) {
    refBlock += r.grade + ',"' + r.section + '"\n';
  });

  var csv = header + '\n' + rows.join('\n') + refBlock;
  var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  var a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'students_import_template.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  showToast('Template downloaded with your actual sections & strands!', 'success');
}

updateSortArrows();render();
</script>
</body>
</html>