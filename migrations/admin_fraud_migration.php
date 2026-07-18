<?php
// admin/migrations/fraud_migration.php
require_once __DIR__ . '/../../lib/db_mysqli.php';

echo "Starting click-fraud validation table migration...\n";

// Disable foreign key checks temporarily during migration setup
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");

$sql = "CREATE TABLE IF NOT EXISTS `bot_ad_fraud_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id` INT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `clicked_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  KEY `ad_id` (`ad_id`),
  CONSTRAINT `fk_bot_ad_fraud_logs_ad` FOREIGN KEY (`ad_id`) REFERENCES `bot_ads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($sql)) {
    echo "Table 'bot_ad_fraud_logs' successfully checked/created.\n";
} else {
    echo "Error creating table 'bot_ad_fraud_logs': " . $mysqli->error . "\n";
}

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "Migration script execution complete!\n";
?>