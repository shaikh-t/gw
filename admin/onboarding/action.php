<?php
// admin/onboarding/action.php
require_once __DIR__ . '/../../lib/middleware.php';
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/users_helpers.php';
session_start();
require_permission_or_die('providers.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $_POST['_csrf'] ?? '')) {
    $_SESSION['flash_errors'] = 'Invalid CSRF token.';
    header('Location: ' . $domain . '/admin/provider_overview.php');
    exit;
}

$entry_id = intval($_POST['entry_id'] ?? 0);
$action = $_POST['action'] ?? '';
$notes = $mysqli->real_escape_string(trim($_POST['notes'] ?? ''));
$assigned_user = isset($_POST['assigned_user_id']) ? intval($_POST['assigned_user_id']) : null;

if ($entry_id <= 0 || !in_array($action, ['in_review','needs_info','approved','rejected'])) {
    $_SESSION['flash_errors'] = 'Invalid request.';
    header('Location: ' . $domain . '/admin/provider_overview.php');
    exit;
}

$entryRes = $mysqli->query("SELECT * FROM onboarding_queue WHERE id = $entry_id LIMIT 1");
if (!$entryRes || $entryRes->num_rows === 0) {
    $_SESSION['flash_errors'] = 'Onboarding entry not found.';
    header('Location: ' . $domain . '/admin/provider_overview.php');
    exit;
}
$entry = $entryRes->fetch_assoc();
$entryRes->free();

$assigned_sql = $assigned_user ? intval($assigned_user) : 'NULL';
$mysqli->query("UPDATE onboarding_queue SET status = '".$mysqli->real_escape_string($action)."', assigned_user_id = $assigned_sql, notes = '".$mysqli->real_escape_string($notes)."', updated_at = NOW(), processed_at = ".($action === 'approved' || $action === 'rejected' ? "NOW()" : "NULL")." WHERE id = ".intval($entry_id));

if ($action === 'approved') {
    $mysqli->query("UPDATE providers SET verification_status = 'approved', updated_at = NOW() WHERE id = ".intval($entry['provider_id']));
} elseif ($action === 'rejected') {
    $mysqli->query("UPDATE providers SET verification_status = 'rejected', updated_at = NOW() WHERE id = ".intval($entry['provider_id']));
} elseif ($action === 'needs_info') {
    $mysqli->query("UPDATE providers SET verification_status = 'needs_info', updated_at = NOW() WHERE id = ".intval($entry['provider_id']));
} else {
    $mysqli->query("UPDATE providers SET verification_status = 'pending', updated_at = NOW() WHERE id = ".intval($entry['provider_id']));
}

// Audit
$actor = intval(current_user()['id']);
$note = $mysqli->real_escape_string("Onboarding action: $action; notes: $notes");
$mysqli->query("INSERT INTO audit_logs (actor_user_id, action, target_type, target_id, note) VALUES ($actor, 'onboarding_$action', 'provider', ".intval($entry['provider_id']).", '$note')");

$_SESSION['flash_success'] = 'Onboarding updated.';
header('Location: ' . $domain . '/admin/onboarding/view.php?id=' . intval($entry_id));
exit;
