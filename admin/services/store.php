<?php
// admin/services/store.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/services_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/services'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$data = [
  'provider_id' => null, // Master services do not have a provider
  'master_service_id' => null, // Master services do not reference another service
  'title' => $_POST['title'] ?? '',
  'short_description' => $_POST['short_description'] ?? '',
  'description' => $_POST['description'] ?? '',
  'category_id' => $_POST['category_id'] ?? null,
  'status' => $_POST['status'] ?? 'draft',
  'tag_ids' => $_POST['tag_ids'] ?? [],
  'icon_class' => $_POST['icon_class'] ?? 'bi-award',
  'price' => null,
  'currency' => 'AED',
  'duration_minutes' => null,
  'duration_text' => '5–7 days'
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

$res = service_create($data);
if (!$res['ok']) {
    $_SESSION['flash_errors'] = [$res['error']];
    header('Location: ' . $domain . '/admin/services/create.php'); exit;
}

$_SESSION['flash_success'] = 'Master service created successfully';
header('Location: ' . $domain . '/admin/services/index.php');
exit;
