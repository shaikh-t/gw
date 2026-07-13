<?php
// lib/services_helpers.php
require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/uuid_helper.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function service_slugify(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[^\p{L}\p{N}\s\-]+/u', '', $s);
    $s = preg_replace('/[\s\-]+/', '-', $s);
    return trim($s, '-');
}

/* Categories */
function service_categories_all(): array {
    global $mysqli;
    $out = [];
    if ($res = $mysqli->query("SELECT * FROM service_categories ORDER BY name")) {
        while ($r = $res->fetch_assoc()) $out[] = $r;
        $res->free();
    }
    return $out;
}

function service_category_create($name, $slug = null, $desc = '') {
    global $mysqli;
    $slug = $slug ?: service_slugify($name);
    $sql = "INSERT INTO service_categories (name, slug, description, created_at) VALUES ('" . $mysqli->real_escape_string($name) . "', '" . $mysqli->real_escape_string($slug) . "', '" . $mysqli->real_escape_string($desc) . "', NOW())";
    return $mysqli->query($sql) ? $mysqli->insert_id : false;
}

/* Tags */
function service_tags_all(): array {
    global $mysqli;
    $out = [];
    if ($res = $mysqli->query("SELECT * FROM service_tags ORDER BY name")) {
        while ($r = $res->fetch_assoc()) $out[] = $r;
        $res->free();
    }
    return $out;
}

function service_tag_create($name) {
    global $mysqli;
    $sql = "INSERT INTO service_tags (name, created_at) VALUES ('" . $mysqli->real_escape_string($name) . "', NOW())";
    return $mysqli->query($sql) ? $mysqli->insert_id : false;
}

/* Services CRUD */
function services_count(array $filters = []): int {
    global $mysqli;
    $where = [];
    if (!empty($filters['provider_id'])) $where[] = "s.provider_id = " . intval($filters['provider_id']);
    if (!empty($filters['status'])) $where[] = "s.status = '" . $mysqli->real_escape_string($filters['status']) . "'";
    if (!empty($filters['category_id'])) $where[] = "s.category_id = " . intval($filters['category_id']);
    $sql = "SELECT COUNT(*) AS cnt FROM services" . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where));
    $res = $mysqli->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_assoc(); $res->free();
    return (int)$row['cnt'];
}

function services_paginated(int $page = 1, int $perPage = 20, array $filters = []): array {
    global $mysqli;
    $page = max(1, intval($page)); $perPage = max(1, intval($perPage));
    $offset = ($page - 1) * $perPage;
    $where = [];
    if (!empty($filters['provider_id'])) $where[] = "s.provider_id = " . intval($filters['provider_id']);
    if (!empty($filters['status'])) $where[] = "s.status = '" . $mysqli->real_escape_string($filters['status']) . "'";
    if (!empty($filters['category_id'])) $where[] = "s.category_id = " . intval($filters['category_id']);
    $sql = "SELECT s.*, p.name AS provider_name, c.name AS category_name
            FROM services s
            LEFT JOIN providers p ON p.id = s.provider_id
            LEFT JOIN service_categories c ON c.id = s.category_id" .
            (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where)) .
            " ORDER BY s.created_at DESC LIMIT $offset, $perPage";
    $out = [];
    if ($res = $mysqli->query($sql)) {
        while ($r = $res->fetch_assoc()) $out[] = $r;
        $res->free();
    }
    return $out;
}

function service_find($idOrUuidOrSlug) {
    global $mysqli;
    if (is_numeric($idOrUuidOrSlug)) {
        $res = $mysqli->query("SELECT s.*, p.name AS provider_name FROM services s JOIN providers p ON p.id = s.provider_id WHERE s.id = " . intval($idOrUuidOrSlug) . " LIMIT 1");
    } else {
        $val = $mysqli->real_escape_string($idOrUuidOrSlug);
        if (preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}$/i', $val)) {
            $res = $mysqli->query("SELECT s.*, p.name AS provider_name FROM services s JOIN providers p ON p.id = s.provider_id WHERE s.uuid = '$val' LIMIT 1");
        } else {
            $res = $mysqli->query("SELECT s.*, p.name AS provider_name FROM services s JOIN providers p ON p.id = s.provider_id WHERE s.slug = '$val' LIMIT 1");
        }
    }
    if (!$res) return null;
    $row = $res->fetch_assoc(); $res->free();
    if ($row) {
        $row['images'] = json_decode($row['images'] ?? '[]', true) ?: [];
        // load tags
        $row['tags'] = [];
        $r2 = $mysqli->query("SELECT t.* FROM service_tags t JOIN service_tag_map m ON m.tag_id = t.id WHERE m.service_id = " . intval($row['id']));
        if ($r2) { while ($tr = $r2->fetch_assoc()) $row['tags'][] = $tr; $r2->free(); }
    }
    return $row ?: null;
}

function service_create(array $data) {
    global $mysqli;
    $provider_id = intval($data['provider_id']);
    $title = $mysqli->real_escape_string(trim($data['title'] ?? ''));
    if ($title === '') return ['ok' => false, 'error' => 'Title required'];
    $slugBase = service_slugify($title);
    $slug = $slugBase; $i = 1;
    while (true) {
        $res = $mysqli->query("SELECT id FROM services WHERE slug = '" . $mysqli->real_escape_string($slug) . "' LIMIT 1");
        if ($res && $res->num_rows === 0) { if ($res) $res->free(); break; }
        if ($res) $res->free();
        $slug = $slugBase . '-' . $i++;
    }
    $category_id = isset($data['category_id']) ? intval($data['category_id']) : 'NULL';
    $short = $mysqli->real_escape_string(trim($data['short_description'] ?? ''));
    $desc = $mysqli->real_escape_string(trim($data['description'] ?? ''));
    $price = isset($data['price']) && $data['price'] !== '' ? $mysqli->real_escape_string($data['price']) : 'NULL';
    $currency = $mysqli->real_escape_string($data['currency'] ?? 'USD');
    $duration = isset($data['duration_minutes']) ? intval($data['duration_minutes']) : 'NULL';
    $status = $mysqli->real_escape_string($data['status'] ?? 'draft');

    // handle images upload (multiple)
    $images = [];
    if (!empty($data['image_files']) && is_array($data['image_files'])) {
        foreach ($data['image_files'] as $file) {
            $resUp = avatar_upload_handle($file, __DIR__ . '/../public/uploads/services');
            if (!$resUp['ok']) return ['ok' => false, 'error' => 'Image upload: ' . $resUp['error']];
            $images[] = '/public/uploads/services/' . $resUp['filename'];
        }
    }

    $icon_class = $mysqli->real_escape_string(trim($data['icon_class'] ?? 'bi-award'));
    $duration_text = $mysqli->real_escape_string(trim($data['duration_text'] ?? '5–7 days'));

    $uuid = generate_uuid();
    $sql = "INSERT INTO services (uuid, provider_id, category_id, title, slug, short_description, description, price, currency, duration_minutes, images, status, icon_class, duration_text, created_at)
            VALUES ('$uuid', " . intval($provider_id) . ", " . ($category_id === 'NULL' ? 'NULL' : intval($category_id)) . ",
                    '" . $title . "', '" . $mysqli->real_escape_string($slug) . "',
                    '" . $short . "', '" . $desc . "',
                    " . ($price === 'NULL' ? 'NULL' : $price) . ",
                    '" . $currency . "', " . ($duration === 'NULL' ? 'NULL' : $duration) . ",
                    '" . $mysqli->real_escape_string(json_encode($images)) . "',
                    '" . $status . "', '$icon_class', '$duration_text', NOW())";
    if ($mysqli->query($sql)) {
        $sid = $mysqli->insert_id;
        // sync tags
        if (!empty($data['tag_ids']) && is_array($data['tag_ids'])) {
            $vals = [];
            foreach ($data['tag_ids'] as $tid) $vals[] = "(" . intval($sid) . "," . intval($tid) . ")";
            if (!empty($vals)) $mysqli->query("INSERT INTO service_tag_map (service_id, tag_id) VALUES " . implode(',', $vals));
        }
        return ['ok' => true, 'id' => $sid];
    }
    return ['ok' => false, 'error' => $mysqli->error];
}

function service_update(int $id, array $data) {
    global $mysqli;
    $id = intval($id);
    $sets = [];
    if (isset($data['title']) && trim($data['title']) !== '') $sets[] = "title = '" . $mysqli->real_escape_string(trim($data['title'])) . "'";
    if (isset($data['short_description'])) $sets[] = "short_description = '" . $mysqli->real_escape_string(trim($data['short_description'])) . "'";
    if (isset($data['description'])) $sets[] = "description = '" . $mysqli->real_escape_string(trim($data['description'])) . "'";
    if (isset($data['price'])) $sets[] = "price = " . ($data['price'] === '' ? "NULL" : $mysqli->real_escape_string($data['price']));
    if (isset($data['currency'])) $sets[] = "currency = '" . $mysqli->real_escape_string($data['currency']) . "'";
    if (isset($data['duration_minutes'])) $sets[] = "duration_minutes = " . (intval($data['duration_minutes']) ?: "NULL");
    if (isset($data['status'])) $sets[] = "status = '" . $mysqli->real_escape_string($data['status']) . "'";
    if (isset($data['category_id'])) $sets[] = "category_id = " . (intval($data['category_id']) ?: "NULL");
    if (isset($data['icon_class'])) $sets[] = "icon_class = '" . $mysqli->real_escape_string(trim($data['icon_class'])) . "'";
    if (isset($data['duration_text'])) $sets[] = "duration_text = '" . $mysqli->real_escape_string(trim($data['duration_text'])) . "'";

    // images: append new images if provided
    if (!empty($data['image_files']) && is_array($data['image_files'])) {
        $existing = [];
        $res = $mysqli->query("SELECT images FROM services WHERE id = $id LIMIT 1");
        if ($res) { $row = $res->fetch_assoc(); $existing = json_decode($row['images'] ?? '[]', true) ?: []; $res->free(); }
        foreach ($data['image_files'] as $file) {
            $resUp = avatar_upload_handle($file, __DIR__ . '/../public/uploads/services');
            if (!$resUp['ok']) return ['ok' => false, 'error' => 'Image upload: ' . $resUp['error']];
            $existing[] = '/public/uploads/services/' . $resUp['filename'];
        }
        $sets[] = "images = '" . $mysqli->real_escape_string(json_encode(array_values($existing))) . "'";
    }

    if (empty($sets)) return ['ok' => false, 'error' => 'Nothing to update'];
    $sql = "UPDATE services SET " . implode(', ', $sets) . " WHERE id = $id";
    if ($mysqli->query($sql)) {
        // sync tags
        if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
            $mysqli->begin_transaction();
            $ok1 = $mysqli->query("DELETE FROM service_tag_map WHERE service_id = $id");
            $vals = [];
            foreach ($data['tag_ids'] as $tid) $vals[] = "(" . intval($id) . "," . intval($tid) . ")";
            $ok2 = true;
            if (!empty($vals)) $ok2 = $mysqli->query("INSERT INTO service_tag_map (service_id, tag_id) VALUES " . implode(',', $vals));
            ($ok1 && $ok2) ? $mysqli->commit() : $mysqli->rollback();
        }
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => $mysqli->error];
}

function service_delete(int $id): bool {
    global $mysqli;
    $id = intval($id);
    return (bool)$mysqli->query("DELETE FROM services WHERE id = $id");
}
