<?php
// vendor/commission.php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
require_login();

$user = current_user();
$providers = providers_for_user($user['uuid']);
if (empty($providers)) {
    die("No provider account found for this user.");
}
$provider = provider_find($providers[0]['uuid']);
$metrics = provider_dashboard_metrics($provider['uuid']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Commission Contract — GlobalWays Vendor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/globalways.css" rel="stylesheet">
</head>
<body class="bg-warm">
  <div class="dashboard-wrapper d-flex">
    <!-- Sidebar -->
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
          <div class="min-w-0"><div class="text-white small fw-semibold text-truncate"><?= htmlspecialchars($provider['name']) ?></div><div class="font-mono text-uppercase" style="font-size:9px;color:#70A5F7"><?= htmlspecialchars(ucfirst($provider['verification_status'] ?? 'Partner')) ?></div></div>
        </div>
      </div>
      <nav class="nav flex-column p-3 gap-1 flex-grow-1">
        <div class="font-mono text-uppercase px-2 mb-2" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.25)">Menu</div>
        <a class="nav-link" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link" href="services.php"><i class="bi bi-box-seam"></i> Services</a>
        <a class="nav-link" href="quotations.php"><i class="bi bi-file-earmark-richtext"></i> Quotations</a>
        <a class="nav-link" href="quote-requests.php"><i class="bi bi-chat-quote"></i> Quote Requests</a>
        <a class="nav-link active" href="commission.php"><i class="bi bi-percent"></i> My Commission</a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
        <a class="nav-link" href="team.php"><i class="bi bi-person-badge"></i> My Team</a>
      </nav>
      <div class="p-3 border-top border-secondary border-opacity-25"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></div>
    </aside>

    <!-- Backdrop -->
    <div class="sidebar-backdrop"></div>

    <!-- Main Workspace -->
    <div class="dashboard-main flex-grow-1">
      <header class="dashboard-topbar d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-light d-lg-none" data-sidebar-toggle><i class="bi bi-list"></i></button>
          <span class="fw-semibold">Contract Parameters</span>
        </div>
      </header>

      <main class="p-4 p-lg-5">
        <div class="mb-4">
          <h1 class="font-serif h2 mb-1">My Contract Commission</h1>
          <p class="text-muted mb-0">Review your customized contract deduction parameters with the platform administrator.</p>
        </div>

        <div class="row">
          <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-4">
              <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary" style="width: 48px; height: 48px;">
                  <i class="bi bi-shield-check fs-4"></i>
                </div>
                <div>
                  <h4 class="h5 mb-0 fw-bold">Active Contract Parameters</h4>
                  <span class="small text-muted">Read-Only Secure Connection</span>
                </div>
              </div>

              <div class="p-3 rounded-3 border bg-light mb-4">
                <div class="row align-items-center">
                  <div class="col-sm-6">
                    <strong class="text-muted d-block small text-uppercase">Deduction Type</strong>
                    <span class="fw-bold text-dark fs-5 text-uppercase">
                      <?= htmlspecialchars($provider['deduction_type'] ?? 'percentage') ?>
                    </span>
                  </div>
                  <div class="col-sm-6">
                    <strong class="text-muted d-block small text-uppercase">Deduction Rate/Value</strong>
                    <span class="fw-bold text-dark fs-5">
                      <?php
                        $dtype = $provider['deduction_type'] ?? 'percentage';
                        $dval = $provider['deduction_value'] ?? 10.00;
                        if ($dtype === 'percentage') {
                            echo htmlspecialchars($dval) . '%';
                        } else {
                            echo 'AED ' . htmlspecialchars(number_format($dval, 2));
                        }
                      ?>
                    </span>
                  </div>
                </div>
              </div>

              <div class="alert alert-info">
                <i class="bi bi-info-circle-fill me-1"></i>
                <strong>Notice:</strong> Contract deduction rates are established upon registration and are strictly read-only for security audits. If you need to renegotiate your commission structure, please submit an official inquiry to the Super Admin.
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
