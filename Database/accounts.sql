-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2025 at 04:05 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `accounts`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_submissions`
--

CREATE TABLE `activity_submissions` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `submission_content` longtext DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `submission_url` varchar(255) DEFAULT NULL,
  `points_earned` int(11) DEFAULT NULL,
  `max_points` int(11) NOT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('draft','submitted','graded','returned') NOT NULL DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_attempts`
--

CREATE TABLE `assessment_attempts` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `points_earned` int(11) DEFAULT NULL,
  `total_points` int(11) NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `passed` tinyint(1) DEFAULT 0,
  `time_spent_minutes` int(11) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` enum('in_progress','completed','abandoned','expired') NOT NULL DEFAULT 'in_progress',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `checked_in` datetime DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `sign_in_status` enum('signedIn','signedOut') DEFAULT 'signedOut'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `checked_in`, `checked_out`, `sign_in_status`) VALUES
(8, 1, '2024-05-13 19:01:56', '2024-05-13 20:51:33', 'signedOut'),
(9, 8, '2024-05-13 19:03:20', '2024-05-13 20:41:44', 'signedOut'),
(10, 7, '2024-05-13 20:11:19', '2024-05-13 20:41:45', 'signedOut'),
(11, 9, '2024-05-13 20:42:36', '2024-05-13 20:42:41', 'signedOut'),
(12, 9, '2024-05-14 06:57:03', '2024-05-14 14:25:27', 'signedOut'),
(13, 9, '2024-05-14 12:54:12', '2024-05-14 14:25:27', 'signedOut'),
(14, 1, '2024-05-14 12:55:16', '2024-05-14 15:04:33', 'signedOut'),
(15, 8, '2024-05-14 12:55:37', '2024-05-14 12:56:19', 'signedOut'),
(16, 7, '2024-05-14 12:56:02', '2024-05-14 12:56:22', 'signedOut'),
(17, 9, '2024-05-14 14:25:22', '2024-05-14 14:25:27', 'signedOut'),
(18, 1, '2024-05-14 15:04:29', '2024-05-14 15:04:33', 'signedOut'),
(19, 9, '2024-05-15 11:21:15', '2024-05-15 11:22:31', 'signedOut'),
(20, 9, '2024-05-20 22:00:49', '2024-05-20 22:02:25', 'signedOut'),
(21, 1, '2024-05-27 21:28:50', '2024-05-27 22:02:10', 'signedOut'),
(22, 9, '2024-05-27 21:29:10', '2024-05-27 22:03:06', 'signedOut'),
(23, 8, '2024-05-27 22:02:01', '2024-05-27 22:02:59', 'signedOut'),
(24, 7, '2024-05-27 14:30:20', '2024-05-28 00:40:11', 'signedOut'),
(25, 1, '2024-06-02 11:42:58', '2024-06-02 11:44:07', 'signedOut'),
(26, 9, '2024-06-02 11:43:10', '2024-06-02 11:44:09', 'signedOut'),
(27, 1, '2024-06-03 21:22:00', '2024-06-03 21:57:25', 'signedOut'),
(28, 9, '2024-06-03 21:26:57', '2024-06-03 21:57:07', 'signedOut'),
(29, 8, '2024-06-03 21:30:16', '2024-06-03 21:56:50', 'signedOut'),
(30, 7, '2024-06-03 21:32:05', '2024-06-03 21:56:12', 'signedOut'),
(31, 9, '2024-06-03 21:35:03', '2024-06-03 21:57:07', 'signedOut'),
(32, 8, '2024-06-03 21:36:57', '2024-06-03 21:56:50', 'signedOut'),
(33, 1, '2024-06-03 21:37:25', '2024-06-03 21:57:25', 'signedOut'),
(34, 9, '2024-06-03 21:37:59', '2024-06-03 21:57:07', 'signedOut'),
(35, 8, '2024-06-03 21:39:54', '2024-06-03 21:56:50', 'signedOut'),
(36, 9, '2024-06-03 21:41:14', '2024-06-03 21:57:07', 'signedOut'),
(37, 8, '2024-06-03 21:49:45', '2024-06-03 21:56:50', 'signedOut'),
(38, 7, '2024-06-03 21:50:08', '2024-06-03 21:56:12', 'signedOut'),
(39, 8, '2024-06-03 21:53:12', '2024-06-03 21:56:50', 'signedOut'),
(40, 9, '2024-06-03 21:54:02', '2024-06-03 21:57:07', 'signedOut'),
(41, 7, '2024-06-03 21:54:33', '2024-06-03 21:56:12', 'signedOut'),
(42, 8, '2024-06-03 21:54:56', '2024-06-03 21:56:50', 'signedOut'),
(43, 9, '2024-06-03 21:55:30', '2024-06-03 21:57:07', 'signedOut'),
(44, 1, '2024-06-03 21:55:49', '2024-06-03 21:57:25', 'signedOut'),
(45, 7, '2024-06-03 21:56:09', '2024-06-03 21:56:12', 'signedOut'),
(46, 8, '2024-06-03 21:56:47', '2024-06-03 21:56:50', 'signedOut'),
(47, 9, '2024-06-03 21:57:05', '2024-06-03 21:57:07', 'signedOut'),
(48, 1, '2024-06-03 21:57:22', '2024-06-03 21:57:25', 'signedOut'),
(49, 1, '2025-02-17 11:02:06', '2025-02-17 11:04:55', 'signedOut'),
(50, 1, '2025-05-06 16:14:37', '2025-05-06 21:03:37', 'signedOut'),
(51, 10, '2025-05-06 16:15:03', '2025-05-06 21:03:38', 'signedOut');

-- --------------------------------------------------------

--
-- Table structure for table `clubhouse_programs`
--

CREATE TABLE `clubhouse_programs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `learning_outcomes` text DEFAULT NULL,
  `target_age_group` varchar(50) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `materials_needed` text DEFAULT NULL,
  `difficulty_level` enum('Beginner','Intermediate','Advanced') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clubhouse_programs`
--

INSERT INTO `clubhouse_programs` (`id`, `title`, `description`, `learning_outcomes`, `target_age_group`, `duration`, `max_participants`, `materials_needed`, `difficulty_level`, `created_at`, `updated_at`) VALUES
(1, 'FTC', 'Robotics Competition for members between the ages of 12 - 18 years old. The FTC Season starts in September and ends in April.', 'Engineering\r\nMechanical Design\r\nMarketing \r\nLogistics\r\nPresentation skills', '12-18 years', '8 months', 10, 'FTC REV Robotics Starter Kit\r\nFTC REV Robotics Expansion kit\r\n', 'Advanced', '2024-09-13 10:24:39', '2024-09-13 12:39:11'),
(2, 'Robotics Workshop', 'Hands-on experience with building and programming robots', 'Basic robotics principles and programming skills', '12-16 years', '2 hours', 10, 'Robot kits, laptops', 'Intermediate', '2024-09-13 10:24:39', '2024-09-13 10:24:39'),
(3, 'Digital Art Creation', 'Explore digital art tools and techniques', 'Proficiency in digital drawing and image editing', '10-14 years', '1.5 hours', 12, 'Tablets with drawing apps', 'Beginner', '2024-09-13 10:24:39', '2024-09-13 10:24:39'),
(4, 'FLL', 'FLL is great', 'Coding and Robotics', '9-16 years', '6 Months', 10, '0', 'Beginner', '2024-09-13 14:44:13', '2024-09-13 14:44:13'),
(5, 'FLL', 'FLL is great', 'Coding and Robotics', '9-16 years', '6 Months', 10, '0', 'Beginner', '2024-09-13 14:47:07', '2024-09-13 14:47:07');

-- --------------------------------------------------------

--
-- Table structure for table `clubhouse_reports`
--

CREATE TABLE `clubhouse_reports` (
  `id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `participants` int(11) NOT NULL,
  `narrative` text DEFAULT NULL,
  `challenges` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clubhouse_reports`
--

INSERT INTO `clubhouse_reports` (`id`, `program_name`, `participants`, `narrative`, `challenges`, `image_path`, `created_at`) VALUES
(1, '1', 4, 'FTC is nice', 'internet please', '2024-09/66e448b212080_FWAC0DJIDYKNTLY.jpg', '2024-09-13 14:14:10'),
(2, '1', 3, 'FTC always the best', 'Internet as always', '2024-09/66e459fca872b_FWAC0DJIDYKNTLY.jpg', '2024-09-13 15:27:56'),
(3, '3', 24, 'Summer camp', 'Hot', '2024-09/66e889ae59f47_pikaso_texttoimage_Futuristic-robot-cyberspace-digital-world-revoluti.jpeg', '2024-09-16 19:40:30');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `learning_objectives` text DEFAULT NULL,
  `course_requirements` text DEFAULT NULL,
  `prerequisites` text DEFAULT NULL,
  `completion_criteria` text DEFAULT NULL,
  `certification_criteria` text DEFAULT NULL,
  `pass_percentage` decimal(5,2) DEFAULT 70.00,
  `type` enum('full_course','short_course','lesson','skill_activity') NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'General',
  `difficulty_level` enum('Beginner','Intermediate','Advanced') NOT NULL DEFAULT 'Beginner',
  `duration` varchar(50) DEFAULT NULL,
  `estimated_duration_hours` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `enrollment_count` int(11) NOT NULL DEFAULT 0,
  `max_enrollments` int(11) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','draft','archived') NOT NULL DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `last_updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `title`, `description`, `learning_objectives`, `course_requirements`, `prerequisites`, `completion_criteria`, `certification_criteria`, `pass_percentage`, `type`, `category`, `difficulty_level`, `duration`, `estimated_duration_hours`, `image_path`, `thumbnail_path`, `enrollment_count`, `max_enrollments`, `display_order`, `is_featured`, `is_published`, `status`, `created_by`, `last_updated_by`, `created_at`, `updated_at`) VALUES
(1, '', 'Introduction to Robotics', 'Learn the basics of robotics programming and design. This course covers fundamental concepts of robotics, including sensors, motors, and programming for autonomous behavior.', NULL, NULL, NULL, NULL, NULL, 70.00, 'full_course', 'General', 'Beginner', '8 weeks', NULL, NULL, NULL, 0, NULL, 0, 1, 1, 'active', 1, NULL, '2025-05-08 10:59:04', '2025-05-22 12:06:51'),
(10, 'FC-TC', 'Test Course', 'Best Course Ever', NULL, NULL, NULL, NULL, NULL, 70.00, 'full_course', 'General', 'Beginner', '2 weeks', NULL, '682f015feceb6_1747911007.jpeg', NULL, 1, NULL, 0, 0, 0, 'draft', 1, NULL, '2025-05-22 10:50:07', '2025-05-22 11:00:53'),
(11, 'SC-TSC', 'Test Short Course', 'Best Short Course ever!!', NULL, NULL, NULL, NULL, NULL, 70.00, 'short_course', 'General', 'Beginner', '1 week', NULL, '682f02af53c86_1747911343.jpg', NULL, 1, NULL, 0, 0, 0, 'draft', 1, NULL, '2025-05-22 10:55:43', '2025-05-22 11:00:48'),
(12, 'LN-TL', 'Test Lesson', 'Best lesson ever!!', NULL, NULL, NULL, NULL, NULL, 70.00, 'lesson', 'General', 'Beginner', '1 hour', NULL, '682f02fc8ae36_1747911420.png', NULL, 1, NULL, 0, 0, 0, 'draft', 1, NULL, '2025-05-22 10:57:00', '2025-05-22 11:00:40'),
(13, 'SA-TSA', 'Test Skills Activity', 'Best Practical activity ever', NULL, NULL, NULL, NULL, NULL, 70.00, 'skill_activity', 'General', 'Beginner', '1 day', NULL, '682f0354b82a6_1747911508.webp', NULL, 1, NULL, 0, 0, 0, 'draft', 1, NULL, '2025-05-22 10:58:28', '2025-05-22 11:00:30');

-- --------------------------------------------------------

--
-- Table structure for table `course_activities`
--

CREATE TABLE `course_activities` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `lesson_section_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `activity_type` enum('practical','assignment','project','quiz','assessment','skill_exercise') NOT NULL,
  `instructions` longtext DEFAULT NULL,
  `resources_needed` text DEFAULT NULL,
  `estimated_duration_minutes` int(11) DEFAULT NULL,
  `max_points` int(11) DEFAULT 100,
  `pass_points` int(11) DEFAULT 70,
  `submission_type` enum('text','file','link','code','none') DEFAULT 'text',
  `auto_grade` tinyint(1) DEFAULT 0,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_activities`
--

INSERT INTO `course_activities` (`id`, `course_id`, `module_id`, `lesson_id`, `lesson_section_id`, `title`, `description`, `activity_type`, `instructions`, `resources_needed`, `estimated_duration_minutes`, `max_points`, `pass_points`, `submission_type`, `auto_grade`, `order_number`, `is_published`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 10, 1, NULL, NULL, 'First Activity', 'This a the first Activit of the firt module of the first lesso', 'practical', 'Do things', 'you will need things', 60, 100, 80, 'text', 0, 1, 0, '2025-06-06 12:02:00', '2025-05-26 13:49:24', '2025-05-26 13:49:24');

-- --------------------------------------------------------

--
-- Table structure for table `course_assessments`
--

CREATE TABLE `course_assessments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assessment_type` enum('module_quiz','module_exam','course_final','course_project') NOT NULL,
  `total_points` int(11) NOT NULL DEFAULT 100,
  `pass_points` int(11) NOT NULL DEFAULT 70,
  `time_limit_minutes` int(11) DEFAULT NULL,
  `attempts_allowed` int(11) DEFAULT 1,
  `instructions` longtext DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_categories`
--

CREATE TABLE `course_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_category_relationships`
--

CREATE TABLE `course_category_relationships` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_certificates`
--

CREATE TABLE `course_certificates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `certificate_number` varchar(50) NOT NULL,
  `completion_date` date NOT NULL,
  `final_score` decimal(5,2) NOT NULL,
  `grade` enum('A+','A','B+','B','C+','C','D','F') DEFAULT NULL,
  `certificate_template` varchar(255) DEFAULT NULL,
  `verification_code` varchar(100) NOT NULL,
  `issued_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_lessons`
--

CREATE TABLE `course_lessons` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `lesson_objectives` text DEFAULT NULL,
  `lesson_type` enum('text','video','quiz','assignment','interactive') NOT NULL DEFAULT 'text',
  `difficulty_level` enum('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
  `pass_percentage` decimal(5,2) DEFAULT 70.00,
  `prerequisites` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `estimated_duration_minutes` int(11) DEFAULT NULL,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_lessons`
--

INSERT INTO `course_lessons` (`id`, `section_id`, `course_id`, `module_id`, `title`, `content`, `lesson_objectives`, `lesson_type`, `difficulty_level`, `pass_percentage`, `prerequisites`, `video_url`, `duration_minutes`, `estimated_duration_minutes`, `order_number`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, 'What is Robotics?', '<p>This lesson introduces the field of robotics and its applications in the modern world.</p><p>Robotics is an interdisciplinary field that combines mechanical engineering, electrical engineering, and computer science. Robots are designed to assist humans or to perform tasks autonomously.</p><h3>Key Concepts</h3><ul><li>Definition of robots and robotics</li><li>History of robotics development</li><li>Types of robots: industrial, service, educational</li><li>Basic components: sensors, actuators, controllers</li></ul><p>By the end of this course, you will be able to design, build, and program a simple robot that can navigate its environment and perform basic tasks.</p>', NULL, 'text', 'Beginner', 70.00, NULL, NULL, 20, NULL, 1, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(2, 1, NULL, NULL, 'Robot Components Overview', '<p>This lesson covers the essential components that make up a robot.</p><h3>Main Components of a Robot</h3><ol><li><strong>Structure/Frame</strong>: The physical body that supports all other components</li><li><strong>Actuators</strong>: Motors and other devices that create movement</li><li><strong>Sensors</strong>: Devices that collect information about the environment</li><li><strong>Controller</strong>: The \"brain\" (usually a microcontroller) that processes information and controls behavior</li><li><strong>Power Source</strong>: Batteries or other energy sources</li></ol><p>In the next lessons, we will explore each of these components in more detail.</p>', NULL, 'text', 'Beginner', 70.00, NULL, NULL, 25, NULL, 2, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(3, 1, NULL, NULL, 'Introduction to Robot Controllers', '<p>Learn about the various controllers used in robotics, from simple microcontrollers to advanced computing systems.</p><h3>Common Robot Controllers</h3><ul><li>Arduino boards</li><li>Raspberry Pi</li><li>LEGO Mindstorms EV3</li><li>Specialized robotics platforms</li></ul><p>In this course, we will primarily use Arduino for our robotics projects.</p>', NULL, 'text', 'Beginner', 70.00, NULL, NULL, 30, NULL, 3, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(4, 2, NULL, NULL, 'Types of Sensors', '<p>This lesson explores different types of sensors used in robotics.</p><h3>Common Sensor Types</h3><ul><li>Distance sensors (ultrasonic, infrared)</li><li>Light sensors (photoresistors, photodiodes)</li><li>Touch sensors (buttons, switches)</li><li>Color sensors</li><li>Gyroscopes and accelerometers</li></ul><p>Each sensor type provides different kinds of information about the environment.</p>', NULL, 'text', 'Beginner', 70.00, NULL, NULL, 25, NULL, 1, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(5, 2, NULL, NULL, 'Working with Ultrasonic Sensors', '<p>Learn how to use ultrasonic sensors to detect obstacles and measure distances.</p><h3>How Ultrasonic Sensors Work</h3><p>Ultrasonic sensors emit high-frequency sound waves and time how long it takes for the echo to return. This time can be converted to distance.</p><h3>Connecting and Programming</h3><p>We will connect an HC-SR04 ultrasonic sensor to our Arduino and write code to read distance values.</p>', NULL, 'video', 'Beginner', 70.00, NULL, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 40, NULL, 2, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(6, 2, NULL, NULL, 'Sensor Quiz', '<p>Test your knowledge about different types of sensors and their applications in robotics.</p>', NULL, 'quiz', 'Beginner', 70.00, NULL, NULL, 15, NULL, 3, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(7, 3, NULL, NULL, 'DC Motors and Servo Motors', '<p>This lesson covers the differences between DC motors and servo motors, and when to use each.</p><h3>DC Motors</h3><p>Continuous rotation, good for wheels and general movement.</p><h3>Servo Motors</h3><p>Precise position control, good for robotic arms and controlled movements.</p>', NULL, 'text', 'Beginner', 70.00, NULL, NULL, 30, NULL, 1, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(8, 3, NULL, NULL, 'Motor Drivers and Control Circuits', '<p>Learn why motors need special control circuits and how to use motor drivers with your controller.</p><h3>Why Motor Drivers?</h3><p>Most microcontrollers cannot provide enough current to drive motors directly. Motor drivers act as intermediaries that can handle the higher current requirements.</p>', NULL, 'text', 'Beginner', 70.00, NULL, NULL, 35, NULL, 2, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(9, 4, NULL, NULL, 'Introduction to Autonomous Behavior', '<p>This lesson introduces the concept of autonomous behavior in robots.</p><h3>What is Autonomous Behavior?</h3><p>Autonomous behavior refers to a robot\'s ability to perform tasks and make decisions without human intervention, based on sensor input and programmed logic.</p>', NULL, 'text', 'Beginner', 70.00, NULL, NULL, 25, NULL, 1, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(10, 4, NULL, NULL, 'Simple Line Following Robot', '<p>In this lesson, we\'ll create a robot that can follow a line on the ground.</p><h3>Components Needed</h3><ul><li>Chassis with two motors</li><li>Line sensors</li><li>Arduino controller</li><li>Motor driver</li></ul><h3>Line Following Algorithm</h3><p>We\'ll implement a simple algorithm that adjusts the robot\'s direction based on sensor readings.</p>', NULL, 'assignment', 'Beginner', 70.00, NULL, NULL, 60, NULL, 2, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(11, 4, NULL, NULL, 'Final Project: Obstacle Avoiding Robot', '<p>For the final project, you will create a robot that can navigate autonomously while avoiding obstacles.</p><h3>Project Requirements</h3><ul><li>Robot must detect obstacles using sensors</li><li>Robot must make decisions about path changes</li><li>Robot should be able to navigate a simple maze</li></ul><p>You will document your design process and demonstrate your robot\'s capabilities.</p>', NULL, 'assignment', 'Beginner', 70.00, NULL, NULL, 120, NULL, 3, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17');

-- --------------------------------------------------------

--
-- Table structure for table `course_modules`
--

CREATE TABLE `course_modules` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `learning_objectives` text DEFAULT NULL,
  `estimated_duration_hours` int(11) DEFAULT NULL,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `pass_percentage` decimal(5,2) DEFAULT 70.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_modules`
--

INSERT INTO `course_modules` (`id`, `course_id`, `title`, `description`, `learning_objectives`, `estimated_duration_hours`, `order_number`, `is_published`, `pass_percentage`, `created_at`, `updated_at`) VALUES
(1, 10, 'First Module', 'This is the first module of this Course', 'You will learn\r\nYou will also learn', 2, 1, 0, 70.00, '2025-05-26 13:45:45', '2025-05-26 13:45:45'),
(2, 10, 'Second Module', 'This is the Second Module', 'Lean a lot in the second module', 4, 2, 0, 70.00, '2025-05-26 14:23:19', '2025-05-26 14:23:19');

-- --------------------------------------------------------

--
-- Table structure for table `course_prerequisites`
--

CREATE TABLE `course_prerequisites` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `prerequisite_course_id` int(11) NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_quizzes`
--

CREATE TABLE `course_quizzes` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `passing_score` int(11) NOT NULL DEFAULT 70,
  `time_limit_minutes` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_ratings`
--

CREATE TABLE `course_ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_sections`
--

CREATE TABLE `course_sections` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_sections`
--

INSERT INTO `course_sections` (`id`, `course_id`, `title`, `description`, `order_number`, `created_at`, `updated_at`) VALUES
(1, 1, 'Robotics Fundamentals', 'Introduction to basic robotics concepts and components', 1, '2025-05-08 11:01:34', '2025-05-08 11:01:34'),
(2, 1, 'Sensors and Input', 'Working with different types of sensors to gather environmental data', 2, '2025-05-08 11:01:34', '2025-05-08 11:01:34'),
(3, 1, 'Motors and Movement', 'Understanding how to control motors for precise movement', 3, '2025-05-08 11:01:34', '2025-05-08 11:01:34'),
(4, 1, 'Programming Autonomous Behavior', 'Creating programs that allow robots to function independently', 4, '2025-05-08 11:01:34', '2025-05-08 11:01:34');

-- --------------------------------------------------------

--
-- Table structure for table `dell_surveys`
--

CREATE TABLE `dell_surveys` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_time_participation` tinyint(1) DEFAULT NULL,
  `used_computer_first_time` tinyint(1) DEFAULT NULL,
  `showed_tech_to_others` tinyint(1) DEFAULT NULL,
  `used_tech_for_school` tinyint(1) DEFAULT NULL,
  `more_comfortable_teamwork` tinyint(1) DEFAULT NULL,
  `more_confident_sharing` tinyint(1) DEFAULT NULL,
  `thinking_about_staying_in_school` tinyint(1) DEFAULT NULL,
  `interested_in_tech_jobs` tinyint(1) DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_programs`
--

CREATE TABLE `holiday_programs` (
  `id` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `dates` varchar(100) NOT NULL,
  `time` varchar(50) DEFAULT '9:00 AM - 4:00 PM',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `location` varchar(255) DEFAULT 'Sci-Bono Clubhouse',
  `age_range` varchar(50) DEFAULT '13-18 years',
  `lunch_included` tinyint(1) DEFAULT 1,
  `program_goals` text DEFAULT NULL,
  `registration_deadline` varchar(100) DEFAULT NULL,
  `max_participants` int(11) DEFAULT 30,
  `registration_open` tinyint(1) DEFAULT 1,
  `status` enum('draft','open','closing_soon','closed','completed','cancelled') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_programs`
--

INSERT INTO `holiday_programs` (`id`, `term`, `title`, `description`, `dates`, `time`, `start_date`, `end_date`, `location`, `age_range`, `lunch_included`, `program_goals`, `registration_deadline`, `max_participants`, `registration_open`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Term 1', 'Multi-Media - Digital Design', 'Dive into the world of digital media creation, learning graphic design, video editing, and animation techniques.', 'March 31 - April 4, 2025', '9:00 AM - 4:00 PM', '2025-03-29', '2025-04-07', 'Sci-Bono Clubhouse', '13-18 years', 1, NULL, NULL, 35, 0, 'completed', '2025-03-26 19:24:06', '2025-06-06 11:07:39');

-- --------------------------------------------------------

--
-- Stand-in structure for view `holiday_programs_with_status`
-- (See below for the actual view)
--
CREATE TABLE `holiday_programs_with_status` (
`id` int(11)
,`term` varchar(50)
,`title` varchar(255)
,`description` text
,`dates` varchar(100)
,`time` varchar(50)
,`start_date` date
,`end_date` date
,`location` varchar(255)
,`age_range` varchar(50)
,`lunch_included` tinyint(1)
,`program_goals` text
,`registration_deadline` varchar(100)
,`max_participants` int(11)
,`registration_open` tinyint(1)
,`status` enum('draft','open','closing_soon','closed','completed','cancelled')
,`created_at` timestamp
,`updated_at` timestamp
,`display_status` varchar(19)
,`total_registrations` bigint(21)
,`member_registrations` bigint(21)
,`mentor_registrations` bigint(21)
,`workshop_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_attendance`
--

CREATE TABLE `holiday_program_attendance` (
  `id` int(11) NOT NULL,
  `attendee_id` int(11) NOT NULL,
  `workshop_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_attendees`
--

CREATE TABLE `holiday_program_attendees` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other','Prefer not to say') DEFAULT NULL,
  `school` varchar(255) DEFAULT NULL,
  `grade` int(2) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_email` varchar(255) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `workshop_preference` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`workshop_preference`)),
  `why_interested` text DEFAULT NULL,
  `experience_level` varchar(50) DEFAULT NULL,
  `needs_equipment` tinyint(1) DEFAULT 0,
  `medical_conditions` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `photo_permission` tinyint(1) DEFAULT 0,
  `data_permission` tinyint(1) DEFAULT 0,
  `dietary_restrictions` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `registration_status` enum('pending','confirmed','canceled','waitlisted','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `mentor_registration` tinyint(1) DEFAULT 0,
  `mentor_status` enum('Pending','Approved','Declined') DEFAULT NULL,
  `mentor_workshop_preference` int(11) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `is_clubhouse_member` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_attendees`
--

INSERT INTO `holiday_program_attendees` (`id`, `program_id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `gender`, `school`, `grade`, `address`, `city`, `province`, `postal_code`, `guardian_name`, `guardian_relationship`, `guardian_phone`, `guardian_email`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `workshop_preference`, `why_interested`, `experience_level`, `needs_equipment`, `medical_conditions`, `allergies`, `photo_permission`, `data_permission`, `dietary_restrictions`, `additional_notes`, `registration_status`, `created_at`, `updated_at`, `mentor_registration`, `mentor_status`, `mentor_workshop_preference`, `password`, `last_login`, `is_clubhouse_member`) VALUES
(1, 1, 1, 'Vuyani', 'Magibisela', 'vuyani.magibisela@sci-bono.co.za', '638393157', '2010-08-23', 'Male', '0', 12, '123 Gull Street', 'Johannesburg', 'Gauteng', '2021', 'Mandisa', 'Mother', '0721166543', 'mandi@gmail.com', '', '', '', '[\"3\",\"4\"]', 'Learn', '0', 1, 'No', '0', 1, 1, 'No', 'Learn', 'confirmed', '2025-03-28 16:40:40', '2025-06-06 14:45:48', 0, NULL, NULL, NULL, NULL, 0),
(2, 1, 7, 'Sam', 'Kabanga', 'sam@example.com', '688965565', '2025-02-21', 'Male', NULL, NULL, '123 Good Street', 'Johannesburg', '', '1920', NULL, NULL, NULL, NULL, 'Thabo', 'Uncle', '0832342342', '[]', 'sdf', 'Advanced', 0, 'dssdf', 'sd', 1, 1, 'dssd', 'sd', 'confirmed', '2025-03-30 14:48:38', '2025-06-06 14:46:01', 1, 'Approved', 4, NULL, NULL, 0),
(3, 1, 2, 'Itumeleng', 'Kgakane', 'itum@gmail.com', '0', '2012-01-12', 'Male', 'Fernadale High School', 12, '123 Good Street', 'Johannesburg', 'Gauteng', '1920', 'Mandi', 'Mother', '736933940', 'mandi@gmail.com', '', '', '', '[\"3\",\"4\"]', 'df', 'Beginner', 0, 'fsd', 'sdf', 1, 1, 'sd', 'sdf', 'pending', '2025-03-30 15:31:51', '2025-03-30 15:31:51', 0, NULL, NULL, NULL, NULL, 0),
(4, 1, 8, 'Jabu', 'Khumalo', 'jabut@example.com', '0', '2012-02-21', 'Male', 'Fernadale High School', 12, '123 Main St', 'Johannesburg', 'Gauteng', '2021', 'Mandisa', 'Mother', '0721166543', 'mandi@gmail.com', '', '', '', '[\"1\",\"2\"]', 'ds', 'Basic', 0, 'sd', 'fds', 1, 1, 'ds', 'sda', 'confirmed', '2025-03-30 16:35:22', '2025-06-06 14:46:44', 0, NULL, NULL, NULL, NULL, 0),
(5, 1, NULL, 'Noma', 'Mabasa', 'noma@gmail.com', '0012000', '2012-02-16', 'Male', 'Fernadale High School', 10, '123 Main St', 'Johannesburg', 'Gauteng', '2021', 'Vuyani', 'Father', '0638393157', 'vuyani@gmail.com', '', '', '', '[\"4\",\"1\"]', 'Love 3D animations', 'Intermediate', 0, 'No', 'No', 1, 1, 'No', 'Want to have fun and learn.', 'confirmed', '2025-03-30 17:09:01', '2025-06-06 10:28:31', 0, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_criteria`
--

CREATE TABLE `holiday_program_criteria` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `criterion` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order_number` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_criteria`
--

INSERT INTO `holiday_program_criteria` (`id`, `program_id`, `criterion`, `description`, `order_number`, `created_at`) VALUES
(1, 1, 'Technical Execution', 'Quality of technical skills demonstrated', 1, '2025-03-31 15:45:48'),
(2, 1, 'Creativity', 'Original ideas and creative approach', 2, '2025-03-31 15:45:48'),
(3, 1, 'Message', 'Clear connection to SDGs and effective communication of message', 3, '2025-03-31 15:45:48'),
(4, 1, 'Completion', 'Level of completion and polish', 4, '2025-03-31 15:45:48'),
(5, 1, 'Presentation', 'Quality of showcase presentation', 5, '2025-03-31 15:45:48');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_faqs`
--

CREATE TABLE `holiday_program_faqs` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `order_number` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_faqs`
--

INSERT INTO `holiday_program_faqs` (`id`, `program_id`, `question`, `answer`, `order_number`, `created_at`) VALUES
(1, 1, 'Do I need prior experience to participate?', 'No prior experience is necessary. Our workshops are designed for beginners, though those with experience will also benefit and can work on more advanced projects.', 1, '2025-03-31 15:29:26'),
(2, 1, 'Can I switch workshops during the week?', 'Due to the progressive nature of the workshops, participants are encouraged to stay with their assigned workshop throughout the week. However, mentors may arrange collaborative activities between workshops.', 2, '2025-03-31 15:29:26'),
(3, 1, 'What are the UN Sustainable Development Goals?', 'The UN Sustainable Development Goals are a collection of 17 interlinked global goals designed to be a \"blueprint to achieve a better and more sustainable future for all.\" You\'ll learn more about these goals on the first day of the program.', 3, '2025-03-31 15:29:26'),
(4, 1, 'Will I need my own laptop or equipment?', 'No, all necessary equipment will be provided at the Clubhouse. If you have special accessibility needs, please let us know during registration.', 4, '2025-03-31 15:29:26'),
(5, 1, 'What happens if I can\'t attend all five days?', 'We encourage full attendance to get the most out of the program. If you must miss a day, please notify us in advance, and your mentor will provide materials to help you catch up.', 5, '2025-03-31 15:29:26');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_items`
--

CREATE TABLE `holiday_program_items` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `item` varchar(255) NOT NULL,
  `order_number` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_items`
--

INSERT INTO `holiday_program_items` (`id`, `program_id`, `item`, `order_number`, `created_at`) VALUES
(1, 1, 'Notebook and pen/pencil', 1, '2025-03-31 15:40:16'),
(2, 1, 'Snacks (lunch will be provided)', 2, '2025-03-31 15:40:16'),
(3, 1, 'Water bottle', 3, '2025-03-31 15:40:16'),
(4, 1, 'Enthusiasm and creativity!', 4, '2025-03-31 15:40:16');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_mentor_details`
--

CREATE TABLE `holiday_program_mentor_details` (
  `id` int(11) NOT NULL,
  `attendee_id` int(11) NOT NULL,
  `experience` text DEFAULT NULL,
  `availability` varchar(50) DEFAULT NULL,
  `workshop_preference` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_mentor_details`
--

INSERT INTO `holiday_program_mentor_details` (`id`, `attendee_id`, `experience`, `availability`, `workshop_preference`, `notes`, `created_at`) VALUES
(1, 2, 'sdf', 'full_time', 4, NULL, '2025-03-30 15:27:08');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_projects`
--

CREATE TABLE `holiday_program_projects` (
  `id` int(11) NOT NULL,
  `attendee_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `workshop_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `feedback` text DEFAULT NULL,
  `rating` int(1) DEFAULT NULL,
  `status` enum('submitted','reviewed','approved','featured') DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_reports`
--

CREATE TABLE `holiday_program_reports` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `total_attendees` int(11) DEFAULT 0,
  `male_attendees` int(11) DEFAULT 0,
  `female_attendees` int(11) DEFAULT 0,
  `other_attendees` int(11) DEFAULT 0,
  `age_groups` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`age_groups`)),
  `grade_distribution` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`grade_distribution`)),
  `workshop_attendance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`workshop_attendance`)),
  `narrative` text DEFAULT NULL,
  `challenges` text DEFAULT NULL,
  `outcomes` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_requirements`
--

CREATE TABLE `holiday_program_requirements` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `requirement` text NOT NULL,
  `order_number` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_requirements`
--

INSERT INTO `holiday_program_requirements` (`id`, `program_id`, `requirement`, `order_number`, `created_at`) VALUES
(1, 1, 'All projects must address at least one UN Sustainable Development Goal', 1, '2025-03-31 15:47:23'),
(2, 1, 'Projects must be completed by the end of the program', 2, '2025-03-31 15:47:23'),
(3, 1, 'Each participant/team must prepare a brief presentation for the showcase', 3, '2025-03-31 15:47:23'),
(4, 1, 'Projects should demonstrate application of skills learned during the program', 4, '2025-03-31 15:47:23');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_schedules`
--

CREATE TABLE `holiday_program_schedules` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `day_number` int(11) NOT NULL,
  `day_name` varchar(50) NOT NULL,
  `date` date DEFAULT NULL,
  `theme` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_schedules`
--

INSERT INTO `holiday_program_schedules` (`id`, `program_id`, `day_number`, `day_name`, `date`, `theme`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Monday', '2025-03-31', 'Introduction & Fundamentals', '2025-03-31 15:56:28', '2025-03-31 15:56:28'),
(2, 1, 2, 'Tuesday', '2025-04-01', 'Skill Development', '2025-03-31 15:56:28', '2025-03-31 15:56:28'),
(3, 1, 3, 'Wednesday', '2025-04-02', 'Project Development', '2025-03-31 15:56:28', '2025-03-31 15:56:28'),
(4, 1, 4, 'Thursday', '2025-04-03', 'Project Refinement', '2025-03-31 15:56:28', '2025-03-31 15:56:28'),
(5, 1, 5, 'Friday', '2025-04-04', 'Showcase & Celebration', '2025-03-31 15:56:28', '2025-03-31 15:56:28');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_schedule_items`
--

CREATE TABLE `holiday_program_schedule_items` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `activity` text NOT NULL,
  `session_type` enum('morning','afternoon') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_schedule_items`
--

INSERT INTO `holiday_program_schedule_items` (`id`, `schedule_id`, `time_slot`, `activity`, `session_type`, `created_at`) VALUES
(1, 1, '9:00 - 9:30', 'Welcome and program overview for all participants', 'morning', '2025-03-31 15:57:58'),
(2, 1, '9:30 - 10:00', 'Introduction to UN Sustainable Development Goals', 'morning', '2025-03-31 15:57:58'),
(3, 1, '10:15 - 12:00', 'Workshop-specific introductions and skill assessments', 'morning', '2025-03-31 15:57:58'),
(4, 1, '1:00 - 2:30', 'Software introduction and basic skills training', 'afternoon', '2025-03-31 15:57:58'),
(5, 1, '2:45 - 3:45', 'Brainstorming session for projects addressing SDGs', 'afternoon', '2025-03-31 15:57:58'),
(6, 2, '9:15 - 10:45', 'Core skills development session I', 'morning', '2025-03-31 15:57:58'),
(7, 2, '11:00 - 12:00', 'Core skills development session II', 'morning', '2025-03-31 15:57:58'),
(8, 2, '1:00 - 2:30', 'Project planning and initial development', 'afternoon', '2025-03-31 15:57:58'),
(9, 2, '2:45 - 3:45', 'Continued skills development and application', 'afternoon', '2025-03-31 15:57:58'),
(10, 3, '9:15 - 10:45', 'Advanced techniques instruction', 'morning', '2025-03-31 15:57:58'),
(11, 3, '11:00 - 12:00', 'Project work with mentor guidance', 'morning', '2025-03-31 15:57:58'),
(12, 3, '1:00 - 2:30', 'Continued project development', 'afternoon', '2025-03-31 15:57:58'),
(13, 3, '2:45 - 3:45', 'Mid-week project review and feedback', 'afternoon', '2025-03-31 15:57:58'),
(14, 4, '9:15 - 10:45', 'Project refinement and advanced techniques', 'morning', '2025-03-31 15:57:58'),
(15, 4, '11:00 - 12:00', 'Problem-solving workshop for project challenges', 'morning', '2025-03-31 15:57:58'),
(16, 4, '1:00 - 2:30', 'Final project development', 'afternoon', '2025-03-31 15:57:58'),
(17, 4, '2:45 - 3:45', 'Project finalization and preparing for showcase', 'afternoon', '2025-03-31 15:57:58'),
(18, 5, '9:15 - 10:45', 'Project completion and showcase preparation', 'morning', '2025-03-31 15:57:58'),
(19, 5, '11:00 - 12:00', 'Project final touches and presentation preparation', 'morning', '2025-03-31 15:57:58'),
(20, 5, '1:00 - 3:00', 'Showcase event (open to parents and other Clubhouse members)', 'afternoon', '2025-03-31 15:57:58'),
(21, 5, '3:00 - 3:30', 'Recognition and certificates', 'afternoon', '2025-03-31 15:57:58'),
(22, 5, '3:30 - 4:00', 'Program conclusion and future opportunities at the Clubhouse', 'afternoon', '2025-03-31 15:57:58');

-- --------------------------------------------------------

--
-- Stand-in structure for view `holiday_program_stats`
-- (See below for the actual view)
--
CREATE TABLE `holiday_program_stats` (
`id` int(11)
,`term` varchar(50)
,`title` varchar(255)
,`dates` varchar(100)
,`registration_open` tinyint(1)
,`status` enum('draft','open','closing_soon','closed','completed','cancelled')
,`max_participants` int(11)
,`total_registrations` bigint(21)
,`member_count` bigint(21)
,`mentor_count` bigint(21)
,`confirmed_count` bigint(21)
,`pending_count` bigint(21)
,`workshop_count` bigint(21)
,`capacity_percentage` decimal(25,1)
);

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_workshops`
--

CREATE TABLE `holiday_program_workshops` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `max_participants` int(11) DEFAULT 15,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_workshops`
--

INSERT INTO `holiday_program_workshops` (`id`, `program_id`, `title`, `description`, `instructor`, `max_participants`, `start_time`, `end_time`, `location`, `created_at`, `updated_at`) VALUES
(1, 1, 'Graphic Design Basics', 'Learn the fundamentals of graphic design using industry tools.', 'Carols Kanye', 10, NULL, NULL, NULL, '2025-03-26 19:24:54', '2025-03-31 19:31:53'),
(2, 1, 'Music and Video Production', 'Create and edit Music and videos using professional techniques.', 'Andrew Klaas and Philani Mbatha', 10, NULL, NULL, NULL, '2025-03-26 19:24:54', '2025-03-31 19:33:04'),
(3, 1, '3D Design Fundamentals', 'Explore the principles of 3D Design and create your 3D visualizations.', 'Samuel Kazadi ', 5, NULL, NULL, NULL, '2025-03-26 19:24:54', '2025-03-31 19:32:24'),
(4, 1, 'Animation Fundamentals', 'Explore the principles of animation and create your animated shorts.', 'Vuyani Magibisela', 5, NULL, NULL, NULL, '2025-03-26 19:24:54', '2025-03-31 19:33:24');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_report_images`
--

CREATE TABLE `holiday_report_images` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_workshop_enrollment`
--

CREATE TABLE `holiday_workshop_enrollment` (
  `id` int(11) NOT NULL,
  `attendee_id` int(11) NOT NULL,
  `workshop_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `attendance_status` enum('registered','attended','absent','excused') DEFAULT 'registered',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `status` enum('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  `progress` float NOT NULL DEFAULT 0,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completion_date` timestamp NULL DEFAULT NULL,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_sections`
--

CREATE TABLE `lesson_sections` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `section_type` enum('text','video','interactive','quiz','assignment') NOT NULL DEFAULT 'text',
  `video_url` varchar(255) DEFAULT NULL,
  `estimated_duration_minutes` int(11) DEFAULT NULL,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_reports`
--

CREATE TABLE `monthly_reports` (
  `id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `total_attendees` int(11) NOT NULL DEFAULT 0,
  `male_attendees` int(11) NOT NULL DEFAULT 0,
  `female_attendees` int(11) NOT NULL DEFAULT 0,
  `age_groups` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`age_groups`)),
  `narrative` text DEFAULT NULL,
  `challenges` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monthly_reports`
--

INSERT INTO `monthly_reports` (`id`, `report_date`, `total_attendees`, `male_attendees`, `female_attendees`, `age_groups`, `narrative`, `challenges`, `created_at`, `updated_at`) VALUES
(1, '2025-01-01', 0, 0, 0, '{\"9-12\":\"0\",\"12-14\":\"0\",\"14-16\":\"0\",\"16-18\":\"0\"}', 'The month was great....', 'Lack of Computers.', '2025-03-04 12:49:22', '2025-03-04 12:49:22'),
(2, '2025-03-01', 0, 0, 0, '{\"9-12\":\"0\",\"12-14\":\"0\",\"14-16\":\"0\",\"16-18\":\"0\"}', 'Test', 'The challenges', '2025-04-01 11:40:11', '2025-04-01 11:40:11'),
(3, '2025-02-01', 1, 1, 0, '{\"9-12\":\"0\",\"12-14\":\"0\",\"14-16\":\"0\",\"16-18\":\"0\"}', 'Fab Narative', 'Challenges', '2025-04-01 12:37:47', '2025-04-01 12:37:47');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_report_activities`
--

CREATE TABLE `monthly_report_activities` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `participants` int(11) NOT NULL DEFAULT 0,
  `completed_projects` int(11) NOT NULL DEFAULT 0,
  `in_progress_projects` int(11) NOT NULL DEFAULT 0,
  `not_started_projects` int(11) NOT NULL DEFAULT 0,
  `narrative` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monthly_report_activities`
--

INSERT INTO `monthly_report_activities` (`id`, `report_id`, `program_id`, `participants`, `completed_projects`, `in_progress_projects`, `not_started_projects`, `narrative`, `created_at`) VALUES
(1, 1, 1, 23, 5, 2, 0, 'Kids leant a lot', '2025-03-04 12:49:22'),
(2, 1, 3, 23, 12, 5, 0, 'Narroto of the year', '2025-03-04 12:49:22'),
(3, 2, 1, 10, 0, 0, 0, 'Currently Recruiting', '2025-04-01 11:40:11'),
(4, 3, 3, 1, 1, 1, 1, 'Drawing', '2025-04-01 12:37:47');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_report_images`
--

CREATE TABLE `monthly_report_images` (
  `id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monthly_report_images`
--

INSERT INTO `monthly_report_images` (`id`, `activity_id`, `image_path`, `created_at`) VALUES
(1, 1, '2025-03/67c6f6d2a2270_Screenshot 2024-08-22 104155.png', '2025-03-04 12:49:22'),
(2, 2, '2025-03/67c6f6d2a2aae_Screenshot (1).png', '2025-03-04 12:49:22'),
(3, 3, '2025-04/67ebd09be8bd1_int.jpg', '2025-04-01 11:40:11'),
(4, 3, '2025-04/67ebd09be9a2f_inter.jpg', '2025-04-01 11:40:11'),
(5, 4, '2025-04/67ebde1b70bd5_IMG_4036.JPG', '2025-04-01 12:37:47'),
(6, 4, '2025-04/67ebde1b71c06_IMG_4037.JPG', '2025-04-01 12:37:47');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_answers`
--

CREATE TABLE `quiz_answers` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `score` float NOT NULL DEFAULT 0,
  `percentage` float NOT NULL DEFAULT 0,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `time_spent_seconds` int(11) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','matching') NOT NULL DEFAULT 'multiple_choice',
  `points` int(11) NOT NULL DEFAULT 1,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skill_activities`
--

CREATE TABLE `skill_activities` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `technique_taught` varchar(255) DEFAULT NULL,
  `tools_required` text DEFAULT NULL,
  `materials_needed` text DEFAULT NULL,
  `difficulty_level` enum('Beginner','Intermediate','Advanced') NOT NULL DEFAULT 'Beginner',
  `estimated_duration_minutes` int(11) DEFAULT NULL,
  `final_outcome_description` text DEFAULT NULL,
  `instructions` longtext DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `resources_links` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `completion_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skill_activity_completions`
--

CREATE TABLE `skill_activity_completions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_activity_id` int(11) NOT NULL,
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `current_step` int(11) DEFAULT 1,
  `final_submission_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rating` int(1) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `feedback` text DEFAULT NULL,
  `status` enum('started','in_progress','completed','abandoned') NOT NULL DEFAULT 'started',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skill_activity_steps`
--

CREATE TABLE `skill_activity_steps` (
  `id` int(11) NOT NULL,
  `skill_activity_id` int(11) NOT NULL,
  `step_number` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `instructions` longtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `estimated_duration_minutes` int(11) DEFAULT NULL,
  `tips` text DEFAULT NULL,
  `common_mistakes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(100) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT 'default@example.com',
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `user_type` enum('admin','mentor','member','alumni','community') NOT NULL DEFAULT 'member',
  `date_of_birth` date DEFAULT NULL,
  `Gender` enum('Male','Female','Other') NOT NULL,
  `grade` int(12) DEFAULT NULL,
  `school` varchar(255) DEFAULT NULL,
  `parent` varchar(255) DEFAULT NULL,
  `parent_email` varchar(255) DEFAULT NULL,
  `leaner_number` int(10) DEFAULT NULL,
  `parent_number` int(10) DEFAULT NULL,
  `Relationship` varchar(255) DEFAULT NULL,
  `Center` enum('Sci-Bono Clubhouse','Waverly Girls Solar Lab','Mapetla Solar Lab','Emdeni Solar Lab') NOT NULL DEFAULT 'Sci-Bono Clubhouse',
  `nationality` varchar(100) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `home_language` varchar(50) DEFAULT NULL,
  `address_street` varchar(255) DEFAULT NULL,
  `address_suburb` varchar(255) DEFAULT NULL,
  `address_city` varchar(255) DEFAULT NULL,
  `address_province` varchar(100) DEFAULT NULL,
  `address_postal_code` varchar(20) DEFAULT NULL,
  `medical_aid_name` varchar(100) DEFAULT NULL,
  `medical_aid_holder` varchar(100) DEFAULT NULL,
  `medical_aid_number` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_relationship` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `emergency_contact_email` varchar(255) DEFAULT NULL,
  `emergency_contact_address` text DEFAULT NULL,
  `interests` text DEFAULT NULL,
  `role_models` text DEFAULT NULL,
  `goals` text DEFAULT NULL,
  `has_computer` tinyint(1) DEFAULT NULL,
  `computer_skills` text DEFAULT NULL,
  `computer_skills_source` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `name`, `surname`, `user_type`, `date_of_birth`, `Gender`, `grade`, `school`, `parent`, `parent_email`, `leaner_number`, `parent_number`, `Relationship`, `Center`, `nationality`, `id_number`, `home_language`, `address_street`, `address_suburb`, `address_city`, `address_province`, `address_postal_code`, `medical_aid_name`, `medical_aid_holder`, `medical_aid_number`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `emergency_contact_email`, `emergency_contact_address`, `interests`, `role_models`, `goals`, `has_computer`, `computer_skills`, `computer_skills_source`) VALUES
(1, 'vuyani_magibisela', 'vuyani.magibisela@sci-bono.co.za', '$2y$10$OEkQUqNT9pp8F.oBn/nAquaBuxyo7.8a0QCiWLXw37ECwvzXWNZDy', 'Vuyani', 'Magibisela', 'admin', '1990-08-23', 'Male', NULL, NULL, NULL, NULL, 638393157, NULL, NULL, 'Sci-Bono Clubhouse', 'South African', '9008235531088', 'isiXhosa', '123 Gull Street', 'Soweto', 'Johannesburg', 'Gauteng', '2021', 'Discovery', 'Vuyani Magibisela', '12345685', 'Mandisa', 'Mother', '0726611543', 'mandisa.magibisela@gmail.com', '123 Bob Street \r\nQueens Town\r\nEastern Cape', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'itumeleng_kgakane', 'itum@gmail.com', '$2y$10$cH5JP8jdlORJuJya9qBHGOCrqHzP4gnefjxdQQk3yEqV2SKdsOdgi', 'Itumeleng', 'Kgakane', 'member', '2000-01-12', 'Male', 12, 'Fernadale High School', 'Mandi', 'mandi@gmail.com', 0, 736933940, 'Mother', '', 'South African', '0001125531088', 'Setswana', '123 Good Street', 'Soweto', 'Johannesburg', 'Gauteng', '1920', NULL, NULL, NULL, 'Mandi', 'Mother', '0832342342', 'mandi@gmail.com', '123 Good street, Soweto, Johannesburg, Gauteng, South Africa', 'Sports', 'Khaya', 'Be the best', 1, 'Digital Drawing', 'At home'),
(4, 'themba_magibisela', 'themba@example.co.za', '13378', 'Themba', 'Magibisela', 'mentor', '2018-01-01', 'Male', NULL, NULL, NULL, NULL, 835562525, NULL, NULL, 'Sci-Bono Clubhouse', 'South African', '1801025522088', 'English', '123 Good Street', 'Soweto', 'Johannesburg', '', '2300', NULL, NULL, NULL, 'Mandi', 'Mother', '0721166543', 'mandi@gmail.com', '123 Good Street \r\nSoweto\r\n23000', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Sam_King', 'sam@example.com', '$2y$10$aSK4SUKKNstMnefJ0VKfs.NyBF32SHBC5wv.ALf2w1HvBrK1hLqgK', 'Sam', 'Kabanga', 'mentor', '2025-02-21', 'Male', 9, '0', 'Bonga Kabanga', 'bonga.kabanga@example.com', 688965565, 868963125, 'Father', 'Sci-Bono Clubhouse', 'South African', '', 'isiNdebele', '123 Good Street', 'Soweto', 'Johannesburg', '', '1920', NULL, NULL, NULL, 'Thabo', 'Uncle', '0832342342', 'thabo@gmail.com', '123 Good street', '', '', '', 1, '', ''),
(8, 'jabu_khumalo ', 'jabut@example.com', '$2y$10$n8Pb/bmu/7rO7KJSlXkaFeaTjabFz7anFGZmxtvWmkvADhZVbIh0S', 'Jabu', 'Khumalo', 'mentor', '2025-02-21', 'Male', 0, '0', '0', '0', 0, 0, '', 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'lebo_skhosana', '', '$2y$10$4.fc0AJKIOfj.YPKp0sePOtDllx98DA8yIMl14wv5hptlsDMEY84O', 'Lebo', 'Skhosana', 'admin', '0000-00-00', 'Male', 0, '0', '0', '0', 0, 0, '', 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'Tim_M', '', '$2y$10$5G/8yYmZdhejlA16qu30Q.Bc/k7E8V7KLvUcnNTcT.xKcbSilpNYW', 'Tim', 'Shabango', 'member', '0000-00-00', 'Male', 0, '0', '0', '0', 0, 0, '', 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'linda_skhosana', 'default@example.com', '$2y$10$OuPeaMzVXhi2gNKmiHKcPOZma8MAaDulkn8Q7mINUh4D.LAhsNYle', 'Linda', 'Skhosana', 'member', '2004-09-12', 'Female', 10, 'Malvern High School', 'Mandi', 'mandi@gmail.com', 719948640, 766703421, 'Mother', 'Mapetla Solar Lab', 'South African', '0409120000000', 'Afrikaans', '123 Good Street', 'Soweto', 'Johannesburg', '', '2000', NULL, NULL, NULL, 'Thabo', 'Father', '0716640054', 'thabo@gmail.com', 'address', 'yes', 'yes', 'yes', 1, 'yes', 'yes'),
(12, 'Pammy', 'default@example.com', '$2y$10$XVbGAfHni2g96herJzNxS.vK69a/1GQVLYnskgkPzw0GKskVV5Ql2', 'Pamela Sithandweyinkosi ', 'Ngwenya', 'mentor', NULL, 'Female', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'NkuliTheGreat', 'default@example.com', '$2y$10$IjHh78npTETLCK2kgZ5UR.Anib0Pgjx91eOq//BiKb8I5iZyt6Y9m', 'Nonkululeko ', 'Shongwe ', 'admin', NULL, 'Female', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'Pammy', 'default@example.com', '$2y$10$4naNeRn30g44Fl7KZ0KAzu/bfmsxus8nY/RHowmHgiRgkX7SArqHa', 'Pamela Sithandweyinkosi ', 'Ngwenya', 'mentor', NULL, 'Female', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'Kgotso_Maponya', 'default@example.com', '$2y$10$e000Bj1pepX8f1LIFrZEy.OxJrQJEIVpXgr94efE7qL2ZiGGZFQuW', 'Kgotso', 'Maponya', 'member', NULL, 'Male', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_enrollments`
--

CREATE TABLE `user_enrollments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `progress` float NOT NULL DEFAULT 0,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completion_date` timestamp NULL DEFAULT NULL,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_enrollments`
--

INSERT INTO `user_enrollments` (`id`, `user_id`, `course_id`, `enrollment_date`, `progress`, `completed`, `completion_date`, `last_accessed`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-05-08 11:12:01', 0, 0, NULL, '2025-05-08 11:12:01', '2025-05-08 11:12:01', '2025-05-08 11:12:01'),
(2, 1, 13, '2025-05-22 11:00:30', 0, 0, NULL, '2025-05-22 11:00:30', '2025-05-22 11:00:30', '2025-05-22 11:00:30'),
(3, 1, 12, '2025-05-22 11:00:40', 0, 0, NULL, '2025-05-22 11:00:40', '2025-05-22 11:00:40', '2025-05-22 11:00:40'),
(4, 1, 11, '2025-05-22 11:00:48', 0, 0, NULL, '2025-05-22 11:00:48', '2025-05-22 11:00:48', '2025-05-22 11:00:48'),
(5, 1, 10, '2025-05-22 11:00:53', 0, 0, NULL, '2025-05-22 11:00:53', '2025-05-22 11:00:53', '2025-05-22 11:00:53');

-- --------------------------------------------------------

--
-- Table structure for table `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `lesson_section_id` int(11) DEFAULT NULL,
  `activity_id` int(11) DEFAULT NULL,
  `progress_type` enum('course','module','lesson','lesson_section','activity') NOT NULL,
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `total_points_earned` int(11) DEFAULT 0,
  `total_points_possible` int(11) DEFAULT 0,
  `status` enum('not_started','in_progress','completed','passed','failed') NOT NULL DEFAULT 'not_started',
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_question_answers`
--

CREATE TABLE `user_question_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_id` int(11) DEFAULT NULL,
  `text_answer` text DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `points_earned` float NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitors`
--

CREATE TABLE `visitors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `grade_school` varchar(100) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `parent_name` varchar(100) NOT NULL,
  `parent_surname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitors`
--

INSERT INTO `visitors` (`id`, `name`, `surname`, `age`, `grade_school`, `student_number`, `parent_name`, `parent_surname`, `email`, `phone_number`, `created_at`) VALUES
(1, 'Vuyani', 'Magibisela', 29, 'Ferndale', '', 'Mandisa', 'Magibisela', 'vuyani.magibisela@sci-bono.co.za', '0638393757', '2025-06-05 14:32:10');

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` int(11) NOT NULL,
  `visitor_id` int(11) NOT NULL,
  `sign_in_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `sign_out_time` timestamp NULL DEFAULT NULL,
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `visitor_id`, `sign_in_time`, `sign_out_time`, `comment`) VALUES
(1, 1, '2025-06-05 14:32:22', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure for view `holiday_programs_with_status`
--
DROP TABLE IF EXISTS `holiday_programs_with_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `holiday_programs_with_status`  AS SELECT `p`.`id` AS `id`, `p`.`term` AS `term`, `p`.`title` AS `title`, `p`.`description` AS `description`, `p`.`dates` AS `dates`, `p`.`time` AS `time`, `p`.`start_date` AS `start_date`, `p`.`end_date` AS `end_date`, `p`.`location` AS `location`, `p`.`age_range` AS `age_range`, `p`.`lunch_included` AS `lunch_included`, `p`.`program_goals` AS `program_goals`, `p`.`registration_deadline` AS `registration_deadline`, `p`.`max_participants` AS `max_participants`, `p`.`registration_open` AS `registration_open`, `p`.`status` AS `status`, `p`.`created_at` AS `created_at`, `p`.`updated_at` AS `updated_at`, CASE WHEN `p`.`status` = 'completed' OR `p`.`end_date` < curdate() THEN 'Completed' WHEN `p`.`status` = 'cancelled' THEN 'Cancelled' WHEN `p`.`status` = 'draft' THEN 'Draft' WHEN `p`.`registration_open` = 1 AND `p`.`status` in ('open','closing_soon') THEN 'Registration Open' WHEN `p`.`registration_open` = 0 OR `p`.`status` = 'closed' THEN 'Registration Closed' WHEN `p`.`start_date` > curdate() THEN 'Upcoming' WHEN curdate() between `p`.`start_date` and `p`.`end_date` THEN 'In Progress' ELSE 'Unknown' END AS `display_status`, (select count(0) from `holiday_program_attendees` where `holiday_program_attendees`.`program_id` = `p`.`id`) AS `total_registrations`, (select count(0) from `holiday_program_attendees` where `holiday_program_attendees`.`program_id` = `p`.`id` and `holiday_program_attendees`.`mentor_registration` = 0) AS `member_registrations`, (select count(0) from `holiday_program_attendees` where `holiday_program_attendees`.`program_id` = `p`.`id` and `holiday_program_attendees`.`mentor_registration` = 1) AS `mentor_registrations`, (select count(0) from `holiday_program_workshops` where `holiday_program_workshops`.`program_id` = `p`.`id`) AS `workshop_count` FROM `holiday_programs` AS `p` ;

-- --------------------------------------------------------

--
-- Structure for view `holiday_program_stats`
--
DROP TABLE IF EXISTS `holiday_program_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `holiday_program_stats`  AS SELECT `p`.`id` AS `id`, `p`.`term` AS `term`, `p`.`title` AS `title`, `p`.`dates` AS `dates`, `p`.`registration_open` AS `registration_open`, `p`.`status` AS `status`, `p`.`max_participants` AS `max_participants`, count(distinct `a`.`id`) AS `total_registrations`, count(distinct case when `a`.`mentor_registration` = 0 then `a`.`id` end) AS `member_count`, count(distinct case when `a`.`mentor_registration` = 1 then `a`.`id` end) AS `mentor_count`, count(distinct case when `a`.`registration_status` = 'confirmed' then `a`.`id` end) AS `confirmed_count`, count(distinct case when `a`.`registration_status` = 'pending' then `a`.`id` end) AS `pending_count`, count(distinct `w`.`id`) AS `workshop_count`, round(count(distinct `a`.`id`) / `p`.`max_participants` * 100,1) AS `capacity_percentage` FROM ((`holiday_programs` `p` left join `holiday_program_attendees` `a` on(`p`.`id` = `a`.`program_id`)) left join `holiday_program_workshops` `w` on(`p`.`id` = `w`.`program_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_submissions`
--
ALTER TABLE `activity_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_id` (`activity_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `graded_by` (`graded_by`);

--
-- Indexes for table `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `clubhouse_programs`
--
ALTER TABLE `clubhouse_programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clubhouse_reports`
--
ALTER TABLE `clubhouse_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_course_status` (`status`),
  ADD KEY `idx_course_category` (`category`),
  ADD KEY `idx_course_type` (`type`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `course_activities`
--
ALTER TABLE `course_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `lesson_section_id` (`lesson_section_id`);

--
-- Indexes for table `course_assessments`
--
ALTER TABLE `course_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `course_categories`
--
ALTER TABLE `course_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `course_category_relationships`
--
ALTER TABLE `course_category_relationships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_category_unique` (`course_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `course_certificates`
--
ALTER TABLE `course_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD UNIQUE KEY `verification_code` (`verification_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `issued_by` (`issued_by`);

--
-- Indexes for table `course_lessons`
--
ALTER TABLE `course_lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `course_lessons_module_fk` (`module_id`),
  ADD KEY `course_lessons_course_fk` (`course_id`);

--
-- Indexes for table `course_modules`
--
ALTER TABLE `course_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `course_prerequisites`
--
ALTER TABLE `course_prerequisites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_prerequisite_unique` (`course_id`,`prerequisite_course_id`),
  ADD KEY `prerequisite_course_id` (`prerequisite_course_id`);

--
-- Indexes for table `course_quizzes`
--
ALTER TABLE `course_quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `course_ratings`
--
ALTER TABLE `course_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_course_rating_unique` (`user_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `dell_surveys`
--
ALTER TABLE `dell_surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `holiday_programs`
--
ALTER TABLE `holiday_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_registration_open` (`registration_open`),
  ADD KEY `idx_dates` (`start_date`,`end_date`),
  ADD KEY `idx_term` (`term`);

--
-- Indexes for table `holiday_program_attendance`
--
ALTER TABLE `holiday_program_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendee_id` (`attendee_id`),
  ADD KEY `workshop_id` (`workshop_id`);

--
-- Indexes for table `holiday_program_attendees`
--
ALTER TABLE `holiday_program_attendees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `email` (`email`),
  ADD KEY `fk_mentor_workshop` (`mentor_workshop_preference`),
  ADD KEY `idx_registration_status` (`registration_status`),
  ADD KEY `idx_mentor_registration` (`mentor_registration`),
  ADD KEY `idx_mentor_status` (`mentor_status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `holiday_program_criteria`
--
ALTER TABLE `holiday_program_criteria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `holiday_program_faqs`
--
ALTER TABLE `holiday_program_faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `holiday_program_items`
--
ALTER TABLE `holiday_program_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `holiday_program_mentor_details`
--
ALTER TABLE `holiday_program_mentor_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendee_id` (`attendee_id`),
  ADD KEY `workshop_preference` (`workshop_preference`);

--
-- Indexes for table `holiday_program_projects`
--
ALTER TABLE `holiday_program_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendee_id` (`attendee_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `workshop_id` (`workshop_id`);

--
-- Indexes for table `holiday_program_reports`
--
ALTER TABLE `holiday_program_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `holiday_program_requirements`
--
ALTER TABLE `holiday_program_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `holiday_program_schedules`
--
ALTER TABLE `holiday_program_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `holiday_program_schedule_items`
--
ALTER TABLE `holiday_program_schedule_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `holiday_program_workshops`
--
ALTER TABLE `holiday_program_workshops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `idx_program_id` (`program_id`),
  ADD KEY `idx_instructor` (`instructor`);

--
-- Indexes for table `holiday_report_images`
--
ALTER TABLE `holiday_report_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `holiday_workshop_enrollment`
--
ALTER TABLE `holiday_workshop_enrollment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendee_workshop_unique` (`attendee_id`,`workshop_id`),
  ADD KEY `workshop_id` (`workshop_id`);

--
-- Indexes for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_lesson_unique` (`user_id`,`lesson_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `lesson_sections`
--
ALTER TABLE `lesson_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_month_year` (`report_date`);

--
-- Indexes for table `monthly_report_activities`
--
ALTER TABLE `monthly_report_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_activity_report` (`report_id`),
  ADD KEY `fk_activity_program` (`program_id`);

--
-- Indexes for table `monthly_report_images`
--
ALTER TABLE `monthly_report_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_image_activity` (`activity_id`);

--
-- Indexes for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `skill_activities`
--
ALTER TABLE `skill_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `skill_category` (`skill_category`),
  ADD KEY `difficulty_level` (`difficulty_level`);

--
-- Indexes for table `skill_activity_completions`
--
ALTER TABLE `skill_activity_completions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_skill_activity_unique` (`user_id`,`skill_activity_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `skill_activity_id` (`skill_activity_id`);

--
-- Indexes for table `skill_activity_steps`
--
ALTER TABLE `skill_activity_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `skill_activity_id` (`skill_activity_id`),
  ADD KEY `step_number` (`step_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_enrollments`
--
ALTER TABLE `user_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_course_unique` (`user_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_progress_unique` (`user_id`,`course_id`,`module_id`,`lesson_id`,`lesson_section_id`,`activity_id`,`progress_type`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `lesson_section_id` (`lesson_section_id`),
  ADD KEY `activity_id` (`activity_id`);

--
-- Indexes for table `user_question_answers`
--
ALTER TABLE `user_question_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `answer_id` (`answer_id`);

--
-- Indexes for table `visitors`
--
ALTER TABLE `visitors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_visitor_email` (`email`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visits_visitor_id` (`visitor_id`),
  ADD KEY `idx_visits_sign_in_time` (`sign_in_time`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_submissions`
--
ALTER TABLE `activity_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `clubhouse_programs`
--
ALTER TABLE `clubhouse_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `clubhouse_reports`
--
ALTER TABLE `clubhouse_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `course_activities`
--
ALTER TABLE `course_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `course_assessments`
--
ALTER TABLE `course_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_categories`
--
ALTER TABLE `course_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_category_relationships`
--
ALTER TABLE `course_category_relationships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_certificates`
--
ALTER TABLE `course_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_lessons`
--
ALTER TABLE `course_lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `course_modules`
--
ALTER TABLE `course_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `course_prerequisites`
--
ALTER TABLE `course_prerequisites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_quizzes`
--
ALTER TABLE `course_quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_ratings`
--
ALTER TABLE `course_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_sections`
--
ALTER TABLE `course_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `dell_surveys`
--
ALTER TABLE `dell_surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_programs`
--
ALTER TABLE `holiday_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `holiday_program_attendance`
--
ALTER TABLE `holiday_program_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_program_attendees`
--
ALTER TABLE `holiday_program_attendees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `holiday_program_criteria`
--
ALTER TABLE `holiday_program_criteria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `holiday_program_faqs`
--
ALTER TABLE `holiday_program_faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `holiday_program_items`
--
ALTER TABLE `holiday_program_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `holiday_program_mentor_details`
--
ALTER TABLE `holiday_program_mentor_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `holiday_program_projects`
--
ALTER TABLE `holiday_program_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_program_reports`
--
ALTER TABLE `holiday_program_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_program_requirements`
--
ALTER TABLE `holiday_program_requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `holiday_program_schedules`
--
ALTER TABLE `holiday_program_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `holiday_program_schedule_items`
--
ALTER TABLE `holiday_program_schedule_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `holiday_program_workshops`
--
ALTER TABLE `holiday_program_workshops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `holiday_report_images`
--
ALTER TABLE `holiday_report_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_workshop_enrollment`
--
ALTER TABLE `holiday_workshop_enrollment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson_sections`
--
ALTER TABLE `lesson_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `monthly_report_activities`
--
ALTER TABLE `monthly_report_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `monthly_report_images`
--
ALTER TABLE `monthly_report_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skill_activities`
--
ALTER TABLE `skill_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skill_activity_completions`
--
ALTER TABLE `skill_activity_completions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skill_activity_steps`
--
ALTER TABLE `skill_activity_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_enrollments`
--
ALTER TABLE `user_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_question_answers`
--
ALTER TABLE `user_question_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visitors`
--
ALTER TABLE `visitors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_submissions`
--
ALTER TABLE `activity_submissions`
  ADD CONSTRAINT `activity_submissions_activity_fk` FOREIGN KEY (`activity_id`) REFERENCES `course_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_submissions_grader_fk` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activity_submissions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  ADD CONSTRAINT `assessment_attempts_assessment_fk` FOREIGN KEY (`assessment_id`) REFERENCES `course_assessments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_attempts_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_activities`
--
ALTER TABLE `course_activities`
  ADD CONSTRAINT `course_activities_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_activities_lesson_fk` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_activities_module_fk` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_activities_section_fk` FOREIGN KEY (`lesson_section_id`) REFERENCES `lesson_sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_assessments`
--
ALTER TABLE `course_assessments`
  ADD CONSTRAINT `course_assessments_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_assessments_module_fk` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_categories`
--
ALTER TABLE `course_categories`
  ADD CONSTRAINT `course_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `course_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_category_relationships`
--
ALTER TABLE `course_category_relationships`
  ADD CONSTRAINT `course_category_relationships_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_category_relationships_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `course_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_certificates`
--
ALTER TABLE `course_certificates`
  ADD CONSTRAINT `course_certificates_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_certificates_issuer_fk` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `course_certificates_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_lessons`
--
ALTER TABLE `course_lessons`
  ADD CONSTRAINT `course_lessons_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_lessons_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_lessons_module_fk` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_modules`
--
ALTER TABLE `course_modules`
  ADD CONSTRAINT `course_modules_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_prerequisites`
--
ALTER TABLE `course_prerequisites`
  ADD CONSTRAINT `course_prerequisites_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_prerequisites_ibfk_2` FOREIGN KEY (`prerequisite_course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_quizzes`
--
ALTER TABLE `course_quizzes`
  ADD CONSTRAINT `course_quizzes_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_ratings`
--
ALTER TABLE `course_ratings`
  ADD CONSTRAINT `course_ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_ratings_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD CONSTRAINT `course_sections_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dell_surveys`
--
ALTER TABLE `dell_surveys`
  ADD CONSTRAINT `dell_surveys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_program_attendance`
--
ALTER TABLE `holiday_program_attendance`
  ADD CONSTRAINT `attendance_attendee_fk` FOREIGN KEY (`attendee_id`) REFERENCES `holiday_program_attendees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_workshop_fk` FOREIGN KEY (`workshop_id`) REFERENCES `holiday_program_workshops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_program_attendees`
--
ALTER TABLE `holiday_program_attendees`
  ADD CONSTRAINT `attendee_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendee_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mentor_workshop` FOREIGN KEY (`mentor_workshop_preference`) REFERENCES `holiday_program_workshops` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `holiday_program_criteria`
--
ALTER TABLE `holiday_program_criteria`
  ADD CONSTRAINT `criteria_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_program_faqs`
--
ALTER TABLE `holiday_program_faqs`
  ADD CONSTRAINT `faq_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_program_items`
--
ALTER TABLE `holiday_program_items`
  ADD CONSTRAINT `item_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_program_mentor_details`
--
ALTER TABLE `holiday_program_mentor_details`
  ADD CONSTRAINT `holiday_program_mentor_details_ibfk_1` FOREIGN KEY (`attendee_id`) REFERENCES `holiday_program_attendees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `holiday_program_mentor_details_ibfk_2` FOREIGN KEY (`workshop_preference`) REFERENCES `holiday_program_workshops` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `holiday_program_projects`
--
ALTER TABLE `holiday_program_projects`
  ADD CONSTRAINT `project_attendee_fk` FOREIGN KEY (`attendee_id`) REFERENCES `holiday_program_attendees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_workshop_fk` FOREIGN KEY (`workshop_id`) REFERENCES `holiday_program_workshops` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `holiday_program_reports`
--
ALTER TABLE `holiday_program_reports`
  ADD CONSTRAINT `report_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_user_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `holiday_program_requirements`
--
ALTER TABLE `holiday_program_requirements`
  ADD CONSTRAINT `requirement_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_program_schedules`
--
ALTER TABLE `holiday_program_schedules`
  ADD CONSTRAINT `schedule_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_program_schedule_items`
--
ALTER TABLE `holiday_program_schedule_items`
  ADD CONSTRAINT `schedule_item_fk` FOREIGN KEY (`schedule_id`) REFERENCES `holiday_program_schedules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_program_workshops`
--
ALTER TABLE `holiday_program_workshops`
  ADD CONSTRAINT `workshop_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_report_images`
--
ALTER TABLE `holiday_report_images`
  ADD CONSTRAINT `report_image_fk` FOREIGN KEY (`report_id`) REFERENCES `holiday_program_reports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_workshop_enrollment`
--
ALTER TABLE `holiday_workshop_enrollment`
  ADD CONSTRAINT `enrollment_attendee_fk` FOREIGN KEY (`attendee_id`) REFERENCES `holiday_program_attendees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollment_workshop_fk` FOREIGN KEY (`workshop_id`) REFERENCES `holiday_program_workshops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD CONSTRAINT `lesson_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_sections`
--
ALTER TABLE `lesson_sections`
  ADD CONSTRAINT `lesson_sections_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `monthly_report_activities`
--
ALTER TABLE `monthly_report_activities`
  ADD CONSTRAINT `fk_activity_program` FOREIGN KEY (`program_id`) REFERENCES `clubhouse_programs` (`id`),
  ADD CONSTRAINT `fk_activity_report` FOREIGN KEY (`report_id`) REFERENCES `monthly_reports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `monthly_report_images`
--
ALTER TABLE `monthly_report_images`
  ADD CONSTRAINT `fk_image_activity` FOREIGN KEY (`activity_id`) REFERENCES `monthly_report_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD CONSTRAINT `quiz_answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `course_quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `course_quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skill_activities`
--
ALTER TABLE `skill_activities`
  ADD CONSTRAINT `skill_activities_creator_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skill_activity_completions`
--
ALTER TABLE `skill_activity_completions`
  ADD CONSTRAINT `skill_completions_activity_fk` FOREIGN KEY (`skill_activity_id`) REFERENCES `skill_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `skill_completions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skill_activity_steps`
--
ALTER TABLE `skill_activity_steps`
  ADD CONSTRAINT `skill_activity_steps_fk` FOREIGN KEY (`skill_activity_id`) REFERENCES `skill_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_enrollments`
--
ALTER TABLE `user_enrollments`
  ADD CONSTRAINT `user_enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `user_progress_activity_fk` FOREIGN KEY (`activity_id`) REFERENCES `course_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_lesson_fk` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_module_fk` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_section_fk` FOREIGN KEY (`lesson_section_id`) REFERENCES `lesson_sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_question_answers`
--
ALTER TABLE `user_question_answers`
  ADD CONSTRAINT `user_question_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_question_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_question_answers_ibfk_3` FOREIGN KEY (`answer_id`) REFERENCES `quiz_answers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
