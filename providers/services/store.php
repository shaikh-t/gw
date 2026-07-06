<?php
// providers/services/store.php
require_once __DIR__ . '/../../lib/middleware.php';
require_login();
require_once __DIR__ . '/../../lib/services_helpers.php';
require_once __DIR__ . '/../../lib/providers_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /providers/dashboard.php');
    exit;
}
if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF');
}

$current = current_user();
if (empty($current['id'])) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// find provider owned by current user
$res = $mysqli->query("SELECT id FROM providers WHERE owner_user_id = " . intval($current['id']) . " LIMIT 1");
if (!$res || $res->num_rows === 0) {
    $_SESSION['flash_errors'] = ['No provider profile found for your account.'];
    header('Location: /providers/dashboard.php');
    exit;
}
$prov = $res->fetch_assoc();
$provider_id = intval($prov['id']);
$res->free();

// prepare data
$data = [
    'provider_id' => $provider_id,
    'title' => trim($_POST['title'] ?? ''),
    'short_description' => trim($_POST['short_description'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'price' => $_POST['price'] ?? '',
    'currency' => $_POST['currency'] ?? 'USD',
    'duration_minutes' => $_POST['duration_minutes'] ?? null,
    'category_id' => $_POST['category_id'] ?? null,
    'status' => $_POST['status'] ?? 'draft',
    'tag_ids' => $_POST['tag_ids'] ?? []
];

// normalize uploaded images
if (!empty($_FILES['images'])) {
    $files = [];
    // support both single and multiple
    if (is_array($_FILES['images']['name'])) {
        foreach ($_FILES['images']['name'] as $i => $name) {
            $files[] = [
                'name' => $_FILES['images']['name'][$i],
                'type' => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error' => $_FILES['images']['error'][$i],
                'size' => $_FILES['images']['size'][$i]
            ];
        }
    } else {
        $files[] = $_FILES['images'];
    }
    $data['image_files'] = $files;
}

$res = service_create($data);
if (!$res['ok']) {
    $_SESSION['flash_errors'] = [$res['error']];
    header('Location: /providers/services/create.php');
    exit;
}

$_SESSION['flash_success'] = 'Service created';
header('Location: /providers/services/edit.php?id=' . intval($res['id']));
exit;
