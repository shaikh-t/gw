<?php
// lib/providers_helpers.php
require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/users_helpers.php';
require_once __DIR__ . '/uuid_helper.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function provider_slugify(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[^\p{L}\p{N}\s\-]+/u', '', $s);
    $s = preg_replace('/[\s\-]+/', '-', $s);
    return trim($s, '-');
}

function providers_count(array $filters = []): int {
    global $mysqli;
    $where = [];
    if (!empty($filters['status'])) $where[] = "status = '" . $mysqli->real_escape_string($filters['status']) . "'";
    if (!empty($filters['city'])) $where[] = "city = '" . $mysqli->real_escape_string($filters['city']) . "'";
    $sql = "SELECT COUNT(*) AS cnt FROM providers" . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where));
    $res = $mysqli->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_assoc();
    $res->free();
    return (int)$row['cnt'];
}

function providers_paginated(int $page = 1, int $perPage = 20, array $filters = []): array {
    global $mysqli;
    $page = max(1, intval($page));
    $perPage = max(1, intval($perPage));
    $offset = ($page - 1) * $perPage;
    $where = [];
    if (!empty($filters['status'])) $where[] = "status = '" . $mysqli->real_escape_string($filters['status']) . "'";
    if (!empty($filters['city'])) $where[] = "city = '" . $mysqli->real_escape_string($filters['city']) . "'";
    $sql = "SELECT id,uuid, owner_user_id, name, slug, city, state, country, logo, status, verification_status, created_at
            FROM providers" . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where)) . "
            ORDER BY created_at DESC LIMIT $offset, $perPage";
    $out = [];
    if ($res = $mysqli->query($sql)) {
        while ($r = $res->fetch_assoc()) $out[] = $r;
        $res->free();
    }
    return $out;
}

/** Documents helpers */
function provider_documents_find_by_provider(int $provider_id): array {
    global $mysqli;
    $provider_id = intval($provider_id);
    $out = [];
    $res = $mysqli->query("SELECT * FROM provider_documents WHERE provider_id = $provider_id ORDER BY created_at DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) $out[] = $row;
        $res->free();
    }
    return $out;
}

function provider_document_find($idOrUuid) {
    global $mysqli;
    if (is_numeric($idOrUuid)) {
        $res = $mysqli->query("SELECT * FROM provider_documents WHERE id = " . intval($idOrUuid) . " LIMIT 1");
    } else {
        $res = $mysqli->query("SELECT * FROM provider_documents WHERE uuid = '" . $mysqli->real_escape_string($idOrUuid) . "' LIMIT 1");
    }
    if ($res && $row = $res->fetch_assoc()) {
        $res->free();
        return $row;
    }
    return null;
}

/** Team Members helpers */
function provider_team_members_find_by_provider(int $provider_id): array {
    global $mysqli;
    $provider_id = intval($provider_id);
    $out = [];
    $res = $mysqli->query("SELECT * FROM provider_team_members WHERE provider_id = $provider_id ORDER BY created_at DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) $out[] = $row;
        $res->free();
    }
    return $out;
}

function provider_team_member_find($idOrUuid) {
    global $mysqli;
    if (is_numeric($idOrUuid)) {
        $res = $mysqli->query("SELECT * FROM provider_team_members WHERE id = " . intval($idOrUuid) . " LIMIT 1");
    } else {
        $res = $mysqli->query("SELECT * FROM provider_team_members WHERE uuid = '" . $mysqli->real_escape_string($idOrUuid) . "' LIMIT 1");
    }
    if ($res && $row = $res->fetch_assoc()) {
        $res->free();
        return $row;
    }
    return null;
}

function provider_find($idOrUuidOrSlug) {
    global $mysqli;
    if (is_numeric($idOrUuidOrSlug)) {
        $id = intval($idOrUuidOrSlug);
        $res = $mysqli->query("SELECT * FROM providers WHERE id = $id LIMIT 1");
    } else {
        $val = $mysqli->real_escape_string($idOrUuidOrSlug);
        // Try UUID first if it looks like one, then slug
        if (preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}$/i', $val)) {
            $res = $mysqli->query("SELECT * FROM providers WHERE uuid = '$val' LIMIT 1");
        } else {
            $res = $mysqli->query("SELECT * FROM providers WHERE slug = '$val' LIMIT 1");
        }
    }
    if (!$res) return null;
    $row = $res->fetch_assoc();
    $res->free();
    return $row ?: null;
}

function provider_create(array $data) {
    global $mysqli;
    $name = $mysqli->real_escape_string(trim($data['name'] ?? ''));
    if ($name === '') return ['ok' => false, 'error' => 'Name required'];
    $slugBase = provider_slugify($name);
    $slug = $slugBase;
    $i = 1;
    while (true) {
        $res = $mysqli->query("SELECT id FROM providers WHERE slug = '" . $mysqli->real_escape_string($slug) . "' LIMIT 1");
        if ($res && $res->num_rows === 0) { if ($res) $res->free(); break; }
        if ($res) $res->free();
        $slug = $slugBase . '-' . $i++;
    }

    $owner = isset($data['owner_user_id']) ? intval($data['owner_user_id']) : 'NULL';
    $email = $mysqli->real_escape_string(trim($data['email'] ?? ''));
    $phone = $mysqli->real_escape_string(trim($data['phone'] ?? ''));
    $address = $mysqli->real_escape_string(trim($data['address'] ?? ''));
    $city = $mysqli->real_escape_string(trim($data['city'] ?? ''));
    $state = $mysqli->real_escape_string(trim($data['state'] ?? ''));
    $country = $mysqli->real_escape_string(trim($data['country'] ?? ''));
    $lat = isset($data['latitude']) ? $mysqli->real_escape_string($data['latitude']) : 'NULL';
    $lng = isset($data['longitude']) ? $mysqli->real_escape_string($data['longitude']) : 'NULL';
    $description = $mysqli->real_escape_string(trim($data['description'] ?? ''));
    $status = $mysqli->real_escape_string($data['status'] ?? 'draft');

    $logoPath = null;
    if (!empty($data['logo_file']) && is_array($data['logo_file'])) {
        $resUpload = avatar_upload_handle($data['logo_file'], __DIR__ . '/../public/uploads/providers');
        if (!$resUpload['ok']) return ['ok' => false, 'error' => 'Logo: ' . $resUpload['error']];
        $logoPath = '/public/uploads/providers/' . $resUpload['filename'];
    }

    $sql = "INSERT INTO providers (owner_user_id, name, slug, email, phone, address, city, state, country, latitude, longitude, logo, description, status, created_at)
            VALUES (" . ($owner === 'NULL' ? 'NULL' : intval($owner)) . ",
                    '" . $mysqli->real_escape_string($name) . "',
                    '" . $mysqli->real_escape_string($slug) . "',
                    '" . $email . "',
                    '" . $phone . "',
                    '" . $address . "',
                    '" . $city . "',
                    '" . $state . "',
                    '" . $country . "',
                    " . ($lat === 'NULL' ? 'NULL' : $lat) . ",
                    " . ($lng === 'NULL' ? 'NULL' : $lng) . ",
                    " . ($logoPath ? "'" . $mysqli->real_escape_string($logoPath) . "'" : "NULL") . ",
                    '" . $description . "',
                    '" . $status . "',
                    NOW())";
    if ($mysqli->query($sql)) {
        return ['ok' => true, 'id' => $mysqli->insert_id];
    }
    return ['ok' => false, 'error' => $mysqli->error];
}

function provider_update($idOrUuid, array $data) {
    global $mysqli;
    $p = provider_find($idOrUuid);
    if (!$p) return ['ok' => false, 'error' => 'Provider not found'];
    $id = intval($p['id']);
    $sets = [];

    if (isset($data['verification_docs'])) {
    $docsJson = is_array($data['verification_docs']) ? json_encode($data['verification_docs']) : $mysqli->real_escape_string($data['verification_docs']);
    $sets[] = "verification_docs = '" . $mysqli->real_escape_string(is_array($data['verification_docs']) ? json_encode($data['verification_docs']) : $data['verification_docs']) . "'";
}

    if (isset($data['name']) && trim($data['name']) !== '') $sets[] = "name = '" . $mysqli->real_escape_string(trim($data['name'])) . "'";
    if (isset($data['email'])) $sets[] = "email = '" . $mysqli->real_escape_string(trim($data['email'])) . "'";
    if (isset($data['phone'])) $sets[] = "phone = '" . $mysqli->real_escape_string(trim($data['phone'])) . "'";
    if (isset($data['address'])) $sets[] = "address = '" . $mysqli->real_escape_string(trim($data['address'])) . "'";
    if (isset($data['city'])) $sets[] = "city = '" . $mysqli->real_escape_string(trim($data['city'])) . "'";
    if (isset($data['state'])) $sets[] = "state = '" . $mysqli->real_escape_string(trim($data['state'])) . "'";
    if (isset($data['country'])) $sets[] = "country = '" . $mysqli->real_escape_string(trim($data['country'])) . "'";
    if (isset($data['latitude'])) $sets[] = "latitude = " . ($data['latitude'] === '' ? "NULL" : $mysqli->real_escape_string($data['latitude']));
    if (isset($data['longitude'])) $sets[] = "longitude = " . ($data['longitude'] === '' ? "NULL" : $mysqli->real_escape_string($data['longitude']));
    if (isset($data['description'])) $sets[] = "description = '" . $mysqli->real_escape_string(trim($data['description'])) . "'";
    if (isset($data['status'])) $sets[] = "status = '" . $mysqli->real_escape_string($data['status']) . "'";
    if (isset($data['verification_status'])) $sets[] = "verification_status = '" . $mysqli->real_escape_string($data['verification_status']) . "'";
    if (isset($data['owner_user_id'])) $sets[] = "owner_user_id = " . (intval($data['owner_user_id']) ?: "NULL");

    if (!empty($data['logo_file']) && is_array($data['logo_file'])) {
        $resUpload = avatar_upload_handle($data['logo_file'], __DIR__ . '/../public/uploads/providers');
        if (!$resUpload['ok']) return ['ok' => false, 'error' => 'Logo: ' . $resUpload['error']];
        $logoPath = '/public/uploads/providers/' . $resUpload['filename'];
        $sets[] = "logo = '" . $mysqli->real_escape_string($logoPath) . "'";
    }

    if (empty($sets)) return ['ok' => false, 'error' => 'Nothing to update'];
    $sql = "UPDATE providers SET " . implode(', ', $sets) . " WHERE id = $id";
    if ($mysqli->query($sql)) return ['ok' => true];
    return ['ok' => false, 'error' => $mysqli->error];
}

function provider_delete(int $id): bool {
    global $mysqli;
    $id = intval($id);
    // soft delete pattern could be implemented; here we hard delete
    return (bool)$mysqli->query("DELETE FROM providers WHERE id = $id");
}



/** Return provider ids owned by a user (provider owner relationship assumed) */
function providers_for_user($user_id): array {
    global $mysqli;
    $out = [];
    $uid = is_numeric($user_id) ? intval($user_id) : null;
    if (!$uid) {
        $res = $mysqli->query("SELECT id FROM users WHERE uuid = '" . $mysqli->real_escape_string($user_id) . "' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) $uid = (int)$row['id'];
        if ($res && !is_bool($res)) $res->free();
    }
    if (!$uid) return [];

    $res = $mysqli->query("SELECT id, uuid, name FROM providers WHERE owner_user_id = $uid ORDER BY name");
    if ($res) {
        while ($r = $res->fetch_assoc()) $out[] = $r;
        $res->free();
    }
    return $out;
}

/** Get dashboard metrics for a provider */
function provider_dashboard_metrics($provider_id): array {
    global $mysqli;
    if (is_numeric($provider_id)) {
        $pid = intval($provider_id);
    } else {
        $res = $mysqli->query("SELECT id FROM providers WHERE uuid = '" . $mysqli->real_escape_string($provider_id) . "' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) $pid = (int)$row['id'];
        else return [];
    }
    $metrics = [
        'total_services' => 0,
        'published_services' => 0,
        'avg_rating' => null,
        'rating_count' => 0,
        'pending_reviews' => 0,
        'recent_reviews' => []
    ];

    $q = "SELECT COUNT(*) AS cnt FROM services WHERE provider_id = $pid";
    if ($r = $mysqli->query($q)) { $row = $r->fetch_assoc(); $metrics['total_services'] = intval($row['cnt']); $r->free(); }

    $q = "SELECT COUNT(*) AS cnt FROM services WHERE provider_id = $pid AND status = 'published'";
    if ($r = $mysqli->query($q)) { $row = $r->fetch_assoc(); $metrics['published_services'] = intval($row['cnt']); $r->free(); }

    $q = "SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM reviews WHERE provider_id = $pid AND status = 'published'";
    if ($r = $mysqli->query($q)) {
        $row = $r->fetch_assoc();
        $metrics['avg_rating'] = $row['avg_rating'] !== null ? round(floatval($row['avg_rating']), 2) : null;
        $metrics['rating_count'] = intval($row['cnt']);
        $r->free();
    }

    $q = "SELECT COUNT(*) AS cnt FROM reviews WHERE provider_id = $pid AND status = 'pending'";
    if ($r = $mysqli->query($q)) { $row = $r->fetch_assoc(); $metrics['pending_reviews'] = intval($row['cnt']); $r->free(); }

    $q = "SELECT r.id, r.rating, r.title, r.body, r.created_at, u.name AS user_name
          FROM reviews r LEFT JOIN users u ON u.id = r.user_id
          WHERE r.provider_id = $pid AND r.status = 'published'
          ORDER BY r.created_at DESC LIMIT 6";
    if ($r = $mysqli->query($q)) {
        while ($row = $r->fetch_assoc()) $metrics['recent_reviews'][] = $row;
        $r->free();
    }

    return $metrics;
}

/** Quick provider summary for admin list */
function provider_summary_list(int $limit = 50): array {
    global $mysqli;
    $out = [];
    $res = $mysqli->query("SELECT p.id,p.uuid, p.name, p.owner_user_id,
        (SELECT COUNT(*) FROM services s WHERE s.provider_id = p.id) AS services_count,
        (SELECT AVG(rating) FROM reviews rv WHERE rv.provider_id = p.id AND rv.status='published') AS avg_rating
        FROM providers p ORDER BY p.created_at DESC LIMIT " . intval($limit));
    if ($res) {
        while ($r = $res->fetch_assoc()) $out[] = $r;
        $res->free();
    }
    return $out;
}
