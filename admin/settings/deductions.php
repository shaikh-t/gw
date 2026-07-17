<?php
// admin/settings/deductions.php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/permissions.php';
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/csrf.php';

// Strict Super Admin Access Lock for updates (read-only for others is allowed as long as admin/staff)
if (!is_role('Super Admin') && !is_role('admin') && !is_role('Manager')) {
    http_response_code(403);
    die("Access denied.");
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only Super Admin can write
    if (!is_role('Super Admin')) {
        http_response_code(403);
        die("Access denied. Super Admin role required to modify commissions.");
    }

    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $provider_uuid = trim($_POST['provider_uuid'] ?? '');
    $deduction_type = trim($_POST['deduction_type'] ?? 'percentage');
    $deduction_value = (float)($_POST['deduction_value'] ?? 10.00);

    if ($deduction_type !== 'percentage' && $deduction_type !== 'flat') {
        $error_message = "Invalid deduction type selected.";
    } else {
        $stmt_up = $mysqli->prepare("UPDATE `providers` SET `deduction_type` = ?, `deduction_value` = ? WHERE `uuid` = ?");
        if ($stmt_up) {
            $stmt_up->bind_param('sds', $deduction_type, $deduction_value, $provider_uuid);
            if ($stmt_up->execute()) {
                $success_message = "Deduction contract parameters successfully updated.";
            } else {
                $error_message = "Failed to update deduction parameters: " . $mysqli->error;
            }
            $stmt_up->close();
        }
    }
}

// Fetch all providers
$providers = [];
$res = $mysqli->query("SELECT p.*, u.name as owner_name, u.email as owner_email
                       FROM providers p
                       LEFT JOIN users u ON u.id = p.owner_user_id
                       ORDER BY p.name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $providers[] = $row;
    }
    $res->free();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="container mt-4">
  <div class="mb-4">
    <a href="../dashboard.php" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
  </div>

  <?php if ($success_message !== ''): ?>
    <div class="alert alert-success shadow-sm alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($error_message !== ''): ?>
    <div class="alert alert-danger shadow-sm alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4">
    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary" style="width: 48px; height: 48px;">
        <i class="bi bi-percent fs-4"></i>
      </div>
      <div>
        <h1 class="h4 mb-0 font-serif fw-bold">Advanced Dimensional Commission Engine</h1>
        <p class="text-muted small mb-0">Manage custom provider deduction contracts. Super Admins have authorization to modify flat/percentage commission settings.</p>
      </div>
    </div>

    <hr class="my-3">

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Provider Name</th>
            <th>Owner Details</th>
            <th>Commission Type</th>
            <th>Commission Value</th>
            <?php if (is_role('Super Admin')): ?>
              <th class="text-end">Actions</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($providers)): ?>
            <tr>
              <td colspan="5" class="text-center py-5 text-muted">No providers registered yet.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($providers as $p): ?>
              <tr>
                <td class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></td>
                <td>
                  <span class="small text-dark d-block"><?= htmlspecialchars($p['owner_name'] ?: 'No Owner') ?></span>
                  <span class="small text-muted font-mono"><?= htmlspecialchars($p['owner_email'] ?: '-') ?></span>
                </td>
                <td>
                  <span class="badge <?= $p['deduction_type'] === 'percentage' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning' ?> text-uppercase">
                    <?= htmlspecialchars($p['deduction_type']) ?>
                  </span>
                </td>
                <td class="fw-semibold">
                  <?= $p['deduction_type'] === 'percentage' ? htmlspecialchars($p['deduction_value']) . '%' : 'AED ' . htmlspecialchars(number_format($p['deduction_value'], 2)) ?>
                </td>
                <?php if (is_role('Super Admin')): ?>
                  <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#editModal-<?= htmlspecialchars($p['uuid']) ?>">
                      <i class="bi bi-gear-fill"></i> Adjust Contract
                    </button>

                    <!-- Adjust Contract Modal -->
                    <div class="modal fade" id="editModal-<?= htmlspecialchars($p['uuid']) ?>" tabindex="-1" aria-hidden="true" style="text-align: left;">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <form action="deductions.php" method="post">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="provider_uuid" value="<?= htmlspecialchars($p['uuid']) ?>">
                            <div class="modal-header">
                              <h5 class="modal-title fw-bold">Adjust Contract for <?= htmlspecialchars($p['name']) ?></h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <div class="mb-3">
                                <label class="form-label fw-semibold">Commission Deduction Type</label>
                                <select name="deduction_type" class="form-select">
                                  <option value="percentage" <?= $p['deduction_type'] === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                                  <option value="flat" <?= $p['deduction_type'] === 'flat' ? 'selected' : '' ?>>Flat Fee (AED)</option>
                                </select>
                              </div>
                              <div class="mb-3">
                                <label class="form-label fw-semibold">Contract Deduction Value</label>
                                <input type="number" step="0.01" min="0" name="deduction_value" class="form-control" value="<?= htmlspecialchars($p['deduction_value']) ?>" required>
                                <div class="form-text small text-muted">Enter percentage rate (e.g. 10.00 for 10%) or flat amount in AED.</div>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-primary px-4">Save Parameters</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
