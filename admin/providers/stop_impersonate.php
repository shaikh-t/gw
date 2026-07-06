<?php
// admin/providers/stop_impersonate.php
require_once __DIR__ . '/../../lib/middleware.php';
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/users_helpers.php';
session_start();

// Only allow POST to stop impersonation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $_SESSION['flash_errors'] = 'Invalid request method.';
    header('Location: ' . $domain . '/');
    exit;
}

// CSRF check
$token = $_POST['_csrf'] ?? '';
if (empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $token)) {
    $_SESSION['flash_errors'] = 'Invalid CSRF token.';
    header('Location: ' . $domain . '/');
    exit;
}

// Ensure impersonation is active
if (empty($_SESSION['impersonator_id'])) {
    $_SESSION['flash_errors'] = 'No active impersonation session.';
    header('Location: ' . $domain . '/');
    exit;
}

$original_admin_id = intval($_SESSION['impersonator_id']);
$current_impersonated = intval($_SESSION['user_id'] ?? 0);
$impersonated_provider = intval($_SESSION['impersonating_provider_id'] ?? 0);

// Restore original admin session
$_SESSION['user_id'] = $original_admin_id;
unset($_SESSION['impersonator_id'], $_SESSION['impersonating_provider_id']);

// Audit log
$actor = $original_admin_id;
$note = $mysqli->real_escape_string("Stopped impersonation of user {$current_impersonated} (provider {$impersonated_provider})");
$mysqli->query("INSERT INTO audit_logs (actor_user_id, action, target_type, target_id, note) VALUES ($actor, 'impersonate_stop', 'provider', ".intval($impersonated_provider).", '$note')");

// Flash and redirect back to admin provider overview
$_SESSION['flash_success'] = 'Impersonation ended. You are back as admin.';
header('Location: ' . $domain . '/admin/provider_overview.php');
exit;
