<?php
// admin/service_categories/edit.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/services_helpers.php';

$id = intval($_GET['id'] ?? 0);
$cat = null;
if ($id > 0) {
    $cats = service_categories_all();
    foreach ($cats as $c) if ($c['id'] == $id) { $cat = $c; break; }
}
if (!$cat) { http_response_code(404); echo 'Not found'; exit; }

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Edit category</h4>
  <form method="post" action="<?php echo $domain;?>/admin/service_categories/update.php">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo intval($cat['id']); ?>">
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input name="name" class="form-control" required value="<?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Slug</label>
      <input name="slug" class="form-control" value="<?php echo htmlspecialchars($cat['slug'], ENT_QUOTES); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($cat['description'] ?? '', ENT_QUOTES); ?></textarea>
    </div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
