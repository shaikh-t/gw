<?php
// migrations/bot_approved_keywords_migration.php
require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/uuid_helper.php';

echo "Starting Approved Keywords Database migration...\n";

$mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");

// Create bot_approved_keywords table
$sql = "CREATE TABLE IF NOT EXISTS `bot_approved_keywords` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `keyword_token` VARCHAR(255) NOT NULL UNIQUE,
  `language_code` VARCHAR(10) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($sql)) {
    echo "bot_approved_keywords table created successfully.\n";
} else {
    echo "Error creating bot_approved_keywords table: " . $mysqli->error . "\n";
}

// Seed manage_bot_keywords permission
$pname = 'manage_bot_keywords';
$plabel = 'Manage Bot Keywords';
$res = $mysqli->query("SELECT id FROM permissions WHERE name = '$pname' LIMIT 1");
$pid = null;
if ($res && $row = $res->fetch_assoc()) {
    $pid = $row['id'];
    echo "Permission '$pname' already exists.\n";
} else {
    $puuid = generate_uuid();
    $sql = "INSERT INTO permissions (uuid, name, label, description) VALUES ('$puuid', '$pname', '$plabel', 'Allows listing, creating and deleting bot approved keywords')";
    if ($mysqli->query($sql)) {
        $pid = $mysqli->insert_id;
        echo "Permission '$pname' created successfully (ID: $pid).\n";
    } else {
        echo "Error creating permission '$pname': " . $mysqli->error . "\n";
    }
}

if ($pid) {
    $roles_to_assign = [1, 4]; // Admin and Super Admin
    foreach ($roles_to_assign as $rid) {
        $res_role = $mysqli->query("SELECT id FROM roles WHERE id = $rid LIMIT 1");
        if ($res_role && $res_role->num_rows > 0) {
            $mysqli->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES ($rid, $pid)");
            echo "Permission '$pname' assigned to role ID $rid.\n";
        }
    }
}

// Seed keywords
$default_keywords = [
    'business', 'setup', 'company', 'immigration', 'visa', 'office',
    'consultation', 'start', 'launch', 'open', 'incorporate', 'firm',
    'services', 'meeting', 'schedule', 'register', 'welcome', 'funnel',
    'selection', 'dispatch', 'visit', 'tourism', 'license', 'permit',
    'emirates', 'national', 'stamping', 'attestation', 'renewal',
    'consultant', 'advisory', 'partner', 'booking'
];

foreach ($default_keywords as $keyword) {
    $stmt = $mysqli->prepare("INSERT IGNORE INTO bot_approved_keywords (keyword_token, language_code) VALUES (?, 'en')");
    if ($stmt) {
        $stmt->bind_param('s', $keyword);
        $stmt->execute();
        $stmt->close();
    }
}
echo "Default keywords seeded successfully.\n";

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");
echo "Approved Keywords migration completed successfully.\n";
