<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/permissions.php';

if (!isset($cspNonce)) {
    $cspNonce = base64_encode(random_bytes(16));
}
// Redundant Security Headers
if (!headers_sent()) {
    //header("Content-Security-Policy: default-src 'self'; script-src 'self' https://google.com https://*.jsdelivr.net 'nonce-" . $cspNonce . "'; style-src 'self' 'unsafe-inline' https://*.jsdelivr.net; font-src 'self' https://*.jsdelivr.net https://*.googleapis.com; img-src 'self' data:; connect-src 'self' https://*.jsdelivr.net; frame-src https://google.com;");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://google.com https://*.jsdelivr.net 'nonce-" . $cspNonce . "'; style-src 'self' 'unsafe-inline' https://*.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://*.jsdelivr.net https://fonts.googleapis.com https://*.gstatic.com; img-src 'self' data:; connect-src 'self' https://*.jsdelivr.net; frame-src https://google.com;");
    header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");
}
$_SESSION['nonce']=$cspNonce;
$current_user = current_user();

// Persistent dynamic bot page-context tracking loop
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
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
  <link rel="icon" type="image/png" href="<?php echo $domain; ?>/assets/favicon-96x96.png" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="<?php echo $domain; ?>/assets/favicon.svg" />
  <link rel="shortcut icon" href="<?php echo $domain; ?>/assets/favicon.ico" />
  <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $domain; ?>/assets/apple-touch-icon.png" />
  <link rel="manifest" href="<?php echo $domain; ?>/assets/site.webmanifest" />
  
  <script nonce="<?php echo $cspNonce;?>">
    (function() {
      const savedTheme = localStorage.getItem('theme') || 'auto';
      const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      const activeTheme = savedTheme === 'auto' ? systemTheme : savedTheme;
      document.documentElement.setAttribute('data-bs-theme', activeTheme);
    })();
    function toggleSystemTheme() {
      const htmlEl = document.documentElement;
      const currentTheme = htmlEl.getAttribute('data-bs-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

      htmlEl.setAttribute('data-bs-theme', newTheme);
      localStorage.setItem('theme', newTheme);
      updateThemeToggleIcon(newTheme);
    }

    function updateThemeToggleIcon(theme) {
      const icon = document.getElementById('themeToggleIcon');
      if (!icon) return;
      if (theme === 'dark') {
        icon.className = 'bi bi-sun-fill text-warning';
      } else {
        icon.className = 'bi bi-moon-fill text-primary';
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      const activeTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
      updateThemeToggleIcon(activeTheme);

      const themeBtn = document.getElementById('themeToggleBtn');
      if (themeBtn) {
        themeBtn.addEventListener('click', toggleSystemTheme);
      }
    });
  </script>
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
        <div class="d-flex align-items-center gap-3 navbar-actions">
          <!-- Theme Toggle Button -->
          <button class="btn btn-link nav-link p-2 d-flex align-items-center justify-content-center" id="themeToggleBtn" title="Toggle Light/Dark Theme" style="border: none; background: none;">
            <i class="bi bi-moon-fill text-primary" id="themeToggleIcon" style="font-size: 1.25rem;"></i>
          </button>

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
