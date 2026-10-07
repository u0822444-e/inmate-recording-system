-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: localhost    Database: inmate_db
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_created_at` (`created_at`),
  KEY `idx_activity_logs_user_id` (`user_id`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `headcounts`
--

DROP TABLE IF EXISTS `headcounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `headcounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recorded_on` date NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expected_count` int unsigned NOT NULL DEFAULT '0',
  `actual_count` int unsigned NOT NULL,
  `remarks` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_headcounts_recorded_on` (`recorded_on`),
  KEY `fk_headcounts_recorded_by` (`recorded_by`),
  CONSTRAINT `fk_headcounts_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `headcounts`
--

LOCK TABLES `headcounts` WRITE;
/*!40000 ALTER TABLE `headcounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `headcounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inmates`
--

DROP TABLE IF EXISTS `inmates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inmates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inmate_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suffix` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` enum('Male','Female','Other','Unspecified') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unspecified',
  `custody_status` enum('In Custody','Released','Transferred') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'In Custody',
  `admission_date` date NOT NULL,
  `case_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inmates_inmate_number` (`inmate_number`),
  KEY `idx_inmates_name` (`last_name`,`first_name`),
  KEY `idx_inmates_custody_status` (`custody_status`),
  KEY `fk_inmates_created_by` (`created_by`),
  KEY `fk_inmates_updated_by` (`updated_by`),
  CONSTRAINT `fk_inmates_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inmates_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inmates`
--

LOCK TABLES `inmates` WRITE;
/*!40000 ALTER TABLE `inmates` DISABLE KEYS */;
/*!40000 ALTER TABLE `inmates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('Administrator','Staff / Officer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$12$6yerSUbY0KzKB6/YdjL2W..a8RsyMyWzFtxNLzVTgO4G.XjrEJI.W','Administrator','System Administrator',1,'2026-10-01 03:20:10','2026-10-01 03:20:10'),(3,'staff01','$2y$12$nClc25jvxOguL1GEdHu.PeAMTDaUtJZpS87aG9Ztk6iOi14o9tZhm','Staff / Officer','Staff Officer',1,'2026-10-01 03:42:51','2026-10-01 03:42:51'),(4,'reynantehulay','$2y$12$5.tKD0TQsW9GqtxLfhus/.ZScM1sXwoa786ekQCmdFFxARnn5mU8K','Staff / Officer','Reynante Hulay',1,'2026-10-04 20:08:43','2026-10-04 20:08:52');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visits`
--

DROP TABLE IF EXISTS `visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inmate_id` bigint unsigned NOT NULL,
  `visitor_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visitor_relationship` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visitor_id_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checked_in_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `checked_out_at` datetime DEFAULT NULL,
  `status` enum('Checked In','Checked Out','Cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Checked In',
  `recorded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_visits_status` (`status`),
  KEY `idx_visits_checked_in_at` (`checked_in_at`),
  KEY `fk_visits_inmate` (`inmate_id`),
  KEY `fk_visits_recorded_by` (`recorded_by`),
  CONSTRAINT `fk_visits_inmate` FOREIGN KEY (`inmate_id`) REFERENCES `inmates` (`id`),
  CONSTRAINT `fk_visits_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visits`
--

LOCK TABLES `visits` WRITE;
/*!40000 ALTER TABLE `visits` DISABLE KEYS */;
/*!40000 ALTER TABLE `visits` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-05 11:03:17
