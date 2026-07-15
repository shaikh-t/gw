<?php
// lib/notifications_helper.php
require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/uuid_helper.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/**
 * Get all administrator user IDs.
 */
function get_admin_user_ids(): array {
    global $mysqli;
    $ids = [];
    $res = $mysqli->query("
        SELECT DISTINCT ur.user_id
        FROM user_roles ur
        JOIN roles r ON ur.role_id = r.id
        WHERE r.name IN ('admin', 'Super Admin')
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int)$row['user_id'];
        }
        $res->free();
    }
    return $ids;
}

/**
 * Create a notification record in the database.
 */
function create_notification(int $user_id, string $title, string $message, string $target_url = ''): bool {
    global $mysqli;
    $uuid = generate_uuid();
    $stmt = $mysqli->prepare("INSERT INTO notifications (uuid, user_id, title, message, target_url, is_read) VALUES (?, ?, ?, ?, ?, 0)");
    $stmt->bind_param('sisss', $uuid, $user_id, $title, $message, $target_url);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Notify all administrators.
 */
function notify_admins(string $title, string $message, string $target_url = ''): void {
    $admin_ids = get_admin_user_ids();
    foreach ($admin_ids as $aid) {
        create_notification($aid, $title, $message, $target_url);
    }
}

/**
 * Notify a vendor (provider owner).
 */
function notify_vendor(int $provider_id, string $title, string $message, string $target_url = ''): void {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT owner_user_id FROM providers WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $owner_id = (int)$row['owner_user_id'];
        if ($owner_id > 0) {
            create_notification($owner_id, $title, $message, $target_url);
        }
    }
    $stmt->close();
}

/**
 * Notify a customer.
 */
function notify_customer(int $customer_user_id, string $title, string $message, string $target_url = ''): void {
    create_notification($customer_user_id, $title, $message, $target_url);
}
?>
