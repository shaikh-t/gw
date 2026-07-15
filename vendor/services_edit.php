<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
require_once __DIR__ . '/../lib/services_helpers.php';
require_login();

$user = current_user();
$providers = providers_for_user($user['uuid']);
if (empty($providers)) { die("No provider account found."); }
$provider = provider_find($providers[0]['uuid']);
$pid = (int)$provider['id'];

$uuid = $_GET['uuid'] ?? '';
$service = service_find($uuid);

if (!$service || $service['provider_id'] != $pid) {
    die("Service not found or unauthorized access.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Service — GlobalWays Vendor</title>
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
          <div class="min-w-0"><div class="text-white small fw-semibold text-truncate"><?= htmlspecialchars($provider['name']) ?></div><div class="font-mono text-uppercase" style="font-size:9px;color:#70A5F7"><?= htmlspecialchars(ucfirst($provider['verification_status'] ?? 'Partner')) ?></div></div>
        </div>
      </div>
      <nav class="nav flex-column p-3 gap-1 flex-grow-1">
        <div class="font-mono text-uppercase px-2 mb-2" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.25)">Menu</div>
        <a class="nav-link" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link active" href="services.php"><i class="bi bi-box-seam"></i> Services</a>
        <a class="nav-link" href="quotations.php"><i class="bi bi-file-earmark-richtext"></i> Quotations</a>
        <a class="nav-link" href="cases.php"><i class="bi bi-briefcase"></i> Cases <span class="badge rounded-pill">0</span></a>
        <a class="nav-link" href="crm.php"><i class="bi bi-people"></i> CRM</a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
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
        <div class="mb-4">
          <a href="services.php" class="text-decoration-none btn btn-sm btn-link p-0 mb-2"><i class="bi bi-arrow-left"></i> Back to Services</a>
          <h1 class="font-serif h2 mb-1">Edit Offered Service</h1>
          <p class="text-muted mb-0">Update your custom pricing and delivery timelines for this service. Admin-controlled details are read-only.</p>
        </div>

        <div class="row g-4">
          <!-- Read-only Service Details -->
          <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 h-100 bg-white">
              <div class="mb-3">
                <span class="badge bg-primary-subtle text-primary mb-2">Admin Controlled</span>
                <h4 class="font-serif"><?= htmlspecialchars($service['title']) ?></h4>
                <p class="text-muted small"><?= htmlspecialchars($service['short_description'] ?? '') ?></p>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold text-muted">Category</label>
                <div class="p-2 bg-light rounded border"><?= htmlspecialchars($service['category_name'] ?? 'None') ?></div>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold text-muted">Service Description</label>
                <div class="p-3 bg-light rounded border small text-muted" style="max-height: 250px; overflow-y: auto;">
                  <?= nl2br(htmlspecialchars($service['description'] ?? '')) ?>
                </div>
              </div>
              <?php if (!empty($service['images'])): ?>
                <div>
                  <label class="form-label small fw-semibold text-muted">Service Images</label>
                  <div class="d-flex gap-2 flex-wrap">
                    <?php foreach($service['images'] as $img): ?>
                      <img src="..<?= htmlspecialchars($img) ?>" class="rounded border" style="width: 72px; height: 72px; object-fit: cover;">
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Provider Editable Form -->
          <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 h-100 bg-white">
              <form method="post" action="services_update.php">
                <?php require_once __DIR__ . '/../lib/csrf.php'; echo csrf_field(); ?>
                <input type="hidden" name="uuid" value="<?= htmlspecialchars($service['uuid']) ?>">

                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-muted">Price (Starting From)</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light"><i class="bi bi-cash"></i></span>
                      <input type="number" step="0.01" min="0" name="price" class="form-control" required value="<?= htmlspecialchars($service['price']) ?>" placeholder="0.00">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-muted">Currency</label>
                    <select name="currency" class="form-select" required>
                      <option value="AED" <?= $service['currency'] === 'AED' ? 'selected' : '' ?>>AED</option>
                      <option value="USD" <?= $service['currency'] === 'USD' ? 'selected' : '' ?>>USD</option>
                      <option value="SAR" <?= $service['currency'] === 'SAR' ? 'selected' : '' ?>>SAR</option>
                      <option value="EUR" <?= $service['currency'] === 'EUR' ? 'selected' : '' ?>>EUR</option>
                    </select>
                  </div>
                </div>

                <div class="mb-4">
                  <label class="form-label small fw-semibold text-muted">Duration (Text description)</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-clock"></i></span>
                    <input type="text" name="duration_text" class="form-control" required value="<?= htmlspecialchars($service['duration_text'] ?? '5–7 days') ?>" placeholder="e.g. 5–7 days">
                  </div>
                  <div class="form-text small text-muted">Describe the expected timeline to complete this application.</div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                  <a href="services.php" class="btn btn-light rounded-pill px-3">Cancel</a>
                  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
</body>
</html>
