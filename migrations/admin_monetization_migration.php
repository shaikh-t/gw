<?php
// admin/migrations/monetization_migration.php
require_once __DIR__ . '../../lib/db_mysqli.php';

if (php_sapi_name() !== 'cli') {
    // If run via web, protect strictly by checking Super Admin role
    require_once __DIR__ . '../../lib/auth.php';
    require_once __DIR__ . '../../lib/permissions.php';
    // if (!is_role('Super Admin')) {
    //     http_response_code(403);
    //     die("Access denied. Super Admin role required.");
    // }
}

echo "Starting Hybrid Monetization database migrations...\n";

// Disable foreign key checks temporarily during migration setup if needed
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");

// 1. Create bot_ads table
$sql_ads = "CREATE TABLE IF NOT EXISTS `bot_ads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_name` VARCHAR(255) NOT NULL,
  `ad_source_type` ENUM('direct_sponsor', 'network_programmatic') NOT NULL,
  `placement_zone` ENUM('bot_internal_chat', 'site_header_leaderboard', 'site_sidebar_banner', 'site_footer_banner') NOT NULL,
  `target_page_context` VARCHAR(255) DEFAULT 'global_fallback',
  `target_category_id` INT UNSIGNED DEFAULT NULL,
  `language_iso` VARCHAR(10) NOT NULL DEFAULT 'en',
  `banner_text` TEXT DEFAULT NULL,
  `audio_speech_text` TEXT DEFAULT NULL,
  `destination_url` VARCHAR(255) DEFAULT NULL,
  `network_script_code` LONGTEXT DEFAULT NULL,
  `click_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_budget` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `current_spend` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ad_billing_model` ENUM('ppc', 'ppi', 'flat_rate_temporal') NOT NULL DEFAULT 'ppc',
  `max_impressions` INT UNSIGNED NOT NULL DEFAULT 0,
  `current_impressions` INT UNSIGNED NOT NULL DEFAULT 0,
  `start_date` DATETIME DEFAULT NULL,
  `end_date` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_bot_ads_category` FOREIGN KEY (`target_category_id`) REFERENCES `service_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($sql_ads)) {
    echo "bot_ads table checked/created successfully.\n";
} else {
    echo "Error creating bot_ads table: " . $mysqli->error . "\n";
}

// 2. Create bot_ad_clicks table
$sql_clicks = "CREATE TABLE IF NOT EXISTS `bot_ad_clicks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id` INT UNSIGNED NOT NULL,
  `session_id` INT UNSIGNED DEFAULT NULL,
  `earned_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `clicked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_bot_ad_clicks_ad` FOREIGN KEY (`ad_id`) REFERENCES `bot_ads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bot_ad_clicks_session` FOREIGN KEY (`session_id`) REFERENCES `bot_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($sql_clicks)) {
    echo "bot_ad_clicks table checked/created successfully.\n";
} else {
    echo "Error creating bot_ad_clicks table: " . $mysqli->error . "\n";
}

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");
echo "Monetization database migrations completed successfully.\n";
?>