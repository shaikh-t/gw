<?php
// admin/crm/update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.manage');
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF');
}

$uuid = trim($_POST['uuid'] ?? '');
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$nationality = trim($_POST['nationality'] ?? '');
$emirate = trim($_POST['emirate'] ?? '');
$goal = trim($_POST['goal'] ?? '');

$customer = null;
$stmt = $mysqli->prepare("SELECT * FROM users WHERE uuid = ? AND deleted_at IS NULL LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $uuid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $customer = $res->fetch_assoc();
    }
    $stmt->close();
}

if (!$customer) {
    $_SESSION['flash_errors'] = 'Customer profile not found.';
    header('Location: index.php');
    exit;
}

// SECURE REMEDIATION GATE: Check if target user has a Super Admin role
$target_user_id = (int)$customer['id'];
$stmt_check = $mysqli->prepare("
    SELECT r.name
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    WHERE ur.user_id = ? AND r.name = 'Super Admin'
");
if ($stmt_check) {
    $stmt_check->bind_param('i', $target_user_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check && $res_check->num_rows > 0) {
        // Only a logged-in Super Admin is authorized to edit another Super Admin
        if (!is_role('Super Admin')) {
            http_response_code(403);
            die("Security Escalation Blocked: Non-Super Admin cannot modify a Super Admin profile.");
        }
    }
    $stmt_check->close();
}

$userId = (int)$customer['id'];
$errors = [];
if ($name === '') $errors[] = 'Name is required';
if (!validate_email($email)) $errors[] = 'Invalid email address';

// Ensure password is safe if provided
if ($password !== '' && !validate_password_strength($password)) {
    $errors[] = 'New password is weak';
}

// Check if email already exists on another account
$stmt_chk = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
if ($stmt_chk) {
    $stmt_chk->bind_param('si', $email, $userId);
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
    header('Location: edit.php?uuid=' . $uuid);
    exit;
}

// Prepare update queries
$stmt_up = $mysqli->prepare("UPDATE users SET name = ?, email = ?, nationality = ?, emirate = ?, goal = ? WHERE id = ?");
if ($stmt_up) {
    $stmt_up->bind_param('sssssi', $name, $email, $nationality, $emirate, $goal, $userId);
    $stmt_up->execute();
    $stmt_up->close();
}

if ($password !== '') {
    $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
    $stmt_pw = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    if ($stmt_pw) {
        $stmt_pw->bind_param('si', $hashed_pass, $userId);
        $stmt_pw->execute();
        $stmt_pw->close();
    }
}

if ($avatarFilename) {
    $stmt_av = $mysqli->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    if ($stmt_av) {
        $stmt_av->bind_param('si', $avatarFilename, $userId);
        $stmt_av->execute();
        $stmt_av->close();
    }
}

$_SESSION['flash_success'] = 'Customer profile updated successfully.';
header('Location: index.php');
exit;
?>
