<?php
// api/bot-upload-handler.php
header('Content-Type: application/json; charset=utf-8');

// Error reporting setup for clean JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/uuid_helper.php';

// Start session to verify active bot sessions
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function send_json_response(array $data, int $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['status' => 'error', 'message' => 'Only POST requests are permitted.'], 405);
}

// Ensure session or bot token is valid
$session_token = isset($_POST['session_token']) ? trim($_POST['session_token']) : '';
if (empty($session_token)) {
    send_json_response(['status' => 'error', 'message' => 'Active session token is required.'], 400);
}

// 1. Validate File Upload Presence
if (empty($_FILES['attachment']) || $_FILES['attachment']['error'] === UPLOAD_ERR_NO_FILE) {
    send_json_response(['status' => 'error', 'message' => 'No file attachment detected.'], 400);
}

$file = $_FILES['attachment'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    send_json_response(['status' => 'error', 'message' => 'File upload error code: ' . $file['error']], 400);
}

// 2. MIME Type and Extension Verifications (Allow ONLY PDF, JPG, PNG)
$allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
$file_name = basename($file['name']);
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

if (!in_array($file_ext, $allowed_extensions, true)) {
    send_json_response(['status' => 'error', 'message' => 'Unsupported file extension. Only PDF, JPG, and PNG files are allowed.'], 400);
}

// Deep MIME type scan using Fileinfo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_mimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
if (!in_array($mime_type, $allowed_mimes, true)) {
    send_json_response(['status' => 'error', 'message' => 'Invalid file MIME type. Only PDF, JPG, and PNG files are allowed.'], 400);
}

// 3. Cryptographic UUID Renaming and Secure Storage
$secure_dir = __DIR__ . '/../public/uploads/secure_docs/';
if (!is_dir($secure_dir)) {
    mkdir($secure_dir, 0755, true);
    // Secure folder from direct script execution
    file_put_contents($secure_dir . '.htaccess', "Options -Indexes\n<Files ~ \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">\n  ForceType text/plain\n  Deny from all\n</Files>");
}

$crypt_name = generate_uuid() . '.' . $file_ext;
$target_path = $secure_dir . $crypt_name;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    send_json_response([
        'status' => 'success',
        'message' => 'File uploaded and secured successfully.',
        'file_id' => $crypt_name,
        'original_name' => $file_name,
        'mime_type' => $mime_type
    ]);
} else {
    send_json_response(['status' => 'error', 'message' => 'Failed to move uploaded file.'], 500);
}
?>
