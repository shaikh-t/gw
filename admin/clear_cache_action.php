<?php
// admin/clear_cache_action.php
require_once __DIR__ . '/../lib/middleware.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/cache_helper.php';

// Strict RBAC Verification
require_permission_or_die('cache.clear');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// Support AJAX request header or POST parameter
$csrf_token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!csrf_check($csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

try {
    // Perform full cache purge
    $success = CacheUtility::clear();
    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Application cache cleared successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to clear application cache.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit;
?>