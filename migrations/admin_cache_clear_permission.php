<?php
// admin/migrations/cache_clear_permission.php
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/uuid_helper.php';

echo "Running cache clear permission seeding...\n";

$p = [
    'id' => 24,
    'name' => 'cache.clear',
    'label' => 'Clear Application Cache',
    'description' => 'Allows administrators to perform a global cache purge of Redis, APCu, and file-based fragments.'
];

// Check if permission already exists
$stmt_chk = $mysqli->prepare("SELECT id FROM permissions WHERE name = ? LIMIT 1");
if ($stmt_chk) {
    $stmt_chk->bind_param('s', $p['name']);
    $stmt_chk->execute();
    $res_chk = $stmt_chk->get_result();
    if ($res_chk->num_rows === 0) {
        $uuid = generate_uuid();
        $stmt_ins = $mysqli->prepare("INSERT INTO permissions (id, uuid, name, label, description) VALUES (?, ?, ?, ?, ?)");
        if ($stmt_ins) {
            $stmt_ins->bind_param('issss', $p['id'], $uuid, $p['name'], $p['label'], $p['description']);
            if ($stmt_ins->execute()) {
                echo "Successfully seeded permission: {$p['name']}\n";

                // Map to Admin (Role ID 1) and Super Admin (Role ID 4)
                $roles_to_map = [1, 4];
                foreach ($roles_to_map as $rid) {
                    $stmt_map = $mysqli->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                    if ($stmt_map) {
                        $stmt_map->bind_param('ii', $rid, $p['id']);
                        $stmt_map->execute();
                        $stmt_map->close();
                        echo "Successfully mapped cache.clear to role ID: $rid\n";
                    }
                }
            } else {
                echo "Failed to seed permission {$p['name']}: " . $mysqli->error . "\n";
            }
            $stmt_ins->close();
        }
    } else {
        echo "Permission {$p['name']} already exists.\n";
    }
    $stmt_chk->close();
}
echo "Cache clear permission seeding complete!\n";
?>