<?php
// admin/providers/update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/providers_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/providers'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) { $_SESSION['flash_errors'] = ['Invalid provider id']; header('Location: ' . $domain . '/admin/providers'); exit; }
$data = [
  'name' => $_POST['name'] ?? null,
  'owner_user_id' => $_POST['owner_user_id'] ?? null,
  'email' => $_POST['email'] ?? null,
  'phone' => $_POST['phone'] ?? null,
  'address' => $_POST['address'] ?? null,
  'city' => $_POST['city'] ?? null,
  'state' => $_POST['state'] ?? null,
  'country' => $_POST['country'] ?? null,
  'latitude' => $_POST['latitude'] ?? null,
  'longitude' => $_POST['longitude'] ?? null,
  'description' => $_POST['description'] ?? null,
  'status' => $_POST['status'] ?? null,
  'verification_status' => $_POST['verification_status'] ?? null
];

if (!empty($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $data['logo_file'] = $_FILES['logo'];
}

$res = provider_update($id, $data);
if (!$res['ok']) {
    $_SESSION['flash_errors'] = [$res['error']];
    header('Location: ' . $domain . '/admin/providers/edit.php?id=' . $id);
    exit;
}

$_SESSION['flash_success'] = 'Provider updated';
header('Location: ' . $domain . '/admin/providers/edit.php?id=' . $id);
exit;
