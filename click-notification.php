<?php
// click-notification.php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db_mysqli.php';

require_login();

$user = current_user();
$userId = (int)$user['id'];

$uuid = trim($_GET['uuid'] ?? '');

if ($uuid !== '') {
    // Fetch notification to confirm ownership
    $stmt = $mysqli->prepare("SELECT * FROM `notifications` WHERE `uuid` = ? AND `user_id` = ? LIMIT 1");
    $stmt->bind_param('si', $uuid, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $notif = $res->fetch_assoc();
    $stmt->close();

    if ($notif) {
        // Mark as read
        $stmt_up = $mysqli->prepare("UPDATE `notifications` SET `is_read` = 1 WHERE `id` = ?");
        $stmt_up->bind_param('i', $notif['id']);
        $stmt_up->execute();
        $stmt_up->close();

        // Redirect to target URL
        $target = trim($notif['target_url'] ?? '');
        if ($target !== '') {
            // Check if it's already an absolute path/url
            if (strpos($target, 'http://') === 0 || strpos($target, 'https://') === 0) {
                header('Location: ' . $target);
                exit;
            } else {
                // Ensure no double slashes when prepending $domain
                $prefix = rtrim($domain, '/');
                $suffix = ltrim($target, '/');
                header('Location: ' . $prefix . '/' . $suffix);
                exit;
            }
        }
    }
}

// Fallback: Redirect to standard portals based on role
require_once __DIR__ . '/lib/permissions.php';
if (is_role('admin') || is_role('Super Admin')) {
    header('Location: ' . $domain . '/admin/dashboard.php');
} else if (is_role('provider')) {
    header('Location: ' . $domain . '/vendor/index.php');
} else {
    header('Location: ' . $domain . '/customer/index.php');
}
exit;
?>
