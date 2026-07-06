<?php
// admin/service_tags/edit.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/services_helpers.php';

$id = intval($_GET['id'] ?? 0);
$tag = null;
if ($id > 0) {
    $tags = service_tags_all();
    foreach ($tags as $t) if ($t['id'] == $id) { $tag = $t; break; }
}
if (!$tag) { http_response_code(404); echo 'Not found'; exit; }

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Edit tag</h4>
  <form method="post" action="<?php echo $domain;?>/admin/service_tags/update.php">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo intval($tag['id']); ?>">
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input name="name" class="form-control" required value="<?php echo htmlspecialchars($tag['name'], ENT_QUOTES); ?>">
    </div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
