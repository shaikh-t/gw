<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('roles.manage');
require_once __DIR__ . '/../../lib/role_helpers.php';

// $id = intval($_GET['id'] ?? 0);
$uuid = isset($_GET['uuid']) ? trim($_GET['uuid']) : '';

$p = permission_find($uuid);
if (!$p) { http_response_code(404); echo 'Not found'; exit; }
include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Edit permission</h4>
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
  <form method="post" action="<?php echo $domain;?>/admin/permissions/update.php">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
    <div class="mb-3">
      <label class="form-label">System name</label>
      <input name="name" class="form-control" value="<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Label</label>
      <input name="label" class="form-control" value="<?php echo htmlspecialchars($p['label'], ENT_QUOTES); ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($p['description'] ?? '', ENT_QUOTES); ?></textarea>
    </div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
