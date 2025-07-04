-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2024 at 03:30 PM
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
  `date of birth` date DEFAULT NULL,
  `grade` int(3) DEFAULT NULL,
  `school` varchar(255) DEFAULT NULL,
  `parent` varchar(255) DEFAULT NULL,
  `parent email` int(255) DEFAULT NULL,
  `leaner number` int(10) DEFAULT NULL,
  `parent number` int(10) DEFAULT NULL,
  `Relationship` varchar(255) DEFAULT NULL
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
(12, 'Yamkela', 'y@gmail.com', '$2y$10$foLU0IwHqvjh9FJQW7cA8uO6MAL9L.KFXnSlWjn0XB3kNnkeYIg5q', 'Yamkela', 'Zozi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(13, 'Ntando Goqo', 'ntando@sci-bono.co.za', '$2y$10$VexMr7RFF31BZL5EZg8w8.i8q.0eoXUGg4mxc43kYcKamYjZaWRwS', 'Ntando', 'Goqo', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(14, 'Kwanele Tshabalala', 'kwanele@sci-bono.co.za', '$2y$10$3qc5qbaY6jemCCEUubXF4eJe5b3V/NGsYP5w.KDlN6biscsQ7kVzy', 'Kwanele', 'Tshabalala', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
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
(25, 'Mvelo', 'mvelo@gmail.com', '$2y$10$J/xpKDL2CAFXJOuo4THqAuPJnhpEu1NupS1XN.N4ofvhczE33kqke', 'Mvelo', 'Shongwe', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
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
(36, 'QueenMafia', 'nkyaniso@sci-bono.co.za', '$2y$10$UmLIJxnXWHAf.Se7mDqnGOftkMaqGqXV9IKUMWgW2764Q2jDNiUXy', 'Nkanyiso', 'Shongwe', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(37, 'wayne', '', '$2y$10$Pehd/6OXLrlUwe/jgrV/Vu0HxLEKcyUgW7WVXFj4srr/xxFZt.Rum', 'Thembalami', 'Mthembo', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(38, 'AB THE KING', '', '$2y$10$ZPUFIkOjm7A2weYmCDRbfeU9tjYNpmPi14t8Jjodgz68UQEPku4kK', 'Abongile', 'Ndamane', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(39, 'wesorted', '', '$2y$10$WCHx.DM8ii38QV2OT1e.feMAD.gp5EwcvchOwkAQ1Js7G8QKrlm/i', 'Beki', 'Dube', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(40, 'ulytroman', '', '$2y$10$qoSWwuq/Kc73lN2jH7Mmxuqaos2ceNTqGkNMxUcndYRsSBjT3DCxa', 'gomolemo', 'matlou', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(41, 'nimah', '', '$2y$10$ilsewscTSGJvaA3eFgF0aOKwf5sFmmENQb9og4cOONh9si5f9tfxG', 'nimah', 'adeyemi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(42, 'aphendulwe', 'aphendulwe@Sci-bono.co.za', '$2y$10$SD5s3ufvMl.A7OqIND1Ie.UsXGxQA8fjVhRkLRuezZGxN424tR7gG', 'Aphendulwe', 'mbadu', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(43, 'Qhayiya', '', '$2y$10$sLopS/kWE3OFeu50FOBx9eFfX3DKTB6ySOqrQaAdYMrtCfCGnFF3q', ' Qhayiya', 'Mbadu', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(44, 'GhostJnr', '', '$2y$10$3ILnBf6g8oGOfmtoGxBbaOM2pSL79Y0xucGI09XffvdwREt8NQJLG', 'Siphosihle', 'Ndwandwe', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(45, 'ntumbuluko', 'ntum@sci-bono.co.za', '$2y$10$RHx9ROoEzUuWau5RBwKjy.xkrYtTZxInKVkAx4oMeWTHiqSYKacQi', 'ntumbuluko', 'mokwena', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
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
(63, 'Blitzgamer715', 'jd@gmail.com', '$2y$10$1HGaywjBTOt42XyDINCGcO7aE3o7OZpXGKZNbNlCN9/ZUvEWK8jzu', 'Christian', 'Muitabayi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
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
(75, 'Ntomb', '', '$2y$10$DUVPbiNz2XYzA0A9KOEGLe0cUPG5O6MMxGs7tU7t2UE1Ki9eAeo1K', 'Ntumbifuthi', 'Wagne', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(76, 'Oscar', '', '$2y$10$Eb3GLsUlzvGYdYwpCqFs0ejlavNTNKtHLiPjRRukfuLLkHRFkF3G.', 'Oscar', 'Ratali', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(77, 'killt', '', '$2y$10$5z0/qhOF5Tzhk55nfI2AWOfuTWEitK8PCEgZXHMXZxU50AsqdVGua', 'Anton ', 'Magunde', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(78, 'bmw', '', '$2y$10$NJLIbfsdWQn1nCt.eG2jn.7o92kdhylT7eDqGDF9MXVHT3TxIsX5i', 'Bima', 'Mabasa', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(79, 'KWAnele', '', '$2y$10$sPM8FQPCGz5.yRIDIAo31OnR5XNavxEpWMRYpHscmnPkgsnNnEJ3a', 'Kwanele', 'Tshabalala', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(80, 'sneh', '', '$2y$10$prxiHZOTVPPiDb5ajPg28.ltT3ibPb9SPhWtUKUeXLq70FAJJPYkG', 'Snethemba', 'Nhleko', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(81, 'teko', '', '$2y$10$sQ0hSUf3ORjnMkSzmBpG9edQl1XqiGtAPsahMB.jDGxEM1aftrHPe', 'teko', 'thabana', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(82, 'lethu', '', '$2y$10$oLN.QoIPlPpiHqrrUJiMrOj.6nFphULJp7ePVvaH1gqsvepc3vzBS', 'Werter', 'Mors', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(83, 'kkb', '', '$2y$10$zvRx5e29oWlgZLT70DzWUurSKyWlzvuwHqn9X/potGBvLIY2oGc1.', 'karabo', 'Mukize', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(84, 'freestyleking', '', '$2y$10$PtaSYBuuLQHFsQmjp37lu..snrkcc15XQcVZCJAH6qehWqzf.IYAS', 'Junior Mashudu', 'Ntuli', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(85, 'DJ Appsolute', '', '$2y$10$dsYBQZTKxm/uKxI0Dv35J.QaGhrChATUnCwPLpdHE44EdzX8/LaBO', 'Aphiwe', 'Valashiya ', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(86, 'r4xx', '', '$2y$10$MMiKI3yauH8bk0XcEog3yep35o4ZsvDstmKhBVVHSGr8Jx/LDwzfC', 'Oratilwe', 'dlabongo', 'mentor', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(87, 'kamvaimange', '', '$2y$10$fHlw4kS8s0mevScbmFkifuYIrgx9KlX7LLAjrPHTR.PcF4KD59w.m', 'kamva', 'madze', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(88, 'Amo Mahlaku', '', '$2y$10$rwMuPeLNO7KLyiOX/xwwZeuJ/AwvX/abDrfEUp./UWWNRhEFiLIZW', 'Amohelang', 'Mahlaku', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(89, 'Amila Gogo', '', '$2y$10$Q6YkQnaZ8yFpAVEUiGZd2uWg/WT8jHZ6Blyl5MKweHSBBKEIna7fW', 'Amila', 'Gogo', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(90, 'CJ', 'cj@sci.co.za', '$2y$10$vHy9zwN2RNpw7wUy05MNtO.ExpLDkUhBPaOb76vTUIIgFZ1L8oYsa', 'nshuti', 'mukize', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(91, 'RELEBOHILE', '', '$2y$10$.oRZcv5CCboqLz3S64S.V.KXbSBaojfgxmKm4teLtq9JK8Z/Wfc4K', 'Relebohile', 'Semu', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(92, 'mrss_seemela', '', '$2y$10$1L/QeLEsi0OjNTSx4k4wJeu7AepdZ/S72ox00MFAAaeM3PPFw9lPS', 'alwande', 'sibanyoni', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(93, 'zeezee', '', '$2y$10$4VbFy9ITmr8cWCtOfER.ce0dtpftMQfV2maLMpFmqdtjO/mxr7w4m', 'Zizipho', 'Mokhachane', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(94, 'hlehle', '', '$2y$10$ld9TeXEq1q1/qBW2BEJlQO6Ir5dwVOugTMoTVI/j6oCiZhYOoKvV2', 'asemahle ', 'mdekazi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(95, 'XXLESH BABY', '', '$2y$10$Y0vO1dg26pkZ31RZSi5YROeDCXd9TREEmQxHi9AHQfjYi6PSksnK.', 'Liyakha', 'Ntengento', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(96, 'TLATSE-BOI', '', '$2y$10$KbH0QgxQLclAf0TRWsmr5eIxtQzQc8hgPbwrQvg7C1sYQGa/5t5C2', 'Mahlatase', 'Seemela', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(97, 'Loyiso', '', '$2y$10$d2zpxOCV3Jqj3mHuX4TUz.wbXhNpRBxfatm7GFb.DVAB8BVw0MVfm', 'Loyiso', 'ludidi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(98, 'Njunju', '', '$2y$10$mZetG2tMBBCWHZ8/.7iOFuRg7Yfli5.2mnTxXwxOu.7X/0Zcfp9m2', 'Katlego', 'Masondo', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(99, 'Gik', '', '$2y$10$7oeyzKfJvskshUj1japRAeRC2glQXk2YBBUow/aWVy5bVNfYESdKu', 'Godswill', 'Onyewueke', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(100, 'ntombi', '', '$2y$10$KrqBKTe7Y3IZFmwJQiCx3Oz43S/eLHhrOaw9cKHu.el7ODsfb4TCm', 'Ntombi', 'Khumalo', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(101, 'lebohang', '', '$2y$10$ben/9I5uY5RkG.KUZfqkN.EIy/uxX6P5WA/kHQM1hEFskY2DDiv62', 'lebohang', 'motaung', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(102, 'fav.daughter', '', '$2y$10$JdZs1/fCuIDkBRyurBkt3eTTkIpegmtH.uP/6dSt3oMA3VKC1HCKa', 'Nonkululeko', 'Shongwe', 'mentor', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(103, 'nyasha', '', '$2y$10$So4TtrowezFsclprmYcziecouZ0F/AgR3iLDY/hoN8GjkMaqwrSV.', 'nyasha', 'buthelezi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(104, 'KG', '', '$2y$10$JRxKSVkQAgGvuVWhWhGoBOt046FldCe72nANt2/A9XBqLFowiAPli', 'Kagiso', 'Mphathi', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(105, 'Ronaldo', '', '$2y$10$BBgyHG/u6KbXu4GQukxBy.MrpJkeRbk8igk/5JFt4Xt.P97v7atci', 'Albert', 'Cossa', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(106, 'Blessings', '', '$2y$10$p67O4Rwdus2m2boOmTNdW.ydP.pBdifXuNVDJ4PkiILaW3NkAme6i', 'Blessings', 'Shongwe', 'member', '0000-00-00', 0, '', '', 0, 0, 0, ''),
(107, 'hate', '', '$2y$10$f8pVAuU4nsW1MEVb3CLaJu0dd9yC7oSM/iiqlek6ngyl.Wn76UhEi', 'nondumiso', 'shongwe', 'member', '0000-00-00', 0, '', '', 0, 0, 0, '');

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
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
