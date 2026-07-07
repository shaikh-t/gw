<?php
// lib/permissions.php
require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function load_user_permissions_mysqli($userId): array {
    global $mysqli;
    $perms = [];

    $uid = is_numeric($userId) ? intval($userId) : null;
    if (!$uid) {
        $res = $mysqli->query("SELECT id FROM users WHERE uuid = '" . $mysqli->real_escape_string($userId) . "' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $uid = (int)$row['id'];
        }
        if ($res && !is_bool($res)) $res->free();
    }
    if (!$uid) return [];

    // load role ids
    $roleIds = [];
    $sql = "SELECT role_id FROM user_roles WHERE user_id = $uid";
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
    static $request_perms = null;
    if (!function_exists('current_user')) return false;
    $user = current_user();
    if (!$user) return false;

    if ($request_perms === null) {
        // Security: Fetch fresh permissions from DB on first check of request to prevent session tampering
        // Prefer UUID for lookup if available in session
        $userId = $user['uuid'] ?? (int)$user['id'];
        $request_perms = load_user_permissions_mysqli($userId);
    }

    return in_array($permission, $request_perms, true);
}


function is_role(string $role): bool {
    static $request_roles = null;
    global $mysqli;
    $user = current_user();
    if (!$user) return false;

    if ($request_roles === null) {
        $uid = null;
        if (!empty($user['uuid'])) {
            $res = $mysqli->query("SELECT id FROM users WHERE uuid = '" . $mysqli->real_escape_string($user['uuid']) . "' LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) $uid = (int)$row['id'];
            if ($res && !is_bool($res)) $res->free();
        } else {
            $uid = intval($user['id']);
        }

        if (!$uid) return false;

        $request_roles = [];
        $sql = "SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = $uid";
        if ($res = $mysqli->query($sql)) {
            while ($row = $res->fetch_assoc()) $request_roles[] = $row['name'];
            $res->free();
        }
    }

    return in_array($role, $request_roles, true);
}
