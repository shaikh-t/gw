<?php
// api/bot-transcript.php
header('Content-Type: application/json; charset=utf-8');

// Error reporting setup for clean JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/permissions.php';

// Ensure the user is logged in
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$user = current_user();

if (!$user) {
    http_response_code(410);
    echo json_encode(['status' => 'error', 'message' => 'User session is not authenticated.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Access Gate validation: must be administrative
$is_admin_staff = is_role('Super Admin') || is_role('admin') || is_role('Manager');
if (!$is_admin_staff) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access to bot transcript audit logs.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if ($session_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid session identifier is required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Query the complete chronological transcript logs for this session ID
$stmt = $mysqli->prepare("SELECT sender, message_content, created_at FROM bot_chat_logs WHERE session_id = ? ORDER BY id ASC");
if ($stmt) {
    $stmt->bind_param('i', $session_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $transcript = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $transcript[] = [
                'sender' => $row['sender'],
                'message_content' => $row['message_content'],
                'created_at' => $row['created_at']
            ];
        }
    }
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'session_id' => $session_id,
        'transcript' => $transcript
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database query preparation failure.'], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
