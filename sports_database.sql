-- ============================================================
--  SPORT SHOP – Database Script
--  Kết nối qua XAMPP: localhost | user: root | pass: (trống)
--  Cách import: Mở phpMyAdmin → Import → chọn file này
-- ============================================================

CREATE DATABASE IF NOT EXISTS `sports`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `sports`;

-- ────────────────────────────────────────────────────────────
-- 1. Bảng người dùng
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user` (
    `id`          BIGINT(20)   NOT NULL AUTO_INCREMENT,
    `avatar`      VARCHAR(255) DEFAULT NULL,
    `create_date` DATETIME(6)  DEFAULT NULL,
    `email`       VARCHAR(255) DEFAULT NULL,
    `email_verified_at` DATETIME(6) DEFAULT NULL,
    `email_otp_hash` VARCHAR(255) DEFAULT NULL,
    `email_otp_expires` DATETIME(6) DEFAULT NULL,
    `full_name`   VARCHAR(255) DEFAULT NULL,
    `gender`      VARCHAR(255) DEFAULT NULL,
    `password`    VARCHAR(255) DEFAULT NULL,
    `phone`       VARCHAR(255) DEFAULT NULL,
    `roles`       ENUM('ADMIN','USER')          DEFAULT NULL,
    `status_user` ENUM('Active','Closed')       DEFAULT NULL,
    `update_date` DATETIME(6)  DEFAULT NULL,
    `username`    VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- 2. Bảng địa chỉ người dùng
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `address_user` (
    `id`      BIGINT(20)   NOT NULL AUTO_INCREMENT,
    `address` VARCHAR(255) DEFAULT NULL,
    `phone`   VARCHAR(255) DEFAULT NULL,
    `user_id` BIGINT(20)   DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_address_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- 3. Bảng thương hiệu
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `brand_product` (
    `id`         BIGINT(20)   NOT NULL AUTO_INCREMENT,
    `image`      VARCHAR(255) DEFAULT NULL,
    `name_brand` VARCHAR(255) DEFAULT NULL,
    `status`     ENUM('Active','Closed') DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- 4. Bảng danh mục
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `category_product` (
    `id`            BIGINT(20)   NOT NULL AUTO_INCREMENT,
    `image`         VARCHAR(255) DEFAULT NULL,
    `name_category` VARCHAR(255) DEFAULT NULL,
    `status`        ENUM('Active','Closed') DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- 5. Bảng sản phẩm
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `product` (
    `id`             BIGINT(20)   NOT NULL AUTO_INCREMENT,
    `color`          VARCHAR(255) DEFAULT NULL,
    `sizes`          VARCHAR(255) DEFAULT 'S, M, L, XL',
    `create_date`    DATETIME(6)  DEFAULT NULL,
    `description`    MEDIUMTEXT   DEFAULT NULL,
    `discount`       BIGINT(20)   DEFAULT 0,
    `name`           VARCHAR(255) DEFAULT NULL,
    `price`          BIGINT(20)   DEFAULT NULL,
    `quantity`       BIGINT(20)   DEFAULT 0,
    `quantity_sell`  BIGINT(20)   DEFAULT 0,
    `status_product` ENUM('Active','Closed') DEFAULT NULL,
    `update_date`    DATETIME(6)  DEFAULT NULL,
    `brand_id`       BIGINT(20)   DEFAULT NULL,
    `category_id`    BIGINT(20)   DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_product_brand`    FOREIGN KEY (`brand_id`)    REFERENCES `brand_product`(`id`)    ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `category_product`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- 6. Bảng hình ảnh sản phẩm
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `image_product` (
    `id`         BIGINT(20)   NOT NULL AUTO_INCREMENT,
    `image_link` VARCHAR(255) DEFAULT NULL,
    `product_id` BIGINT(20)   DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_image_product` FOREIGN KEY (`product_id`) REFERENCES `product`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- 7. Bảng giỏ hàng
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cart` (
    `id`         BIGINT(20) NOT NULL AUTO_INCREMENT,
    `quantity`   BIGINT(20) DEFAULT NULL,
    `size`       VARCHAR(50) DEFAULT '40',
    `product_id` BIGINT(20) DEFAULT NULL,
    `user_id`    BIGINT(20) DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `product`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cart_user`    FOREIGN KEY (`user_id`)    REFERENCES `user`(`id`)    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- 8. Bảng bình luận / đánh giá
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `comment` (
    `id`          BIGINT(20)    NOT NULL AUTO_INCREMENT,
    `date`        DATETIME(6)   DEFAULT NULL,
    `messages`    VARCHAR(2000) DEFAULT NULL,
    `product_id`  BIGINT(20)    DEFAULT NULL,
    `user_id`     BIGINT(20)    DEFAULT NULL,
    `media_url`   VARCHAR(255)  DEFAULT NULL,
    `admin_reply` VARCHAR(2000) DEFAULT NULL,
    `reply_date`  DATETIME      DEFAULT NULL,
    `rate`        INT(11)       DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_comment_product` FOREIGN KEY (`product_id`) REFERENCES `product`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_comment_user`    FOREIGN KEY (`user_id`)    REFERENCES `user`(`id`)    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- 9. Bảng đơn hàng
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `orders` (
    `id`               BIGINT(20)   NOT NULL AUTO_INCREMENT,
    `address`          VARCHAR(255) DEFAULT NULL,
    `date`             DATETIME(6)  DEFAULT NULL,
    `phone`            VARCHAR(255) DEFAULT NULL,
    `quantity_product` BIGINT(20)   DEFAULT NULL,
    `status_order`     ENUM('Cho_Thanh_Toan','Dang_Xu_Ly','Dang_Giao','Da_Giao','Da_Nhan_Hang','Yeu_Cau_Tra_Hang','Da_Tra_Hang','Yeu_Cau_Huy','Da_Huy') DEFAULT NULL,
    `total`            BIGINT(20)   DEFAULT NULL,
    `user_id`          BIGINT(20)   DEFAULT NULL,
    `ghn_order_code`   VARCHAR(50)  DEFAULT NULL,
    `ghn_total_fee`    INT          NOT NULL DEFAULT 0,
    `to_district_id`   INT          DEFAULT NULL,
    `to_ward_code`     VARCHAR(20)  DEFAULT NULL,
    `shipping_status`  VARCHAR(40)  NOT NULL DEFAULT 'not_shipped',
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Đồng bộ enum khi chạy script trên database đã tồn tại.
ALTER TABLE `orders`
    MODIFY `status_order`
    ENUM('Cho_Thanh_Toan','Dang_Xu_Ly','Dang_Giao','Da_Giao','Da_Nhan_Hang','Yeu_Cau_Tra_Hang','Da_Tra_Hang','Yeu_Cau_Huy','Da_Huy')
    DEFAULT NULL;

-- ────────────────────────────────────────────────────────────
-- 10. Bảng chi tiết đơn hàng
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `order_detail` (
    `id`         BIGINT(20) NOT NULL AUTO_INCREMENT,
    `discount`   BIGINT(20) DEFAULT NULL,
    `price`      BIGINT(20) DEFAULT NULL,
    `quantity`   BIGINT(20) DEFAULT NULL,
    `size`       VARCHAR(50) DEFAULT '40',
    `total`      BIGINT(20) DEFAULT NULL,
    `order_id`   BIGINT(20) DEFAULT NULL,
    `product_id` BIGINT(20) DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_order_detail_orders`  FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)  ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_detail_product` FOREIGN KEY (`product_id`) REFERENCES `product`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- 11. Bảng giao dịch thanh toán
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payments` (
    `id`                BIGINT(20) NOT NULL AUTO_INCREMENT,
    `order_id`          BIGINT(20) NOT NULL,
    `request_id`        VARCHAR(100) NOT NULL,
    `provider_order_id` VARCHAR(100) NOT NULL,
    `provider`          VARCHAR(30) NOT NULL DEFAULT 'MoMo',
    `amount`            BIGINT(20) NOT NULL,
    `status`            ENUM('Pending','Paid','Failed') NOT NULL DEFAULT 'Pending',
    `result_code`       INT(11) DEFAULT NULL,
    `trans_id`          VARCHAR(100) DEFAULT NULL,
    `payload`           LONGTEXT DEFAULT NULL,
    `receipt_image`     VARCHAR(255) DEFAULT NULL,
    `customer_submitted_at` DATETIME(6) DEFAULT NULL,
    `admin_reviewed_at` DATETIME(6) DEFAULT NULL,
    `admin_reviewed_by` BIGINT(20) DEFAULT NULL,
    `created_at`        DATETIME(6) NOT NULL,
    `updated_at`        DATETIME(6) NOT NULL,
    `paid_at`           DATETIME(6) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payments_order_id` (`order_id`),
    UNIQUE KEY `uq_payments_request_id` (`request_id`),
    UNIQUE KEY `uq_payments_provider_order_id` (`provider_order_id`),
    CONSTRAINT `fk_payments_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- ============================================================
--  DỮ LIỆU MẪU THỜI TRANG (TRENDSTYLE FASHION SAMPLE DATA)
-- ============================================================

-- Làm sạch dữ liệu mẫu cũ nếu có để tránh trùng lặp
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `image_product`;
TRUNCATE TABLE `product`;
TRUNCATE TABLE `category_product`;
TRUNCATE TABLE `brand_product`;
TRUNCATE TABLE `user`;
SET FOREIGN_KEY_CHECKS = 1;

-- Tài khoản mẫu
-- Password mẫu: password (đã được mã hóa bcrypt)
INSERT INTO `user` (id, username, password, email, full_name, roles, status_user, email_verified_at, create_date, update_date) VALUES
(1, 'admin',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'admin@trendstyle.vn',    'Quản Trị Viên', 'ADMIN',  'Active', NOW(6), NOW(6), NOW(6)),
(2, 'nguyenhue','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'hue@example.com',        'Nguyễn Huệ',   'USER',   'Active', NOW(6), NOW(6), NOW(6)),
(3, 'testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'test@example.com',       'Người Dùng Test','USER', 'Active', NOW(6), NOW(6), NOW(6));

-- Thương hiệu thời trang nổi tiếng
INSERT INTO `brand_product` (id, name_brand, status, image) VALUES
(1, 'Uniqlo',   'Active', 'brand_uniqlo.png'),
(2, 'Zara',     'Active', 'brand_zara.png'),
(3, 'H&M',      'Active', 'brand_hm.png'),
(4, 'Coolmate', 'Active', 'brand_coolmate.png'),
(5, 'Mango',    'Active', 'brand_mango.png');

-- Danh mục thời trang phong phú
INSERT INTO `category_product` (id, name_category, status, image) VALUES
(1, 'Áo Polo & Sơ Mi',      'Active', 'cat_polo_shirt.png'),
(2, 'Áo Khoác & Blazer',    'Active', 'cat_outerwear.png'),
(3, 'Quần Jean & Kaki',     'Active', 'cat_pants.png'),
(4, 'Váy & Đầm Nữ',         'Active', 'cat_dresses.png'),
(5, 'Phụ Kiện Thời Trang',  'Active', 'cat_accessories.png');

-- Sản phẩm thời trang mẫu cao cấp
INSERT INTO `product` (id, name, price, discount, quantity, quantity_sell, color, sizes, description, status_product, brand_id, category_id, create_date, update_date) VALUES
(1, 'Áo Polo Nam Pima Cotton Cao Cấp',        450000,  15, 80, 240, 'Xanh Navy', 'S, M, L, XL',     'Áo polo nam chất liệu Pima Cotton thượng hạng mềm mại, thoáng mát, thấm hút mồ hôi và chống nhăn tối ưu.', 'Active', 4, 1, NOW(6), NOW(6)),
(2, 'Áo Sơ Mi Oxford Form Regular Thanh Lịch', 650000,  10, 60, 190, 'Trắng',     'S, M, L, XL',     'Áo sơ mi Oxford Uniqlo dệt sợi bông tự nhiên, form dáng chuẩn công sở mang lại vẻ ngoài lịch lãm, tinh tế.', 'Active', 1, 1, NOW(6), NOW(6)),
(3, 'Áo Blazer Hàn Quốc Unisex Form Rộng',    1250000, 20, 35, 110, 'Be/Nâu',    'M, L, XL',        'Áo khoác Blazer Zara phong cách minimalism hiện đại, may 2 lớp đứng form, phù hợp đi làm lẫn dạo phố.', 'Active', 2, 2, NOW(6), NOW(6)),
(4, 'Đầm Dạ Hội Lụa Satin Dáng Xòe Tinh Tế',  1450000, 18, 25,  95, 'Đỏ Rượu',   'S, M, L',         'Đầm dạ hội Mango chất liệu lụa Satin bóng nhẹ quý phái, đường cắt may tỉ mỉ tôn trọn đường cong nữ tính.', 'Active', 5, 4, NOW(6), NOW(6)),
(5, 'Quần Jean Nam Slimfit Co Giãn Cao Cấp',   790000,  12, 70, 160, 'Xanh Denim', '29, 30, 31, 32',  'Quần Jeans nam H&M vải denim cao cấp pha sợi spandex co giãn linh hoạt, form ôm vừa vặn tôn dáng.', 'Active', 3, 3, NOW(6), NOW(6)),
(6, 'Áo Khoác Bomber Gió 2 Lớp Chống Nước',    850000,  25, 45, 140, 'Đen',       'M, L, XL, XXL',   'Áo khoác bomber thời trang đường phố trẻ trung, chất liệu cản gió, chống trượt nước nhẹ và giữ ấm tốt.', 'Active', 2, 2, NOW(6), NOW(6)),
(7, 'Chân Váy Xếp Ly Dáng Dài Vintage',        520000,   8, 50,  85, 'Kem Be',    'S, M, FreeSize',  'Chân váy dập ly dáng dài thanh thoát, dễ dàng phối cùng áo len, sơ mi hoặc áo phông nữ tính.', 'Active', 1, 4, NOW(6), NOW(6)),
(8, 'Quần Kaki Công Sở Co Giãn 4 Chiều',       580000,  15, 65, 175, 'Xám Tro',   '29, 30, 31, 32',  'Quần Kaki Coolmate phom dáng công sở năng động, chất liệu cotton spandex co giãn 4 chiều vận động thoải mái.', 'Active', 4, 3, NOW(6), NOW(6));

-- Hình ảnh sản phẩm mẫu
INSERT INTO `image_product` (product_id, image_link) VALUES
(1, 'polo_pima.jpg'),
(2, 'oxford_shirt.jpg'),
(3, 'blazer_unisex.jpg'),
(4, 'dress_satin.jpg'),
(5, 'jeans_slimfit.jpg'),
(6, 'bomber_jacket.jpg'),
(7, 'pleated_skirt.jpg'),
(8, 'kaki_pants.jpg');

