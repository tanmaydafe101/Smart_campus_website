-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2026 at 07:30 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart_campus`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `default_priority` enum('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `default_priority`, `created_at`) VALUES
(1, 'Wi-Fi & Internet', 'Campus Wi-Fi connectivity, speed issues, LAN cable port faults, and portal authentication errors', 'HIGH', '2026-10-19 04:34:32'),
(2, 'Electrical & Lighting', 'Faulty ceiling fans, tube lights, loose switchboards, projector power trip, or AC malfunctioning', 'MEDIUM', '2026-10-19 04:34:32'),
(3, 'Classroom & Furniture', 'Broken desks, damaged benches, blackboard/whiteboard issues, door latches, and window panes', 'LOW', '2026-10-19 04:34:32'),
(4, 'Computer Lab & Projectors', 'PC hardware failure, blue screens, display projector flickering, VGA/HDMI cable damage', 'HIGH', '2026-10-19 04:34:32'),
(5, 'Plumbing & Cleanliness', 'Washroom hygiene, leaking faucets, clogged drains, water dispenser filter replacement, trash bins', 'CRITICAL', '2026-10-19 04:34:32'),
(6, 'Other Campus Facilities', 'Canteen seating, gym equipment, library noise, sports ground issues, and general campus infrastructure', 'LOW', '2026-10-19 04:34:32');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(10) UNSIGNED NOT NULL,
  `building` varchar(100) NOT NULL,
  `room` varchar(50) NOT NULL,
  `qr_code_token` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `building`, `room`, `qr_code_token`, `created_at`) VALUES
(1, 'Academic Block A', 'Room 101 (Lecture Hall)', 'CAMPUS-AB-101', '2026-10-19 04:34:32'),
(2, 'Academic Block A', 'Room 204 (Smart Classroom)', 'CAMPUS-AB-204', '2026-10-19 04:34:32'),
(3, 'Computer Science Block', 'Lab 2 (Systems & AI Lab)', 'CAMPUS-CS-LAB2', '2026-10-19 04:34:32'),
(4, 'Computer Science Block', 'Lab 5 (Network & IoT Lab)', 'CAMPUS-CS-LAB5', '2026-10-19 04:34:32'),
(5, 'Central Library', '2nd Floor Reading Hall', 'CAMPUS-LIB-RH2', '2026-10-19 04:34:32'),
(6, 'Main Auditorium', 'AV Control Room', 'CAMPUS-AUD-AV01', '2026-10-19 04:34:32'),
(7, 'Student Amenities Centre', 'Cafeteria Ground Floor', 'CAMPUS-SAC-CAF1', '2026-10-19 04:34:32');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket_code` varchar(30) NOT NULL,
  `reporter_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `location_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `priority` enum('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
  `status` enum('OPEN','ASSIGNED','IN_PROGRESS','RESOLVED','CLOSED') NOT NULL DEFAULT 'OPEN',
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_code`, `reporter_id`, `category_id`, `location_id`, `title`, `description`, `photo_path`, `priority`, `status`, `assigned_to`, `created_at`, `due_at`, `resolved_at`) VALUES
(1, 'TICK-2026-0001', 4, 1, 1, 'Wi-Fi dropping frequently in Room 101', 'Students are unable to access the online portal during class. The access point blinks red intermittently.', NULL, 'HIGH', 'OPEN', NULL, '2026-10-17 04:34:32', '2026-10-20 10:04:32', NULL),
(2, 'TICK-2026-0002', 4, 4, 3, 'Projector lamp flickering and dim display', 'Ceiling projector in CS Lab 2 flickers every 3 minutes. HDMI cable also seems loose.', NULL, 'HIGH', 'ASSIGNED', 2, '2026-10-18 04:34:32', '2026-10-21 10:04:32', NULL),
(3, 'TICK-2026-0003', 5, 5, 7, 'Water cooler tap continuously leaking', 'Water cooler tap at cafeteria ground floor is loose and leaking clean drinking water continuously.', NULL, 'CRITICAL', 'IN_PROGRESS', 3, '2026-10-18 16:34:32', '2026-10-19 22:04:32', NULL),
(4, 'TICK-2026-0004', 5, 2, 2, 'Fan #3 making loud screeching noise', 'Ceiling fan near the back row in Room 204 makes a loud rattling and screeching sound.', NULL, 'MEDIUM', 'RESOLVED', 2, '2026-10-16 04:34:32', '2026-10-18 10:04:32', '2026-10-19 06:04:32'),
(5, 'TICK-2026-0005', 4, 3, 5, 'Broken study desk near window #4', 'Desk surface is cracked and nail is exposed, could cause minor injury.', NULL, 'LOW', 'CLOSED', 2, '2026-10-14 04:34:32', '2026-10-16 10:04:32', '2026-10-17 10:04:32'),
(6, 'TICK-2026-BA8DBB', 6, 4, 6, 'AC not working', 'the important session is start  but AC not working', NULL, 'HIGH', 'OPEN', NULL, '2026-10-19 05:12:22', '2026-10-20 07:12:22', NULL),
(7, 'TICK-2026-C2D9BC', 7, 3, 5, 'Chair break', 'by mistake it  can break', 'uploads/tickets/ticket_20261019_092913_0815b722.jpeg', 'MEDIUM', 'RESOLVED', NULL, '2026-10-19 07:29:13', '2026-10-21 09:29:13', '2026-10-19 13:01:19'),
(8, 'TICK-2026-0662B1', 4, 1, 1, 'time pass', 'time pass', NULL, 'LOW', 'OPEN', NULL, '2026-10-24 04:47:44', '2026-10-27 06:47:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ticket_comments`
--

CREATE TABLE `ticket_comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_comments`
--

INSERT INTO `ticket_comments` (`id`, `ticket_id`, `user_id`, `message`, `created_at`) VALUES
(1, 2, 1, 'We have dispatched technician Suresh to inspect the HDMI cable and projector lamp.', '2026-10-18 12:34:32'),
(2, 2, 4, 'Thank you! Classes resume at 2 PM, inspection before that would be very helpful.', '2026-10-18 14:34:32'),
(3, 3, 3, 'I have shut off the isolation valve temporarily while getting replacement brass tap.', '2026-10-19 01:34:32'),
(4, 4, 2, 'Issue is resolved. Please test the fan switch on the rear panel.', '2026-10-19 00:34:32');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_history`
--

CREATE TABLE `ticket_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `changed_by` int(10) UNSIGNED NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_history`
--

INSERT INTO `ticket_history` (`id`, `ticket_id`, `changed_by`, `old_status`, `new_status`, `comment`, `created_at`) VALUES
(1, 1, 4, NULL, 'OPEN', 'Ticket registered by student.', '2026-10-17 04:34:32'),
(2, 2, 4, NULL, 'OPEN', 'Ticket registered by student.', '2026-10-18 04:34:32'),
(3, 2, 1, 'OPEN', 'ASSIGNED', 'Assigned to Suresh Kumar (Network/Electrical specialist).', '2026-10-18 10:34:32'),
(4, 3, 5, NULL, 'OPEN', 'Ticket registered by student.', '2026-10-18 16:34:32'),
(5, 3, 1, 'OPEN', 'ASSIGNED', 'Assigned to Ramesh Patel.', '2026-10-18 18:34:32'),
(6, 3, 3, 'ASSIGNED', 'IN_PROGRESS', 'Technician arrived on site with replacement washer/valve.', '2026-10-19 00:34:32'),
(7, 4, 5, NULL, 'OPEN', 'Ticket registered by student.', '2026-10-16 04:34:32'),
(8, 4, 1, 'OPEN', 'ASSIGNED', 'Assigned to Suresh Kumar.', '2026-10-17 04:34:32'),
(9, 4, 2, 'ASSIGNED', 'IN_PROGRESS', 'Inspected motor bearing.', '2026-10-18 04:34:32'),
(10, 4, 2, 'IN_PROGRESS', 'RESOLVED', 'Replaced capacitor and lubricated bearings. Running smoothly.', '2026-10-19 00:34:32'),
(11, 5, 4, NULL, 'OPEN', 'Ticket registered by student.', '2026-10-14 04:34:32'),
(12, 5, 1, 'OPEN', 'ASSIGNED', 'Assigned to carpenter.', '2026-10-15 04:34:32'),
(13, 5, 1, 'ASSIGNED', 'IN_PROGRESS', 'Carpenter team notified.', '2026-10-16 04:34:32'),
(14, 5, 1, 'IN_PROGRESS', 'RESOLVED', 'Desk top replaced with new wooden plank.', '2026-10-17 04:34:32'),
(15, 5, 1, 'RESOLVED', 'CLOSED', 'Student verified and ticket closed by admin.', '2026-10-18 04:34:32'),
(16, 6, 6, NULL, 'OPEN', 'Ticket registered by student via web portal.', '2026-10-19 05:12:22'),
(17, 7, 7, NULL, 'OPEN', 'Ticket registered by student via web portal.', '2026-10-19 07:29:13'),
(18, 7, 1, 'OPEN', 'RESOLVED', 'Status updated to RESOLVED', '2026-10-19 07:31:19'),
(19, 8, 4, NULL, 'OPEN', 'Ticket registered by student via web portal.', '2026-10-24 04:47:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','admin','technician') NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'System Administrator', 'admin@campus.edu', '$2y$10$b1m0i418e/nOD4coxFBXteOrcgG920Yvl2.StVtkrCMQNjYVnV7eq', 'admin', '2026-10-19 04:34:32'),
(2, 'Suresh Kumar (Network/Electrical)', 'suresh.tech@campus.edu', '$2y$10$dCYVVe0ANJYJlwmZP2RDUe74Eosg37JVuSuuAKZutAh/t.d2pAmT.', 'technician', '2026-10-19 04:34:32'),
(3, 'Ramesh Patel (Plumbing/Maintenance)', 'ramesh.tech@campus.edu', '$2y$10$dCYVVe0ANJYJlwmZP2RDUe74Eosg37JVuSuuAKZutAh/t.d2pAmT.', 'technician', '2026-10-19 04:34:32'),
(4, 'Ananya Gupta', 'ananya.student@campus.edu', '$2y$10$F98ykyqbdxsSMHZ6s3WmVePXXU4qGI9zqJ.D9XOBEQmPB2tc7TNvG', 'student', '2026-10-19 04:34:32'),
(5, 'Rohan Verma', 'rohan.student@campus.edu', '$2y$10$F98ykyqbdxsSMHZ6s3WmVePXXU4qGI9zqJ.D9XOBEQmPB2tc7TNvG', 'student', '2026-10-19 04:34:32'),
(6, 'Tanmay Kishor Dafe', 'tanmaydafe855@gmail.com', '$2y$10$/qXEka8EfbCsTetHnS4Lhusp7n14eDbQDL5rktutqYCq8pj55O7pW', 'student', '2026-10-19 05:00:47'),
(7, 'pawanpatil@gmail.com', 'pawanpatil@gmail.com', '$2y$10$D1NDWpuRv7a5or3ixMxDr.QvtPmqmQ01lUkDXODx0kxABNun53TAG', 'student', '2026-10-19 07:26:56'),
(8, 'ram', 'ram@gmail.com', '$2y$10$HwveKD8rJNSEXamfTb5Zb.ISJCRmg5QQSAnKdy3HqqEWOZB1zLgqa', 'student', '2026-10-22 13:46:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_code_token` (`qr_code_token`),
  ADD KEY `idx_locations_building` (`building`),
  ADD KEY `idx_locations_qr` (`qr_code_token`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_code` (`ticket_code`),
  ADD KEY `idx_tickets_reporter` (`reporter_id`),
  ADD KEY `idx_tickets_category` (`category_id`),
  ADD KEY `idx_tickets_location` (`location_id`),
  ADD KEY `idx_tickets_assigned` (`assigned_to`),
  ADD KEY `idx_tickets_status` (`status`),
  ADD KEY `idx_tickets_priority` (`priority`),
  ADD KEY `idx_tickets_created` (`created_at`);

--
-- Indexes for table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tc_ticket` (`ticket_id`),
  ADD KEY `idx_tc_user` (`user_id`);

--
-- Indexes for table `ticket_history`
--
ALTER TABLE `ticket_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_th_ticket` (`ticket_id`),
  ADD KEY `idx_th_changed_by` (`changed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ticket_history`
--
ALTER TABLE `ticket_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  ADD CONSTRAINT `fk_tc_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `ticket_history`
--
ALTER TABLE `ticket_history`
  ADD CONSTRAINT `fk_th_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_th_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
