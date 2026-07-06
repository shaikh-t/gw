<?php
// admin/services/create.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/services_helpers.php';
require_once __DIR__ . '/../../lib/providers_helpers.php';

$providers = [];
if (can('providers.view')) {
    $sql = "SELECT id, name FROM providers ORDER BY name LIMIT 200";
    if ($res = $mysqli->query($sql)) { while ($r = $res->fetch_assoc()) $providers[] = $r; $res->free(); }
}
$categories = service_categories_all();
$tags = service_tags_all();

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Create service</h4>
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
  <form method="post" action="<?php echo $domain;?>/admin/services/store.php" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">Provider</label>
      <select name="provider_id" class="form-select" required>
        <?php foreach ($providers as $p): ?>
          <option value="<?php echo intval($p['id']); ?>"><?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Short description</label>
      <input name="short_description" class="form-control">
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="6"></textarea>
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Price</label>
        <input name="price" class="form-control">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Currency</label>
        <input name="currency" class="form-control" value="USD">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Duration (minutes)</label>
        <input name="duration_minutes" class="form-control">
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Category</label>
      <select name="category_id" class="form-select">
        <option value="">-- none --</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?php echo intval($c['id']); ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Tags (Ctrl/Cmd to multi-select)</label>
      <select name="tag_ids[]" class="form-select" multiple>
        <?php foreach ($tags as $t): ?>
          <option value="<?php echo intval($t['id']); ?>"><?php echo htmlspecialchars($t['name'], ENT_QUOTES); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Images</label>
      <input name="images[]" type="file" accept="image/*" multiple class="form-control">
    </div>

    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="draft">Draft</option>
        <option value="published">Published</option>
      </select>
    </div>

    <button class="btn btn-primary">Create service</button>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
