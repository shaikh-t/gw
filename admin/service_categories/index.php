<?php
// admin/service_categories/index.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.view');
require_once __DIR__ . '/../../lib/services_helpers.php';

$categories = service_categories_all();

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Service Categories</h4>
    <?php if (can('services.manage')): ?>
      <a href="<?php echo $domain;?>/admin/service_categories/create.php" class="btn btn-primary">Create category</a>
    <?php endif; ?>
  </div>

  <table class="table table-hover">
    <thead><tr><th>Name</th><th>Slug</th><th>Description</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($categories as $c): ?>
        <tr>
          <td><?php echo htmlspecialchars($c['name'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($c['slug'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($c['description'] ?? '', ENT_QUOTES); ?></td>
          <td class="text-end">
            <a href="<?php echo $domain;?>/admin/service_categories/edit.php?id=<?php echo intval($c['id']); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
            <?php if (can('services.manage')): ?>
              <form method="post" action="<?php echo $domain;?>/admin/service_categories/delete.php" class="d-inline-block" onsubmit="return confirm('Delete category?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo intval($c['id']); ?>">
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
