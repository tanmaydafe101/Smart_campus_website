-- =============================================================================
-- Smart Campus Issue & Facility Management System
-- Database Schema: smart_campus
-- Designed for: MySQL 5.7+ / MariaDB 10.4+ / XAMPP phpMyAdmin
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `smart_campus` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `smart_campus`;

-- Disable foreign key checks during creation and seeding
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. Table: users
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `ticket_comments`;
DROP TABLE IF EXISTS `ticket_history`;
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `locations`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin', 'technician') NOT NULL DEFAULT 'student',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Table: categories
-- -----------------------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `default_priority` ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL DEFAULT 'MEDIUM',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Table: locations
-- -----------------------------------------------------------------------------
CREATE TABLE `locations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `building` VARCHAR(100) NOT NULL,
  `room` VARCHAR(50) NOT NULL,
  `qr_code_token` VARCHAR(64) NOT NULL UNIQUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_locations_building` (`building`),
  INDEX `idx_locations_qr` (`qr_code_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Table: tickets
-- Workflow: OPEN -> ASSIGNED -> IN_PROGRESS -> RESOLVED -> CLOSED
-- -----------------------------------------------------------------------------
CREATE TABLE `tickets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_code` VARCHAR(30) NOT NULL UNIQUE,
  `reporter_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `photo_path` VARCHAR(255) NULL,
  `priority` ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL DEFAULT 'MEDIUM',
  `status` ENUM('OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED') NOT NULL DEFAULT 'OPEN',
  `assigned_to` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `due_at` DATETIME NULL,
  `resolved_at` DATETIME NULL,
  INDEX `idx_tickets_reporter` (`reporter_id`),
  INDEX `idx_tickets_category` (`category_id`),
  INDEX `idx_tickets_location` (`location_id`),
  INDEX `idx_tickets_assigned` (`assigned_to`),
  INDEX `idx_tickets_status` (`status`),
  INDEX `idx_tickets_priority` (`priority`),
  INDEX `idx_tickets_created` (`created_at`),
  CONSTRAINT `fk_tickets_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tickets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tickets_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Table: ticket_history
-- -----------------------------------------------------------------------------
CREATE TABLE `ticket_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT UNSIGNED NOT NULL,
  `changed_by` INT UNSIGNED NOT NULL,
  `old_status` VARCHAR(20) NULL,
  `new_status` VARCHAR(20) NOT NULL,
  `comment` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_th_ticket` (`ticket_id`),
  INDEX `idx_th_changed_by` (`changed_by`),
  CONSTRAINT `fk_th_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_th_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Table: ticket_comments
-- -----------------------------------------------------------------------------
CREATE TABLE `ticket_comments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tc_ticket` (`ticket_id`),
  INDEX `idx_tc_user` (`user_id`),
  CONSTRAINT `fk_tc_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- SAMPLE DATA INSERTION (FOR IMMEDIATE TESTING & DEMO)
-- =============================================================================

-- Passwords:
-- Admin:      admin@campus.edu       -> Admin@123
-- Technician: suresh.tech@campus.edu -> Tech@123
-- Technician: ramesh.tech@campus.edu -> Tech@123
-- Student:    ananya.student@campus.edu -> Student@123
-- Student:    rohan.student@campus.edu  -> Student@123

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`) VALUES
(1, 'System Administrator', 'admin@campus.edu', '$2y$10$b1m0i418e/nOD4coxFBXteOrcgG920Yvl2.StVtkrCMQNjYVnV7eq', 'admin'),
(2, 'Suresh Kumar (Network/Electrical)', 'suresh.tech@campus.edu', '$2y$10$dCYVVe0ANJYJlwmZP2RDUe74Eosg37JVuSuuAKZutAh/t.d2pAmT.', 'technician'),
(3, 'Ramesh Patel (Plumbing/Maintenance)', 'ramesh.tech@campus.edu', '$2y$10$dCYVVe0ANJYJlwmZP2RDUe74Eosg37JVuSuuAKZutAh/t.d2pAmT.', 'technician'),
(4, 'Ananya Gupta', 'ananya.student@campus.edu', '$2y$10$F98ykyqbdxsSMHZ6s3WmVePXXU4qGI9zqJ.D9XOBEQmPB2tc7TNvG', 'student'),
(5, 'Rohan Verma', 'rohan.student@campus.edu', '$2y$10$F98ykyqbdxsSMHZ6s3WmVePXXU4qGI9zqJ.D9XOBEQmPB2tc7TNvG', 'student');

-- Categories
INSERT INTO `categories` (`id`, `name`, `description`, `default_priority`) VALUES
(1, 'Wi-Fi & Internet', 'Campus Wi-Fi connectivity, speed issues, LAN cable port faults, and portal authentication errors', 'HIGH'),
(2, 'Electrical & Lighting', 'Faulty ceiling fans, tube lights, loose switchboards, projector power trip, or AC malfunctioning', 'MEDIUM'),
(3, 'Classroom & Furniture', 'Broken desks, damaged benches, blackboard/whiteboard issues, door latches, and window panes', 'LOW'),
(4, 'Computer Lab & Projectors', 'PC hardware failure, blue screens, display projector flickering, VGA/HDMI cable damage', 'HIGH'),
(5, 'Plumbing & Cleanliness', 'Washroom hygiene, leaking faucets, clogged drains, water dispenser filter replacement, trash bins', 'CRITICAL'),
(6, 'Other Campus Facilities', 'Canteen seating, gym equipment, library noise, sports ground issues, and general campus infrastructure', 'LOW');

-- Locations
INSERT INTO `locations` (`id`, `building`, `room`, `qr_code_token`) VALUES
(1, 'Academic Block A', 'Room 101 (Lecture Hall)', 'CAMPUS-AB-101'),
(2, 'Academic Block A', 'Room 204 (Smart Classroom)', 'CAMPUS-AB-204'),
(3, 'Computer Science Block', 'Lab 2 (Systems & AI Lab)', 'CAMPUS-CS-LAB2'),
(4, 'Computer Science Block', 'Lab 5 (Network & IoT Lab)', 'CAMPUS-CS-LAB5'),
(5, 'Central Library', '2nd Floor Reading Hall', 'CAMPUS-LIB-RH2'),
(6, 'Main Auditorium', 'AV Control Room', 'CAMPUS-AUD-AV01'),
(7, 'Student Amenities Centre', 'Cafeteria Ground Floor', 'CAMPUS-SAC-CAF1');

-- Sample Complaints (Tickets)
INSERT INTO `tickets` (`id`, `ticket_code`, `reporter_id`, `category_id`, `location_id`, `title`, `description`, `photo_path`, `priority`, `status`, `assigned_to`, `created_at`, `due_at`, `resolved_at`) VALUES
(1, 'TICK-2026-0001', 4, 1, 1, 'Wi-Fi dropping frequently in Room 101', 'Students are unable to access the online portal during class. The access point blinks red intermittently.', NULL, 'HIGH', 'OPEN', NULL, NOW() - INTERVAL 2 DAY, NOW() + INTERVAL 1 DAY, NULL),
(2, 'TICK-2026-0002', 4, 4, 3, 'Projector lamp flickering and dim display', 'Ceiling projector in CS Lab 2 flickers every 3 minutes. HDMI cable also seems loose.', NULL, 'HIGH', 'ASSIGNED', 2, NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 2 DAY, NULL),
(3, 'TICK-2026-0003', 5, 5, 7, 'Water cooler tap continuously leaking', 'Water cooler tap at cafeteria ground floor is loose and leaking clean drinking water continuously.', NULL, 'CRITICAL', 'IN_PROGRESS', 3, NOW() - INTERVAL 12 HOUR, NOW() + INTERVAL 12 HOUR, NULL),
(4, 'TICK-2026-0004', 5, 2, 2, 'Fan #3 making loud screeching noise', 'Ceiling fan near the back row in Room 204 makes a loud rattling and screeching sound.', NULL, 'MEDIUM', 'RESOLVED', 2, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 4 HOUR),
(5, 'TICK-2026-0005', 4, 3, 5, 'Broken study desk near window #4', 'Desk surface is cracked and nail is exposed, could cause minor injury.', NULL, 'LOW', 'CLOSED', 2, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 2 DAY);

-- Sample Ticket History
INSERT INTO `ticket_history` (`ticket_id`, `changed_by`, `old_status`, `new_status`, `comment`, `created_at`) VALUES
(1, 4, NULL, 'OPEN', 'Ticket registered by student.', NOW() - INTERVAL 2 DAY),
(2, 4, NULL, 'OPEN', 'Ticket registered by student.', NOW() - INTERVAL 1 DAY),
(2, 1, 'OPEN', 'ASSIGNED', 'Assigned to Suresh Kumar (Network/Electrical specialist).', NOW() - INTERVAL 18 HOUR),
(3, 5, NULL, 'OPEN', 'Ticket registered by student.', NOW() - INTERVAL 12 HOUR),
(3, 1, 'OPEN', 'ASSIGNED', 'Assigned to Ramesh Patel.', NOW() - INTERVAL 10 HOUR),
(3, 3, 'ASSIGNED', 'IN_PROGRESS', 'Technician arrived on site with replacement washer/valve.', NOW() - INTERVAL 4 HOUR),
(4, 5, NULL, 'OPEN', 'Ticket registered by student.', NOW() - INTERVAL 3 DAY),
(4, 1, 'OPEN', 'ASSIGNED', 'Assigned to Suresh Kumar.', NOW() - INTERVAL 2 DAY),
(4, 2, 'ASSIGNED', 'IN_PROGRESS', 'Inspected motor bearing.', NOW() - INTERVAL 1 DAY),
(4, 2, 'IN_PROGRESS', 'RESOLVED', 'Replaced capacitor and lubricated bearings. Running smoothly.', NOW() - INTERVAL 4 HOUR),
(5, 4, NULL, 'OPEN', 'Ticket registered by student.', NOW() - INTERVAL 5 DAY),
(5, 1, 'OPEN', 'ASSIGNED', 'Assigned to carpenter.', NOW() - INTERVAL 4 DAY),
(5, 1, 'ASSIGNED', 'IN_PROGRESS', 'Carpenter team notified.', NOW() - INTERVAL 3 DAY),
(5, 1, 'IN_PROGRESS', 'RESOLVED', 'Desk top replaced with new wooden plank.', NOW() - INTERVAL 2 DAY),
(5, 1, 'RESOLVED', 'CLOSED', 'Student verified and ticket closed by admin.', NOW() - INTERVAL 1 DAY);

-- Sample Comments
INSERT INTO `ticket_comments` (`ticket_id`, `user_id`, `message`, `created_at`) VALUES
(2, 1, 'We have dispatched technician Suresh to inspect the HDMI cable and projector lamp.', NOW() - INTERVAL 16 HOUR),
(2, 4, 'Thank you! Classes resume at 2 PM, inspection before that would be very helpful.', NOW() - INTERVAL 14 HOUR),
(3, 3, 'I have shut off the isolation valve temporarily while getting replacement brass tap.', NOW() - INTERVAL 3 HOUR),
(4, 2, 'Issue is resolved. Please test the fan switch on the rear panel.', NOW() - INTERVAL 4 HOUR);
