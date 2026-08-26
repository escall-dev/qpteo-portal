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
-- WHERE:  role='admin'

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT IGNORE INTO `admin_users` (`id`, `username`, `password`, `full_name`, `nickname`, `designation`, `office`, `email`, `contact_number`, `role`, `created_at`, `updated_at`) VALUES (1,'test','$2y$10$2KWXThMXOOeXQaO5geIQ2.HeJ4aXu0BWFZiGE60aCZkh1CZCgI9G.','test account','test','test','QPTEO','test@gmail.com','test','admin','2026-08-07 22:56:48','2026-08-26 22:19:00'),(3,'ferdinand.rellorosa','$2y$10$275Yg5Jz6XxFOVEInJTNrecMJRrOcPMR.zYfaY.kxC2lXBSbt/VGu','Ferdinand L. Rellorosa, CEPS','Iking','Chief','Quality Pre-Service Teacher Education Office','ferdinand.rellorosa@deped.gov.ph','+63 917 123 4567','admin','2026-08-26 22:17:20','2026-08-26 22:17:20'),(4,'diane.francisco','$2y$10$275Yg5Jz6XxFOVEInJTNrecMJRrOcPMR.zYfaY.kxC2lXBSbt/VGu','Diane G. Francisco, PDO IV','Diane','Senior Quality Assurance Specialist','Quality Pre-Service Teacher Education Office','diane.francisco@deped.gov.ph','+63 918 234 5678','admin','2026-08-26 22:17:20','2026-08-26 22:17:20'),(5,'mariekristel.corpin','$2y$10$275Yg5Jz6XxFOVEInJTNrecMJRrOcPMR.zYfaY.kxC2lXBSbt/VGu','Marie Kristel B. Corpin, SEPS','Kristel','','Quality Pre-Service Teacher Education Office','mariekristel.corpin@deped.gov.ph','+63 919 345 6789','admin','2026-08-26 22:17:20','2026-08-26 22:17:20'),(6,'cristy.mendoza','$2y$10$275Yg5Jz6XxFOVEInJTNrecMJRrOcPMR.zYfaY.kxC2lXBSbt/VGu','Cristy A. Mendoza, PDO III','Cristy','Research & Development Specialist','Quality Pre-Service Teacher Education Office','cristy.mendoza@deped.gov.ph','+63 920 456 7890','admin','2026-08-26 22:17:20','2026-08-26 22:17:20'),(7,'vernie.lasmarias','$2y$10$275Yg5Jz6XxFOVEInJTNrecMJRrOcPMR.zYfaY.kxC2lXBSbt/VGu','Vernie Glojun T. Lasmarias, PDO III','VJ','Quality Assurance Specialist','QPTEO','vernie.lasmarias@deped.gov.ph','+63 921 567 8901','admin','2026-08-26 22:17:20','2026-08-26 22:17:20'),(8,'lesterdave.pua','$2y$10$275Yg5Jz6XxFOVEInJTNrecMJRrOcPMR.zYfaY.kxC2lXBSbt/VGu','Lester Dave G. Pua, PDO III','Dave','Curriculum & Instruction Specialist','Quality Pre-Service Teacher Education Office','lesterdave.pua@deped.gov.ph','+63 922 678 9012','admin','2026-08-26 22:17:20','2026-08-26 22:17:20'),(9,'christopher.siscar','$2y$10$275Yg5Jz6XxFOVEInJTNrecMJRrOcPMR.zYfaY.kxC2lXBSbt/VGu','Christopher E. Siscar, PDO I','Chris','Administrative & Operations Support','Quality Pre-Service Teacher Education Office','christopher.siscar@deped.gov.ph','+63 923 789 0123','admin','2026-08-26 22:17:20','2026-08-26 22:17:20'),(10,'clarence.villena','$2y$10$275Yg5Jz6XxFOVEInJTNrecMJRrOcPMR.zYfaY.kxC2lXBSbt/VGu','Clarence Jillian Villena, EPS I','Jillian','','Quality Pre-Service Teacher Education Office','clarence.villena@deped.gov.ph','+63 924 890 1234','admin','2026-08-26 22:17:20','2026-08-26 22:17:20'),(11,'venusmae.cabunalda','$2y$10$275Yg5Jz6XxFOVEInJTNrecMJRrOcPMR.zYfaY.kxC2lXBSbt/VGu','Venus Mae D. Cabu├▒alda, ADAS I','Venus','Secretary II / Executive Assistant','Quality Pre-Service Teacher Education Office','venusmae.cabunalda@deped.gov.ph','+63 925 901 2345','admin','2026-08-26 22:17:20','2026-08-26 22:17:20'),(12,'alexander.escallente','$2y$10$275Yg5Jz6XxFOVEInJTNrecMJRrOcPMR.zYfaY.kxC2lXBSbt/VGu','Alexander Joerenz E. Escallente','Alex','Technical & Executive Operations Lead','Quality Pre-Service Teacher Education Office','alexander.escallente@deped.gov.ph','+63 926 012 3456','admin','2026-08-26 22:17:20','2026-08-26 22:17:20');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-26 22:28:01
