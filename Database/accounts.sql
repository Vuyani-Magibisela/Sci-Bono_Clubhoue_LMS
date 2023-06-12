-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 09, 2023 at 06:46 AM
-- Server version: 10.1.29-MariaDB
-- PHP Version: 7.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(100) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `user_type` enum('admin','mentor','member') NOT NULL DEFAULT 'member',
  `date of birth` date NOT NULL,
  `grade` int(3) NOT NULL,
  `school` varchar(255) NOT NULL,
  `parent` varchar(255) NOT NULL,
  `parent email` int(255) NOT NULL,
  `leaner number` int(10) NOT NULL,
  `parent number` int(10) NOT NULL,
  `Relationship` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `name`, `surname`, `user_type`, `date of birth`, `grade`, `school`, `parent`, `parent email`, `leaner number`, `parent number`, `Relationship`) VALUES
(1, 'vuyani_magibisela', '', '$2y$10$OEkQUqNT9pp8F.oBn/nAquaBuxyo7.8a0QCiWLXw37ECwvzXWNZDy', 'Vuyani', 'Magibisela', 'admin', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(2, 'itumeleng_kgakane', '', '$2y$10$d.dK2Y1gx9G7kUrWTE.y3OgNA19ciUScf7tRl7QXBnV7Yndp8cYE.', 'Itumeleng', 'Kgakane', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(4, 'themba_magibisela', '', '$2y$10$gN04DqX.nz2Epf.oiGBlsev18477uJ6srSHX7yjEEpB3fuqVT7M5G', 'Themba', 'Magibisela', 'mentor', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(5, 'themba_kgakane', '', '$2y$10$MF.CYKDPS86B4KofOayZWuSDP7ETZtLpiVXnyDO2aU3bnn4K8BHzW', 'Themba', 'Kgakane', 'mentor', '0000-00-00', 0, '', '', 0, 0, 0, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
