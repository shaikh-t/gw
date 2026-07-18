<?php
// partials/header.php
require_once __DIR__ . '/../lib/auth.php';
$user = current_user();
$current_file = basename($_SERVER['SCRIPT_NAME']);
// echo "<script>var domain='$domain';</script>";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>GW Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  
  <link href="<?php echo $domain; ?>/public/assets/css/app.css" rel="stylesheet">
</head>
<body class="<?php echo 'with-sidebar'; ?>">
<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
  <div class="container-fluid">
    <?php
      $brand_link = $domain . '/index.php';
      if ($user) {
          require_once __DIR__ . '/../lib/permissions.php';
          if (is_role('admin') || is_role('Super Admin')) {
              $brand_link = $domain . '/admin/dashboard.php';
          } else if (is_role('provider')) {
              $brand_link = $domain . '/vendor/index.php';
          }
      }
    ?>
    <a class="navbar-brand d-flex align-items-center" href="<?php echo $brand_link; ?>">
      <strong class="me-2">GW Admin</strong>
    </a>

<?php
// partials/header.php (insert near top bar / global notices)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// ensure CSRF token exists
if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(16));
}

if (!empty($_SESSION['impersonator_id'])):
    $impersonating_provider = $_SESSION['impersonating_provider_id'] ?? null;
    $imperson_text = 'Impersonating user';
    if ($impersonating_provider) {
        // optionally fetch provider name if you want to show it (lightweight)
        $provider_name = '';
        if (isset($mysqli)) {
            $pid = intval($impersonating_provider);
            $r = $mysqli->query("SELECT name FROM providers WHERE id = $pid LIMIT 1");
            if ($r && ($row = $r->fetch_assoc())) $provider_name = $row['name'];
            if ($r) $r->free();
        }
        if ($provider_name) $imperson_text .= ': ' . htmlspecialchars($provider_name, ENT_QUOTES);
    }
?>
<div class="impersonation-banner bg-warning text-dark p-2 d-flex justify-content-between align-items-center" role="status">
  <div class="impersonation-info">
    <strong><?php echo $imperson_text; ?></strong>
    <small class="text-muted ms-2">You are acting as the provider owner. Actions will be recorded as that user.</small>
  </div>
  <form method="post" action="<?php echo $domain; ?>/admin/providers/stop_impersonate.php" class="mb-0">
    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($_SESSION['_csrf'], ENT_QUOTES); ?>">
    <button type="submit" class="btn btn-sm btn-outline-dark">Stop impersonation</button>
  </form>
</div>
<?php endif; ?>



    <div class="d-flex align-items-center ms-auto">
      <!-- Notification bell icon for admin -->
      <div class="me-3 position-relative d-inline-block">
        <button class="btn btn-sm btn-outline-secondary position-relative cp-bell-btn" type="button" id="adminBellBtn">
          <i class="bi bi-bell"></i>
          <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle cp-badge-dot d-none"></span>
        </button>
      </div>

      <?php if ($user):
        $avatar = $user['avatar'] ?? $domain.'/public/assets/img/avatar-placeholder.png';
      ?>
        <div class="d-flex align-items-center">
          <img src="<?php echo htmlspecialchars($avatar, ENT_QUOTES); ?>" alt="avatar" class="header-avatar me-2" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;" onerror="this.onerror=null;this.src='/public/assets/img/avatar-placeholder.png'">
          <div class="d-none d-md-block text-end me-3">
            <div class="fw-bold small mb-0"><?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?></div>
            <a href="<?php echo $domain; ?>/admin/logout.php" class="small text-muted">Sign out</a>
          </div>
        </div>
      <?php else: 
        if ($current_file!=='login.php') {?>
        <a class="btn btn-sm btn-primary" href="<?php echo $domain; ?>/login.php">Sign in</a>
        <?php } ?>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="container container-card mt-4" style="margin-top:5rem !important;">
