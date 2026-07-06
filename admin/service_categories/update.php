<?php
// admin/service_categories/update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/service_categories'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($id <= 0 || $name === '') {
    $_SESSION['flash_errors'] = ['Invalid input'];
    header('Location: ' . $domain . '/admin/service_categories/edit.php?id=' . $id); exit;
}

$sql = "UPDATE service_categories SET name = '" . $mysqli->real_escape_string($name) . "', slug = '" . $mysqli->real_escape_string($slug) . "', description = '" . $mysqli->real_escape_string($desc) . "' WHERE id = $id";
if (!$mysqli->query($sql)) {
    $_SESSION['flash_errors'] = ['Update failed: ' . $mysqli->error];
} else {
    $_SESSION['flash_success'] = 'Category updated';
}
header('Location: ' . $domain . '/admin/service_categories/index.php');
exit;
