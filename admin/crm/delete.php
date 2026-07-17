<?php
// admin/crm/delete.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF');
}

$uuid = isset($_POST['uuid']) ? trim($_POST['uuid']) : '';

$stmt = $mysqli->prepare("UPDATE users SET deleted_at = NOW() WHERE uuid = ?");
if ($stmt) {
    $stmt->bind_param('s', $uuid);
    if ($stmt->execute()) {
        $_SESSION['flash_success'] = 'Customer profile soft-deleted successfully.';
    } else {
        $_SESSION['flash_errors'] = 'Failed to delete customer profile.';
    }
    $stmt->close();
} else {
    $_SESSION['flash_errors'] = 'System database error.';
}

header('Location: index.php');
exit;
?>
