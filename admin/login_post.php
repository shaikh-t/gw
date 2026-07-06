<?php
// login_post.php
require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/auth.php'; // contains login_user_by_id()

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: login.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$res = $mysqli->query("SELECT id, password FROM users WHERE email = '" . $mysqli->real_escape_string($email) . "' LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        // update last_login
        $mysqli->query("UPDATE users SET last_login = NOW() WHERE id = " . intval($row['id']));
        // login user and populate session
        if (login_user_by_id((int)$row['id'])) {
            header('Location: dashboard.php');
            exit;
        }
    }
}
$_SESSION['flash_errors'] = ['Invalid email or password'];
header('Location: login.php');
exit;
