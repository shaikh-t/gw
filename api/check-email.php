<?php
// api/check-email.php
require_once __DIR__ . '/../lib/db_mysqli.php';

header('Content-Type: application/json');

$email = trim($_GET['email'] ?? '');

if ($email === '') {
    echo json_encode(['available' => true]);
    exit;
}

$stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = ($res && $res->num_rows > 0);
    $stmt->close();
    echo json_encode(['available' => !$exists]);
} else {
    echo json_encode(['available' => true]);
}
exit;
?>