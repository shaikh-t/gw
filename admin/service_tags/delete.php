<?php
// admin/service_tags/delete.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/service_tags'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) { header('Location: ' . $domain . '/admin/service_tags'); exit; }
if (!$mysqli->query("DELETE FROM service_tags WHERE id = $id")) {
    $_SESSION['flash_errors'] = ['Delete failed: ' . $mysqli->error];
} else {
    $_SESSION['flash_success'] = 'Tag deleted';
}
header('Location: ' . $domain . '/admin/service_tags/index.php');
exit;
