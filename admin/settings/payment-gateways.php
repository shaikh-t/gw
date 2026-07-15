<?php
// admin/settings/payment-gateways.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('settings.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

$success_message = '';
$error_message = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $gateways_post = $_POST['gateways'] ?? [];

    $mysqli->begin_transaction();
    try {
        foreach ($gateways_post as $gw_name => $gw_data) {
            $public_key = trim($gw_data['public_key'] ?? '');
            $secret_key = trim($gw_data['secret_key'] ?? '');
            $sandbox_mode = isset($gw_data['sandbox_mode']) ? 1 : 0;
            $is_enabled = isset($gw_data['is_enabled']) ? 1 : 0;

            $stmt = $mysqli->prepare("UPDATE `payment_gateways` SET `public_key` = ?, `secret_key` = ?, `sandbox_mode` = ?, `is_enabled` = ? WHERE `name` = ?");
            $stmt->bind_param('ssiis', $public_key, $secret_key, $sandbox_mode, $is_enabled, $gw_name);
            $stmt->execute();
            $stmt->close();
        }
        $mysqli->commit();
        $success_message = 'Payment Gateway Settings updated successfully!';
    } catch (Exception $e) {
        $mysqli->rollback();
        $error_message = 'Failed to update settings: ' . $e->getMessage();
    }
}

// Fetch all payment gateways
$res = $mysqli->query("SELECT * FROM `payment_gateways` ORDER BY `id` ASC");
$gateways = [];
while ($row = $res->fetch_assoc()) {
    $gateways[$row['name']] = $row;
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<main class="main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Payment Settings</h2>
    </div>

    <?php if ($success_message !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <?php if ($error_message !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <p class="text-secondary mb-4">Configure credentials and options for each of your payment gateway integrations. Swapping credentials here allows easy transition between mock sandbox/test mode and real-world billing configurations.</p>

    <form action="payment-gateways.php" method="POST">
        <?= csrf_field(); ?>

        <div class="row g-4">
            <?php foreach (['Stripe', 'PayPal', 'Authorize.net'] as $gw_name):
                $gw = $gateways[$gw_name] ?? ['public_key' => '', 'secret_key' => '', 'sandbox_mode' => 1, 'is_enabled' => 0];
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-dark text-white d-flex align-items-center justify-content-between">
                            <span class="fw-bold"><i class="bi bi-credit-card-2-front me-1"></i> <?= htmlspecialchars($gw_name) ?></span>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input bg-primary border-0" type="checkbox" role="switch" name="gateways[<?= htmlspecialchars($gw_name) ?>][is_enabled]" id="enable_<?= htmlspecialchars($gw_name) ?>" <?= $gw['is_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label text-white small" for="enable_<?= htmlspecialchars($gw_name) ?>">Enabled</label>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label for="pub_<?= htmlspecialchars($gw_name) ?>" class="form-label fw-semibold small text-uppercase">Public / Client API Key</label>
                                <input type="text" class="form-control font-mono" id="pub_<?= htmlspecialchars($gw_name) ?>" name="gateways[<?= htmlspecialchars($gw_name) ?>][public_key]" value="<?= htmlspecialchars($gw['public_key']) ?>" placeholder="pk_test_...">
                            </div>

                            <div class="mb-3">
                                <label for="sec_<?= htmlspecialchars($gw_name) ?>" class="form-label fw-semibold small text-uppercase">Secret / Private Key</label>
                                <input type="password" class="form-control font-mono" id="sec_<?= htmlspecialchars($gw_name) ?>" name="gateways[<?= htmlspecialchars($gw_name) ?>][secret_key]" value="<?= htmlspecialchars($gw['secret_key']) ?>" placeholder="sk_test_...">
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="gateways[<?= htmlspecialchars($gw_name) ?>][sandbox_mode]" id="sandbox_<?= htmlspecialchars($gw_name) ?>" <?= $gw['sandbox_mode'] ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="sandbox_<?= htmlspecialchars($gw_name) ?>">Sandbox / Test Mode</label>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary px-5 py-3 rounded-pill fw-semibold shadow-sm">
                <i class="bi bi-save me-1"></i> Save Gateway Configurations
            </button>
        </div>
    </form>
</main>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
