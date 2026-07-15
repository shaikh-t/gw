<?php
// sse-notifications.php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db_mysqli.php';

$user = current_user();
// Explicitly release session lock to prevent blocking any other page requests
session_write_close();

if (!$user || empty($user['id'])) {
    echo "data: " . json_encode(['error' => 'Unauthorized']) . "\n\n";
    exit;
}

$userId = (int)$user['id'];

// Check for any unread notifications
$stmt = $mysqli->prepare("SELECT * FROM `notifications` WHERE `user_id` = ? AND `is_read` = 0 ORDER BY `id` ASC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();

$new_notifications = [];
while ($row = $res->fetch_assoc()) {
    $new_notifications[] = [
        'uuid' => $row['uuid'],
        'title' => $row['title'],
        'message' => $row['message'],
        'target_url' => $row['target_url']
    ];
}
$stmt->close();

if (!empty($new_notifications)) {
    echo "data: " . json_encode($new_notifications) . "\n\n";
}

// Exit immediately to prevent blocking the single-threaded PHP development server.
// The standard EventSource client will automatically reconnect after a short delay (usually 3 seconds).
exit;
?>
