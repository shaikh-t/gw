<?php
// admin/service_tags/update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/service_tags'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id_val = $_POST['id'] ?? '';
// Simple lookup for tags as they don't have a dedicated helper find yet
$res = $mysqli->query("SELECT id, uuid FROM service_tags WHERE id = " . intval($id_val) . " OR uuid = '" . $mysqli->real_escape_string($id_val) . "' LIMIT 1");
$tag = $res ? $res->fetch_assoc() : null;

if (!$tag) {
    $_SESSION['flash_errors'] = ['Tag not found'];
    header('Location: ' . $domain . '/admin/service_tags/index.php'); exit;
}

$id = (int)$tag['id'];
$uuid = $tag['uuid'];
$name = trim($_POST['name'] ?? '');

if ($name === '') {
    $_SESSION['flash_errors'] = ['Name required'];
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
