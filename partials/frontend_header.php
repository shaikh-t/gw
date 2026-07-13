<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/permissions.php';

$current_user = current_user();

// Fetch Header Menu
$header_menu_res = $mysqli->query("SELECT label, url FROM menu_items WHERE menu_id = (SELECT id FROM menus WHERE name = 'header_main') ORDER BY sort_order ASC");
$header_menu = [];
while ($m = $header_menu_res->fetch_assoc()) $header_menu[] = $m;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GlobalWays® — UAE Marketplace for Documentation & Advisory</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/globalways.css" rel="stylesheet">
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg gw-navbar fixed-top" id="gwNav">
  <div class="container-xl">
    <a class="navbar-brand py-0 d-flex align-items-center" href="index.php">
      <img src="assets/logo.png" alt="globalways" class="gw-logo gw-logo-default">
      <img src="assets/logo-white.png" alt="globalways" class="gw-logo gw-logo-on-dark">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-1">
        <?php foreach ($header_menu as $item): ?>
            <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="d-flex align-items-center gap-2 navbar-actions">
        <?php if ($current_user): ?>
            <?php if (is_role('admin') || is_role('Super Admin')): ?>
                <a href="admin/dashboard.php" class="btn btn-signin rounded-pill px-4">Admin Panel</a>
            <?php elseif (is_role('provider')): ?>
                <a href="vendor/index.php" class="btn btn-signin rounded-pill px-4">Vendor Portal</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-gw-blue px-4">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-signin rounded-pill px-4">Sign In</a>
            <a href="login.php" class="btn btn-gw-blue px-4">Start Case</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
