-- =============================================================
-- QPTEO Portal Database Schema
-- Database: qpteo_portal
-- =============================================================

CREATE DATABASE IF NOT EXISTS `qpteo_portal`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `qpteo_portal`;

-- -------------------------------------------------------------
-- Table: repositories
-- Stores all document repository entries across 8 categories.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `repositories` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title`          VARCHAR(500)   NOT NULL,
    `description`    TEXT           NULL,
    `category`       ENUM(
                        'presentations',
                        'concept_papers',
                        'checklists',
                        'briefers',
                        'reports',
                        'session_guides',
                        'others',
                        'accomplishment_reports',
                        'leave_forms',
                        'proposals',
                        'program_completion_reports',
                        'monitoring_evaluation',
                        'qpteo_office_meetings',
                        'execom_meetings',
                        'other_meetings',
                        'cmos',
                        'psgs',
                        'ppst',
                        'policies',
                        'guidelines',
                        'rite'
                     ) NOT NULL DEFAULT 'others',
    `document_type`  ENUM(
                        'presentations',
                        'concept_papers',
                        'checklists',
                        'briefers',
                        'reports',
                        'session_guides',
                        'others',
                        'accomplishment_reports',
                        'leave_forms',
                        'proposals',
                        'program_completion_reports',
                        'monitoring_evaluation',
                        'qpteo_office_meetings',
                        'execom_meetings',
                        'other_meetings',
                        'cmos',
                        'psgs',
                        'ppst',
                        'policies',
                        'guidelines',
                        'rite'
                     ) NOT NULL DEFAULT 'others',
    `file_type`      ENUM(
                        'slides',
                        'docs',
                        'sheets',
                        'folder',
                        'pdf',
                        'others'
                     ) NOT NULL DEFAULT 'others',
    `date_uploaded`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `file_path`      VARCHAR(1000)  NOT NULL,
    `uploaded_by`    VARCHAR(255)   NULL,
    `file_size`      BIGINT UNSIGNED NULL    COMMENT 'File size in bytes',
    `created_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_category`      (`category`),
    INDEX `idx_document_type` (`document_type`),
    INDEX `idx_file_type`     (`file_type`),
    INDEX `idx_date_uploaded` (`date_uploaded`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table: memorandums
-- Stores QPTEO Office Memorandums (Issuances section).
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `memorandums` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `memo_number` VARCHAR(100)  NOT NULL,
    `subject`     VARCHAR(1000) NOT NULL,
    `description` TEXT          NULL,
    `date_issued` DATE          NOT NULL,
    `file_path`   VARCHAR(1000) NOT NULL,
    `issued_by`   VARCHAR(255)  NULL,
    `file_size`   BIGINT UNSIGNED NULL  COMMENT 'File size in bytes',
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_date_issued`  (`date_issued`),
    INDEX `idx_memo_number`  (`memo_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table: centers_of_excellence
-- Stores Teacher Education Institutions designated as COEs.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `centers_of_excellence` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `institution_name` VARCHAR(500) NOT NULL,
    `region`           VARCHAR(100) NULL,
    `province`         VARCHAR(100) NULL,
    `address`          TEXT         NULL,
    `designation_date` DATE         NULL,
    `status`           ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `contact_info`     VARCHAR(500) NULL,
    `description`      TEXT         NULL,
    `logo_path`        VARCHAR(1000) NULL,
    `doc_link`         VARCHAR(1000) NULL,
    `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_region` (`region`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table: admin_users
-- Superadmin authentication for the admin panel.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(100) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL COMMENT 'bcrypt hashed',
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default superadmin user: admin / admin123
-- Password hash generated with PHP password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `admin_users` (`username`, `password`)
VALUES ('admin', '$2y$10$k2rUnSByr0bfdL.TbeuEIONJwdBnn1zaVdyjQQJB7cjGj2MYkPgj6')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- -------------------------------------------------------------
-- Table: settings
-- Stores key-value configuration settings for the portal.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('meeting_recordings_url', '#');
