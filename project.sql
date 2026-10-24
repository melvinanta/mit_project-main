-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2026 at 03:38 AM
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
-- Database: `project`
--

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `fullname` varchar(150) DEFAULT NULL,
  `role` enum('student','adviser','subteacher') DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` text DEFAULT NULL,
  `login_status` enum('SUCCESS','FAILED') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_history`
--

INSERT INTO `login_history` (`id`, `user_id`, `fullname`, `role`, `login_time`, `ip_address`, `device_info`, `login_status`) VALUES
(1, 'adviser01', 'Test Adviser', 'adviser', '2026-10-18 14:04:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(2, 'teacher01', NULL, NULL, '2026-10-18 14:04:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(3, 'teacher01', NULL, NULL, '2026-10-18 14:05:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(4, 'teacher01', NULL, NULL, '2026-10-18 14:06:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(5, 'teacher01', NULL, NULL, '2026-10-18 14:07:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(6, 'teacher01', 'Test Teacher', 'adviser', '2026-10-18 14:10:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(7, 'teacher01', NULL, NULL, '2026-10-18 14:11:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(8, 'teacher01', NULL, NULL, '2026-10-18 14:13:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(9, 'teacher01', NULL, NULL, '2026-10-18 14:14:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(10, 'teacher01', NULL, NULL, '2026-10-18 14:14:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(11, 'teacher01', NULL, NULL, '2026-10-18 14:15:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(12, 'teacher01', 'Test Teacher', '', '2026-10-18 14:16:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(13, 'adviser01', 'Test Adviser', 'adviser', '2026-10-18 14:23:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(14, 'adviser01', NULL, NULL, '2026-10-19 01:46:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(15, 'adviser01', 'Test Adviser', 'adviser', '2026-10-19 01:46:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(16, 'teacher01', NULL, NULL, '2026-10-19 01:49:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(17, 'teacher01', 'Test Teacher', '', '2026-10-19 01:49:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(18, 'teacher01', NULL, NULL, '2026-10-19 02:10:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(19, 'adviser01', 'Test Adviser', 'adviser', '2026-10-19 02:10:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(20, 'adviser01', NULL, NULL, '2026-10-20 13:12:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(21, 'adviser01', 'Test Adviser', 'adviser', '2026-10-20 13:12:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(22, 'student1', NULL, NULL, '2026-10-22 05:50:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(23, 'student01', NULL, NULL, '2026-10-22 05:51:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(24, 'student01', NULL, NULL, '2026-10-22 06:02:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(25, 'student01', 'Test Student', 'student', '2026-10-22 06:03:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(26, 'adviser01', 'Test Adviser', 'adviser', '2026-10-22 06:18:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(27, 'student01', 'Test Student', 'student', '2026-10-22 06:31:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(28, 'adviser01', 'Test Adviser', 'adviser', '2026-10-22 06:49:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(29, 'student01', 'Test Student', 'student', '2026-10-22 09:57:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(30, 'teacher01', 'Test Teacher', '', '2026-10-24 00:36:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(31, 'student01', 'Test Student', 'student', '2026-10-24 01:21:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(32, 'teacher01', 'Test Teacher', '', '2026-10-24 01:23:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(33, 'teacher01', 'Test Teacher', '', '2026-10-24 01:34:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(34, 'student01', 'Test Student', 'student', '2026-10-24 01:34:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(35, 'student01', 'Test Student', 'student', '2026-10-24 01:34:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(36, '2026-0003', 'John Paul Cruz', 'student', '2026-10-24 01:35:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','adviser','teacher') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `fullname`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'teacher01', 'Test Teacher', '$2y$10$ikLtJkUWRAKkDSG3oVCKDuh3Q2j.VS2Ho.UGD0IXwY3raVHEEIrJW', 'teacher', 'active', '2026-10-18 14:03:47'),
(2, 'adviser01', 'Test Adviser', '$2y$10$Gs1X97uIVyWQAVOk2OQUU.Q.2ipwUNu15YC2L/gggAbot5ln0Ejfe', 'adviser', 'active', '2026-10-18 14:03:55'),
(3, 'student01', 'Test Student', '$2y$10$9JIlSyiAoc9VgMXz0spkOeqWDM8bK9MjX5LYRaG2PklGi6HlOpQZW', 'student', 'active', '2026-10-22 05:46:09'),
(5, 'student02', 'Maria Santos', '$2y$10$t1LCbI0XxGRfveOBLQQpke1L6jb1y8npkkCtmseoYSFaURcNh1ZxS', 'student', 'active', '2026-10-24 00:38:47'),
(6, 'student03', 'Juan Dela Cruz', '$2y$10$t1LCbI0XxGRfveOBLQQpke1L6jb1y8npkkCtmseoYSFaURcNh1ZxS', 'student', 'active', '2026-10-24 00:38:47'),
(7, 'student04', 'Jana Reyes', '$2y$10$t1LCbI0XxGRfveOBLQQpke1L6jb1y8npkkCtmseoYSFaURcNh1ZxS', 'student', 'active', '2026-10-24 00:38:47'),
(8, 'student05', 'Pedro Garcia', '$2y$10$t1LCbI0XxGRfveOBLQQpke1L6jb1y8npkkCtmseoYSFaURcNh1ZxS', 'student', 'active', '2026-10-24 00:38:47'),
(9, '2026-0023', 'John Doe', '$2y$10$zJ9CCZC8y0W8SnnJu4dntu81sJbYqOXtDI6FmSYqK4ZA3/o4GfxGi', 'student', 'active', '2026-10-24 00:47:24'),
(10, '2026-0003', 'John Paul Cruz', '$2y$10$D107PArGBl8oRosYw5yyM.LwVVnyRiqXwKQuKYoBWPhcH14l2wWi.', 'student', 'active', '2026-10-24 01:24:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
