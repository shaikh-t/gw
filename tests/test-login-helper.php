<?php
// tests/test-login-helper.php
// This is a strictly local/test-only helper to establish authenticated user sessions for Playwright testing.
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db_mysqli.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Strict security: Only allow access from localhost/127.0.0.1 for local/CI automated testing
$allowed_ips = ['127.0.0.1', '::1'];
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_ips, true)) {
    http_response_code(403);
    die("Forbidden: Localhost testing access only.");
}

$role = $_GET['role'] ?? 'admin';

if ($role === 'admin_with_permission') {
    $_SESSION['user'] = [
        'id' => 1,
        'uuid' => 'test-admin-uuid-permitted',
        'name' => 'Authorized Admin',
        'email' => 'auth-admin@example.com'
    ];
    $_SESSION['mock_permissions'] = ['dashboard.view', 'cache.clear', 'manage_system_analytics', 'view_voice_telemetry', 'manage_bot_steps', 'view_bot_interaction_logs'];
    $_SESSION['mock_roles'] = ['Super Admin'];
} elseif ($role === 'admin_no_permission') {
    $_SESSION['user'] = [
        'id' => 2,
        'uuid' => 'test-admin-uuid-unpermitted',
        'name' => 'Unauthorized Admin',
        'email' => 'unauth-admin@example.com'
    ];
    $_SESSION['mock_permissions'] = ['dashboard.view']; // lacks cache.clear
    $_SESSION['mock_roles'] = ['admin'];
} elseif ($role === 'logout') {
    foreach($_SESSION as $k=>$v) {
        unset($_SESSION[$k]);
    }
    session_destroy();
}

echo "Session set for role: " . htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
?>