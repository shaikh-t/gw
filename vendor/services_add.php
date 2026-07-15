<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/providers_helpers.php';
require_login();

$user = current_user();
$providers = providers_for_user($user['uuid']);
if (empty($providers)) { die("No provider account found."); }
$provider = provider_find($providers[0]['uuid']);
$pid = (int)$provider['id'];

global $mysqli;

// 1. Fetch Master Services
$master_services = [];
$res_m = $mysqli->query("SELECT id, uuid, title, short_description FROM services WHERE provider_id IS NULL AND master_service_id IS NULL AND status = 'published' ORDER BY title");
if ($res_m) {
    while ($row = $res_m->fetch_assoc()) $master_services[] = $row;
    $res_m->free();
}

// 2. Fetch existing provider service master IDs to avoid duplicates
$existing_master_ids = [];
$res_e = $mysqli->query("SELECT master_service_id FROM services WHERE provider_id = $pid AND master_service_id IS NOT NULL");
if ($res_e) {
    while ($row = $res_e->fetch_assoc()) {
        $existing_master_ids[] = (int)$row['master_service_id'];
    }
    $res_e->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Services — GlobalWays Vendor</title>
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
          <h1 class="font-serif h2 mb-1">Add Offered Services</h1>
          <p class="text-muted mb-0">Select master services created by GlobalWays and specify your custom pricing and delivery timelines.</p>
        </div>

        <div class="card border-0 shadow-sm p-4">
          <form method="post" action="services_store.php">
            <?php require_once __DIR__ . '/../lib/csrf.php'; echo csrf_field(); ?>
            <div id="services-container">
              <!-- Services row entries will be inserted here dynamically -->
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <button type="button" class="btn btn-outline-primary" id="btn-add-row">
                <i class="bi bi-plus-lg me-1"></i> Add Another Service Row
              </button>
              <div>
                <a href="services.php" class="btn btn-light me-2">Cancel</a>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Save Services</button>
              </div>
            </div>
          </form>
        </div>
      </main>
    </div>
  </div>

  <!-- Row Template -->
  <template id="service-row-template">
    <div class="service-row card border bg-light p-3 mb-3 position-relative">
      <button type="button" class="btn-close position-absolute top-0 end-0 m-3 btn-remove-row" aria-label="Close"></button>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label small fw-semibold text-muted">Select Service</label>
          <select name="services[INDEX][master_service_id]" class="form-select select-master-service" required>
            <option value="">-- Choose Service --</option>
            <?php foreach($master_services as $ms): ?>
              <?php if (in_array((int)$ms['id'], $existing_master_ids)) continue; // skip already assigned ?>
              <option value="<?= $ms['id'] ?>"><?= htmlspecialchars($ms['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold text-muted">Price</label>
          <input type="number" step="0.01" min="0" name="services[INDEX][price]" class="form-control" required placeholder="0.00">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold text-muted">Currency</label>
          <select name="services[INDEX][currency]" class="form-select">
            <option value="AED" selected>AED</option>
            <option value="USD">USD</option>
            <option value="SAR">SAR</option>
            <option value="EUR">EUR</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold text-muted">Duration (text)</label>
          <input type="text" name="services[INDEX][duration_text]" class="form-control" required placeholder="e.g. 5–7 days" value="5–7 days">
        </div>
      </div>
    </div>
  </template>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('services-container');
        const btnAdd = document.getElementById('btn-add-row');
        const template = document.getElementById('service-row-template').innerHTML;
        let rowIndex = 0;

        function addRow() {
            let html = template.replace(/INDEX/g, rowIndex);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            const newRow = wrapper.firstElementChild;
            container.appendChild(newRow);

            // remove button handler
            newRow.querySelector('.btn-remove-row').addEventListener('click', function() {
                // Ensure at least one row remains
                if (container.querySelectorAll('.service-row').length > 1) {
                    newRow.remove();
                } else {
                    alert('You must add at least one service row.');
                }
            });

            rowIndex++;
        }

        // Add first row on load
        addRow();

        // Add row handler
        btnAdd.addEventListener('click', addRow);
    });
  </script>
</body>
</html>
