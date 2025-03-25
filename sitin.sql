-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2025 at 05:51 AM
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
-- Database: `sitin`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `admin_username` varchar(50) NOT NULL,
  `date_posted` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `admin_username`, `date_posted`) VALUES
(1, 'testing announcement 1', 'BOOM', 'admin', '2025-03-13 15:58:21'),
(2, 'testing 2', 'Edited', 'admin', '2025-03-13 16:02:39');

-- --------------------------------------------------------

--
-- Table structure for table `register`
--

CREATE TABLE `register` (
  `IDNO` int(20) NOT NULL,
  `LASTNAME` varchar(255) NOT NULL,
  `FIRSTNAME` varchar(255) NOT NULL,
  `MIDNAME` varchar(255) NOT NULL,
  `COURSE` varchar(255) NOT NULL,
  `YEARLEVEL` int(11) NOT NULL,
  `USERNAME` varchar(255) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `PROFILE_IMG` varchar(255) DEFAULT NULL,
  `remaining_sessions` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `register`
--

INSERT INTO `register` (`IDNO`, `LASTNAME`, `FIRSTNAME`, `MIDNAME`, `COURSE`, `YEARLEVEL`, `USERNAME`, `PASSWORD`, `PROFILE_IMG`, `remaining_sessions`) VALUES
(0, 'Admin', 'Administrator', 'Admin', 'N/A', 0, 'admin', '$2y$10$lrq8Ta0sFCMKawbd9oOQu..QzoA06czmRyFyY.uJSzlojpbVfCwyC', NULL, 30),
(20207742, 'Dimarucut', 'James Stanley', 'Jo', 'BSIT', 3, 'stanleyjo755', '$2y$10$6nopCRIbznPx5sV3YCv24./GQrWJCBWcgPh7rbF/AFJ8ykbMdQEcO', NULL, 29),
(20208892, 'Testing', 'Number', 'One ', 'BSIT', 1, 'testing', '$2y$10$oahNkN1XDn.D54zWEncG..4a1F00scXI9NpianIzrOXNQNmMcHKIi', NULL, 30),
(22651798, 'Bustillo', 'Jarom', 'M', 'BSIT', 3, 'jarom', '$2y$10$vViJB9.2Jw68OgK/WkSPoeV8i9YgNNHoagnni1LHNjwD4Kw44GOXi', NULL, 29),
(202025837, 'Solon', 'Jhon Richmon', 'Alforque', 'BSIT', 3, 'rtsmn', '$2y$10$tXSBcbaBodbmyxh0uXxFAezHnHhko7Kr//BL1KA1uBs5XjhJL4TKK', NULL, 29);

-- --------------------------------------------------------

--
-- Table structure for table `sit_in_records`
--

CREATE TABLE `sit_in_records` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `status` enum('present','absent') NOT NULL DEFAULT 'absent',
  `date` date NOT NULL,
  `purpose` varchar(100) DEFAULT NULL,
  `lab` varchar(10) DEFAULT NULL,
  `time_in` time DEFAULT curtime(),
  `time_out` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sit_in_records`
--

INSERT INTO `sit_in_records` (`id`, `student_id`, `status`, `date`, `purpose`, `lab`, `time_in`, `time_out`) VALUES
(6, '20207742', 'absent', '2025-03-19', 'Programming', '524', '21:56:27', '21:58:49'),
(7, '20208892', 'absent', '2025-03-19', 'Programming', '524', '21:58:25', '21:58:36'),
(8, '20207742', 'present', '2025-03-21', 'Programming', '524', '23:18:29', '23:22:09'),
(9, '20207742', 'present', '2025-03-21', 'Programming', '524', '23:23:36', '23:23:39'),
(10, '20207742', 'absent', '2025-03-21', 'Programming', '524', '23:39:41', '23:39:49'),
(11, '202025837', 'absent', '2025-03-25', 'Programming', '524', '12:09:01', '12:09:20'),
(12, '22651798', 'absent', '2025-03-25', 'Programming', '524', '12:29:15', '12:32:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`IDNO`);

--
-- Indexes for table `sit_in_records`
--
ALTER TABLE `sit_in_records`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sit_in_records`
--
ALTER TABLE `sit_in_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
