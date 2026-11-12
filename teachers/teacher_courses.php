<?php
session_start();
include '../connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// ── Edit Lesson ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_lesson'])) {
    $teacher_id     = $_SESSION['user_id'];
    $lesson_id      = intval($_POST['lesson_id'] ?? 0);
    $title          = trim($_POST['title'] ?? '');
    $status         = in_array($_POST['status'] ?? '', ['draft','published']) ? $_POST['status'] : 'draft';
    $resource_type  = $_POST['resource_type'] ?? 'none';
    $description    = trim($_POST['description'] ?? '');
    $manual_content = trim($_POST['manual_content'] ?? '');

    // ── Handle file upload (video/pdf/ppt) ──
    $hasNewFile   = false;
    $video_path     = '';
    $video_filename = '';
    $upload_error   = '';
    if (in_array($resource_type, ['video','pdf','ppt']) && isset($_FILES['resource_file']) && $_FILES['resource_file']['name'] !== '') {
        $fileErr = $_FILES['resource_file']['error'];
        if ($fileErr === UPLOAD_ERR_INI_SIZE || $fileErr === UPLOAD_ERR_FORM_SIZE) {
            $upload_error = 'File is too large. Please upload a smaller file (check server upload limit).';
        } elseif ($fileErr !== UPLOAD_ERR_OK) {
            $upload_error = 'File upload failed (error code: ' . $fileErr . '). Please try again.';
        } else {
            $folderMap = ['video' => 'video', 'pdf' => 'pdf', 'ppt' => 'ppts'];
            $extWhitelist = [
                'video' => ['mp4','webm','avi','mov','mkv'],
                'pdf'   => ['pdf'],
                'ppt'   => ['ppt','pptx'],
            ];
            $targetDir = '../uploads/lesson/' . $folderMap[$resource_type] . '/';
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                    $upload_error = 'Could not create upload folder. Check server folder permissions.';
                }
            }

            $origName = $_FILES['resource_file']['name'];
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (empty($upload_error) && !in_array($ext, $extWhitelist[$resource_type])) {
                $upload_error = 'Invalid file type for ' . strtoupper($resource_type) . ' (.' . $ext . ' uploaded). Allowed: ' . implode(', ', $extWhitelist[$resource_type]) . '.';
            }

            if (empty($upload_error)) {
                $safeName  = uniqid($resource_type . '_', true) . '.' . $ext;
                $destPath  = $targetDir . $safeName;

                if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $destPath)) {
                    $video_path     = $safeName;
                    $video_filename = $origName;
                    $hasNewFile     = true;
                } else {
                    $upload_error = 'File upload failed while saving to server. Please try again.';
                }
            }
        }
    }

    $lesson_subject_id = 0;
    $sres = $conn->prepare("SELECT teacher_subject_id FROM tbl_teacher_lessons WHERE lesson_id = ?");
    $sres->bind_param("i", $lesson_id);
    $sres->execute();
    $srow = $sres->get_result()->fetch_assoc();
    $sres->close();
    if ($srow) $lesson_subject_id = (int)$srow['teacher_subject_id'];

    if ($lesson_id > 0 && !empty($title)) {
        if ($hasNewFile) {
            $upd = $conn->prepare("
                UPDATE tbl_teacher_lessons
                SET title = ?, status = ?, resource_type = ?, description = ?, manual_content = ?, video_path = ?, video_filename = ?, updated_at = NOW()
                WHERE lesson_id = ?
                  AND teacher_subject_id IN (
                      SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?
                  )
            ");
            $upd->bind_param("sssssssii", $title, $status, $resource_type, $description, $manual_content, $video_path, $video_filename, $lesson_id, $teacher_id);
        } else {
            $upd = $conn->prepare("
                UPDATE tbl_teacher_lessons
                SET title = ?, status = ?, resource_type = ?, description = ?, manual_content = ?, updated_at = NOW()
                WHERE lesson_id = ?
                  AND teacher_subject_id IN (
                      SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?
                  )
            ");
            $upd->bind_param("sssssii", $title, $status, $resource_type, $description, $manual_content, $lesson_id, $teacher_id);
        }
        $upd->execute();
        $upd->close();
        if (!empty($upload_error)) {
            $_SESSION['course_error']  = "Lesson updated, but the file upload failed: $upload_error";
        } else {
            $_SESSION['lesson_success'] = "Lesson updated successfully.";
        }
    }
    header("Location: teacher_courses.php" . ($lesson_subject_id > 0 ? "?open_subject=" . $lesson_subject_id . "&open_lesson=" . $lesson_id : ""));
    exit();
}

// ── Delete Lesson ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_lesson'])) {
    $teacher_id = $_SESSION['user_id'];
    $lesson_id  = intval($_POST['lesson_id'] ?? 0);

    $lesson_subject_id = 0;
    $sres = $conn->prepare("SELECT teacher_subject_id FROM tbl_teacher_lessons WHERE lesson_id = ?");
    $sres->bind_param("i", $lesson_id);
    $sres->execute();
    $srow = $sres->get_result()->fetch_assoc();
    $sres->close();
    if ($srow) $lesson_subject_id = (int)$srow['teacher_subject_id'];

    if ($lesson_id > 0) {
        // Delete quiz results first, then quizzes, then lesson
        $conn->query("
            DELETE r FROM tblquiz_results r
            JOIN tblquizzes q ON q.quiz_id = r.quizzes_id
            WHERE q.lesson_id = $lesson_id
        ");
        $conn->query("DELETE FROM tblquizzes WHERE lesson_id = $lesson_id");

        $del = $conn->prepare("
            DELETE FROM tbl_teacher_lessons
            WHERE lesson_id = ?
              AND teacher_subject_id IN (
                  SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = ?
              )
        ");
        $del->bind_param("ii", $lesson_id, $teacher_id);
        $del->execute();
        $del->close();
        $_SESSION['lesson_success'] = "Lesson deleted successfully.";
    }
    header("Location: teacher_courses.php" . ($lesson_subject_id > 0 ? "?open_subject=" . $lesson_subject_id : ""));
    exit();
}

// ── Create Lesson ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_lesson'])) {
    $teacher_id  = $_SESSION['user_id'];
    $subject_id  = intval($_POST['subject_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $resource_type = $_POST['resource_type'] ?? 'none';
    $description   = trim($_POST['description'] ?? '');
    $manual_content = trim($_POST['manual_content'] ?? '');

    // ── Handle file upload (video/pdf/ppt) ──
    $video_path     = '';
    $video_filename = '';
    $upload_error   = '';
    if (in_array($resource_type, ['video','pdf','ppt']) && isset($_FILES['resource_file']) && $_FILES['resource_file']['name'] !== '') {
        $fileErr = $_FILES['resource_file']['error'];
        if ($fileErr === UPLOAD_ERR_INI_SIZE || $fileErr === UPLOAD_ERR_FORM_SIZE) {
            $upload_error = 'File is too large. Please upload a smaller file (check server upload limit).';
        } elseif ($fileErr !== UPLOAD_ERR_OK) {
            $upload_error = 'File upload failed (error code: ' . $fileErr . '). Please try again.';
        } else {
            $folderMap = ['video' => 'video', 'pdf' => 'pdf', 'ppt' => 'ppts'];
            $extWhitelist = [
                'video' => ['mp4','webm','avi','mov','mkv'],
                'pdf'   => ['pdf'],
                'ppt'   => ['ppt','pptx'],
            ];
            $targetDir = '../uploads/lesson/' . $folderMap[$resource_type] . '/';
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                    $upload_error = 'Could not create upload folder. Check server folder permissions.';
                }
            }

            $origName = $_FILES['resource_file']['name'];
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (empty($upload_error) && !in_array($ext, $extWhitelist[$resource_type])) {
                $upload_error = 'Invalid file type for ' . strtoupper($resource_type) . ' (.' . $ext . ' uploaded). Allowed: ' . implode(', ', $extWhitelist[$resource_type]) . '.';
            }

            if (empty($upload_error)) {
                $safeName  = uniqid($resource_type . '_', true) . '.' . $ext;
                $destPath  = $targetDir . $safeName;

                if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $destPath)) {
                    $video_path     = $safeName;
                    $video_filename = $origName;
                } else {
                    $upload_error = 'File upload failed while saving to server. Please try again.';
                }
            }
        }
    }

    if ($subject_id > 0 && !empty($title)) {
        // Verify this subject belongs to the teacher
        $check = $conn->prepare("SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_subject_id = ? AND teacher_id = ?");
        $check->bind_param("ii", $subject_id, $teacher_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Kunin ang susunod na order_index para sa subject na ito
            $ord = $conn->prepare("SELECT COALESCE(MAX(order_index),0) + 1 AS next_order FROM tbl_teacher_lessons WHERE teacher_subject_id = ?");
            $ord->bind_param("i", $subject_id);
            $ord->execute();
            $next_order = (int)($ord->get_result()->fetch_assoc()['next_order'] ?? 1);
            $ord->close();

            $ins = $conn->prepare("
                INSERT INTO tbl_teacher_lessons 
                    (teacher_subject_id, title, description, resource_type, video_path, video_filename, manual_content, status, order_index, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $lesson_status = $_POST['lesson_status'] ?? 'published';
            $ins->bind_param("isssssssi", $subject_id, $title, $description, $resource_type, $video_path, $video_filename, $manual_content, $lesson_status, $next_order);
            if (!$ins->execute()) {
                $upload_error = ($upload_error ? $upload_error . ' | ' : '') . 'DB error: ' . $ins->error;
            }
            $ins->close();
            if (!empty($upload_error)) {
                $_SESSION['course_error']   = "Lesson \"$title\" was created, but the file upload failed: $upload_error";
            } else {
                $_SESSION['course_success'] = "Lesson \"$title\" created successfully.";
            }
        }
        $check->close();
    }
    header("Location: teacher_courses.php");
    exit();
}

// ── Create Quiz ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quiz'])) {
    $teacher_id    = $_SESSION['user_id'];
    $lesson_id     = intval($_POST['lesson_id'] ?? 0);
    $description   = trim($_POST['description'] ?? '');
    $duration      = intval($_POST['duration'] ?? 30);
    $passing_score = intval($_POST['passing_score'] ?? 75);
    $due_date      = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $questions_raw = $_POST['questions_json'] ?? '[]';
    $questions_arr = json_decode($questions_raw, true);
    if (!is_array($questions_arr)) $questions_arr = [];
    $questions_json_clean = json_encode($questions_arr);
    $question_count = count($questions_arr);

    if ($lesson_id > 0 && !empty($description)) {
        $check = $conn->prepare("
            SELECT l.lesson_id FROM tbl_teacher_lessons l
            INNER JOIN tbl_teacher_subjects s ON s.teacher_subject_id = l.teacher_subject_id
            WHERE l.lesson_id = ? AND s.teacher_id = ?
        ");
        $check->bind_param("ii", $lesson_id, $teacher_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $ins = $conn->prepare("
                INSERT INTO tblquizzes 
                    (lesson_id, description, duration, passing_score, due_date, questions, question_count, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'published', NOW(), NOW())
            ");
            $ins->bind_param("isiissi", $lesson_id, $description, $duration, $passing_score, $due_date, $questions_json_clean, $question_count);
            $ins->execute();
            $ins->close();
            $_SESSION['course_success'] = "Quiz created successfully.";
        }
        $check->close();
    }
    header("Location: teacher_courses.php");
    exit();
}

// ── Edit Quiz ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_quiz'])) {
    $teacher_id    = $_SESSION['user_id'];
    $quiz_id       = intval($_POST['quiz_id'] ?? 0);
    $description   = trim($_POST['description'] ?? '');
    $duration      = intval($_POST['duration'] ?? 30);
    $passing_score = intval($_POST['passing_score'] ?? 75);
    $status        = in_array($_POST['status'] ?? '', ['draft','published']) ? $_POST['status'] : 'draft';
    $due_date      = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $questions_raw = $_POST['questions_json'] ?? '[]';
    $questions_arr = json_decode($questions_raw, true);
    if (!is_array($questions_arr)) $questions_arr = [];
    $questions_json_clean = json_encode($questions_arr);
    $question_count = count($questions_arr);

    $quiz_lesson_id  = 0;
    $quiz_subject_id = 0;
    $qsres = $conn->prepare("
        SELECT l.lesson_id, l.teacher_subject_id
        FROM tblquizzes q
        INNER JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
        WHERE q.quiz_id = ?
    ");
    $qsres->bind_param("i", $quiz_id);
    $qsres->execute();
    $qsrow = $qsres->get_result()->fetch_assoc();
    $qsres->close();
    if ($qsrow) {
        $quiz_lesson_id  = (int)$qsrow['lesson_id'];
        $quiz_subject_id = (int)$qsrow['teacher_subject_id'];
    }

    if ($quiz_id > 0 && !empty($description)) {
        $upd = $conn->prepare("
            UPDATE tblquizzes q
            INNER JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
            INNER JOIN tbl_teacher_subjects s ON s.teacher_subject_id = l.teacher_subject_id
            SET q.description = ?, q.duration = ?, q.passing_score = ?, q.status = ?,
                q.due_date = ?, q.questions = ?, q.question_count = ?, q.updated_at = NOW()
            WHERE q.quiz_id = ? AND s.teacher_id = ?
        ");
        $upd->bind_param("siissssii", $description, $duration, $passing_score, $status, $due_date, $questions_json_clean, $question_count, $quiz_id, $teacher_id);
        $upd->execute();
        $upd->close();
        $_SESSION['course_success'] = "Quiz updated successfully.";
    }
    $redirect_qs = ($quiz_subject_id > 0) ? "?open_subject=" . $quiz_subject_id . "&open_lesson=" . $quiz_lesson_id . "&open_quiz=" . $quiz_id : "";
    header("Location: teacher_courses.php" . $redirect_qs);
    exit();
}

// ── Delete Quiz ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_quiz'])) {
    $teacher_id = $_SESSION['user_id'];
    $quiz_id    = intval($_POST['quiz_id'] ?? 0);

    $quiz_lesson_id  = 0;
    $quiz_subject_id = 0;
    $qsres = $conn->prepare("
        SELECT l.lesson_id, l.teacher_subject_id
        FROM tblquizzes q
        INNER JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
        WHERE q.quiz_id = ?
    ");
    $qsres->bind_param("i", $quiz_id);
    $qsres->execute();
    $qsrow = $qsres->get_result()->fetch_assoc();
    $qsres->close();
    if ($qsrow) {
        $quiz_lesson_id  = (int)$qsrow['lesson_id'];
        $quiz_subject_id = (int)$qsrow['teacher_subject_id'];
    }

    if ($quiz_id > 0) {
        // Delete results first
        $conn->query("DELETE FROM tblquiz_results WHERE quizzes_id = $quiz_id");

        $del = $conn->prepare("
            DELETE q FROM tblquizzes q
            INNER JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
            INNER JOIN tbl_teacher_subjects s ON s.teacher_subject_id = l.teacher_subject_id
            WHERE q.quiz_id = ? AND s.teacher_id = ?
        ");
        $del->bind_param("ii", $quiz_id, $teacher_id);
        $del->execute();
        $del->close();
        $_SESSION['course_success'] = "Quiz deleted successfully.";
    }
    $redirect_qs = ($quiz_subject_id > 0) ? "?open_subject=" . $quiz_subject_id . "&open_lesson=" . $quiz_lesson_id : "";
    header("Location: teacher_courses.php" . $redirect_qs);
    exit();
}

// ── Save Manual Score ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_manual_score'])) {
    header('Content-Type: application/json');
    $result_id    = intval($_POST['result_id'] ?? 0);
    $manual_score = intval($_POST['manual_score'] ?? 0);
    $quiz_id      = intval($_POST['quiz_id'] ?? 0);

    // Get total questions to validate (sanity cap na lang, hindi 1-per-item)
    $qrow = $conn->query("SELECT questions FROM tblquizzes WHERE quiz_id = $quiz_id LIMIT 1")->fetch_assoc();
    $questions = json_decode($qrow['questions'] ?? '[]', true);
    $max_manual = is_array($questions) ? max(count($questions), 100) : 100;

    if ($result_id > 0 && $manual_score >= 0 && $manual_score <= $max_manual) {
        // Update manual_score and recalculate total score
        $conn->query("UPDATE tblquiz_results SET manual_score = $manual_score, score = (score + $manual_score) WHERE id = $result_id AND manual_score IS NULL");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid score.']);
    }
    exit();
}

$teacher_id = $_SESSION['user_id'];

function formatTimeAgo($datetime_str) {
    if (empty($datetime_str)) return 'unknown';
    $now  = new DateTime();
    $then = new DateTime($datetime_str);
    if ($then > $now) $then = $now;
    $diff = $now->diff($then);
    if ($diff->y > 0) return $diff->y . ' year'  . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day'   . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour'  . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute'. ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

function buildUrl($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    $base = basename(__FILE__);
    return $base . (!empty($params) ? '?' . http_build_query($params) : '');
}

function getStudentAvatarHTML($student) {
    $pic = $student['profile_pic'] ?? '';
    $fn  = $student['firstname'] ?? '';
    $ln  = $student['lastname']  ?? '';
    $initials = strtoupper(substr($fn, 0, 1) . substr($ln, 0, 1));
    if (strlen(trim($initials)) < 2) $initials = '?';

    if (!empty($pic)) {
        $paths = [
            '../uploads/profile_pic/students/' . $pic,
            '../uploads/profile_pic/' . $pic,
            '../' . $pic
        ];
        foreach ($paths as $p) {
            if (file_exists($p)) {
                return '<div class="sa-avatar" style="padding:0;overflow:hidden"><img src="' . htmlspecialchars($p) . '" style="width:100%;height:100%;object-fit:cover;border-radius:inherit" alt="' . htmlspecialchars($initials) . '"></div>';
            }
        }
    }

    $gradients = [
        'linear-gradient(135deg,var(--cyan),var(--accent))',
        'linear-gradient(135deg,var(--purple),#8b3fbf)',
        'linear-gradient(135deg,var(--green),#1aaa6e)',
        'linear-gradient(135deg,var(--red),#e04040)',
        'linear-gradient(135deg,#f5a623,#e08c0c)',
    ];
    $idx = abs(crc32($fn . $ln)) % count($gradients);
    return '<div class="sa-avatar" style="background:' . $gradients[$idx] . '">' . htmlspecialchars($initials) . '</div>';
}

function getBannerClass($name, $status) {
    $active = ['banner-bio', 'banner-chem', 'banner-earth', 'banner-phys', 'banner-research'];
    $draft  = ['banner-draft-bio', 'banner-draft-chem'];
    $pool   = ($status === 'draft') ? $draft : $active;
    $idx    = abs(crc32($name)) % count($pool);
    return $pool[$idx];
}

// Resolves the actual uploaded file (video/pdf/ppt) path para sa lesson resource.
function getLessonResourceUrl($path, $resource_type = '') {
    if (empty($path)) return '';

    $typeFolders = [
        'pdf'   => 'pdf',
        'ppt'   => 'ppts',
        'video' => 'video',
    ];

    // Relative (para sa <src>/<href> sa browser) at absolute (para sa file_exists check) na magkapares
    $candidates = [];

    if (isset($typeFolders[$resource_type])) {
        $candidates[] = '../uploads/lesson/' . $typeFolders[$resource_type] . '/' . basename($path);
    }

    foreach ($typeFolders as $folder) {
        $candidates[] = '../uploads/lesson/' . $folder . '/' . basename($path);
    }

    $candidates[] = '../' . ltrim($path, '/');
    $candidates[] = $path;

    // I-dedupe
    $candidates = array_values(array_unique($candidates));

    foreach ($candidates as $relUrl) {
        // Gawing absolute ang path base sa actual folder ng script (__DIR__) para hindi
        // maapektuhan ng current-working-directory issues ang file_exists() check.
        $absPath = __DIR__ . '/' . $relUrl;
        if (file_exists($absPath)) {
            return $relUrl; // ibalik yung relative path — ito yung gagamitin bilang browser URL
        }
    }
    return '';
}

 $stmt = $conn->prepare("SELECT id, profile_pic, firstname, middlename, lastname, role FROM tblusers WHERE id = ?");
 $stmt->bind_param("i", $teacher_id);
 $stmt->execute();
 $user = $stmt->get_result()->fetch_assoc();
 $stmt->close();

if (!$user) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

 $firstname    = htmlspecialchars($user['firstname'] ?? '');
 $middlename   = htmlspecialchars($user['middlename'] ?? '');
 $lastname     = htmlspecialchars($user['lastname'] ?? '');
 $display_name = trim("$firstname " . ($middlename ? "$middlename " : '') . "$lastname");
 $initials     = strtoupper(substr($user['firstname'] ?? '', 0, 1) . substr($user['lastname'] ?? '', 0, 1));
if (strlen(trim($initials)) < 2) $initials = 'U';
 $role_display = htmlspecialchars(ucfirst($user['role'] ?? 'Teacher'));

 $profile_pic_path = '';
 $profile_pic_db   = $user['profile_pic'] ?? '';
if (!empty($profile_pic_db)) {
    foreach (['../uploads/profile_pic/teachers/', '../uploads/profile_pic/', '../'] as $dir) {
        $p = $dir . $profile_pic_db;
        if (file_exists($p)) { $profile_pic_path = $p; break; }
    }
}
 $sidebar_avatar_html = '';
if (!empty($profile_pic_path)) {
    $sidebar_avatar_html = '<img src="' . htmlspecialchars($profile_pic_path) . '" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">';
} else {
    $sidebar_avatar_html = htmlspecialchars($initials);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $course_id  = intval($_POST['course_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';

    if ($course_id > 0 && in_array($new_status, ['active', 'draft', 'archived'])) {

        $upd = $conn->prepare("
            UPDATE tblteachers_courses 
            SET status = ?, updated_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $upd->bind_param("sii", $new_status, $course_id, $teacher_id);
        $upd->execute();
        $upd->close();
        $lesson_status = 'draft';

        if ($new_status === 'active') {
            $lesson_status = 'published';
        } else {
            $lesson_status = 'draft';
        }

        $stmt = $conn->prepare("
            UPDATE tbllessons 
            SET status = ?, updated_at = NOW()
            WHERE teacher_course_id = ?
        ");
        $stmt->bind_param("si", $lesson_status, $course_id);
        $stmt->execute();
        $stmt->close();

        $labels = [
            'active' => 'Active',
            'draft' => 'Draft',
            'archived' => 'Archived'
        ];

        $_SESSION['course_success'] = "Course status changed to " . ($labels[$new_status] ?? $new_status) . ".";
    }

    header("Location: " . buildUrl());
    exit();
}

 $success_msg = $_SESSION['course_success'] ?? '';
 $error_msg   = $_SESSION['course_error'] ?? '';
unset($_SESSION['course_success'], $_SESSION['course_error'], $_SESSION['course_switch_filter']);

 $current_sort = $_GET['sort'] ?? 'newest';
if (!in_array($current_sort, ['newest', 'oldest', 'name-az', 'name-za', 'students-high', 'students-low'])) $current_sort = 'newest';

 $current_view = $_GET['view'] ?? 'grid';
if (!in_array($current_view, ['grid', 'list'])) $current_view = 'grid';

 $search_query = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        tts.teacher_subject_id AS id,
        tts.teacher_id,
        tts.subject_id,
        tts.updated_at,
        tts.created_at,

        sub.subject,
        sub.description AS subject_description,
        s.strand,
        s.track,
        s.strand_id,

        COALESCE(sc.student_count, 0) AS student_count,
        COALESCE(lc.lesson_count, 0) AS lesson_count,
        COALESCE(lc.published_count, 0) AS published_count

    FROM tbl_teacher_subjects tts

    LEFT JOIN tblsubjects sub 
        ON tts.subject_id = sub.subject_id

    LEFT JOIN tblstrands s 
        ON sub.strand_id = s.strand_id

    LEFT JOIN (
        SELECT teacher_subject_id, COUNT(DISTINCT student_id) AS student_count
        FROM tbl_teachers_students
        GROUP BY teacher_subject_id
    ) sc 
        ON tts.teacher_subject_id = sc.teacher_subject_id

    LEFT JOIN (
        SELECT 
            teacher_subject_id,
            COUNT(*) AS lesson_count,
            SUM(
                CASE 
                    WHEN status = 'published' THEN 1 
                    ELSE 0 
                END
            ) AS published_count
        FROM tbl_teacher_lessons
        GROUP BY teacher_subject_id
    ) lc 
        ON tts.teacher_subject_id = lc.teacher_subject_id

    WHERE tts.teacher_id = ?
";

 $params = [$teacher_id];
 $types  = "i";

$current_filter = 'all';

if (!empty($search_query)) {
    $like = "%" . $search_query . "%";
    $sql .= " AND (
        sub.subject LIKE ?
        OR s.strand LIKE ?
        OR s.track LIKE ?
    )";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}

switch ($current_sort) {
    case 'newest':
        $sql .= " ORDER BY tts.created_at DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY tts.created_at ASC";
        break;
    case 'name-az':
        $sql .= " ORDER BY sub.subject ASC";
        break;
    case 'name-za':
        $sql .= " ORDER BY sub.subject DESC";
        break;
    case 'students-high':
        $sql .= " ORDER BY COALESCE(sc.student_count, 0) DESC";
        break;
    case 'students-low':
        $sql .= " ORDER BY COALESCE(sc.student_count, 0) ASC";
        break;
}

 $stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
 $stmt->execute();

 $result = $stmt->get_result();

 $courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

 $result->free();
 $stmt->close();

// ── Fetch lessons grouped by teacher_subject_id ──
$lessons_by_subject = [];
$lstmt = $conn->prepare("
    SELECT l.lesson_id, l.teacher_subject_id, l.title, l.description, l.duration, l.resource_type,
           l.video_path, l.video_filename, l.manual_content, l.status, l.order_index, l.created_at,
           COALESCE(qc.quiz_count,0) AS quiz_count
    FROM tbl_teacher_lessons l
    INNER JOIN tbl_teacher_subjects s ON s.teacher_subject_id = l.teacher_subject_id
    LEFT JOIN (
        SELECT lesson_id, COUNT(*) AS quiz_count FROM tblquizzes GROUP BY lesson_id
    ) qc ON qc.lesson_id = l.lesson_id
    WHERE s.teacher_id = ?
    ORDER BY l.order_index ASC, l.lesson_id ASC
");
$lstmt->bind_param("i", $teacher_id);
$lstmt->execute();
$lres = $lstmt->get_result();
while ($row = $lres->fetch_assoc()) {
    $tsid = (int)$row['teacher_subject_id'];
    if (!isset($lessons_by_subject[$tsid])) $lessons_by_subject[$tsid] = [];
    $lessons_by_subject[$tsid][] = [
        'id'              => (int)$row['lesson_id'],
        'title'           => $row['title'],
        'description'     => $row['description'] ?? '',
        'duration'        => $row['duration'] !== null ? (int)$row['duration'] : null,
        'resource_type'   => $row['resource_type'] ?? 'none',
        'video_path'      => $row['video_path'] ?? '',
        'video_filename'  => $row['video_filename'] ?? '',
        'resource_url'    => getLessonResourceUrl($row['video_path'] ?? '', $row['resource_type'] ?? ''),
        'manual_content'  => $row['manual_content'] ?? '',
        'status'          => $row['status'],
        'created_at'      => $row['created_at'] ? date('M d, Y', strtotime($row['created_at'])) : '—',
        'quiz_count'      => (int)$row['quiz_count']
    ];
}
$lstmt->close();

// ── Fetch quizzes grouped by lesson_id ──
$quizzes_by_lesson = [];
$qstmt = $conn->prepare("
    SELECT q.quiz_id, q.lesson_id, q.description AS title, q.due_date, q.status, q.passing_score, q.created_at,
           q.questions,
           COALESCE(rc.result_count,0) AS result_count
    FROM tblquizzes q
    INNER JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    INNER JOIN tbl_teacher_subjects s ON s.teacher_subject_id = l.teacher_subject_id
    LEFT JOIN (
        SELECT quizzes_id, COUNT(DISTINCT student_id) AS result_count FROM tblquiz_results GROUP BY quizzes_id
    ) rc ON rc.quizzes_id = q.quiz_id
    WHERE s.teacher_id = ?
    ORDER BY q.created_at ASC, q.quiz_id ASC
");
$qstmt->bind_param("i", $teacher_id);
$qstmt->execute();
$qres = $qstmt->get_result();
while ($row = $qres->fetch_assoc()) {
    $lid = (int)$row['lesson_id'];
    if (!isset($quizzes_by_lesson[$lid])) $quizzes_by_lesson[$lid] = [];
    $q_questions = [];
    if (!empty($row['questions'])) {
        $decoded = json_decode($row['questions'], true);
        if (is_array($decoded)) $q_questions = $decoded;
    }
    $q_is_overdue = false;
    if (!empty($row['due_date']) && $row['status'] === 'published') {
        $q_is_overdue = strtotime($row['due_date']) < time();
    }
    $quizzes_by_lesson[$lid][] = [
        'id'            => (int)$row['quiz_id'],
        'title'         => $row['title'],
        'status'        => $row['status'],
        'due_date'      => !empty($row['due_date']) ? date('M d, Y', strtotime($row['due_date'])) : '—',
        'due_date_raw'  => $row['due_date'] ?? '',
        'passing_score' => (int)$row['passing_score'],
        'created_at'    => $row['created_at'] ? date('M d, Y', strtotime($row['created_at'])) : '—',
        'result_count'  => (int)$row['result_count'],
        'questions'     => $q_questions,
        'is_overdue'    => $q_is_overdue
    ];
}
$qstmt->close();

// ── Build subjects info map (for modal header) ──
$subjects_map = [];
foreach ($courses as $c) {
    $subjects_map[(int)$c['id']] = [
        'id'                  => (int)$c['id'],
        'subject'             => $c['subject'] ?? 'Untitled Course',
        'subject_description' => $c['subject_description'] ?? '',
        'strand'              => $c['strand'] ?? '',
        'track'               => $c['track'] ?? '',
        'student_count'       => (int)$c['student_count'],
        'lesson_count'        => (int)$c['lesson_count'],
        'updated_at'          => !empty($c['updated_at']) ? date('M d, Y', strtotime($c['updated_at'])) : '—'
    ];
}

// ── Fetch quiz results with student details ──
$quiz_results_map = [];
$rstmt = $conn->prepare("
    SELECT 
        r.id          AS result_id,
        r.quizzes_id  AS quizzes_id,
        r.student_id  AS student_id,
        r.score       AS score,
        r.manual_score AS manual_score,
        r.answers     AS answers,
        r.time_used   AS time_used,
        r.created_at  AS submitted_at,
        st.firstname,
        st.lastname,
        st.profile_pic
    FROM tblquiz_results r
    INNER JOIN tblstudents st ON st.id = r.student_id
    INNER JOIN tblquizzes q ON q.quiz_id = r.quizzes_id
    INNER JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
    INNER JOIN tbl_teacher_subjects s ON s.teacher_subject_id = l.teacher_subject_id
    WHERE s.teacher_id = ?
    ORDER BY r.created_at DESC
");
$rstmt->bind_param("i", $teacher_id);
$rstmt->execute();
$rres = $rstmt->get_result();
while ($row = $rres->fetch_assoc()) {
    $qid = (int)$row['quizzes_id'];
    if (!isset($quiz_results_map[$qid])) $quiz_results_map[$qid] = [];
    // score is varchar — extract numeric value only
    $raw_score = $row['score'] ?? '0';
    $numeric_score = (int) preg_replace('/[^0-9]/', '', $raw_score);

    $quiz_results_map[$qid][] = [
        'result_id'    => (int)$row['result_id'],
        'student_id'   => (int)$row['student_id'],
        'score'        => $numeric_score,
        'score_raw'    => $raw_score,
        'manual_score' => $row['manual_score'] ?? null,
        'answers'      => $row['answers'] ?? null,
        'time_used'    => $row['time_used'] ?? null,
        'submitted_at' => !empty($row['submitted_at']) ? date('M d, Y g:i A', strtotime($row['submitted_at'])) : '—',
        'firstname'    => $row['firstname'] ?? '',
        'lastname'     => $row['lastname'] ?? '',
        'profile_pic'  => $row['profile_pic'] ?? '',
    ];
}
$rstmt->close();

$lessons_by_subject_json = json_encode($lessons_by_subject);
$quizzes_by_lesson_json  = json_encode($quizzes_by_lesson);
$subjects_map_json       = json_encode($subjects_map);
$quiz_results_map_json = json_encode($quiz_results_map);

$per_page    = 10;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$total_items  = count($courses);
$total_pages  = (int)ceil($total_items / $per_page);
$current_page = min($current_page, max(1, $total_pages));
$offset       = ($current_page - 1) * $per_page;
$courses_page = array_slice($courses, $offset, $per_page);

  $total_courses = 0;
  $active_count  = $total_courses;
  $draft_count   = 0;
  $archived_count = 0;
try {
    $cr = $conn->query("
        SELECT COUNT(*) AS cnt 
        FROM tbl_teacher_subjects 
        WHERE teacher_id = $teacher_id
    ");
    if ($cr) {
      if ($crow = $cr->fetch_assoc()) {
          $total_courses = (int)$crow['cnt'];
      }
    }
} catch (Exception $e) {}

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

$unique_students = (int)$conn->query("
    SELECT COUNT(DISTINCT student_id) FROM tbl_teachers_students
    WHERE teacher_subject_id IN (SELECT teacher_subject_id FROM tbl_teacher_subjects WHERE teacher_id = $teacher_id)
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
<link rel="stylesheet" href="../assets/css/teacher/teacher_courses.css">
<style>
    .btn-primary { background:var(--accent,#6366f1);color:#fff;border:none;padding:8px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer; }
    .btn-primary:hover { opacity:0.9; }
    .btn-sm { padding:5px 10px;font-size:11px; }
    .form-group { margin-bottom:12px; }
    .form-group label { display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:4px; }
    .form-group input, .form-group select, .form-group textarea { width:100%;padding:8px 10px;font-size:13px;border:1px solid var(--border,rgba(255,255,255,.1));border-radius:8px;background:var(--bg2,rgba(255,255,255,.03));color:var(--text);box-sizing:border-box; }
    .form-group textarea { min-height:80px;resize:vertical; }
    .course-card { overflow:hidden; word-break:break-word; }
    .banner-title { font-size:14px; line-height:1.3; overflow-wrap:break-word; }
    .banner-cat { font-size:11px; overflow-wrap:break-word; }
    .course-card-desc { font-size:12px; line-height:1.4; }
    .footer-date { font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    @media (min-width: 769px) {
      .modal-large { max-width: 1100px !important; width: 85vw !important; max-height: 90vh !important; }
      .modal-large .modal-body { padding: 24px 28px !important; }
      .modal-large #subj-modal-lessons,
      .modal-large #lesson-modal-quizzes { gap: 12px !important; }
      .modal-large .notif-item { padding: 14px 16px !important; }
    }
    @media (max-width: 768px) {
      .course-card-banner { align-items:flex-start !important; }
      .banner-content { text-align:left !important; align-self:flex-start !important; width:100%; }
      .banner-cat { text-align:left !important; }
      .banner-title { text-align:left !important; }
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
  <div class="sidebar-logo" style="padding:20px 16px 16px;display:flex;align-items:center;gap:10px;flex-wrap:nowrap;overflow:hidden;">
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
    <a class="nav-item active" href="teacher_courses.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Subjects
      <span class="nav-badge" id="course-count-badge"><?php echo $total_courses; ?></span>
    </a>
    <a class="nav-item" href="teacher_performance_tasks.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>
      Performance Tasks <span class="nav-badge"><?php echo $total_tasks_badge; ?></span>
      <?php if ($pending_review_badge > 0): ?><span class="nav-badge" style="background:var(--red,#ef4444);color:#fff;margin-left:4px;"><?= $pending_review_badge ?></span><?php endif; ?>
    </a>
    <?php
    $badge_virtualclass = (int)$conn->query("SELECT COUNT(*) FROM tblvirtualclasses WHERE teacher_id = $teacher_id AND status IN ('upcoming','live')")->fetch_row()[0];
    ?>
    <a class="nav-item" href="teacher_virtual_class.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
      Virtual Class
      <?php if ($badge_virtualclass > 0): ?><span class="nav-badge" style="background:var(--cyan,#06b6d4);color:#000"><?= $badge_virtualclass ?></span><?php endif; ?>
    </a>
    <div class="nav-section-label" style="margin-top:14px">Students & Grades</div>
    <a class="nav-item" href="teacher_students.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Students
      <?php if ($unique_students > 0): ?><span class="nav-badge" style="background:var(--green);color:#fff"><?= $unique_students ?></span><?php endif; ?>
    </a>
    <?php
    $badge_gradebook = (int)$conn->query("
        SELECT COUNT(DISTINCT r.student_id)
        FROM tblquiz_results r
        JOIN tblquizzes q ON q.quiz_id = r.quizzes_id
        JOIN tbl_teacher_lessons l ON l.lesson_id = q.lesson_id
        JOIN tbl_teacher_subjects s ON s.teacher_subject_id = l.teacher_subject_id
        WHERE s.teacher_id = $teacher_id
    ")->fetch_row()[0];
    ?>
    <a class="nav-item" href="teacher_grades.php">
      <svg class="nav-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Gradebook
      <?php if ($badge_gradebook > 0): ?><span class="nav-badge" style="background:var(--purple);color:#fff"><?= $badge_gradebook ?></span><?php endif; ?>
    </a>
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
      <div class="user-avatar" style="background:linear-gradient(135deg,var(--accent),var(--purple))"><?php echo $sidebar_avatar_html; ?></div>
      <div class="user-info">
        <p><?php echo htmlspecialchars($display_name); ?></p>
        <span>Faculty -<?php echo $role_display; ?></span>
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
    <!-- FLASH MESSAGES -->
    <?php if (!empty($success_msg)): ?>
    <div class="flash-msg flash-success" id="flashMsg">
      <span>✅ <?php echo htmlspecialchars($success_msg); ?></span>
      <button onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;margin-left:12px;">✕</button>
    </div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
    <div class="flash-msg flash-error" id="flashMsg">
      <span>❌ <?php echo htmlspecialchars($error_msg); ?></span>
      <button onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;margin-left:12px;">✕</button>
    </div>
    <?php endif; ?>

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div>
        <div class="page-title">Subjects</div>
        <div class="page-sub" id="course-count-text">Showing <strong><?php echo count($courses); ?></strong> of <?php echo $total_courses; ?> courses &middot;</div>
      </div>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:0;">
      <form method="GET" action="<?php echo basename(__FILE__); ?>" class="search-wrap" style="display:flex;align-items:center;position:relative;flex:1;min-width:140px;">
        <?php if ($current_filter !== 'all'): ?><input type="hidden" name="filter" value="<?php echo htmlspecialchars($current_filter); ?>"><?php endif; ?>
        <?php if ($current_sort !== 'newest'): ?><input type="hidden" name="sort" value="<?php echo htmlspecialchars($current_sort); ?>"><?php endif; ?>
        <?php if ($current_view !== 'grid'): ?><input type="hidden" name="view" value="<?php echo htmlspecialchars($current_view); ?>"><?php endif; ?>
        <span class="search-icon" style="position:absolute;left:10px;color:var(--text3);">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search..."
          style="width:100%;padding:7px 10px 7px 32px;font-size:12px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);color:var(--text);">
      </form>
      <form method="GET" action="<?php echo basename(__FILE__); ?>" style="display:flex;">
        <?php if ($current_filter !== 'all'): ?><input type="hidden" name="filter" value="<?php echo htmlspecialchars($current_filter); ?>"><?php endif; ?>
        <?php if (!empty($search_query)): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>"><?php endif; ?>
        <?php if ($current_view !== 'grid'): ?><input type="hidden" name="view" value="<?php echo htmlspecialchars($current_view); ?>"><?php endif; ?>
        <select class="sort-select" name="sort" onchange="this.form.submit()"
          style="padding:7px 10px;font-size:12px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);color:var(--text);cursor:pointer;">
          <option value="newest" <?php echo $current_sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
          <option value="oldest" <?php echo $current_sort === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
          <option value="name-az" <?php echo $current_sort === 'name-az' ? 'selected' : ''; ?>>A–Z</option>
          <option value="name-za" <?php echo $current_sort === 'name-za' ? 'selected' : ''; ?>>Z–A</option>
          <option value="students-high" <?php echo $current_sort === 'students-high' ? 'selected' : ''; ?>>Most Students</option>
          <option value="students-low" <?php echo $current_sort === 'students-low' ? 'selected' : ''; ?>>Fewest Students</option>
        </select>
      </form>
    </div>

    <!-- COURSE GRID -->
    <div class="course-grid" id="courseGrid" style="margin-top:8px;">
      <?php if (empty($courses)): ?>
      <div class="empty-state" style="grid-column:1/-1">
        <div class="empty-icon">📭</div>
        <p>No courses found</p>
        <span>Try adjusting your search or filter criteria.</span>
      </div>
      <?php else: ?>
        <?php foreach ($courses_page as $c):
          $subject   = htmlspecialchars($c['subject'] ?? 'Untitled Course');
          $strand_display = htmlspecialchars($c['strand'] ?? '');
          $track_display  = htmlspecialchars($c['track'] ?? '');
          $section       = '—';
          $grade_level   = 'Senior High';
          $icon          = '📚'; 
          $status        = 'active';
          $student_count = (int)$c['student_count'];
          $max_students  = 40;
          $lesson_count  = (int)$c['lesson_count'];
          $published_count = (int)$c['published_count'];
          $last_updated  = !empty($c['updated_at']) ? date('M d, Y', strtotime($c['updated_at'])) : '—';

          $quiz_count_for_card = 0;
          $subject_lessons = $lessons_by_subject[(int)$c['id']] ?? [];
          foreach ($subject_lessons as $sl) {
              $quiz_count_for_card += count($quizzes_by_lesson[$sl['id']] ?? []);
          }

          $progress = $lesson_count > 0 ? round(($published_count / $lesson_count) * 100) : 0;
          $fillClass = $progress >= 90 ? 'green-fill' : ($progress >= 60 ? 'blue-fill' : ($progress >= 40 ? 'amber-fill' : 'red-fill'));
          $pctColor  = $progress >= 90 ? 'var(--green)' : ($progress >= 60 ? 'var(--accent)' : ($progress >= 40 ? 'var(--amber)' : 'var(--red)'));

          $statusBadge = 'badge-active';
          $statusLabel = 'Active';
          $bannerClass = getBannerClass($c['subject'] ?? '', $status);
          
          $banner_text = '';

          if(!empty($track_display)) {
              $banner_text .= $track_display;
          }

          if(!empty($strand_display)) {
              $banner_text .= ' · ' . $strand_display;
          }

        ?>
        <div class="course-card" onclick="openSubjectModal(<?php echo (int)$c['id']; ?>)" style="cursor:pointer">
          <div class="course-card-banner <?php echo $bannerClass; ?>" style="position:relative;display:flex;flex-direction:column;justify-content:flex-end;min-height:90px;padding:10px 12px 10px;">
            <div class="course-status-badge" style="position:absolute;top:8px;left:8px;z-index:2;">
              <span class="badge badge-status <?php echo $statusBadge; ?>"><?php echo $statusLabel; ?></span>
            </div>
            <span class="banner-icon" style="position:absolute;bottom:10px;right:12px;font-size:20px;z-index:1;opacity:0.3;"><?php echo $icon; ?></span>
            <div class="banner-content" style="margin-top:8px;width:100%;text-align:left;">
              <div class="banner-cat" style="font-size:10px;opacity:0.85;overflow-wrap:break-word;padding-right:10px;text-align:left;"><?php echo $banner_text; ?></div>
              <div class="banner-title" style="font-size:13px;font-weight:700;line-height:1.3;overflow-wrap:break-word;text-align:left;"><?php echo $subject; ?></div>
            </div>
          </div>
          <div class="course-card-body">
            <div class="course-meta-row" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">
              <div class="course-meta-item" style="font-size:12px;white-space:nowrap;"><span class="meta-icon">👥</span> <?php echo $student_count; ?> students</div>
              <div class="course-meta-item" style="font-size:12px;white-space:nowrap;"><span class="meta-icon">📖</span> <?php echo $lesson_count; ?> lessons</div>
              <div class="course-meta-item" style="font-size:12px;white-space:nowrap;"><span class="meta-icon">📝</span> <?php echo $quiz_count_for_card; ?> quizzes</div>
            </div>
            <?php if (!empty($avatarsHTML)) echo $avatarsHTML; ?>
          </div>
          <div class="course-card-footer">
            <div class="footer-left">
              <div class="footer-date">Updated <?php echo $last_updated; ?></div>
            </div>

          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php if ($total_pages > 1): ?>
    <div style="display:flex;justify-content:center;align-items:center;gap:6px;margin-top:20px;flex-wrap:wrap;">
    <?php if ($current_page > 1): ?>
        <a href="<?= buildUrl(['page' => $current_page - 1]) ?>" style="padding:6px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);color:var(--text);font-size:12px;text-decoration:none;">← Prev</a>
    <?php endif; ?>

    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
        <a href="<?= buildUrl(['page' => $p]) ?>"
        style="padding:6px 12px;border-radius:8px;border:1px solid var(--border);font-size:12px;text-decoration:none;
        <?= $p === $current_page ? 'background:var(--accent);color:#fff;font-weight:600;' : 'background:var(--bg2);color:var(--text);' ?>">
        <?= $p ?>
        </a>
    <?php endfor; ?>

    <?php if ($current_page < $total_pages): ?>
        <a href="<?= buildUrl(['page' => $current_page + 1]) ?>" style="padding:6px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg2);color:var(--text);font-size:12px;text-decoration:none;">Next →</a>
    <?php endif; ?>
    </div>
    <div style="text-align:center;font-size:11px;color:var(--text3);margin-top:6px;">
    Page <?= $current_page ?> of <?= $total_pages ?> · <?= $total_items ?> total subjects
    </div>
    <?php endif; ?>
  </div>
</main>
</div>
<!-- SUBJECT DETAIL MODAL -->
<div class="modal-overlay" id="subjectModal">
  <div class="modal modal-large" style="max-width:760px;width:90vw;max-height:88vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="flex-shrink:0;">
      <div class="modal-title" id="subj-modal-title">📚 Subject</div>
      <button class="modal-close" onclick="closeSubjectModal()">✕</button>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1;padding:16px;">
      <div id="subj-modal-info" style="margin-bottom:16px;padding:12px;border-radius:10px;background:var(--bg2,rgba(255,255,255,.03));border:1px solid var(--border,rgba(255,255,255,.07));font-size:13px;color:var(--text2);line-height:1.8;"></div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
        <div style="font-size:13px;font-weight:700;color:var(--text);">📖 Lessons</div>
        <button class="btn btn-primary btn-sm" onclick="openAddLessonModal(lastOpenedSubjectId)">+ Add Lesson</button>
      </div>
      <div id="subj-modal-lessons" style="display:flex;flex-direction:column;gap:8px;"></div>
    </div>
  </div>
</div>

<!-- LESSON DETAIL MODAL -->
<div class="modal-overlay" id="lessonModal">
  <div class="modal modal-large" style="max-width:680px;width:90vw;max-height:88vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="flex-shrink:0;">
      <div class="modal-title" id="lesson-modal-title">📖 Lesson</div>
      <div style="display:flex;align-items:center;gap:8px;">
        <div id="lesson-modal-action-btns" style="display:flex;gap:6px;"></div>
        <button class="modal-close" onclick="closeLessonModal()">✕</button>
      </div>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1;padding:16px;">
      <div id="lesson-modal-info" style="margin-bottom:16px;padding:12px;border-radius:10px;background:var(--bg2,rgba(255,255,255,.03));border:1px solid var(--border,rgba(255,255,255,.07));font-size:13px;color:var(--text2);line-height:1.8;"></div>
      <div id="lesson-quiz-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
        <div style="font-size:13px;font-weight:700;color:var(--text);">📝 Quizzes</div>
        <div id="lesson-quiz-add-btn"></div>
      </div>
      <div id="lesson-modal-quizzes" style="display:flex;flex-direction:column;gap:8px;"></div>
    </div>
    <div class="modal-footer" style="flex-shrink:0;">
      <button class="btn btn-ghost" onclick="backToSubjectModal()">← Back to Subject</button>
    </div>
  </div>
</div>

<!-- ADD LESSON MODAL -->
<div class="modal-overlay" id="addLessonModal">
  <div class="modal" style="max-width:500px;max-height:90vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="flex-shrink:0;">
      <div class="modal-title">📖 Add New Lesson</div>
      <button class="modal-close" onclick="closeAddLessonModal()">✕</button>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1;padding:16px;">
      <form id="addLessonForm" method="POST" action="teacher_courses.php" enctype="multipart/form-data">
        <input type="hidden" name="create_lesson" value="1">
        <input type="hidden" name="subject_id" id="addLesson_subjectId" value="">
        <input type="hidden" name="lesson_status" value="published">
        <div class="form-group">
          <label>Subject</label>
          <input type="text" id="addLesson_subjectName" disabled style="opacity:0.6;cursor:not-allowed;">
        </div>
        <div class="form-group">
          <label>Lesson Title *</label>
          <input type="text" name="title" id="addLesson_title" placeholder="e.g. The Cell Structure" required>
        </div>
        <div class="form-group">
          <label>Lesson</label>
          <textarea name="manual_content" id="addLesson_manualContent" style="min-height:160px;" placeholder="Type your lesson content here..."></textarea>
        </div>
        <div class="form-group">
          <label>Resource Type <small style="color:var(--text3);font-weight:400;">(optional)</small></label>
          <select name="resource_type" id="addLesson_resourceType" onchange="toggleAddLessonFile()">
            <option value="none">None</option>
            <option value="video">Video Lecture</option>
            <option value="pdf">PDF Document</option>
            <option value="ppt">Presentation (PPT/PPTX)</option>
          </select>
        </div>
        <div class="form-group" id="addLesson_fileGroup" style="display:none;">
          <label>Upload File <small style="color:var(--text3);font-weight:400;">(optional)</small></label>
          <input type="file" name="resource_file" id="addLesson_file">
        </div>
        <div class="form-group" id="addLesson_descGroup">
          <label>Description</label>
          <textarea name="description" placeholder="Brief overview..."></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer" style="flex-shrink:0;">
      <button class="btn btn-ghost" onclick="closeAddLessonModal()">Cancel</button>
      <button class="btn btn-primary" onclick="document.getElementById('addLessonForm').submit()">Create Lesson</button>
    </div>
  </div>
</div>

<!-- ADD QUIZ MODAL -->
<div class="modal-overlay" id="addQuizModal">
  <div class="modal modal-large" style="max-width:620px;max-height:92vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="flex-shrink:0;">
      <div class="modal-title">📝 Add New Quiz</div>
      <button class="modal-close" onclick="closeAddQuizModal()">✕</button>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1;padding:16px;">
      <form id="addQuizForm" method="POST" action="teacher_courses.php">
        <input type="hidden" name="create_quiz" value="1">
        <input type="hidden" name="lesson_id" id="addQuiz_lessonId" value="">
        <div class="form-group">
          <label>Lesson</label>
          <input type="text" id="addQuiz_lessonName" disabled style="opacity:0.6;cursor:not-allowed;">
        </div>
        <div class="form-group">
          <label>Quiz Description *</label>
          <textarea name="description" id="addQuiz_desc" placeholder="What is this quiz about?" required style="min-height:70px;"></textarea>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <div class="form-group" style="flex:1;min-width:120px;">
            <label>Duration (minutes) *</label>
            <input type="number" name="duration" min="1" max="180" value="30" required>
          </div>
          <div class="form-group" style="flex:1;min-width:120px;">
            <label>Passing Score (%) *</label>
            <input type="number" name="passing_score" min="1" max="100" value="75" required>
          </div>
          <div class="form-group" style="flex:1;min-width:140px;">
            <label>Due Date <small style="color:var(--text3);font-weight:400;">(optional)</small></label>
            <input type="datetime-local" name="due_date" style="width:100%;padding:8px 10px;font-size:13px;border:1px solid var(--border,rgba(255,255,255,.1));border-radius:8px;background:var(--bg2,rgba(255,255,255,.03));color:var(--text);box-sizing:border-box;">
          </div>
        </div>
        <div style="margin-top:4px;border-top:1px solid var(--border,rgba(255,255,255,.08));padding-top:14px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div style="font-size:13px;font-weight:700;color:var(--text);">📋 Questions <span id="addQuizQCountBadge" style="font-size:11px;font-weight:400;opacity:0.6;">(0)</span></div>
            <button type="button" onclick="addCourseQuestion('add')" style="font-size:11px;padding:5px 12px;border-radius:7px;border:none;background:var(--accent,#6366f1);color:#fff;cursor:pointer;font-weight:600;">+ Add Question</button>
          </div>
          <div id="addQuiz_questionsContainer"></div>
          <input type="hidden" name="question_count" id="addQuiz_questionCount" value="0">
          <input type="hidden" name="questions_json" id="addQuiz_questionsJson" value="[]">
        </div>
      </form>
    </div>
    <div class="modal-footer" style="flex-shrink:0;">
      <button class="btn btn-ghost" onclick="closeAddQuizModal()">Cancel</button>
      <button class="btn btn-primary" onclick="submitCourseAddQuiz()">Create Quiz</button>
    </div>
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

<!-- EDIT LESSON MODAL -->
<div class="modal-overlay" id="editLessonModal">
  <div class="modal" style="max-width:500px;max-height:90vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="flex-shrink:0;">
      <div class="modal-title">✏️ Edit Lesson</div>
      <button class="modal-close" onclick="closeEditLessonModal()">✕</button>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1;padding:16px;">
      <form id="editLessonForm" method="POST" action="teacher_courses.php" enctype="multipart/form-data">
        <input type="hidden" name="edit_lesson" value="1">
        <input type="hidden" name="lesson_id" id="editLesson_id" value="">
        <div class="form-group">
          <label>Lesson Title *</label>
          <input type="text" name="title" id="editLesson_title" placeholder="Lesson title" required>
        </div>
        <div class="form-group">
          <label>Lesson</label>
          <textarea name="manual_content" id="editLesson_manualContent" style="min-height:160px;" placeholder="Type your lesson content here..."></textarea>
        </div>
        <div class="form-group">
          <label>Resource Type <small style="color:var(--text3);font-weight:400;">(optional)</small></label>
          <select name="resource_type" id="editLesson_resourceType" onchange="toggleEditLessonFile()">
            <option value="none">None</option>
            <option value="video">Video Lecture</option>
            <option value="pdf">PDF Document</option>
            <option value="ppt">Presentation (PPT/PPTX)</option>
          </select>
        </div>
        <div class="form-group" id="editLesson_fileGroup" style="display:none;">
          <label>Upload File <small style="color:var(--text3);font-weight:400;">(optional)</small></label>
          <input type="file" name="resource_file" id="editLesson_file">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" id="editLesson_description" placeholder="Brief overview..."></textarea>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="editLesson_status">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
          </select>
        </div>
      </form>
    </div>
    <div class="modal-footer" style="flex-shrink:0;">
      <button class="btn btn-ghost" onclick="closeEditLessonModal()">Cancel</button>
      <button class="btn btn-primary" onclick="document.getElementById('editLessonForm').submit()">Save Changes</button>
    </div>
  </div>
</div>

<!-- DELETE LESSON CONFIRM MODAL -->
<div class="modal-overlay" id="deleteLessonModal">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <div class="modal-title">🗑 Delete Lesson</div>
      <button class="modal-close" onclick="closeDeleteLessonModal()">✕</button>
    </div>
    <div class="modal-body" style="text-align:center;padding:28px 20px;">
      <div style="font-size:42px;margin-bottom:12px;">⚠️</div>
      <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:6px;">Delete "<span id="deleteLesson_title"></span>"?</div>
      <div style="font-size:12px;color:var(--text3);">This will also delete all quizzes and student results under this lesson. This cannot be undone.</div>
    </div>
    <div class="modal-footer" style="justify-content:center;padding:0 20px 24px;">
      <button class="btn btn-ghost" onclick="closeDeleteLessonModal()">Cancel</button>
      <form id="deleteLessonForm" method="POST" action="teacher_courses.php" style="display:inline;">
        <input type="hidden" name="delete_lesson" value="1">
        <input type="hidden" name="lesson_id" id="deleteLesson_id" value="">
        <button type="submit" class="btn btn-danger">Yes, Delete</button>
      </form>
    </div>
  </div>
</div>

<!-- EDIT QUIZ MODAL -->
<div class="modal-overlay" id="editQuizModal">
  <div class="modal modal-large" style="max-width:620px;max-height:92vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="flex-shrink:0;">
      <div class="modal-title">✏️ Edit Quiz</div>
      <button class="modal-close" onclick="closeEditQuizModal()">✕</button>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1;padding:16px;">
      <form id="editQuizForm" method="POST" action="teacher_courses.php">
        <input type="hidden" name="edit_quiz" value="1">
        <input type="hidden" name="quiz_id" id="editQuiz_id" value="">
        <div class="form-group">
          <label>Quiz Description *</label>
          <textarea name="description" id="editQuiz_desc" required style="min-height:70px;" placeholder="What is this quiz about?"></textarea>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <div class="form-group" style="flex:1;min-width:110px;">
            <label>Duration (minutes) *</label>
            <input type="number" name="duration" id="editQuiz_duration" min="1" max="180" value="30" required>
          </div>
          <div class="form-group" style="flex:1;min-width:110px;">
            <label>Passing Score (%) *</label>
            <input type="number" name="passing_score" id="editQuiz_passing" min="1" max="100" value="75" required>
          </div>
          <div class="form-group" style="flex:1;min-width:100px;">
            <label>Status</label>
            <select name="status" id="editQuiz_status">
              <option value="draft">Draft</option>
              <option value="published">Published</option>
            </select>
          </div>
          <div class="form-group" style="flex:1;min-width:140px;">
            <label>Due Date <small style="color:var(--text3);font-weight:400;">(optional)</small></label>
            <input type="datetime-local" name="due_date" id="editQuiz_duedate" style="width:100%;padding:8px 10px;font-size:13px;border:1px solid var(--border,rgba(255,255,255,.1));border-radius:8px;background:var(--bg2,rgba(255,255,255,.03));color:var(--text);box-sizing:border-box;">
          </div>
        </div>
        <div style="margin-top:4px;border-top:1px solid var(--border,rgba(255,255,255,.08));padding-top:14px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div style="font-size:13px;font-weight:700;color:var(--text);">📋 Questions <span id="editQuizQCountBadge" style="font-size:11px;font-weight:400;opacity:0.6;">(0)</span></div>
            <button type="button" onclick="addCourseQuestion('edit')" style="font-size:11px;padding:5px 12px;border-radius:7px;border:none;background:var(--accent,#6366f1);color:#fff;cursor:pointer;font-weight:600;">+ Add Question</button>
          </div>
          <div id="editQuiz_questionsContainer"></div>
          <input type="hidden" name="question_count" id="editQuiz_questionCount" value="0">
          <input type="hidden" name="questions_json" id="editQuiz_questionsJson" value="[]">
        </div>
      </form>
    </div>
    <div class="modal-footer" style="flex-shrink:0;">
      <button class="btn btn-ghost" onclick="closeEditQuizModal()">Cancel</button>
      <button class="btn btn-primary" onclick="submitCourseEditQuiz()">Save Changes</button>
    </div>
  </div>
</div>

<!-- VIEW QUIZ MODAL -->
<div class="modal-overlay" id="viewQuizModal">
  <div class="modal modal-large" style="max-width:780px;width:92vw;max-height:90vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="flex-shrink:0;">
      <div class="modal-title" id="viewQuiz-modal-title">📝 Quiz</div>
      <div style="display:flex;align-items:center;gap:8px;">
        <div id="viewQuiz-modal-action-btns" style="display:flex;gap:6px;"></div>
        <button class="modal-close" onclick="closeViewQuizModal()">✕</button>
      </div>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1;padding:16px;">
      <div id="viewQuiz-modal-info" style="margin-bottom:16px;padding:12px;border-radius:10px;background:var(--bg2,rgba(255,255,255,.03));border:1px solid var(--border,rgba(255,255,255,.07));font-size:13px;color:var(--text2);line-height:1.8;"></div>

      <!-- Tab Switcher -->
      <div style="display:flex;gap:6px;margin-bottom:14px;border-bottom:1px solid var(--border,rgba(255,255,255,.08));padding-bottom:0;">
        <button id="vq-tab-questions" onclick="switchViewQuizTab('questions')"
          style="padding:7px 16px;font-size:12px;font-weight:600;border:none;border-bottom:2px solid var(--accent,#6366f1);background:transparent;color:var(--accent,#6366f1);cursor:pointer;margin-bottom:-1px;">
          📋 Questions & Answers
        </button>
        <button id="vq-tab-results" onclick="switchViewQuizTab('results')"
          style="padding:7px 16px;font-size:12px;font-weight:600;border:none;border-bottom:2px solid transparent;background:transparent;color:var(--text3);cursor:pointer;margin-bottom:-1px;">
          👥 Student Results <span id="vq-results-badge" style="font-size:10px;padding:1px 6px;border-radius:10px;background:rgba(99,102,241,0.12);color:var(--accent,#6366f1);margin-left:3px;">0</span>
        </button>
      </div>

      <!-- Questions Tab -->
      <div id="vq-panel-questions">
        <div id="viewQuiz-modal-questions" style="display:flex;flex-direction:column;"></div>
      </div>

      <!-- Results Tab -->
      <div id="vq-panel-results" style="display:none;">
        <div id="viewQuiz-modal-results"></div>
      </div>
    </div>
    <div class="modal-footer" style="flex-shrink:0;">
      <button class="btn btn-ghost" onclick="closeViewQuizModal()">← Back to Lesson</button>
    </div>
  </div>
</div>

<!-- STUDENT RESULT DETAIL MODAL -->
<div class="modal-overlay" id="studentResultModal">
  <div class="modal modal-large" style="max-width:680px;width:92vw;max-height:90vh;display:flex;flex-direction:column;">
    <div class="modal-header" style="flex-shrink:0;">
      <div class="modal-title" id="srm-title">📋 Student Result</div>
      <button class="modal-close" onclick="closeStudentResultModal()">✕</button>
    </div>
    <div class="modal-body" style="overflow-y:auto;flex:1;padding:16px;">
      <div id="srm-summary" style="margin-bottom:14px;padding:12px;border-radius:10px;background:var(--bg2,rgba(255,255,255,.03));border:1px solid var(--border,rgba(255,255,255,.07));font-size:13px;color:var(--text2);line-height:1.9;"></div>
      <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:10px;">📋 Quiz Questions</div>
      <div id="srm-questions" style="display:flex;flex-direction:column;gap:8px;"></div>
    </div>
    <div class="modal-footer" style="flex-shrink:0;">
      <button class="btn btn-ghost" onclick="closeStudentResultModal()">← Back to Results</button>
    </div>
  </div>
</div>

<!-- DELETE QUIZ CONFIRM MODAL -->
<div class="modal-overlay" id="deleteQuizModal">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <div class="modal-title">🗑 Delete Quiz</div>
      <button class="modal-close" onclick="closeDeleteQuizModal()">✕</button>
    </div>
    <div class="modal-body" style="text-align:center;padding:28px 20px;">
      <div style="font-size:42px;margin-bottom:12px;">⚠️</div>
      <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:6px;">Delete "<span id="deleteQuiz_title"></span>"?</div>
      <div style="font-size:12px;color:var(--text3);">This will also delete all student results for this quiz. This cannot be undone.</div>
    </div>
    <div class="modal-footer" style="justify-content:center;padding:0 20px 24px;">
      <button class="btn btn-ghost" onclick="closeDeleteQuizModal()">Cancel</button>
      <form id="deleteQuizForm" method="POST" action="teacher_courses.php" style="display:inline;">
        <input type="hidden" name="delete_quiz" value="1">
        <input type="hidden" name="quiz_id" id="deleteQuiz_id" value="">
        <button type="submit" class="btn btn-danger">Yes, Delete</button>
      </form>
    </div>
  </div>
</div>
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
<style>
.flash-msg {
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 18px;border-radius:10px;margin-bottom:16px;
  font-size:0.9rem;font-weight:500;
  animation:flashIn .3s ease;
}
.flash-success { background:rgba(34,197,94,0.15);color:var(--green);border:1px solid rgba(34,197,94,0.3); }
.flash-error   { background:rgba(239,68,68,0.15);color:var(--red);border:1px solid rgba(239,68,68,0.3); }
@keyframes flashIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
</style>

<script>
// ── Data ──────────────────────────────────────────────────────────────────
var lessonsBySubject = <?php echo $lessons_by_subject_json ?: '{}'; ?>;
var quizzesByLesson  = <?php echo $quizzes_by_lesson_json ?: '{}'; ?>;
var subjectsMap      = <?php echo $subjects_map_json ?: '{}'; ?>;
var lastOpenedSubjectId = null;
var lastOpenedLessonId  = null;

var quizResultsMap = <?php echo $quiz_results_map_json ?: '{}'; ?>;
var currentViewQuizId = null;

// ── Course Question Builder (mirrors teacher_quizzes.php style) ───────────
var addCourseQIdx  = 0;
var editCourseQIdx = 0;

function escapeAttr(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function getCourseAnswerHtml(idx, type, data, mode) {
  var prefix = 'questions[' + idx + ']';

  if (type === 'multiple_choice') {
    var cv = data ? (data.correct || '') : '';
    var o = (data && data.options) ? data.options : { a:'', b:'', c:'', d:'' };
    var h = '<div style="font-size:11px;font-weight:600;color:var(--text2);margin:6px 0 4px;">Choices</div>';
    h += '<input type="text" name="' + prefix + '[opt_a]" placeholder="Choice A" value="' + escapeAttr(o.a) + '" style="width:100%;padding:6px 8px;font-size:12px;border-radius:6px;border:1px solid var(--border,rgba(255,255,255,.1));background:var(--bg2);color:var(--text);box-sizing:border-box;margin-bottom:4px;">';
    h += '<input type="text" name="' + prefix + '[opt_b]" placeholder="Choice B" value="' + escapeAttr(o.b) + '" style="width:100%;padding:6px 8px;font-size:12px;border-radius:6px;border:1px solid var(--border,rgba(255,255,255,.1));background:var(--bg2);color:var(--text);box-sizing:border-box;margin-bottom:4px;">';
    h += '<input type="text" name="' + prefix + '[opt_c]" placeholder="Choice C" value="' + escapeAttr(o.c) + '" style="width:100%;padding:6px 8px;font-size:12px;border-radius:6px;border:1px solid var(--border,rgba(255,255,255,.1));background:var(--bg2);color:var(--text);box-sizing:border-box;margin-bottom:4px;">';
    h += '<input type="text" name="' + prefix + '[opt_d]" placeholder="Choice D" value="' + escapeAttr(o.d) + '" style="width:100%;padding:6px 8px;font-size:12px;border-radius:6px;border:1px solid var(--border,rgba(255,255,255,.1));background:var(--bg2);color:var(--text);box-sizing:border-box;margin-bottom:6px;">';
    h += '<div style="font-size:11px;font-weight:600;color:var(--text2);margin-bottom:4px;">Correct Answer</div>';
    h += '<select name="' + prefix + '[correct]" style="width:100%;padding:6px 8px;font-size:12px;border-radius:6px;border:1px solid var(--border,rgba(255,255,255,.1));background:var(--bg2);color:var(--text);">';
    h += '<option value="a"' + (cv==='a'||cv==='0'?' selected':'') + '>A</option>';
    h += '<option value="b"' + (cv==='b'||cv==='1'?' selected':'') + '>B</option>';
    h += '<option value="c"' + (cv==='c'||cv==='2'?' selected':'') + '>C</option>';
    h += '<option value="d"' + (cv==='d'||cv==='3'?' selected':'') + '>D</option>';
    h += '</select>';
    return h;

  } else if (type === 'essay') {
    var h = '<div style="margin-top:4px;padding:8px 10px;border-radius:7px;background:rgba(99,102,241,0.05);border:1px solid rgba(99,102,241,0.12);font-size:11px;color:var(--text3);line-height:1.5;">📝 Essay type — no answer key. The teacher will manually score each students answers. </div>';
    h += '<input type="hidden" name="' + prefix + '[correct]" value="">';
    return h;

  } else {
    // identification / fill_in_the_blank
    var av = data ? (data.correct || '') : '';
    var ph = type === 'fill_in_the_blank' ? 'Word/phrase that fills the blank...' : 'Type the correct answer...';
    var h = '<div style="font-size:11px;font-weight:600;color:var(--text2);margin:6px 0 4px;">Correct Answer</div>';
    h += '<input type="text" name="' + prefix + '[correct]" placeholder="' + ph + '" value="' + escapeAttr(av) + '" style="width:100%;padding:6px 8px;font-size:12px;border-radius:6px;border:1px solid var(--border,rgba(255,255,255,.1));background:var(--bg2);color:var(--text);box-sizing:border-box;">';
    return h;
  }
}

function buildCourseQuestionBlock(idx, data, mode) {
  var type = data ? (data.type || 'multiple_choice') : 'multiple_choice';
  // normalize type names
  if (type === 'fill_blank') type = 'fill_in_the_blank';

  var prefix = 'questions[' + idx + ']';
  var removeFunc = mode === 'add' ? 'removeCourseAddQuestion' : 'removeCourseEditQuestion';

  var h = '<div class="q-course-block" id="qCourse_' + mode + '_' + idx + '" style="padding:12px;border-radius:10px;background:var(--bg2,rgba(255,255,255,.03));border:1px solid var(--border,rgba(255,255,255,.08));margin-bottom:10px;position:relative;">';
  h += '<button type="button" onclick="' + removeFunc + '(' + idx + ')" style="position:absolute;top:8px;right:8px;border:none;background:rgba(239,68,68,0.12);color:#ef4444;border-radius:5px;padding:2px 8px;font-size:11px;cursor:pointer;">✕</button>';
  h += '<div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:8px;">Q' + idx + '</div>';
  h += '<select name="' + prefix + '[type]" onchange="swapCourseAnswerArea(\'' + mode + '\',' + idx + ')" style="width:100%;padding:6px 8px;font-size:12px;border-radius:6px;border:1px solid var(--border,rgba(255,255,255,.1));background:var(--bg2);color:var(--text);margin-bottom:6px;">';
  h += '<option value="multiple_choice"' + (type==='multiple_choice'?' selected':'') + '>Multiple Choice</option>';
  h += '<option value="fill_in_the_blank"' + (type==='fill_in_the_blank'?' selected':'') + '>Fill in the Blank</option>';
  h += '<option value="identification"' + (type==='identification'?' selected':'') + '>Identification</option>';
  h += '<option value="essay"' + (type==='essay'?' selected':'') + '>Essay</option>';
  h += '</select>';
  h += '<textarea name="' + prefix + '[text]" placeholder="Enter question..." style="width:100%;padding:7px 9px;font-size:12px;border-radius:6px;border:1px solid var(--border,rgba(255,255,255,.1));background:var(--bg2);color:var(--text);box-sizing:border-box;resize:vertical;min-height:54px;margin-bottom:4px;">' + escapeAttr(data ? data.text : '') + '</textarea>';
  h += '<div id="qCourseAnswer_' + mode + '_' + idx + '">' + getCourseAnswerHtml(idx, type, data, mode) + '</div>';
  h += '</div>';
  return h;
}

function swapCourseAnswerArea(mode, idx) {
  var block = document.getElementById('qCourse_' + mode + '_' + idx);
  if (!block) return;
  var sel = block.querySelector('select[name*="[type]"]');
  var area = document.getElementById('qCourseAnswer_' + mode + '_' + idx);
  if (sel && area) area.innerHTML = getCourseAnswerHtml(idx, sel.value, null, mode);
}

function addCourseQuestion(mode) {
  if (mode === 'add') {
    addCourseQIdx++;
    var c = document.getElementById('addQuiz_questionsContainer');
    if (c) c.insertAdjacentHTML('beforeend', buildCourseQuestionBlock(addCourseQIdx, null, 'add'));
    syncCourseQCount('add');
  } else {
    editCourseQIdx++;
    var c = document.getElementById('editQuiz_questionsContainer');
    if (c) c.insertAdjacentHTML('beforeend', buildCourseQuestionBlock(editCourseQIdx, null, 'edit'));
    syncCourseQCount('edit');
  }
}

function removeCourseAddQuestion(idx) {
  var b = document.getElementById('qCourse_add_' + idx);
  if (b) { b.remove(); syncCourseQCount('add'); }
}

function removeCourseEditQuestion(idx) {
  var b = document.getElementById('qCourse_edit_' + idx);
  if (b) { b.remove(); syncCourseQCount('edit'); }
}

function syncCourseQCount(mode) {
  if (mode === 'add') {
    var c = document.getElementById('addQuiz_questionsContainer');
    var n = c ? c.querySelectorAll('.q-course-block').length : 0;
    var h = document.getElementById('addQuiz_questionCount');
    var b = document.getElementById('addQuizQCountBadge');
    if (h) h.value = n;
    if (b) b.textContent = '(' + n + ')';
  } else {
    var c = document.getElementById('editQuiz_questionsContainer');
    var n = c ? c.querySelectorAll('.q-course-block').length : 0;
    var b = document.getElementById('editQuizQCountBadge');
    if (b) b.textContent = '(' + n + ')';
  }
}

// ── Modal openers ─────────────────────────────────────────────────────────
function openAddQuizModal(lessonId, lessonTitle) {
  addCourseQIdx = 0;
  document.getElementById('addQuiz_lessonId').value = lessonId;
  document.getElementById('addQuiz_lessonName').value = lessonTitle;
  document.getElementById('addQuiz_desc').value = '';
  document.getElementById('addQuiz_questionsContainer').innerHTML = '';
  document.getElementById('addQuiz_questionCount').value = '0';
  syncCourseQCount('add');
  document.getElementById('addQuizModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeAddQuizModal() {
  document.getElementById('addQuizModal').classList.remove('open');
  document.getElementById('lessonModal').classList.add('open');
}

function openEditQuizModal(quizId) {
  var quizzes = quizzesByLesson[lastOpenedLessonId] || [];
  var quiz = quizzes.find(function(q){ return q.id === quizId; });
  if (!quiz) return;

  document.getElementById('viewQuizModal').classList.remove('open');
  editCourseQIdx = 0;
  document.getElementById('editQuiz_id').value       = quizId;
  document.getElementById('editQuiz_desc').value     = quiz.title;
  document.getElementById('editQuiz_duration').value = quiz.duration || 30;
  document.getElementById('editQuiz_passing').value  = quiz.passing_score;
  document.getElementById('editQuiz_status').value   = quiz.status || 'draft';
  var rawDue = quiz.due_date_raw || '';
  var fmtDue = '';
  if (rawDue) {
    var d = new Date(rawDue);
    if (!isNaN(d)) {
      fmtDue = d.getFullYear() + '-'
        + String(d.getMonth()+1).padStart(2,'0') + '-'
        + String(d.getDate()).padStart(2,'0') + 'T'
        + String(d.getHours()).padStart(2,'0') + ':'
        + String(d.getMinutes()).padStart(2,'0');
    }
  }
  document.getElementById('editQuiz_duedate').value = fmtDue;

  var container = document.getElementById('editQuiz_questionsContainer');
  container.innerHTML = '';

  if (quiz.questions && Array.isArray(quiz.questions) && quiz.questions.length > 0) {
    quiz.questions.forEach(function(q) {
      editCourseQIdx++;
      container.insertAdjacentHTML('beforeend', buildCourseQuestionBlock(editCourseQIdx, q, 'edit'));
    });
  }
  syncCourseQCount('edit');

  document.getElementById('editQuizModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function collectCourseQuestions(mode) {
  var container = document.getElementById(mode === 'add' ? 'addQuiz_questionsContainer' : 'editQuiz_questionsContainer');
  var blocks = container ? container.querySelectorAll('.q-course-block') : [];
  var arr = [];
  blocks.forEach(function(block) {
    var typeEl  = block.querySelector('select[name*="[type]"]');
    var textEl  = block.querySelector('textarea[name*="[text]"]');
    var corrEl  = block.querySelector('[name*="[correct]"]');
    var type    = typeEl ? typeEl.value : 'multiple_choice';
    var text    = textEl ? textEl.value : '';
    var correct = corrEl ? corrEl.value : '';
    var item = { type: type, text: text, correct: correct };
    if (type === 'multiple_choice') {
      var opts = {};
      ['a','b','c','d'].forEach(function(l) {
        var el = block.querySelector('[name*="[opt_' + l + ']"]');
        opts[l] = el ? el.value : '';
      });
      item.options = opts;
    }
    arr.push(item);
  });
  return arr;
}

function validateCourseQuestions(arr) {
  if (arr.length === 0) {
    alert('Please add at least one question before saving the quiz.');
    return false;
  }
  for (var i = 0; i < arr.length; i++) {
    var q = arr[i];
    if (!q.text || !q.text.trim()) {
      alert('There is no question text in this question as well ' + (i + 1) + '. Please fill it in first.');
      return false;
    }
    if (q.type === 'multiple_choice') {
      var o = q.options || {};
      if (!o.a || !o.a.trim() || !o.b || !o.b.trim() || !o.c || !o.c.trim() || !o.d || !o.d.trim()) {
        alert('Please complete the four choices (A–D) in the question first. ' + (i + 1) + '.');
        return false;
      }
    } else if (q.type === 'essay') {
      // walang kailangang answer key — manually i-iscore ng teacher ang sagot ng student
    } else {
      if (!q.correct || !q.correct.toString().trim()) {
        alert('No correct answer has been selected for the question. ' + (i + 1) + ' (' + q.type + '). Please fill it in first.');
        return false;
      }
    }
  }
  return true;
}

function submitCourseAddQuiz() {
  var arr = collectCourseQuestions('add');
  if (!validateCourseQuestions(arr)) return;
  document.getElementById('addQuiz_questionsJson').value = JSON.stringify(arr);
  document.getElementById('addQuiz_questionCount').value = arr.length;
  document.getElementById('addQuizForm').submit();
}

function submitCourseEditQuiz() {
  var arr = collectCourseQuestions('edit');
  if (!validateCourseQuestions(arr)) return;
  document.getElementById('editQuiz_questionsJson').value = JSON.stringify(arr);
  document.getElementById('editQuiz_questionCount').value = arr.length;
  document.getElementById('editQuizForm').submit();
}

function closeEditQuizModal() {
  document.getElementById('editQuizModal').classList.remove('open');
  document.getElementById('viewQuizModal').classList.add('open');
}

function openViewQuizModal(quizId) {
  var quizzes = quizzesByLesson[lastOpenedLessonId] || [];
  var quiz = quizzes.find(function(q){ return q.id === quizId; });
  if (!quiz) return;
  currentViewQuizId = quizId;

  document.getElementById('viewQuiz-modal-title').textContent = '📝 ' + quiz.title;

  var safeQTitle = quiz.title.replace(/\\/g,'\\\\').replace(/'/g,"\\'");
  document.getElementById('viewQuiz-modal-action-btns').innerHTML =
    '<button onclick="openEditQuizModal(' + quizId + ')" style="font-size:11px;padding:5px 10px;border-radius:6px;border:none;background:rgba(99,102,241,0.12);color:var(--accent,#6366f1);cursor:pointer;font-weight:600;">✏️ Edit</button>' +
    '<button onclick="confirmDeleteQuiz(' + quizId + ', \'' + safeQTitle + '\')" style="font-size:11px;padding:5px 10px;border-radius:6px;border:none;background:rgba(239,68,68,0.12);color:#ef4444;cursor:pointer;font-weight:600;">🗑 Delete</button>';

  var statusBadge = quiz.status === 'published'
    ? '<span style="font-size:10px;padding:2px 7px;border-radius:5px;background:rgba(34,197,94,0.15);color:var(--green);">Published</span>'
    : '<span style="font-size:10px;padding:2px 7px;border-radius:5px;background:rgba(255,255,255,0.06);color:var(--text3);">Draft</span>';
  var headerHtml = '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:6px;">' + statusBadge + '</div>';
  headerHtml += '<div>📅 <b>Due:</b> ' + quiz.due_date + '</div>';
  headerHtml += '<div>🎯 <b>Passing Score:</b> ' + quiz.passing_score + '%</div>';
  var quizResults = quizResultsMap[quizId] || [];
  var uniqueSubmitters = {};
  quizResults.forEach(function(r){ uniqueSubmitters[r.student_id] = 1; });
  headerHtml += '<div>📬 <b>Submissions:</b> ' + Object.keys(uniqueSubmitters).length + ' student(s) · ' + quizResults.length + ' total take(s)</div>';
  headerHtml += '<div>📋 <b>Total Questions:</b> ' + (quiz.questions ? quiz.questions.length : 0) + '</div>';
  document.getElementById('viewQuiz-modal-info').innerHTML = headerHtml;

  // ── Render Questions Tab ──
  var qListEl = document.getElementById('viewQuiz-modal-questions');
  var questions = quiz.questions || [];
  if (questions.length === 0) {
    qListEl.innerHTML = '<div style="text-align:center;padding:24px;color:var(--text3);font-size:13px;">No questions added yet.</div>';
  } else {
    var typeLabels = { multiple_choice:'Multiple Choice', fill_in_the_blank:'Fill in the Blank', fill_blank:'Fill in the Blank', identification:'Identification', essay:'Essay' };
    var letters = ['a','b','c','d'];
    qListEl.innerHTML = questions.map(function(q, idx) {
      var typeLabel = typeLabels[q.type] || q.type;
      var body = '';
      if (q.type === 'multiple_choice') {
        var correctKey = (q.correct || '').toString().toLowerCase();
        var opts = q.options || {};
        body += '<div style="display:flex;flex-direction:column;gap:6px;margin-top:8px;">';
        letters.forEach(function(letter) {
          var choiceText = opts[letter];
          if (choiceText === undefined || choiceText === null || choiceText === '') return;
          var isCorrect = letter === correctKey;
          body += '<div style="display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:7px;' +
            (isCorrect ? 'background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);' : 'background:var(--bg3,rgba(255,255,255,.03));border:1px solid var(--border,rgba(255,255,255,.06));') + '">' +
            '<span style="font-size:11px;font-weight:700;color:' + (isCorrect ? 'var(--green)' : 'var(--text3)') + ';text-transform:uppercase;min-width:16px;">' + letter + '</span>' +
            '<span style="font-size:12px;color:var(--text);flex:1;">' + escHtml(choiceText) + '</span>' +
            (isCorrect ? '<span style="font-size:11px;color:var(--green);font-weight:600;">✓ Correct</span>' : '') +
            '</div>';
        });

        body += '</div>';
      } else if (q.type === 'essay') {
        body += '<div style="margin-top:6px;padding:8px 10px;border-radius:7px;background:rgba(99,102,241,0.05);border:1px solid rgba(99,102,241,0.12);font-size:11px;color:var(--text3);">📝 Essay type — no answer key. The teacher will manually score each student\'s answer.</div>';
      } else {
        body += '<div style="font-size:11px;font-weight:600;color:var(--text2);margin:8px 0 4px;">Correct Answer</div>';
        body += '<div style="font-size:12px;color:var(--text);padding:8px 10px;border-radius:7px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);font-weight:600;">' + escHtml(q.correct || '') + '</div>';
      }
      return '<div style="padding:12px;border-radius:10px;background:var(--bg2,rgba(255,255,255,.03));border:1px solid var(--border,rgba(255,255,255,.08));margin-bottom:10px;">' +
        '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:4px;">' +
          '<div style="font-size:12px;font-weight:700;color:var(--text);">Q' + (idx+1) + '</div>' +
          '<span style="font-size:10px;padding:2px 7px;border-radius:5px;background:rgba(99,102,241,0.12);color:var(--accent,#6366f1);">' + typeLabel + '</span>' +
        '</div>' +
        '<div style="font-size:13px;color:var(--text);margin-bottom:4px;">' + escHtml(q.text || '') + '</div>' +
        body +
      '</div>';
    }).join('');
  }

  // ── Render Results Tab ──
  renderQuizResults(quizId, quiz);

  // Reset to questions tab
  switchViewQuizTab('questions');

  document.getElementById('lessonModal').classList.remove('open');
  document.getElementById('viewQuizModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function switchViewQuizTab(tab) {
  var isQ = tab === 'questions';
  document.getElementById('vq-panel-questions').style.display = isQ ? 'block' : 'none';
  document.getElementById('vq-panel-results').style.display   = isQ ? 'none' : 'block';
  var qBtn = document.getElementById('vq-tab-questions');
  var rBtn = document.getElementById('vq-tab-results');
  qBtn.style.borderBottomColor = isQ ? 'var(--accent,#6366f1)' : 'transparent';
  qBtn.style.color = isQ ? 'var(--accent,#6366f1)' : 'var(--text3)';
  rBtn.style.borderBottomColor = isQ ? 'transparent' : 'var(--accent,#6366f1)';
  rBtn.style.color = isQ ? 'var(--text3)' : 'var(--accent,#6366f1)';
}

function renderQuizResults(quizId, quiz) {
  var results = quizResultsMap[quizId] || [];
  var el = document.getElementById('viewQuiz-modal-results');
  var badge = document.getElementById('vq-results-badge');
  if (results.length === 0) {
    if (badge) badge.textContent = 0;
    el.innerHTML = '<div style="text-align:center;padding:40px 16px;color:var(--text3);font-size:13px;"><div style="font-size:32px;margin-bottom:8px;">📭</div>No submissions yet.</div>';
    return;
  }

  var totalQuestions = (quiz && quiz.questions) ? quiz.questions.length : 0;
  var passingScore   = quiz ? parseInt(quiz.passing_score) || 75 : 75;
  var isEssayQuiz     = !!(quiz && quiz.questions && quiz.questions.length > 0 &&
                        quiz.questions.every(function(qq){ return qq.type === 'essay'; }));

  // Group by student_id
  var studentMap = {};
  results.forEach(function(r, rIdx) {
    var sid = r.student_id;
    if (!studentMap[sid]) studentMap[sid] = [];
    studentMap[sid].push({ r: r, rIdx: rIdx });
  });

  if (badge) badge.textContent = Object.keys(studentMap).length;
  el.innerHTML = Object.keys(studentMap).map(function(sid) {
    var entries = studentMap[sid];
    var best    = entries.reduce(function(a, b) { return (a.r.score >= b.r.score ? a : b); });
    var r       = best.r;
    var takeCount = entries.length;

    var rawScore = String(r.score_raw || r.score || '0');
    var scoreNum = parseInt(rawScore.replace(/[^0-9]/g, '')) || 0;
    var pct      = totalQuestions > 0 ? Math.round((scoreNum / totalQuestions) * 100) : 0;
    var passed   = pct >= passingScore;
    var fn       = String(r.firstname || '');
    var ln       = String(r.lastname  || '');
    var fullName = escHtml((fn + ' ' + ln).trim() || 'Unknown');
    var initials = ((fn.charAt(0) || '') + (ln.charAt(0) || '')).toUpperCase() || '?';

    var timeDisplay = '—';
    if (r.time_used !== null && r.time_used !== undefined && String(r.time_used) !== '') {
      var secs = parseInt(r.time_used) || 0;
      var mm   = Math.floor(secs / 60);
      var ss   = secs % 60;
      timeDisplay = mm + ':' + (ss < 10 ? '0' : '') + ss;
    }

    var scoreDisplay = scoreNum + (totalQuestions > 0 ? '/' + totalQuestions : '');
    var takeBadge = takeCount > 1
      ? '<span style="font-size:10px;padding:2px 7px;border-radius:10px;background:rgba(99,102,241,0.15);color:var(--accent);margin-left:6px;">' + takeCount + ' takes</span>'
      : '<span style="font-size:10px;padding:2px 7px;border-radius:10px;background:rgba(255,255,255,0.06);color:var(--text3);margin-left:6px;">1 take</span>';

    // Serialize entries para sa onclick
    var entriesJson = escHtml(JSON.stringify(entries.map(function(e) { return e.rIdx; })));

    return '<div style="border-radius:12px;background:var(--bg2,rgba(255,255,255,.03));border:1px solid ' +
        (passed ? 'rgba(34,197,94,0.2)' : 'rgba(239,68,68,0.15)') +
        ';margin-bottom:10px;overflow:hidden;">' +
      '<div onclick="handleStudentResultClick(' + quizId + ',' + sid + ',\'' + entriesJson + '\')" style="cursor:pointer;display:flex;align-items:center;gap:10px;padding:12px 14px;transition:background .15s;" ' +
           'onmouseover="this.style.background=\'rgba(99,102,241,0.05)\'" ' +
           'onmouseout="this.style.background=\'\'">' +
        '<div style="width:36px;height:36px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;background:linear-gradient(135deg,var(--accent,#6366f1),var(--purple,#8b5cf6));">' +
          escHtml(initials) +
        '</div>' +
        '<div style="flex:1;min-width:0;">' +
          '<div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + fullName + takeBadge + '</div>' +
          '<div style="font-size:11px;color:var(--text3);display:flex;flex-wrap:wrap;gap:8px;">' +
            '<span>📅 ' + escHtml(r.submitted_at) + '</span>' +
            '<span>⏱ ' + timeDisplay + '</span>' +
          '</div>' +
        '</div>' +
        (isEssayQuiz
          ? ('<div style="text-align:right;flex-shrink:0;min-width:80px;">' +
              '<div style="font-size:11px;color:var(--text3);margin-bottom:2px;">' + ((r.manual_score !== null && r.manual_score !== undefined) ? 'Score' : 'Status') + '</div>' +
              ((r.manual_score !== null && r.manual_score !== undefined)
                ? '<div style="font-size:18px;font-weight:800;line-height:1;color:var(--amber,#f59e0b);">' + r.manual_score + ' pt' + (r.manual_score == 1 ? '' : 's') + '</div>'
                : '<div style="font-size:12px;font-weight:600;color:var(--amber,#f59e0b);">⏳ Pending</div>') +
            '</div>')
          : ('<div style="text-align:right;flex-shrink:0;min-width:80px;">' +
              '<div style="font-size:11px;color:var(--text3);margin-bottom:2px;">Best score</div>' +
              '<div style="font-size:18px;font-weight:800;line-height:1;color:' + (passed ? 'var(--green)' : 'var(--red,#ef4444)') + ';">' + scoreDisplay + '</div>' +
              (totalQuestions > 0
                ? '<div style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;margin-top:4px;display:inline-block;' +
                    (passed ? 'background:rgba(34,197,94,0.15);color:var(--green);' : 'background:rgba(239,68,68,0.15);color:var(--red,#ef4444);') + '">' +
                    (passed ? '✓ Passed' : '✗ Failed') + ' · ' + pct + '%' +
                  '</div>'
                : '') +
            '</div>')
        ) +
        '<div style="font-size:11px;color:var(--text3);margin-left:4px;flex-shrink:0;">👁</div>' +
      '</div>' +
    '</div>';
  }).join('');
}

var _srm_quizId  = null;
var _srm_rIdx    = null;

// Take selection modal
function handleStudentResultClick(quizId, studentId, entriesJsonStr) {
  var rIdxArr;
  try { rIdxArr = JSON.parse(entriesJsonStr); } catch(e) { rIdxArr = []; }

  if (rIdxArr.length === 1) {
    openStudentResultModal(quizId, rIdxArr[0]);
    return;
  }

  // Multiple takes — show selection modal
  var results = quizResultsMap[quizId] || [];
  var quiz    = (quizzesByLesson[lastOpenedLessonId] || []).find(function(q){ return q.id === quizId; });
  var totalQ  = quiz && quiz.questions ? quiz.questions.length : 0;
  var passing = quiz ? parseInt(quiz.passing_score) || 75 : 75;

  var r0      = results[rIdxArr[0]];
  var fn      = String(r0 ? r0.firstname : '');
  var ln      = String(r0 ? r0.lastname  : '');
  var fullName = escHtml((fn + ' ' + ln).trim() || 'Unknown');
  var initials = ((fn.charAt(0)||'')+(ln.charAt(0)||'')).toUpperCase()||'?';

  var listHtml = rIdxArr.map(function(rIdx, takeNum) {
    var r       = results[rIdx];
    var scoreNum = parseInt(String(r.score_raw || r.score || '0').replace(/[^0-9]/g,'')) || 0;
    var pct      = totalQ > 0 ? Math.round(scoreNum / totalQ * 100) : 0;
    var passed   = pct >= passing;
    var secs     = parseInt(r.time_used) || 0;
    var mm = Math.floor(secs/60), ss = secs % 60;
    var timeDisp = r.time_used !== null && r.time_used !== undefined ? mm+':'+(ss<10?'0':'')+ss : '—';
    return '<div onclick="openStudentResultModal('+quizId+','+rIdx+');closeTakeSelectModal()" style="cursor:pointer;display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:10px;border:1px solid '+(passed?'rgba(34,197,94,0.25)':'rgba(239,68,68,0.2)')+';background:var(--bg2);margin-bottom:8px;transition:background .15s;" onmouseover="this.style.background=\'rgba(99,102,241,0.06)\'" onmouseout="this.style.background=\'var(--bg2)\'">' +
      '<div style="width:30px;height:30px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,var(--accent,#6366f1),var(--purple,#8b5cf6));display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;">' + (takeNum+1) + '</div>' +
      '<div style="flex:1;min-width:0;">' +
        '<div style="font-size:13px;font-weight:600;color:var(--text);">Take ' + (takeNum+1) + '</div>' +
        '<div style="font-size:11px;color:var(--text3);">📅 ' + escHtml(r.submitted_at) + ' · ⏱ ' + timeDisp + '</div>' +
      '</div>' +
      '<div style="text-align:right;flex-shrink:0;">' +
        '<div style="font-size:17px;font-weight:800;color:'+(passed?'var(--green)':'var(--red,#ef4444)')+';">' + scoreNum + (totalQ?'/'+totalQ:'') + '</div>' +
        '<div style="font-size:10px;font-weight:600;padding:2px 7px;border-radius:8px;display:inline-block;margin-top:2px;'+(passed?'background:rgba(34,197,94,0.15);color:var(--green);':'background:rgba(239,68,68,0.12);color:var(--red,#ef4444);')+'">'+(passed?'✓ Passed':'✗ Failed')+' · '+pct+'%</div>' +
      '</div>' +
      '<div style="font-size:11px;color:var(--text3);">👁</div>' +
    '</div>';
  }).join('');

  var overlay = document.createElement('div');
  overlay.id = 'takeSelectOverlay';
  overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;display:flex;align-items:center;justify-content:center;';
  overlay.innerHTML =
    '<div style="background:var(--bg,#0f172a);border-radius:16px;width:90%;max-width:480px;max-height:80vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.5);">' +
      '<div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.08);flex-shrink:0;">' +
        '<div style="display:flex;align-items:center;gap:10px;">' +
          '<div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent,#6366f1),var(--purple,#8b5cf6));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;">' + escHtml(initials) + '</div>' +
          '<div>' +
            '<div style="font-size:14px;font-weight:700;color:var(--text);">' + fullName + '</div>' +
            '<div style="font-size:11px;color:var(--text3);">Select a take to view</div>' +
          '</div>' +
        '</div>' +
        '<button onclick="closeTakeSelectModal()" style="background:none;border:none;color:var(--text3);font-size:18px;cursor:pointer;padding:4px;line-height:1;">✕</button>' +
      '</div>' +
      '<div style="overflow-y:auto;flex:1;padding:14px 16px;">' + listHtml + '</div>' +
    '</div>';
  overlay.addEventListener('click', function(e){ if(e.target===overlay) closeTakeSelectModal(); });
  document.body.appendChild(overlay);
  document.body.style.overflow = 'hidden';
}

function closeTakeSelectModal() {
  var o = document.getElementById('takeSelectOverlay');
  if (o) o.remove();
  document.body.style.overflow = '';
}

function openStudentResultModal(quizId, rIdx) {
  var quizzes  = quizzesByLesson[lastOpenedLessonId] || [];
  var quiz     = quizzes.find(function(q){ return q.id === quizId; });
  var results  = quizResultsMap[quizId] || [];
  var r        = results[rIdx];
  if (!quiz || !r) return;

  _srm_quizId = quizId;
  _srm_rIdx   = rIdx;

  var fn       = String(r.firstname || '');
  var ln       = String(r.lastname  || '');
  var fullName = escHtml((fn + ' ' + ln).trim() || 'Unknown');
  var initials = ((fn.charAt(0) || '') + (ln.charAt(0) || '')).toUpperCase() || '?';

  var totalQ        = quiz.questions ? quiz.questions.length : 0;
  var passingScore  = parseInt(quiz.passing_score) || 75;
  var isEssayQuiz    = !!(quiz.questions && quiz.questions.length > 0 &&
                       quiz.questions.every(function(qq){ return qq.type === 'essay'; }));
  var essayHasScoreTop = (r.manual_score !== null && r.manual_score !== undefined);

  var rawScore, scoreNum, pct, passed;
  if (isEssayQuiz) {
    scoreNum = essayHasScoreTop ? parseInt(r.manual_score) || 0 : 0;
    pct      = totalQ > 0 ? Math.min(100, Math.round((scoreNum / totalQ) * 100)) : 0;
    passed   = essayHasScoreTop ? (pct >= passingScore) : false;
  } else {
    rawScore = String(r.score_raw || r.score || '0');
    scoreNum = parseInt(rawScore.replace(/[^0-9]/g, '')) || 0;
    pct      = totalQ > 0 ? Math.round((scoreNum / totalQ) * 100) : 0;
    passed   = pct >= passingScore;
  }

  var timeDisplay = '—';
  if (r.time_used !== null && r.time_used !== undefined && String(r.time_used) !== '') {
    var secs = parseInt(r.time_used) || 0;
    var mm   = Math.floor(secs / 60);
    var ss   = secs % 60;
    timeDisplay = mm + ':' + (ss < 10 ? '0' : '') + ss;
  }

  // Count takes for this student
  var allResults = quizResultsMap[quizId] || [];
  var studentTakes = allResults.filter(function(x){ return x.student_id === r.student_id; });
  var takeNumber = 0;
  allResults.forEach(function(x, i) { if(i === rIdx) takeNumber = studentTakes.indexOf(x) + 1; });

  document.getElementById('srm-title').innerHTML =
    '<div style="display:flex;align-items:center;gap:10px;">' +
      '<div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;background:linear-gradient(135deg,var(--accent,#6366f1),var(--purple,#8b5cf6));flex-shrink:0;">' + escHtml(initials) + '</div>' +
      '<div>' +
        '<div style="font-size:14px;font-weight:700;">📋 ' + fullName + '</div>' +
        (studentTakes.length > 1 ? '<div style="font-size:11px;color:var(--text3);">Take ' + takeNumber + ' of ' + studentTakes.length + '</div>' : '') +
      '</div>' +
    '</div>';

  var passedBadge;
  if (isEssayQuiz && !essayHasScoreTop) {
    passedBadge = '<span style="padding:2px 10px;border-radius:10px;background:rgba(245,158,11,0.15);color:var(--amber,#f59e0b);font-size:11px;font-weight:600;">⏳ Pending</span>';
  } else {
    passedBadge = passed
      ? '<span style="padding:2px 10px;border-radius:10px;background:rgba(34,197,94,0.15);color:var(--green);font-size:11px;font-weight:600;">✓ Passed</span>'
      : '<span style="padding:2px 10px;border-radius:10px;background:rgba(239,68,68,0.15);color:var(--red,#ef4444);font-size:11px;font-weight:600;">✗ Failed</span>';
  }

  var scoreColor = (isEssayQuiz && !essayHasScoreTop) ? 'var(--amber,#f59e0b)' : (passed ? 'var(--green)' : 'var(--red,#ef4444)');
  var scoreDisplayTop;
  if (isEssayQuiz) {
    scoreDisplayTop = (!essayHasScoreTop) ? '—' : (scoreNum + '<span style="font-size:16px;font-weight:500;opacity:0.7;"> pt' + (scoreNum == 1 ? '' : 's') + '</span>');
  } else {
    scoreDisplayTop = scoreNum + (totalQ > 0 ? '<span style="font-size:16px;font-weight:500;opacity:0.7;">/' + totalQ + '</span>' : '');
  }

  document.getElementById('srm-summary').innerHTML =
    '<div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;">' +
      '<div style="font-size:28px;font-weight:900;color:' + scoreColor + ';line-height:1;">' +
        scoreDisplayTop +
      '</div>' +
      '<div>' + passedBadge + '<div style="font-size:11px;color:var(--text3);margin-top:4px;">' + ((isEssayQuiz && !essayHasScoreTop) ? 'Not yet scored' : (pct + '% · Passing: ' + passingScore + '%')) + '</div></div>' +
      '<div style="margin-left:auto;text-align:right;">' +
        '<div style="font-size:11px;color:var(--text3);">📅 ' + escHtml(r.submitted_at) + '</div>' +
        '<div style="font-size:11px;color:var(--text3);margin-top:2px;">⏱ Time used: <b>' + timeDisplay + '</b></div>' +
      '</div>' +
    '</div>';

  var studentAnswers = {};
  try {
    if (r.answers && typeof r.answers === 'string' && r.answers !== 'null') {
      studentAnswers = JSON.parse(r.answers);
    } else if (r.answers && typeof r.answers === 'object') {
      studentAnswers = r.answers;
    }
  } catch(e) { studentAnswers = {}; }

  var qEl = document.getElementById('srm-questions');
  var questions = quiz.questions || [];
  var typeLabels = { multiple_choice:'Multiple Choice', fill_in_the_blank:'Fill in the Blank', fill_blank:'Fill in the Blank', identification:'Identification', essay:'Essay' };
  var letters = ['a','b','c','d'];

  if (questions.length === 0) {
    qEl.innerHTML = '<div style="text-align:center;padding:24px;color:var(--text3);font-size:13px;">No questions in this quiz.</div>';
  } else {
    qEl.innerHTML = questions.map(function(q, qIdx) {
      var typeLabel = typeLabels[q.type] || q.type;
      var body = '';
      if (q.type === 'multiple_choice') {
        var correctKey = (q.correct || '').toString().toLowerCase();
        var opts = q.options || {};
        body += '<div style="display:flex;flex-direction:column;gap:5px;margin-top:8px;">';
        letters.forEach(function(letter) {
          var choiceText = opts[letter];
          if (!choiceText && choiceText !== 0) return;
          var isCorrect = letter === correctKey;
          body += '<div style="display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:7px;' +
            (isCorrect ? 'background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);' : 'background:var(--bg3,rgba(255,255,255,.03));border:1px solid var(--border,rgba(255,255,255,.06));') + '">' +
            '<span style="font-size:11px;font-weight:700;min-width:16px;text-transform:uppercase;color:' + (isCorrect ? 'var(--green)' : 'var(--text3)') + ';">' + letter + '</span>' +
            '<span style="font-size:12px;color:var(--text);flex:1;">' + escHtml(String(choiceText)) + '</span>' +
            (isCorrect ? '<span style="font-size:10px;color:var(--green);font-weight:700;">✓ Correct</span>' : '') +
            '</div>';
        });
        var stuMcAns = (studentAnswers[String(qIdx)] || '').toLowerCase().trim();
        if (stuMcAns) {
          var stuLetter = '';
          ['a','b','c','d'].forEach(function(lt) {
            if (opts[lt] && opts[lt].toLowerCase().trim() === stuMcAns) stuLetter = lt;
          });
          if (!stuLetter) stuLetter = stuMcAns;
          body += '<div style="margin-top:6px;font-size:11px;color:var(--text3);">✏️ Student chose: <strong style="color:' +
            (stuLetter === correctKey ? 'var(--green)' : 'var(--red)') + ';">' +
            stuLetter.toUpperCase() + (opts[stuLetter] ? ' — ' + escHtml(opts[stuLetter]) : '') +
            (stuLetter === correctKey ? ' ✓' : ' ✗') + '</strong></div>';
        }
        body += '</div>';
      } else if (q.type === 'essay') {
        var stuEssay = studentAnswers[String(qIdx)] || '';
        body += '<div style="margin-top:6px;padding:8px 10px;border-radius:7px;background:rgba(99,102,241,0.05);border:1px solid rgba(99,102,241,0.12);font-size:11px;color:var(--text3);">📝 Essay — manually graded by teacher.</div>';
        if (stuEssay) {
          body += '<div style="margin-top:4px;"><div style="font-size:10px;font-weight:600;color:var(--text3);margin-bottom:3px;">✏️ Student Answer</div>' +
            '<div style="font-size:12px;padding:7px 10px;border-radius:7px;background:rgba(255,255,255,0.04);border:1px solid var(--border);color:var(--text);">' + escHtml(stuEssay) + '</div></div>';
        }
        var essayScoreId = 'essayScore_' + r.result_id + '_' + qIdx;
        var hasScore = (r.manual_score !== null && r.manual_score !== undefined);
        body += '<div style="margin-top:6px;padding:8px 10px;border-radius:7px;background:rgba(245,158,11,0.07);border:1px solid rgba(245,158,11,0.2);">' +
          '<div style="font-size:10px;font-weight:700;color:var(--amber,#f59e0b);margin-bottom:4px;">⭐ Points for this answer</div>' +
          (hasScore
            ? '<div style="font-size:11px;color:var(--text3);">Already included in the total score for this submission.</div>'
            : ('<div style="font-size:11px;color:var(--text3);margin-bottom:6px;">Enter the students score for this answer.</div>' +
               '<input type="number" class="essayPointInput" id="' + essayScoreId + '" min="0" step="1" value="" placeholder="Points" style="width:100px;padding:5px 8px;font-size:12px;border-radius:7px;border:1px solid var(--border,rgba(255,255,255,.1));background:var(--bg2);color:var(--text);">')) +
        '</div>';
      } else {
        var stuAns = studentAnswers[String(qIdx)] || '—';
        var isCorrectAns = stuAns !== '—' && stuAns.trim().toLowerCase() === (q.correct || '').trim().toLowerCase();
        body += '<div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap;">' +
          '<div style="flex:1;min-width:120px;"><div style="font-size:10px;font-weight:600;color:var(--text3);margin-bottom:3px;">✏️ Student Answer</div>' +
          '<div style="font-size:12px;padding:7px 10px;border-radius:7px;font-weight:600;' +
            (stuAns === '—' ? 'background:rgba(255,255,255,0.04);color:var(--text3);border:1px solid var(--border);' :
             isCorrectAns ? 'background:rgba(34,197,94,0.08);color:var(--green);border:1px solid rgba(34,197,94,0.25);' :
                            'background:rgba(239,68,68,0.08);color:var(--red);border:1px solid rgba(239,68,68,0.25);') + '">' +
            escHtml(stuAns) + (stuAns !== '—' ? (isCorrectAns ? ' ✓' : ' ✗') : '') + '</div></div>' +
          '<div style="flex:1;min-width:120px;"><div style="font-size:10px;font-weight:600;color:var(--text3);margin-bottom:3px;">✅ Correct Answer</div>' +
          '<div style="font-size:12px;padding:7px 10px;border-radius:7px;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.25);font-weight:600;color:var(--green);">' +
            escHtml(q.correct || '—') + '</div></div></div>';
        if (!isCorrectAns && stuAns !== '—') {
          body += '<div style="margin-top:6px;padding:8px 10px;border-radius:7px;background:rgba(245,158,11,0.07);border:1px solid rgba(245,158,11,0.2);">' +
            '<div style="font-size:10px;font-weight:700;color:var(--amber,#f59e0b);margin-bottom:4px;">⭐ Teacher Override (Identification)</div>' +
            '<div style="font-size:11px;color:var(--text3);margin-bottom:6px;">If the student\'s answer is acceptable despite not matching exactly, grant the point.</div>' +
            '<div style="display:flex;gap:8px;align-items:center;">' +
              '<button onclick="saveManualScore(' + r.result_id + ',' + quizId + ',1,this)" style="padding:5px 14px;border-radius:7px;border:none;background:rgba(34,197,94,0.15);color:var(--green);font-weight:700;font-size:12px;cursor:pointer;">✓ Accept (+1)</button>' +
              (r.manual_score !== null && r.manual_score !== undefined
                ? '<span style="font-size:11px;color:var(--text3);">Overridden: <b style="color:var(--green);">+' + r.manual_score + ' pt</b></span>'
                : '') +
            '</div>' +
          '</div>';
        }
      }
      return '<div style="padding:12px 14px;border-radius:10px;background:var(--bg2,rgba(255,255,255,.03));border:1px solid var(--border,rgba(255,255,255,.08));">' +
        '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">' +
          '<div style="font-size:12px;font-weight:700;color:var(--text);">Q' + (qIdx + 1) + '</div>' +
          '<span style="font-size:10px;padding:2px 7px;border-radius:5px;background:rgba(99,102,241,0.12);color:var(--accent,#6366f1);">' + typeLabel + '</span>' +
        '</div>' +
        '<div style="font-size:13px;color:var(--text);line-height:1.5;">' + escHtml(q.text || '') + '</div>' +
        body + '</div>';
    }).join('');

    var essayQuestionsList = questions.filter(function(qq){ return qq.type === 'essay'; });
    var essayHasScore = (r.manual_score !== null && r.manual_score !== undefined);
    if (essayQuestionsList.length > 0) {
      if (essayHasScore) {
        qEl.innerHTML += '<div style="margin-top:10px;padding:12px;border-radius:10px;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.25);font-size:12px;color:var(--text3);">Total score given: <b style="color:var(--green);font-size:14px;">' + r.manual_score + ' pt' + (r.manual_score == 1 ? '' : 's') + '</b></div>';
      } else {
        qEl.innerHTML += '<div style="margin-top:10px;padding:12px;border-radius:10px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);display:flex;align-items:center;gap:10px;flex-wrap:wrap;">' +
          '<div style="font-size:12px;color:var(--text3);flex:1;min-width:150px;">Enter the points for each essay item above, then click Save to record the total score.</div>' +
          '<button onclick="saveAllEssayScores(' + r.result_id + ',' + quizId + ',this)" style="padding:7px 16px;border-radius:8px;border:none;background:rgba(34,197,94,0.15);color:var(--green);font-weight:700;font-size:12px;cursor:pointer;">💾 Save Score</button>' +
        '</div>';
      }
    }
  }

  document.getElementById('viewQuizModal').classList.remove('open');
  document.getElementById('studentResultModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeStudentResultModal() {
  document.getElementById('studentResultModal').classList.remove('open');
  document.getElementById('viewQuizModal').classList.add('open');
}

function toggleResultDetail(id) {
  var el = document.getElementById(id);
  if (!el) return;
  var isOpen = el.style.display !== 'none';
  el.style.display = isOpen ? 'none' : 'block';
  // flip arrow
  var arrow = el.previousElementSibling ? el.previousElementSibling.querySelector('span:last-child') : null;
  if (arrow) arrow.textContent = isOpen ? '▼' : '▲';
}

function closeViewQuizModal() {
  document.getElementById('viewQuizModal').classList.remove('open');
  document.getElementById('lessonModal').classList.add('open');
}

// ── Subject Modal ─────────────────────────────────────────────────────────
function openSubjectModal(subjectId) {
  var s = subjectsMap[subjectId];
  if (!s) return;
  lastOpenedSubjectId = subjectId;

  var strandLabel = [s.track, s.strand].filter(Boolean).join(' · ');
  document.getElementById('subj-modal-title').innerHTML =
    (strandLabel ? '<span style="font-size:11px;font-weight:400;color:var(--text3);display:block;margin-bottom:2px;">🏷️ ' + escHtml(strandLabel) + '</span>' : '') +
    '📚 ' + escHtml(s.subject);

  var infoHtml = '';
  if (s.subject_description && s.subject_description.trim() !== '') {
    infoHtml += '<div style="font-size:12px;color:var(--text3);line-height:1.6;margin-bottom:8px;padding:8px 10px;border-radius:8px;background:rgba(99,102,241,0.05);border:1px solid rgba(99,102,241,0.1);text-align:justify;text-indent:1.5em;">' + escHtml(s.subject_description) + '</div>';
  }
  infoHtml += '<div style="border-top:1px solid var(--border,rgba(255,255,255,.08));margin:6px 0 8px;"></div>';
  infoHtml += '<div style="display:flex;gap:16px;flex-wrap:wrap;">';
  infoHtml += '<div style="font-size:12px;color:var(--text2);">👥 <b>' + s.student_count + '</b> Students</div>';
  infoHtml += '<div style="font-size:12px;color:var(--text2);">📖 <b>' + s.lesson_count + '</b> Lessons</div>';
  infoHtml += '<div style="font-size:12px;color:var(--text2);">🕒 Updated <b>' + s.updated_at + '</b></div>';
  infoHtml += '</div>';
  document.getElementById('subj-modal-info').innerHTML = infoHtml;

  var lessons = lessonsBySubject[subjectId] || [];
  var lessonsEl = document.getElementById('subj-modal-lessons');
  if (lessons.length === 0) {
    lessonsEl.innerHTML = '<div style="text-align:center;padding:24px;color:var(--text3);font-size:13px;">No lessons added yet.</div>';
  } else {
    lessonsEl.innerHTML = lessons.map(function(l, idx) {
      var lessonNum = idx + 1;
      var statusBadge = l.status === 'published'
        ? '<span class="badge badge-active" style="font-size:10px;">Published</span>'
        : '<span class="badge" style="font-size:10px;background:var(--bg3,rgba(255,255,255,.06));color:var(--text3);">Draft</span>';
      return '<div class="notif-item" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:10px;background:var(--bg2,rgba(255,255,255,.03));border:1px solid var(--border,rgba(255,255,255,.07));cursor:pointer;" onclick="openLessonModal(' + l.id + ')">' +
        '<div style="min-width:0;flex:1;">' +
          '<div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px;">Lesson ' + lessonNum + ': ' + l.title + '</div>' +
          '<div style="font-size:11px;color:var(--text3);">📅 ' + l.created_at + ' · 📝 ' + l.quiz_count + ' quizzes</div>' +
        '</div>' +
        '<div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">' +
          statusBadge +
        '</div>' +
      '</div>';
    }).join('');
  }

  document.getElementById('subjectModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeSubjectModal() {
  document.getElementById('subjectModal').classList.remove('open');
  document.body.style.overflow = '';
}

function openEditLessonModal(lessonId) {
  var lessons = lessonsBySubject[lastOpenedSubjectId] || [];
  var lesson = lessons.find(function(l){ return l.id === lessonId; });
  if (!lesson) return;
  document.getElementById('editLesson_id').value             = lessonId;
  document.getElementById('editLesson_title').value          = lesson.title;
  document.getElementById('editLesson_status').value         = lesson.status;
  document.getElementById('editLesson_manualContent').value  = lesson.manual_content || '';
  document.getElementById('editLesson_description').value    = lesson.description || '';
  document.getElementById('editLesson_resourceType').value   = (lesson.resource_type && lesson.resource_type !== 'manual') ? lesson.resource_type : 'none';
  toggleEditLessonFile();
  document.getElementById('subjectModal').classList.remove('open');
  document.getElementById('editLessonModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function toggleEditLessonFile() {
  var type = document.getElementById('editLesson_resourceType').value;
  document.getElementById('editLesson_fileGroup').style.display = ['video','pdf','ppt'].includes(type) ? 'block' : 'none';
  var accepts = {video:'video/mp4,video/webm,video/avi',pdf:'application/pdf',ppt:'application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation'};
  document.getElementById('editLesson_file').accept = accepts[type] || '';
}

function closeEditLessonModal() {
  document.getElementById('editLessonModal').classList.remove('open');
  if (lastOpenedSubjectId !== null) document.getElementById('subjectModal').classList.add('open');
  else document.body.style.overflow = '';
}

function confirmDeleteLesson(lessonId, lessonTitle) {
  document.getElementById('deleteLesson_id').value = lessonId;
  document.getElementById('deleteLesson_title').textContent = lessonTitle;
  document.getElementById('subjectModal').classList.remove('open');
  document.getElementById('lessonModal').classList.remove('open');
  document.getElementById('deleteLessonModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeDeleteLessonModal() {
  document.getElementById('deleteLessonModal').classList.remove('open');
  if (lastOpenedLessonId !== null) document.getElementById('lessonModal').classList.add('open');
  else if (lastOpenedSubjectId !== null) document.getElementById('subjectModal').classList.add('open');
  else document.body.style.overflow = '';
}

function openLessonModal(lessonId) {
  var lessons = lessonsBySubject[lastOpenedSubjectId] || [];
  var lesson = lessons.find(function(l){ return l.id === lessonId; });
  lastOpenedLessonId = lessonId;
  if (!lesson) return;

  document.getElementById('lesson-modal-title').textContent = '📖 ' + lesson.title;
  var resourceLabels = { none:'None', video:'🎥 Video Lecture', pdf:'📄 PDF Document', ppt:'📊 Presentation (PPT/PPTX)', manual:'✍️ Manual Lesson' };
  var infoHtml = '';

  if (lesson.description && lesson.description.trim() !== '') {
    infoHtml += '<div style="font-size:11px;font-weight:700;color:var(--text2);margin-bottom:4px;">📝 Description</div>';
    infoHtml += '<div style="margin-bottom:6px;">' + escHtml(lesson.description) + '</div>';
  }
    // Manual lesson content muna sa taas
  if (lesson.manual_content && lesson.manual_content.trim() !== '') {
    infoHtml += '<div style="font-size:11px;font-weight:700;color:var(--text2);margin-bottom:4px;">📘 Lesson Content</div>';
    infoHtml += '<div style="margin-bottom:8px;padding:8px 10px;border-radius:8px;background:rgba(99,102,241,0.05);border:1px solid rgba(99,102,241,0.1);font-size:12px;white-space:pre-wrap;">' + escHtml(lesson.manual_content) + '</div>';
  }

  if (lesson.resource_type === 'manual') {
    infoHtml += '<div>📂 <b>Resource Type:</b> ' + resourceLabels.manual + '</div>';
  } else if (lesson.resource_type === 'video' && lesson.resource_url) {
    infoHtml += '<div>📂 <b>Resource Type:</b> ' + resourceLabels.video + '</div>';
    infoHtml += '<video controls style="width:100%;max-height:260px;border-radius:8px;margin-top:6px;background:#000;"><source src="' + escHtml(lesson.resource_url) + '"></video>';
  } else if (lesson.resource_type === 'pdf' && lesson.resource_url) {
    infoHtml += '<div>📂 <b>Resource Type:</b> ' + resourceLabels.pdf + '</div>';
    infoHtml += '<a href="' + escHtml(lesson.resource_url) + '" target="_blank" rel="noopener" style="display:inline-block;margin-top:6px;font-size:12px;color:var(--accent,#6366f1);text-decoration:underline;">📄 ' + escHtml(lesson.video_filename || 'Open PDF File') + '</a>';
  } else if (lesson.resource_type === 'ppt' && lesson.resource_url) {
    infoHtml += '<div>📂 <b>Resource Type:</b> ' + resourceLabels.ppt + '</div>';
    infoHtml += '<a href="' + escHtml(lesson.resource_url) + '" target="_blank" rel="noopener" style="display:inline-block;margin-top:6px;font-size:12px;color:var(--accent,#6366f1);text-decoration:underline;">📊 ' + escHtml(lesson.video_filename || 'Open Presentation File') + '</a>';
  } else if (lesson.resource_type !== 'none') {
    infoHtml += '<div>📂 <b>Resource Type:</b> ' + (resourceLabels[lesson.resource_type] || lesson.resource_type) + ' <span style="color:var(--text3);">(no file uploaded)</span></div>';
  } else {
    infoHtml += '<div>📂 <b>Resource Type:</b> ' + resourceLabels.none + '</div>';
  }

  infoHtml += '<div>📝 <b>Total Quizzes:</b> ' + lesson.quiz_count + '</div>';
  infoHtml += '<div>📊 <b>Status:</b> ' + (lesson.status === 'published' ? 'Published' : 'Draft') + '</div>';
  if (lesson.duration) {
    infoHtml += '<div>⏱ <b>Duration:</b> ' + lesson.duration + ' min(s)</div>';
  }
  infoHtml += '<div>📅 <b>Date Added:</b> ' + lesson.created_at + '</div>';
  
  document.getElementById('lesson-modal-info').innerHTML = infoHtml;

  var safeTitle = lesson.title.replace(/\\/g,'\\\\').replace(/'/g,"\\'");
  document.getElementById('lesson-quiz-add-btn').innerHTML =
    '<button class="btn btn-primary btn-sm" onclick="openAddQuizModal(' + lessonId + ', \'' + safeTitle + '\')">+ Add Quiz</button>';

  document.getElementById('lesson-modal-action-btns').innerHTML =
    '<button onclick="openEditLessonModal(' + lessonId + ')" style="font-size:11px;padding:5px 10px;border-radius:6px;border:none;background:rgba(99,102,241,0.12);color:var(--accent,#6366f1);cursor:pointer;font-weight:600;">✏️ Edit</button>' +
    '<button onclick="confirmDeleteLesson(' + lessonId + ', \'' + lesson.title.replace(/'/g,"\\'") + '\')" style="font-size:11px;padding:5px 10px;border-radius:6px;border:none;background:rgba(239,68,68,0.12);color:#ef4444;cursor:pointer;font-weight:600;">🗑 Delete</button>';

  var quizzes = quizzesByLesson[lessonId] || [];
  var quizzesEl = document.getElementById('lesson-modal-quizzes');
  if (quizzes.length === 0) {
    quizzesEl.innerHTML = '<div style="text-align:center;padding:24px;color:var(--text3);font-size:13px;">No quizzes added yet.</div>';
  } else {
    quizzesEl.innerHTML = quizzes.map(function(q, idx) {
      var safeT = q.title.replace(/\\/g,'\\\\').replace(/'/g,"\\'");
      var statusBadge = q.status === 'published'
        ? '<span style="font-size:10px;padding:2px 7px;border-radius:5px;background:rgba(34,197,94,0.15);color:var(--green);">Published</span>'
        : '<span style="font-size:10px;padding:2px 7px;border-radius:5px;background:rgba(255,255,255,0.06);color:var(--text3);">Draft</span>';
      var overdueBadge = q.is_overdue
        ? '<span style="font-size:10px;padding:2px 7px;border-radius:5px;background:rgba(239,68,68,0.15);color:#ef4444;margin-left:4px;">⚠ Overdue</span>'
        : '';
      return '<div onclick="openViewQuizModal(' + q.id + ')" style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:10px;background:var(--bg2,rgba(255,255,255,.03));border:1px solid ' + (q.is_overdue ? 'rgba(239,68,68,0.25)' : 'var(--border,rgba(255,255,255,.07))') + ';">' +
        '<div style="min-width:0;flex:1;">' +
          '<div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:3px;display:flex;align-items:center;flex-wrap:wrap;gap:4px;">Quiz ' + (idx+1) + ': ' + q.title + statusBadge + overdueBadge + '</div>' +
          '<div style="font-size:11px;color:var(--text3);">📅 Due: <span style="' + (q.is_overdue ? 'color:#ef4444;font-weight:600;' : '') + '">' + q.due_date + '</span> · 🎯 Pass: ' + q.passing_score + '% · 📬 ' + q.result_count + ' submission(s)</div>' +
        '</div>' +
      '</div>';
    }).join('');
  }

  document.getElementById('subjectModal').classList.remove('open');
  document.getElementById('lessonModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeLessonModal() {
  document.getElementById('lessonModal').classList.remove('open');
  document.body.style.overflow = '';
}

function backToSubjectModal() {
  document.getElementById('lessonModal').classList.remove('open');
  if (lastOpenedSubjectId !== null) document.getElementById('subjectModal').classList.add('open');
  else document.body.style.overflow = '';
}

function confirmDeleteQuiz(quizId, quizTitle) {
  document.getElementById('deleteQuiz_id').value = quizId;
  document.getElementById('deleteQuiz_title').textContent = quizTitle;
  document.getElementById('viewQuizModal').classList.remove('open');
  document.getElementById('deleteQuizModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeDeleteQuizModal() {
  document.getElementById('deleteQuizModal').classList.remove('open');
  document.getElementById('lessonModal').classList.add('open');
}

function openAddLessonModal(subjectId) {
  var s = subjectsMap[subjectId];
  if (!s) return;
  document.getElementById('addLesson_subjectId').value   = subjectId;
  document.getElementById('addLesson_subjectName').value = s.subject;
  document.getElementById('addLesson_title').value = '';
  document.getElementById('addLesson_manualContent').value = '';
  document.getElementById('addLessonModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeAddLessonModal() {
  document.getElementById('addLessonModal').classList.remove('open');
  document.getElementById('subjectModal').classList.add('open');
}

function toggleAddLessonFile() {
  var type = document.getElementById('addLesson_resourceType').value;
  document.getElementById('addLesson_fileGroup').style.display = ['video','pdf','ppt'].includes(type) ? 'block' : 'none';
  var accepts = {video:'video/mp4,video/webm,video/avi',pdf:'application/pdf',ppt:'application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation'};
  document.getElementById('addLesson_file').accept = accepts[type] || '';
}

function escHtml(str) {
  return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Overlay click-to-close ────────────────────────────────────────────────
document.getElementById('subjectModal').addEventListener('click',    function(e){ if(e.target===this) closeSubjectModal(); });
document.getElementById('lessonModal').addEventListener('click',     function(e){ if(e.target===this) closeLessonModal(); });
document.getElementById('addLessonModal').addEventListener('click',  function(e){ if(e.target===this) closeAddLessonModal(); });
document.getElementById('addQuizModal').addEventListener('click',    function(e){ if(e.target===this) closeAddQuizModal(); });
document.getElementById('editQuizModal').addEventListener('click',   function(e){ if(e.target===this) closeEditQuizModal(); });
document.getElementById('editLessonModal').addEventListener('click', function(e){ if(e.target===this) closeEditLessonModal(); });
document.getElementById('deleteLessonModal').addEventListener('click',function(e){ if(e.target===this) closeDeleteLessonModal(); });
document.getElementById('deleteQuizModal').addEventListener('click', function(e){ if(e.target===this) closeDeleteQuizModal(); });
document.getElementById('viewQuizModal').addEventListener('click', function(e){ if(e.target===this) closeViewQuizModal(); });
document.getElementById('studentResultModal').addEventListener('click', function(e){ if(e.target===this) closeStudentResultModal(); });
document.getElementById('logoutModal').addEventListener('click',     function(e){ if(e.target===this) closeLogoutModal(); });

// ── Sidebar ───────────────────────────────────────────────────────────────
var sidebar = document.getElementById('sidebar');
var overlay = document.getElementById('sidebar-overlay');
var menuBtn = document.getElementById('menuBtn');
function openSidebar(){ sidebar.classList.add('open'); overlay.classList.add('open'); document.body.style.overflow='hidden'; }
function closeSidebar(){ sidebar.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow=''; }
menuBtn.addEventListener('click', function(){ sidebar.classList.contains('open') ? closeSidebar() : openSidebar(); });
overlay.addEventListener('click', closeSidebar);
document.querySelectorAll('.nav-item').forEach(function(item){ item.addEventListener('click', function(){ if(window.innerWidth<=768) closeSidebar(); }); });
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeSidebar(); });
var resizeTimer;
window.addEventListener('resize', function(){ clearTimeout(resizeTimer); resizeTimer=setTimeout(function(){ if(window.innerWidth>768) closeSidebar(); },150); });

// ── Theme ─────────────────────────────────────────────────────────────────
var themeToggle = document.getElementById('themeToggle');
var themeIcon   = document.getElementById('theme-icon');
var themeText   = document.getElementById('theme-text');
function applyTheme(isLight){ if(isLight){ document.body.classList.add('light'); if(themeIcon) themeIcon.textContent='☀️'; if(themeText) themeText.textContent='Light Mode'; } else { document.body.classList.remove('light'); if(themeIcon) themeIcon.textContent='🌙'; if(themeText) themeText.textContent='Dark Mode'; } }
if(localStorage.getItem('excelearn-theme')==='light') applyTheme(true);
if(themeToggle) themeToggle.addEventListener('click', function(){ var isLight=document.body.classList.contains('light'); applyTheme(!isLight); localStorage.setItem('excelearn-theme',!isLight?'light':'dark'); });

// ── Flash auto-dismiss ────────────────────────────────────────────────────
setTimeout(function(){
  var f = document.getElementById('flashMsg');
  if(f){ f.style.opacity='0'; f.style.transform='translateY(-8px)'; f.style.transition='all .3s ease'; setTimeout(function(){ f.remove(); },300); }
},5000);

// ── Logout ────────────────────────────────────────────────────────────────
function openLogoutModal(){ document.getElementById('logoutModal').classList.add('open'); document.body.style.overflow='hidden'; }
function closeLogoutModal(){ document.getElementById('logoutModal').classList.remove('open'); document.body.style.overflow=''; }
function confirmLogout(){ window.location.href='../logout.php'; }

// ── Notifications ─────────────────────────────────────────────────────────
function openNotifModal(){ document.getElementById('notifModal').classList.add('open'); document.body.style.overflow='hidden'; }
function closeNotifModal(){ document.getElementById('notifModal').classList.remove('open'); document.body.style.overflow=''; }
document.getElementById('notifModal').addEventListener('click', function(e){ if(e.target===this) closeNotifModal(); });

function markOneRead(id, el) {
  var url = el.dataset.url || '#';
  fetch('notif_mark_read.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'notification_id='+id });
  if (!el.dataset.read) {
    el.style.background='var(--bg2,rgba(255,255,255,.03))'; el.style.border='1px solid var(--border,rgba(255,255,255,.07))';
    var dot=el.querySelector('span[style*="border-radius:50%"]'); var title=el.querySelector('div[style*="font-weight"]');
    if(dot) dot.remove(); if(title) title.style.fontWeight='500';
    el.dataset.read='1'; updateBadgeCount(-1);
  }
  if(url && url!=='#') setTimeout(function(){ window.location.href=url; },150);
}
function markAllRead() {
  fetch('notif_mark_read.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'mark_all=1' });
  document.querySelectorAll('.notif-item').forEach(function(el){
    el.style.background='var(--bg2,rgba(255,255,255,.03))'; el.style.border='1px solid var(--border,rgba(255,255,255,.07))';
    var dot=el.querySelector('span[style*="border-radius:50%"]'); var title=el.querySelector('div[style*="font-weight"]');
    if(dot) dot.remove(); if(title) title.style.fontWeight='500'; el.dataset.read='1';
  });
  updateBadgeCount(0); document.getElementById('notif-unread-count').textContent='';
}
function updateBadgeCount(delta) {
  var badge=document.getElementById('notif-badge'); if(!badge) return;
  var current=parseInt(badge.textContent)||0; var next=delta===0?0:Math.max(0,current+delta);
  if(next<=0){ badge.style.display='none'; } else { badge.textContent=next; badge.style.display='flex'; }
}
function deleteNotif(id, btn, e) {
  e.stopPropagation();
  var item=btn.closest('.notif-item');
  fetch('notif_delete.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'notification_id='+id });
  var wasUnread=!item.dataset.read && item.style.border.includes('99,102,241');
  item.style.transition='opacity .25s, transform .25s'; item.style.opacity='0'; item.style.transform='translateX(30px)';
  setTimeout(function(){
    item.remove();
    if(document.querySelectorAll('.notif-item').length===0){
      document.querySelector('#notifModal .modal-body').innerHTML='<div style="text-align:center;padding:40px 16px;color:var(--text3);"><div style="font-size:36px;margin-bottom:8px;">🔕</div><div style="font-size:13px;">No notifications yet.</div></div>';
    }
  },250);
  if(wasUnread) updateBadgeCount(-1);
}

function addEnumItem(containerId, prefix) {
  var container = document.getElementById(containerId);
  if (!container) return;
  var items = container.querySelectorAll('input[type="text"]');
  var newIdx = items.length + 1;
  var div = document.createElement('div');
  div.style.cssText = 'display:flex;gap:6px;align-items:center;margin-bottom:4px;';
  div.innerHTML = '<span style="font-size:11px;color:var(--text3);min-width:18px;">' + newIdx + '.</span>'
    + '<input type="text" name="' + prefix + '[enum_answers][]" placeholder="Item ' + newIdx + '" style="flex:1;padding:6px 8px;font-size:12px;border-radius:6px;border:1px solid var(--border,rgba(255,255,255,.1));background:var(--bg2);color:var(--text);">'
    + '<button type="button" onclick="removeEnumItem(this)" style="border:none;background:rgba(239,68,68,0.12);color:#ef4444;border-radius:5px;padding:3px 7px;font-size:11px;cursor:pointer;">✕</button>';
  container.appendChild(div);
}

function removeEnumItem(btn) {
  var row = btn.parentElement;
  var container = row.parentElement;
  if (container.querySelectorAll('input[type="text"]').length <= 1) return;
  row.remove();
  // re-number
  container.querySelectorAll('span').forEach(function(sp, i){ sp.textContent = (i+1) + '.'; });
}

// ── Reopen Subject Modal after lesson edit/delete redirect ────────────────
(function(){
  var params = new URLSearchParams(window.location.search);
  var openSubject = params.get('open_subject');
  var openLesson  = params.get('open_lesson');
  var openQuiz    = params.get('open_quiz');
  if (openSubject) {
    openSubjectModal(parseInt(openSubject));
    if (openLesson) {
      openLessonModal(parseInt(openLesson));
      if (openQuiz) {
        openViewQuizModal(parseInt(openQuiz));
      }
    }
    params.delete('open_subject');
    params.delete('open_lesson');
    params.delete('open_quiz');
    var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState({}, '', newUrl);
  }
})();

function saveAllEssayScores(resultId, quizId, btn) {
  var inputs = document.querySelectorAll('#srm-questions .essayPointInput');
  var total = 0;
  var hasInvalid = false;
  inputs.forEach(function(inp){
    var v = parseInt(inp.value, 10);
    if (isNaN(v) || v < 0) { hasInvalid = true; return; }
    total += v;
  });
  if (hasInvalid) {
    alert('Please enter a valid score (0 or higher) for every essay question before saving.');
    return;
  }
  saveManualScore(resultId, quizId, total, btn);
}

function saveManualScore(resultId, quizId, points, btn) {
  points = parseInt(points, 10);
  if (isNaN(points) || points < 0) {
    alert('Please enter a valid score (0 or higher).');
    return;
  }
  var originalText = btn.textContent;
  btn.disabled = true;
  btn.textContent = '⏳ Saving...';
  fetch('teacher_courses.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'save_manual_score=1&result_id=' + resultId + '&quiz_id=' + quizId + '&manual_score=' + points
  })
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(data.success){
      btn.textContent = '✓ Saved!';
      btn.style.background = 'rgba(34,197,94,0.25)';
      // Update local data
      var results = quizResultsMap[quizId] || [];
      results.forEach(function(res){ if(res.result_id === resultId) res.manual_score = points; });
    } else {
      btn.disabled = false;
      btn.textContent = originalText;
      alert(data.message || 'Failed to save score.');
    }
  })
  .catch(function(){
    btn.disabled = false;
    btn.textContent = originalText;
  });
}
</script>
</body>
</html>