<?php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('roles.view');
require_once __DIR__ . '/../../lib/role_helpers.php';

$roles = roles_all();
include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-3">
  <div class="d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Roles</h4>
    <?php if (can('roles.manage')): ?>
      <a href="<?php echo $domain; ?>/admin/roles/create.php" class="btn btn-primary">Create role</a>
    <?php endif; ?>
  </div>

  <table class="table table-hover mt-3">
    <thead><tr><th>Name</th><th>Label</th><th>Description</th><th>Permissions</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($roles as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r['name'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($r['label'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($r['description'] ?? '', ENT_QUOTES); ?></td>
          <td>
            <?php
              $permNames = [];
              $sql = "SELECT p.name FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id WHERE rp.role_id=" . intval($r['id']);
              if ($res = $mysqli->query($sql)) { while ($rw=$res->fetch_assoc()) $permNames[] = htmlspecialchars($rw['name'], ENT_QUOTES); $res->free(); }
              echo implode(', ', $permNames);
            ?>
          </td>
          <td class="text-end">
            <a href="<?php echo $domain; ?>/admin/roles/edit.php?id=<?php echo intval($r['id']); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
            <?php if (can('roles.manage')): ?>
              <form method="post" action="<?php echo $domain; ?>/admin/roles/delete.php" class="d-inline-block" onsubmit="return confirm('Delete role?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo intval($r['id']); ?>">
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
