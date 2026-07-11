<?php
// admin/permissions/update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('roles.manage');
require_once __DIR__ . '/../../lib/role_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.$domain.'/admin/permissions/index.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id_val = $_POST['id'] ?? '';
$perm = permission_find($id_val);
if (!$perm) {
    $_SESSION['flash_errors'] = ['Permission not found'];
    header('Location: '.$domain.'/admin/permissions/index.php'); exit;
}

$id = (int)$perm['id'];
$uuid = $perm['uuid'];
$name = trim($_POST['name'] ?? '');
$label = trim($_POST['label'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($name === '' || $label === '') {
    $_SESSION['flash_errors'] = ['Name and Label are required'];
    header('Location: '.$domain.'/admin/permissions/edit.php?uuid=' . $uuid); exit;
}

if (!permission_update($id, $name, $label, $desc)) {
    $_SESSION['flash_errors'] = ['Update failed'];
} else {
    $_SESSION['flash_success'] = 'Permission updated';
}
header('Location: '.$domain.'/admin/permissions/index.php');
exit;
