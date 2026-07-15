<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
require_once __DIR__ . '/../lib/services_helpers.php';
require_once __DIR__ . '/../lib/uuid_helper.php';
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

$posted_services = $_POST['services'] ?? [];
if (empty($posted_services)) {
    $_SESSION['flash_errors'] = 'No services submitted.';
    header('Location: services_add.php');
    exit;
}

$success_count = 0;
$errors = [];

foreach ($posted_services as $s) {
    $master_id = isset($s['master_service_id']) ? intval($s['master_service_id']) : 0;
    if ($master_id <= 0) continue;

    $price = isset($s['price']) && $s['price'] !== '' ? floatval($s['price']) : 0.00;
    $currency = $mysqli->real_escape_string($s['currency'] ?? 'AED');
    $duration_text = $mysqli->real_escape_string(trim($s['duration_text'] ?? '5–7 days'));

    // Check if this provider already offers this master service
    $check = $mysqli->query("SELECT id FROM services WHERE provider_id = $pid AND master_service_id = $master_id LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $check->free();
        continue; // skip duplicate assignment
    }
    if ($check) $check->free();

    // Fetch master service info to build beautiful provider-specific slug
    $master = service_find($master_id);
    if (!$master) continue;

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
            VALUES ('$uuid', $pid, $master_id, $price, '$currency', '$duration_text', 'published', '" . $mysqli->real_escape_string($slug) . "', '', NOW())";
    if ($mysqli->query($sql)) {
        $success_count++;
    } else {
        $errors[] = "Failed to add service: " . $mysqli->error;
    }
}

if ($success_count > 0) {
    $_SESSION['flash_success'] = "Successfully added $success_count service(s).";
} else {
    $_SESSION['flash_errors'] = !empty($errors) ? $errors : ['No services were added.'];
}

header('Location: services.php');
exit;
