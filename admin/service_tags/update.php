<?php
// admin/service_tags/update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/service_tags'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
if ($id <= 0 || $name === '') {
    $_SESSION['flash_errors'] = ['Invalid input'];
    header('Location: ' . $domain . '/admin/service_tags/edit.php?uuid=' . $uuid); exit;
}

$sql = "UPDATE service_tags SET name = '" . $mysqli->real_escape_string($name) . "' WHERE id = $id";
if (!$mysqli->query($sql)) {
    $_SESSION['flash_errors'] = ['Update failed: ' . $mysqli->error];
} else {
    $_SESSION['flash_success'] = 'Tag updated';
}
header('Location: ' . $domain . '/admin/service_tags/index.php');
exit;
