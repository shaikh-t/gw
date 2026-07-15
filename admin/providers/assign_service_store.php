<?php
// admin/providers/assign_service_store.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/providers_helpers.php';
require_once __DIR__ . '/../../lib/services_helpers.php';
require_once __DIR__ . '/../../lib/uuid_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $domain . '/admin/providers');
    exit;
}

if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF');
}

global $mysqli;

$provider_uuid = $_POST['provider_uuid'] ?? '';
$provider = provider_find($provider_uuid);
if (!$provider) {
    die("Provider not found.");
}
$pid = (int)$provider['id'];

$master_service_id = isset($_POST['master_service_id']) ? intval($_POST['master_service_id']) : 0;
$price = isset($_POST['price']) && $_POST['price'] !== '' ? floatval($_POST['price']) : 0.00;
$currency = $mysqli->real_escape_string($_POST['currency'] ?? 'AED');
$duration_text = $mysqli->real_escape_string(trim($_POST['duration_text'] ?? '5–7 days'));

if ($master_service_id <= 0) {
    $_SESSION['flash_errors'] = 'Invalid master service selection.';
    header('Location: ' . $domain . '/admin/providers/assign_service.php?uuid=' . $provider_uuid);
    exit;
}

// Check for existing assignment
$check = $mysqli->query("SELECT id FROM services WHERE provider_id = $pid AND master_service_id = $master_service_id LIMIT 1");
if ($check && $check->num_rows > 0) {
    $check->free();
    $_SESSION['flash_errors'] = 'This provider already offers this service.';
    header('Location: ' . $domain . '/admin/providers/assign_service.php?uuid=' . $provider_uuid);
    exit;
}
if ($check) $check->free();

// Fetch master service info
$master = service_find($master_service_id);
if (!$master) {
    $_SESSION['flash_errors'] = 'Master service template not found.';
    header('Location: ' . $domain . '/admin/providers/assign_service.php?uuid=' . $provider_uuid);
    exit;
}

// Generate beautiful public slug
$base_slug = $master['slug'];
if (strpos($base_slug, 'master-') === 0) {
    $base_slug = substr($base_slug, 7);
}
$prov_slug = provider_slugify($provider['name']);
$slug_candidate = $base_slug . '-' . $prov_slug;
$slug = $slug_candidate;
$i = 1;
while (true) {
    $res = $mysqli->query("SELECT id FROM services WHERE slug = '" . $mysqli->real_escape_string($slug) . "' LIMIT 1");
    if ($res && $res->num_rows === 0) {
        if ($res) $res->free();
        break;
    }
    if ($res) $res->free();
    $slug = $slug_candidate . '-' . $i++;
}

$uuid = generate_uuid();
$sql = "INSERT INTO services (uuid, provider_id, master_service_id, price, currency, duration_text, status, slug, title, created_at)
        VALUES ('$uuid', $pid, $master_service_id, $price, '$currency', '$duration_text', 'published', '" . $mysqli->real_escape_string($slug) . "', '', NOW())";

if ($mysqli->query($sql)) {
    $_SESSION['flash_success'] = 'Master service successfully assigned to provider.';
    header('Location: ' . $domain . '/admin/providers/dashboard.php?uuid=' . $provider_uuid);
} else {
    $_SESSION['flash_errors'] = 'Failed to assign service: ' . $mysqli->error;
    header('Location: ' . $domain . '/admin/providers/assign_service.php?uuid=' . $provider_uuid);
}
exit;
