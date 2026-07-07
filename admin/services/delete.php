<?php
// admin/services/delete.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/services_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/services'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id_val = $_POST['id'] ?? '';
$service = service_find($id_val);
if (!$service) { header('Location: ' . $domain . '/admin/services'); exit; }
$id = (int)$service['id'];

if (service_delete($id)) {
    $_SESSION['flash_success'] = 'Service deleted';
} else {
    $_SESSION['flash_errors'] = ['Delete failed'];
}
header('Location: ' . $domain . '/admin/services/index.php');
exit;
