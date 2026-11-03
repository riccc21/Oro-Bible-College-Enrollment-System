-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2026 at 04:19 PM
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
-- Database: `obc_enrollment`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_code` varchar(6) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'student',
  `status` varchar(50) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `name`, `email`, `password`, `reset_code`, `role`, `status`) VALUES
(4, NULL, 'jerico@gmail.com', 'pass123', NULL, 'student', 'Active'),
(5, NULL, 'jerico@gmail.com', 'pass123', NULL, 'student', 'Active'),
(12, NULL, 'jpielags@gmail.com', 'pass123', NULL, 'student', 'Active'),
(13, NULL, 'jpielags@gmail.com', 'pass123', NULL, 'student', 'Active'),
(22, NULL, 'jpielags@gmail.com', 'pass123', NULL, 'student', 'Active'),
(23, NULL, 'jpielags@gmail.com', 'pass123', NULL, 'student', 'Active'),
(24, NULL, 'jpielags@gmail.com', 'pass123', NULL, 'student', 'Active'),
(25, NULL, 'jpielags@gmail.com', 'pass123', NULL, 'student', 'Active'),
(26, NULL, 'jpielags@gmail.com', 'pass123', NULL, 'student', 'Active'),
(27, NULL, 'ianjaycalingasanian@gmail.com', 'ianjay2005', NULL, 'student', 'Active'),
(28, NULL, 'ianjaycalingasanian@gmail.com', 'ianjay2005', NULL, 'student', 'Active'),
(33, 'System Admin', 'admin@yahoo.com', '123', NULL, 'Admin', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(255) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `adviser` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `section_name`, `grade_level`, `adviser`, `created_at`) VALUES
(5, 'Faith', '9', 'Joannie mae taypin', '2026-03-04 09:17:54'),
(6, 'EINSTEIN', '10', 'Irish Muring', '2026-03-04 11:11:08');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `status` enum('active','pending','inactive') DEFAULT 'pending',
  `section_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('Registrar','Parent') DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(9, 'Ian Jay Calingasan', 'ialo.calingasan.coc@phinmaed.com', '123', 'Registrar', 'Active'),
(10, 'Steven Pimentel', 'stfa.pimentel.coc@phinmaed.com', '123', 'Registrar', 'Active'),
(11, 'Jerico Pielago', 'jesu.pielago.coc@phinmaed.com', '123', 'Registrar', 'Active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
