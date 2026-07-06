<?php
// lib/db_mysqli.php
// Edit these constants to match your environment
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'lefkedev77');
define('DB_NAME', 'gpa_gw2');
define('DB_PORT', 3306);

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($mysqli->connect_errno) {
    error_log('MySQL connection error: ' . $mysqli->connect_error);
    die('Database connection failed');
}
$mysqli->set_charset('utf8mb4');
