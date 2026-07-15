<?php
// get-unread-notifications.php
header('Content-Type: application/json');

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db_mysqli.php';

$user = current_user();
if (!$user || empty($user['id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = (int)$user['id'];

// Fetch unread notifications
$stmt = $mysqli->prepare("SELECT `uuid`, `title`, `message`, `target_url`, `created_at` FROM `notifications` WHERE `user_id` = ? AND `is_read` = 0 ORDER BY `created_at` DESC LIMIT 10");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();

$notifications = [];
while ($row = $res->fetch_assoc()) {
    $notifications[] = [
        'uuid' => $row['uuid'],
        'title' => $row['title'],
        'message' => $row['message'],
        'target_url' => $row['target_url'],
        'created_at' => $row['created_at']
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'notifications' => $notifications]);
exit;
?>
