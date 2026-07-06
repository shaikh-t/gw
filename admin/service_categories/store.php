<?php
// admin/service_categories/store.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/services_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/service_categories'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$name = trim($_POST['name'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($name === '') {
    $_SESSION['flash_errors'] = ['Name required'];
    header('Location: ' . $domain . '/admin/service_categories/create.php'); exit;
}

$created = service_category_create($name, $slug ?: null, $desc);
if ($created === false) {
    $_SESSION['flash_errors'] = ['Create failed'];
    header('Location: ' . $domain . '/admin/service_categories/create.php'); exit;
}

$_SESSION['flash_success'] = 'Category created';
header('Location: ' . $domain . '/admin/service_categories/index.php');
exit;
