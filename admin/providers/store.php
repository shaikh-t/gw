<?php
// admin/providers/store.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/providers_helpers.php';
require_once __DIR__ . '/../../lib/users_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/providers'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$owner_user_id = !empty($_POST['owner_user_id']) ? intval($_POST['owner_user_id']) : null;
$new_email = trim($_POST['new_user_email'] ?? '');
$new_pass = $_POST['new_user_password'] ?? '';

if (!$owner_user_id && $new_email && $new_pass) {
    require_once __DIR__ . '/../../lib/validation.php';
    if (!validate_email($new_email)) {
        $_SESSION['flash_errors'] = ['Invalid new user email'];
        header('Location: ' . $domain . '/admin/providers/create.php'); exit;
    }
    // Check if email exists
    if (user_find_by_email($new_email)) {
        $_SESSION['flash_errors'] = ['User with this email already exists'];
        header('Location: ' . $domain . '/admin/providers/create.php'); exit;
    }

    // Find provider role id
    $roleRes = $mysqli->query("SELECT id FROM roles WHERE name = 'provider' LIMIT 1");
    $providerRoleId = null;
    if ($roleRes && $row = $roleRes->fetch_assoc()) {
        $providerRoleId = (int)$row['id'];
        $roleRes->free();
    }

    if (!$providerRoleId) {
        $_SESSION['flash_errors'] = ['Provider role not found in system'];
        header('Location: ' . $domain . '/admin/providers/create.php'); exit;
    }

    $nameForUser = $_POST['name'] ?? 'Provider User';
    $userRes = user_create($nameForUser, $new_email, $new_pass, [$providerRoleId]);
    if (!$userRes['ok']) {
        $_SESSION['flash_errors'] = ['Failed to create user: ' . $userRes['error']];
        header('Location: ' . $domain . '/admin/providers/create.php'); exit;
    }
    $owner_user_id = $userRes['id'];
}

$data = [
    'name' => $_POST['name'] ?? '',
    'owner_user_id' => $owner_user_id,
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
