<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('roles.manage');
include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Create permission</h4>
  <?php if (!empty($_SESSION['flash_errors'])): ?>
  <div id="flashErrors" class="flash-errors">
    <?php
      $errors = $_SESSION['flash_errors'];
      if (is_array($errors)) {
          foreach ($errors as $e) {
              echo '<div>' . htmlspecialchars($e, ENT_QUOTES) . '</div>';
          }
      } else {
          echo '<div>' . htmlspecialchars($errors, ENT_QUOTES) . '</div>';
      }
      // clear after showing
      unset($_SESSION['flash_errors']);
    ?>
  </div>
<?php endif; ?>
  <form method="post" action="<?php echo $domain;?>/admin/permissions/store.php">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">System name</label>
      <input name="name" class="form-control" required placeholder="module.action e.g. users.manage">
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
