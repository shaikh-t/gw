<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('roles.manage');
require_once __DIR__ . '/../../lib/role_helpers.php';

$id_val = $_GET['uuid'] ?? $_GET['id'] ?? '';
$role = role_find($id_val);
if (!$role) { http_response_code(404); echo 'Not found'; exit; }
$id = (int)$role['id'];

$allPerms = permissions_all();
$assigned = role_permission_ids($id);

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Edit role</h4>
  <form method="post" action="<?php echo $domain;?>/admin/roles/update.php" class="mb-4">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($role['uuid'] ?? $role['id']); ?>">
    <div class="mb-3">
      <label class="form-label">System name</label>
      <input name="name" class="form-control" value="<?php echo htmlspecialchars($role['name'], ENT_QUOTES); ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Label</label>
      <input name="label" class="form-control" value="<?php echo htmlspecialchars($role['label'], ENT_QUOTES); ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($role['description'] ?? '', ENT_QUOTES); ?></textarea>
    </div>
    <button class="btn btn-primary">Save</button>
  </form>

  <h5>Permissions</h5>
  <form method="post" action="<?php echo $domain;?>/admin/roles/sync_permissions.php">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($role['uuid'] ?? $role['id']); ?>">
    <div class="mb-3">
      <select name="permissions[]" class="form-select" multiple size="12">
        <?php foreach ($allPerms as $p): ?>
          <option value="<?php echo intval($p['id']); ?>" <?php echo in_array($p['id'], $assigned, true) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?> — <?php echo htmlspecialchars($p['label'], ENT_QUOTES); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">Hold Ctrl/Cmd to select multiple permissions.</div>
    </div>
    <button class="btn btn-success">Sync permissions</button>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
