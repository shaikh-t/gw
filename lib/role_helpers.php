<?php
// lib/role_helpers.php
require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/uuid_helper.php';

/* ---------- Roles ---------- */
function roles_all(): array {
    global $mysqli;
    $out = [];
    $sql = "SELECT * FROM roles ORDER BY label";
    if ($res = $mysqli->query($sql)) { while ($r = $res->fetch_assoc()) $out[] = $r; $res->free(); }
    return $out;
}

function role_find($idOrUuid) {
    global $mysqli;
    if (is_numeric($idOrUuid)) {
        $res = $mysqli->query("SELECT * FROM roles WHERE id = " . intval($idOrUuid) . " LIMIT 1");
    } else {
        $uuid = $mysqli->real_escape_string($idOrUuid);
        $res = $mysqli->query("SELECT * FROM roles WHERE uuid = '$uuid' LIMIT 1");
    }
    if ($res) { $row = $res->fetch_assoc(); $res->free(); return $row ?: null; }
    return null;
}

function role_create(string $name, string $label, string $desc = '') {
    global $mysqli;
    $name = $mysqli->real_escape_string($name);
    $label = $mysqli->real_escape_string($label);
    $desc = $mysqli->real_escape_string($desc);
    $uuid = generate_uuid();
    $sql = "INSERT INTO roles (uuid,name,label,description,created_at) VALUES ('$uuid','$name','$label','$desc',NOW())";
    if ($mysqli->query($sql)) return $mysqli->insert_id;
    return false;
}

function role_update(int $id, string $name, string $label, string $desc = ''): bool {
    global $mysqli;
    $id = intval($id);
    $name = $mysqli->real_escape_string($name);
    $label = $mysqli->real_escape_string($label);
    $desc = $mysqli->real_escape_string($desc);
    return (bool)$mysqli->query("UPDATE roles SET name='$name', label='$label', description='$desc' WHERE id=$id");
}

function role_delete(int $id): bool {
    global $mysqli;
    $id = intval($id);
    return (bool)$mysqli->query("DELETE FROM roles WHERE id=$id");
}

/* ---------- Permissions ---------- */
function permissions_all(): array {
    global $mysqli;
    $out = [];
    $sql = "SELECT * FROM permissions ORDER BY label";
    if ($res = $mysqli->query($sql)) { while ($r = $res->fetch_assoc()) $out[] = $r; $res->free(); }
    return $out;
}

function permission_find($idOrUuid) {
    global $mysqli;
    if (is_numeric($idOrUuid)) {
        $res = $mysqli->query("SELECT * FROM permissions WHERE id = " . intval($idOrUuid) . " LIMIT 1");
    } else {
        $uuid = $mysqli->real_escape_string($idOrUuid);
        $res = $mysqli->query("SELECT * FROM permissions WHERE uuid = '$uuid' LIMIT 1");
    }
    if ($res) { $row = $res->fetch_assoc(); $res->free(); return $row ?: null; }
    return null;
}

function permission_create($name, $label, $description = '') {
    global $mysqli;
    $name_sql = $mysqli->real_escape_string($name);
    $label_sql = $mysqli->real_escape_string($label);
    $desc_sql = $mysqli->real_escape_string($description);

    // check if permission already exists
    $res = $mysqli->query("SELECT id FROM permissions WHERE name='$name_sql' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $res->free();
    $_SESSION['flash_errors'] = ['Permission already exists'];
    return false;
    }

    $uuid = generate_uuid();
    $sql = "INSERT INTO permissions (uuid,name,label,description,created_at) VALUES ('$uuid','$name_sql','$label_sql','$desc_sql',NOW())";
    if (!$mysqli->query($sql)) {
        $_SESSION['flash_errors'][] = $mysqli->error;
        return false;
    }
    return $mysqli->insert_id;
}

function permission_update(int $id, string $name, string $label, string $desc = ''): bool {
    global $mysqli;
    $id = intval($id);
    $name = $mysqli->real_escape_string($name);
    $label = $mysqli->real_escape_string($label);
    $desc = $mysqli->real_escape_string($desc);
    return (bool)$mysqli->query("UPDATE permissions SET name='$name', label='$label', description='$desc' WHERE id=$id");
}

function permission_delete(int $id): bool {
    global $mysqli;
    $id = intval($id);
    return (bool)$mysqli->query("DELETE FROM permissions WHERE id=$id");
}

/* ---------- Role ↔ Permissions sync ---------- */
function role_permission_ids(int $roleId): array {
    global $mysqli;
    $out = [];
    $roleId = intval($roleId);
    $res = $mysqli->query("SELECT permission_id FROM role_permissions WHERE role_id=$roleId");
    if ($res) { while ($r = $res->fetch_assoc()) $out[] = (int)$r['permission_id']; $res->free(); }
    return $out;
}

function role_sync_permissions(int $roleId, array $permIds): bool {
    global $mysqli;
    $roleId = intval($roleId);
    $mysqli->begin_transaction();
    $ok1 = $mysqli->query("DELETE FROM role_permissions WHERE role_id=$roleId");
    $vals = [];
    foreach ($permIds as $pid) $vals[] = "($roleId," . intval($pid) . ")";
    $ok2 = true;
    if (!empty($vals)) $ok2 = $mysqli->query("INSERT INTO role_permissions (role_id, permission_id) VALUES " . implode(',', $vals));
    ($ok1 && $ok2) ? $mysqli->commit() : $mysqli->rollback();
    return $ok1 && $ok2;
}

/* ---------- User ↔ Roles sync (used from Users module) ---------- */
function sync_user_roles_mysqli(int $userId, array $roleIds): bool {
    global $mysqli;
    $userId = intval($userId);
    $mysqli->begin_transaction();
    $ok1 = $mysqli->query("DELETE FROM user_roles WHERE user_id=$userId");
    $vals = [];
    foreach ($roleIds as $rid) $vals[] = "($userId," . intval($rid) . ")";
    $ok2 = true;
    if (!empty($vals)) $ok2 = $mysqli->query("INSERT INTO user_roles (user_id, role_id) VALUES " . implode(',', $vals));
    ($ok1 && $ok2) ? $mysqli->commit() : $mysqli->rollback();
    return $ok1 && $ok2;
}
