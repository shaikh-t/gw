<?php
// scripts/seed_admin.php
// Edit DB credentials below
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'lefkedev77';
$dbName = 'gpa_gw2';
$dbPort = 3306;

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($mysqli->connect_errno) {
    echo "DB connect error: " . $mysqli->connect_error;
    exit;
}
$mysqli->set_charset('utf8mb4');

// Admin credentials to create
$adminName = 'Tahir';
$adminEmail = 'tahir@example.com';
$adminPasswordPlain = 'lefkedev';

// Check if admin exists
$emailEsc = $mysqli->real_escape_string(trim($adminEmail));
$res = $mysqli->query("SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1");
if ($res && $res->num_rows > 0) {
    echo "Admin user already exists\n";
    exit;
}

// Create roles JSON for the user (store role names or ids as JSON)
$rolesArray = ['admin'];
$rolesJson = $mysqli->real_escape_string(json_encode($rolesArray));

// Hash password
$hash = password_hash($adminPasswordPlain, PASSWORD_DEFAULT);
$hashEsc = $mysqli->real_escape_string($hash);

// Insert user
$sql = "INSERT INTO users (name, email, password, roles, created_at) VALUES ('"
    . $mysqli->real_escape_string($adminName) . "', '"
    . $emailEsc . "', '"
    . $hashEsc . "', '"
    . $rolesJson . "', NOW())";

if ($mysqli->query($sql)) {
    $userId = $mysqli->insert_id;
    echo "Admin user created with id: $userId\n";
    // Optionally create a roles table entry and permission seeds separately
} else {
    echo "Insert failed: " . $mysqli->error . "\n";
}
$mysqli->close();
