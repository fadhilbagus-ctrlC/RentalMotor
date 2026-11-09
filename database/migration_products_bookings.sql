-- Migrasi: tabel products & bookings untuk MotorKu
-- Database: login_db (users sudah ada, id INT UNSIGNED)
-- Jalankan: mysql -u root login_db < migration_products_bookings.sql

CREATE TABLE IF NOT EXISTS `products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `brand` VARCHAR(100) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `price_per_day` INT NOT NULL DEFAULT 0,
    `price_per_week` INT NOT NULL DEFAULT 0,
    `image` VARCHAR(255) NULL DEFAULT NULL,
    `description` TEXT NULL,
    `status` ENUM('available','rented','maintenance') NOT NULL DEFAULT 'available',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_products_status` (`status`),
    KEY `idx_products_brand` (`brand`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `booking_date` DATE NOT NULL,
    `pickup_date` DATE NOT NULL,
    `return_date` DATE NOT NULL,
    `total_days` INT NOT NULL DEFAULT 1,
    `total_price` BIGINT NOT NULL DEFAULT 0,
    `status` ENUM('pending','confirmed','active','completed','cancelled') NOT NULL DEFAULT 'pending',
    `payment_method` VARCHAR(20) NULL DEFAULT NULL,
    `payment_number` VARCHAR(50) NULL DEFAULT NULL,
    `payment_ref` VARCHAR(64) NULL DEFAULT NULL,
    `payment_url` TEXT NULL DEFAULT NULL,
    `paid_at` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_bookings_user` (`user_id`),
    KEY `idx_payment_ref` (`payment_ref`),
    KEY `idx_bookings_product_status_dates` (`product_id`, `status`, `pickup_date`, `return_date`),
    CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pembayaran e-wallet untuk DB lama (idempoten, hanya jalan bila kolom belum ada)
SET @has_pm := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'payment_method');
SET @sql_pm := IF(@has_pm = 0, 'ALTER TABLE `bookings` ADD COLUMN `payment_method` VARCHAR(20) NULL DEFAULT NULL AFTER `status`', 'SELECT 1');
PREPARE stmt_pm FROM @sql_pm; EXECUTE stmt_pm; DEALLOCATE PREPARE stmt_pm;
SET @has_pn := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'payment_number');
SET @sql_pn := IF(@has_pn = 0, 'ALTER TABLE `bookings` ADD COLUMN `payment_number` VARCHAR(50) NULL DEFAULT NULL AFTER `payment_method`', 'SELECT 1');
PREPARE stmt_pn FROM @sql_pn; EXECUTE stmt_pn; DEALLOCATE PREPARE stmt_pn;
SET @has_pr := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'payment_ref');
SET @sql_pr := IF(@has_pr = 0, 'ALTER TABLE `bookings` ADD COLUMN `payment_ref` VARCHAR(64) NULL DEFAULT NULL AFTER `payment_number`', 'SELECT 1');
PREPARE stmt_pr FROM @sql_pr; EXECUTE stmt_pr; DEALLOCATE PREPARE stmt_pr;
SET @has_pu := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'payment_url');
SET @sql_pu := IF(@has_pu = 0, 'ALTER TABLE `bookings` ADD COLUMN `payment_url` TEXT NULL DEFAULT NULL AFTER `payment_ref`', 'SELECT 1');
PREPARE stmt_pu FROM @sql_pu; EXECUTE stmt_pu; DEALLOCATE PREPARE stmt_pu;
SET @has_pa := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'paid_at');
SET @sql_pa := IF(@has_pa = 0, 'ALTER TABLE `bookings` ADD COLUMN `paid_at` DATETIME NULL DEFAULT NULL AFTER `payment_url`', 'SELECT 1');
PREPARE stmt_pa FROM @sql_pa; EXECUTE stmt_pa; DEALLOCATE PREPARE stmt_pa;
SET @has_ara := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'actual_return_at');
SET @sql_ara := IF(@has_ara = 0, 'ALTER TABLE `bookings` ADD COLUMN `actual_return_at` DATETIME NULL DEFAULT NULL AFTER `return_date`', 'SELECT 1');
PREPARE stmt_ara FROM @sql_ara; EXECUTE stmt_ara; DEALLOCATE PREPARE stmt_ara;
SET @has_lm := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'late_minutes');
SET @sql_lm := IF(@has_lm = 0, 'ALTER TABLE `bookings` ADD COLUMN `late_minutes` INT NOT NULL DEFAULT 0 AFTER `actual_return_at`', 'SELECT 1');
PREPARE stmt_lm FROM @sql_lm; EXECUTE stmt_lm; DEALLOCATE PREPARE stmt_lm;
SET @has_fa := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'fine_amount');
SET @sql_fa := IF(@has_fa = 0, 'ALTER TABLE `bookings` ADD COLUMN `fine_amount` INT NOT NULL DEFAULT 0 AFTER `late_minutes`', 'SELECT 1');
PREPARE stmt_fa FROM @sql_fa; EXECUTE stmt_fa; DEALLOCATE PREPARE stmt_fa;
SET @has_idxpr := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND INDEX_NAME = 'idx_payment_ref');
SET @sql_idxpr := IF(@has_idxpr = 0, 'ALTER TABLE `bookings` ADD KEY `idx_payment_ref` (`payment_ref`)', 'SELECT 1');
PREPARE stmt_idxpr FROM @sql_idxpr; EXECUTE stmt_idxpr; DEALLOCATE PREPARE stmt_idxpr;
SET @has_pp := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_photo');
SET @sql_pp := IF(@has_pp = 0, 'ALTER TABLE `users` ADD COLUMN `profile_photo` VARCHAR(255) NULL DEFAULT NULL AFTER `role`', 'SELECT 1');
PREPARE stmt_pp FROM @sql_pp; EXECUTE stmt_pp; DEALLOCATE PREPARE stmt_pp;

-- Contoh data agar katalog tidak kosong (tanpa tahun produksi, angka CC dipertahankan)
-- Idempoten: baris yang namanya sudah ada tidak diinsert ulang
INSERT INTO `products` (`name`, `brand`, `type`, `price_per_day`, `price_per_week`, `image`, `description`, `status`)
SELECT `seed`.* FROM (
    SELECT 'Honda Beat' AS `name`, 'Honda' AS `brand`, 'Matic' AS `type`, 75000 AS `price_per_day`, 450000 AS `price_per_week`, 'uploads/products/honda-beat.webp' AS `image`, 'Motor matic irit, cocok untuk harian.' AS `description`, 'available' AS `status`
    UNION ALL SELECT 'Yamaha NMAX', 'Yamaha', 'Matic', 120000, 700000, 'uploads/products/yamaha-nmax.webp', 'Matic premium nyaman untuk jarak jauh.', 'available'
    UNION ALL SELECT 'Honda Vario 160', 'Honda', 'Matic', 100000, 600000, 'uploads/products/honda-vario-160.webp', 'Matic sporty bertenaga.', 'available'
    UNION ALL SELECT 'Yamaha Mio M3', 'Yamaha', 'Matic', 70000, 420000, 'uploads/products/yamaha-mio-m3.webp', 'Matic harian ringan dan irit.', 'available'
    UNION ALL SELECT 'Honda Scoopy', 'Honda', 'Matic', 85000, 500000, 'uploads/products/honda-scoopy.webp', 'Matic stylish favorit anak muda.', 'available'
    UNION ALL SELECT 'Honda Beat Deluxe', 'Honda', 'Matic', 75000, 450000, 'uploads/products/honda-beat-deluxe.webp', 'Beat varian deluxe, irit harian.', 'available'
    UNION ALL SELECT 'Honda Vario 125', 'Honda', 'Matic', 90000, 540000, 'uploads/products/honda-vario-125.webp', 'Matic 125cc nyaman jarak menengah.', 'available'
    UNION ALL SELECT 'Honda PCX 160', 'Honda', 'Matic', 130000, 780000, 'uploads/products/honda-pcx-160.webp', 'Matic premium nyaman jarak jauh.', 'available'
) AS `seed`
LEFT JOIN `products` AS `p` ON `p`.`name` = `seed`.`name`
WHERE `p`.`id` IS NULL;

-- Samakan gambar produk lama dengan file gambar yang tersedia.
UPDATE `products` SET `image` = 'uploads/products/honda-beat.webp' WHERE `name` = 'Honda Beat' AND `brand` = 'Honda';
UPDATE `products` SET `image` = 'uploads/products/yamaha-nmax.webp' WHERE `name` = 'Yamaha NMAX' AND `brand` = 'Yamaha';
UPDATE `products` SET `image` = 'uploads/products/honda-vario-160.webp' WHERE `name` = 'Honda Vario 160' AND `brand` = 'Honda';
UPDATE `products` SET `image` = 'uploads/products/yamaha-mio-m3.webp' WHERE `name` = 'Yamaha Mio M3' AND `brand` = 'Yamaha';
UPDATE `products` SET `image` = 'uploads/products/honda-scoopy.webp' WHERE `name` = 'Honda Scoopy' AND `brand` = 'Honda';
UPDATE `products` SET `image` = 'uploads/products/honda-beat-deluxe.webp' WHERE `name` = 'Honda Beat Deluxe' AND `brand` = 'Honda';
UPDATE `products` SET `image` = 'uploads/products/honda-vario-125.webp' WHERE `name` = 'Honda Vario 125' AND `brand` = 'Honda';
UPDATE `products` SET `image` = 'uploads/products/honda-pcx-160.webp' WHERE `name` = 'Honda PCX 160' AND `brand` = 'Honda';
