-- ============================================================
-- SportShop - QR thanh toán thủ công do ADMIN quản lý
-- Import sau sports_database.sql. Có thể chạy lại an toàn.
-- ============================================================
USE `sports`;

CREATE TABLE IF NOT EXISTS `payment_qr_settings` (
    `id`             BIGINT(20) NOT NULL AUTO_INCREMENT,
    `provider`       ENUM('VNPayQR') NOT NULL,
    `display_name`   VARCHAR(100) NOT NULL,
    `account_name`   VARCHAR(150) NOT NULL,
    `account_number` VARCHAR(100) DEFAULT NULL,
    `qr_image`       VARCHAR(255) NOT NULL,
    `transfer_prefix` VARCHAR(20) NOT NULL DEFAULT 'SPORTSHOP',
    `instructions`   VARCHAR(500) DEFAULT NULL,
    `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
    `updated_at`     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                     ON UPDATE CURRENT_TIMESTAMP(6),
    `updated_by`     BIGINT(20) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payment_qr_provider` (`provider`),
    CONSTRAINT `fk_payment_qr_admin`
      FOREIGN KEY (`updated_by`) REFERENCES `user`(`id`)
      ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `payments`
  ADD COLUMN IF NOT EXISTS `receipt_image` VARCHAR(255) DEFAULT NULL AFTER `payload`,
  ADD COLUMN IF NOT EXISTS `customer_submitted_at` DATETIME(6) DEFAULT NULL AFTER `receipt_image`,
  ADD COLUMN IF NOT EXISTS `admin_reviewed_at` DATETIME(6) DEFAULT NULL AFTER `customer_submitted_at`,
  ADD COLUMN IF NOT EXISTS `admin_reviewed_by` BIGINT(20) DEFAULT NULL AFTER `admin_reviewed_at`;

