<?php
// providers/onboard_store.php
require_once __DIR__ . '/../lib/middleware.php';
require_login();
require_once __DIR__ . '/../lib/onboarding_helpers.php';
require_once __DIR__ . '/../lib/notifier.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /providers/onboard.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$current = current_user();
$data = [
  'name' => $_POST['name'] ?? '',
  'owner_user_id' => $current['id'],
  'email' => $_POST['email'] ?? '',
  'phone' => $_POST['phone'] ?? '',
  'address' => $_POST['address'] ?? '',
  'city' => $_POST['city'] ?? '',
  'state' => $_POST['state'] ?? '',
  'country' => $_POST['country'] ?? '',
  'description' => $_POST['description'] ?? ''
];

$res = onboarding_start($data);
if (!$res['ok']) {
    $_SESSION['flash_errors'] = [$res['error']];
    header('Location: /providers/onboard.php'); exit;
}

$onb_id = intval($res['onboarding_id']);
if (!empty($_FILES['verification_docs'])) {
    $files = [];
    foreach ($_FILES['verification_docs']['name'] as $i => $name) {
        $files[] = [
            'name' => $_FILES['verification_docs']['name'][$i],
            'type' => $_FILES['verification_docs']['type'][$i],
            'tmp_name' => $_FILES['verification_docs']['tmp_name'][$i],
            'error' => $_FILES['verification_docs']['error'][$i],
            'size' => $_FILES['verification_docs']['size'][$i]
        ];
    }
    $r2 = onboarding_submit_documents($onb_id, $files, $current['id']);
    if (!$r2['ok']) {
        $_SESSION['flash_errors'] = [$r2['error']];
        header('Location: /providers/onboard.php'); exit;
    }
}

$provider = provider_find($res['provider_id']);
notifier_send_email('admin@example.com', 'New provider onboarding', 'A new provider has started onboarding: ' . htmlspecialchars($provider['name'], ENT_QUOTES));

$_SESSION['flash_success'] = 'Onboarding started. We will review your documents and notify you.';
header('Location: /providers/onboarding_status.php?onb=' . $onb_id);
exit;
