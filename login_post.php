<?php
// login_post.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/permissions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: login.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (attempt_login($email, $password)) {
    $mysqli->query("UPDATE users SET last_login = NOW() WHERE id = " . intval($_SESSION['user']['id']));

    // Redirect based on role
    if (is_role('admin') || is_role('Super Admin')) {
        header('Location: ' . $domain . '/admin/dashboard.php');
    } else if (is_role('provider')) {
        header('Location: ' . $domain . '/vendor/index.php');
    } else {
        header('Location: ' . $domain . '/admin/dashboard.php');
    }
    exit;
}

$_SESSION['flash_errors'] = ['Invalid email or password'];
header('Location: login.php');
exit;
