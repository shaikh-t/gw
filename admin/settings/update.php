<?php
// admin/settings/update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('settings.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/settings/landing_page.php');
    exit;
}

if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF');
}

// Handle file uploads
$upload_dir = __DIR__ . '/../../public/uploads/site';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Map files to settings keys
$file_map = [
    'hero_bg_file' => 'hero_bg_image',
    'cta_banner_file' => 'cta_banner_bg'
];

// Handle text settings
if (!empty($_POST['settings']) && is_array($_POST['settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $mysqli->prepare("UPDATE site_settings SET value = ? WHERE `key` = ?");
        $stmt->bind_param('ss', $value, $key);
        $stmt->execute();
    }
}

foreach ($file_map as $file_input => $setting_key) {
    if (!empty($_FILES[$file_input]) && $_FILES[$file_input]['error'] === UPLOAD_ERR_OK) {
        $res = avatar_upload_handle($_FILES[$file_input], $upload_dir,900);
        if ($res['ok']) {
            $path = $domain.'/public/uploads/site/' . $res['filename'];
            $stmt = $mysqli->prepare("UPDATE site_settings SET value = ? WHERE `key` = ?");
            $stmt->bind_param('ss', $path, $setting_key);
            $stmt->execute();
        }
    }
}


$_SESSION['flash_success'] = 'Settings updated successfully.';
header('Location: '.$domain.'/admin/settings/landing_page.php');
exit;
