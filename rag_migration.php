<?php
// rag_migration.php
require_once __DIR__ . '/lib/db_mysqli.php';

echo "Starting Local RAG & Analytical database migrations...\n";

if (!isset($mysqli) || $mysqli->connect_errno) {
    die("Database connection is unavailable.\n");
}

// Create 'bot_failed_questions' table
$sql = "CREATE TABLE IF NOT EXISTS `bot_failed_questions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL DEFAULT NULL,
  `language_iso` VARCHAR(10) NOT NULL,
  `unanswered_question` TEXT NOT NULL,
  `page_context_url` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_failed_questions_session` FOREIGN KEY (`session_id`) REFERENCES `bot_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($sql)) {
    echo "Table 'bot_failed_questions' checked/created successfully with foreign key referencing bot_sessions.\n";
} else {
    echo "Error creating 'bot_failed_questions' table: " . $mysqli->error . "\n";
}

echo "Migrations completed successfully!\n";
?>
