<?php
// provider/settings_save.php
// Handles saving provider settings. Accessible to provider owner or admins with providers.manage.

require_once __DIR__ . '/../lib/middleware.php';
require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/users_helpers.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
// session_start();

$current = current_user();

// CSRF helper fallback
if (!function_exists('csrf_check')) {
    function csrf_check($token) {
        if (empty($_SESSION['_csrf']) || empty($token)) return false;
        return hash_equals($_SESSION['_csrf'], $token);
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field() {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($_SESSION['_csrf'], ENT_QUOTES) . '">';
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$csrf = $_POST['_csrf'] ?? '';
if (!csrf_check($csrf)) {
    $_SESSION['flash_errors'] = 'Invalid CSRF token.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

$provider_id = isset($_POST['provider_id']) ? intval($_POST['provider_id']) : 0;
if ($provider_id <= 0) {
    $_SESSION['flash_errors'] = 'Provider id is required.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

// Fetch provider
$provRes = $mysqli->query("SELECT * FROM providers WHERE id = " . $provider_id . " LIMIT 1");
if (!$provRes || $provRes->num_rows === 0) {
    $_SESSION['flash_errors'] = 'Provider not found.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}
$provider = $provRes->fetch_assoc();
$provRes->free();

// Permission check: owner or admin with providers.manage
$is_owner = (!empty($provider['owner_user_id']) && intval($provider['owner_user_id']) === intval($current['id']));
if (!$is_owner && !user_has_permission($current['id'], 'providers.manage')) {
    $_SESSION['flash_errors'] = 'You do not have permission to update this provider.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

// Validate and sanitize inputs
$name = trim($_POST['name'] ?? $provider['name'] ?? '');
$phone = trim($_POST['phone'] ?? $provider['phone'] ?? '');
$description = trim($_POST['description'] ?? $provider['description'] ?? '');
$notify_email = isset($_POST['notify_email']) ? 1 : 0;
$settings = [
    'notify_email' => $notify_email
];

// Basic validation
$errors = [];
if ($name === '') $errors[] = 'Provider name is required.';
if (!empty($phone) && strlen($phone) > 50) $errors[] = 'Phone is too long.';

if (!empty($errors)) {
    $_SESSION['flash_errors'] = $errors;
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/provider/settings.php?id=' . $provider_id));
    exit;
}

// Persist changes
$name_sql = $mysqli->real_escape_string($name);
$phone_sql = $mysqli->real_escape_string($phone);
$desc_sql = $mysqli->real_escape_string($description);
$settings_sql = $mysqli->real_escape_string(json_encode($settings));

$updateSql = "UPDATE providers SET
    name = '$name_sql',
    phone = '$phone_sql',
    description = '$desc_sql',
    settings = '$settings_sql',
    updated_at = NOW()
    WHERE id = " . intval($provider_id);

if (!$mysqli->query($updateSql)) {
    $_SESSION['flash_errors'] = 'Failed to update provider: ' . $mysqli->error;
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/provider/settings.php?id=' . $provider_id));
    exit;
}

// Audit log
$actor = intval($current['id']);
$note = $mysqli->real_escape_string("Updated provider settings: name={$name}, phone={$phone}");
$mysqli->query("INSERT INTO audit_logs (actor_user_id, action, target_type, target_id, note) VALUES ($actor, 'update_settings', 'provider', ".intval($provider_id).", '$note')");

// Success
$_SESSION['flash_success'] = 'Provider settings updated successfully.';
header('Location: /provider/settings.php?id=' . intval($provider_id));
exit;
