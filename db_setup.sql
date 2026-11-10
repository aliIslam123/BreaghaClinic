-- db_setup.sql
-- Safe setup script for Brèagha Clinic booking database and tables.
-- This script creates the database and tables only if they do not already exist.

CREATE DATABASE IF NOT EXISTS `breagha`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `breagha`;

CREATE TABLE IF NOT EXISTS `customer` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `Customer_age` INT(3) NOT NULL DEFAULT 0,
  `phone` VARCHAR(255) NOT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `service` VARCHAR(255) NOT NULL,
  `sessions` INT DEFAULT NULL,
  `offer_eligible` TINYINT(1) NOT NULL DEFAULT 0,
  `booking_date` DATE NOT NULL,
  `time_slot` VARCHAR(50) NOT NULL DEFAULT '',
  `gender` VARCHAR(255) NOT NULL DEFAULT 'غير محدد',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking_date` (`booking_date`),
  KEY `idx_time_slot` (`time_slot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `booking_id` VARCHAR(30) NOT NULL UNIQUE,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `age` INT(3) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `service` VARCHAR(255) NOT NULL,
  `sessions` VARCHAR(50) DEFAULT NULL COMMENT 'Only for Laser department',
  `booking_date` DATE NOT NULL,
  `booking_time` VARCHAR(50) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_id` (`booking_id`),
  KEY `idx_date_time` (`booking_date`, `booking_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional schema fixes for legacy customer table
ALTER TABLE `customer`
  ADD COLUMN IF NOT EXISTS `department` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `sessions` INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `offer_eligible` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `gender` VARCHAR(255) NOT NULL DEFAULT 'غير محدد';

ALTER TABLE `customer`
  MODIFY COLUMN `Customer_age` INT(3) NOT NULL DEFAULT 0;
