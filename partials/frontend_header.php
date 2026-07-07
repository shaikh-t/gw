<?php
require_once __DIR__ . '/../lib/auth.php';
$current_user = current_user();
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
  <!-- Header -->
  <header class="gw-header sticky-top">
    <nav class="navbar navbar-expand-lg py-3">
      <div class="container-xl">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
          <img src="assets/logo.png" alt="globalways" class="gw-logo">
        </a>
        <button class="navbar-toggler border-0 p-0" type="button" data-bs-toggle="collapse" data-bs-target="#gwNavbar">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="gwNavbar">
          <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-3">
            <li class="nav-item"><a class="nav-link fw-medium" href="index.php">Home</a></li>
            <li class="nav-item"><a class="nav-link fw-medium" href="services.php">Marketplace</a></li>
            <li class="nav-item"><a class="nav-link fw-medium" href="vendors.php">Vendors</a></li>
            <li class="nav-item"><a class="nav-link fw-medium" href="about.php">About</a></li>
          </ul>
          <div class="d-flex align-items-center gap-3">
            <?php if ($current_user): ?>
                <?php if (is_role('admin') || is_role('Super Admin')): ?>
                    <a href="admin/dashboard.php" class="btn btn-outline-gw-blue btn-sm px-4 rounded-pill fw-medium">Admin Panel</a>
                <?php elseif (is_role('provider')): ?>
                    <a href="vendor/index.php" class="btn btn-outline-gw-blue btn-sm px-4 rounded-pill fw-medium">Vendor Portal</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-gw-blue btn-sm px-4 rounded-pill fw-medium">Logout</a>
            <?php else: ?>
                <a href="login.php" class="text-decoration-none fw-medium text-dark small">Login</a>
                <a href="login.php" class="btn btn-gw-blue btn-sm px-4 rounded-pill fw-medium">Start Case</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>
  </header>
