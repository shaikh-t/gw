<?php
// logout.php
require_once __DIR__ . '/../lib/auth.php';
session_start();
logout_user();

// $_SESSION = [];
// session_destroy();
header('Location: '.$domain.'/admin/login.php');
exit;
