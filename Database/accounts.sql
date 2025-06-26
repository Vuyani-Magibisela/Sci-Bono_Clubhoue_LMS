-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 26, 2025 at 10:22 AM
-- Server version: 8.0.42-0ubuntu0.22.04.1
-- PHP Version: 8.1.2-1ubuntu2.21

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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CheckRegistrationDeadlines` ()  BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_program_id INT;
    DECLARE deadline_cursor CURSOR FOR 
        SELECT id FROM holiday_programs 
        WHERE auto_close_on_date = 1 
          AND registration_open = 1 
          AND registration_deadline IS NOT NULL 
          AND NOW() >= registration_deadline;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN deadline_cursor;
    
    deadline_loop: LOOP
        FETCH deadline_cursor INTO v_program_id;
        IF done THEN
            LEAVE deadline_loop;
        END IF;
        
        -- Close registration
        UPDATE holiday_programs 
        SET registration_open = 0, updated_at = NOW()
        WHERE id = v_program_id;
        
        -- Log the closure
        INSERT INTO holiday_program_status_log 
        (program_id, old_status, new_status, change_reason, ip_address, created_at)
        SELECT 
            id, 1, 0, 
            CONCAT('Auto-closed: deadline reached (', registration_deadline, ')'), 
            'system', 
            NOW()
        FROM holiday_programs 
        WHERE id = v_program_id;
        
    END LOOP;
    
    CLOSE deadline_cursor;
    
    SELECT ROW_COUNT() as programs_closed;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetProgramStatistics` (IN `p_program_id` INT)  BEGIN
    SELECT 
        p.id,
        p.term,
        p.title,
        p.registration_open,
        p.max_participants,
        COUNT(a.id) as total_registrations,
        COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) as confirmed_registrations,
        COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_registrations,
        COUNT(CASE WHEN a.status = 'cancelled' THEN 1 END) as cancelled_registrations,
        COUNT(CASE WHEN a.mentor_registration = 1 THEN 1 END) as mentor_applications,
        COUNT(CASE WHEN a.mentor_registration = 0 THEN 1 END) as member_registrations,
        CASE 
            WHEN p.max_participants > 0 THEN 
                ROUND((COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) / p.max_participants) * 100, 1)
            ELSE 0 
        END as capacity_percentage,
        (p.max_participants - COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END)) as spots_remaining
    FROM holiday_programs p
    LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
    WHERE p.id = p_program_id
    GROUP BY p.id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateProgramStatus` (IN `p_program_id` INT, IN `p_new_status` TINYINT, IN `p_user_id` INT, IN `p_reason` VARCHAR(255), IN `p_ip_address` VARCHAR(45))  BEGIN
    DECLARE v_old_status TINYINT DEFAULT 0;
    DECLARE v_program_exists INT DEFAULT 0;
    DECLARE exit handler for sqlexception
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Check if program exists and get current status
    SELECT registration_open, 1 INTO v_old_status, v_program_exists
    FROM holiday_programs 
    WHERE id = p_program_id;
    
    IF v_program_exists = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Program not found';
    END IF;
    
    -- Only update if status is actually changing
    IF v_old_status != p_new_status THEN
        -- Set session variables for trigger
        SET @current_user_id = p_user_id;
        SET @user_ip = p_ip_address;
        
        -- Update the program status
        UPDATE holiday_programs 
        SET registration_open = p_new_status,
            updated_at = NOW()
        WHERE id = p_program_id;
        
        -- Manual log entry with additional details
        INSERT INTO holiday_program_status_log 
        (program_id, changed_by, old_status, new_status, change_reason, ip_address, created_at)
        VALUES 
        (p_program_id, p_user_id, v_old_status, p_new_status, p_reason, p_ip_address, NOW());
        
        -- Clear the session variables
        SET @current_user_id = NULL;
        SET @user_ip = NULL;
        
        SELECT 'success' as result, 'Status updated successfully' as message, v_old_status as old_status, p_new_status as new_status;
    ELSE
        SELECT 'no_change' as result, 'Status is already set to the requested value' as message, v_old_status as old_status, p_new_status as new_status;
    END IF;
    
    COMMIT;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `GetProgramCapacityPercentage` (`p_program_id` INT) RETURNS DECIMAL(5,2) READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE v_confirmed_count INT DEFAULT 0;
    DECLARE v_max_participants INT DEFAULT 0;
    
    SELECT 
        COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END),
        p.max_participants
    INTO v_confirmed_count, v_max_participants
    FROM holiday_programs p
    LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
    WHERE p.id = p_program_id
    GROUP BY p.id, p.max_participants;
    
    IF v_max_participants > 0 THEN
        RETURN ROUND((v_confirmed_count / v_max_participants) * 100, 2);
    ELSE
        RETURN 0;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `GetProgramCapacityStatus` (`p_program_id` INT) RETURNS VARCHAR(20) CHARSET utf8mb4 COLLATE utf8mb4_general_ci READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE v_confirmed_count INT DEFAULT 0;
    DECLARE v_max_participants INT DEFAULT 0;
    DECLARE v_percentage DECIMAL(5,2) DEFAULT 0;
    
    SELECT 
        COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END),
        p.max_participants
    INTO v_confirmed_count, v_max_participants
    FROM holiday_programs p
    LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
    WHERE p.id = p_program_id
    GROUP BY p.id, p.max_participants;
    
    IF v_max_participants > 0 THEN
        SET v_percentage = (v_confirmed_count / v_max_participants) * 100;
        
        CASE 
            WHEN v_confirmed_count >= v_max_participants THEN
                RETURN 'full';
            WHEN v_percentage >= 90 THEN
                RETURN 'nearly_full';
            WHEN v_percentage >= 75 THEN
                RETURN 'filling_up';
            ELSE
                RETURN 'available';
        END CASE;
    ELSE
        RETURN 'unlimited';
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_submissions`
--

CREATE TABLE `activity_submissions` (
  `id` int NOT NULL,
  `activity_id` int NOT NULL,
  `user_id` int NOT NULL,
  `submission_content` longtext COLLATE utf8mb4_general_ci,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submission_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `points_earned` int DEFAULT NULL,
  `max_points` int NOT NULL,
  `feedback` text COLLATE utf8mb4_general_ci,
  `status` enum('draft','submitted','graded','returned') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `graded_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_attempts`
--

CREATE TABLE `assessment_attempts` (
  `id` int NOT NULL,
  `assessment_id` int NOT NULL,
  `user_id` int NOT NULL,
  `attempt_number` int NOT NULL DEFAULT '1',
  `points_earned` int DEFAULT NULL,
  `total_points` int NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `passed` tinyint(1) DEFAULT '0',
  `time_spent_minutes` int DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` enum('in_progress','completed','abandoned','expired') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'in_progress',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `checked_in` datetime DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `sign_in_status` enum('signedIn','signedOut') COLLATE utf8mb4_general_ci DEFAULT 'signedOut'
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
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `learning_outcomes` text COLLATE utf8mb4_general_ci,
  `target_age_group` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `duration` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `max_participants` int DEFAULT NULL,
  `materials_needed` text COLLATE utf8mb4_general_ci,
  `difficulty_level` enum('Beginner','Intermediate','Advanced') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `program_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `participants` int NOT NULL,
  `narrative` text COLLATE utf8mb4_general_ci,
  `challenges` text COLLATE utf8mb4_general_ci,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `course_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `learning_objectives` text COLLATE utf8mb4_general_ci,
  `course_requirements` text COLLATE utf8mb4_general_ci,
  `prerequisites` text COLLATE utf8mb4_general_ci,
  `completion_criteria` text COLLATE utf8mb4_general_ci,
  `certification_criteria` text COLLATE utf8mb4_general_ci,
  `pass_percentage` decimal(5,2) DEFAULT '70.00',
  `type` enum('full_course','short_course','lesson','skill_activity') COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'General',
  `difficulty_level` enum('Beginner','Intermediate','Advanced') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Beginner',
  `duration` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estimated_duration_hours` int DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `thumbnail_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enrollment_count` int NOT NULL DEFAULT '0',
  `max_enrollments` int DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive','draft','archived') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `created_by` int NOT NULL,
  `last_updated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `title`, `description`, `learning_objectives`, `course_requirements`, `prerequisites`, `completion_criteria`, `certification_criteria`, `pass_percentage`, `type`, `category`, `difficulty_level`, `duration`, `estimated_duration_hours`, `image_path`, `thumbnail_path`, `enrollment_count`, `max_enrollments`, `display_order`, `is_featured`, `is_published`, `status`, `created_by`, `last_updated_by`, `created_at`, `updated_at`) VALUES
(1, '', 'Introduction to Robotics', 'Learn the basics of robotics programming and design. This course covers fundamental concepts of robotics, including sensors, motors, and programming for autonomous behavior.', NULL, NULL, NULL, NULL, NULL, '70.00', 'full_course', 'General', 'Beginner', '8 weeks', NULL, NULL, NULL, 0, NULL, 0, 1, 1, 'active', 1, NULL, '2025-05-08 10:59:04', '2025-05-22 12:06:51'),
(10, 'FC-TC', 'Test Course', 'Best Course Ever', NULL, NULL, NULL, NULL, NULL, '70.00', 'full_course', 'General', 'Beginner', '2 weeks', NULL, '682f015feceb6_1747911007.jpeg', NULL, 1, NULL, 0, 0, 0, 'draft', 1, NULL, '2025-05-22 10:50:07', '2025-05-22 11:00:53'),
(11, 'SC-TSC', 'Test Short Course', 'Best Short Course ever!!', NULL, NULL, NULL, NULL, NULL, '70.00', 'short_course', 'General', 'Beginner', '1 week', NULL, '682f02af53c86_1747911343.jpg', NULL, 1, NULL, 0, 0, 0, 'draft', 1, NULL, '2025-05-22 10:55:43', '2025-05-22 11:00:48'),
(12, 'LN-TL', 'Test Lesson', 'Best lesson ever!!', NULL, NULL, NULL, NULL, NULL, '70.00', 'lesson', 'General', 'Beginner', '1 hour', NULL, '682f02fc8ae36_1747911420.png', NULL, 1, NULL, 0, 0, 0, 'draft', 1, NULL, '2025-05-22 10:57:00', '2025-05-22 11:00:40'),
(13, 'SA-TSA', 'Test Skills Activity', 'Best Practical activity ever', NULL, NULL, NULL, NULL, NULL, '70.00', 'skill_activity', 'General', 'Beginner', '1 day', NULL, '682f0354b82a6_1747911508.webp', NULL, 1, NULL, 0, 0, 0, 'draft', 1, NULL, '2025-05-22 10:58:28', '2025-05-22 11:00:30');

-- --------------------------------------------------------

--
-- Table structure for table `course_activities`
--

CREATE TABLE `course_activities` (
  `id` int NOT NULL,
  `course_id` int DEFAULT NULL,
  `module_id` int DEFAULT NULL,
  `lesson_id` int DEFAULT NULL,
  `lesson_section_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `activity_type` enum('practical','assignment','project','quiz','assessment','skill_exercise') COLLATE utf8mb4_general_ci NOT NULL,
  `instructions` longtext COLLATE utf8mb4_general_ci,
  `resources_needed` text COLLATE utf8mb4_general_ci,
  `estimated_duration_minutes` int DEFAULT NULL,
  `max_points` int DEFAULT '100',
  `pass_points` int DEFAULT '70',
  `submission_type` enum('text','file','link','code','none') COLLATE utf8mb4_general_ci DEFAULT 'text',
  `auto_grade` tinyint(1) DEFAULT '0',
  `order_number` int NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `course_id` int DEFAULT NULL,
  `module_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `assessment_type` enum('module_quiz','module_exam','course_final','course_project') COLLATE utf8mb4_general_ci NOT NULL,
  `total_points` int NOT NULL DEFAULT '100',
  `pass_points` int NOT NULL DEFAULT '70',
  `time_limit_minutes` int DEFAULT NULL,
  `attempts_allowed` int DEFAULT '1',
  `instructions` longtext COLLATE utf8mb4_general_ci,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_categories`
--

CREATE TABLE `course_categories` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `parent_id` int DEFAULT NULL,
  `order_number` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_category_relationships`
--

CREATE TABLE `course_category_relationships` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `category_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_certificates`
--

CREATE TABLE `course_certificates` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `certificate_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `completion_date` date NOT NULL,
  `final_score` decimal(5,2) NOT NULL,
  `grade` enum('A+','A','B+','B','C+','C','D','F') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `certificate_template` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `verification_code` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `issued_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_lessons`
--

CREATE TABLE `course_lessons` (
  `id` int NOT NULL,
  `section_id` int NOT NULL,
  `course_id` int DEFAULT NULL,
  `module_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_general_ci,
  `lesson_objectives` text COLLATE utf8mb4_general_ci,
  `lesson_type` enum('text','video','quiz','assignment','interactive') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'text',
  `difficulty_level` enum('Beginner','Intermediate','Advanced') COLLATE utf8mb4_general_ci DEFAULT 'Beginner',
  `pass_percentage` decimal(5,2) DEFAULT '70.00',
  `prerequisites` text COLLATE utf8mb4_general_ci,
  `video_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `duration_minutes` int DEFAULT NULL,
  `estimated_duration_minutes` int DEFAULT NULL,
  `order_number` int NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_lessons`
--

INSERT INTO `course_lessons` (`id`, `section_id`, `course_id`, `module_id`, `title`, `content`, `lesson_objectives`, `lesson_type`, `difficulty_level`, `pass_percentage`, `prerequisites`, `video_url`, `duration_minutes`, `estimated_duration_minutes`, `order_number`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, 'What is Robotics?', '<p>This lesson introduces the field of robotics and its applications in the modern world.</p><p>Robotics is an interdisciplinary field that combines mechanical engineering, electrical engineering, and computer science. Robots are designed to assist humans or to perform tasks autonomously.</p><h3>Key Concepts</h3><ul><li>Definition of robots and robotics</li><li>History of robotics development</li><li>Types of robots: industrial, service, educational</li><li>Basic components: sensors, actuators, controllers</li></ul><p>By the end of this course, you will be able to design, build, and program a simple robot that can navigate its environment and perform basic tasks.</p>', NULL, 'text', 'Beginner', '70.00', NULL, NULL, 20, NULL, 1, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(2, 1, NULL, NULL, 'Robot Components Overview', '<p>This lesson covers the essential components that make up a robot.</p><h3>Main Components of a Robot</h3><ol><li><strong>Structure/Frame</strong>: The physical body that supports all other components</li><li><strong>Actuators</strong>: Motors and other devices that create movement</li><li><strong>Sensors</strong>: Devices that collect information about the environment</li><li><strong>Controller</strong>: The \"brain\" (usually a microcontroller) that processes information and controls behavior</li><li><strong>Power Source</strong>: Batteries or other energy sources</li></ol><p>In the next lessons, we will explore each of these components in more detail.</p>', NULL, 'text', 'Beginner', '70.00', NULL, NULL, 25, NULL, 2, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(3, 1, NULL, NULL, 'Introduction to Robot Controllers', '<p>Learn about the various controllers used in robotics, from simple microcontrollers to advanced computing systems.</p><h3>Common Robot Controllers</h3><ul><li>Arduino boards</li><li>Raspberry Pi</li><li>LEGO Mindstorms EV3</li><li>Specialized robotics platforms</li></ul><p>In this course, we will primarily use Arduino for our robotics projects.</p>', NULL, 'text', 'Beginner', '70.00', NULL, NULL, 30, NULL, 3, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(4, 2, NULL, NULL, 'Types of Sensors', '<p>This lesson explores different types of sensors used in robotics.</p><h3>Common Sensor Types</h3><ul><li>Distance sensors (ultrasonic, infrared)</li><li>Light sensors (photoresistors, photodiodes)</li><li>Touch sensors (buttons, switches)</li><li>Color sensors</li><li>Gyroscopes and accelerometers</li></ul><p>Each sensor type provides different kinds of information about the environment.</p>', NULL, 'text', 'Beginner', '70.00', NULL, NULL, 25, NULL, 1, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(5, 2, NULL, NULL, 'Working with Ultrasonic Sensors', '<p>Learn how to use ultrasonic sensors to detect obstacles and measure distances.</p><h3>How Ultrasonic Sensors Work</h3><p>Ultrasonic sensors emit high-frequency sound waves and time how long it takes for the echo to return. This time can be converted to distance.</p><h3>Connecting and Programming</h3><p>We will connect an HC-SR04 ultrasonic sensor to our Arduino and write code to read distance values.</p>', NULL, 'video', 'Beginner', '70.00', NULL, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 40, NULL, 2, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(6, 2, NULL, NULL, 'Sensor Quiz', '<p>Test your knowledge about different types of sensors and their applications in robotics.</p>', NULL, 'quiz', 'Beginner', '70.00', NULL, NULL, 15, NULL, 3, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(7, 3, NULL, NULL, 'DC Motors and Servo Motors', '<p>This lesson covers the differences between DC motors and servo motors, and when to use each.</p><h3>DC Motors</h3><p>Continuous rotation, good for wheels and general movement.</p><h3>Servo Motors</h3><p>Precise position control, good for robotic arms and controlled movements.</p>', NULL, 'text', 'Beginner', '70.00', NULL, NULL, 30, NULL, 1, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(8, 3, NULL, NULL, 'Motor Drivers and Control Circuits', '<p>Learn why motors need special control circuits and how to use motor drivers with your controller.</p><h3>Why Motor Drivers?</h3><p>Most microcontrollers cannot provide enough current to drive motors directly. Motor drivers act as intermediaries that can handle the higher current requirements.</p>', NULL, 'text', 'Beginner', '70.00', NULL, NULL, 35, NULL, 2, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(9, 4, NULL, NULL, 'Introduction to Autonomous Behavior', '<p>This lesson introduces the concept of autonomous behavior in robots.</p><h3>What is Autonomous Behavior?</h3><p>Autonomous behavior refers to a robot\'s ability to perform tasks and make decisions without human intervention, based on sensor input and programmed logic.</p>', NULL, 'text', 'Beginner', '70.00', NULL, NULL, 25, NULL, 1, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(10, 4, NULL, NULL, 'Simple Line Following Robot', '<p>In this lesson, we\'ll create a robot that can follow a line on the ground.</p><h3>Components Needed</h3><ul><li>Chassis with two motors</li><li>Line sensors</li><li>Arduino controller</li><li>Motor driver</li></ul><h3>Line Following Algorithm</h3><p>We\'ll implement a simple algorithm that adjusts the robot\'s direction based on sensor readings.</p>', NULL, 'assignment', 'Beginner', '70.00', NULL, NULL, 60, NULL, 2, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17'),
(11, 4, NULL, NULL, 'Final Project: Obstacle Avoiding Robot', '<p>For the final project, you will create a robot that can navigate autonomously while avoiding obstacles.</p><h3>Project Requirements</h3><ul><li>Robot must detect obstacles using sensors</li><li>Robot must make decisions about path changes</li><li>Robot should be able to navigate a simple maze</li></ul><p>You will document your design process and demonstrate your robot\'s capabilities.</p>', NULL, 'assignment', 'Beginner', '70.00', NULL, NULL, 120, NULL, 3, 1, '2025-05-08 11:11:17', '2025-05-08 11:11:17');

-- --------------------------------------------------------

--
-- Table structure for table `course_modules`
--

CREATE TABLE `course_modules` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `learning_objectives` text COLLATE utf8mb4_general_ci,
  `estimated_duration_hours` int DEFAULT NULL,
  `order_number` int NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `pass_percentage` decimal(5,2) DEFAULT '70.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_modules`
--

INSERT INTO `course_modules` (`id`, `course_id`, `title`, `description`, `learning_objectives`, `estimated_duration_hours`, `order_number`, `is_published`, `pass_percentage`, `created_at`, `updated_at`) VALUES
(1, 10, 'First Module', 'This is the first module of this Course', 'You will learn\r\nYou will also learn', 2, 1, 0, '70.00', '2025-05-26 13:45:45', '2025-05-26 13:45:45'),
(2, 10, 'Second Module', 'This is the Second Module', 'Lean a lot in the second module', 4, 2, 0, '70.00', '2025-05-26 14:23:19', '2025-05-26 14:23:19');

-- --------------------------------------------------------

--
-- Table structure for table `course_prerequisites`
--

CREATE TABLE `course_prerequisites` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `prerequisite_course_id` int NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_quizzes`
--

CREATE TABLE `course_quizzes` (
  `id` int NOT NULL,
  `lesson_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `passing_score` int NOT NULL DEFAULT '70',
  `time_limit_minutes` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_ratings`
--

CREATE TABLE `course_ratings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `rating` int NOT NULL,
  `review` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_sections`
--

CREATE TABLE `course_sections` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `order_number` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `first_time_participation` tinyint(1) DEFAULT NULL,
  `used_computer_first_time` tinyint(1) DEFAULT NULL,
  `showed_tech_to_others` tinyint(1) DEFAULT NULL,
  `used_tech_for_school` tinyint(1) DEFAULT NULL,
  `more_comfortable_teamwork` tinyint(1) DEFAULT NULL,
  `more_confident_sharing` tinyint(1) DEFAULT NULL,
  `thinking_about_staying_in_school` tinyint(1) DEFAULT NULL,
  `interested_in_tech_jobs` tinyint(1) DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_programs`
--

CREATE TABLE `holiday_programs` (
  `id` int NOT NULL,
  `term` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `dates` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `time` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '9:00 AM - 4:00 PM',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'Sci-Bono Clubhouse',
  `age_range` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '13-18 years',
  `lunch_included` tinyint(1) DEFAULT '1',
  `program_goals` text COLLATE utf8mb4_general_ci,
  `registration_deadline` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `max_participants` int DEFAULT '30',
  `registration_open` tinyint(1) DEFAULT '1',
  `status` enum('draft','open','closing_soon','closed','completed','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `auto_close_on_capacity` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Auto-close registration when capacity is reached',
  `auto_close_on_date` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Auto-close registration on deadline',
  `notification_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'JSON settings for notifications',
  `program_structure` text COLLATE utf8mb4_general_ci COMMENT 'Stores program configuration'
) ;

--
-- Dumping data for table `holiday_programs`
--

INSERT INTO `holiday_programs` (`id`, `term`, `title`, `description`, `dates`, `time`, `start_date`, `end_date`, `location`, `age_range`, `lunch_included`, `program_goals`, `registration_deadline`, `max_participants`, `registration_open`, `status`, `created_at`, `updated_at`, `auto_close_on_capacity`, `auto_close_on_date`, `notification_settings`, `program_structure`) VALUES
(1, 'Term 1', 'Multi-Media - Digital Design', 'Dive into the world of digital media creation, learning graphic design, video editing, and animation techniques.', 'March 31 - April 4, 2025', '9:00 AM - 4:00 PM', '2025-03-29', '2025-04-07', 'Sci-Bono Clubhouse', '13-18 years', 1, NULL, '2025-03-22 00:00:00', 35, 0, 'completed', '2025-03-26 19:24:06', '2025-06-24 14:24:49', 1, 0, NULL, '{\"updated_at\": \"2025-06-23 20:05:19.000000\", \"cohort_size\": 20, \"max_cohorts\": 2, \"cohort_system\": true, \"duration_weeks\": 2, \"prerequisites_enabled\": true}'),
(2, 'Term 2', 'Sci-Bono Clubhouse Term 2 AI Festival ', 'The Term 2 AI Festival is designed to immerse participants in the exciting world of artificial intelligence through hands-on programming, web development, and electronics projects. This intensive program will explore AI through three distinct but interconnected tracks: Programming AI Projects, Web Projects, and Electronics Projects.', 'June 30 - July 18, 2025', '9:00 AM - 4:00 PM', '2025-06-30', '2025-07-18', 'Sci-Bono Clubhouse', '13-18 years', 1, 'Participants will gain practical experience with machine learning algorithms, web-based AI applications, and AI-powered hardware projects. The program emphasizes practical application of AI concepts through coding exercises, interactive workshops, and creative project development. All projects will incorporate real-world problem-solving scenarios that demonstrate how AI can be used to address challenges in education, healthcare, environment, and community development.', '27 June 2025', 30, 1, 'draft', '2025-06-09 14:36:41', '2025-06-23 18:14:57', 0, 0, NULL, '{\"updated_at\": \"2025-06-23 20:14:57.000000\", \"cohort_system\": false, \"duration_weeks\": 2, \"prerequisites_enabled\": false}');

--
-- Triggers `holiday_programs`
--
DELIMITER $$
CREATE TRIGGER `holiday_program_status_change_log` AFTER UPDATE ON `holiday_programs` FOR EACH ROW BEGIN
    -- Log status changes
    IF OLD.registration_open != NEW.registration_open THEN
        INSERT INTO holiday_program_status_log 
        (program_id, changed_by, old_status, new_status, change_reason, ip_address, created_at)
        VALUES 
        (NEW.id, 
         COALESCE(@current_user_id, NULL), 
         OLD.registration_open, 
         NEW.registration_open, 
         CASE 
             WHEN NEW.registration_open = 1 THEN 'Registration opened'
             ELSE 'Registration closed'
         END,
         COALESCE(@user_ip, 'system'), 
         NOW());
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `holiday_programs_with_status`
-- (See below for the actual view)
--
CREATE TABLE `holiday_programs_with_status` (
`age_range` varchar(50)
,`created_at` timestamp
,`dates` varchar(100)
,`description` text
,`display_status` varchar(19)
,`end_date` date
,`id` int
,`location` varchar(255)
,`lunch_included` tinyint(1)
,`max_participants` int
,`member_registrations` bigint
,`mentor_registrations` bigint
,`program_goals` text
,`registration_deadline` varchar(100)
,`registration_open` tinyint(1)
,`start_date` date
,`status` enum('draft','open','closing_soon','closed','completed','cancelled')
,`term` varchar(50)
,`time` varchar(50)
,`title` varchar(255)
,`total_registrations` bigint
,`updated_at` timestamp
,`workshop_count` bigint
);

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_attendance`
--

CREATE TABLE `holiday_program_attendance` (
  `id` int NOT NULL,
  `attendee_id` int NOT NULL,
  `workshop_id` int NOT NULL,
  `date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `status` enum('present','absent','late','excused') COLLATE utf8mb4_general_ci DEFAULT 'present',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_attendees`
--

CREATE TABLE `holiday_program_attendees` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other','Prefer not to say') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `school` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `grade` int DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `province` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `guardian_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `guardian_relationship` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `guardian_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `guardian_email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `emergency_contact_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `workshop_preference` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `why_interested` text COLLATE utf8mb4_general_ci,
  `experience_level` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `needs_equipment` tinyint(1) DEFAULT '0',
  `medical_conditions` text COLLATE utf8mb4_general_ci,
  `allergies` text COLLATE utf8mb4_general_ci,
  `photo_permission` tinyint(1) DEFAULT '0',
  `data_permission` tinyint(1) DEFAULT '0',
  `dietary_restrictions` text COLLATE utf8mb4_general_ci,
  `additional_notes` text COLLATE utf8mb4_general_ci,
  `registration_status` enum('pending','confirmed','canceled','waitlisted','completed') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `mentor_registration` tinyint(1) DEFAULT '0',
  `mentor_status` enum('Pending','Approved','Declined') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mentor_workshop_preference` int DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `is_clubhouse_member` tinyint(1) DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `cohort_id` int DEFAULT NULL COMMENT 'Assigned cohort for this attendee',
  `prerequisites_met` json DEFAULT NULL COMMENT 'Track which prerequisites are satisfied'
) ;

--
-- Dumping data for table `holiday_program_attendees`
--

INSERT INTO `holiday_program_attendees` (`id`, `program_id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `gender`, `school`, `grade`, `address`, `city`, `province`, `postal_code`, `guardian_name`, `guardian_relationship`, `guardian_phone`, `guardian_email`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `workshop_preference`, `why_interested`, `experience_level`, `needs_equipment`, `medical_conditions`, `allergies`, `photo_permission`, `data_permission`, `dietary_restrictions`, `additional_notes`, `registration_status`, `created_at`, `updated_at`, `mentor_registration`, `mentor_status`, `mentor_workshop_preference`, `password`, `last_login`, `is_clubhouse_member`, `status`, `cohort_id`, `prerequisites_met`) VALUES
(1, 1, 1, 'Vuyani', 'Magibisela', 'vuyani.magibisela@sci-bono.co.za', '638393157', '2010-08-23', 'Male', '0', 12, '123 Gull Street', 'Johannesburg', 'Gauteng', '2021', 'Mandisa', 'Mother', '0721166543', 'mandi@gmail.com', '', '', '', '[\"3\",\"4\"]', 'Learn', '0', 1, 'No', '0', 1, 1, 'No', 'Learn', 'confirmed', '2025-03-28 16:40:40', '2025-06-06 14:45:48', 0, NULL, NULL, NULL, NULL, 0, 'pending', NULL, NULL),
(2, 1, 7, 'Sam', 'Kabanga', 'sam@example.com', '688965565', '2025-02-21', 'Male', NULL, NULL, '123 Good Street', 'Johannesburg', '', '1920', NULL, NULL, NULL, NULL, 'Thabo', 'Uncle', '0832342342', '[]', 'sdf', 'Advanced', 0, 'dssdf', 'sd', 1, 1, 'dssd', 'sd', 'confirmed', '2025-03-30 14:48:38', '2025-06-06 14:46:01', 1, 'Approved', 4, NULL, NULL, 0, 'pending', NULL, NULL),
(3, 1, 2, 'Itumeleng', 'Kgakane', 'itum@gmail.com', '0', '2012-01-12', 'Male', 'Fernadale High School', 12, '123 Good Street', 'Johannesburg', 'Gauteng', '1920', 'Mandi', 'Mother', '736933940', 'mandi@gmail.com', '', '', '', '[\"3\",\"4\"]', 'df', 'Beginner', 0, 'fsd', 'sdf', 1, 1, 'sd', 'sdf', 'pending', '2025-03-30 15:31:51', '2025-03-30 15:31:51', 0, NULL, NULL, NULL, NULL, 0, 'pending', NULL, NULL),
(4, 1, 8, 'Jabu', 'Khumalo', 'jabut@example.com', '0', '2012-02-21', 'Male', 'Fernadale High School', 12, '123 Main St', 'Johannesburg', 'Gauteng', '2021', 'Mandisa', 'Mother', '0721166543', 'mandi@gmail.com', '', '', '', '[\"1\",\"2\"]', 'ds', 'Basic', 0, 'sd', 'fds', 1, 1, 'ds', 'sda', 'confirmed', '2025-03-30 16:35:22', '2025-06-06 14:46:44', 0, NULL, NULL, NULL, NULL, 0, 'pending', NULL, NULL),
(5, 1, NULL, 'Noma', 'Mabasa', 'noma@gmail.com', '0012000', '2012-02-16', 'Male', 'Fernadale High School', 10, '123 Main St', 'Johannesburg', 'Gauteng', '2021', 'Vuyani', 'Father', '0638393157', 'vuyani@gmail.com', '', '', '', '[\"4\",\"1\"]', 'Love 3D animations', 'Intermediate', 0, 'No', 'No', 1, 1, 'No', 'Want to have fun and learn.', 'confirmed', '2025-03-30 17:09:01', '2025-06-09 14:25:54', 0, NULL, NULL, NULL, NULL, 0, 'pending', NULL, NULL),
(6, 2, NULL, 'Kgotso', 'Maponya', 'kgotso.maponya@live.com', '0658975346', '2010-05-19', 'Male', 'Fernadale High School', 9, '123 Main St', 'Johannesburg', 'Gauteng', '2021', 'John', 'Futher', '0788945689', 'john.maponya@live.com', '', '', '', '[\"7\"]', 'I love building things', 'Basic', 0, 'Discovery', 'None', 1, 1, 'None', 'None', 'confirmed', '2025-06-17 19:18:10', '2025-06-24 08:09:15', 0, NULL, NULL, NULL, NULL, 0, 'confirmed', NULL, NULL),
(7, 2, 7, 'Sam', 'Kabanga', 'sam@example.com', '0817851714', '2017-02-21', 'Male', 'Fernadale High School', 9, '123 Main St', 'Johannesburg', 'Gauteng', '2021', 'Tshepo Kabanga', 'Father', '0868965623', 'tshepo.kabanga@live.com', '', '', '', '[7,6]', 'I love coding and electronics', 'Beginner', 1, 'Discovery', 'No', 1, 1, 'No', 'I would like this to work please!', 'confirmed', '2025-06-24 07:46:45', '2025-06-24 08:09:06', 0, NULL, NULL, NULL, NULL, 0, 'confirmed', NULL, NULL),
(8, 2, NULL, 'Thato', 'Banda', 'thato.banda@live.com', '0817851714', '2015-07-22', 'Male', 'Fordsburg Primary School', 7, '123 Dodo Street', 'Johannesburg', 'Gauteng', '2021', 'Themba', 'Banda', '0868972514', 'thameba.banda@live.com', '', '', '', '[5,7]', 'Love of tech', 'Intermediate', 0, 'No', 'Yes', 1, 1, 'No Pork', 'I would like to have fun.', 'confirmed', '2025-06-24 08:13:20', '2025-06-24 15:17:38', 0, NULL, NULL, NULL, NULL, 0, 'confirmed', NULL, NULL),
(9, 2, NULL, 'dsaf', 'dsfa', 'sdfa@g.df', '0865368965', '2005-05-09', 'Female', 'sdfa', 7, 'sdaf', 'sdaf', 'KwaZulu-Natal', '2032', 'dsfa', 'sdaf', '0608658975', 'sdaf@dsf.hgf', '', '', '', '[7,6]', 'sdf', 'Beginner', 1, '', '', 1, 1, '', '', 'canceled', '2025-06-24 10:54:20', '2025-06-24 15:17:51', 0, NULL, NULL, '$2y$10$jz8MVCL2IIRjLSIpBTEvkOs0bH2Ol48noGuv2ZVkv.8uIsyFTe9OG', NULL, 0, 'pending', 3, NULL),
(10, 2, 4, 'Themba', 'Magibisela', 'themba@example.co.za', '08638658921', '2005-02-05', 'Female', '', NULL, '123 Main St', 'Johannesburg', 'Gauteng', '2121', '', '', '', '', 'Vuyani', 'Brother', '0865698741', '[]', 'sdf', 'Beginner', 1, 'No', 'No', 1, 1, 'No', 'No', 'pending', '2025-06-24 11:15:40', '2025-06-24 11:15:40', 1, 'Pending', 7, NULL, NULL, 0, 'pending', 3, NULL),
(11, 2, 2, 'Itumeleng', 'Kgakane', 'itum@gmail.com', '0856974589', '2000-01-12', 'Male', 'Fernadale High School', 12, '123 Main St', 'Johannesburg', 'Gauteng', '2021', 'Vuyani', 'Father', '0864789578', 'vuyani.magibisela@live.com', 'Itumeleng', 'Brother', '0768954825', '[7,5]', 'Coding and electronics', 'Novice', 1, 'Discovery', 'No', 1, 1, 'No Pork', 'No', 'pending', '2025-06-24 15:23:00', '2025-06-24 15:23:00', 0, NULL, NULL, NULL, NULL, 0, 'pending', 4, NULL),
(12, 2, NULL, 'Tumelo', 'Motsho', 'tumelo.Motsho@live.com', '0856974589', '1999-08-05', 'Male', '', NULL, '123 Main St', 'Johannesburg', 'Gauteng', '2021', '', '', '', '', 'Vuyani', 'Brother', '0638393157', '[]', 'Want to show the kids really exciting technology.', 'Advanced', 1, 'Discovery', 'No', 1, 1, 'No', 'Thanks for the consideration.', 'pending', '2025-06-24 15:27:23', '2025-06-24 15:27:23', 1, 'Pending', 6, '$2y$10$lhfuxKqv4MKFYlXR6UJu0.GdwKr4TYUVoqGXUqoebMPhYa3S7e6Xq', NULL, 0, 'pending', 3, NULL),
(13, 2, NULL, 'dsfa', 'dsfa', 'fdsa@dfds.d', '0856478965', '1999-08-08', 'Female', '', NULL, '123 Main St', 'Johannesburg', 'Gauteng', '2121', '', '', '', '', 'Vuyani', 'Brother', '0846579689', '[]', 'sdfa', 'Intermediate', 1, 'sdfa', 'fdsa', 1, 1, 'sdaf', 'dsa', 'canceled', '2025-06-24 15:52:26', '2025-06-24 16:07:28', 1, 'Pending', 6, '$2y$10$XEYWRAqeBpCrtGwJ58ILiuUBVKFm2/6kNF.kJndpCJVLYqzxI.5ya', NULL, 0, 'pending', 3, NULL),
(14, 2, NULL, 'Tshepiso', 'Mabele', 'tshepiso.mabele@live.com', '0617895438', '1999-08-07', 'Male', '', NULL, '123 Gull Street', 'Johannesburg', 'Gauteng', '1819', '', '', '', '', 'Vuyani', 'Magibisela', '0678954289', '[]', 'I am interested in web development, hence it&#039;s my profession.', 'Advanced', 1, 'Discovery', 'Pork', 1, 1, 'No Pork', 'Excited to share with others.', 'confirmed', '2025-06-24 16:05:42', '2025-06-24 16:06:14', 1, 'Pending', 6, '$2y$10$GegMPzav3skKhTQyImJFVOhyWvEVkVNg1e8SBe9V4Mcbrt.h6lK6O', NULL, 0, 'pending', 3, NULL),
(15, 2, NULL, 'qwer', 'fds', 'this@that.com', '0862547893', '2000-02-03', 'Female', '', NULL, 'sdaf', 'asdf', 'Northern Cape', '1234', '', '', '', '', '', '', '', '[]', 'sdaf', 'Novice', 1, 'sadf', 'afd', 1, 1, 'fa', 'af', 'pending', '2025-06-25 11:24:28', '2025-06-25 11:24:28', 1, 'Pending', 5, '$2y$10$SEkIOpGvVIR8JhEhr6PSFulzYJHPqZCqPMWITzliyF79644S13QWy', NULL, 0, 'pending', 4, NULL),
(16, 2, NULL, 'Jabu', 'Letsatsi', 'jabu.letsatsi@live.com', '082345678985', '2012-08-05', 'Male', 'General Primary', 7, '123 Gull Street', 'Johannesburg', '0', '2120', 'Mbali Letsatsi', NULL, '087564892', 'mbali.letsatsi@live.com', NULL, NULL, NULL, '[7,5]', 'I want to be the best', NULL, 0, NULL, NULL, 1, 1, NULL, NULL, 'pending', '2025-06-25 20:11:44', '2025-06-25 20:11:44', 0, NULL, NULL, NULL, NULL, 0, 'pending', NULL, NULL),
(17, 2, NULL, 'Kagiso', 'Letsatsi', 'kagiso.letsatsi@live.com', '0784567898', '2010-08-06', 'Male', 'Gloden High School', 8, '123 Main St', 'Johannesburg', '0', '2021', 'James Letsatsi', NULL, '06278945878', 'james.letsatsi@live.com', 'James Letsatsi', 'Father', '06278945878', '[6,7]', 'Very interested', NULL, 0, NULL, NULL, 1, 1, NULL, NULL, 'pending', '2025-06-25 20:49:17', '2025-06-25 20:49:17', 0, NULL, NULL, NULL, NULL, 0, 'pending', NULL, NULL),
(18, 2, NULL, 'Matome', 'Mabele', 'matome.mabele@live.com', '0834567823', '2012-08-05', 'Female', 'Fernadale High School', 10, '123 Gull Street', 'Johannesburg', '0', '2356', 'James Mabele', NULL, '0627895243', 'james.mabele@live.com', 'James Mabele', 'Father', '0627895243', '[7,5]', 'Greate', NULL, 0, NULL, NULL, 1, 1, NULL, NULL, 'pending', '2025-06-25 22:22:02', '2025-06-25 22:22:02', 0, NULL, NULL, NULL, NULL, 0, 'pending', 3, NULL),
(19, 2, NULL, 'Matome', 'Letsatsi', 'matome.letsatsi@live.com', '0835648978', '2012-08-05', 'Male', 'Fernadale High School', 10, '123 Gull Street', 'Johannesburg', '0', '2120', 'Thembi Letsatsi', NULL, '0817851714', 'thembi.letsatsi@live.com', 'Thembi Letsatsi', 'Mother', '0817851714', '[7,6]', 'Best', NULL, 0, NULL, NULL, 1, 1, NULL, NULL, 'pending', '2025-06-25 22:31:04', '2025-06-25 22:31:04', 0, NULL, NULL, NULL, NULL, 0, 'pending', 3, NULL);

--
-- Triggers `holiday_program_attendees`
--
DELIMITER $$
CREATE TRIGGER `holiday_program_capacity_check` AFTER INSERT ON `holiday_program_attendees` FOR EACH ROW BEGIN
    DECLARE v_confirmed_count INT DEFAULT 0;
    DECLARE v_max_participants INT DEFAULT 0;
    DECLARE v_auto_close TINYINT DEFAULT 0;
    DECLARE v_registration_open TINYINT DEFAULT 0;
    
    -- Get program details
    SELECT max_participants, auto_close_on_capacity, registration_open
    INTO v_max_participants, v_auto_close, v_registration_open
    FROM holiday_programs 
    WHERE id = NEW.program_id;
    
    -- Count confirmed attendees
    SELECT COUNT(*) INTO v_confirmed_count
    FROM holiday_program_attendees 
    WHERE program_id = NEW.program_id AND status = 'confirmed';
    
    -- Auto-close if capacity reached and auto-close is enabled
    IF v_auto_close = 1 AND v_registration_open = 1 AND v_confirmed_count >= v_max_participants THEN
        UPDATE holiday_programs 
        SET registration_open = 0, updated_at = NOW()
        WHERE id = NEW.program_id;
        
        INSERT INTO holiday_program_status_log 
        (program_id, old_status, new_status, change_reason, ip_address, created_at)
        VALUES 
        (NEW.program_id, 1, 0, 'Auto-closed: capacity reached', 'system', NOW());
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `holiday_program_capacity_check_update` AFTER UPDATE ON `holiday_program_attendees` FOR EACH ROW BEGIN
    DECLARE v_confirmed_count INT DEFAULT 0;
    DECLARE v_max_participants INT DEFAULT 0;
    DECLARE v_auto_close TINYINT DEFAULT 0;
    DECLARE v_registration_open TINYINT DEFAULT 0;
    
    -- Only check if status changed to confirmed
    IF OLD.status != 'confirmed' AND NEW.status = 'confirmed' THEN
        -- Get program details
        SELECT max_participants, auto_close_on_capacity, registration_open
        INTO v_max_participants, v_auto_close, v_registration_open
        FROM holiday_programs 
        WHERE id = NEW.program_id;
        
        -- Count confirmed attendees
        SELECT COUNT(*) INTO v_confirmed_count
        FROM holiday_program_attendees 
        WHERE program_id = NEW.program_id AND status = 'confirmed';
        
        -- Auto-close if capacity reached and auto-close is enabled
        IF v_auto_close = 1 AND v_registration_open = 1 AND v_confirmed_count >= v_max_participants THEN
            UPDATE holiday_programs 
            SET registration_open = 0, updated_at = NOW()
            WHERE id = NEW.program_id;
            
            INSERT INTO holiday_program_status_log 
            (program_id, old_status, new_status, change_reason, ip_address, created_at)
            VALUES 
            (NEW.program_id, 1, 0, 'Auto-closed: capacity reached', 'system', NOW());
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_audit_trail`
--

CREATE TABLE `holiday_program_audit_trail` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_cohorts`
--

CREATE TABLE `holiday_program_cohorts` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `max_participants` int DEFAULT '20',
  `current_participants` int DEFAULT '0',
  `status` enum('active','full','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `holiday_program_cohorts`
--

INSERT INTO `holiday_program_cohorts` (`id`, `program_id`, `name`, `start_date`, `end_date`, `max_participants`, `current_participants`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Week 1 - Morning Cohort', '2025-07-07', '2025-07-11', 20, 0, 'active', '2025-06-23 18:05:43', '2025-06-23 18:05:43'),
(2, 1, 'Week 2 - Morning Cohort', '2025-07-14', '2025-07-18', 20, 0, 'active', '2025-06-23 18:05:43', '2025-06-23 18:05:43'),
(3, 2, 'Week 1 Group', '2025-06-30', '2025-07-04', 30, 0, 'active', '2025-06-23 18:16:39', '2025-06-23 18:16:39'),
(4, 2, 'Week 2 Group', '2025-07-07', '2025-07-11', 30, 0, 'active', '2025-06-23 18:17:34', '2025-06-23 18:17:34');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_criteria`
--

CREATE TABLE `holiday_program_criteria` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `criterion` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `order_number` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
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
-- Stand-in structure for view `holiday_program_dashboard_stats`
-- (See below for the actual view)
--
CREATE TABLE `holiday_program_dashboard_stats` (
`auto_close_on_capacity` tinyint(1)
,`auto_close_on_date` tinyint(1)
,`capacity_percentage` decimal(25,1)
,`capacity_status` varchar(11)
,`confirmed_registrations` bigint
,`created_at` timestamp
,`max_participants` int
,`member_registrations` bigint
,`mentor_applications` bigint
,`pending_registrations` bigint
,`program_id` int
,`registration_deadline` varchar(100)
,`registration_open` tinyint(1)
,`term` varchar(50)
,`title` varchar(255)
,`total_registrations` bigint
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_faqs`
--

CREATE TABLE `holiday_program_faqs` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `question` text COLLATE utf8mb4_general_ci NOT NULL,
  `answer` text COLLATE utf8mb4_general_ci NOT NULL,
  `order_number` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `item` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `order_number` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `attendee_id` int NOT NULL,
  `experience` text COLLATE utf8mb4_general_ci,
  `availability` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `workshop_preference` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_mentor_details`
--

INSERT INTO `holiday_program_mentor_details` (`id`, `attendee_id`, `experience`, `availability`, `workshop_preference`, `notes`, `created_at`) VALUES
(1, 2, 'sdf', 'full_time', 4, NULL, '2025-03-30 15:27:08'),
(2, 14, 'I would like to share my expertise with youth to inspire them.', 'full_time', 6, NULL, '2025-06-24 16:05:42'),
(3, 15, 'sdfwe', 'part_time', 5, NULL, '2025-06-25 11:24:28');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_notifications`
--

CREATE TABLE `holiday_program_notifications` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `notification_type` enum('status_change','capacity_warning','deadline_reminder','registration_received') COLLATE utf8mb4_general_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `recipient_emails` text COLLATE utf8mb4_general_ci,
  `last_sent` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_offerings`
--

CREATE TABLE `holiday_program_offerings` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `workshop_id` int NOT NULL,
  `cohort_id` int DEFAULT NULL,
  `offering_date` date DEFAULT NULL,
  `time_slot` varchar(50) DEFAULT NULL,
  `available_spots` int NOT NULL,
  `status` enum('scheduled','running','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_prerequisites`
--

CREATE TABLE `holiday_program_prerequisites` (
  `id` int NOT NULL,
  `workshop_id` int NOT NULL,
  `prerequisite_type` enum('age','skill','workshop','equipment') NOT NULL,
  `requirement_value` varchar(255) NOT NULL,
  `description` text,
  `is_mandatory` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `holiday_program_prerequisites`
--

INSERT INTO `holiday_program_prerequisites` (`id`, `workshop_id`, `prerequisite_type`, `requirement_value`, `description`, `is_mandatory`, `created_at`) VALUES
(1, 1, 'age', '13', 'Minimum age requirement for graphic design workshop', 1, '2025-06-23 18:06:35'),
(2, 1, 'skill', 'basic_computer', 'Basic computer navigation and file management', 1, '2025-06-23 18:06:35'),
(3, 2, 'age', '15', 'Minimum age for video editing due to software complexity', 1, '2025-06-23 18:06:35'),
(4, 3, 'age', '12', 'Minimum age for animation workshop', 1, '2025-06-23 18:06:35');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_projects`
--

CREATE TABLE `holiday_program_projects` (
  `id` int NOT NULL,
  `attendee_id` int NOT NULL,
  `program_id` int NOT NULL,
  `workshop_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `feedback` text COLLATE utf8mb4_general_ci,
  `rating` int DEFAULT NULL,
  `status` enum('submitted','reviewed','approved','featured') COLLATE utf8mb4_general_ci DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_reports`
--

CREATE TABLE `holiday_program_reports` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `total_attendees` int DEFAULT '0',
  `male_attendees` int DEFAULT '0',
  `female_attendees` int DEFAULT '0',
  `other_attendees` int DEFAULT '0',
  `age_groups` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `grade_distribution` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `workshop_attendance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `narrative` text COLLATE utf8mb4_general_ci,
  `challenges` text COLLATE utf8mb4_general_ci,
  `outcomes` text COLLATE utf8mb4_general_ci,
  `recommendations` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_requirements`
--

CREATE TABLE `holiday_program_requirements` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `requirement` text COLLATE utf8mb4_general_ci NOT NULL,
  `order_number` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `day_number` int NOT NULL,
  `day_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `date` date DEFAULT NULL,
  `theme` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_schedules`
--

INSERT INTO `holiday_program_schedules` (`id`, `program_id`, `day_number`, `day_name`, `date`, `theme`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Monday', '2025-03-31', 'Introduction & Fundamentals', '2025-03-31 15:56:28', '2025-03-31 15:56:28'),
(2, 1, 2, 'Tuesday', '2025-04-01', 'Skill Development', '2025-03-31 15:56:28', '2025-03-31 15:56:28'),
(3, 1, 3, 'Wednesday', '2025-04-02', 'Project Development', '2025-03-31 15:56:28', '2025-03-31 15:56:28'),
(4, 1, 4, 'Thursday', '2025-04-03', 'Project Refinement', '2025-03-31 15:56:28', '2025-03-31 15:56:28'),
(5, 1, 5, 'Friday', '2025-04-04', 'Showcase & Celebration', '2025-03-31 15:56:28', '2025-03-31 15:56:28'),
(9, 2, 2, 'Week 1 - Skill Building', NULL, 'Week 1 Focus: Skill Building', '2025-06-24 05:15:15', '2025-06-24 05:15:15'),
(10, 2, 1, 'Day 1', '2025-06-30', 'Introductions', '2025-06-24 05:15:15', '2025-06-24 05:21:23'),
(11, 2, 5, 'Week 1 - Showcase', NULL, 'Week 1 Focus: Showcase', '2025-06-24 05:15:15', '2025-06-24 05:15:15'),
(13, 2, 3, 'Week 1 - Project Work', NULL, 'Week 1 Focus: Project Work', '2025-06-24 05:15:15', '2025-06-24 05:15:15'),
(14, 2, 4, 'Week 1 - Advanced Topics', NULL, 'Week 1 Focus: Advanced Topics', '2025-06-24 05:15:15', '2025-06-24 05:15:15');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_schedule_items`
--

CREATE TABLE `holiday_program_schedule_items` (
  `id` int NOT NULL,
  `schedule_id` int NOT NULL,
  `time_slot` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `activity` text COLLATE utf8mb4_general_ci NOT NULL,
  `session_type` enum('morning','afternoon') COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
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
`capacity_percentage` decimal(25,1)
,`confirmed_count` bigint
,`dates` varchar(100)
,`id` int
,`max_participants` int
,`member_count` bigint
,`mentor_count` bigint
,`pending_count` bigint
,`registration_open` tinyint(1)
,`status` enum('draft','open','closing_soon','closed','completed','cancelled')
,`term` varchar(50)
,`title` varchar(255)
,`total_registrations` bigint
,`workshop_count` bigint
);

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_status_log`
--

CREATE TABLE `holiday_program_status_log` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `changed_by` int DEFAULT NULL,
  `old_status` tinyint(1) NOT NULL DEFAULT '0',
  `new_status` tinyint(1) NOT NULL DEFAULT '0',
  `change_reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_status_log`
--

INSERT INTO `holiday_program_status_log` (`id`, `program_id`, `changed_by`, `old_status`, `new_status`, `change_reason`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, NULL, 0, 1, 'Registration opened', 'system', NULL, '2025-06-24 11:57:11'),
(2, 1, NULL, 1, 0, 'Registration closed', 'system', NULL, '2025-06-24 14:24:49');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_program_workshops`
--

CREATE TABLE `holiday_program_workshops` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `instructor` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `max_participants` int DEFAULT '15',
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `prerequisites` text COLLATE utf8mb4_general_ci COMMENT 'Workshop prerequisites and requirements',
  `cohort_id` int DEFAULT NULL COMMENT 'Link workshop to specific cohort',
  `week_number` int DEFAULT '1' COMMENT 'Which week of program this workshop runs'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_program_workshops`
--

INSERT INTO `holiday_program_workshops` (`id`, `program_id`, `title`, `description`, `instructor`, `max_participants`, `start_time`, `end_time`, `location`, `created_at`, `updated_at`, `prerequisites`, `cohort_id`, `week_number`) VALUES
(1, 1, 'Graphic Design Basics', 'Learn the fundamentals of graphic design using industry tools.', 'Carols Kanye', 10, NULL, NULL, NULL, '2025-03-26 19:24:54', '2025-06-23 18:06:09', 'Basic computer skills, Age 13+', NULL, 1),
(2, 1, 'Music and Video Production', 'Create and edit Music and videos using professional techniques.', 'Andrew Klaas and Philani Mbatha', 10, NULL, NULL, NULL, '2025-03-26 19:24:54', '2025-06-23 18:06:09', 'Familiarity with editing software, Age 15+', NULL, 1),
(3, 1, '3D Design Fundamentals', 'Explore the principles of 3D Design and create your 3D visualizations.', 'Samuel Kazadi ', 5, NULL, NULL, NULL, '2025-03-26 19:24:54', '2025-06-23 18:06:09', 'Creative mindset, Age 12+', NULL, 2),
(4, 1, 'Animation Fundamentals', 'Explore the principles of animation and create your animated shorts.', 'Vuyani Magibisela', 5, NULL, NULL, NULL, '2025-03-26 19:24:54', '2025-03-31 19:33:24', NULL, NULL, 1),
(5, 2, 'Programming AI Projects', 'Focus: Machine learning algorithms, data analysis, and AI model development\r\nLearning Objectives:\r\n•	Understand fundamental AI and machine learning concepts\r\n•	Implement basic machine learning algorithms from scratch\r\n•	Work with AI libraries and frameworks\r\n•	Develop data preprocessing and analysis skills\r\n•	Create AI models for classification and prediction\r\n', 'Vuyani', 10, NULL, NULL, 'Sci-Bono Clubhouse', '2025-06-09 14:36:41', '2025-06-09 14:36:41', NULL, NULL, 1),
(6, 2, 'Web Projects', 'Focus: Web-based AI applications, APIs, and interactive AI interfaces\r\nLearning Objectives:\r\n•	Build responsive web applications with AI integration\r\n•	Understand API development and consumption\r\n•	Create interactive user interfaces for AI systems\r\n•	Implement real-time AI features in web applications\r\n•	Deploy web applications with AI capabilities\r\n', 'Phamela', 12, NULL, NULL, 'Sci-Bono YDP Classroom', '2025-06-09 14:36:41', '2025-06-09 14:36:41', NULL, NULL, 1),
(7, 2, 'Electronics Projects', 'Focus: AI-powered hardware, IoT devices, and embedded systems\r\nLearning Objectives:\r\n•	Understand microcontroller programming\r\n•	Integrate sensors and actuators with AI processing\r\n•	Develop IoT applications with AI capabilities\r\n•	Create autonomous systems using AI\r\n•	Build AI-powered robotics projects\r\n', 'Simphiwe Phiri', 8, NULL, NULL, 'Sci-Bono Simplon Centre', '2025-06-09 14:36:41', '2025-06-09 14:36:41', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `holiday_report_images`
--

CREATE TABLE `holiday_report_images` (
  `id` int NOT NULL,
  `report_id` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `caption` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_workshop_enrollment`
--

CREATE TABLE `holiday_workshop_enrollment` (
  `id` int NOT NULL,
  `attendee_id` int NOT NULL,
  `workshop_id` int NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `attendance_status` enum('registered','attended','absent','excused') COLLATE utf8mb4_general_ci DEFAULT 'registered',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `lesson_id` int NOT NULL,
  `status` enum('not_started','in_progress','completed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'not_started',
  `progress` float NOT NULL DEFAULT '0',
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `completion_date` timestamp NULL DEFAULT NULL,
  `last_accessed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_sections`
--

CREATE TABLE `lesson_sections` (
  `id` int NOT NULL,
  `lesson_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_general_ci,
  `section_type` enum('text','video','interactive','quiz','assignment') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'text',
  `video_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estimated_duration_minutes` int DEFAULT NULL,
  `order_number` int NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_reports`
--

CREATE TABLE `monthly_reports` (
  `id` int NOT NULL,
  `report_date` date NOT NULL,
  `total_attendees` int NOT NULL DEFAULT '0',
  `male_attendees` int NOT NULL DEFAULT '0',
  `female_attendees` int NOT NULL DEFAULT '0',
  `age_groups` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `narrative` text COLLATE utf8mb4_general_ci,
  `challenges` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

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
  `id` int NOT NULL,
  `report_id` int NOT NULL,
  `program_id` int NOT NULL,
  `participants` int NOT NULL DEFAULT '0',
  `completed_projects` int NOT NULL DEFAULT '0',
  `in_progress_projects` int NOT NULL DEFAULT '0',
  `not_started_projects` int NOT NULL DEFAULT '0',
  `narrative` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `activity_id` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
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
-- Stand-in structure for view `program_structure_view`
-- (See below for the actual view)
--
CREATE TABLE `program_structure_view` (
`duration_weeks` json
,`end_date` date
,`has_cohorts` json
,`has_prerequisites` json
,`id` int
,`start_date` date
,`title` varchar(255)
,`total_attendees` bigint
,`total_cohorts` bigint
,`total_workshops` bigint
);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_answers`
--

CREATE TABLE `quiz_answers` (
  `id` int NOT NULL,
  `question_id` int NOT NULL,
  `answer_text` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `order_number` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `quiz_id` int NOT NULL,
  `score` float NOT NULL DEFAULT '0',
  `percentage` float NOT NULL DEFAULT '0',
  `passed` tinyint(1) NOT NULL DEFAULT '0',
  `time_spent_seconds` int DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int NOT NULL,
  `quiz_id` int NOT NULL,
  `question_text` text COLLATE utf8mb4_general_ci NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','matching') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'multiple_choice',
  `points` int NOT NULL DEFAULT '1',
  `order_number` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skill_activities`
--

CREATE TABLE `skill_activities` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `skill_category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `technique_taught` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tools_required` text COLLATE utf8mb4_general_ci,
  `materials_needed` text COLLATE utf8mb4_general_ci,
  `difficulty_level` enum('Beginner','Intermediate','Advanced') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Beginner',
  `estimated_duration_minutes` int DEFAULT NULL,
  `final_outcome_description` text COLLATE utf8mb4_general_ci,
  `instructions` longtext COLLATE utf8mb4_general_ci,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `video_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resources_links` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `view_count` int DEFAULT '0',
  `completion_count` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skill_activity_completions`
--

CREATE TABLE `skill_activity_completions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `skill_activity_id` int NOT NULL,
  `completion_percentage` decimal(5,2) DEFAULT '0.00',
  `current_step` int DEFAULT '1',
  `final_submission_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `rating` int DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_general_ci,
  `status` enum('started','in_progress','completed','abandoned') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'started',
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `skill_activity_steps`
--

CREATE TABLE `skill_activity_steps` (
  `id` int NOT NULL,
  `skill_activity_id` int NOT NULL,
  `step_number` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `instructions` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `video_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estimated_duration_minutes` int DEFAULT NULL,
  `tips` text COLLATE utf8mb4_general_ci,
  `common_mistakes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT 'default@example.com',
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `user_type` enum('admin','mentor','member','alumni','community') NOT NULL DEFAULT 'member',
  `date_of_birth` date DEFAULT NULL,
  `Gender` enum('Male','Female','Other') NOT NULL,
  `grade` int DEFAULT NULL,
  `school` varchar(255) DEFAULT NULL,
  `parent` varchar(255) DEFAULT NULL,
  `parent_email` varchar(255) DEFAULT NULL,
  `leaner_number` int DEFAULT NULL,
  `parent_number` int DEFAULT NULL,
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
  `emergency_contact_address` text,
  `interests` text,
  `role_models` text,
  `goals` text,
  `has_computer` tinyint(1) DEFAULT NULL,
  `computer_skills` text,
  `computer_skills_source` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `name`, `surname`, `user_type`, `date_of_birth`, `Gender`, `grade`, `school`, `parent`, `parent_email`, `leaner_number`, `parent_number`, `Relationship`, `Center`, `nationality`, `id_number`, `home_language`, `address_street`, `address_suburb`, `address_city`, `address_province`, `address_postal_code`, `medical_aid_name`, `medical_aid_holder`, `medical_aid_number`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `emergency_contact_email`, `emergency_contact_address`, `interests`, `role_models`, `goals`, `has_computer`, `computer_skills`, `computer_skills_source`) VALUES
(1, 'vuyani_magibisela', 'vuyani.magibisela@sci-bono.co.za', '$2y$10$OEkQUqNT9pp8F.oBn/nAquaBuxyo7.8a0QCiWLXw37ECwvzXWNZDy', 'Vuyani', 'Magibisela', 'admin', '1990-08-23', 'Male', NULL, NULL, NULL, NULL, 638393157, NULL, NULL, 'Sci-Bono Clubhouse', 'South African', '9008235531088', 'isiXhosa', '123 Gull Street', 'Soweto', 'Johannesburg', 'Gauteng', '2021', 'Discovery', 'Vuyani Magibisela', '12345685', 'Mandisa', 'Mother', '0726611543', 'mandisa.magibisela@gmail.com', '123 Bob Street \r\nQueens Town\r\nEastern Cape', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'itumeleng_kgakane', 'itum@gmail.com', '$2y$10$cH5JP8jdlORJuJya9qBHGOCrqHzP4gnefjxdQQk3yEqV2SKdsOdgi', 'Itumeleng', 'Kgakane', 'member', '2000-01-12', 'Male', 12, 'Fernadale High School', 'Mandi', 'mandi@gmail.com', 0, 736933940, 'Mother', 'Sci-Bono Clubhouse', 'South African', '0001125531088', 'Setswana', '123 Good Street', 'Soweto', 'Johannesburg', 'Gauteng', '1920', NULL, NULL, NULL, 'Mandi', 'Mother', '0832342342', 'mandi@gmail.com', '123 Good street, Soweto, Johannesburg, Gauteng, South Africa', 'Sports', 'Khaya', 'Be the best', 1, 'Digital Drawing', 'At home'),
(4, 'themba_magibisela', 'themba@example.co.za', '13378', 'Themba', 'Magibisela', 'mentor', '2018-01-01', 'Male', NULL, NULL, NULL, NULL, 835562525, NULL, NULL, 'Sci-Bono Clubhouse', 'South African', '1801025522088', 'English', '123 Good Street', 'Soweto', 'Johannesburg', '', '2300', NULL, NULL, NULL, 'Mandi', 'Mother', '0721166543', 'mandi@gmail.com', '123 Good Street \r\nSoweto\r\n23000', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Sam_King', 'sam@example.com', '$2y$10$aSK4SUKKNstMnefJ0VKfs.NyBF32SHBC5wv.ALf2w1HvBrK1hLqgK', 'Sam', 'Kabanga', 'mentor', '2025-02-21', 'Male', 9, '0', 'Bonga Kabanga', 'bonga.kabanga@example.com', 688965565, 868963125, 'Father', 'Sci-Bono Clubhouse', 'South African', '', 'isiNdebele', '123 Good Street', 'Soweto', 'Johannesburg', '', '1920', NULL, NULL, NULL, 'Thabo', 'Uncle', '0832342342', 'thabo@gmail.com', '123 Good street', '', '', '', 1, '', ''),
(8, 'jabu_khumalo ', 'jabut@example.com', '$2y$10$n8Pb/bmu/7rO7KJSlXkaFeaTjabFz7anFGZmxtvWmkvADhZVbIh0S', 'Jabu', 'Khumalo', 'mentor', '2025-02-21', 'Male', 0, '0', '0', '0', 0, 0, '', 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'lebo_skhosana', '', '$2y$10$4.fc0AJKIOfj.YPKp0sePOtDllx98DA8yIMl14wv5hptlsDMEY84O', 'Lebo', 'Skhosana', 'admin', '0000-00-00', 'Male', 0, '0', '0', '0', 0, 0, '', 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'Tim_M', '', '$2y$10$5G/8yYmZdhejlA16qu30Q.Bc/k7E8V7KLvUcnNTcT.xKcbSilpNYW', 'Tim', 'Shabango', 'member', '0000-00-00', 'Male', 0, '0', '0', '0', 0, 0, '', 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'linda_skhosana', 'default@example.com', '$2y$10$OuPeaMzVXhi2gNKmiHKcPOZma8MAaDulkn8Q7mINUh4D.LAhsNYle', 'Linda', 'Skhosana', 'member', '2004-09-12', 'Female', 10, 'Malvern High School', 'Mandi', 'mandi@gmail.com', 719948640, 766703421, 'Mother', 'Mapetla Solar Lab', 'South African', '0409120000000', 'Afrikaans', '123 Good Street', 'Soweto', 'Johannesburg', '', '2000', NULL, NULL, NULL, 'Thabo', 'Father', '0716640054', 'thabo@gmail.com', 'address', 'yes', 'yes', 'yes', 1, 'yes', 'yes'),
(12, 'Pammy', 'default@example.com', '$2y$10$XVbGAfHni2g96herJzNxS.vK69a/1GQVLYnskgkPzw0GKskVV5Ql2', 'Pamela Sithandweyinkosi ', 'Ngwenya', 'mentor', NULL, 'Female', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'NkuliTheGreat', 'default@example.com', '$2y$10$IjHh78npTETLCK2kgZ5UR.Anib0Pgjx91eOq//BiKb8I5iZyt6Y9m', 'Nonkululeko ', 'Shongwe ', 'admin', NULL, 'Female', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'Pammy', 'default@example.com', '$2y$10$4naNeRn30g44Fl7KZ0KAzu/bfmsxus8nY/RHowmHgiRgkX7SArqHa', 'Pamela Sithandweyinkosi ', 'Ngwenya', 'mentor', NULL, 'Female', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Sci-Bono Clubhouse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'Kgotso_Maponya', 'kgotso.maponya@live.com', '$2y$10$fE6/F4ryBS1f1oC0dTnHY.n6fHAKx50tJxJevZkHKAr8fy1gzIUfS', 'Kgotso', 'Maponya', 'member', '2010-10-05', 'Male', 10, 'Fernadale High School', 'John', 'john.maponya@live.com', 638393157, 863895689, 'Father', 'Sci-Bono Clubhouse', 'South African', '201005890888', 'Sesotho', '123 Good Street', 'Soweto', 'Johannesburg', 'Gauteng', '2002', NULL, NULL, NULL, 'John Maponya', 'Father', '086895689', 'john.maponya@live.com', '123 Good Street\r\nSoweto\r\nJohannesburg\r\n2002', 'Gaming', 'Mom', 'Be the best developer', 1, 'Program', 'Home and School');

-- --------------------------------------------------------

--
-- Table structure for table `user_enrollments`
--

CREATE TABLE `user_enrollments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `progress` float NOT NULL DEFAULT '0',
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `completion_date` timestamp NULL DEFAULT NULL,
  `last_accessed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_id` int DEFAULT NULL,
  `module_id` int DEFAULT NULL,
  `lesson_id` int DEFAULT NULL,
  `lesson_section_id` int DEFAULT NULL,
  `activity_id` int DEFAULT NULL,
  `progress_type` enum('course','module','lesson','lesson_section','activity') COLLATE utf8mb4_general_ci NOT NULL,
  `completion_percentage` decimal(5,2) DEFAULT '0.00',
  `total_points_earned` int DEFAULT '0',
  `total_points_possible` int DEFAULT '0',
  `status` enum('not_started','in_progress','completed','passed','failed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'not_started',
  `last_accessed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_question_answers`
--

CREATE TABLE `user_question_answers` (
  `id` int NOT NULL,
  `attempt_id` int NOT NULL,
  `question_id` int NOT NULL,
  `answer_id` int DEFAULT NULL,
  `text_answer` text COLLATE utf8mb4_general_ci,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `points_earned` float NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitors`
--

CREATE TABLE `visitors` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `surname` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `age` int NOT NULL,
  `grade_school` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `student_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parent_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `parent_surname` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `visitor_id` int NOT NULL,
  `sign_in_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sign_out_time` timestamp NULL DEFAULT NULL,
  `comment` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `visitor_id`, `sign_in_time`, `sign_out_time`, `comment`) VALUES
(1, 1, '2025-06-05 14:32:22', NULL, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `workshop_capacity_view`
-- (See below for the actual view)
--
CREATE TABLE `workshop_capacity_view` (
`available_spots` bigint
,`capacity_percentage` decimal(25,1)
,`cohort_id` int
,`cohort_name` varchar(100)
,`enrolled_count` bigint
,`max_participants` int
,`prerequisites` text
,`title` varchar(255)
,`workshop_id` int
);

-- --------------------------------------------------------

--
-- Structure for view `holiday_programs_with_status`
--
DROP TABLE IF EXISTS `holiday_programs_with_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `holiday_programs_with_status`  AS SELECT `p`.`id` AS `id`, `p`.`term` AS `term`, `p`.`title` AS `title`, `p`.`description` AS `description`, `p`.`dates` AS `dates`, `p`.`time` AS `time`, `p`.`start_date` AS `start_date`, `p`.`end_date` AS `end_date`, `p`.`location` AS `location`, `p`.`age_range` AS `age_range`, `p`.`lunch_included` AS `lunch_included`, `p`.`program_goals` AS `program_goals`, `p`.`registration_deadline` AS `registration_deadline`, `p`.`max_participants` AS `max_participants`, `p`.`registration_open` AS `registration_open`, `p`.`status` AS `status`, `p`.`created_at` AS `created_at`, `p`.`updated_at` AS `updated_at`, (case when ((`p`.`status` = 'completed') or (`p`.`end_date` < curdate())) then 'Completed' when (`p`.`status` = 'cancelled') then 'Cancelled' when (`p`.`status` = 'draft') then 'Draft' when ((`p`.`registration_open` = 1) and (`p`.`status` in ('open','closing_soon'))) then 'Registration Open' when ((`p`.`registration_open` = 0) or (`p`.`status` = 'closed')) then 'Registration Closed' when (`p`.`start_date` > curdate()) then 'Upcoming' when (curdate() between `p`.`start_date` and `p`.`end_date`) then 'In Progress' else 'Unknown' end) AS `display_status`, (select count(0) from `holiday_program_attendees` where (`holiday_program_attendees`.`program_id` = `p`.`id`)) AS `total_registrations`, (select count(0) from `holiday_program_attendees` where ((`holiday_program_attendees`.`program_id` = `p`.`id`) and (`holiday_program_attendees`.`mentor_registration` = 0))) AS `member_registrations`, (select count(0) from `holiday_program_attendees` where ((`holiday_program_attendees`.`program_id` = `p`.`id`) and (`holiday_program_attendees`.`mentor_registration` = 1))) AS `mentor_registrations`, (select count(0) from `holiday_program_workshops` where (`holiday_program_workshops`.`program_id` = `p`.`id`)) AS `workshop_count` FROM `holiday_programs` AS `p` ;

-- --------------------------------------------------------

--
-- Structure for view `holiday_program_dashboard_stats`
--
DROP TABLE IF EXISTS `holiday_program_dashboard_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `holiday_program_dashboard_stats`  AS SELECT `p`.`id` AS `program_id`, `p`.`term` AS `term`, `p`.`title` AS `title`, `p`.`registration_open` AS `registration_open`, `p`.`max_participants` AS `max_participants`, `p`.`auto_close_on_capacity` AS `auto_close_on_capacity`, `p`.`auto_close_on_date` AS `auto_close_on_date`, `p`.`registration_deadline` AS `registration_deadline`, `p`.`created_at` AS `created_at`, `p`.`updated_at` AS `updated_at`, count(`a`.`id`) AS `total_registrations`, count((case when (`a`.`status` = 'confirmed') then 1 end)) AS `confirmed_registrations`, count((case when (`a`.`status` = 'pending') then 1 end)) AS `pending_registrations`, count((case when (`a`.`mentor_registration` = 1) then 1 end)) AS `mentor_applications`, count((case when (`a`.`mentor_registration` = 0) then 1 end)) AS `member_registrations`, (case when (`p`.`max_participants` > 0) then round(((count((case when (`a`.`status` = 'confirmed') then 1 end)) / `p`.`max_participants`) * 100),1) else 0 end) AS `capacity_percentage`, (case when (count((case when (`a`.`status` = 'confirmed') then 1 end)) >= `p`.`max_participants`) then 'full' when (count((case when (`a`.`status` = 'confirmed') then 1 end)) >= (`p`.`max_participants` * 0.9)) then 'nearly_full' when (count((case when (`a`.`status` = 'confirmed') then 1 end)) >= (`p`.`max_participants` * 0.75)) then 'filling_up' else 'available' end) AS `capacity_status` FROM (`holiday_programs` `p` left join `holiday_program_attendees` `a` on((`p`.`id` = `a`.`program_id`))) GROUP BY `p`.`id`, `p`.`term`, `p`.`title`, `p`.`registration_open`, `p`.`max_participants`, `p`.`auto_close_on_capacity`, `p`.`auto_close_on_date`, `p`.`registration_deadline`, `p`.`created_at`, `p`.`updated_at` ;

-- --------------------------------------------------------

--
-- Structure for view `holiday_program_stats`
--
DROP TABLE IF EXISTS `holiday_program_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `holiday_program_stats`  AS SELECT `p`.`id` AS `id`, `p`.`term` AS `term`, `p`.`title` AS `title`, `p`.`dates` AS `dates`, `p`.`registration_open` AS `registration_open`, `p`.`status` AS `status`, `p`.`max_participants` AS `max_participants`, count(distinct `a`.`id`) AS `total_registrations`, count(distinct (case when (`a`.`mentor_registration` = 0) then `a`.`id` end)) AS `member_count`, count(distinct (case when (`a`.`mentor_registration` = 1) then `a`.`id` end)) AS `mentor_count`, count(distinct (case when (`a`.`registration_status` = 'confirmed') then `a`.`id` end)) AS `confirmed_count`, count(distinct (case when (`a`.`registration_status` = 'pending') then `a`.`id` end)) AS `pending_count`, count(distinct `w`.`id`) AS `workshop_count`, round(((count(distinct `a`.`id`) / `p`.`max_participants`) * 100),1) AS `capacity_percentage` FROM ((`holiday_programs` `p` left join `holiday_program_attendees` `a` on((`p`.`id` = `a`.`program_id`))) left join `holiday_program_workshops` `w` on((`p`.`id` = `w`.`program_id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `program_structure_view`
--
DROP TABLE IF EXISTS `program_structure_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`vuksDev`@`localhost` SQL SECURITY DEFINER VIEW `program_structure_view`  AS SELECT `p`.`id` AS `id`, `p`.`title` AS `title`, `p`.`start_date` AS `start_date`, `p`.`end_date` AS `end_date`, json_extract(`p`.`program_structure`,'$.duration_weeks') AS `duration_weeks`, json_extract(`p`.`program_structure`,'$.cohort_system') AS `has_cohorts`, json_extract(`p`.`program_structure`,'$.prerequisites_enabled') AS `has_prerequisites`, count(distinct `c`.`id`) AS `total_cohorts`, count(distinct `w`.`id`) AS `total_workshops`, count(distinct `a`.`id`) AS `total_attendees` FROM (((`holiday_programs` `p` left join `holiday_program_cohorts` `c` on((`p`.`id` = `c`.`program_id`))) left join `holiday_program_workshops` `w` on((`p`.`id` = `w`.`program_id`))) left join `holiday_program_attendees` `a` on((`p`.`id` = `a`.`program_id`))) GROUP BY `p`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `workshop_capacity_view`
--
DROP TABLE IF EXISTS `workshop_capacity_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`vuksDev`@`localhost` SQL SECURITY DEFINER VIEW `workshop_capacity_view`  AS SELECT `w`.`id` AS `workshop_id`, `w`.`title` AS `title`, `w`.`max_participants` AS `max_participants`, `w`.`prerequisites` AS `prerequisites`, `c`.`name` AS `cohort_name`, `c`.`id` AS `cohort_id`, count(distinct `a`.`id`) AS `enrolled_count`, (`w`.`max_participants` - count(distinct `a`.`id`)) AS `available_spots`, (case when (`w`.`max_participants` > 0) then round(((count(distinct `a`.`id`) / `w`.`max_participants`) * 100),1) else 0 end) AS `capacity_percentage` FROM ((`holiday_program_workshops` `w` left join `holiday_program_cohorts` `c` on((`w`.`cohort_id` = `c`.`id`))) left join `holiday_program_attendees` `a` on(((`w`.`program_id` = `a`.`program_id`) and json_contains(`a`.`workshop_preference`,cast(`w`.`id` as json))))) GROUP BY `w`.`id`, `c`.`id` ;

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
  ADD KEY `idx_term` (`term`),
  ADD KEY `idx_updated_at` (`updated_at`),
  ADD KEY `idx_program_dates` (`start_date`,`end_date`);

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
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_attendee_status` (`status`),
  ADD KEY `idx_attendee_mentor` (`mentor_registration`),
  ADD KEY `idx_attendee_cohort` (`cohort_id`);

--
-- Indexes for table `holiday_program_audit_trail`
--
ALTER TABLE `holiday_program_audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program_audit` (`program_id`),
  ADD KEY `idx_user_audit` (`user_id`),
  ADD KEY `idx_action_audit` (`action`),
  ADD KEY `idx_created_audit` (`created_at`);

--
-- Indexes for table `holiday_program_cohorts`
--
ALTER TABLE `holiday_program_cohorts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program_dates` (`program_id`,`start_date`,`end_date`);

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
-- Indexes for table `holiday_program_notifications`
--
ALTER TABLE `holiday_program_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_program_notification` (`program_id`,`notification_type`),
  ADD KEY `idx_program_notification` (`program_id`,`notification_type`);

--
-- Indexes for table `holiday_program_offerings`
--
ALTER TABLE `holiday_program_offerings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_offering` (`workshop_id`,`cohort_id`,`offering_date`),
  ADD KEY `idx_program_schedule` (`program_id`,`offering_date`),
  ADD KEY `fk_offerings_cohort` (`cohort_id`);

--
-- Indexes for table `holiday_program_prerequisites`
--
ALTER TABLE `holiday_program_prerequisites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workshop_prereqs` (`workshop_id`,`prerequisite_type`);

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
-- Indexes for table `holiday_program_status_log`
--
ALTER TABLE `holiday_program_status_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program_id` (`program_id`),
  ADD KEY `idx_changed_by` (`changed_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `holiday_program_workshops`
--
ALTER TABLE `holiday_program_workshops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `idx_program_id` (`program_id`),
  ADD KEY `idx_instructor` (`instructor`),
  ADD KEY `idx_workshop_cohort` (`cohort_id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `clubhouse_programs`
--
ALTER TABLE `clubhouse_programs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `clubhouse_reports`
--
ALTER TABLE `clubhouse_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `course_activities`
--
ALTER TABLE `course_activities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `course_assessments`
--
ALTER TABLE `course_assessments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_categories`
--
ALTER TABLE `course_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_category_relationships`
--
ALTER TABLE `course_category_relationships`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_certificates`
--
ALTER TABLE `course_certificates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_lessons`
--
ALTER TABLE `course_lessons`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `course_modules`
--
ALTER TABLE `course_modules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `course_prerequisites`
--
ALTER TABLE `course_prerequisites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_quizzes`
--
ALTER TABLE `course_quizzes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_ratings`
--
ALTER TABLE `course_ratings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_sections`
--
ALTER TABLE `course_sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `dell_surveys`
--
ALTER TABLE `dell_surveys`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_programs`
--
ALTER TABLE `holiday_programs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_program_attendance`
--
ALTER TABLE `holiday_program_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_program_attendees`
--
ALTER TABLE `holiday_program_attendees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_program_audit_trail`
--
ALTER TABLE `holiday_program_audit_trail`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_program_cohorts`
--
ALTER TABLE `holiday_program_cohorts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `holiday_program_criteria`
--
ALTER TABLE `holiday_program_criteria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `holiday_program_faqs`
--
ALTER TABLE `holiday_program_faqs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `holiday_program_items`
--
ALTER TABLE `holiday_program_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `holiday_program_mentor_details`
--
ALTER TABLE `holiday_program_mentor_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `holiday_program_notifications`
--
ALTER TABLE `holiday_program_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_program_offerings`
--
ALTER TABLE `holiday_program_offerings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_program_prerequisites`
--
ALTER TABLE `holiday_program_prerequisites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `holiday_program_projects`
--
ALTER TABLE `holiday_program_projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_program_reports`
--
ALTER TABLE `holiday_program_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_program_requirements`
--
ALTER TABLE `holiday_program_requirements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `holiday_program_schedules`
--
ALTER TABLE `holiday_program_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `holiday_program_schedule_items`
--
ALTER TABLE `holiday_program_schedule_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `holiday_program_status_log`
--
ALTER TABLE `holiday_program_status_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `holiday_program_workshops`
--
ALTER TABLE `holiday_program_workshops`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `holiday_report_images`
--
ALTER TABLE `holiday_report_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_workshop_enrollment`
--
ALTER TABLE `holiday_workshop_enrollment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lesson_sections`
--
ALTER TABLE `lesson_sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_report_activities`
--
ALTER TABLE `monthly_report_activities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `monthly_report_images`
--
ALTER TABLE `monthly_report_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skill_activities`
--
ALTER TABLE `skill_activities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skill_activity_completions`
--
ALTER TABLE `skill_activity_completions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skill_activity_steps`
--
ALTER TABLE `skill_activity_steps`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_enrollments`
--
ALTER TABLE `user_enrollments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_question_answers`
--
ALTER TABLE `user_question_answers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visitors`
--
ALTER TABLE `visitors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  ADD CONSTRAINT `fk_attendees_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `holiday_program_cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mentor_workshop` FOREIGN KEY (`mentor_workshop_preference`) REFERENCES `holiday_program_workshops` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `holiday_program_audit_trail`
--
ALTER TABLE `holiday_program_audit_trail`
  ADD CONSTRAINT `audit_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `audit_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `holiday_program_cohorts`
--
ALTER TABLE `holiday_program_cohorts`
  ADD CONSTRAINT `fk_cohorts_program` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `holiday_program_notifications`
--
ALTER TABLE `holiday_program_notifications`
  ADD CONSTRAINT `notification_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_program_offerings`
--
ALTER TABLE `holiday_program_offerings`
  ADD CONSTRAINT `fk_offerings_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `holiday_program_cohorts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_offerings_program` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_offerings_workshop` FOREIGN KEY (`workshop_id`) REFERENCES `holiday_program_workshops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holiday_program_prerequisites`
--
ALTER TABLE `holiday_program_prerequisites`
  ADD CONSTRAINT `fk_prerequisites_workshop` FOREIGN KEY (`workshop_id`) REFERENCES `holiday_program_workshops` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `holiday_program_status_log`
--
ALTER TABLE `holiday_program_status_log`
  ADD CONSTRAINT `status_log_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `status_log_user_fk` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `holiday_program_workshops`
--
ALTER TABLE `holiday_program_workshops`
  ADD CONSTRAINT `fk_workshops_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `holiday_program_cohorts` (`id`) ON DELETE SET NULL,
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
