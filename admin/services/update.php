<?php
// admin/services/update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/services_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/services'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id_val = $_POST['id'] ?? '';
$service = service_find($id_val);
if (!$service) { $_SESSION['flash_errors'] = ['Invalid service id']; 
header('Location: ' . $domain . '/admin/services'); 
exit; }
$id = (int)$service['id'];
$uuid = $service['uuid'];
$data = [
  'title' => $_POST['title'] ?? null,
  'short_description' => $_POST['short_description'] ?? null,
  'description' => $_POST['description'] ?? null,
  'price' => $_POST['price'] ?? null,
  'currency' => $_POST['currency'] ?? null,
  'duration_minutes' => $_POST['duration_minutes'] ?? null,
  'category_id' => $_POST['category_id'] ?? null,
  'status' => $_POST['status'] ?? null,
  'tag_ids' => $_POST['tag_ids'] ?? [],
  'icon_class' => $_POST['icon_class'] ?? null,
  'duration_text' => $_POST['duration_text'] ?? null
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
    echo $res['error'].'----<br>';
    $_SESSION['flash_errors'] = [$res['error']];
    echo "2";
  //  header('Location: ' . $domain . '/admin/services/edit.php?uuid=' . $uuid); exit;
}

$_SESSION['flash_success'] = 'Service updated';
header('Location: ' . $domain . '/admin/services/index.php');
exit;
