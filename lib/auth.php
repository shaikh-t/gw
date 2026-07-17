<?php
// lib/auth.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    } else {
        session_set_cookie_params(0, '/; SameSite=Strict; Secure; HttpOnly');
    }
    session_start();
}
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

    // Resolve internal ID if needed by legacy code using parameterized prepared statement
    if (empty($user['id']) && !empty($user['uuid'])) {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE uuid = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $user['uuid']);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $user['id'] = (int)$row['id'];
            }
            $stmt->close();
        }
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

    // Retrieve user via prepared statement
    $stmt = $mysqli->prepare("SELECT id, uuid, name, email, avatar FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $u = $res->fetch_assoc();
    $stmt->close();
    if (!$u) return false;

    // load role names via prepared statement
    $roleNames = [];
    $stmt_roles = $mysqli->prepare("
        SELECT r.name
        FROM user_roles ur
        JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = ?
    ");
    if ($stmt_roles) {
        $stmt_roles->bind_param('i', $id);
        $stmt_roles->execute();
        $r = $stmt_roles->get_result();
        while ($rw = $r->fetch_assoc()) {
            $roleNames[] = $rw['name'];
        }
        $stmt_roles->close();
    }

    $_SESSION['user'] = [
        'id' => (int)$u['id'],
        'uuid' => $u['uuid'],
        'name' => $u['name'],
        'email' => $u['email'],
        'avatar' => $u['avatar'] ?? null
    ];

    // session_regenerate_id(true) fires cleanly on successful login
    session_regenerate_id(true);
    return true;
}

/**
 * Attempt login by email + password.
 * Returns true on success.
 */
function attempt_login(string $email, string $password): bool {
    global $mysqli;
    $trimmed_email = trim($email);

    // Retrieve credentials via prepared statement
    $stmt = $mysqli->prepare("SELECT id, password FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param('s', $trimmed_email);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
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
?>