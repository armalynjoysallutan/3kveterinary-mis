-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 01, 2026 at 03:19 AM
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
-- Database: `veterinary_mis`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `account_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Staff','Customer') NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`account_id`, `username`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'florencegem1@gmail.com', '$2y$10$9K6NdiQ9gGTDN4HETfMf1.cWcpSzfMZ1.lKUctUAX.W6Bom3a4T3O', 'Admin', 'Active', '2026-06-18 00:53:46', '2026-07-26 17:34:55'),
(2, 'staff', 'staff@3kpet.com', '$2y$12$ppCjHthtSSRyhxdlnvtT7ejE56QCaLNa20xbM8K/jVU4CvLnB8gs6', 'Staff', 'Active', '2026-08-29 22:39:24', '2026-08-29 22:49:31');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `service_category` varchar(100) NOT NULL,
  `service` varchar(100) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `appointment_type` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `appointment_source` varchar(20) NOT NULL DEFAULT 'Walk-in',
  `billing_created` tinyint(1) DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `customer_id`, `pet_id`, `service_category`, `service`, `appointment_date`, `appointment_time`, `appointment_type`, `reason`, `cancellation_reason`, `status`, `appointment_source`, `billing_created`, `is_archived`, `cancel_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'particulars', 'Consultation', '2026-08-10', '11:30:00', 'initial', 'g', NULL, 'Completed', 'Walk-in', 0, 0, NULL, '2026-08-08 21:50:54', '2026-08-08 22:38:04'),
(2, 2, 2, 'deworming', 'Tablet', '2026-08-11', '01:00:00', 'initial', 'Deworm', NULL, 'Completed', 'Walk-in', 1, 1, NULL, '2026-08-09 01:48:24', '2026-08-23 21:38:35'),
(3, 3, 3, 'vaccination', 'QuadCat', '2026-08-10', '09:00:00', 'initial', 'Injection', NULL, 'Completed', 'Walk-in', 1, 0, NULL, '2026-08-09 01:58:51', '2026-08-13 07:15:17'),
(4, 4, 4, 'vaccination', '9 in 1', '2026-08-11', '02:00:00', 'initial', 'Vaccine ngani', NULL, 'Arrived', 'Walk-in', 0, 0, NULL, '2026-08-09 02:21:25', '2026-08-09 21:55:42'),
(5, 2, 2, 'particulars', 'Consultation', '2026-08-25', '11:00:00', 'followup', '', NULL, 'Pending', 'Walk-in', 0, 0, NULL, '2026-08-09 19:08:26', NULL),
(6, 5, 5, 'vaccination', 'Anti Rabies', '2026-08-14', '02:30:00', 'initial', '', 'Veterinarian unavailable', 'Cancelled', 'Walk-in', 0, 0, NULL, '2026-08-09 19:20:10', '2026-08-09 23:38:34'),
(7, 6, 6, 'vaccination', '9 in 1', '2026-08-13', '01:30:00', 'initial', '', NULL, 'Completed', 'Walk-in', 1, 1, NULL, '2026-08-09 19:50:52', '2026-08-23 21:38:35'),
(8, 2, 2, 'particulars', 'Consultation', '2026-08-19', '10:30:00', 'initial', '', NULL, 'Pending', 'Walk-in', 0, 0, NULL, '2026-08-09 19:54:36', NULL),
(9, 1, 1, 'particulars', 'Consultation', '2026-08-14', '10:00:00', 'initial', '', NULL, 'Pending', 'Walk-in', 0, 0, NULL, '2026-08-09 20:06:52', NULL),
(10, 1, 1, 'vaccination', 'Anti Rabies', '2026-08-12', '02:00:00', 'initial', '', NULL, 'Arrived', 'Walk-in', 0, 0, NULL, '2026-08-09 20:11:14', '2026-08-09 22:16:27'),
(11, 1, 1, 'particulars', 'Consultation', '2026-08-13', '11:00:00', 'initial', '', 'Wala lang', 'Cancelled', 'Walk-in', 0, 0, NULL, '2026-08-09 20:15:31', '2026-08-09 23:38:54'),
(12, 2, 2, 'vaccination', 'QuadCat', '2026-08-10', '04:00:00', 'initial', '', NULL, 'Confirmed', 'Walk-in', 0, 0, NULL, '2026-08-09 20:17:27', '2026-08-09 22:16:16'),
(13, 2, 2, 'vaccination', 'Anti Rabies', '2026-08-11', '08:30:00', 'initial', '', NULL, 'Completed', 'Walk-in', 0, 0, NULL, '2026-08-09 20:19:48', '2026-08-09 22:16:44'),
(14, 2, 2, 'vaccination', 'QuadCat', '2026-08-12', '13:30:00', 'initial', '', NULL, 'Completed', 'Walk-in', 0, 0, NULL, '2026-08-09 20:46:11', '2026-08-09 22:17:00'),
(15, 7, 7, 'vaccination', 'Anti Rabies', '2026-08-14', '13:30:00', 'initial', '', NULL, 'Confirmed', 'Walk-in', 0, 0, NULL, '2026-08-09 22:54:36', '2026-08-09 22:55:19'),
(16, 8, 8, 'deworming', 'Paste', '2026-08-14', '14:00:00', 'initial', '', NULL, 'Completed', 'Walk-in', 0, 0, NULL, '2026-08-09 22:58:37', '2026-08-10 18:39:07'),
(17, 2, 9, 'vaccination', 'QuadCat', '2026-08-11', '13:30:00', 'initial', '', NULL, 'Confirmed', 'Walk-in', 0, 0, NULL, '2026-08-10 01:52:14', '2026-08-10 18:43:28'),
(18, 9, 10, 'deworming', 'Tablet', '2026-08-11', '15:00:00', 'followup', '', NULL, 'Completed', 'Walk-in', 1, 1, NULL, '2026-08-10 04:49:03', '2026-08-23 21:38:35'),
(19, 6, 6, 'deworming', 'Tablet', '2026-08-14', '09:30:00', 'followup', '', NULL, 'Confirmed', 'Walk-in', 0, 0, NULL, '2026-08-10 18:31:14', '2026-08-10 18:31:55'),
(20, 2, 9, 'deworming', 'Tablet', '2026-08-14', '09:30:00', 'followup', '', NULL, 'Pending', 'Walk-in', 0, 0, NULL, '2026-08-10 18:37:34', NULL),
(21, 8, 8, 'deworming', 'Tablet', '2026-08-14', '09:30:00', 'initial', '', NULL, 'Confirmed', 'Walk-in', 0, 0, NULL, '2026-08-10 18:39:48', '2026-08-10 18:50:10'),
(22, 10, 11, 'Deworming', 'Tablet', '2026-08-15', '11:00:00', 'initial', '', NULL, 'Completed', 'Walk-in', 1, 1, NULL, '2026-08-12 08:39:17', '2026-08-23 21:38:35'),
(23, 8, 8, 'Vaccination', 'Anti-Rabies', '2026-08-15', '11:00:00', 'initial', '', NULL, 'Pending', 'Walk-in', 0, 0, NULL, '2026-08-13 20:16:47', NULL),
(24, 8, 8, 'Deworming', 'Paste', '2026-08-18', '15:00:00', 'initial', '', NULL, 'Completed', 'Walk-in', 1, 1, NULL, '2026-08-17 23:17:29', '2026-08-23 21:38:35'),
(25, 2, 12, 'Vaccination', 'Anti-Rabies', '2026-08-20', '13:00:00', 'initial', '', NULL, 'Completed', 'Walk-in', 1, 1, NULL, '2026-08-18 01:30:33', '2026-08-23 21:38:35'),
(26, 11, 13, 'Vaccination', 'QuadCat', '2026-08-22', '10:30:00', 'initial', '', NULL, 'Completed', 'Walk-in', 1, 1, NULL, '2026-08-22 01:58:47', '2026-08-23 21:38:35'),
(27, 12, 14, 'Deworming', 'Paste', '2026-08-31', '10:00:00', 'initial', '', NULL, 'Completed', 'Walk-in', 1, 1, NULL, '2026-08-28 21:52:53', '2026-08-28 22:00:15'),
(28, 13, 15, 'Vaccination', 'Anti-Rabies', '2026-08-31', '14:00:00', 'initial', '', NULL, 'Completed', 'Walk-in', 1, 0, NULL, '2026-08-29 00:45:39', '2026-08-30 21:13:16'),
(29, 2, 12, 'Vaccination', '9 in 1', '2026-10-13', '10:00:00', 'initial', '', NULL, 'Confirmed', 'Walk-in', 0, 0, NULL, '2026-08-29 23:32:25', '2026-08-29 23:33:10'),
(30, 14, 16, 'Vaccination', '9 in 1', '2026-09-02', '15:30:00', 'initial', '', NULL, 'Completed', 'Walk-in', 1, 1, NULL, '2026-08-30 21:39:17', '2026-08-31 22:51:05'),
(31, 15, 17, 'Deworming', 'Paste', '2026-09-02', '15:30:00', 'initial', '', NULL, 'Completed', 'Walk-in', 1, 1, NULL, '2026-08-31 19:18:26', '2026-08-31 20:34:20'),
(32, 16, 18, 'Laboratory', 'Ultrasound', '2026-09-07', '11:30:00', 'initial', '', NULL, 'Completed', 'Walk-in', 1, 1, NULL, '2026-08-31 19:37:22', '2026-08-31 19:45:52'),
(33, 17, 19, 'Vaccination', 'Anti-Rabies', '2026-09-05', '10:00:00', 'initial', '', NULL, 'Completed', 'Walk-in', 1, 1, NULL, '2026-08-31 23:01:20', '2026-08-31 23:13:22');

-- --------------------------------------------------------

--
-- Table structure for table `appointments_old`
--

CREATE TABLE `appointments_old` (
  `appointment_id` int(11) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `pet_name` varchar(100) NOT NULL,
  `service` varchar(100) NOT NULL,
  `visit_type` varchar(50) NOT NULL DEFAULT 'New Consultation',
  `next_visit` date DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `billing_created` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments_old`
--

INSERT INTO `appointments_old` (`appointment_id`, `owner_name`, `pet_name`, `service`, `visit_type`, `next_visit`, `appointment_date`, `appointment_time`, `status`, `billing_created`, `is_archived`, `cancel_reason`, `created_at`, `updated_at`) VALUES
(8, 'Juan Dela Cruz', 'Max', 'Vaccination', 'New Consultation', NULL, '2026-06-29', '09:00:00', 'Pending', 0, 0, NULL, '2026-07-03 02:46:29', NULL),
(9, 'Maria Santos', 'Luna', 'Grooming', 'New Consultation', NULL, '2026-06-30', '10:30:00', 'Confirmed', 0, 0, NULL, '2026-07-03 02:46:29', NULL),
(10, 'Carlo Reyes', 'Milo', 'Checkup', 'New Consultation', NULL, '2026-07-01', '01:00:00', 'Pending', 0, 0, NULL, '2026-07-03 02:46:29', NULL),
(11, 'Angela Cruz', 'Coco', 'Deworming', 'New Consultation', NULL, '2026-07-02', '02:30:00', 'Completed', 0, 0, NULL, '2026-07-03 02:46:29', NULL),
(12, 'Paolo Ramos', 'Bantay', 'Vaccination', 'New Consultation', NULL, '2026-07-03', '11:00:00', 'Pending', 0, 0, NULL, '2026-07-03 02:46:29', NULL),
(13, 'Jessa Lim', 'Snow', 'Consultation', 'New Consultation', NULL, '2026-07-04', '03:00:00', 'Confirmed', 0, 0, NULL, '2026-07-03 02:46:29', NULL),
(14, 'Rica Gomez', 'Tiger', 'Checkup', 'New Consultation', NULL, '2026-07-05', '04:00:00', 'Pending', 0, 0, NULL, '2026-07-03 02:46:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointment_schedule`
--

CREATE TABLE `appointment_schedule` (
  `schedule_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `opening_time` time DEFAULT NULL,
  `closing_time` time DEFAULT NULL,
  `slot_interval` int(11) NOT NULL DEFAULT 30,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_schedule`
--

INSERT INTO `appointment_schedule` (`schedule_id`, `day_of_week`, `is_available`, `opening_time`, `closing_time`, `slot_interval`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Monday', 1, '08:00:00', '17:00:00', 30, 'Active', '2026-08-13 23:10:32', '2026-08-14 02:25:50'),
(2, 'Tuesday', 1, '08:00:00', '17:00:00', 30, 'Active', '2026-08-13 23:10:32', '2026-08-14 02:25:50'),
(3, 'Wednesday', 1, '08:00:00', '17:00:00', 30, 'Active', '2026-08-13 23:10:32', '2026-08-14 02:25:50'),
(4, 'Thursday', 1, '08:00:00', '17:00:00', 30, 'Active', '2026-08-13 23:10:32', '2026-08-14 02:25:50'),
(5, 'Friday', 1, '08:00:00', '17:00:00', 30, 'Active', '2026-08-13 23:10:32', '2026-08-14 02:25:50'),
(6, 'Saturday', 1, '08:00:00', '17:00:00', 30, 'Active', '2026-08-13 23:10:32', '2026-08-14 02:25:50'),
(7, 'Sunday', 0, '08:00:00', '17:00:00', 30, 'Inactive', '2026-08-13 23:10:32', '2026-08-14 02:25:50');

-- --------------------------------------------------------

--
-- Table structure for table `appointment_statuses`
--

CREATE TABLE `appointment_statuses` (
  `appointment_status_id` int(11) NOT NULL,
  `appointment_status` varchar(50) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_statuses`
--

INSERT INTO `appointment_statuses` (`appointment_status_id`, `appointment_status`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Pending', 'Active', '2026-08-13 23:09:33', NULL),
(2, 'Confirmed', 'Active', '2026-08-13 23:09:33', NULL),
(3, 'Arrived', 'Active', '2026-08-13 23:09:33', NULL),
(4, 'Completed', 'Active', '2026-08-13 23:09:33', NULL),
(5, 'Cancelled', 'Active', '2026-08-13 23:09:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointment_types`
--

CREATE TABLE `appointment_types` (
  `appointment_type_id` int(11) NOT NULL,
  `appointment_type` varchar(50) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_types`
--

INSERT INTO `appointment_types` (`appointment_type_id`, `appointment_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Initial', 'Active', '2026-08-13 23:07:08', NULL),
(2, 'Follow-up', 'Active', '2026-08-13 23:07:08', NULL),
(3, 'Emergency', 'Active', '2026-08-14 01:03:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `module` varchar(100) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `username`, `role`, `module`, `action`, `description`, `reference_no`, `created_at`) VALUES
(1, 1, 'admin', 'Admin', 'Audit Trail', 'Created', 'Test audit log entry.', 'TEST-001', '2026-08-26 20:35:54'),
(2, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-26 20:52:31'),
(3, 1, 'admin', 'Admin', 'Authentication', 'Logout', 'User logged out of the system.', NULL, '2026-08-26 20:55:58'),
(4, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-26 20:56:07'),
(5, 1, 'admin', 'Admin', 'Inventory', 'Archived', 'Inventory item \"Catheter BLUES\" was archived. Reason: Discontinued', 'CS-017', '2026-08-26 21:21:56'),
(6, 1, 'admin', 'Admin', 'Inventory', 'Restored', 'Inventory item \"Catheter BLUES\" was restored from archive.', 'CS-017', '2026-08-26 21:31:13'),
(7, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-28 14:04:12'),
(8, 1, 'admin', 'Admin', 'Inventory', 'Stock In', 'Inventory item \"Catheter DOG\" received 100 unit(s). Batch: BAT 2026-001.', 'CS-016', '2026-08-28 14:13:31'),
(9, 1, 'admin', 'Admin', 'Inventory', 'Stock Out', 'Inventory item \"Catheter DOG\" had 5 unit(s) removed. Batch: BAT 2026-001. Reason: Used for Treatment/Procedure.', 'CS-016', '2026-08-28 14:18:48'),
(10, 1, 'admin', 'Admin', 'Inventory', 'Updated', 'Inventory item \"Catheter (DOG)\" was updated.', 'CS-016', '2026-08-28 14:27:48'),
(11, 1, 'admin', 'Admin', 'Billing', 'Created', 'Billing #9 was created from Medical Record #9.', '9', '2026-08-28 14:54:21'),
(12, 1, 'admin', 'Admin', 'Customer Records', 'Archived', 'Archived customer record', '1', '2026-08-28 16:30:15'),
(13, 1, 'admin', 'Admin', 'Customer Records', 'Restored', 'Restored customer record', '1', '2026-08-28 16:42:25'),
(14, 1, 'admin', 'Admin', 'Medical Records', 'Created', 'Medical Record #10 was created for Pet #9.', '10', '2026-08-28 19:15:45'),
(15, 1, 'admin', 'Admin', 'Medical Records', 'Created', 'Medical Record #11 was created for Pet #15.', '11', '2026-08-28 20:17:17'),
(16, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-29 11:30:12'),
(17, 1, 'admin', 'Admin', 'Authentication', 'Logout', 'User logged out of the system.', NULL, '2026-08-29 15:17:51'),
(18, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-29 15:18:02'),
(19, 1, 'admin', 'Admin', 'Authentication', 'Logout', 'User logged out of the system.', NULL, '2026-08-29 15:46:59'),
(20, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-29 15:49:52'),
(21, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-29 16:03:33'),
(22, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-29 16:13:43'),
(23, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-29 16:24:23'),
(24, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-29 18:26:11'),
(25, 1, 'admin', 'Admin', 'Authentication', 'Logout', 'User logged out of the system.', NULL, '2026-08-29 18:26:25'),
(26, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-29 18:26:34'),
(27, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-30 11:41:47'),
(28, NULL, NULL, NULL, 'Inventory', 'Stock In', 'Inventory item \"Catheter BLUES\" received 50 unit(s). Batch: BAT-2026-002.', 'CS-017', '2026-08-30 12:54:52'),
(29, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-30 12:55:35'),
(30, 1, 'admin', 'Admin', 'Authentication', 'Logout', 'User logged out of the system.', NULL, '2026-08-30 13:02:15'),
(31, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-30 13:02:25'),
(32, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-30 13:14:34'),
(33, 1, 'admin', 'Admin', 'Inventory', 'Stock In', 'Inventory item \"Catheter ORANGE\" received 50 unit(s). Batch: BAT-2026-001.', 'CS-018', '2026-08-30 13:17:16'),
(34, 1, 'admin', 'Admin', 'Authentication', 'Logout', 'User logged out of the system.', NULL, '2026-08-30 13:18:36'),
(35, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-30 13:18:48'),
(36, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-30 13:49:17'),
(37, 1, 'admin', 'Admin', 'Authentication', 'Logout', 'User logged out of the system.', NULL, '2026-08-30 13:49:25'),
(38, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-30 13:49:33'),
(39, NULL, NULL, NULL, 'Billing', 'Created', 'Billing #10 was created from Medical Record #11.', '10', '2026-08-30 14:13:16'),
(40, NULL, NULL, NULL, 'Medical Records', 'Created', 'Medical Record #12 was created for Pet #16.', '12', '2026-08-30 14:40:47'),
(41, NULL, NULL, NULL, 'Billing', 'Created', 'Billing #11 was created from Medical Record #12.', '11', '2026-08-30 14:41:04'),
(42, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-30 14:57:04'),
(43, 1, 'admin', 'Admin', 'Authentication', 'Logout', 'User logged out of the system.', NULL, '2026-08-30 15:20:26'),
(44, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-30 15:20:35'),
(45, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-30 15:21:19'),
(46, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-31 12:13:08'),
(47, 1, 'admin', 'Admin', 'Medical Records', 'Created', 'Medical Record #13 was created for Pet #17.', '13', '2026-08-31 12:26:32'),
(48, 1, 'admin', 'Admin', 'Billing', 'Created', 'Billing #12 was created from Medical Record #13.', '12', '2026-08-31 12:26:45'),
(49, 1, 'admin', 'Admin', 'Medical Records', 'Created', 'Medical Record #14 was created for Pet #18.', '14', '2026-08-31 12:45:14'),
(50, 1, 'admin', 'Admin', 'Billing', 'Created', 'Billing #13 was created from Medical Record #14.', '13', '2026-08-31 12:45:27'),
(51, 1, 'admin', 'Admin', 'Billing', 'Payment Confirmed', 'Payment for Billing #13 was confirmed. Amount paid: ₱2,000.00. Change: ₱0.00.', '13', '2026-08-31 12:45:52'),
(52, 1, 'admin', 'Admin', 'Billing', 'Archived', 'Billing #13 was automatically archived after payment was confirmed.', '13', '2026-08-31 12:45:52'),
(53, 1, 'admin', 'Admin', 'Authentication', 'Logout', 'User logged out of the system.', NULL, '2026-08-31 12:50:18'),
(54, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-31 12:50:31'),
(55, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-31 12:50:56'),
(56, 1, 'admin', 'Admin', 'Billing', 'Payment Confirmed', 'Payment for Billing #12 was confirmed. Amount paid: ₱350.00. Change: ₱0.00.', '12', '2026-08-31 13:34:20'),
(57, 1, 'admin', 'Admin', 'Billing', 'Archived', 'Billing #12 was automatically archived after payment was confirmed.', '12', '2026-08-31 13:34:20'),
(58, 1, 'admin', 'Admin', 'Authentication', 'Logout', 'User logged out of the system.', NULL, '2026-08-31 15:48:50'),
(59, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-31 15:49:05'),
(60, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-31 15:50:30'),
(61, 1, 'admin', 'Admin', 'Billing', 'Payment Confirmed', 'Payment for Billing #11 was confirmed. Amount paid: ₱650.00. Change: ₱0.00.', '11', '2026-08-31 15:51:05'),
(62, 1, 'admin', 'Admin', 'Billing', 'Archived', 'Billing #11 was automatically archived after payment was confirmed.', '11', '2026-08-31 15:51:05'),
(63, 1, 'admin', 'Admin', 'Medical Records', 'Created', 'Medical Record #15 was created for Pet #19.', '15', '2026-08-31 16:10:22'),
(64, 1, 'admin', 'Admin', 'Billing', 'Created', 'Billing #14 was created from Medical Record #15.', '14', '2026-08-31 16:11:23'),
(65, 1, 'admin', 'Admin', 'Billing', 'Payment Confirmed', 'Payment for Billing #14 was confirmed. Amount paid: ₱650.00. Change: ₱0.00.', '14', '2026-08-31 16:13:22'),
(66, 1, 'admin', 'Admin', 'Billing', 'Archived', 'Billing #14 was automatically archived after payment was confirmed.', '14', '2026-08-31 16:13:22'),
(67, 1, 'admin', 'Admin', 'Authentication', 'Logout', 'User logged out of the system.', NULL, '2026-08-31 17:00:10'),
(68, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-31 17:00:32'),
(69, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-31 17:01:19'),
(70, 1, 'admin', 'Admin', 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-31 17:02:11'),
(71, 1, 'admin', 'Admin', 'Authentication', 'Logout', 'User logged out of the system.', NULL, '2026-08-31 17:50:07'),
(72, NULL, NULL, NULL, 'Authentication', 'Login', 'User logged into the system.', NULL, '2026-08-31 17:50:18');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `billing_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `pet_weight` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `billing_status` varchar(30) NOT NULL DEFAULT 'Unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing`
--

INSERT INTO `billing` (`billing_id`, `appointment_id`, `customer_id`, `pet_id`, `pet_weight`, `total_amount`, `payment_status`, `billing_status`, `created_at`, `updated_at`) VALUES
(1, 2, 2, 2, NULL, 350.00, 'Paid', 'Paid', '2026-08-10 22:02:24', '2026-08-11 04:35:08'),
(2, 3, 3, 3, 6.00, 0.00, 'Pending', 'Unpaid', '2026-08-13 07:15:17', NULL),
(3, 22, 10, 11, 4.00, 860.00, 'Paid', 'Paid', '2026-08-13 07:22:04', '2026-08-13 08:38:52'),
(4, 7, 6, 6, 5.00, 1000.00, 'Paid', 'Paid', '2026-08-13 07:28:19', '2026-08-13 07:46:15'),
(5, 18, 9, 10, 6.00, 350.00, 'Paid', 'Paid', '2026-08-15 23:19:18', '2026-08-15 23:19:44'),
(6, 24, 8, 8, 5.00, 300.00, 'Paid', 'Paid', '2026-08-17 23:19:08', '2026-08-17 23:51:12'),
(7, 25, 2, 12, 3.00, 350.00, 'Paid', 'Paid', '2026-08-18 01:37:08', '2026-08-18 01:39:05'),
(8, 26, 11, 13, 2.50, 1100.00, 'Paid', 'Paid', '2026-08-22 02:00:08', '2026-08-22 02:00:24'),
(9, 27, 12, 14, 2.50, 300.00, 'Paid', 'Paid', '2026-08-28 21:54:21', '2026-08-28 22:00:15'),
(10, 28, 13, 15, 3.00, 350.00, 'Pending', 'Unpaid', '2026-08-30 21:13:16', NULL),
(11, 30, 14, 16, 4.70, 650.00, 'Paid', 'Paid', '2026-08-30 21:41:04', '2026-08-31 22:51:05'),
(12, 31, 15, 17, 7.00, 350.00, 'Paid', 'Paid', '2026-08-31 19:26:45', '2026-08-31 20:34:20'),
(13, 32, 16, 18, 5.00, 2000.00, 'Paid', 'Paid', '2026-08-31 19:45:27', '2026-08-31 19:45:52'),
(14, 33, 17, 19, 5.00, 650.00, 'Paid', 'Paid', '2026-08-31 23:11:23', '2026-08-31 23:13:22');

-- --------------------------------------------------------

--
-- Table structure for table `billing_items`
--

CREATE TABLE `billing_items` (
  `billing_item_id` int(11) NOT NULL,
  `billing_id` int(11) NOT NULL,
  `item_type` varchar(30) NOT NULL DEFAULT 'Service',
  `item_name` varchar(150) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(30) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_items`
--

INSERT INTO `billing_items` (`billing_item_id`, `billing_id`, `item_type`, `item_name`, `quantity`, `unit`, `unit_price`, `amount`, `created_at`) VALUES
(1, 1, 'Service', 'Tablet', 1.00, 'service', 0.00, 0.00, '2026-08-10 22:02:24'),
(2, 1, 'Vaccination', 'Anti-Rabies Vaccination', 1.00, NULL, 350.00, 350.00, '2026-08-11 03:51:16'),
(3, 2, 'Service', 'QuadCat', 1.00, 'service', 0.00, 0.00, '2026-08-13 07:15:17'),
(4, 3, 'Service', 'Tablet', 1.00, 'service', 0.00, 0.00, '2026-08-13 07:22:04'),
(5, 4, 'Service', '9 in 1', 1.00, 'service', 650.00, 650.00, '2026-08-13 07:28:19'),
(6, 4, 'Deworming', 'Pastes', 1.00, NULL, 350.00, 350.00, '2026-08-13 07:43:01'),
(7, 3, 'Deworming', 'Pastes', 1.00, NULL, 300.00, 300.00, '2026-08-13 08:22:07'),
(8, 3, 'Laboratory', 'Smear Test', 1.00, NULL, 560.00, 560.00, '2026-08-13 08:38:02'),
(9, 5, 'Service', 'Tablet', 1.00, 'service', 350.00, 350.00, '2026-08-15 23:19:18'),
(10, 6, 'Service', 'Paste', 1.00, 'service', 300.00, 300.00, '2026-08-17 23:19:08'),
(11, 7, 'Service', 'Anti-Rabies', 1.00, 'service', 350.00, 350.00, '2026-08-18 01:37:08'),
(12, 8, 'Service', 'QuadCat', 1.00, 'service', 1100.00, 1100.00, '2026-08-22 02:00:08'),
(13, 9, 'Service', 'Paste', 1.00, 'service', 300.00, 300.00, '2026-08-28 21:54:21'),
(14, 10, 'Service', 'Anti-Rabies', 1.00, 'service', 350.00, 350.00, '2026-08-30 21:13:16'),
(15, 11, 'Service', '9 in 1', 1.00, 'service', 650.00, 650.00, '2026-08-30 21:41:04'),
(16, 12, 'Service', 'Paste', 1.00, 'service', 350.00, 350.00, '2026-08-31 19:26:45'),
(17, 13, 'Service', 'Ultrasound', 1.00, 'service', 2000.00, 2000.00, '2026-08-31 19:45:27'),
(18, 14, 'Service', 'Anti-Rabies', 1.00, 'service', 350.00, 350.00, '2026-08-31 23:11:23'),
(19, 14, 'Deworming', 'Paste', 1.00, NULL, 300.00, 300.00, '2026-08-31 23:12:39');

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `event_id` int(11) NOT NULL,
  `event_title` varchar(255) NOT NULL,
  `event_type` enum('Delivery Day','Order/Restock','Other Event') NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `calendar_events`
--

INSERT INTO `calendar_events` (`event_id`, `event_title`, `event_type`, `event_date`, `event_time`, `notes`, `created_at`) VALUES
(1, 'Pet Essentials Delivery', 'Delivery Day', '2026-07-03', '10:00:00', 'Supplier delivery scheduled', '2026-07-06 23:40:26'),
(2, 'Restock Deworming Tablets', 'Order/Restock', '2026-07-03', '14:00:00', 'Prepare supplier order', '2026-07-06 23:40:26'),
(3, 'Clinic Staff Meeting', 'Other Event', '2026-07-03', '16:00:00', 'Monthly operations check-in', '2026-07-06 23:40:26');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `record_status` varchar(20) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `archive_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `owner_name`, `contact_number`, `email`, `address`, `record_status`, `created_at`, `updated_at`, `archived_at`, `archive_reason`) VALUES
(1, 'sd', '87878787878', 'renzflorentino2@gmail.com', 'ygtg', 'Active', '2026-08-08 21:50:54', '2026-08-28 23:42:25', NULL, NULL),
(2, 'ArmalynJoy Sallutan', '0968779011', 'armalynjoysallutan16@gmail.com', 'Bagong Pook Street', 'Active', '2026-08-09 01:48:24', '2026-08-09 02:05:00', NULL, NULL),
(3, 'Florence Florentino', '09781234567', 'renzflorentino2@gmail.com', 'Bacoor City', 'Active', '2026-08-09 01:58:51', '2026-08-28 23:35:43', NULL, NULL),
(4, 'Jasmine Eliza Ramirez', '09674428194', 'jasmine@gmail.com', 'Novero St.', 'Pending', '2026-08-09 02:21:25', NULL, NULL, NULL),
(5, 'Jaimeeluz Ronquillo', '09567483725', 'jaironquillo@gmai.com', 'Kokiko st.', 'Pending', '2026-08-09 19:20:10', NULL, NULL, NULL),
(6, 'Felona Adriano', '09678954326', 'f.adriano@gmail.com', 'Pangilinan St.', 'Active', '2026-08-09 19:50:52', '2026-08-09 22:20:00', NULL, NULL),
(7, 'John Rhycelle Gache', '09786847366', 'renzflorentino2@gmail.com', 'pangilinan st', 'Pending', '2026-08-09 22:54:36', NULL, NULL, NULL),
(8, 'Samantha Sonajo', '09678573452', '', 'Bacoor City', 'Active', '2026-08-09 22:58:37', '2026-08-10 18:39:07', NULL, NULL),
(9, 'Jolly Novelo', '09788734662', 'armalynjoysallutan16@gmail.com', '437D A Del Rosario St.', 'Active', '2026-08-10 04:49:03', '2026-08-15 23:19:17', NULL, NULL),
(10, 'Irma Luna', '09785765342', '', 'Bagong Pook Street', 'Active', '2026-08-12 08:39:17', '2026-08-12 08:42:25', NULL, NULL),
(11, 'Julia Dimaya', '09785732563', '', 'Bagong Pook Street', 'Active', '2026-08-22 01:58:47', '2026-08-22 01:59:05', NULL, NULL),
(12, 'Jaimeeluz Ronquillo', '09847377373', '', 'Kokiko st.', 'Active', '2026-08-28 21:52:53', '2026-08-28 21:53:08', NULL, NULL),
(13, 'John Rhycelle Gache', '09675736248', '', 'Pangilinan', 'Active', '2026-08-29 00:45:39', '2026-08-29 02:16:59', NULL, NULL),
(14, 'Candice Derain', '09785736251', '', 'San Jose st', 'Active', '2026-08-30 21:39:17', '2026-08-30 21:39:34', NULL, NULL),
(15, 'lily cruz', '09687790111', '', 'Bacoor City', 'Active', '2026-08-31 19:18:26', '2026-08-31 19:25:29', NULL, NULL),
(16, 'Charles Jherwin', '09112233441', 'jasmine@gmail.com', 'Novero St.', 'Active', '2026-08-31 19:37:22', '2026-08-31 19:39:06', NULL, NULL),
(17, 'Toni Fowler', '09876655311', 'johnrhycelle26@gmail.com', 'Pangilinan', 'Active', '2026-08-31 23:01:20', '2026-08-31 23:03:13', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_archive_checks`
--

CREATE TABLE `customer_archive_checks` (
  `id` int(11) NOT NULL,
  `check_month` char(7) NOT NULL,
  `checked_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`category_id`, `category_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Medicine', 'Medicines and pharmaceutical products used by the clinic.', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(2, 'Supplements', 'Pet vitamins, nutritional and supportive supplements.', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(3, 'Pet Food', 'Dog and cat food products stocked by the clinic.', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(4, 'Vaccines', 'Vaccines and immunization products.', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(5, 'Test Kits', 'Diagnostic test kits and related testing products.', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(6, 'Clinic Supplies', 'Surgical, medical, protective and general clinic supplies.', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(7, 'Others', 'Inventory items that do not fit the standard categories.', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `item_id` int(11) NOT NULL,
  `item_code` varchar(30) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `category_id` int(11) NOT NULL,
  `subcategory_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `reorder_level` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `retail_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive','Archived') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`item_id`, `item_code`, `item_name`, `category_id`, `subcategory_id`, `unit_id`, `supplier_id`, `reorder_level`, `unit_cost`, `retail_price`, `status`, `created_at`, `updated_at`, `archived_at`, `archive_reason`) VALUES
(1, 'FOOD-001', 'GUAPO DOG BEEF AND RICE (FOR ADULTS)', 3, NULL, 7, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-26 23:17:19', NULL, NULL),
(2, 'FOOD-002', 'GUAPO DOG BEEF AND RICE (FOR PUPPIES, LACTATING, AND PREGNANT)', 3, NULL, 7, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(3, 'FOOD-003', 'PRO HEALTH (FOR ALL STAGES)', 3, NULL, 7, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(4, 'FOOD-004', 'MIGHTY (OCEAN FISH & EGGS)', 3, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(5, 'FOOD-005', 'PATSIMO (TUNA TOPPING SALMON IN JELLY BOX OR POUCH)', 3, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(6, 'FOOD-006', 'PATSIMO (OCEAN SEAFOOD AND GRAVY CANNED)', 3, NULL, 9, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(7, 'FOOD-007', 'PATSIMO (CHICKEN CHUNK WITH LIVER IN GRAVY CANNED)', 3, NULL, 9, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(8, 'SUP-001', 'LC-SCOUR SUSPENSION VITAMINS', 2, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(9, 'SUP-002', 'CANICE ASCORBIC ACID VITAMINS SYRUP (CAT&DOG)', 2, NULL, 4, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(10, 'SUP-003', 'EAC PET IN-SIDEOUT & IMMUNE HEALTH OPTIMISER (DOG)', 2, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(11, 'SUP-004', 'KODEX EYE DROP', 2, NULL, 4, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(12, 'SUP-005', 'MICRONAZOLE NITRATE (MICRODERM) TOPICAL LOTION', 2, NULL, 5, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(13, 'SUP-006', 'PROTEXIN VETERINARY CYSTOPHAN CAPSULES (CAT)', 2, NULL, 12, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(14, 'SUP-007', 'EAC PET IN-SIDEOUT & IMMUNE HEALTH OPTIMISER (CAT)', 2, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:00', '2026-08-18 22:46:00', NULL, NULL),
(15, 'SUP-008', 'K9 LACTABOOST MILK (LACTATING DOG&CAT)', 2, NULL, 7, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(16, 'CS-001', 'Lubricant', 6, NULL, 1, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(17, 'CS-002', 'Sando Bag (M)', 6, NULL, 3, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(18, 'CS-003', 'Sando Bag', 6, NULL, 3, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(19, 'CS-004', 'Sando Bag Large', 6, NULL, 3, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(20, 'CS-005', 'Tissue', 6, NULL, 3, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(21, 'CS-006', 'Chromic 1', 6, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(22, 'CS-007', 'Chromic 3/0', 6, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(23, 'CS-008', 'Chromic 4/0', 6, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(24, 'CS-009', 'Polygactin 3/0', 6, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(25, 'CS-010', 'Polygactin 2/0', 6, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(26, 'CS-011', 'Polygactin 1', 6, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(27, 'CS-012', 'Polygactin 0', 6, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(28, 'CS-013', 'Silk 0', 6, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(29, 'CS-014', 'Silk 1', 6, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(30, 'CS-015', 'Silk 2', 6, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(31, 'CS-016', 'Catheter (DOG)', 6, NULL, 2, NULL, 30.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-28 21:27:48', NULL, NULL),
(32, 'CS-017', 'Catheter BLUES', 6, NULL, 1, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-27 04:31:13', NULL, NULL),
(33, 'CS-018', 'Catheter ORANGE', 6, NULL, 1, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(34, 'CS-019', 'Mupiderm (ointment)', 6, NULL, 5, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(35, 'CS-020', 'Cotton Balls', 6, NULL, 3, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(36, 'CS-021', 'EDTA Yellow', 6, NULL, 1, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(37, 'CS-022', 'EDTA Violet', 6, NULL, 1, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(38, 'CS-023', 'EDTA Green', 6, NULL, 1, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(39, 'CS-024', 'Gloves Non-sterile', 6, NULL, 2, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(40, 'CS-025', 'Gloves Sterile', 6, NULL, 2, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(41, 'CS-026', 'I.V. Line 2nd Line', 6, NULL, 3, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(42, 'CS-027', 'I.V. Line Ormed', 6, NULL, 3, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(43, 'CS-028', 'Leukoplast', 6, NULL, 1, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(44, 'CS-029', 'Micropore (1/2 inch tape)', 6, NULL, 2, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(45, 'CS-030', 'Micropore (1 inch tape)', 6, NULL, 2, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(46, 'CS-031', 'Needle #18', 6, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(47, 'CS-032', 'Needle #19', 6, NULL, 1, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(48, 'CS-033', 'Syringe #1 mL', 6, NULL, 2, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(49, 'CS-034', 'Syringe #10 mL', 6, NULL, 2, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(50, 'CS-035', 'Syringe #3 mL', 6, NULL, 2, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(51, 'CS-036', 'Syringe #5 mL', 6, NULL, 2, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL),
(52, 'CS-037', 'Underpads', 6, NULL, 3, NULL, 0.00, 0.00, 0.00, 'Active', '2026-08-18 22:46:01', '2026-08-18 22:46:01', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_stock`
--

CREATE TABLE `inventory_stock` (
  `stock_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `expiration_date` date DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_stock`
--

INSERT INTO `inventory_stock` (`stock_id`, `item_id`, `batch_number`, `quantity`, `expiration_date`, `date_received`, `supplier_id`, `unit_cost`, `created_at`, `updated_at`) VALUES
(1, 32, 'BAT-2026-001', 5.00, '2027-08-20', '2026-08-20', NULL, 0.00, '2026-08-21 03:08:37', '2026-08-21 03:38:59'),
(2, 31, 'BAT 2026-001', 95.00, NULL, '2026-08-28', NULL, 0.00, '2026-08-28 21:13:31', '2026-08-28 21:18:48'),
(3, 32, 'BAT-2026-002', 50.00, '2026-09-30', '2026-08-30', NULL, 0.00, '2026-08-30 19:54:52', '2026-08-30 19:54:52'),
(4, 33, 'BAT-2026-001', 50.00, NULL, '2026-08-30', NULL, 0.00, '2026-08-30 20:17:16', '2026-08-30 20:17:16');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_stock_in`
--

CREATE TABLE `inventory_stock_in` (
  `stock_in_id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `date_received` date NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_stock_in`
--

INSERT INTO `inventory_stock_in` (`stock_in_id`, `stock_id`, `quantity`, `date_received`, `reference_number`, `remarks`, `created_at`) VALUES
(1, 1, 30.00, '2026-08-20', 'DR-2026-001', '', '2026-08-21 03:08:37'),
(2, 2, 100.00, '2026-08-28', '', '', '2026-08-28 21:13:31'),
(3, 3, 50.00, '2026-08-30', '', '', '2026-08-30 19:54:52'),
(4, 4, 50.00, '2026-08-30', '', '', '2026-08-30 20:17:16');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_stock_out`
--

CREATE TABLE `inventory_stock_out` (
  `stock_out_id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `stock_out_date` date NOT NULL,
  `reason` varchar(100) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_stock_out`
--

INSERT INTO `inventory_stock_out` (`stock_out_id`, `stock_id`, `quantity`, `stock_out_date`, `reason`, `reference_number`, `remarks`, `created_at`) VALUES
(1, 1, 5.00, '2026-08-20', 'Used for Treatment/Procedure', NULL, NULL, '2026-08-21 03:29:00'),
(2, 1, 20.00, '2026-08-20', 'Used for Treatment/Procedure', NULL, NULL, '2026-08-21 03:38:59'),
(3, 2, 5.00, '2026-08-28', 'Used for Treatment/Procedure', NULL, NULL, '2026-08-28 21:18:48');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_subcategories`
--

CREATE TABLE `inventory_subcategories` (
  `subcategory_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `subcategory_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_subcategories`
--

INSERT INTO `inventory_subcategories` (`subcategory_id`, `category_id`, `subcategory_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Antibiotic', 'Medicines used to treat bacterial infections.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(2, 1, 'Dewormer / Antiparasitic', 'Medicines used to control internal parasites and worms.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(3, 1, 'Parasite Control', 'Medicines used for flea, tick and other parasite control.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(4, 1, 'Pain / Anti-inflammatory', 'Medicines used for pain relief and inflammation.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(5, 1, 'Allergy / Dermatology', 'Medicines used for allergies and skin-related conditions.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(6, 1, 'Cardiovascular', 'Medicines used for heart and cardiovascular conditions.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(7, 1, 'Other Medicine', 'Other medicines that do not belong to the standard medicine categories.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(8, 2, 'Vitamins', 'Vitamin supplements for pets.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(9, 2, 'Immune Support', 'Supplements intended to support the immune system.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(10, 2, 'Nutritional Support', 'Supplements used to provide additional nutritional support.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(11, 2, 'Digestive Support', 'Supplements used to support healthy digestion.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(12, 2, 'Urinary Support', 'Supplements used to support urinary health.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(13, 2, 'Milk / Lactation Support', 'Supplements intended for milk production and lactation support.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(14, 2, 'Other Supplement', 'Other supplements that do not belong to the standard supplement categories.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(15, 3, 'Dog Food', 'Food products intended for dogs.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(16, 3, 'Cat Food', 'Food products intended for cats.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(17, 3, 'Other Pet Food', 'Other pet food products.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(18, 6, 'Surgical / Suture Supplies', 'Supplies used for surgical and suturing procedures.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(19, 6, 'Catheter & IV Supplies', 'Catheters, IV sets and related supplies.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(20, 6, 'Syringes & Needles', 'Syringes, needles and related injection supplies.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(21, 6, 'Blood Collection Supplies', 'Supplies used for blood collection and handling.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(22, 6, 'Dressing & Protective Supplies', 'Bandages, dressings and protective clinical supplies.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(23, 6, 'Packaging Supplies', 'Packaging and containment supplies used by the clinic.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23'),
(24, 6, 'Other Clinic Supplies', 'Other clinic supplies that do not belong to the standard supply categories.', 'Active', '2026-08-19 04:49:23', '2026-08-19 04:49:23');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_suppliers`
--

CREATE TABLE `inventory_suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_units`
--

CREATE TABLE `inventory_units` (
  `unit_id` int(11) NOT NULL,
  `unit_name` varchar(50) NOT NULL,
  `abbreviation` varchar(20) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_units`
--

INSERT INTO `inventory_units` (`unit_id`, `unit_name`, `abbreviation`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Piece', 'pc', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(2, 'Box', 'box', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(3, 'Pack', 'pack', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(4, 'Bottle', 'btl', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(5, 'Tube', 'tube', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(6, 'Vial', 'vial', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(7, 'Bag', 'bag', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(8, 'Pouch', 'pouch', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(9, 'Can', 'can', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(10, 'Sachet', 'sachet', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(11, 'Tablet', 'tab', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(12, 'Capsule', 'cap', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(13, 'Milliliter', 'mL', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(14, 'Liter', 'L', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(15, 'Gram', 'g', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36'),
(16, 'Kilogram', 'kg', 'Active', '2026-08-18 22:18:36', '2026-08-18 22:18:36');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `medical_record_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `billing_id` int(11) DEFAULT NULL,
  `record_date` date NOT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `vaccination` text DEFAULT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `next_visit` date DEFAULT NULL,
  `no_days_return` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`medical_record_id`, `pet_id`, `appointment_id`, `billing_id`, `record_date`, `weight`, `temperature`, `diagnosis`, `treatment`, `vaccination`, `amount_paid`, `next_visit`, `no_days_return`, `created_at`, `updated_at`) VALUES
(1, 9, NULL, NULL, '2026-08-17', 3.00, 35.8, NULL, NULL, NULL, 0.00, '2026-09-16', 30, '2026-08-17 06:57:14', NULL),
(3, 2, 14, NULL, '2026-08-17', 5.00, 36.7, NULL, NULL, 'QuadCat', 0.00, '2026-08-31', 14, '2026-08-17 20:50:40', NULL),
(4, 6, 7, NULL, '2026-08-17', 10.00, 35.6, NULL, NULL, '9 in 1', 0.00, '2026-08-31', 14, '2026-08-17 21:02:36', NULL),
(5, 3, 3, 2, '2026-08-17', 6.00, 35.7, NULL, NULL, 'QuadCat', 0.00, '2026-08-31', 14, '2026-08-17 21:29:45', '2026-08-17 22:59:35'),
(6, 8, 24, 6, '2026-08-18', 5.00, 36.5, NULL, NULL, NULL, 300.00, '2026-11-17', 91, '2026-08-17 23:18:57', '2026-08-18 00:12:16'),
(7, 12, 25, 7, '2026-08-18', 3.00, 35.7, NULL, NULL, 'Anti-Rabies', 350.00, '2026-11-17', 91, '2026-08-18 01:34:52', '2026-08-18 01:39:05'),
(8, 13, 26, 8, '2026-08-22', 2.50, 35.7, NULL, NULL, 'QuadCat', 1500.00, '2026-09-04', 13, '2026-08-22 01:59:46', '2026-08-22 02:00:24'),
(9, 14, 27, 9, '2026-08-28', 2.50, 34.6, NULL, NULL, NULL, 300.00, '2026-09-11', 14, '2026-08-28 21:54:14', '2026-08-28 22:00:15'),
(10, 9, NULL, NULL, '2026-08-29', 3.00, 34.6, NULL, NULL, NULL, 0.00, '2026-08-30', 1, '2026-08-29 02:15:45', NULL),
(11, 15, 28, 10, '2026-08-29', 3.00, 35.7, NULL, NULL, 'Anti-Rabies', 0.00, '2026-10-28', 60, '2026-08-29 03:17:17', '2026-08-30 21:13:16'),
(12, 16, 30, 11, '2026-08-30', 4.70, 38.5, NULL, NULL, '9 in 1', 650.00, '2026-09-16', 17, '2026-08-30 21:40:47', '2026-08-31 22:51:05'),
(13, 17, 31, 12, '2026-08-31', 7.00, 33.6, NULL, NULL, NULL, 350.00, '2026-09-05', 5, '2026-08-31 19:26:32', '2026-08-31 20:34:20'),
(14, 18, 32, 13, '2026-08-31', 5.00, 35.6, NULL, NULL, NULL, 2000.00, '2026-09-07', 7, '2026-08-31 19:45:14', '2026-08-31 19:45:52'),
(15, 19, 33, 14, '2026-09-01', 5.00, 35.7, NULL, NULL, 'Anti-Rabies', 650.00, '2026-12-30', 120, '2026-08-31 23:10:22', '2026-08-31 23:13:22');

-- --------------------------------------------------------

--
-- Table structure for table `medical_record_services`
--

CREATE TABLE `medical_record_services` (
  `medical_record_service_id` int(11) NOT NULL,
  `medical_record_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `service_category` varchar(100) NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `pet_weight` decimal(10,2) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `service_source` enum('Appointment','Additional') NOT NULL DEFAULT 'Additional',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_record_services`
--

INSERT INTO `medical_record_services` (`medical_record_service_id`, `medical_record_id`, `service_id`, `service_category`, `service_name`, `quantity`, `pet_weight`, `unit_price`, `amount`, `service_source`, `created_at`) VALUES
(1, 3, NULL, 'vaccination', 'QuadCat', 1.00, 5.00, 0.00, 0.00, 'Appointment', '2026-08-17 20:50:40'),
(2, 4, NULL, 'vaccination', '9 in 1', 1.00, 10.00, 0.00, 0.00, 'Appointment', '2026-08-17 21:02:36'),
(3, 5, NULL, 'vaccination', 'QuadCat', 1.00, 6.00, 0.00, 0.00, 'Appointment', '2026-08-17 21:29:45'),
(4, 5, 6, '', 'Anti-Rabies', 1.00, 6.00, 350.00, 350.00, 'Additional', '2026-08-17 21:29:45'),
(5, 6, NULL, 'Deworming', 'Paste', 1.00, 5.00, 0.00, 0.00, 'Appointment', '2026-08-17 23:18:57'),
(6, 7, NULL, 'Vaccination', 'Anti-Rabies', 1.00, 3.00, 0.00, 0.00, 'Appointment', '2026-08-18 01:34:52'),
(7, 8, NULL, 'Vaccination', 'QuadCat', 1.00, 2.50, 0.00, 0.00, 'Appointment', '2026-08-22 01:59:46'),
(8, 9, NULL, 'Deworming', 'Paste', 1.00, 2.50, 0.00, 0.00, 'Appointment', '2026-08-28 21:54:14'),
(9, 11, NULL, 'Vaccination', 'Anti-Rabies', 1.00, 3.00, 0.00, 0.00, 'Appointment', '2026-08-29 03:17:17'),
(10, 12, NULL, 'Vaccination', '9 in 1', 1.00, 4.70, 0.00, 0.00, 'Appointment', '2026-08-30 21:40:47'),
(11, 13, NULL, 'Deworming', 'Paste', 1.00, 7.00, 0.00, 0.00, 'Appointment', '2026-08-31 19:26:32'),
(12, 14, NULL, 'Laboratory', 'Ultrasound', 1.00, 5.00, 0.00, 0.00, 'Appointment', '2026-08-31 19:45:14'),
(13, 15, NULL, 'Vaccination', 'Anti-Rabies', 1.00, 5.00, 0.00, 0.00, 'Appointment', '2026-08-31 23:10:22');

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

CREATE TABLE `medications` (
  `medication_id` int(11) NOT NULL,
  `medication_name` varchar(150) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medications`
--

INSERT INTO `medications` (`medication_id`, `medication_name`, `unit_price`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Cephalexin', 375.00, 'Active', '2026-08-12 01:08:33', '2026-08-12 01:32:27'),
(2, 'Doxycycline', 229.00, 'Active', '2026-08-12 01:20:42', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `verification_code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `pet_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `pet_name` varchar(100) NOT NULL,
  `species` varchar(30) NOT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `estimated_age` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`pet_id`, `customer_id`, `pet_name`, `species`, `breed`, `color`, `gender`, `weight`, `estimated_age`, `created_at`, `updated_at`) VALUES
(1, 1, 'yh', 'dog', 'Bulldog', 'Select Color', 'Female', 7.00, '9', '2026-08-08 21:50:54', NULL),
(2, 2, 'Cheesecake', 'cat', 'Persian', 'Select Color', 'Female', 4.00, '2', '2026-08-09 01:48:24', NULL),
(3, 3, 'Chandler', 'cat', 'Persian', 'Select Color', 'Male', 6.00, '2', '2026-08-09 01:58:51', NULL),
(4, 4, 'Pampi', 'dog', 'Shih Tzu', 'Select Color', 'Female', 5.00, '3', '2026-08-09 02:21:25', NULL),
(5, 5, 'Bunbun', 'cat', 'Puspin', 'Orange and White', 'Female', 3.00, '2', '2026-08-09 19:20:10', NULL),
(6, 6, 'Tamtam', 'dog', 'Beagle', 'Brown', 'Male', 5.00, '3', '2026-08-09 19:50:52', NULL),
(7, 7, 'Lala', 'cat', 'British Shorthair', 'Grey', 'Male', 5.00, '1', '2026-08-09 22:54:36', NULL),
(8, 8, 'Donut', 'cat', 'Persian', 'Gray and White', 'Male', 3.00, '1year and 7months', '2026-08-09 22:58:37', NULL),
(9, 2, 'Burikat', 'cat', 'Persian', 'Orange', 'Female', 2.00, '1y', '2026-08-10 01:52:14', NULL),
(10, 9, 'Betty', 'dog', 'Shih Tzu', 'White and brown', 'Female', 6.00, '2y and 2m', '2026-08-10 04:49:03', NULL),
(11, 10, 'Milo', 'cat', 'Siamese', 'Gray', 'Male', 4.00, '2', '2026-08-12 08:39:17', '2026-08-13 08:38:02'),
(12, 2, 'Kulowi', 'cat', 'Puspin', 'Black and white', 'Female', 4.00, '3', '2026-08-18 01:30:33', NULL),
(13, 11, 'Pomni', 'cat', 'Puspin', 'Orange', 'Female', 0.00, '3', '2026-08-22 01:58:47', NULL),
(14, 12, 'Poknat', 'cat', 'Puspin', 'Tilapia', 'Male', 0.00, '1y', '2026-08-28 21:52:53', NULL),
(15, 13, 'Lala', 'cat', 'Persian', 'Gray', 'Male', 0.00, '1y and 5m', '2026-08-29 00:45:39', NULL),
(16, 14, 'Luna', 'dog', 'Shih Tzu', 'Brown', 'Female', 0.00, '2', '2026-08-30 21:39:17', NULL),
(17, 15, 'cardo', 'cat', 'Ragdoll', 'Gray and White', 'Male', 7.00, '3yrs old', '2026-08-31 19:18:26', NULL),
(18, 16, 'bonnie', 'dog', 'Golden Retriever', 'Brown', 'Male', 6.00, '3', '2026-08-31 19:37:22', NULL),
(19, 17, 'Yumi', 'dog', 'German Shepherd', 'Black', 'Female', 5.00, '4', '2026-08-31 23:01:20', '2026-08-31 23:12:39');

-- --------------------------------------------------------

--
-- Table structure for table `pet_breeds`
--

CREATE TABLE `pet_breeds` (
  `breed_id` int(11) NOT NULL,
  `species_id` int(11) NOT NULL,
  `breed` varchar(100) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pet_breeds`
--

INSERT INTO `pet_breeds` (`breed_id`, `species_id`, `breed`, `status`, `created_at`, `updated_at`) VALUES
(58, 1, 'Labrador Retriever', 'Active', '2026-08-14 04:21:12', NULL),
(59, 1, 'Golden Retriever', 'Active', '2026-08-14 04:21:12', NULL),
(60, 1, 'German Shepherd', 'Active', '2026-08-14 04:21:12', NULL),
(61, 1, 'Shih Tzu', 'Active', '2026-08-14 04:21:12', NULL),
(62, 1, 'Pomeranian', 'Active', '2026-08-14 04:21:12', NULL),
(63, 1, 'Poodle', 'Active', '2026-08-14 04:21:12', NULL),
(64, 1, 'Beagle', 'Active', '2026-08-14 04:21:12', NULL),
(65, 1, 'Bulldog', 'Active', '2026-08-14 04:21:12', NULL),
(66, 1, 'Chihuahua', 'Active', '2026-08-14 04:21:12', NULL),
(67, 1, 'Siberian Husky', 'Active', '2026-08-14 04:21:12', NULL),
(68, 2, 'Persian', 'Active', '2026-08-14 04:21:12', NULL),
(69, 2, 'Siamese', 'Active', '2026-08-14 04:21:12', NULL),
(70, 2, 'Maine Coon', 'Active', '2026-08-14 04:21:12', NULL),
(71, 2, 'Ragdoll', 'Active', '2026-08-14 04:21:12', NULL),
(72, 2, 'British Shorthair', 'Active', '2026-08-14 04:21:12', NULL),
(73, 2, 'Bengal', 'Active', '2026-08-14 04:21:12', NULL),
(74, 2, 'Scottish Fold', 'Active', '2026-08-14 04:21:12', NULL),
(75, 2, 'American Shorthair', 'Active', '2026-08-14 04:21:12', NULL),
(76, 2, 'Sphynx', 'Active', '2026-08-14 04:21:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pet_species`
--

CREATE TABLE `pet_species` (
  `species_id` int(11) NOT NULL,
  `species` varchar(30) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pet_species`
--

INSERT INTO `pet_species` (`species_id`, `species`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Dog', 'Active', '2026-08-14 04:19:35', NULL),
(2, 'Cat', 'Active', '2026-08-14 04:19:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pricing_rules`
--

CREATE TABLE `pricing_rules` (
  `pricing_rule_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `base_min_weight` decimal(5,2) NOT NULL DEFAULT 0.00,
  `base_max_weight` decimal(5,2) NOT NULL DEFAULT 5.00,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `weight_increment` decimal(5,2) NOT NULL DEFAULT 5.00,
  `price_increment` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pricing_rules`
--

INSERT INTO `pricing_rules` (`pricing_rule_id`, `service_id`, `base_min_weight`, `base_max_weight`, `base_price`, `weight_increment`, `price_increment`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 0.00, 5.00, 600.00, 5.00, 100.00, 'Active', '2026-08-11 21:18:02', NULL),
(2, 9, 0.00, 5.00, 300.00, 5.00, 50.00, 'Active', '2026-08-11 21:18:02', NULL),
(3, 10, 0.00, 5.00, 300.00, 5.00, 50.00, 'Active', '2026-08-11 21:18:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `pricing_type` enum('Fixed','Weight-Based','Manual / Variable') NOT NULL DEFAULT 'Manual / Variable',
  `fixed_price` decimal(10,2) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `category_id`, `service_name`, `pricing_type`, `fixed_price`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Consultation', 'Fixed', 400.00, 'Active', '2026-08-11 21:01:35', NULL),
(2, 1, 'Medication', 'Manual / Variable', NULL, 'Active', '2026-08-11 21:01:35', NULL),
(3, 1, 'Treatment', 'Manual / Variable', NULL, 'Active', '2026-08-11 21:01:35', NULL),
(4, 1, 'Confinement / Boarding', 'Weight-Based', NULL, 'Active', '2026-08-11 21:01:35', NULL),
(5, 2, '9 in 1', 'Fixed', 650.00, 'Active', '2026-08-11 21:01:35', NULL),
(6, 2, 'Anti-Rabies', 'Fixed', 350.00, 'Active', '2026-08-11 21:01:35', NULL),
(7, 2, 'Kennel Cough', 'Fixed', 700.00, 'Active', '2026-08-11 21:01:35', NULL),
(8, 2, 'QuadCat', 'Fixed', 1100.00, 'Active', '2026-08-11 21:01:35', NULL),
(9, 3, 'Tablet', 'Weight-Based', NULL, 'Active', '2026-08-11 21:01:35', NULL),
(10, 3, 'Paste', 'Weight-Based', NULL, 'Active', '2026-08-11 21:01:35', '2026-08-13 21:07:17'),
(11, 4, 'Blood Chemistry', 'Fixed', 2500.00, 'Active', '2026-08-11 21:01:35', NULL),
(12, 4, 'Smear Test', 'Fixed', 560.00, 'Active', '2026-08-11 21:01:35', NULL),
(13, 4, 'Ultrasound', 'Fixed', 2000.00, 'Active', '2026-08-11 21:01:35', NULL),
(14, 4, 'Test Kit', 'Manual / Variable', NULL, 'Active', '2026-08-11 21:01:35', NULL),
(15, 5, 'Surgical Procedures', 'Manual / Variable', NULL, 'Active', '2026-08-11 21:01:35', NULL),
(16, 5, 'Dental Hygiene', 'Manual / Variable', NULL, 'Active', '2026-08-11 21:01:35', NULL),
(17, 5, 'Skin Disease Treatment', 'Manual / Variable', NULL, 'Active', '2026-08-11 21:01:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `service_categories`
--

CREATE TABLE `service_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_categories`
--

INSERT INTO `service_categories` (`category_id`, `category_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Particulars', 'General veterinary services and procedures.', 'Active', '2026-08-11 20:37:33', NULL),
(2, 'Vaccination', 'Vaccination services for pets.', 'Active', '2026-08-11 20:37:33', NULL),
(3, 'Deworming', 'Deworming services and treatments.', 'Active', '2026-08-11 20:37:33', NULL),
(4, 'Laboratory', 'Laboratory examinations and diagnostic tests.', 'Active', '2026-08-11 20:37:33', NULL),
(5, 'Specialties', 'Specialized veterinary procedures and services.', 'Active', '2026-08-11 20:37:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `test_kits`
--

CREATE TABLE `test_kits` (
  `test_kit_id` int(11) NOT NULL,
  `test_kit_name` varchar(150) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_kits`
--

INSERT INTO `test_kits` (`test_kit_id`, `test_kit_name`, `unit_price`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Canine Parvo Test Kit', 1500.00, 'Active', '2026-08-11 21:08:38', NULL),
(2, 'Canine Distemper Test Kit', 950.00, 'Active', '2026-08-11 21:08:38', NULL),
(3, 'FIV/FeLV Test Kit', 1100.00, 'Active', '2026-08-11 21:08:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_certificates`
--

CREATE TABLE `vaccination_certificates` (
  `certificate_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `qr_token` varchar(100) NOT NULL,
  `status` enum('Generated','Not Generated') NOT NULL DEFAULT 'Not Generated',
  `generated_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccination_certificates`
--

INSERT INTO `vaccination_certificates` (`certificate_id`, `pet_id`, `qr_token`, `status`, `generated_at`, `updated_at`) VALUES
(1, 10, '8c5405e52534fafdabdf07e2e16ef0f73fbf8d182e7819e251dc9e5e592d81ee', 'Generated', '2026-08-21 18:45:44', '2026-08-22 01:45:44'),
(2, 9, '29e5ba175ac135208efe4a016bab23ce0dc673b2e62bdb7b0592d7d28c981c10', 'Generated', '2026-08-30 13:02:41', '2026-08-30 20:02:41'),
(3, 18, '8f7f33cc4038ba7bedb64a41ecedee05eabd674f52e668f4c816c90786caa10c', 'Generated', '2026-08-31 12:43:47', '2026-08-31 19:43:47'),
(4, 3, 'ed02ab6ec3fbf1016606f9a80506f709bbbd4fee5f8d876d90443bd6821fba12', 'Generated', '2026-08-31 13:46:48', '2026-08-31 20:46:48'),
(5, 2, '26378180bb60dcaeff434a44dd24cf9e6ebdac300823a090239d9bb23671ba3e', 'Generated', '2026-08-31 13:46:57', '2026-08-31 20:46:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `fk_appointment_customer` (`customer_id`),
  ADD KEY `fk_appointment_pet` (`pet_id`);

--
-- Indexes for table `appointments_old`
--
ALTER TABLE `appointments_old`
  ADD PRIMARY KEY (`appointment_id`);

--
-- Indexes for table `appointment_schedule`
--
ALTER TABLE `appointment_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `unique_day` (`day_of_week`);

--
-- Indexes for table `appointment_statuses`
--
ALTER TABLE `appointment_statuses`
  ADD PRIMARY KEY (`appointment_status_id`),
  ADD UNIQUE KEY `unique_appointment_status` (`appointment_status`);

--
-- Indexes for table `appointment_types`
--
ALTER TABLE `appointment_types`
  ADD PRIMARY KEY (`appointment_type_id`),
  ADD UNIQUE KEY `unique_appointment_type` (`appointment_type`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`billing_id`),
  ADD UNIQUE KEY `unique_appointment_billing` (`appointment_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_pet_id` (`pet_id`);

--
-- Indexes for table `billing_items`
--
ALTER TABLE `billing_items`
  ADD PRIMARY KEY (`billing_item_id`),
  ADD KEY `idx_billing_id` (`billing_id`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `customer_archive_checks`
--
ALTER TABLE `customer_archive_checks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_check_month` (`check_month`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `uq_inventory_category_name` (`category_name`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `uq_inventory_item_code` (`item_code`),
  ADD KEY `fk_inventory_item_category` (`category_id`),
  ADD KEY `fk_inventory_item_unit` (`unit_id`),
  ADD KEY `fk_inventory_item_supplier` (`supplier_id`);

--
-- Indexes for table `inventory_stock`
--
ALTER TABLE `inventory_stock`
  ADD PRIMARY KEY (`stock_id`),
  ADD UNIQUE KEY `uq_item_batch` (`item_id`,`batch_number`),
  ADD KEY `fk_inventory_stock_supplier` (`supplier_id`);

--
-- Indexes for table `inventory_stock_in`
--
ALTER TABLE `inventory_stock_in`
  ADD PRIMARY KEY (`stock_in_id`),
  ADD KEY `fk_stock_in_stock` (`stock_id`);

--
-- Indexes for table `inventory_stock_out`
--
ALTER TABLE `inventory_stock_out`
  ADD PRIMARY KEY (`stock_out_id`),
  ADD KEY `fk_stock_out_stock` (`stock_id`);

--
-- Indexes for table `inventory_subcategories`
--
ALTER TABLE `inventory_subcategories`
  ADD PRIMARY KEY (`subcategory_id`),
  ADD UNIQUE KEY `uq_inventory_subcategory` (`category_id`,`subcategory_name`),
  ADD KEY `idx_inventory_subcategory_category` (`category_id`);

--
-- Indexes for table `inventory_suppliers`
--
ALTER TABLE `inventory_suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD UNIQUE KEY `uq_inventory_supplier_name` (`supplier_name`);

--
-- Indexes for table `inventory_units`
--
ALTER TABLE `inventory_units`
  ADD PRIMARY KEY (`unit_id`),
  ADD UNIQUE KEY `uq_inventory_unit_name` (`unit_name`),
  ADD UNIQUE KEY `uq_inventory_unit_abbreviation` (`abbreviation`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`medical_record_id`),
  ADD KEY `idx_medical_records_pet` (`pet_id`),
  ADD KEY `idx_medical_records_appointment` (`appointment_id`),
  ADD KEY `idx_medical_records_billing` (`billing_id`);

--
-- Indexes for table `medical_record_services`
--
ALTER TABLE `medical_record_services`
  ADD PRIMARY KEY (`medical_record_service_id`),
  ADD KEY `idx_medical_record_id` (`medical_record_id`),
  ADD KEY `idx_service_id` (`service_id`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`medication_id`),
  ADD UNIQUE KEY `unique_medication_name` (`medication_name`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`pet_id`),
  ADD KEY `fk_pet_customer` (`customer_id`);

--
-- Indexes for table `pet_breeds`
--
ALTER TABLE `pet_breeds`
  ADD PRIMARY KEY (`breed_id`),
  ADD UNIQUE KEY `unique_breed_species` (`species_id`,`breed`);

--
-- Indexes for table `pet_species`
--
ALTER TABLE `pet_species`
  ADD PRIMARY KEY (`species_id`),
  ADD UNIQUE KEY `unique_species` (`species`);

--
-- Indexes for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  ADD PRIMARY KEY (`pricing_rule_id`),
  ADD UNIQUE KEY `unique_service_pricing_rule` (`service_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD UNIQUE KEY `unique_service_per_category` (`category_id`,`service_name`);

--
-- Indexes for table `service_categories`
--
ALTER TABLE `service_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `unique_category_name` (`category_name`);

--
-- Indexes for table `test_kits`
--
ALTER TABLE `test_kits`
  ADD PRIMARY KEY (`test_kit_id`),
  ADD UNIQUE KEY `unique_test_kit_name` (`test_kit_name`);

--
-- Indexes for table `vaccination_certificates`
--
ALTER TABLE `vaccination_certificates`
  ADD PRIMARY KEY (`certificate_id`),
  ADD UNIQUE KEY `uq_vaccination_pet` (`pet_id`),
  ADD UNIQUE KEY `uq_vaccination_token` (`qr_token`),
  ADD KEY `idx_vaccination_pet` (`pet_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `appointments_old`
--
ALTER TABLE `appointments_old`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `appointment_schedule`
--
ALTER TABLE `appointment_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `appointment_statuses`
--
ALTER TABLE `appointment_statuses`
  MODIFY `appointment_status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `appointment_types`
--
ALTER TABLE `appointment_types`
  MODIFY `appointment_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `billing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `billing_items`
--
ALTER TABLE `billing_items`
  MODIFY `billing_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `customer_archive_checks`
--
ALTER TABLE `customer_archive_checks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `inventory_stock`
--
ALTER TABLE `inventory_stock`
  MODIFY `stock_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_stock_in`
--
ALTER TABLE `inventory_stock_in`
  MODIFY `stock_in_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_stock_out`
--
ALTER TABLE `inventory_stock_out`
  MODIFY `stock_out_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory_subcategories`
--
ALTER TABLE `inventory_subcategories`
  MODIFY `subcategory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `inventory_suppliers`
--
ALTER TABLE `inventory_suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_units`
--
ALTER TABLE `inventory_units`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `medical_record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `medical_record_services`
--
ALTER TABLE `medical_record_services`
  MODIFY `medical_record_service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `medication_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `pet_breeds`
--
ALTER TABLE `pet_breeds`
  MODIFY `breed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `pet_species`
--
ALTER TABLE `pet_species`
  MODIFY `species_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  MODIFY `pricing_rule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `service_categories`
--
ALTER TABLE `service_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `test_kits`
--
ALTER TABLE `test_kits`
  MODIFY `test_kit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vaccination_certificates`
--
ALTER TABLE `vaccination_certificates`
  MODIFY `certificate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointment_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointment_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE;

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `fk_billing_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_billing_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_billing_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON UPDATE CASCADE;

--
-- Constraints for table `billing_items`
--
ALTER TABLE `billing_items`
  ADD CONSTRAINT `fk_billing_items_billing` FOREIGN KEY (`billing_id`) REFERENCES `billing` (`billing_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `fk_inventory_item_category` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`category_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inventory_item_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `inventory_suppliers` (`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inventory_item_unit` FOREIGN KEY (`unit_id`) REFERENCES `inventory_units` (`unit_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `inventory_stock`
--
ALTER TABLE `inventory_stock`
  ADD CONSTRAINT `fk_inventory_stock_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inventory_stock_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `inventory_suppliers` (`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `inventory_stock_in`
--
ALTER TABLE `inventory_stock_in`
  ADD CONSTRAINT `fk_stock_in_stock` FOREIGN KEY (`stock_id`) REFERENCES `inventory_stock` (`stock_id`) ON UPDATE CASCADE;

--
-- Constraints for table `inventory_stock_out`
--
ALTER TABLE `inventory_stock_out`
  ADD CONSTRAINT `fk_stock_out_stock` FOREIGN KEY (`stock_id`) REFERENCES `inventory_stock` (`stock_id`) ON UPDATE CASCADE;

--
-- Constraints for table `inventory_subcategories`
--
ALTER TABLE `inventory_subcategories`
  ADD CONSTRAINT `fk_inventory_subcategory_category` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `fk_medical_records_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_medical_records_billing` FOREIGN KEY (`billing_id`) REFERENCES `billing` (`billing_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_medical_records_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON UPDATE CASCADE;

--
-- Constraints for table `medical_record_services`
--
ALTER TABLE `medical_record_services`
  ADD CONSTRAINT `fk_mrs_medical_record` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`medical_record_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mrs_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `fk_pet_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `pet_breeds`
--
ALTER TABLE `pet_breeds`
  ADD CONSTRAINT `fk_pet_breeds_species` FOREIGN KEY (`species_id`) REFERENCES `pet_species` (`species_id`) ON UPDATE CASCADE;

--
-- Constraints for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  ADD CONSTRAINT `fk_pricing_rules_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON UPDATE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`category_id`) ON UPDATE CASCADE;

--
-- Constraints for table `vaccination_certificates`
--
ALTER TABLE `vaccination_certificates`
  ADD CONSTRAINT `fk_vaccination_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
