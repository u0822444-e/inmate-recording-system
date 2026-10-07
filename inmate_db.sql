-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2026 at 07:28 AM
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
-- Database: `inmate_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `headcounts`
--

CREATE TABLE `headcounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `recorded_on` date NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expected_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `actual_count` int(10) UNSIGNED NOT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inmates`
--

CREATE TABLE `inmates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `inmate_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` enum('Male','Female','Other','Unspecified') NOT NULL DEFAULT 'Unspecified',
  `custody_status` enum('In Custody','Released','Transferred') NOT NULL DEFAULT 'In Custody',
  `admission_date` date NOT NULL,
  `case_reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `sex` enum('Male','Female') DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated') DEFAULT NULL,
  `employee_no` varchar(50) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `rank` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `date_hired` date DEFAULT NULL,
  `employment_status` enum('Regular','Probationary','Contractual','Casual','Job Order') DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Administrator','Staff / Officer') NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `birthdate`, `sex`, `civil_status`, `employee_no`, `position`, `rank`, `department`, `date_hired`, `employment_status`, `password`, `role`, `full_name`, `is_active`, `created_at`, `updated_at`, `archived_at`) VALUES
(1, 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$Jm8U62OvFMgVSDtTCS2cx.xb4oiQE9w5n124ena.It1rdz.g30Dou', 'Administrator', 'System Administrator', 1, '2026-10-01 03:20:10', '2026-10-07 01:47:16', NULL),
(3, 'staff01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$GivfRXIkq2RtZwQmUTdvCebiss0373A4Dbr/T9dS6aMbpGZkxVycq', 'Staff / Officer', 'Staff Officer', 1, '2026-10-01 03:42:51', '2026-10-07 02:13:34', NULL),
(4, 'reynantehulay', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$12$5.tKD0TQsW9GqtxLfhus/.ZScM1sXwoa786ekQCmdFFxARnn5mU8K', 'Staff / Officer', 'Reynante Hulay', 1, '2026-10-04 20:08:43', '2026-10-04 20:08:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `inmate_id` bigint(20) UNSIGNED NOT NULL,
  `visitor_name` varchar(150) NOT NULL,
  `visitor_relationship` varchar(100) DEFAULT NULL,
  `visitor_id_reference` varchar(100) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `checked_in_at` datetime NOT NULL DEFAULT current_timestamp(),
  `checked_out_at` datetime DEFAULT NULL,
  `status` enum('Checked In','Checked Out','Cancelled') NOT NULL DEFAULT 'Checked In',
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_created_at` (`created_at`),
  ADD KEY `idx_activity_logs_user_id` (`user_id`);

--
-- Indexes for table `headcounts`
--
ALTER TABLE `headcounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_headcounts_recorded_on` (`recorded_on`),
  ADD KEY `fk_headcounts_recorded_by` (`recorded_by`);

--
-- Indexes for table `inmates`
--
ALTER TABLE `inmates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_inmates_inmate_number` (`inmate_number`),
  ADD KEY `idx_inmates_name` (`last_name`,`first_name`),
  ADD KEY `idx_inmates_custody_status` (`custody_status`),
  ADD KEY `fk_inmates_created_by` (`created_by`),
  ADD KEY `fk_inmates_updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_active` (`is_active`),
  ADD KEY `idx_users_archived_at` (`archived_at`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visits_status` (`status`),
  ADD KEY `idx_visits_checked_in_at` (`checked_in_at`),
  ADD KEY `fk_visits_inmate` (`inmate_id`),
  ADD KEY `fk_visits_recorded_by` (`recorded_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `headcounts`
--
ALTER TABLE `headcounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inmates`
--
ALTER TABLE `inmates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `headcounts`
--
ALTER TABLE `headcounts`
  ADD CONSTRAINT `fk_headcounts_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inmates`
--
ALTER TABLE `inmates`
  ADD CONSTRAINT `fk_inmates_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inmates_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `fk_visits_inmate` FOREIGN KEY (`inmate_id`) REFERENCES `inmates` (`id`),
  ADD CONSTRAINT `fk_visits_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
