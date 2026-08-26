-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
-- Added ALTER TABLE to update schema before insertion

ALTER TABLE `admin_users`
ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(150) NULL AFTER `password`,
ADD COLUMN IF NOT EXISTS `nickname` VARCHAR(100) NULL AFTER `full_name`,
ADD COLUMN IF NOT EXISTS `designation` VARCHAR(150) NULL AFTER `nickname`,
ADD COLUMN IF NOT EXISTS `office` VARCHAR(100) NULL AFTER `designation`,
ADD COLUMN IF NOT EXISTS `email` VARCHAR(100) NULL AFTER `office`,
ADD COLUMN IF NOT EXISTS `contact_number` VARCHAR(50) NULL AFTER `email`,
ADD COLUMN IF NOT EXISTS `role` ENUM('admin','superadmin') NOT NULL DEFAULT 'admin' AFTER `contact_number`;

--
-- Host: localhost    Database: qpteo_portal
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `admin_users`
--
-- WHERE:  username='qpteo'

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT IGNORE INTO `admin_users` (`id`, `username`, `password`, `full_name`, `nickname`, `designation`, `office`, `email`, `contact_number`, `role`, `created_at`, `updated_at`) VALUES (13,'qpteo','$2y$10$U2FJVOZKJYRYFmRXrJ3gp.9JjO2RpeWqh5ixO/AZdJFpRdlKwa4ni','QPTEO Superadmin',NULL,NULL,NULL,NULL,NULL,'superadmin','2026-08-26 22:30:26','2026-08-26 22:30:26');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-26 22:30:42
