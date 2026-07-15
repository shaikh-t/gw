<?php
// sse-notifications.php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db_mysqli.php';

$user = current_user();
if (!$user || empty($user['id'])) {
    echo "data: " . json_encode(['error' => 'Unauthorized']) . "\n\n";
    ob_flush();
    flush();
    exit;
}

$userId = (int)$user['id'];

// Disable execution time limit
set_time_limit(0);

// Keep track of notification UUIDs sent during this connection session
$sent_uuids = [];

// Infinite loop to stream new notifications
while (true) {
    if (connection_aborted()) {
        break;
    }

    // Check for any unread notifications
    $stmt = $mysqli->prepare("SELECT * FROM `notifications` WHERE `user_id` = ? AND `is_read` = 0 ORDER BY `id` ASC");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();

    $new_notifications = [];
    while ($row = $res->fetch_assoc()) {
        $uuid = $row['uuid'];
        if (!in_array($uuid, $sent_uuids)) {
            $new_notifications[] = [
                'uuid' => $uuid,
                'title' => $row['title'],
                'message' => $row['message'],
                'target_url' => $row['target_url']
            ];
            $sent_uuids[] = $uuid;
        }
    }
    $stmt->close();

    if (!empty($new_notifications)) {
        // Send to frontend as event data
        echo "data: " . json_encode($new_notifications) . "\n\n";
        ob_flush();
        flush();
    }

    // Sleep for 2 seconds before polling again
    sleep(2);
}
?>
