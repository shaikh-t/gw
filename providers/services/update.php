<?php
// providers/services/update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_login();
require_once __DIR__ . '/../../lib/services_helpers.php';
require_once __DIR__ . '/../../lib/providers_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /providers/services/index.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$current = current_user();
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) { $_SESSION['flash_errors'] = ['Invalid service id']; header('Location: /providers/services/index.php'); exit; }

$service = service_find($id);
if (!$service) { $_SESSION['flash_errors'] = ['Service not found']; header('Location: /providers/services/index.php'); exit; }

// ensure ownership
$prov = provider_find($service['provider_id']);
if (!$prov || intval($prov['owner_user_id']) !== intval($current['id'])) {
    http_response_code(403); echo 'Forbidden'; exit;
}

$data = [
  'title' => $_POST['title'] ?? null,
  'short_description' => $_POST['short_description'] ?? null,
  'description' => $_POST['description'] ?? null,
  'price' => $_POST['price'] ?? null,
  'currency' => $_POST['currency'] ?? null,
  'duration_minutes' => $_POST['duration_minutes'] ?? null,
  'category_id' => $_POST['category_id'] ?? null,
  'status' => $_POST['status'] ?? null,
  'tag_ids' => $_POST['tag_ids'] ?? []
];

if (!empty($_FILES['images'])) {
    $files = [];
    foreach ($_FILES['images']['name'] as $i => $name) {
        $files[] = [
            'name' => $_FILES['images']['name'][$i],
            'type' => $_FILES['images']['type'][$i],
            'tmp_name' => $_FILES['images']['tmp_name'][$i],
            'error' => $_FILES['images']['error'][$i],
            'size' => $_FILES['images']['size'][$i]
        ];
    }
    $data['image_files'] = $files;
}

$res = service_update($id, $data);
if (!$res['ok']) {
    $_SESSION['flash_errors'] = [$res['error']];
    header('Location: /providers/services/edit.php?id=' . $id); exit;
}

$_SESSION['flash_success'] = 'Service updated';
header('Location: /providers/services/edit.php?id=' . $id);
exit;
