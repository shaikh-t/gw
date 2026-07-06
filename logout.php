<?php
// logout.php
require_once __DIR__ . '/lib/auth.php';
logout_user();
header('Location: ' . $domain . '/login.php');
exit;
