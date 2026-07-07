<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
require_login();

$user = current_user();
$providers = providers_for_user($user['uuid']);
if (empty($providers)) { die("No provider account found."); }
$provider = provider_find($providers[0]['uuid']);

// Handle update if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateData = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['address'] ?? '',
        'city' => $_POST['city'] ?? '',
        'country' => $_POST['country'] ?? '',
        'description' => $_POST['description'] ?? '',
    ];
    provider_update($provider['uuid'], $updateData);
    $provider = provider_find($provider['uuid']); // refresh
    $success = "Profile updated successfully.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile — GlobalWays Vendor</title>
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
        <a class="nav-link" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link" href="services.php"><i class="bi bi-box-seam"></i> Services</a>
        <a class="nav-link" href="quotations.php"><i class="bi bi-file-earmark-richtext"></i> Quotations</a>
        <a class="nav-link" href="cases.php"><i class="bi bi-briefcase"></i> Cases <span class="badge rounded-pill">0</span></a>
        <a class="nav-link" href="crm.php"><i class="bi bi-people"></i> CRM</a>
        <a class="nav-link active" href="profile.php"><i class="bi bi-person"></i> Profile</a>
      </nav>
      <div class="p-3 border-top border-secondary border-opacity-25"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></div>
    </aside>
    <div class="sidebar-backdrop"></div>
    <div class="dashboard-main">
      <header class="dashboard-topbar d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3"><button class="btn btn-light d-lg-none" data-sidebar-toggle><i class="bi bi-list"></i></button></div>
        <div class="d-flex align-items-center gap-2"><button class="btn btn-light"><i class="bi bi-bell"></i></button><span class="avatar-circle bg-dark"><?= strtoupper(substr($user['name'], 0, 2)) ?></span></div>
      </header>
      <main class="p-4 p-lg-5">
        <div class="mb-4"><h1 class="font-serif h2 mb-1">Vendor Profile</h1><p class="text-muted mb-0">Manage your company profile and settings</p></div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white border-bottom"><h2 class="h6 mb-0">Company Information</h2></div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-6"><label class="form-label small">Company Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($provider['name']) ?>"></div>
                  <div class="col-md-6"><label class="form-label small">Trade License No.</label><input type="text" class="form-control" value="N/A" readonly></div>
                  <div class="col-md-6"><label class="form-label small">Contact Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($provider['email']) ?>"></div>
                  <div class="col-md-6"><label class="form-label small">Phone</label><input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($provider['phone']) ?>"></div>
                  <div class="col-md-6"><label class="form-label small">City</label><input type="text" name="city" class="form-control" value="<?= htmlspecialchars($provider['city']) ?>"></div>
                  <div class="col-md-6"><label class="form-label small">Country</label><input type="text" name="country" class="form-control" value="<?= htmlspecialchars($provider['country']) ?>"></div>
                  <div class="col-12"><label class="form-label small">Address</label><textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($provider['address']) ?></textarea></div>
                  <div class="col-12"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($provider['description']) ?></textarea></div>
                </div>
              </div>
            </div>
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-bottom"><h2 class="h6 mb-0">Banking Details (Read Only)</h2></div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-6"><label class="form-label small">Bank Name</label><input type="text" class="form-control" value="N/A" readonly></div>
                  <div class="col-md-6"><label class="form-label small">IBAN</label><input type="text" class="form-control" value="N/A" readonly></div>
                  <div class="col-md-6"><label class="form-label small">Account Holder</label><input type="text" class="form-control" value="N/A" readonly></div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-body text-center">
                <?php if($provider['logo']): ?>
                    <img src="..<?= htmlspecialchars($provider['logo']) ?>" class="avatar-circle d-inline-flex mb-3 border border-primary" style="width:4rem;height:4rem;object-fit:cover;">
                <?php else: ?>
                    <span class="avatar-circle d-inline-flex mb-3 border border-primary" style="width:4rem;height:4rem;font-size:1rem;background:rgba(17,101,239,.2);color:#70A5F7"><?= strtoupper(substr($provider['name'], 0, 2)) ?></span>
                <?php endif; ?>
                <div class="fw-semibold"><?= htmlspecialchars($provider['name']) ?></div>
                <span class="badge bg-primary-subtle text-primary mb-3"><?= htmlspecialchars(ucfirst($provider['verification_status'])) ?></span>
                <div class="small text-muted mb-3">Member since <?= date('M Y', strtotime($provider['created_at'])) ?></div>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill">Upload Logo</button>
              </div>
            </div>
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white border-bottom"><h3 class="h6 mb-0">Verification Status</h3></div>
              <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between"><span>Profile Completion</span><i class="bi bi-check-circle-fill text-success"></i></li>
                <li class="list-group-item d-flex justify-content-between"><span>Email Verified</span><i class="bi bi-check-circle-fill text-success"></i></li>
                <li class="list-group-item d-flex justify-content-between"><span>Identity Verified</span><i class="bi bi-dash-circle text-warning"></i></li>
              </ul>
            </div>
            <button type="submit" class="btn btn-primary w-100 rounded-pill">Save Changes</button>
          </div>
        </div>
        </form>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
</body>
</html>
