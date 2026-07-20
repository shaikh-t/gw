<?php
// upgrade_migration.php
require_once  '../lib/db_mysqli.php';

echo "Starting platform upgrade migrations...\n";

if (!isset($mysqli) || $mysqli->connect_errno) {
    die("Database connection is unavailable.\n");
}

// 1. Alter 'users' table to add 'deleted_at' for soft deletes
$res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'deleted_at'");
if ($res && $res->num_rows === 0) {
    if ($mysqli->query("ALTER TABLE users ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL")) {
        echo "Column 'deleted_at' added to users successfully.\n";
    } else {
        echo "Error adding 'deleted_at' to users: " . $mysqli->error . "\n";
    }
} else {
    echo "Column 'deleted_at' already exists in users.\n";
}

// 2. Alter 'providers' table to add deduction configurations
$res = $mysqli->query("SHOW COLUMNS FROM providers LIKE 'deduction_type'");
if ($res && $res->num_rows === 0) {
    if ($mysqli->query("ALTER TABLE providers ADD COLUMN `deduction_type` ENUM('percentage', 'flat') NOT NULL DEFAULT 'percentage'")) {
        echo "Column 'deduction_type' added to providers successfully.\n";
    } else {
        echo "Error adding 'deduction_type' to providers: " . $mysqli->error . "\n";
    }
} else {
    echo "Column 'deduction_type' already exists in providers.\n";
}

$res = $mysqli->query("SHOW COLUMNS FROM providers LIKE 'deduction_value'");
if ($res && $res->num_rows === 0) {
    if ($mysqli->query("ALTER TABLE providers ADD COLUMN `deduction_value` DECIMAL(10,2) NOT NULL DEFAULT 10.00")) {
        echo "Column 'deduction_value' added to providers successfully.\n";
    } else {
        echo "Error adding 'deduction_value' to providers: " . $mysqli->error . "\n";
    }
} else {
    echo "Column 'deduction_value' already exists in providers.\n";
}

// 3. Alter 'payment_transactions' table to add Gross, Fee, Net, Case UUID and Provider ID
$columns_to_add_payment_tx = [
    'gross_amount' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'platform_fee' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'vendor_net_amount' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'case_uuid' => "VARCHAR(36) NULL DEFAULT NULL",
    'provider_id' => "INT UNSIGNED NULL DEFAULT NULL"
];

foreach ($columns_to_add_payment_tx as $col => $definition) {
    $res = $mysqli->query("SHOW COLUMNS FROM payment_transactions LIKE '$col'");
    if ($res && $res->num_rows === 0) {
        if ($mysqli->query("ALTER TABLE payment_transactions ADD COLUMN `$col` $definition")) {
            echo "Column '$col' added to payment_transactions successfully.\n";
        } else {
            echo "Error adding '$col' to payment_transactions: " . $mysqli->error . "\n";
        }
    } else {
        echo "Column '$col' already exists in payment_transactions.\n";
    }
}

// 4. Create local_knowledge_base table
$sql = "CREATE TABLE IF NOT EXISTS `local_knowledge_base` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `file_name` VARCHAR(255) NOT NULL,
  `document_category` VARCHAR(100) NOT NULL,
  `page_number` INT UNSIGNED NOT NULL,
  `text_content` LONGTEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FULLTEXT KEY `idx_text_content` (`text_content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($sql)) {
    echo "Table 'local_knowledge_base' checked/created successfully with FULLTEXT index.\n";
} else {
    echo "Error creating 'local_knowledge_base' table: " . $mysqli->error . "\n";
}

echo "Platform upgrade migrations completed successfully!\n";
?>
