<?php
// lib/onboarding_helpers.php
require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/providers_helpers.php';
require_once __DIR__ . '/upload.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function onboarding_start(array $data) {
    global $mysqli;
    $name = trim($data['name'] ?? '');
    $owner_user_id = isset($data['owner_user_id']) ? intval($data['owner_user_id']) : null;
    if ($name === '') return ['ok'=>false,'error'=>'Name required'];

    $res = provider_create([
        'name' => $name,
        'owner_user_id' => $owner_user_id,
        'email' => $data['email'] ?? null,
        'phone' => $data['phone'] ?? null,
        'address' => $data['address'] ?? null,
        'city' => $data['city'] ?? null,
        'state' => $data['state'] ?? null,
        'country' => $data['country'] ?? null,
        'description' => $data['description'] ?? '',
        'status' => 'draft'
    ]);
    if (!$res['ok']) return $res;
    $provider_id = intval($res['id']);

    $stmt = $mysqli->prepare("INSERT INTO provider_onboarding (provider_id, owner_user_id, step, progress, duplicate_check_status, created_at) VALUES (?, ?, 'profile', ?, 'unchecked', NOW())");
    $progress = json_encode(['profile' => 'completed']);
    $stmt->bind_param('iis', $provider_id, $owner_user_id, $progress);
    if (!$stmt->execute()) return ['ok'=>false,'error'=>$mysqli->error];
    $onb_id = $stmt->insert_id;
    $stmt->close();

    $dup = onboarding_check_duplicates($provider_id);
    $mysqli->query("UPDATE provider_onboarding SET duplicate_check_status = '" . $mysqli->real_escape_string($dup['status']) . "' WHERE id = " . intval($onb_id));

    return ['ok'=>true,'provider_id'=>$provider_id,'onboarding_id'=>$onb_id];
}

function onboarding_update_step(int $onboarding_id, string $step, array $progress = []) {
    global $mysqli;
    $pj = json_encode($progress);
    $stmt = $mysqli->prepare("UPDATE provider_onboarding SET step = ?, progress = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('ssi', $step, $pj, $onboarding_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok ? ['ok'=>true] : ['ok'=>false,'error'=>$mysqli->error];
}

function onboarding_submit_documents(int $onboarding_id, array $files, int $actor_user_id = null) {
    global $mysqli;
    $res = $mysqli->query("SELECT provider_id, verification_docs FROM providers JOIN provider_onboarding ON providers.id = provider_onboarding.provider_id WHERE provider_onboarding.id = " . intval($onboarding_id) . " LIMIT 1");
    if (!$res || $res->num_rows === 0) return ['ok'=>false,'error'=>'Onboarding not found'];
    $row = $res->fetch_assoc(); $res->free();
    $provider_id = intval($row['provider_id']);
    $existing = json_decode($row['verification_docs'] ?? '[]', true) ?: [];

    $uploaded = [];
    foreach ($files as $file) {
        $r = file_upload_handle($file, __DIR__ . '/../public/uploads/providers/verification', 10 * 1024 * 1024, true);
        if (!$r['ok']) return ['ok'=>false,'error'=>'Upload failed: ' . $r['error']];
        $uploaded[] = '/public/uploads/providers/verification/' . $r['filename'];
    }
    $merged = array_values(array_merge($existing, $uploaded));
    $mysqli->query("UPDATE providers SET verification_docs = '" . $mysqli->real_escape_string(json_encode($merged)) . "', verification_status = 'pending' WHERE id = " . intval($provider_id));
    $mysqli->query("UPDATE provider_onboarding SET step = 'verification', progress = '" . $mysqli->real_escape_string(json_encode(['documents' => 'submitted'])) . "', updated_at = NOW() WHERE id = " . intval($onboarding_id));
    $actor = $actor_user_id ? intval($actor_user_id) : 'NULL';
    $mysqli->query("INSERT INTO provider_verification_logs (provider_id, actor_user_id, action, note) VALUES (" . intval($provider_id) . ", " . ($actor === 'NULL' ? "NULL" : $actor) . ", 'submitted', 'Provider submitted verification docs via onboarding')");
    return ['ok'=>true];
}

function onboarding_check_duplicates(int $provider_id): array {
    global $mysqli;
    $p = provider_find($provider_id);
    if (!$p) return ['status'=>'no_duplicate','matches'=>[]];
    $name = $mysqli->real_escape_string($p['name']);
    $city = $mysqli->real_escape_string($p['city'] ?? '');
    $phone = $mysqli->real_escape_string($p['phone'] ?? '');

    $where = [];
    if ($name !== '') $where[] = "name LIKE '%$name%'";
    if ($city !== '') $where[] = "city = '$city'";
    if ($phone !== '') $where[] = "phone = '$phone'";

    if (empty($where)) return ['status'=>'no_duplicate','matches'=>[]];

    $sql = "SELECT id, name, city, phone FROM providers WHERE (" . implode(' OR ', $where) . ") AND id != " . intval($provider_id) . " LIMIT 10";
    $matches = [];
    if ($res = $mysqli->query($sql)) {
        while ($r = $res->fetch_assoc()) $matches[] = $r;
        $res->free();
    }
    return ['status' => empty($matches) ? 'no_duplicate' : 'possible_duplicate', 'matches' => $matches];
}

function onboarding_admin_action(int $onboarding_id, string $action, int $actor_user_id = null, string $note = '') {
    global $mysqli;
    $res = $mysqli->query("SELECT provider_id FROM provider_onboarding WHERE id = " . intval($onboarding_id) . " LIMIT 1");
    if (!$res || $res->num_rows === 0) return ['ok'=>false,'error'=>'Onboarding not found'];
    $row = $res->fetch_assoc(); $res->free();
    $provider_id = intval($row['provider_id']);

    $map = [
        'approve' => 'verified',
        'reject' => 'rejected',
        'request_more' => 'pending'
    ];
    if (!isset($map[$action])) return ['ok'=>false,'error'=>'Invalid action'];

    $newStatus = $map[$action];
    $mysqli->query("UPDATE providers SET verification_status = '" . $mysqli->real_escape_string($newStatus) . "' WHERE id = " . intval($provider_id));
    $step = $action === 'approve' ? 'complete' : ($action === 'reject' ? 'rejected' : 'verification');
    $mysqli->query("UPDATE provider_onboarding SET step = '" . $mysqli->real_escape_string($step) . "', updated_at = NOW() WHERE id = " . intval($onboarding_id));
    $actor = $actor_user_id ? intval($actor_user_id) : 'NULL';
    $mysqli->query("INSERT INTO provider_verification_logs (provider_id, actor_user_id, action, note) VALUES (" . intval($provider_id) . ", " . ($actor === 'NULL' ? "NULL" : $actor) . ", '" . $mysqli->real_escape_string($action) . "', '" . $mysqli->real_escape_string($note) . "')");
    return ['ok'=>true];
}

function onboarding_pending_count(): int {
    global $mysqli;
    $cnt = 0;
    $sql = "SELECT COUNT(*) AS cnt FROM provider_onboarding WHERE step IN ('profile','documents','verification') OR (step = 'verification' AND EXISTS (SELECT 1 FROM providers p WHERE p.id = provider_onboarding.provider_id AND p.verification_status = 'pending'))";
    if ($res = $mysqli->query($sql)) {
        $row = $res->fetch_assoc();
        $cnt = intval($row['cnt'] ?? 0);
        $res->free();
    }
    return $cnt;
}
function onboarding_recent(int $limit = 5): array {
    global $mysqli;
    $out = [];
    $limit = max(1, intval($limit));
    $sql = "SELECT po.id AS onb_id, po.provider_id, p.name AS provider_name, p.email, po.step, po.duplicate_check_status, p.verification_status, po.created_at
            FROM provider_onboarding po
            LEFT JOIN providers p ON p.id = po.provider_id
            ORDER BY po.created_at DESC
            LIMIT " . intval($limit);
    if ($res = $mysqli->query($sql)) {
        while ($r = $res->fetch_assoc()) $out[] = $r;
        $res->free();
    }
    return $out;
}
