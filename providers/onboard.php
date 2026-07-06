<?php
// providers/onboard.php
require_once __DIR__ . '/../lib/middleware.php';
require_login();
require_once __DIR__ . '/../lib/providers_helpers.php';
require_once __DIR__ . '/../lib/onboarding_helpers.php';

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Provider Onboarding</h4>
  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger"><?php foreach ($_SESSION['flash_errors'] as $e) echo htmlspecialchars($e, ENT_QUOTES) . '<br>'; unset($_SESSION['flash_errors']); ?></div>
  <?php endif; ?>
  <form method="post" action="/providers/onboard_store.php" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">Business / Provider name</label>
      <input name="name" class="form-control" required>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
      <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
    </div>
    <div class="mb-3"><label class="form-label">Address</label><input name="address" class="form-control"></div>
    <div class="row">
      <div class="col-md-4 mb-3"><label class="form-label">City</label><input name="city" class="form-control"></div>
      <div class="col-md-4 mb-3"><label class="form-label">State</label><input name="state" class="form-control"></div>
      <div class="col-md-4 mb-3"><label class="form-label">Country</label><input name="country" class="form-control"></div>
    </div>
    <div class="mb-3"><label class="form-label">Short description</label><textarea name="description" class="form-control" rows="3"></textarea></div>

    <h6>Verification documents</h6>
    <div class="mb-3">
      <label class="form-label">Upload documents (ID, business license, proof of address)</label>
      <input name="verification_docs[]" type="file" multiple accept=".pdf,image/*" class="form-control">
    </div>

    <button class="btn btn-primary">Start onboarding</button>
  </form>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
