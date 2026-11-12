<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

 $teacher_id = $_SESSION['user_id'];

 $user = null;

 $stmtUser = $conn->prepare("SELECT profile_pic, firstname, middlename, lastname, role FROM tblusers WHERE id = ?");
 $stmtUser->bind_param("i", $teacher_id);
 $stmtUser->execute();
 $resUser = $stmtUser->get_result();

if ($resUser && $resUser->num_rows > 0) {
    $user = $resUser->fetch_assoc();
}
 $stmtUser->close();

/* Build display name */
 $display_name = 'User';
 $initials = 'U';
 $profile_pic = '';

if ($user) {
    $fn = $user['firstname'] ?? '';
    $mn = $user['middlename'] ?? '';
    $ln = $user['lastname'] ?? '';

    $display_name = trim($fn . ' ' . ($mn ? $mn . ' ' : '') . $ln);

    $initials = strtoupper(
        substr($fn, 0, 1) . substr($ln, 0, 1)
    );

    $profile_pic = !empty($user['profile_pic']) 
      ? '../uploads/profile_pic/teachers/' . $user['profile_pic'] 
      : '';
}

$available_students = [];

$studentSearch = $conn->prepare("
    SELECT
    s.id, 
    s.student_id, 
    s.firstname, 
    s.lastname,
    sec.grade_level_id
FROM tblstudents s
LEFT JOIN tblsections sec ON s.section_id = sec.section_id
ORDER BY s.firstname ASC, s.lastname ASC
");

$studentSearch->execute();
$studentResult = $studentSearch->get_result();

$teacher_subjects = [];

$subjectStmt = $conn->prepare("
SELECT 
    ts.teacher_subject_id,
    ts.subject_id,
    sub.subject,
    sub.strand_id
    FROM tbl_teacher_subjects ts
    LEFT JOIN tblsubjects sub 
        ON ts.subject_id = sub.subject_id
    WHERE ts.teacher_id = ?
    ORDER BY sub.subject ASC
");

$subjectStmt->bind_param("i", $teacher_id);
$subjectStmt->execute();

$subjectRes = $subjectStmt->get_result();

while ($row = $subjectRes->fetch_assoc()) {

    $teacher_subjects[] = [
        'teacher_subject_id' => $row['teacher_subject_id'],
        'subject_id' => $row['subject_id'],
        'subject'    => $row['subject'],
        'strand_id'  => (int)$row['strand_id']
    ];
}

$subjectStmt->close();

while ($row = $studentResult->fetch_assoc()) {
    $available_students[] = [
        'id' => $row['id'],
        'student_id' => $row['student_id'],
        'name' => trim($row['firstname'] . ' ' . $row['lastname']),
        'grade_level_id' => (int)$row['grade_level_id']
    ];
}

$studentSearch->close();

// Full list ng sections at strands (para sa "New Student" form)
$all_sections = [];
$resSec = $conn->query("
    SELECT sec.section_id, sec.section, sec.grade_level_id, gl.grade_level
    FROM tblsections sec
    LEFT JOIN tblgrade_levels gl ON sec.grade_level_id = gl.grade_id
    ORDER BY gl.grade_level ASC, sec.section ASC
");
while ($r = $resSec->fetch_assoc()) {
    $all_sections[] = [
        'id'    => (int)$r['section_id'],
        'label' => trim(($r['grade_level'] ?? '') . ' - ' . $r['section'], ' -')
    ];
}

$success_msg = $_SESSION['student_success'] ?? '';
$error_msg   = $_SESSION['student_error'] ?? '';
unset($_SESSION['student_success'], $_SESSION['student_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_student'])) {

    $teacher_subject_ids  = $_POST['teacher_subject_ids'] ?? [];

    if (empty($teacher_subject_ids)) {
        $_SESSION['student_error'] = "Please select at least one subject.";
        header("Location: teacher_students.php");
        exit();
    }

    // ── Bagong student info mula sa form ──
    $new_student_id  = trim($_POST['new_student_id'] ?? '');
    $new_section_id  = intval($_POST['new_section_id'] ?? 0);
    $new_firstname   = trim($_POST['new_firstname'] ?? '');
    $new_middlename  = trim($_POST['new_middlename'] ?? '');
    $new_lastname    = trim($_POST['new_lastname'] ?? '');
    $new_suffix      = trim($_POST['new_suffix'] ?? '');
    $new_lrn         = trim($_POST['new_lrn'] ?? '');
    $new_birthdate   = trim($_POST['new_birthdate'] ?? '');
    $new_age         = trim($_POST['new_age'] ?? '');
    $new_gender      = trim($_POST['new_gender'] ?? '');
    $new_contact     = trim($_POST['new_contact_number'] ?? '');
    $new_guard_name  = trim($_POST['new_guardian_name'] ?? '');
    $new_guard_email = trim($_POST['new_guardian_email'] ?? '');
    $new_address     = trim($_POST['new_address'] ?? '');
    $new_password    = $_POST['new_password'] ?? '';

    if ($new_student_id === '' || $new_firstname === '' || $new_lastname === '' || $new_password === '') {
        $_SESSION['student_error'] = "Please fill in all required fields (Student ID, First Name, Last Name, Password).";
        header("Location: teacher_students.php");
        exit();
    }

    // Check kung existing na yung student_id o lrn
    $dupCheck = $conn->prepare("SELECT id FROM tblstudents WHERE student_id = ? OR (lrn <> '' AND lrn = ?)");
    $dupCheck->bind_param("ss", $new_student_id, $new_lrn);
    $dupCheck->execute();
    $dupCheck->store_result();

    if ($dupCheck->num_rows > 0) {
        $dupCheck->close();
        $_SESSION['student_error'] = "Student ID or LRN already exists.";
        header("Location: teacher_students.php");
        exit();
    }
    $dupCheck->close();

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $status_new       = 'active';

    $insStudent = $conn->prepare("
        INSERT INTO tblstudents
            (student_id, section_id, password, firstname, middlename, lastname,
             suffix, lrn, birthdate, age, gender, contact_number, guardian_name,
             guardian_email, address, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $insStudent->bind_param(
        str_repeat("s", 16),
        $new_student_id, $new_section_id, $hashed_password,
        $new_firstname, $new_middlename, $new_lastname, $new_suffix, $new_lrn,
        $new_birthdate, $new_age, $new_gender, $new_contact, $new_guard_name,
        $new_guard_email, $new_address, $status_new
    );

    if (!$insStudent->execute()) {
        $insStudent->close();
        $_SESSION['student_error'] = "Failed to create student: " . $conn->error;
        header("Location: teacher_students.php");
        exit();
    }

    $student_id = $insStudent->insert_id;
    $insStudent->close();

    // ── Assign subjects sa tbl_teachers_students ──
    $inserted = 0;
    $skipped  = 0;

    $insert = $conn->prepare("
        INSERT INTO tbl_teachers_students
            (teacher_id, student_id, teacher_subject_id, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ");

    $checkExisting = $conn->prepare("
        SELECT id FROM tbl_teachers_students
        WHERE teacher_id = ? AND student_id = ? AND teacher_subject_id = ?
    ");

    foreach ($teacher_subject_ids as $tsid) {
        $tsid = intval($tsid);
        if ($tsid < 1) continue;

        $checkExisting->bind_param("iii", $teacher_id, $student_id, $tsid);
        $checkExisting->execute();
        $checkExisting->store_result();

        if ($checkExisting->num_rows > 0) {
            $skipped++;
            $checkExisting->free_result();
            continue;
        }
        $checkExisting->free_result();

        $insert->bind_param("iii", $teacher_id, $student_id, $tsid);
        if ($insert->execute()) {
            $inserted++;
        }
    }

    $insert->close();
    $checkExisting->close();

    if ($inserted > 0) {
        $_SESSION['student_success'] = "Student added with $inserted subject(s).";
        if ($skipped > 0) {
            $_SESSION['student_success'] .= " ($skipped already existed, skipped.)";
        }
    } elseif ($skipped > 0) {
        $_SESSION['student_error'] = "Student already added for all selected subjects.";
    } else {
        $_SESSION['student_error'] = "Nothing was inserted.";
    }

    header("Location: teacher_students.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {

    $eid              = intval($_POST['edit_id'] ?? 0);
    $stat             = trim($_POST['edit_status'] ?? 'active');
    $edit_subject_ids = $_POST['edit_subject_ids'] ?? [];

    $edit_section_id  = intval($_POST['edit_section_id'] ?? 0);
    $edit_firstname   = trim($_POST['edit_firstname'] ?? '');
    $edit_middlename  = trim($_POST['edit_middlename'] ?? '');
    $edit_lastname    = trim($_POST['edit_lastname'] ?? '');
    $edit_suffix      = trim($_POST['edit_suffix'] ?? '');
    $edit_lrn         = trim($_POST['edit_lrn'] ?? '');
    $edit_birthdate   = trim($_POST['edit_birthdate'] ?? '');
    $edit_age         = trim($_POST['edit_age'] ?? '');
    $edit_gender      = trim($_POST['edit_gender'] ?? '');
    $edit_contact     = trim($_POST['edit_contact_number'] ?? '');
    $edit_guard_name  = trim($_POST['edit_guardian_name'] ?? '');
    $edit_guard_email = trim($_POST['edit_guardian_email'] ?? '');
    $edit_address     = trim($_POST['edit_address'] ?? '');
    $edit_password    = $_POST['edit_password'] ?? ''; // optional - palitan lang kung may laman

    if ($eid < 1) {

        $_SESSION['student_error'] = "Invalid student record.";

    } elseif ($edit_firstname === '' || $edit_lastname === '') {

        $_SESSION['student_error'] = "First name and last name are required.";

    } elseif (empty($edit_subject_ids)) {

        $_SESSION['student_error'] = "Please select at least one subject.";

    } else {

        if ($edit_password !== '') {
            $hashed = password_hash($edit_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                UPDATE tblstudents
                SET section_id = ?, firstname = ?, middlename = ?, lastname = ?,
                    suffix = ?, lrn = ?, birthdate = ?, age = ?, gender = ?, contact_number = ?,
                    guardian_name = ?, guardian_email = ?, address = ?, status = ?, password = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param(
                str_repeat("s", 15) . "i",
                $edit_section_id, $edit_firstname, $edit_middlename, $edit_lastname,
                $edit_suffix, $edit_lrn, $edit_birthdate, $edit_age, $edit_gender, $edit_contact,
                $edit_guard_name, $edit_guard_email, $edit_address, $stat, $hashed, $eid
            );
        } else {
            $stmt = $conn->prepare("
                UPDATE tblstudents
                SET section_id = ?, firstname = ?, middlename = ?, lastname = ?,
                    suffix = ?, lrn = ?, birthdate = ?, age = ?, gender = ?, contact_number = ?,
                    guardian_name = ?, guardian_email = ?, address = ?, status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param(
                str_repeat("s", 14) . "i",
                $edit_section_id, $edit_firstname, $edit_middlename, $edit_lastname,
                $edit_suffix, $edit_lrn, $edit_birthdate, $edit_age, $edit_gender, $edit_contact,
                $edit_guard_name, $edit_guard_email, $edit_address, $stat, $eid
            );
        }

        if ($stmt->execute()) {

            $stmt->close();

            // Get current assigned subjects
            $curStmt = $conn->prepare("
                SELECT teacher_subject_id
                FROM tbl_teachers_students
                WHERE teacher_id = ?
                AND student_id = ?
            ");
            $curStmt->bind_param("ii", $teacher_id, $eid);
            $curStmt->execute();
            $curRes = $curStmt->get_result();

            $currentSubjects = [];
            while ($row = $curRes->fetch_assoc()) {
                $currentSubjects[] = (int)$row['teacher_subject_id'];
            }
            $curStmt->close();

            $newSubjects = array_map('intval', $edit_subject_ids);

            $toAdd = array_diff($newSubjects, $currentSubjects);

            if (!empty($toAdd)) {
                $insStmt = $conn->prepare("
                    INSERT INTO tbl_teachers_students
                    (teacher_id, student_id, teacher_subject_id, created_at, updated_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                foreach ($toAdd as $tsid) {
                    $insStmt->bind_param("iii", $teacher_id, $eid, $tsid);
                    $insStmt->execute();
                }
                $insStmt->close();
            }

            $toRemove = array_diff($currentSubjects, $newSubjects);

            if (!empty($toRemove)) {
                $delStmt = $conn->prepare("
                    DELETE FROM tbl_teachers_students
                    WHERE teacher_id = ?
                    AND student_id = ?
                    AND teacher_subject_id = ?
                ");
                foreach ($toRemove as $tsid) {
                    $delStmt->bind_param("iii", $teacher_id, $eid, $tsid);
                    $delStmt->execute();
                }
                $delStmt->close();
            }

            $_SESSION['student_success'] = "Student updated successfully!";

        } else {
            $_SESSION['student_error'] = "Failed to update student.";
        }
    }

    header("Location: teacher_students.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {

    $did = intval($_POST['delete_id'] ?? 0);

    if ($did > 0) {

        // Verify if this student belongs to the teacher
        $verify = $conn->prepare("
            SELECT id 
            FROM tbl_teachers_students 
            WHERE student_id = ? 
            AND teacher_id = ?
        ");

        $verify->bind_param("ii", $did, $teacher_id);
        $verify->execute();
        $verify->store_result();

        if ($verify->num_rows > 0) {

            $verify->close();

            // Delete from tbl_teachers_students first
            $stmt1 = $conn->prepare("
                DELETE FROM tbl_teachers_students
                WHERE student_id = ?
                AND teacher_id = ?
            ");
            $stmt1->bind_param("ii", $did, $teacher_id);
            $stmt1->execute();
            $stmt1->close();
            
            // Then delete the student from tblstudents entirely
            $stmt2 = $conn->prepare("
                DELETE FROM tblstudents
                WHERE id = ?
            ");
            $stmt2->bind_param("i", $did);
            
            if ($stmt2->execute() && $stmt2->affected_rows > 0) {
                $_SESSION['student_success'] = "Student deleted successfully.";
            } else {
                $_SESSION['student_error'] = "Failed to delete student record.";
            }
            
            $stmt2->close();

        } else {

            $verify->close();

            $_SESSION['student_error'] = "Student not found or you don't have permission to delete this record.";
        }
    }

    header("Location: teacher_students.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {

    $tid   = intval($_POST['toggle_id'] ?? 0);
    $tstat = ($_POST['toggle_val'] ?? '') === 'active' ? 'active' : 'inactive';

    if ($tid > 0) {

        $stmt = $conn->prepare("
            UPDATE tblstudents 
            SET status = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param("si", $tstat, $tid);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['student_success'] = "Student status updated to $tstat.";
            } else {
                $_SESSION['student_error'] = "Record not found or no changes made.";
            }
        } else {
            $_SESSION['student_error'] = "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    header("Location: teacher_students.php");
    exit();
}

$students_db = [];
$stmt = $conn->prepare("
    SELECT 
        s.id, s.student_id, s.firstname, s.middlename, s.suffix, s.lrn, s.birthdate, s.age, s.gender,
        s.contact_number, s.guardian_name, s.guardian_email, s.address, s.lastname, s.profile_pic, 
        s.section_id, s.status, s.created_at,
        sec.section AS section_name, sec.grade_level_id,
        gl.grade_level,
        GROUP_CONCAT(DISTINCT st.strand ORDER BY st.strand SEPARATOR ', ') AS strand,
        GROUP_CONCAT(DISTINCT st.track  ORDER BY st.track  SEPARATOR ', ') AS track,
        GROUP_CONCAT(DISTINCT sub.subject ORDER BY sub.subject SEPARATOR ', ') AS subjects
    FROM tblstudents s

    LEFT JOIN tbl_teachers_students tts 
        ON s.id = tts.student_id
        AND tts.teacher_id = ?

    LEFT JOIN tbl_teacher_subjects ts2
        ON tts.teacher_subject_id = ts2.teacher_subject_id

    LEFT JOIN tblsubjects sub 
        ON ts2.subject_id = sub.subject_id

    LEFT JOIN tblstrands st 
        ON sub.strand_id = st.strand_id

    LEFT JOIN tblsections sec 
        ON s.section_id = sec.section_id

    LEFT JOIN tblgrade_levels gl 
        ON sec.grade_level_id = gl.grade_id

    GROUP BY 
        s.id, s.student_id, s.firstname, s.lastname, s.profile_pic,
        s.section_id, s.status, s.created_at,
        sec.section, sec.grade_level_id, gl.grade_level

    ORDER BY s.created_at DESC
");

$stmt->bind_param("i", $teacher_id);
 $stmt->execute();
 $res = $stmt->get_result();
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $fn = $r['firstname'] ?? '';
        $ln = $r['lastname'] ?? '';
        $name = trim("$fn $ln");

        $joined = '';
        if (!empty($r['created_at'])) {
            $joined = date_create($r['created_at'])->format('M d, Y');
        }

        $grade_label = '';
        if (!empty($r['grade_level'])) {
            $grade_label = $r['grade_level'];
            if (!empty($r['section_name'])) $grade_label .= ' - ' . $r['section_name'];
        } elseif (!empty($r['section_name'])) {
            $grade_label = $r['section_name'];
        }

        $students_db[] = [
            'id'          => (int)$r['id'],
            'firstname'   => htmlspecialchars($r['firstname'] ?? ''),
            'lastname'    => htmlspecialchars($r['lastname'] ?? ''),
            'name'        => htmlspecialchars($name),
            'student_id'  => htmlspecialchars($r['student_id']),
            'subjects' => htmlspecialchars($r['subjects'] ?? ''),
            'grade_label' => htmlspecialchars($grade_label),
            'section_name'=> htmlspecialchars($r['section_name'] ?? ''),
            'grade_level' => htmlspecialchars($r['grade_level'] ?? ''),
            'strand'      => htmlspecialchars($r['strand'] ?? ''),
            'track'       => htmlspecialchars($r['track'] ?? ''),
            'profile_pic' => !empty($r['profile_pic']) 
            ? '../uploads/profile_pic/students/' . $r['profile_pic'] 
            : '',
            'status'      => $r['status'] ?? 'active',
            'joined'      => $joined,
            'section_id'     => (int)($r['section_id'] ?? 0),
            'raw_section_id'  => (int)($r['section_id'] ?? 0),
            'middlename'      => $r['middlename'] ?? '',
            'suffix'          => $r['suffix'] ?? '',
            'lrn'             => $r['lrn'] ?? '',
            'birthdate'       => $r['birthdate'] ?? '',
            'age'             => $r['age'] ?? '',
            'gender'          => $r['gender'] ?? '',
            'contact_number'  => $r['contact_number'] ?? '',
            'guardian_name'   => $r['guardian_name'] ?? '',
            'guardian_email'  => $r['guardian_email'] ?? '',
            'address'         => $r['address'] ?? ''
        ];
    }
}
 $stmt->close();

// Fetch current teacher_subject_ids per student for this teacher
$subjMap = [];
$smStmt = $conn->prepare("
    SELECT student_id, teacher_subject_id
    FROM tbl_teachers_students
    WHERE teacher_id = ?
");
$smStmt->bind_param("i", $teacher_id);
$smStmt->execute();
$smRes = $smStmt->get_result();
while ($smRow = $smRes->fetch_assoc()) {
    $sid = (int)$smRow['student_id'];
    if (!isset($subjMap[$sid])) $subjMap[$sid] = [];
    $subjMap[$sid][] = (int)$smRow['teacher_subject_id'];
}
$smStmt->close();

// Attach subject_ids to each student
foreach ($students_db as &$sdb) {
    $sdb['subject_ids'] = $subjMap[$sdb['id']] ?? [];
}
unset($sdb);

$sections_all = [];
$sec_res = $conn->query("
    SELECT DISTINCT 
        sec.section_id, 
        sec.section, 
        sec.grade_level_id, 
        gl.grade_level
    FROM tblsections sec
    LEFT JOIN tblgrade_levels gl 
        ON sec.grade_level_id = gl.grade_id
    INNER JOIN tblstudents s 
        ON s.section_id = sec.section_id
    ORDER BY gl.grade_level ASC, sec.section ASC
");
if ($sec_res) {
    while ($r = $sec_res->fetch_assoc()) {
        $label = trim(($r['grade_level'] ?? '') . ' - ' . ($r['section'] ?? ''), ' -');
        $sections_all[] = [
            'id'             => (int)$r['section_id'],
            'grade_level_id' => (int)($r['grade_level_id'] ?? 0),
            'label'          => htmlspecialchars($label)
        ];
    }
}

$sql_subjects = "
    SELECT COUNT(*) AS total_subjects
    FROM tbl_teacher_subjects
    WHERE teacher_id = ?
";

$stmt = $conn->prepare($sql_subjects);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total_subjects_badge = $row['total_subjects'] ?? 0;
$stmt->close();

$sql_quizzes = "
    SELECT COUNT(*) AS total_quizzes
    FROM tblquizzes q
    INNER JOIN tbl_teacher_lessons l
        ON q.lesson_id = l.lesson_id
    INNER JOIN tbl_teacher_subjects s
        ON l.teacher_subject_id = s.teacher_subject_id
    WHERE s.teacher_id = ?
";

$stmt = $conn->prepare($sql_quizzes);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total_quizzes = $row['total_quizzes'] ?? 0;

$stmt->close();

$sql_lessons = "
    SELECT COUNT(*) AS total_lessons
    FROM tbl_teacher_lessons l
    INNER JOIN tbl_teacher_subjects s 
        ON l.teacher_subject_id = s.teacher_subject_id
    WHERE s.teacher_id = ?
";

$stmt = $conn->prepare($sql_lessons);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total_lessons_badge = $row['total_lessons'] ?? 0;
$stmt->close();

$badge_students = (int)$conn->query("
    SELECT COUNT(DISTINCT student_id) FROM tbl_teachers_students
    WHERE teacher_subject_id IN (SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = $teacher_id)
")->fetch_row()[0];

$badge_virtualclass = (int)$conn->query("
    SELECT COUNT(*) FROM tblvirtualclasses 
    WHERE teacher_id = $teacher_id AND status IN ('upcoming','live')
")->fetch_row()[0];

$badge_gradebook = (int)$conn->query("
    SELECT COUNT(DISTINCT r.student_id)
    FROM tblquiz_results r
    JOIN tblquizzes q ON q.quiz_id = r.quizzes_id
    JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    JOIN tbl_teacher_subjects s ON s.teacher_subject_id = l.teacher_subject_id
    WHERE s.teacher_id = $teacher_id
")->fetch_row()[0];

// ── Notifications ──────────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT notification_id, title, message, type, status, created_at
    FROM tblnotifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 30
");
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unread_count = count(array_filter($notifications, fn($n) => $n['status'] === 'unread'));

// ── Notification URL mapper ────────────────────────────────────────────────
function getNotifUrl(string $type): string {
    return match($type) {
        'new_subject_assigned'   => 'teacher_courses.php',
        'new_lesson',
        'lesson_expiring',
        'lesson_expired'         => 'teacher_lessons.php',
        'new_quiz',
        'quiz_due_soon',
        'quiz_overdue'           => 'teacher_quizzes.php',
        'quiz_passed',
        'quiz_failed',
        'student_submitted_quiz' => 'teacher_grades.php',
        'new_announcement'       => 'index.php#announcements',
        default                  => '#',
    };
}

$valid_subject_list = !empty($subjects)
    ? implode(',', array_map(fn($s) => (int)$s['teacher_subject_id'], $subjects))
    : '0';

$total_tasks_badge = 0;
$tb = $conn->query("SELECT COUNT(*) AS cnt FROM tblperformance_tasks WHERE teacher_subject_id IN ($valid_subject_list)");
if ($tb) $total_tasks_badge = (int)($tb->fetch_assoc()['cnt'] ?? 0);

$pending_review_badge = 0;
$pr = $conn->query("
    SELECT COUNT(*) AS cnt FROM tblperformance_task_submissions sub
    INNER JOIN tblperformance_tasks t ON t.task_id = sub.task_id
    WHERE t.teacher_subject_id IN ($valid_subject_list) AND sub.status = 'submitted'
");
if ($pr) $pending_review_badge = (int)($pr->fetch_assoc()['cnt'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ExceLearn LMS — Teacher Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/teacher/teacher_students.css">
<style>
.toolbar-top {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 10px;
}
.toolbar-top .search-wrap {
  flex: 1;
  min-width: 0;
}
.toolbar-top .sort-select {
  flex-shrink: 0;
  width: auto;
  min-width: 100px;
}
.toolbar-bottom {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.toolbar-bottom::-webkit-scrollbar {
  display: none;
}
.toolbar-bottom .filter-tabs {
  display: flex;
  gap: 6px;
  flex-wrap: nowrap;
  min-width: max-content;
}
@media (max-width: 768px) {
  .toolbar-top {
    flex-wrap: nowrap;
  }
  .toolbar-top .sort-select {
    min-width: 80px;
    font-size: 12px;
  }
}
@media (max-width: 768px) {
  .hide-mobile { display: none !important; }
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
<body>
<div class="layout">
<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <img src="../assets/img/Excelsior_Seal_Logo.png" alt="ExceLearn Logo"
         style="height:38px;width:auto;object-fit:contain;flex-shrink:0;">
    <div style="min-width:0">
      <div style="font-weight:800;font-size:1.15rem;letter-spacing:-0.05em;line-height:1;white-space:nowrap;">
        Exce<span style="color:#22c55e;">Learn</span>
      </div>
      <div style="font-size:0.7rem;color:#9ca3af;white-space:nowrap;margin-top:2px;">
        Learn Better. Anytime, Anywhere.
      </div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a class="nav-item" href="index.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <div class="nav-section-label" style="margin-top:14px">Teaching</div>
    <a class="nav-item" href="teacher_courses.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Subjects
      <span class="nav-badge" style="background:var(--accent);color:#fff"><?php echo $total_subjects_badge; ?></span>
    </a>
    <a class="nav-item" href="teacher_performance_tasks.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>
      Performance Tasks <span class="nav-badge"><?php echo $total_tasks_badge; ?></span>
      <?php if ($pending_review_badge > 0): ?><span class="nav-badge" style="background:var(--red,#ef4444);color:#fff;margin-left:4px;"><?= $pending_review_badge ?></span><?php endif; ?>
    </a>
    <a class="nav-item" href="teacher_virtual_class.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
      Virtual Class
      <?php if ($badge_virtualclass > 0): ?><span class="nav-badge" style="background:var(--cyan,#06b6d4);color:#000"><?= $badge_virtualclass ?></span><?php endif; ?>
    </a>
    <div class="nav-section-label" style="margin-top:14px">Students & Grades</div>
    <a class="nav-item active" href="teacher_students.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Students
      <span class="nav-badge" style="background:var(--green);color:#fff" id="student-count-badge"><?php echo $badge_students; ?></span>
    </a>
    <a class="nav-item" href="teacher_grades.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Gradebook
      <?php if ($badge_gradebook > 0): ?><span class="nav-badge" style="background:var(--purple);color:#fff"><?= $badge_gradebook ?></span><?php endif; ?>
    </a>
    <!--<a class="nav-item" href="teacher_reports.php">-->
    <!--  <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>-->
    <!--  Reports-->
    <!--</a>-->
    <div class="nav-section-label" style="margin-top:14px">Account</div>
    <a class="nav-item" href="teacher_profile.php">
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
    <div style="margin-top:8px;border-top:1px solid rgba(255,255,255,0.08);padding-top:8px"></div>
    <a class="nav-item" href="#" onclick="openLogoutModal(); return false;" style="color:var(--red)">
        <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Logout
    </a>
  </nav>
<div class="sidebar-footer">
  <div class="user-card">
    <div class="user-avatar" style="background:linear-gradient(135deg,var(--accent),var(--purple)); overflow:hidden;">
      <?php if (!empty($profile_pic)): ?>
        <img src="<?php echo htmlspecialchars($profile_pic); ?>" 
            alt="Profile" 
            style="width:100%;height:100%;object-fit:cover;">
      <?php else: ?>
        <?php echo htmlspecialchars($initials); ?>
      <?php endif; ?>
    </div>
    <div class="user-info">
      <p><?php echo htmlspecialchars($display_name); ?></p>
      <span>Faculty-<?php echo htmlspecialchars(ucfirst($user['role'] ?? 'User')); ?></span>
    </div>
  </div>
</div>
</aside>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- MAIN -->
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
  <div class="topbar-title"></div>
  <div class="topbar-actions">
    <button class="btn-icon" onclick="openNotifModal()" aria-label="Notifications" style="position:relative;">
      🔔
      <?php if ($unread_count > 0): ?>
      <span class="notif-dot" id="notif-badge" style="position:absolute;top:4px;right:4px;background:var(--red,#ef4444);color:#fff;font-size:9px;font-weight:700;border-radius:999px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 3px;line-height:1;"><?= $unread_count ?></span>
      <?php else: ?>
      <span class="notif-dot" id="notif-badge" style="display:none"></span>
      <?php endif; ?>
    </button>
  </div>
</div>

  <div class="content">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div>
        <div class="page-title">Student Directory</div>
        <div class="page-sub" id="student-count-text">Manage student accounts and access credentials.</div>
      </div>
      <div class="page-actions">
        <button class="btn btn-primary btn-sm" onclick="openCreateModal()">+ Add Student</button>
      </div>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar">
      <div class="toolbar-top">
        <div class="search-wrap">
          <span class="search-icon">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </span>
          <input type="text" id="searchInput" placeholder="Search by name or ID...">
        </div>
        <select class="sort-select" id="sortSelect">
          <option value="newest">Newest</option>
          <option value="name-asc">A–Z</option>
          <option value="id-asc">ID</option>
          <option value="grade">Grade</option>
          <option value="section">Section</option>
        </select>
      </div>
      <div class="toolbar-bottom">
        <div class="filter-tabs" id="filterTabs">
          <div class="filter-tab active" data-filter="all">All Students</div>
          <div class="filter-tab" data-filter="active">Active</div>
          <div class="filter-tab" data-filter="inactive">Inactive</div>
        </div>
      </div>
    </div>

    <!-- STUDENT GRID -->
    <div class="student-list" id="studentList"></div>
  </div>
</main>
</div>

<!-- ═══ CREATE STUDENT MODAL ═══ -->
<div class="modal-overlay" id="createModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add New Student</div>
      <button class="modal-close" onclick="closeCreateModal()">✕</button>
    </div>
    <form method="POST" action="teacher_students.php" id="createForm">
      <input type="hidden" name="create_student" value="1">
        <div class="modal-body">
        
        <div id="newStudentFields">
          <div class="form-group"><label>Student ID *</label><input type="text" name="new_student_id" id="newStudentId" placeholder="e.g. STUD-2025-00123"></div>
          <div class="form-group"><label>LRN</label><input type="text" name="new_lrn" placeholder="e.g. 123456789012"></div>
          <div class="form-group"><label>First Name *</label><input type="text" name="new_firstname" id="newFirstname" placeholder="e.g. Juan"></div>
          <div class="form-group"><label>Middle Name</label><input type="text" name="new_middlename" placeholder="e.g. Reyes"></div>
          <div class="form-group"><label>Last Name *</label><input type="text" name="new_lastname" id="newLastname" placeholder="e.g. Dela Cruz"></div>
          <div class="form-group"><label>Suffix</label><input type="text" name="new_suffix" placeholder="e.g. Jr., III"></div>
          <div class="form-group"><label>Section</label>
            <select name="new_section_id">
              <option value="0">— None —</option>
              <?php foreach ($all_sections as $sec): ?>
                <option value="<?php echo $sec['id']; ?>"><?php echo htmlspecialchars($sec['label']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Birthdate</label><input type="date" name="new_birthdate" id="newBirthdate" onchange="document.getElementById('newAge').value = computeAgeFromBirthdate(this.value);"></div>
          <div class="form-group"><label>Age</label><input type="number" name="new_age" id="newAge" min="0" placeholder="e.g. 17" readonly></div>
          <div class="form-group"><label>Gender</label>
            <select name="new_gender"><option value="">— Select —</option><option value="Male">Male</option><option value="Female">Female</option></select>
          </div>
          <div class="form-group"><label>Contact Number</label><input type="text" name="new_contact_number" placeholder="e.g. 09171234567" inputmode="numeric" maxlength="11" oninput="this.value=this.value.replace(/\D/g,'').slice(0,11)"></div>
          <div class="form-group"><label>Guardian Name</label><input type="text" name="new_guardian_name" placeholder="e.g. Maria Dela Cruz"></div>
          <div class="form-group"><label>Guardian Email</label><input type="email" name="new_guardian_email" placeholder="e.g. guardian@gmail.com"></div>
          <div class="form-group"><label>Address</label><input type="text" name="new_address" placeholder="e.g. Brgy. San Isidro, Tanauan, Leyte"></div>
          <div class="form-group"><label>Password *</label><input type="password" name="new_password" id="newPassword" placeholder="e.g. minimum 8 characters"></div>
        </div>
        
        <div class="form-group">
          <label>Select Subject(s) * <span style="font-weight:400;color:var(--text3);font-size:0.75rem">(choose one or more)</span></label>
          <div id="subjectCheckboxList" style="
              display:flex;
              flex-direction:column;
              gap:8px;
              max-height:200px;
              overflow-y:auto;
              padding:4px 2px;
          ">
            <?php if (empty($teacher_subjects)): ?>
              <span style="color:var(--text3);font-size:0.875rem">No subjects assigned to you.</span>
            <?php else: ?>
              <?php foreach ($teacher_subjects as $sub): ?>
                <label class="create-subject-label" data-strand-id="<?php echo $sub['strand_id']; ?>" style="
                    display:flex;
                    align-items:center;
                    gap:10px;
                    padding:10px 14px;
                    border-radius:10px;
                    border:1px solid var(--border);
                    cursor:pointer;
                    transition:background .15s;
                    font-size:0.875rem;
                "
                onmouseover="this.style.background='rgba(99,102,241,.08)'"
                onmouseout="this.style.background='transparent'"
                >
                  <input type="checkbox"
                         name="teacher_subject_ids[]"
                         value="<?php echo $sub['teacher_subject_id']; ?>"
                         style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer;">
                  <?php echo htmlspecialchars($sub['subject']); ?>
                </label>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        
        </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeCreateModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Account</button>
      </div>
    </form>
  </div>
</div>
<!-- NOTIFICATION MODAL -->
<div class="modal-overlay" id="notifModal">
  <div class="modal" style="max-width:460px;max-height:80vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="flex-shrink:0;display:flex;align-items:center;justify-content:space-between;">
      <div class="modal-title">🔔 Notifications
        <span id="notif-unread-count" style="font-size:11px;font-weight:500;color:var(--text3);margin-left:6px;"><?= $unread_count > 0 ? "($unread_count unread)" : '' ?></span>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <?php if ($unread_count > 0): ?>
        <button class="btn btn-ghost btn-sm" onclick="markAllRead()" style="font-size:11px;padding:4px 10px;">✓ Mark all read</button>
        <?php endif; ?>
        <button class="modal-close" onclick="closeNotifModal()">✕</button>
      </div>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1;padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
      <?php if (empty($notifications)): ?>
        <div style="text-align:center;padding:40px 16px;color:var(--text3);">
          <div style="font-size:36px;margin-bottom:8px;">🔕</div>
          <div style="font-size:13px;">No notifications yet.</div>
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $notif):
          $n_ts   = strtotime($notif['created_at'] . ' Asia/Manila');
          $n_diff = time() - $n_ts;
          if ($n_diff < 60)         $n_time = 'Just now';
          elseif ($n_diff < 3600)   $n_time = floor($n_diff/60).'m ago';
          elseif ($n_diff < 86400)  $n_time = floor($n_diff/3600).'h ago';
          elseif ($n_diff < 604800) $n_time = floor($n_diff/86400).'d ago';
          else                      $n_time = date('M j, Y', $n_ts);
          $is_unread = $notif['status'] === 'unread';

          $n_icon = match($notif['type']) {
            'new_lesson'             => '📖',
            'new_quiz'               => '📝',
            'quiz_passed'            => '🎉',
            'quiz_failed'            => '😞',
            'quiz_due_soon'          => '⏰',
            'quiz_overdue'           => '🚨',
            'new_subject_assigned'   => '📚',
            'student_submitted_quiz' => '📬',
            'new_announcement'       => '📢',
            'lesson_expiring'        => '⌛',
            'lesson_expired'         => '🔒',
            default                  => '🔔',
          };
        ?>
        <div class="notif-item" data-id="<?= $notif['notification_id'] ?>"
             data-url="<?= htmlspecialchars(getNotifUrl($notif['type'])) ?>"
             onclick="markOneRead(<?= $notif['notification_id'] ?>, this)"
             style="display:flex;gap:12px;align-items:flex-start;padding:10px 12px;border-radius:10px;cursor:pointer;transition:background .15s;
                    background:<?= $is_unread ? 'rgba(99,102,241,0.08)' : 'var(--bg2,rgba(255,255,255,.03))' ?>;
                    border:1px solid <?= $is_unread ? 'rgba(99,102,241,0.2)' : 'var(--border,rgba(255,255,255,.07))' ?>;">
          <div style="font-size:20px;flex-shrink:0;margin-top:1px;"><?= $n_icon ?></div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:<?= $is_unread ? '700' : '500' ?>;color:var(--text);margin-bottom:2px;line-height:1.3;">
              <?= htmlspecialchars($notif['title']) ?>
              <?php if ($is_unread): ?>
              <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--accent,#6366f1);margin-left:5px;vertical-align:middle;"></span>
              <?php endif; ?>
            </div>
            <div style="font-size:12px;color:var(--text3);line-height:1.45;margin-bottom:4px;">
              <?= htmlspecialchars($notif['message']) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:2px;">
              <div style="font-size:10px;color:var(--text3);opacity:.7;"><?= $n_time ?></div>
              <button onclick="deleteNotif(<?= $notif['notification_id'] ?>, this, event)"
                      style="font-size:10px;padding:2px 8px;border-radius:6px;border:none;background:rgba(239,68,68,0.12);color:#ef4444;cursor:pointer;line-height:1.5;">🗑 Delete</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Student</div>
      <button class="modal-close" onclick="closeEditModal()">✕</button>
    </div>
    <form method="POST" action="teacher_students.php" id="editForm">
      <input type="hidden" name="update_student" value="1">
      <input type="hidden" name="edit_id" id="editId">
        <div class="modal-body">
            <div class="form-group"><label>First Name *</label><input type="text" name="edit_firstname" id="editFirstname" placeholder="e.g. Juan"></div>
            <div class="form-group"><label>Middle Name</label><input type="text" name="edit_middlename" id="editMiddlename" placeholder="e.g. Reyes"></div>
            <div class="form-group"><label>Last Name *</label><input type="text" name="edit_lastname" id="editLastname" placeholder="e.g. Dela Cruz"></div>
            <div class="form-group"><label>Suffix</label><input type="text" name="edit_suffix" id="editSuffix" placeholder="e.g. Jr., III"></div>
            <div class="form-group"><label>LRN</label><input type="text" name="edit_lrn" id="editLrn" placeholder="e.g. 123456789012"></div>
            <div class="form-group"><label>Section</label>
              <select name="edit_section_id" id="editSectionId">
                <option value="0">— None —</option>
                <?php foreach ($all_sections as $sec): ?>
                  <option value="<?php echo $sec['id']; ?>"><?php echo htmlspecialchars($sec['label']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group"><label>Birthdate</label><input type="date" name="edit_birthdate" id="editBirthdate" onchange="document.getElementById('editAge').value = computeAgeFromBirthdate(this.value);"></div>
            <div class="form-group"><label>Age</label><input type="number" name="edit_age" id="editAge" placeholder="e.g. 17" readonly></div>
            <div class="form-group"><label>Gender</label>
              <select name="edit_gender" id="editGender"><option value="">— Select —</option><option value="Male">Male</option><option value="Female">Female</option></select>
            </div>
            <div class="form-group"><label>Contact Number</label><input type="text" name="edit_contact_number" id="editContact" placeholder="e.g. 09171234567" inputmode="numeric" maxlength="11" oninput="this.value=this.value.replace(/\D/g,'').slice(0,11)"></div>
            <div class="form-group"><label>Guardian Name</label><input type="text" name="edit_guardian_name" id="editGuardName" placeholder="e.g. Maria Dela Cruz"></div>
            <div class="form-group"><label>Guardian Email</label><input type="email" name="edit_guardian_email" id="editGuardEmail" placeholder="e.g. guardian@gmail.com"></div>
            <div class="form-group"><label>Address</label><input type="text" name="edit_address" id="editAddress" placeholder="e.g. Brgy. San Isidro, Tanauan, Leyte"></div>
            <div class="form-group"><label>New Password <span style="font-weight:400;color:var(--text3);font-size:0.75rem">(leave blank para hindi mapalitan)</span></label><input type="password" name="edit_password" placeholder="e.g. minimum 8 characters"></div>
                <div class="form-group">
                <label>Status</label>
                <select name="edit_status" id="editStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                </div>
                <div class="form-group">
                <label>Subjects * <span style="font-weight:400;color:var(--text3);font-size:0.75rem">(check to add, uncheck to remove)</span></label>
                <div id="editSubjectCheckboxList" style="
                    display:flex;
                    flex-direction:column;
                    gap:8px;
                    max-height:200px;
                    overflow-y:auto;
                    padding:4px 2px;
                ">
                    <?php if (empty($teacher_subjects)): ?>
                    <span style="color:var(--text3);font-size:0.875rem">No subjects assigned to you.</span>
                    <?php else: ?>
                    <?php foreach ($teacher_subjects as $sub): ?>
                        <label class="edit-subject-label" style="
                            display:flex;
                            align-items:center;
                            gap:10px;
                            padding:10px 14px;
                            border-radius:10px;
                            border:1px solid var(--border);
                            cursor:pointer;
                            transition:background .15s;
                            font-size:0.875rem;
                        "
                        onmouseover="this.style.background='rgba(99,102,241,.08)'"
                        onmouseout="this.style.background='transparent'"
                        >
                        <input type="checkbox"
                                name="edit_subject_ids[]"
                                class="edit-subject-cb"
                                value="<?php echo $sub['teacher_subject_id']; ?>"
                                style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer;">
                        <?php echo htmlspecialchars($sub['subject']); ?>
                        </label>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                </div>
            </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<form method="POST" action="teacher_students.php" id="deleteForm" style="display:none">
  <input type="hidden" name="delete_student" value="1">
  <input type="hidden" name="delete_id" id="deleteIdInput">
</form>

<form method="POST" action="teacher_students.php" id="toggleForm" style="display:none">
  <input type="hidden" name="toggle_status" value="1">
  <input type="hidden" name="toggle_id" id="toggleIdInput">
  <input type="hidden" name="toggle_val" id="toggleValInput">
</form>

<div class="toast-container" id="toast-container"></div>
<div class="modal-overlay" id="logoutModal">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <div class="modal-title">Confirm Logout</div>
      <button class="modal-close" onclick="closeLogoutModal()">✕</button>
    </div>
    <div class="modal-body" style="text-align:center;padding:28px 20px">
      <div style="font-size:48px;margin-bottom:12px">👋</div>
      <div style="font-size:15px;color:var(--text);font-weight:500;margin-bottom:6px">Are you sure you want to log out?</div>
      <div style="font-size:13px;color:var(--text3)">Any unsaved changes will be lost.</div>
    </div>
    <div class="modal-footer" style="justify-content:center;padding:0 20px 24px">
      <button class="btn btn-ghost" onclick="closeLogoutModal()">Cancel</button>
      <button class="btn btn-danger" onclick="confirmLogout()">Yes, Log Out</button>
    </div>
  </div>
</div>
<script>
var students      = <?php echo json_encode($students_db); ?>;
var sectionsAll   = <?php echo json_encode($sections_all); ?>;
var availableStudents = <?php echo json_encode($available_students); ?>;

var currentFilter = 'all';
var currentSort   = 'newest';
var searchQuery   = '';

function getFilteredStudents() {
    var filtered = students.slice();
    if (currentFilter !== 'all') {
        filtered = filtered.filter(function(s){ return s.status === currentFilter; });
    }
    if (searchQuery.trim()) {
        var q = searchQuery.toLowerCase();
        filtered = filtered.filter(function(s){
            return s.name.toLowerCase().indexOf(q)         !== -1 ||
                   s.student_id.toLowerCase().indexOf(q)   !== -1 ||
                   s.grade_label.toLowerCase().indexOf(q)  !== -1 ||
                   s.strand.toLowerCase().indexOf(q)       !== -1 ||
                   s.track.toLowerCase().indexOf(q)        !== -1 ||
                   s.section_name.toLowerCase().indexOf(q) !== -1 ||
                   s.subjects.toLowerCase().indexOf(q)     !== -1;
        });
    }
    filtered.sort(function(a,b){
        switch(currentSort){
            case 'newest':   return b.id - a.id;
            case 'name-asc': return (a.name||'zzz').localeCompare(b.name||'zzz');
            case 'id-asc':   return a.student_id.localeCompare(b.student_id);
            case 'grade':    return a.grade_label.localeCompare(b.grade_label);
            case 'section':  return a.section_name.localeCompare(b.section_name);
            default:         return 0;
        }
    });
    return filtered;
}

function renderStudents() {
    var list     = document.getElementById('studentList');
    var filtered = getFilteredStudents();

    document.getElementById('student-count-badge').textContent = students.length;
    document.getElementById('student-count-text').textContent  =
        'Showing ' + filtered.length + ' of ' + students.length +
        ' student' + (students.length !== 1 ? 's' : '');

    if (filtered.length === 0) {
        list.innerHTML = '<div class="empty-state"><div class="empty-icon">👥</div>' +
            '<p>No students found</p><span>Try adjusting search or add a new student.</span></div>';
        return;
    }

    var gradients = [
        'linear-gradient(135deg,#6c8fff,#b57bee)',
        'linear-gradient(135deg,#22d3ee,#3dd68c)',
        'linear-gradient(135deg,#f5a623,#ff6b6b)',
        'linear-gradient(135deg,#4d6fff,#1e3c72)',
        'linear-gradient(135deg,#e44d90,#f7a44c)',
        'linear-gradient(135deg,#6366f1,#a855f7)'
    ];

    function buildCard(s) {
        var initials = s.name
            ? s.name.split(' ').map(function(n){ return n[0]; }).join('').substring(0,2).toUpperCase()
            : 'S';

        var avatarContent = s.profile_pic
            ? '<img src="' + s.profile_pic + '" style="width:100%;height:100%;object-fit:cover;">'
            : initials;

        var statusColor = s.status === 'active' ? 'var(--green)' : 'var(--text3)';
        var bg          = gradients[s.id % gradients.length];

        var nameDisplay = s.name
            ? '<span class="student-name">' + s.name + '</span>'
            : '<span class="student-name" style="color:var(--text3);font-style:italic">Profile not set up</span>';

        var gradeTag = s.grade_label
            ? '<span style="color:var(--text3);margin:0 4px">•</span>' +
              '<span style="font-size:0.75rem;color:var(--text2)">' + s.grade_label + '</span>'
            : '';

        var subjectPills = '';
        if (s.subjects) {
            subjectPills = s.subjects.split(', ').map(function(sub) {
                return '<span style="' +
                    'display:inline-block;padding:2px 8px;border-radius:20px;' +
                    'background:rgba(99,102,241,.15);color:var(--accent);' +
                    'font-size:0.7rem;font-weight:600;white-space:nowrap;' +
                    '">' + sub + '</span>';
            }).join('');
        } else {
            subjectPills = '<span style="font-size:0.75rem;color:var(--text3)">No subject</span>';
        }

        var strandDisplay = s.strand || 'Unassigned';
        if (s.strand && s.track) strandDisplay += ' (' + s.track + ')';

        var toggleIcon  = s.status === 'active' ? '🔒' : '🔓';
        var toggleTitle = s.status === 'active' ? 'Deactivate' : 'Activate';

        return '<div class="student-card">' +
          '<div class="student-left">' +
            '<div class="student-avatar" style="background:' + bg + ';overflow:hidden;">' +
              avatarContent +
              '<div class="student-status-dot" style="background:' + statusColor + '"></div>' +
            '</div>' +
            '<div class="student-info">' +
              '<div class="student-name-row">' +
                nameDisplay +
                '<span class="student-badge ' + (s.status === 'active' ? 'badge-green' : 'badge-red') + '">' +
                  s.status +
                '</span>' +
              '</div>' +
              '<div class="student-id-row">' +
                '<span class="student-id">' + s.student_id + '</span>' +
                gradeTag +
              '</div>' +
              '<div style="margin-top:5px;display:flex;flex-wrap:wrap;gap:4px;">' +
                subjectPills +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div class="student-right">' +
            '<div class="student-course-tag">' +
              '<span class="course-label">Strand</span>' +
              '<span class="course-name">' + strandDisplay + '</span>' +
            '</div>' +
            '<div class="student-course-tag hide-mobile">' +
              '<span class="course-label">Subject(s)</span>' +
              '<span class="course-name">' + (s.subjects || 'No subject') + '</span>' +
            '</div>' +
            '<div class="student-actions">' +
              '<button class="card-action-btn" title="Edit" onclick="openEditModal(' + s.id + ')">✏️</button>' +
              '<button class="card-action-btn" title="' + toggleTitle + '" onclick="toggleStatus(' + s.id + ',\'' + s.status + '\')">' + toggleIcon + '</button>' +
              '<button class="card-action-btn danger" title="Delete" onclick="deleteStudent(' + s.id + ',\'' + s.student_id.replace(/'/g,"\\'") + '\')">🗑</button>' +
            '</div>' +
          '</div>' +
        '</div>';
    }

    // Only group by section when sort is not overriding layout
    var groupBySection = (currentSort === 'newest' || currentSort === 'name-asc' || currentSort === 'id-asc');

    if (!groupBySection) {
        // Flat list for grade/section sorts so the sort order is clear
        list.innerHTML = filtered.map(buildCard).join('');
        return;
    }

    // Group students by section label
    var groups = {};
    var groupOrder = [];

    filtered.forEach(function(s) {
        var key = s.grade_label || 'No Section';
        if (!groups[key]) {
            groups[key] = [];
            groupOrder.push(key);
        }
        groups[key].push(s);
    });

    // Sort group headings: Grade 11 before Grade 12, then alphabetical within
    groupOrder.sort(function(a, b) {
        return a.localeCompare(b, undefined, { numeric: true });
    });

    var html = '';
    groupOrder.forEach(function(key) {
        var count = groups[key].length;
        html += '<div style="' +
            'margin-bottom:6px;margin-top:18px;' +
            'display:flex;align-items:center;gap:10px;' +
        '">' +
            '<span style="' +
                'font-size:0.78rem;font-weight:700;letter-spacing:0.06em;' +
                'text-transform:uppercase;color:var(--accent);white-space:nowrap;' +
            '">' + key + '</span>' +
            '<span style="' +
                'font-size:0.72rem;padding:1px 8px;border-radius:20px;' +
                'background:rgba(99,102,241,.13);color:var(--accent);font-weight:600;' +
            '">' + count + ' student' + (count !== 1 ? 's' : '') + '</span>' +
            '<div style="flex:1;height:1px;background:var(--border,rgba(255,255,255,.08));"></div>' +
        '</div>';
        html += groups[key].map(buildCard).join('');
    });

    list.innerHTML = html;
}

function computeAgeFromBirthdate(birthdateStr) {
    if (!birthdateStr) return '';
    var bd = new Date(birthdateStr + 'T00:00:00');
    if (isNaN(bd.getTime())) return '';
    var today = new Date();
    var age = today.getFullYear() - bd.getFullYear();
    var m = today.getMonth() - bd.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < bd.getDate())) {
        age--;
    }
    return age >= 0 ? age : '';
}

function openCreateModal() {
    document.getElementById('createModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(function(){ document.getElementById('newStudentId').focus(); }, 100);
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('createModal').addEventListener('click', function(e){
    if (e.target === this) closeCreateModal();
});

function openEditModal(id) {
    var s = students.find(function(x){ return x.id === id; });
    if (!s) { showToast('Student not found.','error'); return; }

    document.getElementById('editId').value          = s.id;
    document.getElementById('editStatus').value      = s.status;
    document.getElementById('editFirstname').value   = s.firstname || '';
    document.getElementById('editMiddlename').value  = s.middlename || '';
    document.getElementById('editLastname').value    = s.lastname || '';
    document.getElementById('editSuffix').value      = s.suffix || '';
    document.getElementById('editLrn').value         = s.lrn || '';
    document.getElementById('editSectionId').value   = s.raw_section_id || 0;
    document.getElementById('editBirthdate').value   = s.birthdate || '';
    document.getElementById('editAge').value         = s.age || '';
    document.getElementById('editGender').value      = s.gender || '';
    document.getElementById('editContact').value     = s.contact_number || '';
    document.getElementById('editGuardName').value   = s.guardian_name || '';
    document.getElementById('editGuardEmail').value  = s.guardian_email || '';
    document.getElementById('editAddress').value     = s.address || '';

    var currentSubjectIds = s.subject_ids || [];
    document.querySelectorAll('.edit-subject-cb').forEach(function(cb){
        cb.checked = currentSubjectIds.indexOf(parseInt(cb.value)) !== -1;
    });

    document.getElementById('editModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('editModal').addEventListener('click', function(e){
    if (e.target === this) closeEditModal();
});

function deleteStudent(id, studentId) {
    var label = studentId || 'this student';
    if (confirm('Are you sure you want to permanently remove ' + label +
        '? All related data (enrollments, quiz results, etc.) will also be deleted. This cannot be undone.')) {
        document.getElementById('deleteIdInput').value = id;
        document.getElementById('deleteForm').submit();
    }
}
function toggleStatus(id, currentStatus) {
    var newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    var action    = newStatus === 'inactive'   ? 'deactivate' : 'activate';
    if (confirm('Are you sure you want to ' + action + ' this student?')) {
        document.getElementById('toggleIdInput').value  = id;
        document.getElementById('toggleValInput').value = newStatus;
        document.getElementById('toggleForm').submit();
    }
}

document.querySelectorAll('.filter-tab').forEach(function(tab){
    tab.addEventListener('click', function(){
        document.querySelectorAll('.filter-tab').forEach(function(t){ t.classList.remove('active'); });
        tab.classList.add('active');
        currentFilter = tab.getAttribute('data-filter');
        renderStudents();
    });
});

var searchTimer;
document.getElementById('searchInput').addEventListener('input', function(){
    clearTimeout(searchTimer);
    var val = this.value;
    searchTimer = setTimeout(function(){ searchQuery = val; renderStudents(); }, 200);
});

document.getElementById('sortSelect').addEventListener('change', function(){
    currentSort = this.value;
    renderStudents();
});

document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') { closeCreateModal(); closeEditModal(); closeSidebar(); }
});

var sidebar = document.getElementById('sidebar');
var overlay = document.getElementById('sidebar-overlay');
function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('open');    document.body.style.overflow='hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow=''; }
document.getElementById('menuBtn').addEventListener('click', function(){
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
});

overlay.addEventListener('click', closeSidebar);
document.querySelectorAll('.nav-item').forEach(function(item){
    item.addEventListener('click', function(){ if(window.innerWidth<=768) closeSidebar(); });
});
document.getElementById('logoutModal').addEventListener('click',function(e){if(e.target===this)closeLogoutModal();});

var themeToggle = document.getElementById('themeToggle');
function applyTheme(isLight) {
    if (isLight) { document.body.classList.add('light'); }
    else          { document.body.classList.remove('light'); }
    var icon = document.getElementById('theme-icon');
    var text = document.getElementById('theme-text');
    if (icon) icon.textContent = isLight ? '☀️' : '🌙';
    if (text) text.textContent = isLight ? 'Light Mode' : 'Dark Mode';
}
(function(){ if (localStorage.getItem('excelearn-theme')==='light') applyTheme(true); })();
if (themeToggle) {
    themeToggle.addEventListener('click', function(){
        var isLight = document.body.classList.contains('light');
        applyTheme(!isLight);
        localStorage.setItem('excelearn-theme', !isLight ? 'light' : 'dark');
    });
}

function showToast(msg, type) {
    type = type || 'success';
    var icons = { success:'✅', error:'❌', info:'ℹ️' };
    var t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = '<span class="toast-icon">' + (icons[type]||'✅') + '</span><span>' + msg + '</span>';
    document.getElementById('toast-container').appendChild(t);
    setTimeout(function(){
        t.style.opacity = '0'; t.style.transform = 'translateX(60px)';
        t.style.transition = 'all .3s ease';
        setTimeout(function(){ t.remove(); }, 300);
    }, 3000);
}

var resizeTimer;
window.addEventListener('resize', function(){
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function(){ if(window.innerWidth>768) closeSidebar(); }, 150);
});

<?php if (!empty($success_msg)): ?>
showToast('<?php echo addslashes($success_msg); ?>', 'success');
<?php endif; ?>
<?php if (!empty($error_msg)): ?>
showToast('<?php echo addslashes($error_msg); ?>', 'error');
<?php endif; ?>

function openLogoutModal(){document.getElementById('logoutModal').classList.add('open');document.body.style.overflow='hidden';}
function closeLogoutModal(){document.getElementById('logoutModal').classList.remove('open');document.body.style.overflow='';}
function confirmLogout(){window.location.href='../logout.php';}

renderStudents();

// ── Notification Modal ────────────────────────────────────────────────────
function openNotifModal() {
  document.getElementById('notifModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeNotifModal() {
  document.getElementById('notifModal').classList.remove('open');
  document.body.style.overflow = '';
}
document.getElementById('notifModal').addEventListener('click', function(e) {
  if (e.target === this) closeNotifModal();
});

function markOneRead(id, el) {
  var url = el.dataset.url || '#';

  // Mark read in DB (fire and forget)
  fetch('notif_mark_read.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'notification_id=' + id
  });

  // Visual update immediately
  if (!el.dataset.read) {
    el.style.background = 'var(--bg2,rgba(255,255,255,.03))';
    el.style.border     = '1px solid var(--border,rgba(255,255,255,.07))';
    var dot   = el.querySelector('span[style*="border-radius:50%"]');
    var title = el.querySelector('div[style*="font-weight"]');
    if (dot)   dot.remove();
    if (title) title.style.fontWeight = '500';
    el.dataset.read = '1';
    updateBadgeCount(-1);
  }

  // Navigate after a brief delay so the fetch fires first
  if (url && url !== '#') {
    setTimeout(function() {
      window.location.href = url;
    }, 150);
  }
}

function markAllRead() {
  fetch('notif_mark_read.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'mark_all=1'
  });
  // Visual update — clear all unread styles
  document.querySelectorAll('.notif-item').forEach(function(el) {
    el.style.background = 'var(--bg2,rgba(255,255,255,.03))';
    el.style.border     = '1px solid var(--border,rgba(255,255,255,.07))';
    var dot   = el.querySelector('span[style*="border-radius:50%"]');
    var title = el.querySelector('div[style*="font-weight"]');
    if (dot)   dot.remove();
    if (title) title.style.fontWeight = '500';
    el.dataset.read = '1';
  });
  updateBadgeCount(0);
  document.getElementById('notif-unread-count').textContent = '';
}

function updateBadgeCount(delta) {
  var badge = document.getElementById('notif-badge');
  if (!badge) return;
  var current = parseInt(badge.textContent) || 0;
  var next = delta === 0 ? 0 : Math.max(0, current + delta);
  if (next <= 0) {
    badge.style.display = 'none';
  } else {
    badge.textContent   = next;
    badge.style.display = 'flex';
  }
}

function deleteNotif(id, btn, e) {
  e.stopPropagation(); // prevent triggering markOneRead
  var item = btn.closest('.notif-item');
  fetch('notif_delete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'notification_id=' + id
  });
  var wasUnread = !item.dataset.read && item.style.border.includes('99,102,241');
  item.style.transition = 'opacity .25s, transform .25s';
  item.style.opacity = '0';
  item.style.transform = 'translateX(30px)';
  setTimeout(function() {
    item.remove();
    // Show empty state if no items left
    var remaining = document.querySelectorAll('.notif-item');
    if (remaining.length === 0) {
      var body = document.querySelector('#notifModal .modal-body');
      body.innerHTML = '<div style="text-align:center;padding:40px 16px;color:var(--text3);">'
        + '<div style="font-size:36px;margin-bottom:8px;">🔕</div>'
        + '<div style="font-size:13px;">No notifications yet.</div>'
        + '</div>';
    }
  }, 250);
  if (wasUnread) updateBadgeCount(-1);
}
</script>
</body>
</html>