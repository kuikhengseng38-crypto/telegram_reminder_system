-- Telegram Reminder Management System
-- MySQL 5.7+ / MariaDB 10.2+
-- Charset: utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE DATABASE IF NOT EXISTS `telegram_reminder`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `telegram_reminder`;

DROP TABLE IF EXISTS `message_logs`;
DROP TABLE IF EXISTS `reminder_recipients`;
DROP TABLE IF EXISTS `reminder_messages`;
DROP TABLE IF EXISTS `reminders`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `login_attempts`;

CREATE TABLE `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `reset_token` VARCHAR(64) DEFAULT NULL,
  `reset_expires` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`),
  UNIQUE KEY `uq_admins_email` (`email`),
  KEY `idx_admins_reset` (`reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `chat_id` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_chat_id` (`chat_id`),
  KEY `idx_users_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reminders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `scheduled_time` DATETIME NOT NULL,
  `status` ENUM('pending','sending','sent','failed','partial') NOT NULL DEFAULT 'pending',
  `started_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reminders_due` (`status`, `scheduled_time`),
  KEY `idx_reminders_title` (`title`),
  KEY `idx_reminders_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reminder_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reminder_id` INT UNSIGNED NOT NULL,
  `message_text` TEXT NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_rm_reminder` (`reminder_id`, `sort_order`),
  CONSTRAINT `fk_rm_reminder`
    FOREIGN KEY (`reminder_id`) REFERENCES `reminders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reminder_recipients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reminder_id` INT UNSIGNED NOT NULL,
  `chat_id` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rr_reminder_chat` (`reminder_id`, `chat_id`),
  KEY `idx_rr_chat` (`chat_id`),
  CONSTRAINT `fk_rr_reminder`
    FOREIGN KEY (`reminder_id`) REFERENCES `reminders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `message_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reminder_id` INT UNSIGNED NOT NULL,
  `chat_id` VARCHAR(50) NOT NULL,
  `message_text` TEXT NOT NULL,
  `status` ENUM('sent','failed') NOT NULL,
  `sent_time` DATETIME DEFAULT NULL,
  `error_message` VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ml_reminder` (`reminder_id`),
  KEY `idx_ml_chat` (`chat_id`),
  KEY `idx_ml_status` (`status`),
  KEY `idx_ml_sent_time` (`sent_time`),
  CONSTRAINT `fk_ml_reminder`
    FOREIGN KEY (`reminder_id`) REFERENCES `reminders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `username` VARCHAR(100) NOT NULL DEFAULT '',
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_la_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema only. Create the first admin with install.php, not with a seeded password hash.

SET FOREIGN_KEY_CHECKS = 1;
