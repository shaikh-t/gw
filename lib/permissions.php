<?php
// lib/permissions.php
require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function load_user_permissions_mysqli(int $userId): array {
    global $mysqli;
    $perms = [];

    // load role ids
    $roleIds = [];
    $sql = "SELECT role_id FROM user_roles WHERE user_id = " . intval($userId);
    if ($res = $mysqli->query($sql)) {
        while ($r = $res->fetch_assoc()) $roleIds[] = (int)$r['role_id'];
        $res->free();
    }

    // permissions via roles
    if (!empty($roleIds)) {
        $in = implode(',', array_map('intval', $roleIds));
        $sql = "SELECT DISTINCT p.name FROM permissions p JOIN role_permissions rp ON rp.permission_id = p.id WHERE rp.role_id IN ($in)";
        if ($res = $mysqli->query($sql)) {
            while ($r = $res->fetch_assoc()) $perms[] = $r['name'];
            $res->free();
        }
    }

    // user-specific overrides
    // $sql = "SELECT p.name, up.allow FROM user_permissions_override up JOIN permissions p ON p.id = up.permission_id WHERE up.user_id = " . intval($userId);
    // if ($res = $mysqli->query($sql)) {
    //     while ($r = $res->fetch_assoc()) {
    //         if ((int)$r['allow'] === 1) {
    //             $perms[] = $r['name'];
    //         } else {
    //             $perms = array_values(array_filter($perms, function($x) use ($r) { return $x !== $r['name']; }));
    //         }
    //     }
    //     $res->free();
    // }

    return array_values(array_unique($perms));
}

// lib/permissions.php (ensure TTL logic)
function can(string $permission): bool {
    if (!function_exists('current_user')) return false;
    $user = current_user();
    if (!$user) return false;

    $now = time();
    $ttl = 300; // seconds

    // If not loaded or expired, reload from DB
    if (empty($_SESSION['user']['_perms_loaded_at']) || ($now - ($_SESSION['user']['_perms_loaded_at'] ?? 0)) > $ttl) {
        $_SESSION['user']['permissions'] = load_user_permissions_mysqli((int)$user['id']);
        $_SESSION['user']['_perms_loaded_at'] = $now;
    }

    return in_array($permission, $_SESSION['user']['permissions'] ?? [], true);
}


function is_role(string $role): bool {
    $user = current_user();
    if (!$user) return false;
    $roles = $user['roles'] ?? ($_SESSION['user']['roles'] ?? []);
    return in_array($role, (array)$roles, true);
}
