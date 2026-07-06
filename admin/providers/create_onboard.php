<?php
// admin/providers/create_onboard.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/role_helpers.php';
require_once __DIR__ . '/../../lib/users_helpers.php';

// optional: list users to assign as owner
$users = [];
if (can('users.view')) {
    $sql = "SELECT id, name, email FROM users ORDER BY name LIMIT 200";
    if ($res = $mysqli->query($sql)) { while ($r = $res->fetch_assoc()) $users[] = $r; $res->free(); }
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Admin: Create provider and start onboarding</h4>

  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger"><?php foreach ($_SESSION['flash_errors'] as $e) echo htmlspecialchars($e, ENT_QUOTES) . '<br>'; unset($_SESSION['flash_errors']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>

  <form method="post" action="/admin/providers/onboard_store.php" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Provider / Business name</label>
        <input name="name" class="form-control" required>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Assign owner (optional)</label>
        <select name="owner_user_id" class="form-select">
          <option value="">-- none --</option>
          <?php foreach ($users as $u): ?>
            <option value="<?php echo intval($u['id']); ?>"><?php echo htmlspecialchars($u['name'] . ' <' . $u['email'] . '>', ENT_QUOTES); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4 mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
      <div class="col-md-4 mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
      <div class="col-md-4 mb-3"><label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="draft">Draft</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>

    <div class="mb-3"><label class="form-label">Address</label><input name="address" class="form-control"></div>
    <div class="row">
      <div class="col-md-4 mb-3"><label class="form-label">City</label><input name="city" class="form-control"></div>
      <div class="col-md-4 mb-3"><label class="form-label">State</label><input name="state" class="form-control"></div>
      <div class="col-md-4 mb-3"><label class="form-label">Country</label><input name="country" class="form-control"></div>
    </div>

    <div class="mb-3"><label class="form-label">Short description</label><textarea name="description" class="form-control" rows="3"></textarea></div>

    <h6>Verification documents (optional)</h6>
    <div class="mb-3">
      <label class="form-label">Upload documents (ID, license, proof of address)</label>
      <input name="verification_docs[]" type="file" multiple accept=".pdf,image/*" class="form-control">
    </div>

    <button class="btn btn-primary">Create provider & start onboarding</button>
    <a href="/admin/providers/index.php" class="btn btn-link">Back</a>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
