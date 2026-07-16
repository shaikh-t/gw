<?php
// login_post.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/permissions.php';
require_once __DIR__ . '/lib/anti_spam_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: login.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

// Finding 2: Check for Login DoS Rate Limiting (5 attempts in 5 minutes)
if (is_login_throttled($mysqli)) {
    $_SESSION['flash_errors'] = ['Too many failed login attempts. Please wait 5 minutes before trying again.'];
    header('Location: login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (attempt_login($email, $password)) {
    // Clear out past failed login attempts for this specific IP address upon successful login
    clear_failed_logins($mysqli);

    $mysqli->query("UPDATE users SET last_login = NOW() WHERE uuid = '" . $mysqli->real_escape_string($_SESSION['user']['uuid']) . "'");

    // If we have a target redirection page in session, redirect there
    if (!empty($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
        exit;
    }

    // Redirect based on role
    if (is_role('admin') || is_role('Super Admin')) {
        header('Location: ' . $domain . '/admin/dashboard.php');
    } else if (is_role('provider')) {
        header('Location: ' . $domain . '/vendor/index.php');
    } else {
        require_once __DIR__ . '/lib/customer_helpers.php';
        $u = current_user();
        if ($u && !empty($u['id'])) {
            ensure_customer_seeded((int)$u['id']);
        }
        header('Location: ' . $domain . '/customer/index.php');
    }
    exit;
} else {
    // Record failed attempt for throttling
    log_failed_login($mysqli);
}

$_SESSION['flash_errors'] = ['Invalid email or password'];
header('Location: login.php');
exit;
?>