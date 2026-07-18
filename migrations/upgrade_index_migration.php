<?php
// upgrade_index_migration.php
require_once __DIR__ . '/lib/db_mysqli.php';

echo "Running index and column migration for providers...\n";

// 1. Add is_active if not exists
$res1 = $mysqli->query("SHOW COLUMNS FROM providers LIKE 'is_active'");
if ($res1 && $res1->num_rows === 0) {
    if ($mysqli->query("ALTER TABLE providers ADD COLUMN `is_active` TINYINT(1) DEFAULT 1")) {
        echo "Column 'is_active' added to providers.\n";
    } else {
        echo "Could not add is_active column (might already exist or mock DB used).\n";
    }
} else {
    echo "Column 'is_active' already exists or mock DB used.\n";
}

// 2. Add idx_status_is_active index
try {
    $mysqli->query("ALTER TABLE providers ADD INDEX `idx_status_is_active` (`status`, `is_active`)");
    echo "Index idx_status_is_active added successfully.\n";
} catch (Throwable $e) {
    echo "idx_status_is_active addition bypassed or already exists: " . $e->getMessage() . "\n";
}

// 3. Add idx_id_uuid index
try {
    $mysqli->query("ALTER TABLE providers ADD INDEX `idx_id_uuid` (`id`, `uuid`)");
    echo "Index idx_id_uuid added successfully.\n";
} catch (Throwable $e) {
    echo "idx_id_uuid addition bypassed or already exists: " . $e->getMessage() . "\n";
}

echo "Index migration completed.\n";
?>