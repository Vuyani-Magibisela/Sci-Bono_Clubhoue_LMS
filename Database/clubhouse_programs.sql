-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 09, 2024 at 09:46 AM
-- Server version: 10.3.39-MariaDB
-- PHP Version: 7.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vuyanjcb_users`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `clubhouse_programs`
--

INSERT INTO `clubhouse_programs` (`id`, `title`, `description`, `learning_outcomes`, `target_age_group`, `duration`, `max_participants`, `materials_needed`, `difficulty_level`, `created_at`, `updated_at`) VALUES
(1, 'Coding Basics', 'Introduction to basic programming concepts', 'Understanding of variables, loops, and conditionals', '8-12 years', '1 hour', 15, 'Computers with Scratch installed', 'Beginner', '2024-09-13 10:26:26', '2024-09-13 10:26:26'),
(2, 'Robotics Workshop', 'Hands-on experience with building and programming robots', 'Basic robotics principles and programming skills', '12-16 years', '2 hours', 10, 'Robot kits, laptops', 'Intermediate', '2024-09-13 10:26:26', '2024-09-13 10:26:26'),
(3, 'Digital Art Creation', 'Explore digital art tools and techniques', 'Proficiency in digital drawing and image editing', '10-14 years', '1.5 hours', 12, 'Tablets with drawing apps', 'Beginner', '2024-09-13 10:26:26', '2024-09-13 10:26:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clubhouse_programs`
--
ALTER TABLE `clubhouse_programs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clubhouse_programs`
--
ALTER TABLE `clubhouse_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
