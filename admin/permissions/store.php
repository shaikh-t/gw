<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('roles.manage');
require_once __DIR__ . '/../../lib/role_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.$domain.'/admin/permissions/index.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$name = trim($_POST['name'] ?? '');
$label = trim($_POST['label'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($name === '' || $label === '') {
    $_SESSION['flash_errors'] = ['Name and label are required'];
    header('Location: '.$domain.'/admin/permissions/create.php'); exit;
}

$id = permission_create($name, $label, $desc);
if ($id === false) {
    // $_SESSION['flash_errors'] = ['Create failed'];
    header('Location: '.$domain.'/admin/permissions/create.php'); exit;
}

$_SESSION['flash_success'] = 'Permission created';
header('Location: '.$domain.'/admin/permissions/index.php?id=' . intval($id));
exit;
