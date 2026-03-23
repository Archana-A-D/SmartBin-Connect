-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2026 at 08:31 AM
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
-- Database: `smartbin`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$KjyB.X4fYiRi9HtBi.HememGJlToW7GBPzbEccd8iVZ8NJyLK3oQS');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT 'cannot_collect',
  `message` text NOT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int(11) NOT NULL,
  `ward_id` int(11) NOT NULL,
  `batch_number` int(11) NOT NULL,
  `pickup_date` date DEFAULT NULL,
  `status` enum('pending','scheduled','in_progress','completed') DEFAULT 'pending',
  `total_requests` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collectors`
--

CREATE TABLE `collectors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `ward_assigned` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collectors`
--

INSERT INTO `collectors` (`id`, `name`, `email`, `password`, `ward_assigned`, `phone`) VALUES
(1, 'Collector 1', 'collector1@gmail.com', '$2y$10$7k3XPQmak04TioIoZsQc3Ov0Pvrbvsh6C07zSX/LIkWRBXcdmUXWC', 1, '9876543210'),
(2, 'Collector 2', 'collector2@gmail.com', '1234', 2, '9876543211'),
(3, 'Collector 3', 'collector3@gmail.com', '1234', 3, '9876543212'),
(4, 'Collector 4', 'collector4@gmail.com', '1234', 4, '9876543213'),
(5, 'Collector 5', 'collector5@gmail.com', '1234', 5, '9876543214'),
(6, 'Collector 6', 'collector6@gmail.com', '1234', 6, '9876543215'),
(7, 'Collector 7', 'collector7@gmail.com', '1234', 7, '9876543216'),
(8, 'Collector 8', 'collector8@gmail.com', '1234', 8, '9876543217'),
(9, 'Collector 9', 'collector9@gmail.com', '1234', 9, '9876543218'),
(10, 'Collector 10', 'collecto10@gmail.com', '1234', 10, '9876543219');

-- --------------------------------------------------------

--
-- Table structure for table `collector_notifications`
--

CREATE TABLE `collector_notifications` (
  `id` int(11) NOT NULL,
  `collector_id` int(11) NOT NULL,
  `ward_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT 'batch_ready',
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collector_notifications`
--

INSERT INTO `collector_notifications` (`id`, `collector_id`, `ward_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 1, 'batch_ready', 'New batch #1 created for Ward 1 with 5 pickup requests. Set a pickup date to notify users.', 0, '2026-03-16 20:46:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 10, 'info', 'Your waste pickup (Ward 1, Batch #1) has been scheduled for Saturday, 21 Mar 2026. Please have your waste ready by 8:00 AM.', 0, '2026-03-16 20:52:09'),
(2, 11, 'info', 'Your waste pickup (Ward 1, Batch #1) has been scheduled for Saturday, 21 Mar 2026. Please have your waste ready by 8:00 AM.', 0, '2026-03-16 20:52:09'),
(3, 12, 'info', 'Your waste pickup (Ward 1, Batch #1) has been scheduled for Saturday, 21 Mar 2026. Please have your waste ready by 8:00 AM.', 0, '2026-03-16 20:52:09'),
(4, 13, 'info', 'Your waste pickup (Ward 1, Batch #1) has been scheduled for Saturday, 21 Mar 2026. Please have your waste ready by 8:00 AM.', 0, '2026-03-16 20:52:09'),
(5, 14, 'info', 'Your waste pickup (Ward 1, Batch #1) has been scheduled for Saturday, 21 Mar 2026. Please have your waste ready by 8:00 AM.', 0, '2026-03-16 20:52:09');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `latitude` float DEFAULT NULL,
  `longitude` float DEFAULT NULL,
  `status` enum('requested','accepted','completed','rescheduled') DEFAULT 'requested',
  `request_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `waste_type` varchar(100) DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `unavailable` tinyint(1) NOT NULL DEFAULT 0,
  `rescheduled_date` date DEFAULT NULL,
  `route_order` int(11) DEFAULT NULL,
  `alert_sent` int(11) DEFAULT 0,
  `batch_id` int(11) DEFAULT NULL,
  `batch_status` enum('queued','active','completed') DEFAULT 'queued'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `user_id`, `ward_id`, `latitude`, `longitude`, `status`, `request_time`, `phone`, `address`, `waste_type`, `pickup_date`, `unavailable`, `rescheduled_date`, `route_order`, `alert_sent`, `batch_id`, `batch_status`) VALUES
(20, 10, 1, 10.3951, 76.348, 'accepted', '2026-03-15 09:57:42', '+917034072601', 'Areekkal house nooluvally chembuchira po 680684', 'Plastic,Medical Waste', '2026-03-21', 0, NULL, NULL, 0, NULL, 'queued'),
(21, 11, 1, 10.3941, 76.3485, 'accepted', '2026-03-15 13:33:21', '7592847042', 'kodapully house nooluvally chembuchira po 680684', 'Plastic', '2026-03-21', 0, NULL, NULL, 0, NULL, 'queued'),
(22, 12, 1, 10.3941, 76.3485, 'accepted', '2026-03-15 13:38:29', '9446939137', 'kandanattil house nooluvally', 'Plastic,Glass', '2026-03-21', 0, NULL, NULL, 0, NULL, 'queued'),
(23, 13, 1, 10.3941, 76.3485, 'accepted', '2026-03-15 13:41:49', '9448612309', 'kodapully nooluvally chembuchira', 'Plastic,Footwear', '2026-03-21', 0, NULL, NULL, 0, NULL, 'queued'),
(24, 14, 1, 10.395, 76.3481, 'accepted', '2026-03-15 13:45:37', '9446939137', 'ummalaparambil house nooluvally chembuchira po 680684', 'Plastic,Mixed Waste', '2026-03-21', 0, NULL, NULL, 0, NULL, 'queued'),
(26, 16, 3, 10.3951, 76.348, 'requested', '2026-03-17 14:45:52', '9806431256', 'Akarakaran house nooluvally chembuchira', 'Plastic,E-Waste', NULL, 0, NULL, NULL, 0, NULL, 'queued');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `ward` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `ward`, `phone`, `address`) VALUES
(10, 'Archana', 'adarchana72@gmail.com', '$2y$10$7z5aOgSAv0i6pXf6/7lWkuE1h0jim5/mxThW84u9szmOmOhdSEpyC', 1, '+917034072601', 'Areekkal house Nooluvally Chembuchira po 680684'),
(11, 'ABHISHEK A D', 'adabhishek72@gmail.com', '$2y$10$.RA8Mtn0XmcBNH9QpN4vBOK7xIG5ny16pFw4wrvm4IyVCrGyoXXoi', 1, '7592847042', 'kodapully house,nooluvally chembuchira  po 680684'),
(12, 'Dileep Kumar A S', 'dileep72@gmaim.com', '$2y$10$qIXnRBQzjYzVws3i0j0jK.mB5/Xw1AqncjfPAvmCLuicvSGoXfJJ.', 1, '9446939137', 'kandanattil house nooluvally chembuchira po 680684'),
(13, 'shivaraman', 'shivaraman72@gmail.com', '$2y$10$hPeGx6HfFz2hrE1VyWZTNeGOMc9elURVlNhd6CXz7V3M7wFEAMtzO', 1, '9448612309', 'kodapully shivaraman nooluvally'),
(14, 'sandra', 'sandra72@gmail.com', '$2y$10$QOtrEtz659V8HwceTw5HseuAOxLr0Yumykxu5ESEq71mZJcvemr7i', 1, '9446939137', 'ummalaparambil house nooluvally chembuchira po 680684'),
(15, 'Ajith', 'ajith72@gmail.com', '$2y$10$ffgdOKboXpylpGvHYUrYduZ6M.DhW2P5VpQHkrOEl6ctZLR.JGkVi', 2, '8470912345', 'Areekkal house Nooluvally Chembuchira po 680684'),
(16, 'Sriya', 'sriya@gmail.com', '$2y$10$bJzqPmi0m2tZ0jur9K8QtOcNqiPDh1c/6rz9ZpkjA4mZ3HzotjaFe', 3, '9806431256', 'Akarakaran House Nooluvally chembuchira');

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

CREATE TABLE `wards` (
  `id` int(11) NOT NULL,
  `ward_name` varchar(50) DEFAULT NULL,
  `collector_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wards`
--

INSERT INTO `wards` (`id`, `ward_name`, `collector_id`) VALUES
(1, 'Ward 1', 1),
(2, 'Ward 2', 2),
(3, 'Ward 3', 3),
(4, 'Ward 4', 4),
(5, 'Ward 5', 5),
(6, 'Ward 6', 6),
(7, 'Ward 7', 7),
(8, 'Ward 8', 8),
(9, 'Ward 9', 9),
(10, 'Ward 10', 10);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `collectors`
--
ALTER TABLE `collectors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `collector_notifications`
--
ALTER TABLE `collector_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wards`
--
ALTER TABLE `wards`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `collectors`
--
ALTER TABLE `collectors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `collector_notifications`
--
ALTER TABLE `collector_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
