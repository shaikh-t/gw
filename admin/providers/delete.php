<?php
// admin/providers/delete.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/providers_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/providers'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) { header('Location: ' . $domain . '/admin/providers'); exit; }
// Prevent accidental deletion of providers with services/bookings in production (optional check)
// Example: check services table for provider_id before delete

if (provider_delete($id)) {
    $_SESSION['flash_success'] = 'Provider deleted';
} else {
    $_SESSION['flash_errors'] = ['Delete failed'];
}
header('Location: ' . $domain . '/admin/providers/index.php');
exit;
