<?php
// admin/crm/store.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.manage');
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/upload.php';
require_once __DIR__ . '/../../lib/users_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF');
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$nationality = trim($_POST['nationality'] ?? '');
$emirate = trim($_POST['emirate'] ?? '');
$goal = trim($_POST['goal'] ?? '');

$errors = [];
if ($name === '') $errors[] = 'Name is required';
if (!validate_email($email)) $errors[] = 'Invalid email address';
if (!validate_password_strength($password)) $errors[] = 'Password is weak';

// Check if email already exists
$stmt_chk = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
if ($stmt_chk) {
    $stmt_chk->bind_param('s', $email);
    $stmt_chk->execute();
    $res_chk = $stmt_chk->get_result();
    if ($res_chk && $res_chk->num_rows > 0) {
        $errors[] = 'An account with this email address already exists.';
    }
    $stmt_chk->close();
}

$avatarFilename = null;
if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $res = avatar_upload_handle($_FILES['avatar'], __DIR__ . '/../../public/uploads/avatars');
    if (!$res['ok']) {
        $errors[] = 'Avatar upload failed: ' . $res['error'];
    } else {
        $avatarFilename = '/public/uploads/avatars/' . $res['filename'];
    }
}

if (!empty($errors)) {
    $_SESSION['flash_errors'] = $errors;
    header('Location: create.php');
    exit;
}

// Find default customer/viewer role
$stmt_r = $mysqli->prepare("SELECT id FROM roles WHERE name = 'viewer' LIMIT 1");
$stmt_r->execute();
$res_r = $stmt_r->get_result();
$viewer_role_id = 3; // fallback default
if ($res_r && $row_r = $res_r->fetch_assoc()) {
    $viewer_role_id = (int)$row_r['id'];
}
$stmt_r->close();

// Create user
$create = user_create($name, $email, $password, [$viewer_role_id]);
if (!$create['ok']) {
    $_SESSION['flash_errors'] = [$create['error']];
    header('Location: create.php');
    exit;
}

$userId = (int)$create['id'];

// Update onboarding fields and avatar (if uploaded)
$stmt_up = $mysqli->prepare("UPDATE users SET nationality = ?, emirate = ?, goal = ? WHERE id = ?");
if ($stmt_up) {
    $stmt_up->bind_param('sssi', $nationality, $emirate, $goal, $userId);
    $stmt_up->execute();
    $stmt_up->close();
}

if ($avatarFilename) {
    $stmt_av = $mysqli->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    if ($stmt_av) {
        $stmt_av->bind_param('si', $avatarFilename, $userId);
        $stmt_av->execute();
        $stmt_av->close();
    }
}

$_SESSION['flash_success'] = 'Customer profile created successfully.';
header('Location: index.php');
exit;
?>
