<?php
// admin/migrations/rbac_seeding.php
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/uuid_helper.php';

echo "Starting RBAC permission seeding...\n";

$new_perms = [
    [
        'id' => 21,
        'name' => 'can_manage_ads',
        'label' => 'Manage Ads',
        'description' => 'Allows access to admin/settings/bot_ads.php and ad creation forms'
    ],
    [
        'id' => 22,
        'name' => 'can_view_failed_queries',
        'label' => 'View Failed Queries',
        'description' => 'Allows access to admin/crm/failed-questions.php'
    ],
    [
        'id' => 23,
        'name' => 'can_edit_knowledge_base',
        'label' => 'Edit Knowledge Base',
        'description' => 'Allows access to our local PDF/text CRUD manager at admin/crm/knowledge-base.php'
    ]
];

foreach ($new_perms as $p) {
    // Check if permission already exists
    $stmt_chk = $mysqli->prepare("SELECT id FROM permissions WHERE name = ? LIMIT 1");
    $stmt_chk->bind_param('s', $p['name']);
    $stmt_chk->execute();
    $res_chk = $stmt_chk->get_result();
    if ($res_chk->num_rows === 0) {
        $uuid = generate_uuid();
        $stmt_ins = $mysqli->prepare("INSERT INTO permissions (id, uuid, name, label, description) VALUES (?, ?, ?, ?, ?)");
        $stmt_ins->bind_param('issss', $p['id'], $uuid, $p['name'], $p['label'], $p['description']);
        if ($stmt_ins->execute()) {
            echo "Successfully seeded permission: {$p['name']}\n";

            // Map to Super Admin (Role ID 1)
            $stmt_map = $mysqli->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, ?)");
            $stmt_map->bind_param('i', $p['id']);
            $stmt_map->execute();
            $stmt_map->close();
        } else {
            echo "Failed to seed permission {$p['name']}: " . $mysqli->error . "\n";
        }
        $stmt_ins->close();
    } else {
        echo "Permission {$p['name']} already exists.\n";
    }
    $stmt_chk->close();
}

echo "RBAC Seeding complete!\n";
?>