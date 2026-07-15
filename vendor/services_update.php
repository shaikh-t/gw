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

global $mysqli;

$uuid = $_POST['uuid'] ?? '';
$service = service_find($uuid);

if (!$service || $service['provider_id'] != $pid) {
    die("Service not found or unauthorized access.");
}

$id = (int)$service['id'];
$price = isset($_POST['price']) && $_POST['price'] !== '' ? floatval($_POST['price']) : 0.00;
$currency = $mysqli->real_escape_string($_POST['currency'] ?? 'AED');
$duration_text = $mysqli->real_escape_string(trim($_POST['duration_text'] ?? '5–7 days'));

$data = [
    'price' => $price,
    'currency' => $currency,
    'duration_text' => $duration_text
];

$res = service_update($id, $data);
if ($res['ok']) {
    $_SESSION['flash_success'] = "Service updated successfully.";
} else {
    $_SESSION['flash_errors'] = ["Failed to update service: " . $res['error']];
}

header('Location: services.php');
exit;
