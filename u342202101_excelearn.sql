-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 04, 2026 at 07:55 AM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u342202101_excelearn`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `students`
-- (See below for the actual view)
--
CREATE TABLE `students` (
`student_id` int(11)
,`student_number` varchar(50)
,`first_name` varchar(255)
,`last_name` varchar(255)
,`section_id` int(11)
,`grade_level` varchar(255)
,`section` varchar(255)
,`guardian_name` varchar(255)
,`guardian_phone` varchar(255)
,`guardian_email` varchar(255)
,`qr_code` varchar(50)
,`lrn` varchar(255)
,`status` enum('active','inactive')
);

-- --------------------------------------------------------

--
-- Table structure for table `tblachievements`
--

CREATE TABLE `tblachievements` (
  `id` int(11) NOT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblactivities`
--

CREATE TABLE `tblactivities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_text` text DEFAULT NULL,
  `activity_time` datetime DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblannouncements`
--

CREATE TABLE `tblannouncements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'system',
  `category` enum('for_teacher','for_student','both','') NOT NULL DEFAULT 'both',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblannouncements`
--

INSERT INTO `tblannouncements` (`id`, `user_id`, `title`, `message`, `type`, `category`, `created_at`) VALUES
(18, 5, 'Announcement for Brigada Eskwela', 'All teachers and student are required to attend for brigada eskwela 2026', 'important', 'both', '2026-06-07 06:39:54');

-- --------------------------------------------------------

--
-- Table structure for table `tblcertificates`
--

CREATE TABLE `tblcertificates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `certificate_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcourse_enrollments`
--

CREATE TABLE `tblcourse_enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `teacher_course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrolled_at` datetime DEFAULT current_timestamp(),
  `status` enum('active','dropped') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblenrollments`
--

CREATE TABLE `tblenrollments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblgrade_levels`
--

CREATE TABLE `tblgrade_levels` (
  `grade_id` int(11) NOT NULL,
  `grade_level` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblgrade_levels`
--

INSERT INTO `tblgrade_levels` (`grade_id`, `grade_level`, `updated_at`, `created_at`) VALUES
(1, 'Grade 11', '2026-04-18 01:03:39', '2026-04-18 01:03:39'),
(2, 'Grade 12', '2026-04-18 17:13:56', '2026-04-18 17:13:56');

-- --------------------------------------------------------

--
-- Table structure for table `tbllesson_completions`
--

CREATE TABLE `tbllesson_completions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `lesson_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbllesson_progress`
--

CREATE TABLE `tbllesson_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `status` enum('done','in_progress','locked') DEFAULT 'in_progress',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblnotifications`
--

CREATE TABLE `tblnotifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(100) NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblnotifications`
--

INSERT INTO `tblnotifications` (`notification_id`, `user_id`, `title`, `message`, `type`, `status`, `created_at`) VALUES
(91, 70, 'New Quiz Available', 'A new quiz \"N/A\" has been posted for Business 1 (Basic Accounting) (Grade 11 — ABM (ACADEMIC TRACK)) by Jhamel Habla. Due: Jun 13, 2026 12:59 PM.', 'new_quiz', 'read', '2026-06-12 04:59:45');

-- --------------------------------------------------------

--
-- Table structure for table `tblperformance_tasks`
--

CREATE TABLE `tblperformance_tasks` (
  `task_id` int(11) NOT NULL,
  `teacher_subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `max_score` int(11) NOT NULL DEFAULT 100,
  `due_date` datetime DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `tblperformance_tasks`
--

INSERT INTO `tblperformance_tasks` (`task_id`, `teacher_subject_id`, `title`, `description`, `max_score`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 124, 'Basic Electrical Wiring Installation Activity', 'Perform a basic wiring activity by creating a simple lighting circuit. Follow proper safety guidelines and demonstrate the correct use of electrical tools and materials.\r\n\r\nMaterials Needed:\r\n1. Electrical wires\r\n2. Switch\r\n3. Light bulb/socket\r\n4. Circuit breaker/fuse\r\n5. Electrical tools\r\n6. Multimeter', 100, NULL, 'published', '2026-06-21 14:40:00', '2026-06-21 14:40:00');

-- --------------------------------------------------------

--
-- Table structure for table `tblperformance_task_submissions`
--

CREATE TABLE `tblperformance_task_submissions` (
  `submission_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `video_path` varchar(255) NOT NULL,
  `video_filename` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('submitted','graded') NOT NULL DEFAULT 'submitted',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `graded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `tblperformance_task_submissions`
--

INSERT INTO `tblperformance_task_submissions` (`submission_id`, `task_id`, `student_id`, `video_path`, `video_filename`, `notes`, `score`, `feedback`, `status`, `submitted_at`, `graded_at`) VALUES
(1, 1, 70, 'uploads/performance_tasks/videos/pt_1_70_1782053230.mp4', 'c45a5b47-f9c5-4922-88b4-7d41de1fa5f2-1_all_4429.mp4', '', 100, '', 'graded', '2026-06-21 14:47:10', '2026-06-21 14:48:28');

-- --------------------------------------------------------

--
-- Table structure for table `tblquizzes`
--

CREATE TABLE `tblquizzes` (
  `quiz_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `questions` longtext DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) DEFAULT 30,
  `question_count` int(11) DEFAULT 0,
  `passing_score` int(11) DEFAULT 75,
  `due_date` datetime DEFAULT NULL,
  `status` enum('draft','published','scheduled') DEFAULT 'draft',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblquizzes`
--

INSERT INTO `tblquizzes` (`quiz_id`, `lesson_id`, `questions`, `description`, `duration`, `question_count`, `passing_score`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(32, 88, '[{\"type\":\"multiple_choice\",\"text\":\"What does EIM stand for?\",\"correct\":\"a\",\"options\":{\"a\":\"Electrical Installation and Maintenance\",\"b\":\"Electronic Installation Method\",\"c\":\"Electrical Industrial Machine\",\"d\":\"Equipment Installation Management\"}},{\"type\":\"multiple_choice\",\"text\":\"What is the main purpose of Personal Protective Equipment (PPE)?\",\"correct\":\"b\",\"options\":{\"a\":\"To make work faster\",\"b\":\"To protect workers from hazards\",\"c\":\"To replace electrical tools\",\"d\":\"To increase electricity\"}}]', 'EIM Quiz: Electrical Safety and Basic Electrical Concepts', 30, 2, 75, '2026-07-03 21:40:00', 'published', '2026-06-21 08:37:48', '2026-07-02 13:41:01'),
(33, 88, '[{\"type\":\"identification\",\"text\":\"The device used to measure voltage, current, and resistance.\",\"correct\":\"Multimeter\"},{\"type\":\"identification\",\"text\":\"The safety equipment worn to protect a worker from electrical hazards.\",\"correct\":\"Personal Protective Equipment (PPE)\"}]', 'EIM Quiz: Identification (Electrical Installation and Maintenance)', 30, 2, 75, NULL, 'published', '2026-06-21 08:52:18', '2026-06-21 08:52:18'),
(34, 88, '[{\"type\":\"fill_in_the_blank\",\"text\":\"The unit of electrical resistance is ____________.\",\"correct\":\"Ohm\"},{\"type\":\"fill_in_the_blank\",\"text\":\"The instrument used to measure electrical current, voltage, and resistance is ____________.\",\"correct\":\"Multimeter\"}]', 'Fill in the blank with the correct answer.', 30, 2, 75, '2026-06-22 16:54:00', 'published', '2026-06-21 08:54:22', '2026-06-21 08:54:22'),
(35, 88, '[{\"type\":\"essay\",\"text\":\"Explain the importance of electrical safety in Electrical Installation and Maintenance (EIM).\",\"correct\":\"\"},{\"type\":\"essay\",\"text\":\"Describe the role of a multimeter in electrical work.\",\"correct\":\"\"},{\"type\":\"essay\",\"text\":\"Why is proper wiring installation important in residential buildings?\",\"correct\":\"\"}]', 'Answer the following questions in complete sentences.', 30, 3, 75, NULL, 'published', '2026-06-21 09:01:08', '2026-06-21 09:01:08'),
(36, 89, '[{\"type\":\"multiple_choice\",\"text\":\"what is dog?\",\"correct\":\"a\",\"options\":{\"a\":\"animal\",\"b\":\"human\",\"c\":\"plants\",\"d\":\"things\"}}]', 'Answer the following questions in complete sentences.', 30, 1, 75, '2026-07-09 21:17:00', 'published', '2026-07-05 13:18:15', '2026-07-05 13:44:36'),
(37, 89, '[{\"type\":\"identification\",\"text\":\"sdgdsgsgdsgdsgs?\",\"correct\":\"sdgsd\"}]', 'sfdgsdsgsgdsgds', 30, 1, 75, NULL, 'published', '2026-07-05 13:45:14', '2026-07-05 13:45:14'),
(38, 89, '[{\"type\":\"identification\",\"text\":\"sdgsgds?\",\"correct\":\"dssd\"}]', 'sgdsgds', 30, 1, 75, NULL, 'published', '2026-07-05 13:45:59', '2026-07-05 13:45:59'),
(39, 89, '[{\"type\":\"fill_in_the_blank\",\"text\":\"sgdsgsgd?\",\"correct\":\"dgdsgds\"}]', 'dsgsds?', 30, 1, 75, NULL, 'published', '2026-07-05 13:46:20', '2026-07-05 13:46:20');

-- --------------------------------------------------------

--
-- Table structure for table `tblquizzes_answers`
--

CREATE TABLE `tblquizzes_answers` (
  `id` int(11) NOT NULL,
  `quizzes_question_id` int(11) NOT NULL,
  `answer` varchar(250) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblquiz_results`
--

CREATE TABLE `tblquiz_results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `quizzes_id` int(11) NOT NULL,
  `score` varchar(255) NOT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answers`)),
  `time_used` int(11) DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `manual_score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblquiz_results`
--

INSERT INTO `tblquiz_results` (`id`, `student_id`, `quizzes_id`, `score`, `answers`, `time_used`, `updated_at`, `created_at`, `manual_score`) VALUES
(78, 70, 32, '2', NULL, 12, '2026-06-21 09:10:18', '2026-06-21 09:10:18', NULL),
(79, 70, 33, '0', NULL, 4, '2026-06-21 09:21:58', '2026-06-21 09:21:58', NULL),
(80, 70, 34, '0', NULL, 2, '2026-06-21 09:22:04', '2026-06-21 09:22:04', NULL),
(81, 70, 33, '0', '[\"dsgs\",\"dsgsd\"]', 5, '2026-06-21 09:32:52', '2026-06-21 09:32:52', NULL),
(82, 70, 34, '0', '[\"sdgd\",\"sfsa\"]', 3, '2026-06-21 09:32:57', '2026-06-21 09:32:57', NULL),
(84, 70, 33, '2', '[\"Multimeter\",\"dsvs\"]', 14, '2026-06-21 09:50:57', '2026-06-21 09:50:57', 1),
(85, 70, 34, '2', '[\"ohm\",\"multimeter\"]', 14, '2026-06-21 09:52:25', '2026-06-21 09:52:25', NULL),
(87, 70, 32, '2', '[\"Electrical Installation and Maintenance\",\"To protect workers from hazards\"]', 7, '2026-06-21 12:46:24', '2026-06-21 12:46:24', NULL),
(91, 70, 32, '2', '[\"Electrical Installation and Maintenance\",\"To protect workers from hazards\"]', 7, '2026-06-21 13:10:55', '2026-06-21 13:10:55', NULL),
(94, 70, 35, '15', '[\"sagsg\",\"sfasfsaf\",\"asfafasa\"]', 6, '2026-07-05 10:53:18', '2026-07-05 10:53:18', 15);

-- --------------------------------------------------------

--
-- Table structure for table `tblsections`
--

CREATE TABLE `tblsections` (
  `section_id` int(11) NOT NULL,
  `grade_level_id` int(11) NOT NULL,
  `section` varchar(255) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblsections`
--

INSERT INTO `tblsections` (`section_id`, `grade_level_id`, `section`, `updated_at`, `created_at`) VALUES
(12, 1, 'C', '2026-06-10 01:24:14', '2026-06-10 01:24:14'),
(13, 2, 'C', '2026-06-10 01:25:21', '2026-06-10 01:24:24'),
(14, 2, 'A', '2026-06-12 05:32:14', '2026-06-12 05:32:14'),
(15, 1, 'A', '2026-06-12 05:32:21', '2026-06-12 05:32:21'),
(16, 2, 'B', '2026-06-12 05:32:27', '2026-06-12 05:32:27'),
(17, 1, 'B', '2026-06-12 05:32:36', '2026-06-12 05:32:36');

-- --------------------------------------------------------

--
-- Table structure for table `tblstrands`
--

CREATE TABLE `tblstrands` (
  `strand_id` int(11) NOT NULL,
  `grade_level_id` int(11) NOT NULL,
  `strand` varchar(255) NOT NULL,
  `track` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblstrands`
--

INSERT INTO `tblstrands` (`strand_id`, `grade_level_id`, `strand`, `track`, `updated_at`, `created_at`) VALUES
(22, 1, 'ALL STRANDS', 'CORE SUBJECTS', '2026-06-02 11:20:45', '2026-06-02 11:09:29'),
(23, 1, 'ABM', 'ACADEMIC TRACK', '2026-06-09 12:44:05', '2026-06-02 11:40:37'),
(24, 2, 'ABM', 'ACADEMIC TRACK', '2026-06-09 12:44:14', '2026-06-02 11:46:06'),
(25, 1, 'ASH', 'ACADEMIC TRACK', '2026-06-09 12:44:22', '2026-06-02 11:52:32'),
(26, 2, 'ASH', 'ACADEMIC TRACK', '2026-06-09 12:44:31', '2026-06-02 11:54:48'),
(27, 1, 'EIM', 'TVL (TECHNICAL-PROFESSIONAL TRACK)', '2026-06-09 12:44:38', '2026-06-02 12:05:26'),
(28, 2, 'EIM', 'TVL (TECHNICAL-PROFESSIONAL TRACK)', '2026-06-09 12:44:47', '2026-06-02 12:07:34'),
(29, 1, 'CSS', 'TVL (TECHNICAL–PROFESSIONAL TRACK)', '2026-06-10 00:39:32', '2026-06-10 00:38:36'),
(30, 2, 'CSS', 'TVL (TECHNICAL-PROFESSIONAL TRACK)', '2026-06-10 00:40:32', '2026-06-10 00:40:32'),
(31, 1, 'CCS', 'TVL (TECHNICAL-PROFESSIONAL TRACK)', '2026-06-10 00:57:16', '2026-06-10 00:57:16'),
(32, 2, 'CCS', 'TVL (TECHNICAL-PROFESSIONAL TRACK)', '2026-06-10 00:57:35', '2026-06-10 00:57:35');

-- --------------------------------------------------------

--
-- Table structure for table `tblstudents`
--

CREATE TABLE `tblstudents` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `section_id` int(11) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_pic` longblob DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `suffix` varchar(255) DEFAULT NULL,
  `lrn` varchar(255) DEFAULT NULL,
  `birthdate` varchar(255) DEFAULT NULL,
  `age` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `remember_token` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblstudents`
--

INSERT INTO `tblstudents` (`id`, `student_id`, `section_id`, `password`, `profile_pic`, `firstname`, `middlename`, `lastname`, `suffix`, `lrn`, `birthdate`, `age`, `gender`, `contact_number`, `guardian_name`, `guardian_email`, `address`, `status`, `remember_token`, `updated_at`, `created_at`) VALUES
(70, 'STUD-123456', 12, '$2y$10$C.Eb8XKSU8onR80TC9QilONrNkRO.f46h4RTFPiCCQPABZd0J9Mzy', NULL, 'Bryan', 'Fulo', 'Donceras', 'Jr', '46443754754342', '2000-08-29', '25', 'Male', '09382625945', 'mom', 'anjelheartburgos@gmail.com', 'Macawayan, Irosin, Sorsogon', 'active', NULL, '2026-07-19 11:10:16', '2026-06-12 01:47:13'),
(72, 'STUD-2026-12345', 16, '$2y$10$yKLeiRjxdJUjVkkkQ4aVDOR6I.XlyKwXiiwa6bYMG6PMxh7gIfNrG', NULL, 'Janloyd', 'Benas', 'Lotino', '', '12345678910', '2003-01-02', '23', 'Male', '09422144112', 'Loida', 'janloyd@lotino.com', 'Tabon-tabon', 'active', NULL, '2026-06-19 13:03:46', '2026-06-19 13:03:46'),
(73, 'stud123', 17, NULL, NULL, 'k', NULL, 'k', NULL, 'k', NULL, NULL, NULL, '', 'k', 'anjelheartburgos@gmail.com', NULL, 'active', NULL, '2026-07-19 10:44:29', '2026-07-19 10:44:29');

-- --------------------------------------------------------

--
-- Table structure for table `tblsubjects`
--

CREATE TABLE `tblsubjects` (
  `subject_id` int(11) NOT NULL,
  `strand_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblsubjects`
--

INSERT INTO `tblsubjects` (`subject_id`, `strand_id`, `subject`, `description`, `updated_at`, `created_at`) VALUES
(33, 22, 'Mabisang Komunikasyon/Effective Communication', NULL, '2026-06-02 11:34:09', '2026-06-02 11:33:56'),
(34, 22, 'General Mathematics', NULL, '2026-06-02 11:34:24', '2026-06-02 11:34:24'),
(35, 22, 'General Science', NULL, '2026-06-02 11:34:48', '2026-06-02 11:34:48'),
(36, 22, 'Life-and-Career-Skills', NULL, '2026-06-02 11:35:56', '2026-06-02 11:35:56'),
(37, 22, 'Pag-aaral ng Kasaysayan at Lipunang Pilipino', NULL, '2026-06-02 11:36:30', '2026-06-02 11:36:30'),
(38, 23, 'Business 1 (Basic Accounting)', NULL, '2026-06-02 11:41:08', '2026-06-02 11:41:08'),
(39, 23, 'Introduction to Organization and Management', NULL, '2026-06-02 11:44:34', '2026-06-02 11:44:34'),
(40, 23, 'Business 2 (Business Finance and Income Taxation)', NULL, '2026-06-02 11:45:14', '2026-06-02 11:45:14'),
(41, 23, 'Contemporary Marketing', NULL, '2026-06-02 11:45:32', '2026-06-02 11:45:32'),
(42, 24, 'Business 3 (Business Economics)', NULL, '2026-06-02 11:49:51', '2026-06-02 11:49:51'),
(43, 24, 'Entrepreneurship', NULL, '2026-06-02 11:50:20', '2026-06-02 11:50:20'),
(44, 24, 'Research Methods', NULL, '2026-06-02 11:50:35', '2026-06-02 11:50:35'),
(45, 24, 'Design and Innovation or Field Exposure (Off-campus)', NULL, '2026-06-02 11:51:09', '2026-06-02 11:51:09'),
(46, 25, 'Creative Composition 1', NULL, '2026-06-02 11:53:11', '2026-06-02 11:53:11'),
(47, 25, 'Contemporary Literature 1', NULL, '2026-06-02 11:53:33', '2026-06-02 11:53:33'),
(48, 25, 'Social Sciences Theory and Practice', NULL, '2026-06-02 11:53:56', '2026-06-02 11:53:56'),
(49, 25, 'Citizenship and Civil Engagement', NULL, '2026-06-02 11:54:21', '2026-06-02 11:54:21'),
(50, 26, 'Philippine Politics and Governance', NULL, '2026-06-02 11:58:22', '2026-06-02 11:58:22'),
(51, 26, 'Creative Composition 2', NULL, '2026-06-02 12:01:59', '2026-06-02 12:01:59'),
(52, 26, 'Contemporary Literature 2', NULL, '2026-06-02 12:02:16', '2026-06-02 12:02:16'),
(53, 26, 'Research Methods', NULL, '2026-06-02 12:02:34', '2026-06-02 12:02:34'),
(54, 26, 'Design and Innovation', NULL, '2026-06-02 12:03:11', '2026-06-02 12:03:11'),
(55, 26, 'Work Immersion', NULL, '2026-06-02 12:03:26', '2026-06-02 12:03:26'),
(56, 27, 'Electrical Tools and Equipment', NULL, '2026-06-02 12:05:52', '2026-06-02 12:05:52'),
(57, 27, 'Basic Electrical Installation', NULL, '2026-06-13 12:41:20', '2026-06-02 12:06:09'),
(58, 27, 'Occupational Health and Safety', NULL, '2026-06-02 12:06:29', '2026-06-02 12:06:29'),
(59, 28, 'Wiring Installation(Residencial)', NULL, '2026-06-02 12:08:05', '2026-06-02 12:08:05'),
(60, 28, 'Motor Control and Maintenance', NULL, '2026-06-02 12:08:25', '2026-06-02 12:08:25'),
(61, 28, 'Electrical Troubleshooting', NULL, '2026-06-02 12:08:46', '2026-06-02 12:08:46'),
(62, 28, 'Work Immersion', NULL, '2026-06-02 12:09:03', '2026-06-02 12:09:03'),
(63, 29, 'Computer Hardware Servicing', NULL, '2026-06-10 00:45:28', '2026-06-10 00:45:28'),
(64, 29, 'Networking Fundamentals', NULL, '2026-06-10 00:45:41', '2026-06-10 00:45:41'),
(65, 29, 'Occupational Health and Safety', NULL, '2026-06-10 00:45:57', '2026-06-10 00:45:57'),
(66, 29, 'Broadband Installation', 'Broadband installation is the process of setting up a high-speed internet connection in a home, school, or office by connecting the necessary equipment and configuring the service provided by an internet service provider (ISP). It usually involves installing a modem or router, connecting it to a telephone line, fiber optic cable, or coaxial cable depending on the type of broadband service, and ensuring that the device is properly configured to access the internet. A technician may visit the location to run cables, test signal strength, and activate the connection to ensure stable and reliable internet access. Once installed, users can connect their devices such as computers, smartphones, and smart TVs to enjoy fast internet for browsing, streaming, online learning, and communication.\r\n', '2026-06-13 12:41:33', '2026-06-10 00:46:09'),
(67, 30, 'Network Installation and Configuration', NULL, '2026-06-10 00:46:25', '2026-06-10 00:46:25'),
(68, 30, 'Systems Maintenance and Troubleshooting', NULL, '2026-06-10 00:46:39', '2026-06-10 00:46:39'),
(69, 30, 'ICT Project Implementation', NULL, '2026-06-10 00:47:06', '2026-06-10 00:47:06'),
(70, 30, 'Work Immersion', NULL, '2026-06-10 00:47:33', '2026-06-10 00:47:33'),
(71, 31, 'Communication Skills for BPO', NULL, '2026-06-10 01:09:11', '2026-06-10 01:09:11'),
(72, 31, 'Customer Service Fundamentals', NULL, '2026-06-10 01:09:23', '2026-06-10 01:09:23'),
(73, 31, 'Workplace Communication', NULL, '2026-06-10 01:09:35', '2026-06-10 01:09:35'),
(74, 32, 'Call Handling Techniques', NULL, '2026-06-10 01:09:49', '2026-06-10 01:09:49'),
(75, 32, 'Sales and Customer Relations', NULL, '2026-06-10 01:10:02', '2026-06-10 01:10:02'),
(76, 32, 'BPO Simulation / Practicum', NULL, '2026-06-13 12:41:25', '2026-06-10 01:10:13'),
(77, 23, 'BPO Simulation / Practicum', NULL, '2026-06-12 14:56:35', '2026-06-12 14:56:35');

-- --------------------------------------------------------

--
-- Table structure for table `tblteachers_courses`
--

CREATE TABLE `tblteachers_courses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `strand_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `max_students` int(11) DEFAULT 40,
  `status` enum('active','draft','archived') DEFAULT 'draft',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblteacher_education`
--

CREATE TABLE `tblteacher_education` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `highest_degree` varchar(255) DEFAULT NULL,
  `university` varchar(255) DEFAULT NULL,
  `bachelors_degree` varchar(255) DEFAULT NULL,
  `licensure` varchar(255) DEFAULT NULL,
  `lpt_year` varchar(50) DEFAULT NULL,
  `masters` varchar(255) DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblteacher_settings`
--

CREATE TABLE `tblteacher_settings` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `language` varchar(10) NOT NULL DEFAULT 'en',
  `timezone` varchar(50) NOT NULL DEFAULT 'PHT',
  `theme` varchar(10) NOT NULL DEFAULT 'dark',
  `email_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `email_digest` tinyint(1) NOT NULL DEFAULT 0,
  `sms_alerts` tinyint(1) NOT NULL DEFAULT 0,
  `sms_reminder` tinyint(1) NOT NULL DEFAULT 1,
  `push_browser` tinyint(1) NOT NULL DEFAULT 1,
  `push_system` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `tblteacher_settings`
--

INSERT INTO `tblteacher_settings` (`id`, `teacher_id`, `language`, `timezone`, `theme`, `email_alerts`, `email_digest`, `sms_alerts`, `sms_reminder`, `push_browser`, `push_system`, `created_at`, `updated_at`) VALUES
(52, 5, 'en', 'PHT', 'dark', 1, 0, 1, 1, 1, 1, '2026-06-07 08:29:16', '2026-06-13 11:52:58'),
(73, 46, 'en', 'PHT', 'dark', 1, 0, 0, 1, 1, 1, '2026-06-13 12:47:01', '2026-06-21 11:13:18');

-- --------------------------------------------------------

--
-- Table structure for table `tblusers`
--

CREATE TABLE `tblusers` (
  `id` int(11) NOT NULL,
  `profile_pic` longblob DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `age` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `is_verified` int(11) NOT NULL DEFAULT 0,
  `role` enum('teacher','admin') DEFAULT 'teacher',
  `status` enum('active','inactive') DEFAULT 'active',
  `reset_expires` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `remember_token` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblusers`
--

INSERT INTO `tblusers` (`id`, `profile_pic`, `email`, `password`, `firstname`, `middlename`, `lastname`, `age`, `gender`, `contact_number`, `address`, `is_verified`, `role`, `status`, `reset_expires`, `reset_token`, `two_factor_secret`, `two_factor_enabled`, `remember_token`, `updated_at`, `created_at`) VALUES
(5, 0x313738303430343931385f457863656c73696f725f5365616c5f4c6f676f2e706e67, 'excelsiorpolyacad@gmail.com', '$2y$10$KlAJUnkKIxurRKEI4suk7O9Z6TNilAIk9Qk6uYZb.tpNI7cYbSrd6', 'ExceLearn', '', 'Admin', '25', 'Male', '09480040600', 'Cabitan, Mandaon, Masbate', 1, 'admin', 'active', NULL, NULL, '0ADD0FFA35A7957B', 1, NULL, '2026-06-05 02:18:10', '2026-04-26 13:31:03'),
(37, NULL, 'junacvelarde@gmail.com', '$2y$10$DMWwys3a3qJS6oavWJrh2uwVWRafEf8YGdAevM/n/BR4yRli7XT06', 'Juna', 'Capinig', 'Velarde', NULL, NULL, NULL, NULL, 1, 'teacher', 'active', NULL, NULL, NULL, 0, NULL, '2026-06-12 07:00:45', '2026-06-12 07:00:45'),
(42, NULL, 'jhamel.habla@deped.gov.ph', '$2y$10$A0.9IM1AcBPYyemrzaO.suTD1dW4sUBZzIp06ADy8J8837LqoogoW', 'JHAMEL', 'BALOSO', 'HABLA', '25', 'Male', '09382625945', 'Macawayan, Irosin, Sorsogon', 1, 'teacher', 'active', NULL, NULL, NULL, 0, NULL, '2026-06-12 14:03:44', '2026-06-12 13:33:53'),
(46, NULL, 'hablajhamel00@gmail.com', '$2y$10$LdNt7iMT5tOfLywqb3sbhuDzhn3N9LBiIhCP4cPggBVeg2bhknNRa', 'JHAMEL', 'BALOSO', 'HABLA', '25', 'Male', '09382625945', 'Macawayan Irosin Sorsogon', 1, 'teacher', 'active', NULL, NULL, NULL, 0, NULL, '2026-06-13 12:59:09', '2026-06-12 14:14:38'),
(47, NULL, 'bryandonceras24@gmail.com', '$2y$10$hAr1SFddGgzDqHnJgwLnHuPJ5ylPJx8m5RbLPDnY5rGdXI9Tq84va', 'Bryan', 'Fulo', 'Donceras', '25', 'Male', '09382625945', '0', 1, 'teacher', 'active', NULL, NULL, NULL, 0, NULL, '2026-06-12 14:24:54', '2026-06-12 14:24:54'),
(48, NULL, 'gvffnyfm@immenseignite.info', '$2y$10$On8FigecNDrEv5O3TdKhM.7mIRnu3XUX1tRNYhQssx7/9FdGYpP9y', 'uzgnijglxg', 'demyyjihqm', 'nljumwwquv', NULL, NULL, NULL, NULL, 0, 'teacher', '', NULL, NULL, NULL, 0, NULL, '2026-08-31 15:43:50', '2026-08-31 15:43:50');

-- --------------------------------------------------------

--
-- Table structure for table `tbluser_sessions`
--

CREATE TABLE `tbluser_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device` varchar(255) NOT NULL,
  `browser` varchar(100) NOT NULL,
  `ip_address` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT 'Unknown',
  `is_current` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbluser_sessions`
--

INSERT INTO `tbluser_sessions` (`id`, `user_id`, `device`, `browser`, `ip_address`, `location`, `is_current`, `created_at`) VALUES
(3, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 09:51:58'),
(4, 5, 'Mobile Android Linux', 'Google Chrome', '192.168.1.16', 'Philippines', 0, '2026-05-08 09:53:17'),
(5, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 09:53:31'),
(6, 5, 'Mobile Android Linux', 'Google Chrome', '192.168.1.16', 'Philippines', 0, '2026-05-08 09:57:02'),
(7, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 09:58:09'),
(8, 5, 'Mobile Android Linux', 'Google Chrome', '192.168.1.16', 'Philippines', 0, '2026-05-08 09:58:15'),
(9, 5, 'Mobile Android Linux', 'Google Chrome', '192.168.1.16', 'Philippines', 0, '2026-05-08 09:59:28'),
(10, 5, 'Mobile Android Linux', 'Google Chrome', '192.168.1.16', 'Philippines', 0, '2026-05-08 09:59:35'),
(11, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 09:59:55'),
(12, 5, 'Mobile Android Linux', 'Google Chrome', '192.168.1.16', 'Philippines', 0, '2026-05-08 10:05:23'),
(13, 5, 'Mobile Android Linux', 'Google Chrome', '192.168.1.16', 'Philippines', 0, '2026-05-08 10:05:32'),
(14, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:05:39'),
(15, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:05:46'),
(16, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:05:51'),
(17, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:07:17'),
(18, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:13:55'),
(19, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:14:06'),
(20, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:14:12'),
(21, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:15:07'),
(22, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:16:16'),
(23, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:16:26'),
(24, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:17:54'),
(25, 5, 'Mobile Android Linux', 'Google Chrome', '192.168.1.16', 'Philippines', 0, '2026-05-08 10:18:01'),
(26, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:18:04'),
(27, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:26:36'),
(28, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:29:31'),
(29, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-08 10:40:44'),
(30, 5, 'Mobile Android Linux', 'Google Chrome', '192.168.1.16', 'Philippines', 0, '2026-05-08 11:41:20'),
(31, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 06:51:59'),
(32, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:09:29'),
(33, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:13:08'),
(34, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:17:56'),
(35, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:18:40'),
(36, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:19:37'),
(37, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:19:39'),
(38, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:19:42'),
(39, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:19:54'),
(40, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:20:49'),
(41, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:21:26'),
(42, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:21:28'),
(43, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:21:31'),
(44, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:21:35'),
(45, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:21:49'),
(46, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:22:01'),
(47, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:22:03'),
(48, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:23:01'),
(49, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:23:01'),
(50, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:23:02'),
(51, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:23:02'),
(52, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:23:02'),
(53, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:23:24'),
(54, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:26:39'),
(55, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:26:41'),
(56, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:27:52'),
(57, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:28:01'),
(58, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:28:12'),
(59, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:28:49'),
(60, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:28:52'),
(61, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:27'),
(62, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:27'),
(63, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:28'),
(64, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:28'),
(65, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:28'),
(66, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:28'),
(67, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:28'),
(68, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:28'),
(69, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:29'),
(70, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:29'),
(71, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:29'),
(72, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:30'),
(73, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:31'),
(74, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:32'),
(75, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:32'),
(76, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:32'),
(77, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:33'),
(78, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:33'),
(79, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:33'),
(80, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:33'),
(81, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:33'),
(82, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:33'),
(83, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:34'),
(84, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:34'),
(85, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:34'),
(86, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:34'),
(87, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:34'),
(88, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:34'),
(89, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:35'),
(90, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:35'),
(91, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:35'),
(92, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:35'),
(93, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:35'),
(94, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:36'),
(95, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:30:36'),
(96, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:36:06'),
(97, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:36:07'),
(98, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:36:07'),
(99, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:36:08'),
(100, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:36:23'),
(101, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:36:24'),
(102, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:37:31'),
(103, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:37:39'),
(104, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:39:55'),
(105, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:40:47'),
(106, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:40:57'),
(107, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:41:00'),
(108, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:41:13'),
(109, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:41:15'),
(110, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:41:15'),
(111, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:41:16'),
(112, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:43:06'),
(113, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:43:16'),
(114, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:43:54'),
(115, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:44:13'),
(116, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:44:20'),
(117, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:48:19'),
(118, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:48:20'),
(119, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:51:07'),
(120, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:51:10'),
(121, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:51:13'),
(122, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 10:51:25'),
(123, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:00:23'),
(124, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:00:35'),
(125, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:02:16'),
(126, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:02:57'),
(127, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:04:20'),
(128, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:04:26'),
(129, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:04:29'),
(130, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:05:42'),
(131, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:06:34'),
(132, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:07:03'),
(133, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:07:04'),
(134, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:07:05'),
(135, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:07:05'),
(136, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:07:05'),
(137, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:07:35'),
(138, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:08:36'),
(139, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:08:38'),
(140, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:08:38'),
(141, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:08:39'),
(142, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:08:39'),
(143, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:08:39'),
(144, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:08:55'),
(145, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:08:55'),
(146, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:08:56'),
(147, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:08:56'),
(152, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:10:19'),
(153, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:11:27'),
(154, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:11:29'),
(155, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:11:34'),
(156, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:15:30'),
(157, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:15:32'),
(158, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:15:34'),
(159, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:17:47'),
(160, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:18:38'),
(161, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:19:53'),
(162, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-12 11:19:59'),
(163, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-16 11:52:17'),
(164, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-16 11:52:47'),
(165, 5, 'Desktop Windows PC', 'Microsoft Edge', '::1', 'Philippines', 0, '2026-05-16 11:52:49'),
(166, 5, 'Desktop Windows PC', 'Google Chrome', '::1', 'Philippines', 0, '2026-05-17 05:03:53'),
(167, 5, 'Desktop Windows PC', 'Google Chrome', '::1', 'Philippines', 0, '2026-05-17 05:03:55'),
(168, 5, 'Desktop Windows PC', 'Google Chrome', '::1', 'Philippines', 0, '2026-05-23 11:00:53'),
(169, 5, 'Desktop Windows PC', 'Google Chrome', '::1', 'Philippines', 0, '2026-05-23 11:00:55'),
(170, 5, 'Desktop Windows PC', 'Google Chrome', '::1', 'Philippines', 0, '2026-05-23 11:00:58'),
(171, 5, 'Desktop Windows PC', 'Google Chrome', '::1', 'Philippines', 0, '2026-05-23 11:00:59'),
(172, 5, 'Desktop Windows PC', 'Google Chrome', '::1', 'Philippines', 0, '2026-05-23 11:01:01'),
(173, 5, 'Desktop Windows PC', 'Google Chrome', '::1', 'Philippines', 0, '2026-05-23 11:26:38'),
(174, 5, 'Desktop Windows PC', 'Google Chrome', '::1', 'Philippines', 0, '2026-05-23 11:26:39'),
(175, 5, 'Desktop Windows PC', 'Google Chrome', '::1', 'Philippines', 0, '2026-05-23 11:26:42'),
(176, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-01 10:40:15'),
(177, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-01 10:40:48'),
(178, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-01 10:41:02'),
(179, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-01 10:42:06'),
(180, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.102.207', 'Philippines', 0, '2026-06-01 11:00:07'),
(181, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.102.207', 'Philippines', 0, '2026-06-01 11:00:16'),
(182, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 11:38:28'),
(183, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.102.207', 'Philippines', 0, '2026-06-02 12:54:49'),
(184, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.102.207', 'Philippines', 0, '2026-06-02 12:55:18'),
(185, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:04:36'),
(186, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:07:03'),
(187, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:08:01'),
(188, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:08:03'),
(189, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:08:04'),
(190, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:08:04'),
(191, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:08:57'),
(192, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:09:12'),
(193, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:09:21'),
(194, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:10:27'),
(195, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:10:30'),
(196, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:11:25'),
(197, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:11:27'),
(198, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:11:28'),
(199, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:11:28'),
(200, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:11:29'),
(201, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:11:41'),
(202, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:12:12'),
(203, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:14:28'),
(204, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:14:30'),
(205, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:14:32'),
(206, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:14:33'),
(207, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:14:34'),
(208, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:14:35'),
(209, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:15:15'),
(210, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-02 16:17:31'),
(212, 5, 'Mobile Android Linux', 'Google Chrome', '124.106.151.211', 'Philippines', 0, '2026-06-02 23:58:49'),
(213, 5, 'Mobile Android Linux', 'Google Chrome', '124.106.151.211', 'Philippines', 0, '2026-06-03 00:09:41'),
(214, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.207', 'Philippines', 0, '2026-06-03 12:13:53'),
(215, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:10:45'),
(216, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:11:02'),
(217, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:11:25'),
(218, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:13:00'),
(219, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:13:10'),
(220, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:13:11'),
(221, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:14:03'),
(222, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:14:42'),
(223, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:14:47'),
(224, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:15:27'),
(225, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:16:40'),
(226, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:17:31'),
(227, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:17:41'),
(228, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-05 02:18:10'),
(229, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-06 15:23:48'),
(230, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 07:51:55'),
(231, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 07:52:03'),
(232, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 07:52:10'),
(233, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 07:52:48'),
(234, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 07:52:52'),
(235, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 07:53:01'),
(236, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 08:25:18'),
(237, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 08:26:46'),
(238, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 08:26:51'),
(239, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 08:27:55'),
(240, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 08:46:42'),
(241, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 08:46:51'),
(242, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.206', 'Philippines', 0, '2026-06-07 08:49:03'),
(243, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-07 09:07:51'),
(244, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-07 09:08:45'),
(245, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-07 09:08:54'),
(246, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-07 09:09:17'),
(247, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-07 09:10:41'),
(248, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-07 09:12:02'),
(249, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-07 09:12:21'),
(250, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-07 09:32:45'),
(251, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-07 09:39:14'),
(252, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-07 09:39:18'),
(253, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-07 09:39:29'),
(254, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.206', 'Philippines', 0, '2026-06-07 09:41:07'),
(255, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.94', 'Philippines', 0, '2026-06-10 12:53:27'),
(256, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.255', 'Philippines', 0, '2026-06-12 01:15:58'),
(257, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.255', 'Philippines', 0, '2026-06-12 14:33:56'),
(258, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.255', 'Philippines', 0, '2026-06-12 14:34:05'),
(259, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.255', 'Philippines', 0, '2026-06-12 14:34:44'),
(260, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.255', 'Philippines', 0, '2026-06-12 14:34:54'),
(261, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.255', 'Philippines', 0, '2026-06-13 11:37:11'),
(262, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.255', 'Philippines', 0, '2026-06-13 11:48:38'),
(263, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.255', 'Philippines', 0, '2026-06-13 11:49:57'),
(264, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.255', 'Philippines', 0, '2026-06-13 12:05:30'),
(265, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.255', 'Philippines', 0, '2026-06-13 12:06:23'),
(266, 5, 'Desktop Windows PC', 'Microsoft Edge', '136.158.103.255', 'Philippines', 0, '2026-06-13 12:06:33'),
(267, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.255', 'Philippines', 0, '2026-06-13 12:08:09'),
(268, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.255', 'Philippines', 0, '2026-06-13 12:08:12'),
(269, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.103.255', 'Philippines', 0, '2026-06-13 12:09:40'),
(270, 5, 'Mobile Android Linux', 'Google Chrome', '136.158.102.98', 'Philippines', 1, '2026-07-02 12:51:33');

-- --------------------------------------------------------

--
-- Table structure for table `tblvirtualclasses`
--

CREATE TABLE `tblvirtualclasses` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `teacher_subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `status` enum('live','upcoming','ended','cancelled') DEFAULT 'upcoming',
  `date` date NOT NULL,
  `time` time NOT NULL,
  `duration` int(11) DEFAULT 60,
  `camera_on` tinyint(1) DEFAULT 0,
  `mic_on` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblvirtualclass_participants`
--

CREATE TABLE `tblvirtualclass_participants` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `teacher_subject_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `camera_status` tinyint(1) DEFAULT 0,
  `mic_status` tinyint(1) DEFAULT 0,
  `join` enum('YES','NO','','') NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblvirtualclass_recordings`
--

CREATE TABLE `tblvirtualclass_recordings` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `file_path` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblvirtualclass_signals`
--

CREATE TABLE `tblvirtualclass_signals` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `from_id` int(11) NOT NULL,
  `from_role` varchar(10) NOT NULL,
  `to_role` varchar(10) NOT NULL,
  `signal` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_emailer`
--

CREATE TABLE `tbl_emailer` (
  `email_id` int(11) NOT NULL,
  `smtp_email` varchar(255) NOT NULL,
  `smtp_password` varchar(255) NOT NULL,
  `smtp_from` varchar(255) NOT NULL,
  `smtp_name` varchar(255) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_emailer`
--

INSERT INTO `tbl_emailer` (`email_id`, `smtp_email`, `smtp_password`, `smtp_from`, `smtp_name`, `updated_at`, `created_at`) VALUES
(1, 'mail@excelearn.excelsiorpolytechnicacademy.com', 'wifl-iqrn-pymn-ibks', 'mail@excelearn.excelsiorpolytechnicacademy.com', 'ExceLearn', '2026-05-23 17:31:26', '2026-05-23 17:31:26');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_storage_files`
--

CREATE TABLE `tbl_storage_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_students_settings`
--

CREATE TABLE `tbl_students_settings` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `theme` varchar(10) NOT NULL DEFAULT 'dark',
  `language` varchar(50) NOT NULL DEFAULT 'English (Philippines)',
  `timezone` varchar(50) NOT NULL DEFAULT '(GMT+08:00) Philippine Time',
  `notif_push` tinyint(1) NOT NULL DEFAULT 1,
  `notif_sms` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_students_settings`
--

INSERT INTO `tbl_students_settings` (`id`, `student_id`, `theme`, `language`, `timezone`, `notif_push`, `notif_sms`, `updated_at`) VALUES
(29, 70, 'dark', 'English (Philippines)', '(GMT+08:00) Philippine Time', 1, 0, '2026-06-12 01:51:05');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_student_settings`
--

CREATE TABLE `tbl_student_settings` (
  `student_id` int(11) NOT NULL,
  `theme` varchar(10) DEFAULT 'dark',
  `language` varchar(50) DEFAULT 'English (Philippines)',
  `timezone` varchar(50) DEFAULT '(GMT+08:00) Philippine Time',
  `notif_push` tinyint(1) DEFAULT 0,
  `notif_sms` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_student_settings`
--

INSERT INTO `tbl_student_settings` (`student_id`, `theme`, `language`, `timezone`, `notif_push`, `notif_sms`, `updated_at`) VALUES
(34, 'dark', 'English (Philippines)', '(GMT+08:00) Philippine Time', 0, 0, '2026-05-30 12:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_teachers_students`
--

CREATE TABLE `tbl_teachers_students` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_subject_id` int(11) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_teachers_students`
--

INSERT INTO `tbl_teachers_students` (`id`, `teacher_id`, `student_id`, `teacher_subject_id`, `updated_at`, `created_at`) VALUES
(47, 46, 70, 124, '2026-06-19 12:57:08', '2026-06-19 12:57:08'),
(48, 46, 70, 125, '2026-06-19 12:57:08', '2026-06-19 12:57:08'),
(49, 46, 70, 126, '2026-06-19 12:57:08', '2026-06-19 12:57:08'),
(50, 46, 72, 124, '2026-06-19 13:03:46', '2026-06-19 13:03:46'),
(51, 46, 72, 125, '2026-06-19 13:03:46', '2026-06-19 13:03:46'),
(52, 46, 72, 126, '2026-06-19 13:03:46', '2026-06-19 13:03:46');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_teacher_lessons`
--

CREATE TABLE `tbl_teacher_lessons` (
  `lesson_id` int(11) NOT NULL,
  `teacher_subject_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `duration` int(11) DEFAULT 0,
  `resource_type` enum('video','pdf','ppt','none') DEFAULT 'none',
  `video_path` varchar(500) DEFAULT NULL,
  `video_filename` varchar(255) DEFAULT NULL,
  `manual_content` longtext DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `order_index` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_teacher_lessons`
--

INSERT INTO `tbl_teacher_lessons` (`lesson_id`, `teacher_subject_id`, `title`, `description`, `duration`, `resource_type`, `video_path`, `video_filename`, `manual_content`, `status`, `order_index`, `created_at`, `updated_at`) VALUES
(88, 126, 'Electrical Safety Practices', 'This subject focuses on the importance of safety procedures, proper handling of electrical tools, and preventive measures to avoid electrical accidents during installation and maintenance work.', 0, 'video', 'video_6a379c97c89597.21431786.mp4', 'Untitled design (3).mp4', 'Introduction to electrical hazards\r\nSafety rules and regulations\r\nProper use of Personal Protective Equipment (PPE)\r\nElectrical shock prevention\r\nFire prevention and emergency procedures\r\nSafe working practices in electrical installation', 'published', 0, '2026-06-21 08:11:03', '2026-06-21 11:26:13'),
(89, 126, 'lesson 2.', 'dhshdshds', 0, 'none', '', '', 'dshshdshds', 'published', 0, '2026-07-05 13:17:27', '2026-07-05 13:42:02'),
(92, 126, 'dshsdh', 'dshshds', 0, 'pdf', 'pdf_6a4a6fbe05f534.07159161.pdf', 'Doc1.pdf', 'dhsgdshds', 'published', 1, '2026-07-05 14:52:46', '2026-07-05 14:57:43'),
(93, 126, 'dshdsh', 'dshdsd', 0, 'ppt', 'ppt_6a4a6fd6d70b84.44854689.pptx', 'Excelsior Attendance.pptx', 'dssgds', 'published', 2, '2026-07-05 14:53:10', '2026-07-05 14:57:43');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_teacher_subjects`
--

CREATE TABLE `tbl_teacher_subjects` (
  `teacher_subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_teacher_subjects`
--

INSERT INTO `tbl_teacher_subjects` (`teacher_subject_id`, `teacher_id`, `subject_id`, `updated_at`, `created_at`) VALUES
(124, 46, 57, '2026-06-13 12:41:20', '2026-06-13 12:41:20'),
(125, 46, 76, '2026-06-13 12:41:25', '2026-06-13 12:41:25'),
(126, 46, 66, '2026-06-13 12:41:33', '2026-06-13 12:41:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblachievements`
--
ALTER TABLE `tblachievements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblactivities`
--
ALTER TABLE `tblactivities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblannouncements`
--
ALTER TABLE `tblannouncements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tblcertificates`
--
ALTER TABLE `tblcertificates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcourse_enrollments`
--
ALTER TABLE `tblcourse_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `uniq_enrollment` (`teacher_course_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `tblenrollments`
--
ALTER TABLE `tblenrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `tblgrade_levels`
--
ALTER TABLE `tblgrade_levels`
  ADD PRIMARY KEY (`grade_id`);

--
-- Indexes for table `tbllesson_completions`
--
ALTER TABLE `tbllesson_completions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbllesson_progress`
--
ALTER TABLE `tbllesson_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `tblnotifications`
--
ALTER TABLE `tblnotifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tblperformance_tasks`
--
ALTER TABLE `tblperformance_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `idx_pt_subject` (`teacher_subject_id`),
  ADD KEY `idx_pt_status` (`status`);

--
-- Indexes for table `tblperformance_task_submissions`
--
ALTER TABLE `tblperformance_task_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD UNIQUE KEY `uniq_task_student` (`task_id`,`student_id`),
  ADD KEY `idx_pts_task` (`task_id`),
  ADD KEY `idx_pts_student` (`student_id`);

--
-- Indexes for table `tblquizzes`
--
ALTER TABLE `tblquizzes`
  ADD PRIMARY KEY (`quiz_id`),
  ADD KEY `fk_lesson` (`lesson_id`);

--
-- Indexes for table `tblquizzes_answers`
--
ALTER TABLE `tblquizzes_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quizzes_question_id` (`quizzes_question_id`);

--
-- Indexes for table `tblquiz_results`
--
ALTER TABLE `tblquiz_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`student_id`),
  ADD KEY `quizzes_id` (`quizzes_id`);

--
-- Indexes for table `tblsections`
--
ALTER TABLE `tblsections`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `grade_level_id` (`grade_level_id`);

--
-- Indexes for table `tblstrands`
--
ALTER TABLE `tblstrands`
  ADD PRIMARY KEY (`strand_id`),
  ADD KEY `grade_level_id` (`grade_level_id`);

--
-- Indexes for table `tblstudents`
--
ALTER TABLE `tblstudents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `tblsubjects`
--
ALTER TABLE `tblsubjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD KEY `strand_id` (`strand_id`);

--
-- Indexes for table `tblteachers_courses`
--
ALTER TABLE `tblteachers_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `strand_id` (`strand_id`),
  ADD KEY `grade_id` (`grade_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `tblteacher_education`
--
ALTER TABLE `tblteacher_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_teacher_education_user` (`user_id`);

--
-- Indexes for table `tblteacher_settings`
--
ALTER TABLE `tblteacher_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher` (`teacher_id`);

--
-- Indexes for table `tblusers`
--
ALTER TABLE `tblusers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tbluser_sessions`
--
ALTER TABLE `tbluser_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tblvirtualclasses`
--
ALTER TABLE `tblvirtualclasses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_teacher` (`teacher_id`),
  ADD KEY `teacher_subject_id` (`teacher_subject_id`);

--
-- Indexes for table `tblvirtualclass_participants`
--
ALTER TABLE `tblvirtualclass_participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_subject_id` (`teacher_subject_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `tblvirtualclass_recordings`
--
ALTER TABLE `tblvirtualclass_recordings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `tblvirtualclass_signals`
--
ALTER TABLE `tblvirtualclass_signals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_class_role` (`class_id`,`to_role`,`id`);

--
-- Indexes for table `tbl_emailer`
--
ALTER TABLE `tbl_emailer`
  ADD PRIMARY KEY (`email_id`);

--
-- Indexes for table `tbl_storage_files`
--
ALTER TABLE `tbl_storage_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_students_settings`
--
ALTER TABLE `tbl_students_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `tbl_student_settings`
--
ALTER TABLE `tbl_student_settings`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `tbl_teachers_students`
--
ALTER TABLE `tbl_teachers_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `teacher_courses_id` (`teacher_subject_id`);

--
-- Indexes for table `tbl_teacher_lessons`
--
ALTER TABLE `tbl_teacher_lessons`
  ADD PRIMARY KEY (`lesson_id`),
  ADD KEY `teacher_course_id` (`teacher_subject_id`);

--
-- Indexes for table `tbl_teacher_subjects`
--
ALTER TABLE `tbl_teacher_subjects`
  ADD PRIMARY KEY (`teacher_subject_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblachievements`
--
ALTER TABLE `tblachievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblactivities`
--
ALTER TABLE `tblactivities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblannouncements`
--
ALTER TABLE `tblannouncements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `tblcertificates`
--
ALTER TABLE `tblcertificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcourse_enrollments`
--
ALTER TABLE `tblcourse_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblenrollments`
--
ALTER TABLE `tblenrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblgrade_levels`
--
ALTER TABLE `tblgrade_levels`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbllesson_completions`
--
ALTER TABLE `tbllesson_completions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbllesson_progress`
--
ALTER TABLE `tbllesson_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblnotifications`
--
ALTER TABLE `tblnotifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `tblperformance_tasks`
--
ALTER TABLE `tblperformance_tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblperformance_task_submissions`
--
ALTER TABLE `tblperformance_task_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblquizzes`
--
ALTER TABLE `tblquizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `tblquizzes_answers`
--
ALTER TABLE `tblquizzes_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblquiz_results`
--
ALTER TABLE `tblquiz_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `tblsections`
--
ALTER TABLE `tblsections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tblstrands`
--
ALTER TABLE `tblstrands`
  MODIFY `strand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `tblstudents`
--
ALTER TABLE `tblstudents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `tblsubjects`
--
ALTER TABLE `tblsubjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `tblteachers_courses`
--
ALTER TABLE `tblteachers_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tblteacher_education`
--
ALTER TABLE `tblteacher_education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tblteacher_settings`
--
ALTER TABLE `tblteacher_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `tblusers`
--
ALTER TABLE `tblusers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `tbluser_sessions`
--
ALTER TABLE `tbluser_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=271;

--
-- AUTO_INCREMENT for table `tblvirtualclasses`
--
ALTER TABLE `tblvirtualclasses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `tblvirtualclass_participants`
--
ALTER TABLE `tblvirtualclass_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tblvirtualclass_recordings`
--
ALTER TABLE `tblvirtualclass_recordings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblvirtualclass_signals`
--
ALTER TABLE `tblvirtualclass_signals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6465;

--
-- AUTO_INCREMENT for table `tbl_emailer`
--
ALTER TABLE `tbl_emailer`
  MODIFY `email_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_storage_files`
--
ALTER TABLE `tbl_storage_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_students_settings`
--
ALTER TABLE `tbl_students_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `tbl_teachers_students`
--
ALTER TABLE `tbl_teachers_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `tbl_teacher_lessons`
--
ALTER TABLE `tbl_teacher_lessons`
  MODIFY `lesson_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `tbl_teacher_subjects`
--
ALTER TABLE `tbl_teacher_subjects`
  MODIFY `teacher_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

-- --------------------------------------------------------

--
-- Structure for view `students`
--
DROP TABLE IF EXISTS `students`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u342202101_excelearn`@`localhost` SQL SECURITY DEFINER VIEW `students`  AS SELECT `s`.`id` AS `student_id`, `s`.`student_id` AS `student_number`, `s`.`firstname` AS `first_name`, `s`.`lastname` AS `last_name`, `s`.`section_id` AS `section_id`, coalesce(`g`.`grade_level`,'N/A') AS `grade_level`, coalesce(`sec`.`section`,'N/A') AS `section`, `s`.`guardian_name` AS `guardian_name`, `s`.`contact_number` AS `guardian_phone`, `s`.`guardian_email` AS `guardian_email`, `s`.`student_id` AS `qr_code`, `s`.`lrn` AS `lrn`, `s`.`status` AS `status` FROM ((`tblstudents` `s` left join `tblsections` `sec` on(`s`.`section_id` = `sec`.`section_id`)) left join `tblgrade_levels` `g` on(`sec`.`grade_level_id` = `g`.`grade_id`)) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblannouncements`
--
ALTER TABLE `tblannouncements`
  ADD CONSTRAINT `tblannouncements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tblusers` (`id`);

--
-- Constraints for table `tblcourse_enrollments`
--
ALTER TABLE `tblcourse_enrollments`
  ADD CONSTRAINT `tblcourse_enrollments_ibfk_1` FOREIGN KEY (`teacher_course_id`) REFERENCES `tblteachers_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tblcourse_enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `tblusers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tblenrollments`
--
ALTER TABLE `tblenrollments`
  ADD CONSTRAINT `fk_tblstrands` FOREIGN KEY (`course_id`) REFERENCES `tblstrands` (`strand_id`),
  ADD CONSTRAINT `fk_tblstudents` FOREIGN KEY (`user_id`) REFERENCES `tblstudents` (`id`);

--
-- Constraints for table `tbllesson_progress`
--
ALTER TABLE `tbllesson_progress`
  ADD CONSTRAINT `tbllesson_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `tblstudents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbllesson_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `tbl_teacher_lessons` (`lesson_id`) ON DELETE CASCADE;

--
-- Constraints for table `tblperformance_tasks`
--
ALTER TABLE `tblperformance_tasks`
  ADD CONSTRAINT `fk_pt_teacher_subject` FOREIGN KEY (`teacher_subject_id`) REFERENCES `tbl_teacher_subjects` (`teacher_subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `tblperformance_task_submissions`
--
ALTER TABLE `tblperformance_task_submissions`
  ADD CONSTRAINT `fk_pts_student` FOREIGN KEY (`student_id`) REFERENCES `tblstudents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pts_task` FOREIGN KEY (`task_id`) REFERENCES `tblperformance_tasks` (`task_id`) ON DELETE CASCADE;

--
-- Constraints for table `tblquizzes`
--
ALTER TABLE `tblquizzes`
  ADD CONSTRAINT `fk_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `tbl_teacher_lessons` (`lesson_id`) ON DELETE CASCADE;

--
-- Constraints for table `tblquizzes_answers`
--
ALTER TABLE `tblquizzes_answers`
  ADD CONSTRAINT `fk_tbl_quizzes` FOREIGN KEY (`quizzes_question_id`) REFERENCES `tblquizzes` (`quiz_id`);

--
-- Constraints for table `tblquiz_results`
--
ALTER TABLE `tblquiz_results`
  ADD CONSTRAINT `fk_quizzes` FOREIGN KEY (`quizzes_id`) REFERENCES `tblquizzes` (`quiz_id`),
  ADD CONSTRAINT `fk_student` FOREIGN KEY (`student_id`) REFERENCES `tblstudents` (`id`);

--
-- Constraints for table `tblsections`
--
ALTER TABLE `tblsections`
  ADD CONSTRAINT `fk_tbl_grade_levels2` FOREIGN KEY (`grade_level_id`) REFERENCES `tblgrade_levels` (`grade_id`);

--
-- Constraints for table `tblstrands`
--
ALTER TABLE `tblstrands`
  ADD CONSTRAINT `fk_tblgrade_levels` FOREIGN KEY (`grade_level_id`) REFERENCES `tblgrade_levels` (`grade_id`);

--
-- Constraints for table `tblstudents`
--
ALTER TABLE `tblstudents`
  ADD CONSTRAINT `fk_tblsection11` FOREIGN KEY (`section_id`) REFERENCES `tblsections` (`section_id`);

--
-- Constraints for table `tblsubjects`
--
ALTER TABLE `tblsubjects`
  ADD CONSTRAINT `fktbl_strands2` FOREIGN KEY (`strand_id`) REFERENCES `tblstrands` (`strand_id`);

--
-- Constraints for table `tblteachers_courses`
--
ALTER TABLE `tblteachers_courses`
  ADD CONSTRAINT `fk_tbl_grade_levels1` FOREIGN KEY (`grade_id`) REFERENCES `tblgrade_levels` (`grade_id`),
  ADD CONSTRAINT `fk_tblsection1` FOREIGN KEY (`section_id`) REFERENCES `tblsections` (`section_id`),
  ADD CONSTRAINT `fk_tblstrands1` FOREIGN KEY (`strand_id`) REFERENCES `tblstrands` (`strand_id`),
  ADD CONSTRAINT `fk_tblsubject` FOREIGN KEY (`subject_id`) REFERENCES `tblsubjects` (`subject_id`),
  ADD CONSTRAINT `fk_tblusers` FOREIGN KEY (`user_id`) REFERENCES `tblusers` (`id`);

--
-- Constraints for table `tblteacher_education`
--
ALTER TABLE `tblteacher_education`
  ADD CONSTRAINT `fk_teacher_education_user` FOREIGN KEY (`user_id`) REFERENCES `tblusers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tblteacher_settings`
--
ALTER TABLE `tblteacher_settings`
  ADD CONSTRAINT `fk_teacher_settings` FOREIGN KEY (`teacher_id`) REFERENCES `tblusers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbluser_sessions`
--
ALTER TABLE `tbluser_sessions`
  ADD CONSTRAINT `tbluser_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tblusers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tblvirtualclasses`
--
ALTER TABLE `tblvirtualclasses`
  ADD CONSTRAINT `fk_tblteachers_subject1` FOREIGN KEY (`teacher_subject_id`) REFERENCES `tbl_teacher_subjects` (`teacher_subject_id`),
  ADD CONSTRAINT `fk_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `tblusers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tblvirtualclass_participants`
--
ALTER TABLE `tblvirtualclass_participants`
  ADD CONSTRAINT `tblvirtualclass` FOREIGN KEY (`class_id`) REFERENCES `tblvirtualclasses` (`id`),
  ADD CONSTRAINT `tblvirtualclass_participants_ibfk_1` FOREIGN KEY (`teacher_subject_id`) REFERENCES `tbl_teacher_subjects` (`teacher_subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tblvirtualclass_participants_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `tblstudents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tblvirtualclass_recordings`
--
ALTER TABLE `tblvirtualclass_recordings`
  ADD CONSTRAINT `fk_tbl_virtual_class` FOREIGN KEY (`class_id`) REFERENCES `tblvirtualclasses` (`id`),
  ADD CONSTRAINT `fk_tblusers5` FOREIGN KEY (`teacher_id`) REFERENCES `tblusers` (`id`);

--
-- Constraints for table `tbl_students_settings`
--
ALTER TABLE `tbl_students_settings`
  ADD CONSTRAINT `tbl_students_settings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `tblstudents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_teachers_students`
--
ALTER TABLE `tbl_teachers_students`
  ADD CONSTRAINT `fk_tblstudents1` FOREIGN KEY (`student_id`) REFERENCES `tblstudents` (`id`),
  ADD CONSTRAINT `fk_tblteachers_courses1` FOREIGN KEY (`teacher_subject_id`) REFERENCES `tbl_teacher_subjects` (`teacher_subject_id`),
  ADD CONSTRAINT `fk_tblusers1` FOREIGN KEY (`teacher_id`) REFERENCES `tblusers` (`id`);

--
-- Constraints for table `tbl_teacher_lessons`
--
ALTER TABLE `tbl_teacher_lessons`
  ADD CONSTRAINT `fktbl_teacher_subjects` FOREIGN KEY (`teacher_subject_id`) REFERENCES `tbl_teacher_subjects` (`teacher_subject_id`);

--
-- Constraints for table `tbl_teacher_subjects`
--
ALTER TABLE `tbl_teacher_subjects`
  ADD CONSTRAINT `fk_tblsubjects11` FOREIGN KEY (`subject_id`) REFERENCES `tblsubjects` (`subject_id`),
  ADD CONSTRAINT `fk_tblteacher11` FOREIGN KEY (`teacher_id`) REFERENCES `tblusers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
