<?php
// admin/services/store.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/services_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/services'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$data = [
  'provider_id' => $_POST['provider_id'] ?? null,
  'title' => $_POST['title'] ?? '',
  'short_description' => $_POST['short_description'] ?? '',
  'description' => $_POST['description'] ?? '',
  'price' => $_POST['price'] ?? '',
  'currency' => $_POST['currency'] ?? 'USD',
  'duration_minutes' => $_POST['duration_minutes'] ?? null,
  'category_id' => $_POST['category_id'] ?? null,
  'status' => $_POST['status'] ?? 'draft',
  'tag_ids' => $_POST['tag_ids'] ?? [],
  'icon_class' => $_POST['icon_class'] ?? 'bi-award',
  'duration_text' => $_POST['duration_text'] ?? '5–7 days'
];

if (!empty($_FILES['images'])) {
    // normalize files into array of file arrays
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

$_SESSION['flash_success'] = 'Service created';
header('Location: ' . $domain . '/admin/services/index.php');
exit;
