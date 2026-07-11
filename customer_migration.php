<?php
// customer_migration.php
require_once __DIR__ . '/lib/db_mysqli.php';

echo "Starting customer database tables migration...\n";

// Table 1: customer_applications
$mysqli->query("CREATE TABLE IF NOT EXISTS `customer_applications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(36) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NOT NULL,
  `service_name` VARCHAR(255) NOT NULL,
  `tracking_id` VARCHAR(50) NOT NULL UNIQUE,
  `vendor_name` VARCHAR(255) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'In Progress',
  `progress` INT NOT NULL DEFAULT 0,
  `submitted_at` DATE NOT NULL,
  `est_completion` DATE NOT NULL,
  `last_update` VARCHAR(100) NOT NULL,
  `next_action` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `paid_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Table 2: customer_documents
$mysqli->query("CREATE TABLE IF NOT EXISTS `customer_documents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(36) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NOT NULL,
  `application_id` INT UNSIGNED NULL,
  `name` VARCHAR(255) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Uploaded',
  `uploaded_at` DATE NOT NULL,
  `expires_at` VARCHAR(50) NOT NULL DEFAULT 'N/A',
  `file_type` VARCHAR(10) NOT NULL DEFAULT 'PDF',
  `file_size` VARCHAR(50) NOT NULL DEFAULT '2.4 MB',
  `tags` VARCHAR(255) NOT NULL DEFAULT '',
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Table 3: customer_payments
$mysqli->query("CREATE TABLE IF NOT EXISTS `customer_payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(36) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NOT NULL,
  `application_id` INT UNSIGNED NULL,
  `service_name` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Completed',
  `payment_date` DATE NOT NULL,
  `method` VARCHAR(50) NOT NULL DEFAULT 'Credit Card',
  `invoice_num` VARCHAR(50) NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Table 4: customer_messages
$mysqli->query("CREATE TABLE IF NOT EXISTS `customer_messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(36) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NOT NULL,
  `sender` VARCHAR(100) NOT NULL,
  `service_name` VARCHAR(100) NOT NULL,
  `message_text` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

echo "Customer tables migrated successfully!\n";
