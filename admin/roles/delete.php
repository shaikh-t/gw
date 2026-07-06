<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('roles.manage');
require_once __DIR__ . '/../../lib/role_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/roles/index.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) { header('Location: /admin/roles/index.php'); exit; }

if (!role_delete($id)) {
    $_SESSION['flash_errors'] = ['Delete failed'];
} else {
    $_SESSION['flash_success'] = 'Role deleted';
}
header('Location: /admin/roles/index.php');
exit;
