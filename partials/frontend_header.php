<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/permissions.php';
$current_user = current_user();

// Persistent dynamic bot page-context tracking loop
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$page_name_tracked = basename($_SERVER['PHP_SELF']);
$bot_context = [
    'page_name' => $page_name_tracked,
    'timestamp' => time()
];

if ($page_name_tracked === 'vendor-profile.php' && isset($provider)) {
    $bot_context['vendor_uuid'] = $provider['uuid'] ?? '';
    $bot_context['vendor_name'] = $provider['name'] ?? '';
} elseif ($page_name_tracked === 'service-detail.php' && isset($service)) {
    $bot_context['service_slug'] = $service['slug'] ?? '';
    $bot_context['service_title'] = $service['title'] ?? '';
    $bot_context['category_name'] = $service['category_name'] ?? '';
}

$_SESSION['bot_page_context'] = $bot_context;

// Fetch dynamic header menu items
$header_items = [];
$h_res = $mysqli->query("SELECT mi.* FROM menu_items mi JOIN menus m ON m.id = mi.menu_id WHERE m.location = 'header' ORDER BY mi.sort_order ASC, mi.id ASC");
if ($h_res) {
    while ($row = $h_res->fetch_assoc()) {
        $header_items[] = $row;
    }
}
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
  <!-- Header / Navbar -->
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
          <?php foreach ($header_items as $item): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>">
                <?= htmlspecialchars($item['title']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="d-flex align-items-center gap-2 navbar-actions">
          <?php if ($current_user): ?>
              <?php if (is_role('admin') || is_role('Super Admin')): ?>
                  <a href="admin/dashboard.php" class="btn btn-signin rounded-pill px-4">Admin Panel</a>
              <?php elseif (is_role('provider')): ?>
                  <a href="vendor/index.php" class="btn btn-signin rounded-pill px-4">Vendor Portal</a>
              <?php elseif (is_role('customer')): ?>
                  <a href="customer/index.php" class="btn btn-signin rounded-pill px-4">Customer Portal</a>
              <?php endif; ?>
              <a href="logout.php" class="btn btn-gw-blue px-4">Logout</a>
          <?php else: ?>
              <a href="login.php" class="btn btn-signin rounded-pill px-4">Sign In</a>
              <a href="register.php" class="btn btn-gw-blue px-4">Get Started Free</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
  <div id="main-content-layout">
    <div class="container-xl">
      <?php
        require_once __DIR__ . '/../lib/monetization_helper.php';
        echo render_layout_ad_placement('site_header_leaderboard');
      ?>
    </div>
