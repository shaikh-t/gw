<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.manage');
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/upload.php';
require_once __DIR__ . '/../../lib/users_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/users'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$roles = $_POST['roles'] ?? [];

$errors = [];
if ($name === '') $errors[] = 'Name required';
if (!validate_email($email)) $errors[] = 'Invalid email';
if ($password !== '' && !validate_password_strength($password)) $errors[] = 'Weak password';

$avatarFilename = null;
if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $res = avatar_upload_handle($_FILES['avatar'], __DIR__ . '/../../public/uploads/avatars');
    if (!$res['ok']) $errors[] = 'Avatar: ' . $res['error'];
    else $avatarFilename = '/public/uploads/avatars/' . $res['filename'];
}

if (!empty($errors)) {
    $_SESSION['flash_errors'] = $errors;
    header('Location: ' . $domain . '/admin/users/edit.php?id=' . $id);
    exit;
}

$update = user_update($id, [
    'name' => $name,
    'email' => $email,
    'password' => $password,
    'roles' => $roles
]);

if (!$update['ok']) {
    $_SESSION['flash_errors'] = [$update['error']];
    header('Location: ' . $domain . '/admin/users/edit.php?id=' . $id);
    exit;
}

if ($avatarFilename) {
    $mysqli->query("UPDATE users SET avatar = '" . $mysqli->real_escape_string($avatarFilename) . "' WHERE id = $id");
    // If current user updated their own avatar, update session
    if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $id) {
        $_SESSION['user']['avatar'] = $avatarFilename;
    }
}

$_SESSION['flash_success'] = 'User updated';
header('Location: ' . $domain . '/admin/users/index.php');
exit;
