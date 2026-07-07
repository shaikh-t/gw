<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
require_login();

$user = current_user();
$providers = providers_for_user($user['uuid']);
if (empty($providers)) { die("No provider account found."); }
$provider = provider_find($providers[0]['uuid']);

// Since we don't have a cases table yet, we can't show real case details.
// For now, redirect back to cases list.
header('Location: cases.php');
exit;
?>
