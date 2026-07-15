<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
require_once __DIR__ . '/../lib/services_helpers.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: services.php');
    exit;
}

require_once __DIR__ . '/../lib/csrf.php';
if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF token');
}

$user = current_user();
$providers = providers_for_user($user['uuid']);
if (empty($providers)) { die("No provider account found."); }
$provider = provider_find($providers[0]['uuid']);
$pid = (int)$provider['id'];

$uuid = $_POST['uuid'] ?? '';
$service = service_find($uuid);

if (!$service || $service['provider_id'] != $pid) {
    die("Service not found or unauthorized access.");
}

$id = (int)$service['id'];

if (service_delete($id)) {
    $_SESSION['flash_success'] = "Service successfully removed from your offerings.";
} else {
    $_SESSION['flash_errors'] = ["Failed to remove service."];
}

header('Location: services.php');
exit;
