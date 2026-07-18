<?php
// lib/auth.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    $is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure' => $is_secure,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}
require_once __DIR__ . '/db_mysqli.php';

$domain="/gw3/gw";
define('REMEMBER_ME_SECRET', 'GlobalWays_Remember_Me_Secret_2026!');

function get_client_subnet(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $ip);
        return $parts[0] . ':' . $parts[1] . ':' . $parts[2] . ':0';
    }
    return '0.0.0.0';
}

function validate_session_bindings(): bool {
    if (empty($_SESSION['user'])) return true;

    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $subnet = get_client_subnet();

    if (!isset($_SESSION['session_user_agent'])) {
        $_SESSION['session_user_agent'] = $user_agent;
    }
    if (!isset($_SESSION['session_subnet'])) {
        $_SESSION['session_subnet'] = $subnet;
    }

    if ($_SESSION['session_user_agent'] !== $user_agent || $_SESSION['session_subnet'] !== $subnet) {
        logout_user();
        return false;
    }
    return true;
}

function set_remember_me_cookie($user_id) {
    require_once __DIR__ . '/cache_helper.php';
    $token = bin2hex(random_bytes(32));
    $signature = hash_hmac('sha256', $token, REMEMBER_ME_SECRET);
    $cookie_value = $user_id . '|' . $token . '|' . $signature;

    cache_set('remember_token_' . $user_id, $token, 30 * 86400);
    setcookie('remember_me', $cookie_value, time() + (30 * 86400), '/', '', true, true);
}

function check_remember_me_cookie(): bool {
    if (!empty($_SESSION['user'])) return true;
    if (empty($_COOKIE['remember_me'])) return false;

    $parts = explode('|', $_COOKIE['remember_me']);
    if (count($parts) !== 3) return false;

    list($user_id, $token, $signature) = $parts;

    $expected_signature = hash_hmac('sha256', $token, REMEMBER_ME_SECRET);
    if (!hash_equals($expected_signature, $signature)) return false;

    require_once __DIR__ . '/cache_helper.php';
    $cached_token = cache_get('remember_token_' . $user_id);
    if ($cached_token === null || !hash_equals($cached_token, $token)) return false;

    return login_user_by_id((int)$user_id);
}

/**
 * Return current user array or null.
 * Minimal fields: id, name, email, avatar, roles (array), permissions (array)
 */
function current_user(): ?array {
    global $mysqli;

    if (empty($_SESSION['user'])) {
        check_remember_me_cookie();
    }

    if (!validate_session_bindings()) {
        return null;
    }

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
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}
?>