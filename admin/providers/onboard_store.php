<?php
// admin/providers/onboard_store.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/onboarding_helpers.php';
require_once __DIR__ . '/../../lib/providers_helpers.php';
require_once __DIR__ . '/../../lib/notifier.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/providers/index.php'); exit; }
if (!csrf_check($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

$current = current_user();

// Prepare provider data
$data = [
  'name' => trim($_POST['name'] ?? ''),
  'owner_user_id' => !empty($_POST['owner_user_id']) ? intval($_POST['owner_user_id']) : null,
  'email' => trim($_POST['email'] ?? ''),
  'phone' => trim($_POST['phone'] ?? ''),
  'address' => trim($_POST['address'] ?? ''),
  'city' => trim($_POST['city'] ?? ''),
  'state' => trim($_POST['state'] ?? ''),
  'country' => trim($_POST['country'] ?? ''),
  'description' => trim($_POST['description'] ?? ''),
  'status' => $_POST['status'] ?? 'draft'
];

// Start onboarding (creates provider and onboarding record)
$res = onboarding_start($data);
if (!$res['ok']) {
    $_SESSION['flash_errors'] = [$res['error']];
    header('Location: /admin/providers/create_onboard.php');
    exit;
}

$onb_id = intval($res['onboarding_id']);
$provider_id = intval($res['provider_id']);

// Handle uploaded verification docs (if any)
if (!empty($_FILES['verification_docs'])) {
    $files = [];
    // normalize files array
    if (is_array($_FILES['verification_docs']['name'])) {
        for ($i=0;$i<count($_FILES['verification_docs']['name']);$i++) {
            if ($_FILES['verification_docs']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            $files[] = [
                'name' => $_FILES['verification_docs']['name'][$i],
                'type' => $_FILES['verification_docs']['type'][$i],
                'tmp_name' => $_FILES['verification_docs']['tmp_name'][$i],
                'error' => $_FILES['verification_docs']['error'][$i],
                'size' => $_FILES['verification_docs']['size'][$i]
            ];
        }
    } else {
        if ($_FILES['verification_docs']['error'] !== UPLOAD_ERR_NO_FILE) $files[] = $_FILES['verification_docs'];
    }

    if (!empty($files)) {
        $r2 = onboarding_submit_documents($onb_id, $files, $current['id']);
        if (!$r2['ok']) {
            $_SESSION['flash_errors'] = [$r2['error']];
            header('Location: /admin/providers/create_onboard.php');
            exit;
        }
    }
}

// Log admin action and notify owner (if assigned)
$ownerId = $data['owner_user_id'];
if ($ownerId) {
    // notify owner that admin created provider and started onboarding
    $ownerRes = $mysqli->query("SELECT id, name, email FROM users WHERE id = " . intval($ownerId) . " LIMIT 1");
    if ($ownerRes && $ownerRow = $ownerRes->fetch_assoc()) {
        $provider = provider_find($provider_id);
        notifier_send_email($ownerRow['email'], 'Provider profile created for you', "<p>An admin created a provider profile for you: " . htmlspecialchars($provider['name'], ENT_QUOTES) . ".</p><p>Please review and complete any missing information.</p>");
        $ownerRes->free();
    }
}

// Optionally notify internal admin team
notifier_send_email('admin@example.com', 'Admin created provider onboarding', 'Admin ' . htmlspecialchars($current['name'] ?? 'admin', ENT_QUOTES) . ' created provider ID ' . intval($provider_id) . ' and started onboarding.');

// Redirect to onboarding review page for admin
$_SESSION['flash_success'] = 'Provider created and onboarding started.';
header('Location: /admin/providers/onboarding_review.php?uuid=' . $onb_id);
exit;
