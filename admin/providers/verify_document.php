<?php
// admin/providers/verify_document.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/providers_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $domain . '/admin/provider_overview.php');
    exit;
}

if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF');
}

$docUuid = $_POST['doc_uuid'] ?? '';
$action = $_POST['action'] ?? ''; // 'status' or 'toggle_frontend'
$status = $_POST['status'] ?? '';
$show_on_frontend = isset($_POST['show_on_frontend']) ? intval($_POST['show_on_frontend']) : 0;

$doc = provider_document_find($docUuid);
if (!$doc) {
    $_SESSION['flash_errors'] = ['Document not found.'];
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? ($domain . '/admin/provider_overview.php'));
    exit;
}

$provider = provider_find($doc['provider_id']);
$provUuid = $provider['uuid'];

global $mysqli;

if ($action === 'status') {
    if (!in_array($status, ['pending', 'verified', 'rejected'], true)) {
        $_SESSION['flash_errors'] = ['Invalid document status.'];
    } else {
        $stmt = $mysqli->prepare("UPDATE provider_documents SET status = ? WHERE uuid = ?");
        $stmt->bind_param('ss', $status, $docUuid);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Document verification status updated successfully.';
        } else {
            $_SESSION['flash_errors'] = ['Failed to update status: ' . $mysqli->error];
        }
        $stmt->close();
    }
} elseif ($action === 'toggle_frontend') {
    $stmt = $mysqli->prepare("UPDATE provider_documents SET show_on_frontend = ? WHERE uuid = ?");
    $stmt->bind_param('is', $show_on_frontend, $docUuid);
    if ($stmt->execute()) {
        $_SESSION['flash_success'] = 'Document visibility updated successfully.';
    } else {
        $_SESSION['flash_errors'] = ['Failed to update visibility: ' . $mysqli->error];
    }
    $stmt->close();
}

header('Location: ' . $domain . '/admin/providers/edit.php?uuid=' . urlencode($provUuid));
exit;
