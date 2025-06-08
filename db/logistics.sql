-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 08, 2025 at 04:14 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `logistics`
--

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

DROP TABLE IF EXISTS `regions`;
CREATE TABLE IF NOT EXISTS `regions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `region_code` varchar(10) NOT NULL,
  `region_name` varchar(50) NOT NULL,
  `voucher_prefix` varchar(5) NOT NULL,
  `current_sequence` int DEFAULT '0',
  `price_per_kg` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `region_code` (`region_code`),
  UNIQUE KEY `voucher_prefix` (`voucher_prefix`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` (`id`, `region_code`, `region_name`, `voucher_prefix`, `current_sequence`, `price_per_kg`) VALUES
(1, 'MM', 'Myanmar', 'MD', 3, 10.00),
(2, 'ML', 'Malaysia', 'ML', 0, 15.00),
(3, 'AUS', 'Australia', 'AU', 0, 25.00),
(4, 'TH', 'Thailand', 'TH', 0, 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

DROP TABLE IF EXISTS `stock`;
CREATE TABLE IF NOT EXISTS `stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `voucher_id` int NOT NULL,
  `current_location_region` varchar(10) NOT NULL,
  `status` enum('PENDING_ORIGIN_PICKUP','IN_TRANSIT','ARRIVED_PENDING_RECEIVE','DELIVERED','RETURNED') NOT NULL DEFAULT 'PENDING_ORIGIN_PICKUP',
  `last_status_update_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_id` (`voucher_id`),
  KEY `current_location_region` (`current_location_region`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`id`, `voucher_id`, `current_location_region`, `status`, `last_status_update_at`, `created_at`) VALUES
(1, 1, 'MM', 'PENDING_ORIGIN_PICKUP', '2025-06-08 02:54:46', '2025-06-08 02:54:46'),
(2, 2, 'MM', 'PENDING_ORIGIN_PICKUP', '2025-06-08 03:08:06', '2025-06-08 03:08:06'),
(3, 3, 'MM', 'PENDING_ORIGIN_PICKUP', '2025-06-08 03:51:05', '2025-06-08 03:51:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `region` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `region`, `created_at`) VALUES
(4, 'mm_user', '$2y$10$N/0bZXjgEKUs8xZaPnDNCeupx4kaMWFda2fkLKsAtnLzj43iHJPN.', 'MM', '2025-06-08 03:47:01'),
(3, 'Myanmar', '$2y$10$ABLKuJTtnIcNEpOddSsuNOUM6G6XwmPlQkC0380eLoRmhb4jyTf7u', 'Admins', '2025-06-08 02:53:01');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

DROP TABLE IF EXISTS `vouchers`;
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `voucher_number` varchar(20) NOT NULL,
  `origin_region` varchar(10) NOT NULL,
  `destination_region` varchar(10) NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `sender_phone` varchar(20) NOT NULL,
  `sender_address` text NOT NULL,
  `receiver_name` varchar(100) NOT NULL,
  `receiver_phone` varchar(20) NOT NULL,
  `receiver_address` text NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `weight_kg` decimal(10,2) NOT NULL,
  `price_per_kg_at_voucher` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_by_user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_number` (`voucher_number`),
  KEY `created_by_user_id` (`created_by_user_id`),
  KEY `origin_region` (`origin_region`),
  KEY `destination_region` (`destination_region`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `voucher_number`, `origin_region`, `destination_region`, `sender_name`, `sender_phone`, `sender_address`, `receiver_name`, `receiver_phone`, `receiver_address`, `payment_method`, `weight_kg`, `price_per_kg_at_voucher`, `total_amount`, `created_by_user_id`, `created_at`) VALUES
(1, 'MD000001', 'MM', 'ML', 'Thu Ya', '9595959595', 'qwertyuiop', 'Kyaw', '34567', 'sdfghjk', 'Cash', 10.00, 10.00, 100.00, 3, '2025-06-08 02:54:46'),
(2, 'MD000002', 'MM', 'AUS', 'Thu Ya', '09954480806', '40th streer', 'Thu Ya Kyaw', '09954480806', 'Yangon, Myanmar', 'Cash', 11.00, 9.00, 99.00, 3, '2025-06-08 03:08:06'),
(3, 'MD000003', 'MM', 'ML', 'Thu Ya', '09954480806', '40th streer', 'Thu Ya Kyaw', '09954480806', 'Yangon, Myanmar', 'Cash', 5.00, 15.00, 75.00, 4, '2025-06-08 03:51:05');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
