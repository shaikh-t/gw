<?php
// lib/users_helpers.php
require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/role_helpers.php'; // for sync_user_roles_mysqli if needed
require_once __DIR__ . '/uuid_helper.php';

function users_count(): int {
    global $mysqli;
    $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM users");
    if (!$res) return 0;
    $row = $res->fetch_assoc();
    $res->free();
    return (int)$row['cnt'];
}

function users_paginated(int $page = 1, int $perPage = 20): array {
    global $mysqli;
    $page = max(1, intval($page));
    $perPage = max(1, intval($perPage));
    $offset = ($page - 1) * $perPage;
    $out = [];
    $sql = "SELECT id, name, email, avatar, phone, created_at FROM users ORDER BY created_at DESC LIMIT $offset, $perPage";
    if ($res = $mysqli->query($sql)) {
        while ($r = $res->fetch_assoc()) $out[] = $r;
        $res->free();
    }
    return $out;
}

function user_find($id) {
    global $mysqli;
    if (is_numeric($id)) {
        $id = intval($id);
        $res = $mysqli->query("SELECT * FROM users WHERE id = $id LIMIT 1");
    } else {
        $uuid = $mysqli->real_escape_string($id);
        $res = $mysqli->query("SELECT * FROM users WHERE uuid = '$uuid' LIMIT 1");
    }
    if (!$res) return null;
    $row = $res->fetch_assoc();
    $res->free();
    return $row ?: null;
}

// function user_create(array $data) {
//     global $mysqli;
//     $name = $mysqli->real_escape_string(trim($data['name'] ?? ''));
//     $email = $mysqli->real_escape_string(trim($data['email'] ?? ''));
//     $password = $data['password'] ?? '';
//     $phone = $mysqli->real_escape_string(trim($data['phone'] ?? ''));
//     $bio = $mysqli->real_escape_string(trim($data['bio'] ?? ''));
//     $roles = $data['roles'] ?? [];

//     if ($password === '') return ['ok' => false, 'error' => 'Password required'];

//     $hash = password_hash($password, PASSWORD_DEFAULT);
//     $hashEsc = $mysqli->real_escape_string($hash);

//     $rolesJson = $mysqli->real_escape_string(json_encode(array_values($roles)));

//     $sql = "INSERT INTO users (name, email, password, phone, bio, roles, created_at) VALUES ('$name', '$email', '$hashEsc', '$phone', '$bio', '$rolesJson', NOW())";
//     if ($mysqli->query($sql)) {
//         $uid = $mysqli->insert_id;
//         // If roles are role ids, sync user_roles table
//         if (!empty($roles)) {
//             $roleIds = array_map('intval', $roles);
//             sync_user_roles_mysqli($uid, $roleIds);
//         }
//         return ['ok' => true, 'id' => $uid];
//     }
//     return ['ok' => false, 'error' => $mysqli->error];
// }

// function user_assign_role($user_id,$role_id){
//   global $mysqli;
//   $mysqli->query("INSERT IGNORE INTO user_roles (user_id,role_id) VALUES (".intval($user_id).",".intval($role_id).")");
// }

// function user_remove_role($user_id,$role_id){
//   global $mysqli;
//   $mysqli->query("DELETE FROM user_roles WHERE user_id=".intval($user_id)." AND role_id=".intval($role_id));
// }

function user_has_permission($user_id,$perm_name){
  global $mysqli;
  $perm_sql = $mysqli->real_escape_string($perm_name);
  $sql = "SELECT 1 FROM permissions p
          INNER JOIN role_permissions rp ON rp.permission_id=p.id
          INNER JOIN user_roles ur ON ur.role_id=rp.role_id
          WHERE ur.user_id=".intval($user_id)." AND p.name='$perm_sql' LIMIT 1";
  $res = $mysqli->query($sql);
  return $res && $res->num_rows>0;
}

function user_create($name,$email,$password,$roles=[]) {
  global $mysqli;
  $hash = password_hash($password,PASSWORD_DEFAULT);
  $uuid = generate_uuid();
  $sql = "INSERT INTO users (uuid,name,email,password) VALUES ('$uuid','".$mysqli->real_escape_string($name)."','".$mysqli->real_escape_string($email)."','".$mysqli->real_escape_string($hash)."')";
  if(!$mysqli->query($sql)) return ['ok'=>false,'error'=>$mysqli->error];
  $id = $mysqli->insert_id;
  foreach($roles as $role_id){
    $mysqli->query("INSERT INTO user_roles (user_id,role_id) VALUES ($id,".intval($role_id).")");
  }
  return ['ok'=>true,'id'=>$id];
}

function user_update(int $id, array $data) {
    global $mysqli;
    $id = intval($id);
    $name = $mysqli->real_escape_string(trim($data['name'] ?? ''));
    $email = $mysqli->real_escape_string(trim($data['email'] ?? ''));
    $phone = $mysqli->real_escape_string(trim($data['phone'] ?? ''));
    $bio = $mysqli->real_escape_string(trim($data['bio'] ?? ''));
    $roles = $data['roles'] ?? [];
    $password = $data['password'] ?? '';

    $parts = [];
    if ($name !== '') $parts[] = "name = '$name'";
    if ($email !== '') $parts[] = "email = '$email'";
    $parts[] = "phone = '$phone'";
    $parts[] = "bio = '$bio'";

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $parts[] = "password = '" . $mysqli->real_escape_string($hash) . "'";
    }

    if (empty($parts)) return ['ok' => false, 'error' => 'Nothing to update'];

    $sql = "UPDATE users SET " . implode(', ', $parts) . " WHERE id = $id";
    if ($mysqli->query($sql)) {
        // sync roles if provided
        if (is_array($roles)) {

            $roleIds = array_map('intval', $roles);
            sync_user_roles_mysqli($id, $roleIds);
            // If current session user updated their own roles, update session
            $curr = current_user();
        }
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => $mysqli->error];
}

function user_delete(int $id) {
    global $mysqli;
    $id = intval($id);
    // prevent deleting last admin? optional check
    return $mysqli->query("DELETE FROM users WHERE id = $id");
}


/**
 * Find user by email
 */
function user_find_by_email(string $email) {
    global $mysqli;
    $email = $mysqli->real_escape_string(trim($email));
    if ($email === '') return null;
    $res = $mysqli->query("SELECT * FROM users WHERE email = '$email' LIMIT 1");
    if (!$res || $res->num_rows === 0) return null;
    $row = $res->fetch_assoc();
    $res->free();
    return $row;
}

/**
 * Create a minimal user record in invited state.
 * Returns ['ok'=>true,'id'=>int] or ['ok'=>false,'error'=>...]
 */
function user_create_invited(array $data) {
    global $mysqli;
    $email = trim($data['email'] ?? '');
    $name = trim($data['name'] ?? '');
    if ($email === '') return ['ok'=>false,'error'=>'Email required'];
    // ensure not duplicate
    if (user_find_by_email($email)) return ['ok'=>false,'error'=>'User already exists'];

    $password_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // placeholder password
    $stmt = $mysqli->prepare("INSERT INTO users (name, email, password_hash, status, created_at) VALUES (?, ?, ?, 'invited', NOW())");
    $stmt->bind_param('sss', $name, $email, $password_hash);
    if (!$stmt->execute()) {
        $err = $mysqli->error;
        $stmt->close();
        return ['ok'=>false,'error'=>$err];
    }
    $id = $stmt->insert_id;
    $stmt->close();
    return ['ok'=>true,'id'=>$id];
}

/**
 * Create an invite token and store it.
 * $expiresSeconds default 7 days
 * Returns ['ok'=>true,'token'=>..., 'invite_id'=>...] or error
 */
function user_create_invite(string $email, int $created_by = null, int $expiresSeconds = 604800) {
    global $mysqli;
    $email = trim($email);
    if ($email === '') return ['ok'=>false,'error'=>'Email required'];
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + $expiresSeconds);
    $stmt = $mysqli->prepare("INSERT INTO user_invites (email, token, created_by, expires_at) VALUES (?, ?, ?, ?)");
    $cb = $created_by ? intval($created_by) : null;
    $stmt->bind_param('siss', $email, $token, $cb, $expires_at);
    if (!$stmt->execute()) {
        $err = $mysqli->error;
        $stmt->close();
        return ['ok'=>false,'error'=>$err];
    }
    $invite_id = $stmt->insert_id;
    $stmt->close();
    return ['ok'=>true,'token'=>$token, 'invite_id'=>$invite_id];
}

/**
 * Mark invite used and optionally attach user_id
 */
function user_mark_invite_used(string $token, int $user_id = null) {
    global $mysqli;
    $token = $mysqli->real_escape_string($token);
    $user_id_sql = $user_id ? intval($user_id) : 'NULL';
    $sql = "UPDATE user_invites SET used_at = NOW(), user_id = " . $user_id_sql . " WHERE token = '$token' LIMIT 1";
    return (bool)$mysqli->query($sql);
}

/**
 * Validate invite token and return invite row or null
 */
function user_get_invite_by_token(string $token) {
    global $mysqli;
    $token = $mysqli->real_escape_string(trim($token));
    if ($token === '') return null;
    $res = $mysqli->query("SELECT * FROM user_invites WHERE token = '$token' LIMIT 1");
    if (!$res || $res->num_rows === 0) return null;
    $row = $res->fetch_assoc();
    $res->free();
    // check expiry and used
    if (!empty($row['used_at'])) return null;
    if (strtotime($row['expires_at']) < time()) return null;
    return $row;
}

/**
 * Activate invited user by setting password and status active
 */
function user_activate_from_invite(int $user_id, string $password) {
    global $mysqli;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("UPDATE users SET password_hash = ?, status = 'active', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $hash, $user_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok ? ['ok'=>true] : ['ok'=>false,'error'=>$mysqli->error];
}
