<?php
// services/review_submit.php
require_once __DIR__ . '/../lib/middleware.php';
require_login();
require_once __DIR__ . '/../lib/reviews_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$current = current_user();
$service_id = intval($_POST['service_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$title = trim($_POST['title'] ?? '');
$body = trim($_POST['body'] ?? '');

// Simple CAPTCHA / spam check
$captcha = trim($_POST['captcha_answer'] ?? '');
if (strtolower($captcha) !== 'human') {
    $_SESSION['flash_errors'] = ['Anti-spam check failed'];
    header('Location: /services/reviews_list.php?service_id=' . $service_id); exit;
}

$service = null;
if ($service_id) {
    require_once __DIR__ . '/../lib/services_helpers.php';
    $service = service_find($service_id);
    if (!$service) { $_SESSION['flash_errors'] = ['Service not found']; header('Location: /'); exit; }
}

$data = [
  'user_id' => $current['id'],
  'service_id' => $service_id,
  'provider_id' => $service ? $service['provider_id'] : null,
  'rating' => $rating,
  'title' => $title,
  'body' => $body
];

$res = review_create($data);
if (!$res['ok']) {
    $_SESSION['flash_errors'] = [$res['error']];
    header('Location: /services/reviews_list.php?service_id=' . $service_id); exit;
}

$_SESSION['flash_success'] = 'Thanks for your review. It will appear once approved.';
header('Location: /services/reviews_list.php?service_id=' . $service_id);
exit;
