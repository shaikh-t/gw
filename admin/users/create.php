<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.manage');
require_once __DIR__ . '/../../lib/role_helpers.php';
$roles = roles_all();
include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Create user</h4>
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

  <form method="post" action="<?php echo $domain;?>/admin/users/store.php" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input name="name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input name="email" type="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input name="password" type="password" class="form-control" required>
      <div class="form-text">Minimum 8 chars, mixed case, number, symbol</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Roles</label>
      <select name="roles[]" class="form-select" multiple>
        <?php foreach ($roles as $r): ?>
          <option value="<?php echo intval($r['id']); ?>"><?php echo htmlspecialchars($r['label'], ENT_QUOTES); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Avatar</label>
      <input name="avatar" type="file" accept="image/*" class="form-control">
    </div>
    <button class="btn btn-primary">Create</button>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
