<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('roles.manage');
include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Create role</h4>
  <form method="post" action="<?php echo $domain;?>/admin/roles/store.php">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">System name</label>
      <input name="name" class="form-control" required>
      <div class="form-text">Use lowercase and underscores (e.g., admin, content_editor).</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Label</label>
      <input name="label" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"></textarea>
    </div>
    <button class="btn btn-primary">Create</button>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
