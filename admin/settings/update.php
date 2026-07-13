<?php
// admin/settings/update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('settings.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: '.$domain.'/admin/settings/landing_page.php');
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

// Handle text settings
if (!empty($_POST['settings']) && is_array($_POST['settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $mysqli->prepare("UPDATE site_settings SET value = ? WHERE `key` = ?");
        $stmt->bind_param('ss', $value, $key);
        $stmt->execute();
    }
}

// Map files to settings keys
$file_map = [
    'hero_bg_file' => 'hero_bg_image',
    'cta_banner_file' => 'cta_banner_bg'
];
    // echo "<pre>";

foreach ($file_map as $file_input => $setting_key) {
    echo "<h4>Processing File Input: '{$file_input}' for Key: '{$setting_key}'</h4>";
    print_r($_FILES[$file_input]);
    echo "<br>";

    if (!empty($_FILES[$file_input]) && $_FILES[$file_input]['error'] === UPLOAD_ERR_OK) {
        $res = avatar_upload_handle($_FILES[$file_input], $upload_dir);
        
        if ($res['ok']) {
            $path = $domain.'/public/uploads/site/' . $res['filename'];
            
            // 1. Setup and print the query string
            $query = "UPDATE site_settings SET value = ? WHERE `key` = ?";
            echo "<strong>SQL Query Template:</strong> " . htmlspecialchars($query) . "<br>";
            echo "<strong>Bound Parameters:</strong> value = [{$path}], key = [{$setting_key}]<br>";
            
            // 2. Prepare statement and check for structural errors
            $stmt = $mysqli->prepare($query);
            if (!$stmt) {
                echo "<span style='color:red;'><strong>Preparation Error:</strong> " . $mysqli->error . "</span><br><br>";
                continue; // Skip to next file in loop
            }
            
            $stmt->bind_param('ss', $path, $setting_key);
            
            // 3. Execute and check for execution errors
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo "<span style='color:green;'><strong>Success!</strong> Row updated. (Affected rows: " . $stmt->affected_rows . ")</span><br><br>";
                } else {
                    echo "<span style='color:orange;'><strong>Warning:</strong> Query ran but 0 rows changed.</span><br>";
                    echo "<em>Check if '{$setting_key}' actually exists in the database 'key' column, or if the path was already identical.</em><br><br>";
                }
            } else {
                echo "<span style='color:red;'><strong>Execution Error:</strong> " . $stmt->error . "</span><br><br>";
            }
            
            $stmt->close();
        } else {
            echo "<span style='color:red;'><strong>Upload Handle Error:</strong> \$res['ok'] failed for this file.</span><br><br>";
        }
    } else {
        echo "<em>No file uploaded or file upload error code occurred for '{$file_input}'.</em><br><br>";
    }
}

    // echo "</pre>";


$_SESSION['flash_success'] = 'Settings updated successfully.';
header('Location: '.$domain.'/admin/settings/landing_page.php');
exit;
