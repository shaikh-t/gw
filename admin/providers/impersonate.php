<?php
// admin/providers/impersonate.php
// Allows an admin to impersonate the owner of a provider account.
// Requires providers.manage permission.

require_once __DIR__ . '/../../lib/middleware.php';
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/users_helpers.php';
require_once __DIR__ . '/../../lib/providers_helpers.php';

require_permission_or_die('providers.manage');

$current = current_user();

$provider_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($provider_id <= 0) {
    $_SESSION['flash_errors'] = 'Provider id is required.';
    header('Location: '.$domain.'/admin/provider_overview.php');
    exit;
}

// Fetch provider and owner
$provStmt = $mysqli->query("SELECT id, name, owner_user_id FROM providers WHERE id = " . $provider_id . " LIMIT 1");
if (!$provStmt || $provStmt->num_rows === 0) {
    $_SESSION['flash_errors'] = 'Provider not found.';
    header('Location: '.$domain.'/admin/provider_overview.php');
    exit;
}
$provider = $provStmt->fetch_assoc();
$provStmt->free();

$owner_id = intval($provider['owner_user_id']);
if ($owner_id <= 0) {
    $_SESSION['flash_errors'] = 'Provider has no owner assigned.';
    header('Location: '.$domain.'/admin/provider_overview.php');
    exit;
}

// Fetch owner user
$userRes = $mysqli->query("SELECT id, name, email FROM users WHERE id = $owner_id LIMIT 1");
if (!$userRes || $userRes->num_rows === 0) {
    $_SESSION['flash_errors'] = 'Owner user not found.';
    header('Location: '.$domain.'/admin/provider_overview.php');
    exit;
}
$owner = $userRes->fetch_assoc();
$userRes->free();

// Save impersonation state
// Keep original admin uuid so we can restore later
$_SESSION['impersonator_uuid'] = $current['uuid'];

// Update current user session to impersonated user
$_SESSION['user'] = [
    'uuid' => $owner['uuid'],
    'name' => $owner['name'],
    'email' => $owner['email'],
    'avatar' => $owner['avatar'] ?? null
];

// Optional: store which provider is being impersonated
$_SESSION['impersonating_provider_uuid'] = $provider['uuid'];

// Audit log
$actor_uuid = $mysqli->real_escape_string($current['uuid']);
$note = $mysqli->real_escape_string("Impersonated provider owner {$owner['uuid']} for provider {$provider['uuid']} ({$provider['name']})");
$mysqli->query("INSERT INTO audit_logs (actor_user_id, action, target_type, target_id, note)
                SELECT id, 'impersonate_start', 'provider', ".intval($provider['id']).", '$note'
                FROM users WHERE uuid = '$actor_uuid'");

// Flash and redirect to provider dashboard (owner view)
$_SESSION['flash_success'] = 'Now impersonating provider owner: ' . htmlspecialchars($owner['name'], ENT_QUOTES);
header('Location: '.$domain.'/provider/dashboard.php');
exit;
