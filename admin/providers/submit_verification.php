<?php
// providers/submit_verification.php
require_once __DIR__ . '/../lib/middleware.php';
require_login(); // provider owner must be logged in
require_once __DIR__ . '/../lib/providers_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /dashboard.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$providerId = intval($_POST['provider_id'] ?? 0);
$provider = provider_find($providerId);
if (!$provider) { http_response_code(404); echo 'Provider not found'; exit; }

// Only owner or admin can submit verification
$current = current_user();
$isOwner = isset($current['id']) && $current['id'] == $provider['owner_user_id'];
if (!$isOwner && !can('providers.manage')) {
    http_response_code(403); echo 'Forbidden'; exit;
}

$uploaded = [];
if (!empty($_FILES['verification_docs'])) {
    // allow multiple files
    $files = $_FILES['verification_docs'];
    for ($i=0;$i<count($files['name']);$i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $fileArr = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];
        $res = avatar_upload_handle($fileArr, __DIR__ . '/../public/uploads/providers/verification');
        if (!$res['ok']) {
            $_SESSION['flash_errors'] = ['Upload error: ' . $res['error']];
            header('Location: /providers/dashboard.php'); exit;
        }
        $uploaded[] = '/public/uploads/providers/verification/' . $res['filename'];
    }
}

if (!empty($uploaded)) {
    // merge with existing docs
    $existing = json_decode($provider['verification_docs'] ?? '[]', true) ?: [];
    $merged = array_values(array_merge($existing, $uploaded));
    $update = provider_update($providerId, ['verification_status' => 'pending', 'verification_docs' => json_encode($merged)]);
    // provider_update doesn't currently accept verification_docs directly; update via direct query
    global $mysqli;
    $mysqli->query("UPDATE providers SET verification_status='pending', verification_docs = '" . $mysqli->real_escape_string(json_encode($merged)) . "' WHERE id = " . intval($providerId));
    // log
    $actor = current_user()['id'] ?? null;
    $mysqli->query("INSERT INTO provider_verification_logs (provider_id, actor_user_id, action, note) VALUES (" . intval($providerId) . ", " . ($actor ? intval($actor) : "NULL") . ", 'submitted', 'Provider submitted verification docs')");
}

$_SESSION['flash_success'] = 'Verification documents submitted. Admin will review.';
header('Location: /providers/dashboard.php');
exit;
