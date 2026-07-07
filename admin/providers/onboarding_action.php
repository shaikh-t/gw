<?php
// admin/providers/onboarding_action.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/onboarding_helpers.php';
require_once __DIR__ . '/../../lib/notifier.php';
require_once __DIR__ . '/../../lib/db_mysqli.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/providers/onboarding_list.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$onb_id = intval($_POST['onb_id'] ?? 0);
$action = $_POST['action'] ?? '';
$note = trim($_POST['note'] ?? '');
$current = current_user();

$res = onboarding_admin_action($onb_id, $action, $current['id'], $note);
if (!$res['ok']) {
    $_SESSION['flash_errors'] = [$res['error'] ?? 'Action failed'];
    header('Location: /admin/providers/onboarding_review.php?uuid=' . $onb_id); exit;
}

$r = $mysqli->query("SELECT p.* FROM provider_onboarding po JOIN providers p ON p.id = po.provider_id WHERE po.id = " . intval($onb_id) . " LIMIT 1");
if ($r && $row = $r->fetch_assoc()) {
    notifier_onboarding_status_changed($row, $action === 'approve' ? 'verified' : ($action === 'reject' ? 'rejected' : 'pending'));
    $r->free();
}

$_SESSION['flash_success'] = 'Action applied';
header('Location: /admin/providers/onboarding_list.php');
exit;
