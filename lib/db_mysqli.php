<?php
// lib/db_mysqli.php
// Edit these constants to match your environment
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'lefkedev77');
define('DB_NAME', 'gpa_gw2');
define('DB_PORT', 3306);

try {
    $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($mysqli->connect_errno) {
        throw new Exception($mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');
} catch (Throwable $e) {
    // If the database is not running or connection failed, fallback to a Mock connection to allow testing & rendering
    require_once __DIR__ . '/mock_mysqli.php';
    $mysqli = new MockMySQLi();
}
