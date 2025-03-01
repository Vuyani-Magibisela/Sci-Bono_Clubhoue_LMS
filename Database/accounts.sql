-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 01, 2025 at 02:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(48, 1, '2024-06-03 21:57:22', '2024-06-03 21:57:25', 'signedOut');

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
(5, 'FLL', 'FLL is great', 'Coding and Robotics', '9-16 years', '6 Months', 10, '0', 'Beginner', '2024-09-13 14:47:07', '2024-09-13 14:47:07'),
(6, 'AR', 'A lesson on Augmented reality and how it can be used in our daily life\'s to help us improve the quality of our life.', 'Learn about AR\r\nLearn about use of AR\r\nUse AR to solve problems', '14', '1 week', 20, '0', 'Beginner', '2024-12-10 02:04:26', '2024-12-10 02:04:26');

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
(3, '3', 24, 'Summer camp', 'Hot', '2024-09/66e889ae59f47_pikaso_texttoimage_Futuristic-robot-cyberspace-digital-world-revoluti.jpeg', '2024-09-16 19:40:30'),
(4, '6', 15, 'Learners went through the basics of AR, they experimented with the hardware and the sofware and where able to test a variety of AR apps. Members are now keen to creating their own AR experiences using meta studio.', 'Slow internet was a big challenge when trying to use the AR apps.', '2024-12/6757a499935ff_20241204_093249.jpg', '2024-12-10 02:16:57');

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
  `Center` enum('Sci-Bono Clubhouse','Waverly Girls Solar Lab','Mapetla Solar Lab','Emdeni Solar Lab') NOT NULL DEFAULT 'Sci-Bono Clubhouse',
  `date_of_birth` date DEFAULT NULL,
  `grade` int(12) DEFAULT NULL,
  `school` varchar(255) DEFAULT NULL,
  `parent` varchar(255) DEFAULT NULL,
  `parent_email` varchar(255) DEFAULT NULL,
  `leaner_number` int(10) DEFAULT NULL,
  `parent_number` int(10) DEFAULT NULL,
  `Relationship` varchar(255) DEFAULT NULL,
  `Gender` enum('Male','Female','Other') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `name`, `surname`, `user_type`, `Center`, `date_of_birth`, `grade`, `school`, `parent`, `parent_email`, `leaner_number`, `parent_number`, `Relationship`, `Gender`) VALUES
(1, 'vuyani_magibisela', 'vuyani.magibisela@sci-bono.co.za', '$2y$10$OEkQUqNT9pp8F.oBn/nAquaBuxyo7.8a0QCiWLXw37ECwvzXWNZDy', 'Vuyani', 'Magibisela', 'admin', 'Sci-Bono Clubhouse', '1990-08-23', 0, '0', '0', '0', 0, 0, '', 'Male'),
(2, 'itumeleng_kgakane', 'itum@gmail.com', '$2y$10$djcIfQ1gSBbtbe7dKgbphO6myGetqJ0t6F.SGlpbDvp.sOn53iSBK', 'Itumeleng', 'Kgakane', 'member', 'Waverly Girls Solar Lab', '2005-03-29', 10, 'Werverly Girls High School', 'Vuyani', 'vimagibisela@gmail.com', 638393157, 638393157, 'Father', 'Female'),
(4, 'themba_magibisela', '', '13378', 'Themba', 'Magibisela', 'mentor', 'Sci-Bono Clubhouse', '2010-05-22', 0, '0', '0', '0', 0, 0, '', 'Male'),
(5, 'themba_kgakane', '', '$2y$10$MF.CYKDPS86B4KofOayZWuSDP7ETZtLpiVXnyDO2aU3bnn4K8BHzW', 'Themba', 'Kgakane', 'mentor', 'Sci-Bono Clubhouse', '2011-01-01', 0, '0', '0', '0', 0, 0, '', 'Male'),
(6, 'Kgotso_Maponya', '', '$2y$10$8BzYI1MeWyctdaCnC6oJzufCqMd2CFSzhseZYBPz.0pQtofyv1mya', 'Kgotso', 'Maponya', 'member', 'Sci-Bono Clubhouse', '2016-06-03', 0, '0', '0', '0', 0, 0, '', 'Male'),
(7, 'Sam_King', '', '$2y$10$aSK4SUKKNstMnefJ0VKfs.NyBF32SHBC5wv.ALf2w1HvBrK1hLqgK', 'Sam', 'Kabanga', 'member', 'Sci-Bono Clubhouse', '2015-02-03', 0, '0', '0', '0', 0, 0, '', 'Male'),
(8, 'jabu_khumalo ', 'jabu.khumalo@gmail.com', '$2y$10$n8Pb/bmu/7rO7KJSlXkaFeaTjabFz7anFGZmxtvWmkvADhZVbIh0S', 'Jabu', 'Khumalo', 'mentor', 'Sci-Bono Clubhouse', '2015-08-07', 0, '0', '0', '0', 0, 0, '', 'Male'),
(9, 'lebo_skhosana', '', '$2y$10$4.fc0AJKIOfj.YPKp0sePOtDllx98DA8yIMl14wv5hptlsDMEY84O', 'Lebo', 'Skhosana', 'admin', 'Sci-Bono Clubhouse', '2014-05-20', 0, '0', '0', '0', 0, 0, '', 'Male'),
(10, 'Tim_M', '', '$2y$10$5G/8yYmZdhejlA16qu30Q.Bc/k7E8V7KLvUcnNTcT.xKcbSilpNYW', 'Tim', 'Shabango', 'member', 'Sci-Bono Clubhouse', '0000-00-00', 0, '0', '0', '0', 0, 0, '', 'Male');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `clubhouse_programs`
--
ALTER TABLE `clubhouse_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `clubhouse_reports`
--
ALTER TABLE `clubhouse_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
