<?php
// admin/service_tags/store.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/services_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/service_tags'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    $_SESSION['flash_errors'] = ['Name required'];
    header('Location: ' . $domain . '/admin/service_tags/create.php'); exit;
}

$id = service_tag_create($name);
if ($id === false) {
    $_SESSION['flash_errors'] = ['Create failed'];
    header('Location: ' . $domain . '/admin/service_tags/create.php'); exit;
}

$_SESSION['flash_success'] = 'Tag created';
header('Location: ' . $domain . '/admin/service_tags/index.php');
exit;
