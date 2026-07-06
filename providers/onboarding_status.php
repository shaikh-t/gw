<?php
// providers/onboarding_status.php
require_once __DIR__ . '/../lib/middleware.php';
require_login();
require_once __DIR__ . '/../lib/db_mysqli.php';

$onb = intval($_GET['onb'] ?? 0);
if ($onb <= 0) { header('Location: /providers/dashboard.php'); exit; }

$res = $mysqli->query("SELECT po.*, p.name AS provider_name, p.verification_status FROM provider_onboarding po LEFT JOIN providers p ON p.id = po.provider_id WHERE po.id = " . intval($onb) . " LIMIT 1");
if (!$res || $res->num_rows === 0) { http_response_code(404); echo 'Not found'; exit; }
$row = $res->fetch_assoc(); $res->free();

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Onboarding status for <?php echo htmlspecialchars($row['provider_name'], ENT_QUOTES); ?></h4>
  <p>Current step: <strong><?php echo htmlspecialchars($row['step'], ENT_QUOTES); ?></strong></p>
  <p>Verification status: <strong><?php echo htmlspecialchars($row['verification_status'] ?? 'unverified', ENT_QUOTES); ?></strong></p>

  <?php if ($row['duplicate_check_status'] === 'possible_duplicate'): ?>
    <div class="alert alert-warning">We detected possible duplicate providers. Please contact support if this is an error.</div>
  <?php endif; ?>

  <h6>Progress</h6>
  <pre><?php echo htmlspecialchars(json_encode(json_decode($row['progress'] ?? '[]', true), JSON_PRETTY_PRINT), ENT_QUOTES); ?></pre>

  <?php if ($row['step'] === 'rejected'): ?>
    <form method="post" action="/providers/onboard_store.php" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['provider_name'], ENT_QUOTES); ?>">
      <div class="mb-3">
        <label class="form-label">Upload additional documents</label>
        <input name="verification_docs[]" type="file" multiple accept=".pdf,image/*" class="form-control">
      </div>
      <button class="btn btn-primary">Resubmit documents</button>
    </form>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
