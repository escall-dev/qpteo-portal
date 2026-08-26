ALTER TABLE `admin_users`
ADD COLUMN `full_name` VARCHAR(150) NULL AFTER `password`,
ADD COLUMN `nickname` VARCHAR(100) NULL AFTER `full_name`,
ADD COLUMN `designation` VARCHAR(150) NULL AFTER `nickname`,
ADD COLUMN `office` VARCHAR(100) NULL AFTER `designation`,
ADD COLUMN `email` VARCHAR(100) NULL AFTER `office`,
ADD COLUMN `contact_number` VARCHAR(50) NULL AFTER `email`,
ADD COLUMN `role` ENUM('admin','superadmin') NOT NULL DEFAULT 'admin' AFTER `contact_number`;
