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
    exit;
}

$userId = (int)$user['id'];

// Explicitly release session lock to prevent blocking any other page requests
session_write_close();

// Restrict continuous execution to a safe 120-second timeout
$start_time = time();
$timeout = 120;

// Flush output buffering for EventSource live streaming compatibility
if (function_exists('ob_get_level') && ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

while (true) {
    // Verify connection_aborted() on each turn to instantly kill orphaned server processes
    if (connection_aborted()) {
        exit;
    }

    // Expiration checks for safety
    if ((time() - $start_time) >= $timeout) {
        break;
    }

    // Query database with strict prepared statements
    $stmt = $mysqli->prepare("SELECT * FROM `notifications` WHERE `user_id` = ? AND `is_read` = 0 ORDER BY `id` ASC");
    if ($stmt) {
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
            ob_flush();
            flush();
        }
    }

    // Mandatory sleep(3); database query regulation interval
    sleep(3);
}
?>
