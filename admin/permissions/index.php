<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('roles.view'); // or 'permissions.view' if you split
require_once __DIR__ . '/../../lib/role_helpers.php';

$perms = permissions_all();
include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-3">
  <div class="d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Permissions</h4>
    <?php if (can('roles.manage')): ?>
      <a href="<?php echo $domain;?>/admin/permissions/create.php" class="btn btn-primary">Create permission</a>
    <?php endif; ?>
  </div>
  <table class="table table-hover mt-3">
    <thead><tr><th>Name</th><th>Label</th><th>Description</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($perms as $p): ?>
        <tr>
          <td><?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($p['label'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($p['description'] ?? '', ENT_QUOTES); ?></td>
          <td class="text-end">
            <a href="<?php echo $domain; ?>/admin/permissions/edit.php?uuid=<?php echo htmlspecialchars($p['uuid']); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
            <?php if (can('roles.manage')): ?>
              <form method="post" action="<?php echo $domain; ?>/admin/permissions/delete.php" class="d-inline-block" onsubmit="return confirm('Delete permission?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo intval($p['id']); ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
