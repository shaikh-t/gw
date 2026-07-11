<?php
// admin/blog/delete.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('blog.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF');
}

$uuid = $_POST['id'] ?? '';
$stmt = $mysqli->prepare("DELETE FROM blog_posts WHERE uuid = ?");
$stmt->bind_param('s', $uuid);

if ($stmt->execute()) {
    $_SESSION['flash_success'] = 'Blog post deleted successfully.';
} else {
    $_SESSION['flash_errors'] = 'Database error: ' . $mysqli->error;
}
$stmt->close();

header('Location: index.php');
exit;
