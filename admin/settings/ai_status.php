<?php
// admin/settings/ai_status.php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/permissions.php';
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/csrf.php';

// Strict Super Admin Access Lock
if (!is_role('Super Admin')) {
    http_response_code(403);
    die("Access denied. Super Admin role required.");
}

// Fetch current status
$current_status = 'enabled';
$stmt = $mysqli->prepare("SELECT `value` FROM `site_settings` WHERE `key` = 'ai_bot_global_status' LIMIT 1");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $current_status = $row['value'];
    }
    $stmt->close();
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $new_status = isset($_POST['ai_status']) && $_POST['ai_status'] === 'disabled' ? 'disabled' : 'enabled';

    $stmt_up = $mysqli->prepare("UPDATE `site_settings` SET `value` = ? WHERE `key` = 'ai_bot_global_status'");
    if ($stmt_up) {
        $stmt_up->bind_param('s', $new_status);
        if ($stmt_up->execute()) {
            $current_status = $new_status;
            $success_message = "Global AI Bot Status updated to: " . strtoupper($new_status);
        } else {
            $error_message = "Failed to update global bot status: " . $mysqli->error;
        }
        $stmt_up->close();
    }
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="container mt-4">
  <div class="row justify-content-center">
    <div class="col-md-8">

      <div class="mb-4">
        <a href="../dashboard.php" class="text-decoration-none text-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
      </div>

      <?php if ($success_message !== ''): ?>
        <div class="alert alert-success shadow-sm"><?= htmlspecialchars($success_message) ?></div>
      <?php endif; ?>

      <?php if ($error_message !== ''): ?>
        <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error_message) ?></div>
      <?php endif; ?>

      <div class="card border-0 shadow-sm p-4 bg-white rounded-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary" style="width: 48px; height: 48px;">
            <i class="bi bi-robot fs-4"></i>
          </div>
          <div>
            <h1 class="h4 mb-0 font-serif fw-bold">Super Admin Global AI Kill-Switch</h1>
            <p class="text-muted small mb-0">Completely enable or disable the Chat and Voice Bot widget application-wide.</p>
          </div>
        </div>

        <hr class="my-4">

        <form action="ai_status.php" method="post">
          <?= csrf_field(); ?>

          <div class="p-3 mb-4 rounded-3 border <?=$current_status === 'enabled' ? 'border-success bg-success bg-opacity-10 text-success' : 'border-danger bg-danger bg-opacity-10 text-danger'?> d-flex align-items-center justify-content-between">
            <div>
              <strong class="d-block mb-1">Current Operational State</strong>
              <span class="badge <?=$current_status === 'enabled' ? 'bg-success' : 'bg-danger'?> px-3 py-2 rounded-pill font-mono text-uppercase">
                <i class="bi <?=$current_status === 'enabled' ? 'bi-shield-check' : 'bi-shield-x'?> me-1"></i> AI Bot is <?= $current_status ?>
              </span>
            </div>
            <div class="fs-1 text-opacity-25">
              <i class="bi <?=$current_status === 'enabled' ? 'bi-toggle2-on text-success' : 'bi-toggle2-off text-danger'?>"></i>
            </div>
          </div>

          <p class="small text-secondary mb-4">
            Setting the status to <strong>disabled</strong> will prevent the bot templates, styles, script logic, and audio speech synthesis engines from loading on the client's browser, improving performance and guaranteeing a clean zero-waste load path globally.
          </p>

          <div class="d-flex flex-column gap-3 mb-4">
            <label class="border p-3 rounded-3 d-flex align-items-center gap-3 cursor-pointer">
              <input type="radio" name="ai_status" value="enabled" <?= $current_status === 'enabled' ? 'checked' : '' ?> class="form-check-input">
              <div>
                <strong class="text-dark d-block">Enable Globally</strong>
                <small class="text-muted">Chat and Voice Bot will render interactively across the portal.</small>
              </div>
            </label>

            <label class="border p-3 rounded-3 d-flex align-items-center gap-3 cursor-pointer">
              <input type="radio" name="ai_status" value="disabled" <?= $current_status === 'disabled' ? 'checked' : '' ?> class="form-check-input">
              <div>
                <strong class="text-danger d-block">Disable Globally (Kill-Switch Active)</strong>
                <small class="text-muted">Completely disable the AI bot and stop all backend controller executions.</small>
              </div>
            </label>
          </div>

          <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow-sm">
            <i class="bi bi-shield-lock-fill me-1"></i> Save Status Configuration
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
