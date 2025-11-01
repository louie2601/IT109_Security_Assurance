-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2025 at 12:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sucurity`
--

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) DEFAULT 0,
  `attempt_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempt_time`) VALUES
(1, 'joylyn123', '::1', 1, '2025-10-19 22:13:56'),
(2, 'joylyn123', '::1', 0, '2025-10-19 22:33:29'),
(3, 'root', '::1', 0, '2025-10-19 22:40:44'),
(4, 'root', '::1', 0, '2025-10-19 22:40:50'),
(5, 'Juan123', '::1', 1, '2025-10-23 07:36:57'),
(6, 'admin', '::1', 0, '2025-10-23 07:53:39'),
(7, 'Juan@gmail.com', '::1', 0, '2025-10-23 07:59:12'),
(8, 'Juan@gmail.com', '::1', 1, '2025-10-23 07:59:44'),
(9, 'root', '::1', 0, '2025-10-23 07:59:59'),
(10, 'root', '::1', 0, '2025-10-23 08:24:29'),
(11, 'Juan@gmail.com', '::1', 0, '2025-10-23 08:24:42'),
(12, 'VJS035028581', '::1', 0, '2025-10-28 20:11:27'),
(13, 'VJS035028581', '::1', 0, '2025-10-28 20:11:30'),
(14, 'VJS035028581', '::1', 0, '2025-10-28 20:11:33'),
(15, 'VJS035028581', '::1', 0, '2025-10-28 20:11:49'),
(16, 'VJS035028581', '::1', 0, '2025-10-28 20:12:04'),
(17, 'VJS035028581', '::1', 0, '2025-10-28 20:12:19'),
(18, 'VJS035028581', '::1', 0, '2025-10-28 20:12:49'),
(19, 'VJS035028581', '::1', 0, '2025-10-28 20:12:54'),
(20, 'kate123', '::1', 0, '2025-10-28 20:16:51'),
(21, 'root', '::1', 0, '2025-10-29 13:57:10'),
(22, 'root', '::1', 0, '2025-10-29 13:57:14'),
(23, 'root', '::1', 0, '2025-10-29 13:57:37'),
(24, 'root', '::1', 0, '2025-10-29 13:57:52'),
(25, 'root', '::1', 0, '2025-10-29 13:58:07'),
(26, 'louie123', '::1', 0, '2025-10-29 14:30:03'),
(27, 'joylyn123', '::1', 1, '2025-10-29 14:31:56'),
(28, 'root', '::1', 0, '2025-10-29 14:32:45'),
(29, 'joylyn123', '::1', 1, '2025-10-29 14:44:05'),
(30, 'joylyn123', '::1', 0, '2025-10-29 15:10:12'),
(31, 'joylyn123', '::1', 0, '2025-10-29 15:10:17'),
(32, 'joylyn123', '::1', 1, '2025-10-29 15:10:23'),
(33, 'joylyn123', '::1', 0, '2025-10-29 15:10:34'),
(34, 'joylyn123', '::1', 0, '2025-10-29 15:10:49'),
(35, 'joylyn123', '::1', 0, '2025-10-29 15:11:19'),
(36, 'joylyn123', '::1', 0, '2025-10-29 15:11:50'),
(37, 'joylyn123', '::1', 0, '2025-10-29 15:12:21'),
(38, 'joylyn123', '::1', 0, '2025-10-29 15:13:53'),
(39, 'joylyn123', '::1', 0, '2025-10-29 15:15:32'),
(40, 'joylyn123', '::1', 0, '2025-10-29 15:15:46'),
(41, 'joylyn123', '::1', 0, '2025-10-29 15:16:14'),
(42, 'joylyn123', '::1', 0, '2025-10-29 15:16:43'),
(43, 'joylyn123', '::1', 0, '2025-10-29 15:16:53'),
(44, 'root', '::1', 0, '2025-10-29 15:17:00'),
(45, 'joylyn123', '::1', 1, '2025-10-29 15:18:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `suffix` varchar(10) DEFAULT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `purok_street` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `municipal_city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `security1` varchar(255) DEFAULT NULL,
  `answer1` varchar(255) DEFAULT NULL,
  `security2` varchar(255) DEFAULT NULL,
  `answer2` varchar(255) DEFAULT NULL,
  `security3` varchar(255) DEFAULT NULL,
  `answer3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `is_active`, `suffix`, `extension`, `birthdate`, `age`, `sex`, `purok_street`, `barangay`, `municipal_city`, `province`, `country`, `zipcode`, `email`, `username`, `password`, `role`, `security1`, `answer1`, `security2`, `answer2`, `security3`, `answer3`, `created_at`, `updated_at`) VALUES
(1, 'Louie', 'Lasit', 'Abadines', 1, NULL, '', '2001-02-26', 24, 'male', '', '', '', '', '', '', '', '', '$2y$10$XXHtpFoSnmJMxR0IOYvMWel4LN1eLx92DsGlr8Bo6gZuQJR.u2eIa', 'student', '', '', '', '', '', '', '2025-10-13 13:44:53', '2025-10-19 22:16:52'),
(2, 'Joylyn', 'Eslier', 'Balondo', 1, NULL, '', '2001-07-15', 24, '0', 'Purok-2', 'Songkoy', 'Kitcharao', 'Agusan Del Norte', 'Philippines', '8400', 'Joylynbalondo@gmail.com', 'joylyn123', '$2y$10$rvkMOvxx3/3M70GUzLoV4u6Vh4R7Sj4avwK.FGXOzedSUhuh7oYaK', 'student', 'Who is your best friend in Elementary?', '$2y$10$SkN98zXJDz9n3jp1WEWPDuCk/qjDCNjHOJG87bTjbJYexGnsJOZLa', 'What is the name of your favorite pet?', '$2y$10$wH3y10XfX4i3mfewzbOFD.pWd4j6O0b/72MjBOSGoPWJqfWingltu', 'Who is your favorite teacher in high school?', '$2y$10$3mMRgkt7u2V7C7wiyrXJ4e4loRwrXZJ9ZARCHbSNtH7DA5d9ZARHO', '2025-10-14 04:13:53', '2025-10-19 22:16:52'),
(3, 'Juan', '', 'Cruz', 1, NULL, '', '1980-11-21', 44, '0', 'Purok 8 Alipao, Alegria, Surigao Del Norte', 'A', 'Surigao City', 'Surigao Del Norte', 'Philippines', '8400', 'Juan@gmail.com', 'Juan123', '$2y$10$f68MeQm8pALxMXchdTJGLuRHwjejQ2P8kkzVE/f0nQwwMxhXQMJH.', 'student', 'Who is your best friend in Elementary?', '$2y$10$aiZ40f6DKh0HpV8ATnsW3OKidQd6PcH.4IxLBLhDE3XK0ahQ0IImu', 'What is the name of your favorite pet?', '$2y$10$/mUcETUU0ShSAN5o45JrE.Cb74mcPIYU9u7s0JKO/U2ElaiPdjj2m', 'Who is your favorite teacher in high school?', '$2y$10$pbp6N0ofPaxCuDFj7ZaVv..uAvNVz.WkZdg/nnLFOl/vt26ciW61W', '2025-10-22 23:36:26', '2025-10-23 07:36:26');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `login_time` datetime DEFAULT current_timestamp(),
  `logout_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `is_active`, `login_time`, `logout_time`) VALUES
(1, 3, 'j264o46u774vf6v4apiooj70p3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 0, '2025-10-23 07:36:57', NULL),
(2, 3, 'ria6huveb573bm2tr9utstoll2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 0, '2025-10-23 07:59:44', NULL),
(3, 2, 'tqru5mr7lrsulan2pabnr0k7k4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 0, '2025-10-29 14:31:56', NULL),
(4, 2, 'ohh3t3ds5gvcjq86adq9je9f5o', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 0, '2025-10-29 14:44:05', NULL),
(5, 2, '00qrs20up2eo71pqa1ed69l6g1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 0, '2025-10-29 15:10:23', NULL),
(6, 2, 'gu39i2bfjmahsvpvjg2hnq0en0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 0, '2025-10-29 15:18:37', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
