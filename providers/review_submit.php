<?php
// providers/review_submit.php
require_once __DIR__ . '/../lib/middleware.php';
require_login();
require_once __DIR__ . '/../lib/reviews_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$current = current_user();
$provider_id = intval($_POST['provider_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$title = trim($_POST['title'] ?? '');
$body = trim($_POST['body'] ?? '');

// Simple CAPTCHA / spam check
$captcha = trim($_POST['captcha_answer'] ?? '');
if (strtolower($captcha) !== 'human') {
    $_SESSION['flash_errors'] = ['Anti-spam check failed'];
    header('Location: /providers/reviews_list.php?provider_id=' . $provider_id); exit;
}

require_once __DIR__ . '/../lib/providers_helpers.php';
$provider = provider_find($provider_id);
if (!$provider) { $_SESSION['flash_errors'] = ['Provider not found']; header('Location: /'); exit; }

$data = [
  'user_id' => $current['id'],
  'provider_id' => $provider_id,
  'rating' => $rating,
  'title' => $title,
  'body' => $body
];

$res = review_create($data);
if (!$res['ok']) {
    $_SESSION['flash_errors'] = [$res['error']];
    header('Location: /providers/reviews_list.php?provider_id=' . $provider_id); exit;
}

$_SESSION['flash_success'] = 'Thanks for your review. It will appear once approved.';
header('Location: /providers/reviews_list.php?provider_id=' . $provider_id);
exit;
