<?php
// admin/providers/assign_service.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/providers_helpers.php';
require_once __DIR__ . '/../../lib/services_helpers.php';

$uuid = $_GET['uuid'] ?? '';
$provider = provider_find($uuid);
if (!$provider) {
    die("Provider not found.");
}
$pid = (int)$provider['id'];

global $mysqli;

// 1. Fetch published Master Services
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

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="card mt-4 p-4">
  <div class="mb-3">
    <a href="<?php echo $domain;?>/admin/providers/dashboard.php?uuid=<?php echo htmlspecialchars($provider['uuid']); ?>" class="btn btn-sm btn-link p-0 mb-2"><i class="bi bi-arrow-left"></i> Back to Provider Dashboard</a>
    <h4>Assign Master Service to <?php echo htmlspecialchars($provider['name'], ENT_QUOTES); ?></h4>
    <p class="text-muted small">Select a master service template and enter customized price and delivery details for this provider.</p>
  </div>

  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger">
      <?php
        if (is_array($_SESSION['flash_errors'])) {
            foreach ($_SESSION['flash_errors'] as $e) echo htmlspecialchars($e, ENT_QUOTES) . '<br>';
        } else {
            echo htmlspecialchars($_SESSION['flash_errors'], ENT_QUOTES);
        }
        unset($_SESSION['flash_errors']);
      ?>
    </div>
  <?php endif; ?>

  <form method="post" action="<?php echo $domain;?>/admin/providers/assign_service_store.php">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="provider_uuid" value="<?php echo htmlspecialchars($provider['uuid'], ENT_QUOTES); ?>">

    <div class="mb-3">
      <label class="form-label">Master Service Template</label>
      <select name="master_service_id" class="form-select" required>
        <option value="">-- Choose Service --</option>
        <?php foreach ($master_services as $ms): ?>
          <?php if (in_array((int)$ms['id'], $existing_master_ids)) continue; // skip already assigned ?>
          <option value="<?php echo intval($ms['id']); ?>"><?php echo htmlspecialchars($ms['title'], ENT_QUOTES); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Price</label>
        <input type="number" step="0.01" min="0" name="price" class="form-control" required placeholder="0.00">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Currency</label>
        <select name="currency" class="form-select">
          <option value="AED" selected>AED</option>
          <option value="USD">USD</option>
          <option value="SAR">SAR</option>
          <option value="EUR">EUR</option>
        </select>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Duration Text</label>
      <input type="text" name="duration_text" class="form-control" required value="5–7 days" placeholder="e.g. 5–7 days">
    </div>

    <button class="btn btn-primary px-4 rounded-pill">Assign Service</button>
    <a href="<?php echo $domain;?>/admin/providers/dashboard.php?uuid=<?php echo htmlspecialchars($provider['uuid']); ?>" class="btn btn-link">Cancel</a>
  </form>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
