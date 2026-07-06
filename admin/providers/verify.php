<?php
// admin/providers/verify.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/providers_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/providers'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$note = trim($_POST['note'] ?? '');

if ($id <= 0 || !in_array($action, ['admin_approved','admin_rejected','admin_requested_more'], true)) {
    $_SESSION['flash_errors'] = ['Invalid input'];
    header('Location: /admin/providers/edit.php?id=' . $id); exit;
}

$statusMap = [
  'admin_approved' => 'verified',
  'admin_rejected' => 'rejected',
  'admin_requested_more' => 'pending'
];

$newStatus = $statusMap[$action] ?? 'pending';

// update provider verification_status
global $mysqli;
$ok = $mysqli->query("UPDATE providers SET verification_status = '" . $mysqli->real_escape_string($newStatus) . "' WHERE id = " . intval($id));
if ($ok) {
    // log action
    $actor = current_user()['id'] ?? null;
    $stmtNote = $mysqli->real_escape_string($note);
    $mysqli->query("INSERT INTO provider_verification_logs (provider_id, actor_user_id, action, note) VALUES (" . intval($id) . ", " . ($actor ? intval($actor) : "NULL") . ", '" . $mysqli->real_escape_string($action) . "', '$stmtNote')");
    $_SESSION['flash_success'] = 'Verification status updated';
} else {
    $_SESSION['flash_errors'] = ['Update failed: ' . $mysqli->error];
}

header('Location: /admin/providers/edit.php?id=' . $id);
exit;
