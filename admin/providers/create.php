<?php
// admin/providers/create.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/role_helpers.php';
require_once __DIR__ . '/../../lib/users_helpers.php';

$users = []; // optional: list users to assign as owner
if (can('users.view')) {
    // simple list for owner selection (first 100)
    $sql = "SELECT id, name, email FROM users ORDER BY name LIMIT 100";
    if ($res = $mysqli->query($sql)) { while ($r = $res->fetch_assoc()) $users[] = $r; $res->free(); }
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Create provider</h4>
  <form method="post" action="<?php echo $domain;?>/admin/providers/store.php" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Owner (optional)</label>
        <select name="owner_user_id" class="form-select">
          <option value="">-- none --</option>
          <?php foreach ($users as $u): ?>
            <option value="<?php echo intval($u['id']); ?>"><?php echo htmlspecialchars($u['name'] . ' <' . $u['email'] . '>', ENT_QUOTES); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Email</label>
      <input name="email" type="email" class="form-control">
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Phone</label>
        <input name="phone" class="form-control">
      </div>
      <div class="col-md-8 mb-3">
        <label class="form-label">Address</label>
        <input name="address" class="form-control">
      </div>
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">City</label>
        <input name="city" class="form-control">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">State</label>
        <input name="state" class="form-control">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Country</label>
        <input name="country" class="form-control">
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="4"></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Logo</label>
      <input name="logo" type="file" accept="image/*" class="form-control">
    </div>

    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="draft">Draft</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>

    <button class="btn btn-primary">Create provider</button>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
