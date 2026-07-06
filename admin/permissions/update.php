<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('roles.manage');
require_once __DIR__ . '/../../lib/role_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.$domain.'/admin/permissions/index.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$label = trim($_POST['label'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($id <= 0 || $name === '' || $label === '') {
    $_SESSION['flash_errors'] = ['Invalid input'];
    header('Location: '.$domain.'/admin/permissions/edit.php?id=' . $id); exit;
}

if (!permission_update($id, $name, $label, $desc)) {
    $_SESSION['flash_errors'] = ['Update failed'];
} else {
    $_SESSION['flash_success'] = 'Permission updated';
}
header('Location: '.$domain.'/admin/permissions/index.php');
exit;
