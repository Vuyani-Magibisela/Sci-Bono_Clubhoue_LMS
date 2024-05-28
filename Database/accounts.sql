-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 28, 2024 at 04:59 PM
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
(3, 1, '2024-05-21 15:30:57', '2024-05-21 16:56:14', 'signedOut'),
(4, 2, '2024-05-21 15:33:06', '2024-05-21 16:44:11', 'signedOut'),
(5, 4, '2024-05-21 15:39:37', '2024-05-21 16:28:32', 'signedOut'),
(6, 5, '2024-05-21 15:43:43', '2024-05-21 16:35:22', 'signedOut'),
(7, 6, '2024-05-21 15:45:52', '2024-05-21 16:47:33', 'signedOut'),
(8, 7, '2024-05-21 15:47:48', '2024-05-21 16:49:06', 'signedOut'),
(9, 8, '2024-05-21 15:52:09', '2024-05-21 16:29:08', 'signedOut'),
(10, 9, '2024-05-21 15:54:28', '2024-05-21 16:56:10', 'signedOut'),
(11, 10, '2024-05-21 15:57:33', '2024-05-21 16:53:27', 'signedOut'),
(12, 11, '2024-05-21 15:59:53', '2024-05-21 16:28:11', 'signedOut'),
(13, 14, '2024-05-21 16:19:28', '2024-05-21 16:32:50', 'signedOut'),
(14, 15, '2024-05-21 16:21:13', '2024-05-21 16:53:03', 'signedOut'),
(15, 16, '2024-05-21 16:23:01', '2024-05-21 16:53:10', 'signedOut'),
(16, 17, '2024-05-21 16:25:10', '2024-05-21 16:43:29', 'signedOut'),
(17, 18, '2024-05-21 16:27:34', '2024-05-21 16:32:44', 'signedOut'),
(18, 19, '2024-05-21 16:31:23', '2024-05-21 16:55:59', 'signedOut'),
(19, 20, '2024-05-21 16:38:55', '2024-05-21 16:55:25', 'signedOut'),
(20, 21, '2024-05-21 16:40:02', '2024-05-21 16:55:28', 'signedOut'),
(21, 1, '2024-05-22 07:24:50', '2024-05-22 17:02:34', 'signedOut'),
(22, 4, '2024-05-22 10:23:56', '2024-05-22 16:34:14', 'signedOut'),
(23, 22, '2024-05-22 10:24:22', '2024-05-22 17:03:32', 'signedOut'),
(24, 5, '2024-05-22 10:24:40', '2024-05-22 16:33:38', 'signedOut'),
(25, 23, '2024-05-22 10:26:08', '2024-05-22 17:05:15', 'signedOut'),
(26, 24, '2024-05-22 11:19:29', '2024-05-22 14:01:28', 'signedOut'),
(27, 25, '2024-05-22 11:22:16', '2024-05-22 13:14:14', 'signedOut'),
(28, 7, '2024-05-22 12:24:20', '2024-05-22 17:03:00', 'signedOut'),
(29, 2, '2024-05-22 12:25:14', '2024-05-22 17:05:20', 'signedOut'),
(30, 2, '2024-05-22 12:25:37', '2024-05-22 17:05:20', 'signedOut'),
(31, 26, '2024-05-22 13:49:29', '2024-05-22 17:04:50', 'signedOut'),
(32, 27, '2024-05-22 13:50:25', '2024-05-22 17:04:20', 'signedOut'),
(33, 29, '2024-05-22 14:08:34', '2024-05-22 17:00:39', 'signedOut'),
(34, 15, '2024-05-22 14:09:22', '2024-05-22 17:04:05', 'signedOut'),
(35, 30, '2024-05-22 14:11:20', '2024-05-22 17:03:45', 'signedOut'),
(36, 31, '2024-05-22 14:12:53', '2024-05-22 17:03:43', 'signedOut'),
(37, 31, '2024-05-22 14:12:59', '2024-05-22 17:03:43', 'signedOut'),
(38, 32, '2024-05-22 14:28:42', '2024-05-22 17:07:31', 'signedOut'),
(39, 33, '2024-05-22 14:30:30', '2024-05-22 17:00:56', 'signedOut'),
(40, 34, '2024-05-22 14:40:20', '2024-05-22 16:51:08', 'signedOut'),
(41, 35, '2024-05-22 14:58:27', '2024-05-22 17:01:54', 'signedOut'),
(42, 34, '2024-05-22 15:28:13', '2024-05-22 16:51:08', 'signedOut'),
(43, 16, '2024-05-22 15:43:20', '2024-05-22 17:05:24', 'signedOut'),
(44, 37, '2024-05-22 15:48:35', '2024-05-22 17:07:32', 'signedOut'),
(45, 9, '2024-05-22 15:49:08', '2024-05-22 17:01:28', 'signedOut'),
(46, 10, '2024-05-22 15:49:41', '2024-05-22 17:01:34', 'signedOut'),
(47, 8, '2024-05-22 15:56:13', '2024-05-22 16:59:35', 'signedOut'),
(48, 11, '2024-05-22 15:56:36', '2024-05-22 17:01:36', 'signedOut'),
(49, 38, '2024-05-22 15:58:31', '2024-05-22 16:59:52', 'signedOut'),
(50, 23, '2024-05-23 12:13:04', '2024-05-23 16:57:36', 'signedOut'),
(51, 1, '2024-05-23 12:13:30', '2024-05-23 16:59:51', 'signedOut'),
(52, 2, '2024-05-23 12:14:19', '2024-05-23 16:56:45', 'signedOut'),
(53, 43, '2024-05-23 14:20:12', '2024-05-23 16:35:43', 'signedOut'),
(54, 44, '2024-05-23 14:24:25', '2024-05-23 16:35:40', 'signedOut'),
(55, 30, '2024-05-23 15:05:35', '2024-05-23 16:46:57', 'signedOut'),
(56, 15, '2024-05-23 15:17:37', '2024-05-23 16:47:37', 'signedOut'),
(57, 34, '2024-05-23 15:18:17', '2024-05-23 16:46:52', 'signedOut'),
(58, 27, '2024-05-23 15:54:40', '2024-05-23 16:13:30', 'signedOut'),
(59, 6, '2024-05-23 15:55:54', '2024-05-23 16:48:32', 'signedOut'),
(60, 6, '2024-05-23 15:56:05', '2024-05-23 16:48:32', 'signedOut'),
(61, 38, '2024-05-23 15:57:33', '2024-05-23 16:46:30', 'signedOut'),
(62, 35, '2024-05-23 15:59:13', '2024-05-23 16:46:40', 'signedOut'),
(63, 11, '2024-05-23 15:59:40', '2024-05-23 16:45:57', 'signedOut'),
(64, 8, '2024-05-23 15:59:57', '2024-05-23 16:48:20', 'signedOut'),
(65, 4, '2024-05-23 16:08:12', '2024-05-23 16:47:43', 'signedOut'),
(66, 5, '2024-05-23 16:09:02', '2024-05-23 16:47:39', 'signedOut'),
(67, 22, '2024-05-24 09:14:24', '2024-05-24 17:38:28', 'signedOut'),
(68, 1, '2024-05-24 09:16:50', '2024-05-24 14:50:52', 'signedOut'),
(69, 8, '2024-05-24 14:41:57', '2024-05-24 16:46:14', 'signedOut'),
(70, 10, '2024-05-24 14:42:19', '2024-05-24 16:44:37', 'signedOut'),
(71, 10, '2024-05-24 14:42:42', '2024-05-24 16:44:37', 'signedOut'),
(72, 10, '2024-05-24 14:43:07', '2024-05-24 16:44:37', 'signedOut'),
(73, 8, '2024-05-24 14:43:29', '2024-05-24 16:46:14', 'signedOut'),
(74, 19, '2024-05-24 14:45:43', '2024-05-24 14:50:55', 'signedOut'),
(75, 9, '2024-05-24 14:46:14', '2024-05-24 14:50:54', 'signedOut'),
(76, 38, '2024-05-24 14:46:47', '2024-05-24 16:50:26', 'signedOut'),
(77, 11, '2024-05-24 14:47:29', '2024-05-24 16:46:00', 'signedOut'),
(78, 11, '2024-05-24 14:47:57', '2024-05-24 16:46:00', 'signedOut'),
(79, 11, '2024-05-24 14:48:22', '2024-05-24 16:46:00', 'signedOut'),
(80, 29, '2024-05-24 14:50:40', '2024-05-24 16:50:11', 'signedOut'),
(81, 46, '2024-05-24 16:09:35', '2024-05-24 16:49:38', 'signedOut'),
(82, 47, '2024-05-24 16:12:09', '2024-05-24 16:50:29', 'signedOut'),
(83, 34, '2024-05-24 16:12:49', '2024-05-24 16:54:38', 'signedOut'),
(84, 48, '2024-05-24 16:24:52', '2024-05-24 16:49:35', 'signedOut'),
(85, 49, '2024-05-24 16:34:27', '2024-05-24 17:39:04', 'signedOut'),
(86, 50, '2024-05-24 16:36:27', '2024-05-24 16:49:33', 'signedOut'),
(87, 50, '2024-05-24 16:36:39', '2024-05-24 16:49:33', 'signedOut'),
(88, 35, '2024-05-24 16:37:12', '2024-05-24 16:46:08', 'signedOut'),
(89, 35, '2024-05-24 16:38:32', '2024-05-24 16:46:08', 'signedOut'),
(90, 35, '2024-05-24 16:38:42', '2024-05-24 16:46:08', 'signedOut'),
(91, 1, '2024-05-25 10:14:23', '2024-05-25 16:45:12', 'signedOut'),
(92, 19, '2024-05-25 12:30:35', '2024-05-25 16:45:08', 'signedOut'),
(93, 11, '2024-05-25 12:43:43', '2024-05-25 16:24:02', 'signedOut'),
(94, 51, '2024-05-25 12:47:14', '2024-05-25 16:47:10', 'signedOut'),
(95, 52, '2024-05-25 12:48:54', '2024-05-25 16:47:11', 'signedOut'),
(96, 34, '2024-05-25 12:49:22', '2024-05-25 16:47:09', 'signedOut'),
(97, 53, '2024-05-25 12:50:26', '2024-05-25 16:46:05', 'signedOut'),
(98, 54, '2024-05-25 12:52:24', '2024-05-25 16:27:59', 'signedOut'),
(99, 55, '2024-05-25 12:54:46', '2024-05-25 16:22:27', 'signedOut'),
(100, 56, '2024-05-25 12:55:53', '2024-05-25 16:22:35', 'signedOut'),
(101, 56, '2024-05-25 12:56:06', '2024-05-25 16:22:35', 'signedOut'),
(102, 56, '2024-05-25 12:56:17', '2024-05-25 16:22:35', 'signedOut'),
(103, 24, '2024-05-25 12:57:23', '2024-05-25 16:47:09', 'signedOut'),
(104, 57, '2024-05-25 12:59:01', '2024-05-25 16:30:27', 'signedOut'),
(105, 8, '2024-05-25 13:06:18', '2024-05-25 16:24:08', 'signedOut'),
(106, 60, '2024-05-25 13:07:38', '2024-05-25 16:47:11', 'signedOut'),
(107, 61, '2024-05-25 13:09:50', '2024-05-25 16:47:11', 'signedOut'),
(108, 35, '2024-05-25 13:11:49', '2024-05-25 16:47:09', 'signedOut'),
(109, 38, '2024-05-25 13:12:16', '2024-05-25 16:47:09', 'signedOut'),
(110, 62, '2024-05-25 13:14:35', '2024-05-25 16:47:11', 'signedOut'),
(111, 63, '2024-05-25 13:18:07', '2024-05-25 16:26:09', 'signedOut'),
(112, 64, '2024-05-25 13:31:02', '2024-05-25 16:47:12', 'signedOut'),
(113, 65, '2024-05-25 13:41:08', '2024-05-25 16:47:12', 'signedOut'),
(114, 65, '2024-05-25 13:43:34', '2024-05-25 16:47:12', 'signedOut'),
(115, 66, '2024-05-25 14:17:42', '2024-05-25 16:47:12', 'signedOut'),
(116, 48, '2024-05-25 14:19:41', '2024-05-25 16:47:10', 'signedOut'),
(117, 30, '2024-05-25 15:01:05', '2024-05-25 16:37:05', 'signedOut'),
(118, 15, '2024-05-25 15:02:48', '2024-05-25 16:37:11', 'signedOut'),
(119, 6, '2024-05-25 15:03:17', '2024-05-25 16:47:08', 'signedOut'),
(120, 16, '2024-05-25 15:16:45', '2024-05-25 16:37:12', 'signedOut'),
(121, 16, '2024-05-25 15:16:57', '2024-05-25 16:37:12', 'signedOut'),
(122, 16, '2024-05-25 15:17:28', '2024-05-25 16:37:12', 'signedOut'),
(123, 16, '2024-05-25 15:18:05', '2024-05-25 16:37:12', 'signedOut'),
(124, 67, '2024-05-25 16:29:23', '2024-05-25 16:45:56', 'signedOut'),
(125, 23, '2024-05-27 09:31:56', '2024-05-27 16:57:26', 'signedOut'),
(126, 1, '2024-05-27 09:34:54', '2024-05-27 16:55:54', 'signedOut'),
(127, 68, '2024-05-27 09:43:58', '2024-05-27 16:57:30', 'signedOut'),
(128, 69, '2024-05-27 09:50:30', '2024-05-27 16:54:09', 'signedOut'),
(129, 4, '2024-05-27 09:51:31', '2024-05-27 15:38:22', 'signedOut'),
(130, 22, '2024-05-27 09:52:27', '2024-05-27 16:42:51', 'signedOut'),
(131, 5, '2024-05-27 11:41:07', '2024-05-27 15:38:24', 'signedOut'),
(132, 24, '2024-05-27 11:51:17', '2024-05-27 16:57:35', 'signedOut'),
(133, 24, '2024-05-27 11:51:29', '2024-05-27 16:57:35', 'signedOut'),
(134, 7, '2024-05-27 12:03:22', '2024-05-27 16:54:44', 'signedOut'),
(135, 7, '2024-05-27 12:03:42', '2024-05-27 16:54:44', 'signedOut'),
(136, 21, '2024-05-27 12:04:36', '2024-05-27 16:57:12', 'signedOut'),
(137, 21, '2024-05-27 12:05:06', '2024-05-27 16:57:12', 'signedOut'),
(138, 60, '2024-05-27 12:56:58', '2024-05-27 16:57:37', 'signedOut'),
(139, 19, '2024-05-27 13:07:45', '2024-05-27 16:57:27', 'signedOut'),
(140, 56, '2024-05-27 13:48:26', '2024-05-27 16:47:08', 'signedOut'),
(141, 70, '2024-05-27 13:50:20', '2024-05-27 16:01:31', 'signedOut'),
(142, 71, '2024-05-27 13:53:00', '2024-05-27 16:01:27', 'signedOut'),
(143, 71, '2024-05-27 13:53:04', '2024-05-27 16:01:27', 'signedOut'),
(144, 52, '2024-05-27 14:33:46', '2024-05-27 14:54:48', 'signedOut'),
(145, 6, '2024-05-27 14:34:52', '2024-05-27 16:58:00', 'signedOut'),
(146, 54, '2024-05-27 15:18:09', '2024-05-27 16:47:23', 'signedOut'),
(147, 34, '2024-05-27 15:25:53', '2024-05-27 16:30:28', 'signedOut'),
(148, 35, '2024-05-27 15:36:40', '2024-05-27 16:52:05', 'signedOut'),
(149, 35, '2024-05-27 15:37:36', '2024-05-27 16:52:05', 'signedOut'),
(150, 72, '2024-05-27 15:46:16', '2024-05-27 16:30:36', 'signedOut'),
(151, 38, '2024-05-27 15:47:24', '2024-05-27 16:52:06', 'signedOut'),
(152, 8, '2024-05-27 15:47:48', '2024-05-27 16:52:18', 'signedOut'),
(153, 1, '2024-05-28 11:04:23', '2024-05-28 16:52:26', 'signedOut'),
(154, 5, '2024-05-28 11:04:50', '2024-05-28 15:21:38', 'signedOut'),
(155, 52, '2024-05-28 13:17:57', '2024-05-28 13:58:42', 'signedOut'),
(156, 73, '2024-05-28 13:19:14', '2024-05-28 13:58:34', 'signedOut'),
(157, 7, '2024-05-28 13:19:46', '2024-05-28 16:51:41', 'signedOut'),
(158, 69, '2024-05-28 13:21:28', '2024-05-28 16:42:29', 'signedOut'),
(159, 24, '2024-05-28 13:44:41', '2024-05-28 14:36:51', 'signedOut'),
(160, 23, '2024-05-28 13:45:33', '2024-05-28 16:52:24', 'signedOut'),
(161, 56, '2024-05-28 13:54:45', '2024-05-28 16:52:33', 'signedOut'),
(162, 20, '2024-05-28 14:00:26', '2024-05-28 16:52:30', 'signedOut'),
(163, 74, '2024-05-28 14:08:45', '2024-05-28 16:50:28', 'signedOut'),
(164, 74, '2024-05-28 14:09:47', '2024-05-28 16:50:28', 'signedOut'),
(165, 75, '2024-05-28 14:14:16', '2024-05-28 16:50:21', 'signedOut'),
(166, 29, '2024-05-28 14:22:58', '2024-05-28 16:50:34', 'signedOut'),
(167, 6, '2024-05-28 14:58:20', '2024-05-28 15:26:03', 'signedOut'),
(168, 16, '2024-05-28 15:50:22', '2024-05-28 16:52:27', 'signedOut'),
(169, 15, '2024-05-28 16:15:52', '2024-05-28 16:50:54', 'signedOut'),
(170, 30, '2024-05-28 16:16:10', '2024-05-28 16:51:02', 'signedOut');

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `name`, `surname`, `user_type`, `date of birth`, `grade`, `school`, `parent`, `parent email`, `leaner number`, `parent number`, `Relationship`) VALUES
(1, 'vuyani_magibisela', '', '$2y$10$IE3MPGo.tWLf37S9o4m3eeXWOdFh7cx2ZMfk9ytkoCYULKBzGuJOO', 'vuyani', 'M', 'admin', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(2, 'Katlego.Mentor', '', '$2y$10$Yvo3g66RXZwdP/U.qwsLMey/q1G1lQkg1onOD/Ke45o2t69XS1OF2', 'Katlego', 'Maphosa', 'mentor', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(4, 'King', '', '$2y$10$zyo/GWr2GQ/rlpUFhxvQZuLonb80n07DVoQrtFhxGiy8GkvHjBJd6', 'Naziwe', 'Nhlapho', 'mentor', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(5, 'Dunani', '', '$2y$10$ugRFycH1KwY4eqEIxJjRbu4IjjZVoK.UVaCb4wIsH8MWrP/Rfe7dS', 'Dunani', 'Mathebula', 'mentor', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(6, 'Tetelo Aphane', '', '$2y$10$kC/B38dIUJJFWnKe3E478OYlWPQyQ8dY9y0OxJ.pXCpM9N6t7I2bi', 'Tetelo', 'Aphane', 'mentor', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(7, 'andrew klaas', '', '$2y$10$SlgEspwt5IKwVICp/Ui7k./p31TGr9WRNMjbETfANUPdP/RdpSeYG', 'andrew', 'klaas', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(8, 'JIMMY JAMES', '', '$2y$10$IEegmpQODPLiWsQrmN2kS.kzQxgyH6MJXLYqfXt1qfVrFeYbI2Aem', 'Zaine', 'Shafiq', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(9, 'sam', '', '$2y$10$sxxzEzOj6Ab0l7LdF6WiIeekeFUsE0HUngZHR1uYuzM/VXvKLWEFO', 'Bonga', 'Miya', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(10, 'malinga', '', '$2y$10$BD/wH34QHnzqJDpVwG05bu95xirTtF/Qt56JCrGny/Pz9t4CDWIN.', 'wandile', 'miya', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(11, 'kgalalelo', '', '$2y$10$EZ.R9I/9iZlEK6L7lkSf2O87vWhJDkNJgTBCYtukdaRqYIKk83qzO', 'Glory', 'Motsosi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(12, 'Yamkela', '', '$2y$10$A4ITTqQvBsL6SCUjUeKqOOZbXuA1iv5Xp08x3QFfximabHRaJsFOm', 'Yamkela', 'Zozi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(13, 'Ntamdo Goqo', '', '$2y$10$MeLZVWxH0aOXWKvBWzxVsOan7rCrkk18bA4Zyj32kqmMlENfe93Gm', 'Ntando', 'Goqo', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(14, 'Kwanele Tshabalala', '', '$2y$10$QG5mVX2b7kHIQJLWreveTOJ8w1hrmNlZdQ0e3xczsIZK1nE2xJvVy', 'Kwanele', 'Tshabalala', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(15, 'Sickomotions_', '', '$2y$10$kjf.8bljFeDb272dnU6IBu9UrnYfNdWegg8mPZSHoqowoT.PhURju', 'Orapeleng', 'Letlhatlhe', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(16, 'Enhle', '', '$2y$10$y.eHiLBNaYqgjq65lpGGFe.UrkTOPtJwuznrj/DpuEsWRCCltrpYi', 'Azola Ntwenhle', 'Ndabankulu', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(17, 'Kaizen', '', '$2y$10$7hMkwWnD5OH0cLYNPotluu3m4t3MdNuNLwgjijgn9XdUt9wzg6aFm', 'Maqhawe', 'Tshabangu', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(18, 'Banele Dhlamini', '', '$2y$10$X9S3PfXruLD6IYZkmh4fWOUHtsIgJAdGZvzsFlUiI0JbCfM0j5yyC', 'Banele', 'Dhlamini', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(19, 'djtfcradkanp', '', '$2y$10$Rp6K7l.iAGZ0g0axOOPXy.mNdnu17maLp.c3UwsxjsUlvvK5SoYxa', 'pashley', 'moyo', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(20, 'Lathi', '', '$2y$10$mPq9jpQHs/Ql6rBXfZI/2e5HSyfSeHiy.S8buBooy8FCghkvaTID6', 'Lathi', 'Ngoma', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(21, 'KiRAH', '', '$2y$10$jJW8RttHRuYVBdb4S5OOzeUOfI58mHOMBE/D9XmVGKmMOzW3B.0BK', 'Lungani Philani', 'Mbatha', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(22, 'S@310', '', '$2y$10$jPCorVUkA..Wqub1PSHwd.GonsB4yvWKDeEjtmsOpl/EKS6TtlQPW', 'Nhlanhla', 'Ndebele', 'mentor', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(23, 'Sphiri', '', '$2y$10$KxrzfT9L7t/WOkF1RcuMmOnRXiG4yQQvxoBGy0S7jgISabZd7Y.lS', 'Simphiwe', 'Phiri', 'mentor', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(24, 'Oro waAfrika', '', '$2y$10$M5r2I8k1oA3jtEVtdSoP4u733CvqiPQdji2sjwmTW9ZgB/EgG35u.', 'Sabelo', 'Njakazi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(25, 'Mvelo', '', '$2y$10$0CesxXJhGY3ws6ajkmN4cuafXiDuwfsr8wQ/shEufJcyBAsZPREOm', 'Mvelo', 'Shongwe', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(26, 'SANDZAMAN', '', '$2y$10$tpMhouHwHayL/7HDq.tk.uigUmXmdpsLZ5EKuYKuWEQeG5M5Tv3la', 'Sanele', 'Mbatha', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(27, 'msizimagwaza', '', '$2y$10$GfJMAsWowBlRYGWQgWnloecN38RUFO.ykU4e5d9C55ip.cR2blafO', 'Msizi', 'Magwaza', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(28, 'samukelomnguni485', '', '$2y$10$pVzRrzXURhNzddNza.nPWeZKR/SgtP.mHCuhH4rm0pjn9GgpwCDy.', 'Samukelo', 'Mnguni', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(29, 'Thato', '', '$2y$10$8BYo9EkJlxIc1agQVZva7erAXav0Om7ZNtf.Lua9zIm3pmkLrnVRK', 'Thato', 'Monnaruri', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(30, 'Olwethu', '', '$2y$10$MIT/HPMMpn5kEWNUfiruLOvmcAq97IM91BpHbsxFMvmhJt/a26L5e', 'Olwethu', 'Biyase', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(31, 'Zakhele', '', '$2y$10$se/AzLDEgq7mRz9Rh5dDgew5rpN1RQynFL052A6O3MdnXcwsaf9/a', 'Zakele', 'Luthuli', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(32, 'wren', '', '$2y$10$b9x.bd7FBHcnsRI6hi7Fm.rmlKVB3HW.8XVRvnv8BV2mOOaquKgHy', 'wren', 'mafuya', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(33, 'Lebo', '', '$2y$10$moReDK8r2rwN4kzhntASauQertFA26E5NEDUZyOPSllmEihL4Jb0i', 'Lebohang', 'Motaung', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(34, 'mo vlogz ', '', '$2y$10$Gio8k7weHv4Cd2RYuhlFDuWloJiYFEONBuQH/BZewbQHX7zTNEP1y', 'tshiamo ', 'sediblwane', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(35, 'max', '', '$2y$10$pJ6cnmSbFkGWXWI4FFyKpuI7Z8X8MPdHfOCS3SiwgbLBgnVEhW/KK', 'Bandile', 'Ndlovu', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(36, 'QueenMafia', '', '$2y$10$8912omim5DeDZc4UNufh1.fX3BWoqHz.7RAU5uLJtUhHVEEFO2/gq', 'Nkanyiso', 'Shongwe', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(37, 'wayne', '', '$2y$10$Pehd/6OXLrlUwe/jgrV/Vu0HxLEKcyUgW7WVXFj4srr/xxFZt.Rum', 'Thembalami', 'Mthembo', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(38, 'AB THE KING', '', '$2y$10$ZPUFIkOjm7A2weYmCDRbfeU9tjYNpmPi14t8Jjodgz68UQEPku4kK', 'Abongile', 'Ndamane', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(39, 'wesorted', '', '$2y$10$WCHx.DM8ii38QV2OT1e.feMAD.gp5EwcvchOwkAQ1Js7G8QKrlm/i', 'Beki', 'Dube', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(40, 'ulytroman', '', '$2y$10$qoSWwuq/Kc73lN2jH7Mmxuqaos2ceNTqGkNMxUcndYRsSBjT3DCxa', 'gomolemo', 'matlou', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(41, 'nimah', '', '$2y$10$ilsewscTSGJvaA3eFgF0aOKwf5sFmmENQb9og4cOONh9si5f9tfxG', 'nimah', 'adeyemi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(42, 'aphendulwe', '', '$2y$10$ypLM3PrNXA5ruu/4CGvuHOASzDmntlVC8qC86Flm1M51/pOsYLnkS', 'Aphendulwe', 'mbadu', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(43, 'Qhayiya', '', '$2y$10$sLopS/kWE3OFeu50FOBx9eFfX3DKTB6ySOqrQaAdYMrtCfCGnFF3q', ' Qhayiya', 'Mbadu', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(44, 'GhostJnr', '', '$2y$10$3ILnBf6g8oGOfmtoGxBbaOM2pSL79Y0xucGI09XffvdwREt8NQJLG', 'Siphosihle', 'Ndwandwe', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(45, 'ntumbuluko', '', '$2y$10$Gw78W0g/yfv3LIJAn1isLOcfXjTU17821CsFMqVGODTbmrmvZQ6eO', 'ntumbuluko', 'mokwena', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(46, 'bxddiee', '', '$2y$10$78dlLug.RuL7Ykh7ph893uxnJ2u1XnfOd2R8ECZ1g/ght5/X7SlJa', 'Mosa', 'Makgalo', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(47, 'Xero222', '', '$2y$10$YXaOyg.gm6hYxqCLZYdG.u5Q1vaL..yTD/u8mr1WhqBZ7ESvBOeDq', 'phathu', 'mubva', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(48, 'Lisa', '', '$2y$10$s8DyE2ngwsBPkmAZ3FooPuq7p/LkoMFf2Cg3U58FFiDApYEkKPFua', 'Princess', 'Dube', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(49, 'Letlhogonolo', '', '$2y$10$iRSQd.ICD7Z2FkJjG6GdhO.4sbeSn729cAvFtaGSOKVQR0VsddWhW', 'Letlhogonolo', 'Motlhabane', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(50, 'Lwazi', '', '$2y$10$pY9Q1lCLxQMIyOoZLhKhge77oYVoFPqT8xQt7DHYCnNWfwUUEv3gm', 'Lwazi', 'Nomame', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(51, 'Siqiniseko', '', '$2y$10$FcYgDyQBOIeL5s5O8qDo4OtO/dR9LGvb9vFO8hZNTFwRAtow4bckq', 'Siqiniseko', 'Radebe', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(52, 'casanova', '', '$2y$10$uqNKCkwWxVhNqaGwziHUv.V70MhAbJ3PxHM8btAbhYHrsdEvmbOqy', 'Bukosi', 'Maleho', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(53, 'andre', '', '$2y$10$24LlPUXiNVRW2cMsoRk5r.W6f8G1E9fK0gOK/bd1I3NRD5tJIww4i', 'Lwandle', 'Madze', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(54, 'lunikho', '', '$2y$10$G3kJNQ3O8lECkPRQOB7d6.PHwdYosxAQdqmrbmlK6/R9U3RmRw7Fe', 'Emeka', 'Gumede', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(55, 'kat', '', '$2y$10$m.uL.1gonKp5l/YITlHXGePiwyaNuMXel5Q.ig0upNqBZABnfEb7O', 'Katlego', 'Radebe', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(56, 'junior', '', '$2y$10$8aZF8uc/m9FlxdkkwwwOveQl6XlhE6gooWoiYlHMZAKT8YCa3baLe', 'Ntumbuluko', 'Mokwena', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(57, 'JOJO', '', '$2y$10$Qa.ftg5qh98uiLFcZ2of6uRLkGw6X9M4lNNlkpYK9H.w3ePL99n4i', 'LERATO', 'MLOTSHWA', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(58, 'DRIPPEEZY213', '', '$2y$10$9TFsX9ArWMLaStysdgCCfO8HcSJ2714dwrNWPCiz/AppyLF3K3vlm', 'SABELO', 'FELEM', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(59, 'SPONO', '', '$2y$10$s5mMHK8mzhMZx4t075i7x.2xB1aGHM7vSt3zygDj3J.aqv/hVEBXS', 'MASEGO', 'MOSUKWA', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(60, 'Tim Senn', '', '$2y$10$NmXNTmdKiV7nzThJh1O1sO1u9TTuoCV0Ep/nkHzRlW/m5ewkcrxgC', 'Tim', 'Senoamadi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(61, 'hearts4kutlwiii', '', '$2y$10$iRfrhPlneBBA0E/8MF2lDuCEpZS5l2vvNj0Iyk4A5qJJt2CiORMVW', 'kutlwano', 'ngubane', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(62, 'kutlwano', '', '$2y$10$TikYqtOg0pjKr.TaeJzv0OdTZhWVuWedlzEL1/NE8KkbjTE2hCR8y', 'Kutlwano', 'skhosana', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(63, 'Blitzgamer715', '', '$2y$10$i/JUKT67rCE/nJOLnJSzk.DEVc6/yF/ycgB.Q8rp6z5VILfxryM6.', 'Christian', 'Muitabayi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(64, 'dark night', '', '$2y$10$.3DByEvUfR506cKP1itrbu6OXHhJbcNBjjVhz9oJwjh47j2giJOZS', 'prince', 'beans', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(65, 'gene', '', '$2y$10$L7rUrk7A7hV49xlpgFbW.ulS30OFho.vIEyMfa221FHC9VWYBYkS6', 'bokang', 'msibi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(66, 'Kutlwano', '', '$2y$10$QtHHY/ZimYBloFUGyQBWjOEjv3rPOHTfNw/Y6pusTrdUWOEzJ05Zy', 'Tumelo', 'Nkosi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(67, 'wtfsam', '', '$2y$10$XsyKBSaHxwGf9JcueZE/0.S.K2Frzj0OdAOoO0iAUhfaqKvx7HuCK', 'Samuel', 'kazadi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(68, 'Pammy', '', '$2y$10$T6tKPHMrfasQw7v6/fACo.J95HV7iLyLc3KlZbmsRqXVcNsRSSudW', 'Pamela', 'Ngwenya', 'mentor', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(69, 'cynthia', '', '$2y$10$kvKAmvBbPfwaqTbACn4ay./wBwh0c94HyfUo4.p6yydi0CTbCd5eC', 'Cynthia', 'Siziba', 'mentor', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(70, 'ZenBot_', '', '$2y$10$I/tGaSwhoi4ulmQ.ykvd1utcsBHqI40wXXcPWyu9XFOnKAN6Zt.HG', 'Kgwedi', 'Sehume', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(71, 'LifeVitim', '', '$2y$10$Vukr73N4wUpmWZgJTRuTt.udXNt3hsabJMZx3jDtP8gTBH4pUzkdS', 'Tsebo ', 'Sehume', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(72, 'Young,Heart', '', '$2y$10$INaxHcyuILrQVengs3AiueVCZ8QfrdDxzjPT1hIk2v0OJtdgemYAq', 'Wayne', 'chikanyambidze', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(73, 'Ms.sunshine', '', '$2y$10$J.Q5yhDGCYKKoL7NyoLt5OIWjE1h2BEZAV9/iflUXJcUXcrJ6.z.y', 'Lumeka', 'Maleho', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(74, 'Mbali', '', '$2y$10$UQrKhnV4oszf6jqe6gvw8ObIXG4pj0oKEbUG5EZ/sMq6pYT4b3e9C', 'Mbalenhle', 'Mazibuko', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(75, 'Ntomb', '', '$2y$10$DUVPbiNz2XYzA0A9KOEGLe0cUPG5O6MMxGs7tU7t2UE1Ki9eAeo1K', 'Ntumbifuthi', 'Wagne', 'member', '0000-00-00', 0, '', '', 0, 0, 0, '');

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
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

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
