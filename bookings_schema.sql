-- ============================================================
-- Brèagha Clinic - Bookings Table Schema
-- Run this in phpMyAdmin or MySQL console
-- ============================================================

CREATE TABLE IF NOT EXISTS `bookings` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `booking_id`   VARCHAR(30)  NOT NULL UNIQUE,
  `full_name`    VARCHAR(255) NOT NULL,
  `phone`        VARCHAR(20)  NOT NULL,
  `age`          INT(3)       NOT NULL,
  `department`   VARCHAR(100) NOT NULL,
  `service`      VARCHAR(255) NOT NULL,
  `sessions`     VARCHAR(50)  DEFAULT NULL COMMENT 'Only for Laser department',
  `booking_date` DATE         NOT NULL,
  `booking_time` VARCHAR(50)  NOT NULL,
  `status`       VARCHAR(20)  NOT NULL DEFAULT 'pending',
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_id` (`booking_id`),
  KEY `idx_date_time` (`booking_date`, `booking_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
