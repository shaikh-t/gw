<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('roles.manage');
require_once __DIR__ . '/../../lib/role_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.$domain.'/admin/roles/index.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id_val = $_POST['id'] ?? '';
$role = role_find($id_val);
if (!$role) { header('Location: '.$domain.'/admin/roles/index.php'); exit; }
$id = (int)$role['id'];
$uuid = $role['uuid'];

$perms = $_POST['permissions'] ?? [];
$perms = array_map('intval', (array)$perms);

if (!role_sync_permissions($id, $perms)) {
    $_SESSION['flash_errors'] = ['Sync failed'];
} else {
    $_SESSION['flash_success'] = 'Permissions synced';
}
header("Location: $domain/admin/roles/edit.php?uuid=$uuid");
exit;
