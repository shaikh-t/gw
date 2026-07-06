<?php
// providers/services/delete.php
require_once __DIR__ . '/../../lib/middleware.php';
require_login();
require_once __DIR__ . '/../../lib/services_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /providers/services/index.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$current = current_user();
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) { header('Location: /providers/services/index.php'); exit; }

$service = service_find($id);
if (!$service) { $_SESSION['flash_errors'] = ['Service not found']; header('Location: /providers/services/index.php'); exit; }

// ensure ownership
$prov = provider_find($service['provider_id']);
if (!$prov || intval($prov['owner_user_id']) !== intval($current['id'])) {
    http_response_code(403); echo 'Forbidden'; exit;
}

if (service_delete($id)) {
    $_SESSION['flash_success'] = 'Service deleted';
} else {
    $_SESSION['flash_errors'] = ['Delete failed'];
}
header('Location: /providers/services/index.php');
exit;
