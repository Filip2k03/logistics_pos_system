-- Table: regions
DROP TABLE IF EXISTS `regions`;
CREATE TABLE `regions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `region_code` VARCHAR(10) NOT NULL,
  `region_name` VARCHAR(50) NOT NULL,
  `voucher_prefix` VARCHAR(5) NOT NULL,
  `current_sequence` INT DEFAULT 0,
  `price_per_kg` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `region_code` (`region_code`),
  UNIQUE KEY `voucher_prefix` (`voucher_prefix`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO `regions` (`id`, `region_code`, `region_name`, `voucher_prefix`, `current_sequence`, `price_per_kg`) VALUES
  (1, 'MM',  'Myanmar',   'MD', 3, 10.00),
  (2, 'ML',  'Malaysia',  'ML', 0, 15.00),
  (3, 'AUS', 'Australia', 'AU', 0, 25.00),
  (4, 'TH',  'Thailand',  'TH', 0, 12.00);

-- Table: stock
DROP TABLE IF EXISTS `stock`;
CREATE TABLE `stock` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `voucher_id` INT NOT NULL,
  `current_location_region` VARCHAR(10) NOT NULL,
  `status` ENUM('PENDING_ORIGIN_PICKUP','IN_TRANSIT','ARRIVED_PENDING_RECEIVE','DELIVERED','RETURNED') NOT NULL DEFAULT 'PENDING_ORIGIN_PICKUP',
  `last_status_update_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_id` (`voucher_id`),
  KEY `current_location_region` (`current_location_region`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO `stock` (`id`, `voucher_id`, `current_location_region`, `status`, `last_status_update_at`, `created_at`) VALUES
  (1, 1, 'MM', 'PENDING_ORIGIN_PICKUP', '2025-06-08 02:54:46', '2025-06-08 02:54:46'),
  (2, 2, 'MM', 'PENDING_ORIGIN_PICKUP', '2025-06-08 03:08:06', '2025-06-08 03:08:06'),
  (3, 3, 'MM', 'PENDING_ORIGIN_PICKUP', '2025-06-08 03:51:05', '2025-06-08 03:51:05');

-- Table: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `region` VARCHAR(10) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `username`, `password`, `region`, `created_at`) VALUES
  (4, 'mm_user', '$2y$10$N/0bZXjgEKUs8xZaPnDNCeupx4kaMWFda2fkLKsAtnLzj43iHJPN.', 'MM', '2025-06-08 03:47:01'),
  (3, 'Myanmar', '$2y$10$ABLKuJTtnIcNEpOddSsuNOUM6G6XwmPlQkC0380eLoRmhb4jyTf7u', 'Admins', '2025-06-08 02:53:01');

-- Table: vouchers
DROP TABLE IF EXISTS `vouchers`;
CREATE TABLE `vouchers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `voucher_number` VARCHAR(20) NOT NULL,
  `origin_region` VARCHAR(10) NOT NULL,
  `destination_region` VARCHAR(10) NOT NULL,
  `sender_name` VARCHAR(100) NOT NULL,
  `sender_phone` VARCHAR(20) NOT NULL,
  `sender_address` TEXT NOT NULL,
  `receiver_name` VARCHAR(100) NOT NULL,
  `receiver_phone` VARCHAR(20) NOT NULL,
  `receiver_address` TEXT NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `weight_kg` DECIMAL(10,2) NOT NULL,
  `price_per_kg_at_voucher` DECIMAL(10,2) NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `created_by_user_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_number` (`voucher_number`),
  KEY `created_by_user_id` (`created_by_user_id`),
  KEY `origin_region` (`origin_region`),
  KEY `destination_region` (`destination_region`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO `vouchers` (`id`, `voucher_number`, `origin_region`, `destination_region`, `sender_name`, `sender_phone`, `sender_address`, `receiver_name`, `receiver_phone`, `receiver_address`, `payment_method`, `weight_kg`, `price_per_kg_at_voucher`, `total_amount`, `created_by_user_id`, `created_at`) VALUES
  (1, 'MD000001', 'MM', 'ML', 'Thu Ya', '9595959595', 'qwertyuiop', 'Kyaw', '34567', 'sdfghjk', 'Cash', 10.00, 10.00, 100.00, 3, '2025-06-08 02:54:46'),
  (2, 'MD000002', 'MM', 'AUS', 'Thu Ya', '09954480806', '40th streer', 'Thu Ya Kyaw', '09954480806', 'Yangon, Myanmar', 'Cash', 11.00, 9.00, 99.00, 3, '2025-06-08 03:08:06'),
  (3, 'MD000003', 'MM', 'ML', 'Thu Ya', '09954480806', '40th streer', 'Thu Ya Kyaw', '09954480806', 'Yangon, Myanmar', 'Cash', 5.00, 15.00, 75.00, 4, '2025-06-08 03:51:05');


  CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) NOT NULL,
    description VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE expenses
ADD COLUMN date DATE NOT NULL AFTER id,
ADD COLUMN region VARCHAR(50) NOT NULL AFTER date,
ADD COLUMN created_by INT AFTER description;

-- If you also need to store currency information, ensure your 'currency' column is sufficient.
-- The PHP script currently doesn't use 'currency' in the display.

ALTER TABLE `vouchers`
ADD COLUMN `delivery_charge` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `price_per_kg_at_voucher`;