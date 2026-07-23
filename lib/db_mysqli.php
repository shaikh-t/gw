<?php
// lib/db_mysqli.php
// Edit these constants to match your environment
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'lefkedev77');
define('DB_NAME', 'gpa_gw2');
define('DB_PORT', 3306);

// Refined Multi-Tier Fallback Sequence
$is_test_env = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 8000) || (isset($_COOKIE['force_mock_db']) && $_COOKIE['force_mock_db'] === 'true');
if ($is_test_env) {
    require_once __DIR__ . '/mock_mysqli.php';
    $mysqli = new MockMySQLi();
} else {
try {
    // 1. Primary Connection: Attempt persistent connection using 'p:' prefix
    $mysqli = @new mysqli('p:' . DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($mysqli->connect_errno) {
        throw new Exception($mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');
} catch (Throwable $e1) {
    try {
        // 2. First Fallback: Attempt standard, non-persistent connection
        $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($mysqli->connect_errno) {
            throw new Exception($mysqli->connect_error);
        }
        $mysqli->set_charset('utf8mb4');
    } catch (Throwable $e2) {
        // 3. Second Fallback: Gracefully route execution to mock database layer
        require_once __DIR__ . '/mock_mysqli.php';
        $mysqli = new MockMySQLi();
    }
}
}
if (isset($_SESSION['nonce']) && $_SESSION['nonce']!=='')
$cspNonce=$_SESSION['nonce'];