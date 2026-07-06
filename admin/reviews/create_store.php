<?php
// admin/reviews/create_store.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('reviews.manage');
require_once __DIR__ . '/../../lib/reviews_helpers.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $domain . '/admin/reviews/index.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$current = current_user();
$admin_id = intval($current['id']);
$user_id = intval($_POST['user_id'] ?? 0);
$provider_id = intval($_POST['provider_id'] ?? 0) ?: null;
$service_id = intval($_POST['service_id'] ?? 0) ?: null;
$rating = intval($_POST['rating'] ?? 0);
$title = trim($_POST['title'] ?? '');
$body = trim($_POST['body'] ?? '');
$publish_now = !empty($_POST['publish_now']);
$bypass_dups = !empty($_POST['bypass_duplicates']);

// If both service and provider provided, prefer service
if ($service_id) $provider_id = null;

$data = [
  'admin_user_id' => $admin_id,
  'user_id' => $user_id,
  'provider_id' => $provider_id,
  'service_id' => $service_id,
  'rating' => $rating,
  'title' => $title,
  'body' => $body,
  'publish_now' => $publish_now,
  'bypass_duplicates' => $bypass_dups
];

$res = review_create_admin($data);
if (!$res['ok']) {
    $_SESSION['flash_errors'] = [$res['error']];
    header('Location: ' . $domain . '/admin/reviews/create.php'); exit;
}

$_SESSION['flash_success'] = 'Review created successfully.';
header('Location: ' . $domain . '/admin/reviews/review.php?id=' . intval($res['id']));
exit;
