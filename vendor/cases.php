<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
require_login();

$user = current_user();
$providers = providers_for_user($user['uuid']);
if (empty($providers)) { die("No provider account found."); }
$provider = provider_find($providers[0]['uuid']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cases — GlobalWays Vendor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/globalways.css" rel="stylesheet">
</head>
<body class="bg-warm">
  <div class="dashboard-wrapper d-flex">
    <aside class="dashboard-sidebar d-flex flex-column">
      <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex align-items-center justify-content-between">
        <a href="../index.php" class="text-decoration-none d-flex align-items-center gap-2">
          <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:linear-gradient(135deg,#1165EF,#3F83F4)"><i class="bi bi-globe2 text-white small"></i></div>
          <div><div class="text-white font-serif small">GlobalWays</div><div class="font-mono text-uppercase" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.4)">Vendor Portal</div></div>
        </a>
        <button class="btn btn-link text-white-50 p-0 d-lg-none" data-sidebar-close><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="p-3 border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-2 p-2 rounded-3 mb-2" style="background:rgba(255,255,255,.05)">
          <?php if($provider['logo']): ?>
            <img src="..<?= htmlspecialchars($provider['logo']) ?>" class="avatar-circle border border-primary" style="width:32px;height:32px;object-fit:cover;">
          <?php else: ?>
            <span class="avatar-circle border border-primary" style="background:rgba(17,101,239,.2);color:#70A5F7"><?= strtoupper(substr($provider['name'], 0, 2)) ?></span>
          <?php endif; ?>
          <div class="min-w-0"><div class="text-white small fw-semibold text-truncate"><?= htmlspecialchars($provider['name']) ?></div></div>
        </div>
      </div>
      <nav class="nav flex-column p-3 gap-1 flex-grow-1">
        <div class="font-mono text-uppercase px-2 mb-2" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.25)">Menu</div>
        <a class="nav-link" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link" href="services.php"><i class="bi bi-box-seam"></i> Services</a>
        <a class="nav-link" href="quotations.php"><i class="bi bi-file-earmark-richtext"></i> Quotations</a>
        <a class="nav-link active" href="cases.php"><i class="bi bi-briefcase"></i> Cases <span class="badge rounded-pill">0</span></a>
        <a class="nav-link" href="crm.php"><i class="bi bi-people"></i> CRM</a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
        <a class="nav-link" href="team.php"><i class="bi bi-person-badge"></i> My Team</a>
      </nav>
      <div class="p-3 border-top border-secondary border-opacity-25"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></div>
    </aside>
    <div class="sidebar-backdrop"></div>
    <div class="dashboard-main">
      <header class="dashboard-topbar d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3 flex-grow-1"><button class="btn btn-light d-lg-none" data-sidebar-toggle><i class="bi bi-list"></i></button></div>
        <div class="d-flex align-items-center gap-2"><button class="btn btn-light"><i class="bi bi-bell"></i></button><span class="avatar-circle bg-dark"><?= strtoupper(substr($user['name'], 0, 2)) ?></span></div>
      </header>
      <main class="p-4 p-lg-5">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
          <div><h1 class="font-serif h2 mb-1">Case Management</h1><p class="text-muted mb-0">Track and manage all active customer cases</p></div>
        </div>
        <div class="card border-0 shadow-sm p-5 text-center">
            <div class="text-muted mb-3"><i class="bi bi-briefcase fs-1"></i></div>
            <h2 class="h5">No active cases</h2>
            <p class="text-muted">Once you receive orders, they will appear here as cases.</p>
        </div>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
</body>
</html>
