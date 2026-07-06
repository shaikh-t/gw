<?php
// admin/service_tags/create.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Create tag</h4>
  <form method="post" action="<?php echo $domain; ?>/admin/service_tags/store.php">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input name="name" class="form-control" required>
    </div>
    <button class="btn btn-primary">Create</button>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
