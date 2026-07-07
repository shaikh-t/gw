<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('roles.manage');
require_once __DIR__ . '/../../lib/role_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/roles/index.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id_val = $_POST['id'] ?? '';
$role = role_find($id_val);
if (!$role) { header('Location: /admin/roles/index.php'); exit; }
$id = (int)$role['id'];
$uuid = $role['uuid'];

$name = trim($_POST['name'] ?? '');
$label = trim($_POST['label'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($name === '' || $label === '') {
    $_SESSION['flash_errors'] = ['Invalid input'];
    header('Location: /admin/roles/edit.php?uuid=' . $uuid); exit;
}

if (!role_update($id, $name, $label, $desc)) {
    $_SESSION['flash_errors'] = ['Update failed'];
} else {
    $_SESSION['flash_success'] = 'Role updated';
}
header('Location: /admin/roles/edit.php?uuid=' . $uuid);
exit;
