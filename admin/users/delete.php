<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.manage');
require_once __DIR__ . '/../../lib/users_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/users'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id_val = $_POST['id'] ?? '';
$user_new = user_find($id_val);
if (!$user_new) { header('Location: ' . $domain . '/admin/users'); exit; }
$id = (int)$user_new['id'];

// Prevent deleting yourself
$curr = current_user();
if ($curr && $curr['uuid'] == $user_new['uuid']) {
    $_SESSION['flash_errors'] = ['You cannot delete your own account'];
    header('Location: ' . $domain . '/admin/users/index.php');
    exit;
}

if (user_delete($id)) {
    $_SESSION['flash_success'] = 'User deleted';
} else {
    $_SESSION['flash_errors'] = ['Delete failed'];
}
header('Location: ' . $domain . '/admin/users/index.php');
exit;
