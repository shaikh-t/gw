<?php
// lib/auth.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/db_mysqli.php';

$domain="/gw3/gw";
/**
 * Return current user array or null.
 * Minimal fields: id, name, email, avatar, roles (array), permissions (array)
 */
function current_user(): ?array {
    global $mysqli;
    if (empty($_SESSION['user'])) return null;
    $user = $_SESSION['user'];

    // Resolve internal ID if needed by legacy code
    if (empty($user['id']) && !empty($user['uuid'])) {
        $uuid = $mysqli->real_escape_string($user['uuid']);
        $res = $mysqli->query("SELECT id FROM users WHERE uuid = '$uuid' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $user['id'] = (int)$row['id'];
        }
        if ($res && !is_bool($res)) $res->free();
    }

    return $user;
}


function require_login() {
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function login_user_by_id(int $userId): bool {
    global $mysqli;
    $id = intval($userId);

    $sql = "SELECT id, uuid, name, email, avatar FROM users WHERE id = $id LIMIT 1";
    $res = $mysqli->query($sql);
    if (!$res) return false;
    $u = $res->fetch_assoc();
    $res->free();
    if (!$u) return false;

    // load role names
    $roleNames = [];
    $r = $mysqli->query("SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = $id");
    if ($r) {
        while ($rw = $r->fetch_assoc()) $roleNames[] = $rw['name'];
        $r->free();
    }

    $_SESSION['user'] = [
        'uuid' => $u['uuid'],
        'name' => $u['name'],
        'email' => $u['email'],
        'avatar' => $u['avatar'] ?? null
    ];
    session_regenerate_id(true);
    return true;
}

/**
 * Attempt login by email + password.
 * Returns true on success.
 */
function attempt_login(string $email, string $password): bool {
    global $mysqli;
    $emailEsc = $mysqli->real_escape_string(trim($email));
    $sql = "SELECT id, password FROM users WHERE email = '" . $emailEsc . "' LIMIT 1";
    $res = $mysqli->query($sql);
    if (!$res) return false;
    $row = $res->fetch_assoc();
    $res->free();
    if (!$row) return false;
    if (password_verify($password, $row['password'])) {
        return login_user_by_id((int)$row['id']);
    }
    return false;
}

/**
 * Logout current user and clear session user data.
 */
function logout_user(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    foreach($_SESSION as $k=>$v) {
        unset($_SESSION[$k]);
    }
    session_destroy();
    unset($_SESSION['user']);
    unset($_SESSION['_csrf_token']);
    session_regenerate_id(true);
}
