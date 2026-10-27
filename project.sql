-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2026 at 02:36 PM
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
(36, '2026-0003', 'John Paul Cruz', 'student', '2026-10-24 01:35:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(37, '2026-0003', 'John Paul Cruz', 'student', '2026-10-24 01:52:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(38, 'adviser01', NULL, NULL, '2026-10-24 01:52:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(39, 'teacher01', 'Test Teacher', '', '2026-10-24 01:52:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(40, 'adviser01', 'Test Adviser', 'adviser', '2026-10-24 13:49:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(41, 'adviser01', 'Test Adviser', 'adviser', '2026-10-24 13:51:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(42, 'student01', 'Test Student', 'student', '2026-10-24 13:51:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(43, 'adviser01', 'Test Adviser', 'adviser', '2026-10-24 13:56:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(44, 'adviser01', 'Test Adviser', 'adviser', '2026-10-24 14:05:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(45, 'adviser01', 'Test Adviser', 'adviser', '2026-10-24 14:05:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(46, 'adviser01', 'Test Adviser', 'adviser', '2026-10-24 14:08:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(47, 'adviser01', 'Test Adviser', 'adviser', '2026-10-24 14:10:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(48, 'teacher01', 'Test Teacher', '', '2026-10-24 14:15:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(49, 'adviser01', 'Test Adviser', 'adviser', '2026-10-24 14:15:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(50, 'teacher01', 'Test Teacher', '', '2026-10-24 14:23:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(51, 'adviser01', 'Test Adviser', 'adviser', '2026-10-24 14:25:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(52, 'teacher01', 'Test Teacher', '', '2026-10-24 14:29:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(53, 'adviser01', 'Test Adviser', 'adviser', '2026-10-24 14:31:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(54, 'teacher01', 'Test Teacher', '', '2026-10-24 14:32:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(55, 'teacher01', 'Test Teacher', '', '2026-10-25 11:18:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(56, '2026-005', 'John Paul', 'student', '2026-10-25 12:25:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(57, 'teacher01', 'Test Teacher', '', '2026-10-25 12:26:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(58, '20206-0005', 'ALCORAN  ALTHEA', 'student', '2026-10-25 12:50:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(59, 'teacher01', 'Test Teacher', '', '2026-10-25 12:51:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(60, '20206-0005', 'ALCORAN  ALTHEA', 'student', '2026-10-25 12:52:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(61, 'teacher01', 'Test Teacher', '', '2026-10-25 12:53:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(62, 'adviser01', 'Test Adviser', 'adviser', '2026-10-25 13:05:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(63, 'adviser01', NULL, NULL, '2026-10-25 13:07:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(64, 'adviser01', NULL, NULL, '2026-10-25 13:07:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(65, 'adviser01', NULL, NULL, '2026-10-25 13:07:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(66, 'adviser01', NULL, NULL, '2026-10-25 13:07:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(67, 'adviser01', NULL, NULL, '2026-10-25 13:08:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(68, 'admin01', NULL, NULL, '2026-10-25 13:11:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(69, 'admin01', NULL, NULL, '2026-10-25 13:14:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'FAILED'),
(70, 'admin01', 'System Administrator', '', '2026-10-25 13:15:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(71, 'admin01', 'System Administrator', '', '2026-10-25 13:27:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(72, 'teacher02', 'Mike Ross', '', '2026-10-25 13:29:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(73, 'teacher02', 'Mike Ross', '', '2026-10-25 13:31:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(74, 'teacher02', 'Mike Ross', '', '2026-10-25 13:34:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(75, '20206-0005', 'ALCORAN  ALTHEA', 'student', '2026-10-25 13:34:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS'),
(76, 'admin01', 'System Administrator', '', '2026-10-25 13:34:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'SUCCESS');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin','teacher') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `fullname`, `password`, `role`, `status`, `created_at`, `email`) VALUES
(1, 'teacher01', 'Test Teacher', '$2y$10$ikLtJkUWRAKkDSG3oVCKDuh3Q2j.VS2Ho.UGD0IXwY3raVHEEIrJW', 'teacher', 'active', '2026-10-18 14:03:47', NULL),
(2, 'adviser01', 'Test Adviser', '$2y$10$Gs1X97uIVyWQAVOk2OQUU.Q.2ipwUNu15YC2L/gggAbot5ln0Ejfe', 'admin', 'active', '2026-10-18 14:03:55', NULL),
(3, 'student01', 'Test Student', '$2y$10$9JIlSyiAoc9VgMXz0spkOeqWDM8bK9MjX5LYRaG2PklGi6HlOpQZW', 'student', 'active', '2026-10-22 05:46:09', NULL),
(5, 'student02', 'Maria Santos', '$2y$10$t1LCbI0XxGRfveOBLQQpke1L6jb1y8npkkCtmseoYSFaURcNh1ZxS', 'student', 'active', '2026-10-24 00:38:47', NULL),
(6, 'student03', 'Juan Dela Cruz', '$2y$10$t1LCbI0XxGRfveOBLQQpke1L6jb1y8npkkCtmseoYSFaURcNh1ZxS', 'student', 'active', '2026-10-24 00:38:47', NULL),
(7, 'student04', 'Jana Reyes', '$2y$10$t1LCbI0XxGRfveOBLQQpke1L6jb1y8npkkCtmseoYSFaURcNh1ZxS', 'student', 'active', '2026-10-24 00:38:47', NULL),
(8, 'student05', 'Pedro Garcia', '$2y$10$t1LCbI0XxGRfveOBLQQpke1L6jb1y8npkkCtmseoYSFaURcNh1ZxS', 'student', 'active', '2026-10-24 00:38:47', NULL),
(9, '2026-0023', 'John Doe', '$2y$10$zJ9CCZC8y0W8SnnJu4dntu81sJbYqOXtDI6FmSYqK4ZA3/o4GfxGi', 'student', 'active', '2026-10-24 00:47:24', NULL),
(10, '2026-0003', 'John Paul Cruz', '$2y$10$D107PArGBl8oRosYw5yyM.LwVVnyRiqXwKQuKYoBWPhcH14l2wWi.', 'student', 'active', '2026-10-24 01:24:39', NULL),
(11, '2026-15245', 'John Doe', '$2y$10$5UmhknfuU92F7M06ykHvg.lWJjhcpaai7.9Phx2HU5A8weiXCc6Jq', 'student', 'active', '2026-10-25 12:02:52', NULL),
(12, '2026-005', 'John Paul', '$2y$10$7k4K.Xx.lestGj91Pv9FYuZvjktSLi/QLCWqwrKimcZfSirC5WLSy', 'student', 'active', '2026-10-25 12:08:04', NULL),
(13, '2026-0021', 'Jake Paul', '$2y$10$ng.VwJO.SuE45WY/HAXOoOHdYtLqQSqNG9yuziPUV1p0xETHwrF1G', 'student', 'active', '2026-10-25 12:12:54', NULL),
(14, '2026-009', 'Amir Gaul', '$2y$10$dFhgJ8ubsx49GCx8horjReuccniiwelpDRp7KQIDKeGcOhZmqQY.u', 'student', 'active', '2026-10-25 12:13:42', NULL),
(15, '2026-2325', 'Don Krieg', '$2y$10$Rgz9ZWobWk/.v5PjeyNS/.kJuK9XlWsoCYuOjPpiyQGwv/fOqae3S', 'student', 'active', '2026-10-25 12:14:14', NULL),
(16, '20206-0005', 'ALCORAN  ALTHEA', '$2y$10$2xyaXHd5YnFjF5D9NCf.HeCBBkXtjHmDCI7MoQzheuhTEpY3MGfya', 'student', 'active', '2026-10-25 12:38:18', NULL),
(17, '20206-0006', 'ANDRIN  MYK AARON CYL', '$2y$10$AfV50fG70bSRE2wrbR6.7uNDWZx1YREqi3HlmgKev8svCU45YplR.', 'student', 'active', '2026-10-25 12:38:18', NULL),
(18, '20206-0007', 'BACALING  ANGEL MAY', '$2y$10$nC9Wfbzgd0s/8go8nYk51uLgUQUItJnLDdfX5keoDtFm4kfAWV7TW', 'student', 'active', '2026-10-25 12:38:18', NULL),
(19, '20206-0008', 'BADE  PRINCESS JAYANN', '$2y$10$qtTY268DybtxeyvPPO1tku9CsZggWR.Eh5ygB6m/IaIhN4pulGfKy', 'student', 'active', '2026-10-25 12:38:19', NULL),
(20, '20206-0009', 'BAYNOSA  ROY', '$2y$10$fwofROoUrSkYK59m6mah6eQwllp3f4ogojDzC6.io/gT6aZyjswS.', 'student', 'active', '2026-10-25 12:38:19', NULL),
(21, '20206-0010', 'CACHUELA  MEHCA ELLA', '$2y$10$CsmLsgBzlJ/ZUt/Xrme7rOp0W2WtyxBU3/BxsH1Og2QuTJBYfWymy', 'student', 'active', '2026-10-25 12:38:19', NULL),
(22, '20206-0011', 'CASTILLO  SHARLENE KATE', '$2y$10$kB3DxazdAa1EIVOt8OTL5u99IjSuGk6WnJSwR/IzDznTNmJhJW3gO', 'student', 'active', '2026-10-25 12:38:19', NULL),
(23, '20206-0012', 'CAÑON  JESARIE', '$2y$10$8uR.ByYuWifGBt0WU6f0CulFuX5pUK8xTrv/WtTusWQkkSr5ZhuAq', 'student', 'active', '2026-10-25 12:38:19', NULL),
(24, '20206-0013', 'CLAVERIA  ELLISON', '$2y$10$R0im41wXlyLFg9qrWbNEhuGEl7p5/KWMSMCumO.3CK7vmnx5OEIDe', 'student', 'active', '2026-10-25 12:38:19', NULL),
(25, '20206-0014', 'COLANO  JESABEL', '$2y$10$iLifm0cJOviFgjWCTRARWeWmRSueOX86N0xKStQpP0NMIX00saUsy', 'student', 'active', '2026-10-25 12:38:19', NULL),
(26, '20206-0015', 'DALEN', '$2y$10$f.djJyKEILKyvw3o8uh0x.nCRSmQCa1zffDsWefi/4vFWK2aClLNW', 'student', 'active', '2026-10-25 12:38:19', NULL),
(27, '20206-0016', 'DE JESUS', '$2y$10$g3/2rT.80ep0XT4NMzJlgOs93wGn.2ZyRToKnPjwMX9ASSRDVohZu', 'student', 'active', '2026-10-25 12:38:20', NULL),
(28, '20206-0017', 'DECOLONGON', '$2y$10$UNVZVzdeSdSTYOHA1nVRK.pnqToajx19jV9RO0zeEKmmzD/sSe.KW', 'student', 'active', '2026-10-25 12:38:20', NULL),
(29, 'admin01', 'System Administrator', '$2y$10$ikzg.DVaIDtnyxpzTeqsy..ph72/On4oiwhFZ/7cvI4hNl.Q5/5Pm', 'admin', 'active', '2026-10-25 13:11:00', NULL),
(30, 'teacher02', 'Mike Ross', '$2y$10$zMVi8oXa4fbFjs4s8wEs1eEiUAvw3dyxKrPrkJ8svC/ag7anklCRq', 'teacher', 'active', '2026-10-25 13:28:49', NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
