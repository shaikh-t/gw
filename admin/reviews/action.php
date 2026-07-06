<?php
// admin/reviews/action.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('reviews.manage');
require_once __DIR__ . '/../../lib/reviews_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/reviews/index.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$note = trim($_POST['note'] ?? '');
$current = current_user();

$res = review_moderate($id, $action, $current['id'], $note);
if (!$res['ok']) {
    $_SESSION['flash_errors'] = [$res['error'] ?? 'Action failed'];
    header('Location: /admin/reviews/review.php?id=' . $id); exit;
}

$_SESSION['flash_success'] = 'Action applied';
header('Location: /admin/reviews/index.php');
exit;
