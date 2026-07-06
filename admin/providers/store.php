<?php
// admin/providers/store.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/providers_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/providers'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$data = [
    'name' => $_POST['name'] ?? '',
    'owner_user_id' => $_POST['owner_user_id'] ?? null,
    'email' => $_POST['email'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'address' => $_POST['address'] ?? '',
    'city' => $_POST['city'] ?? '',
    'state' => $_POST['state'] ?? '',
    'country' => $_POST['country'] ?? '',
    'description' => $_POST['description'] ?? '',
    'status' => $_POST['status'] ?? 'draft'
];

if (!empty($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $data['logo_file'] = $_FILES['logo'];
}

$res = provider_create($data);
if (!$res['ok']) {
    $_SESSION['flash_errors'] = [$res['error']];
    header('Location: ' . $domain . '/admin/providers/create.php');
    exit;
}

$_SESSION['flash_success'] = 'Provider created';
header('Location: ' . $domain . '/admin/providers/index.php');
exit;
