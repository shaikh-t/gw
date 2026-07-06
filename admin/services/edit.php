<?php
// admin/services/edit.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.manage');
require_once __DIR__ . '/../../lib/services_helpers.php';
require_once __DIR__ . '/../../lib/providers_helpers.php';

$id = intval($_GET['id'] ?? 0);
$service = service_find($id);
if (!$service) { http_response_code(404); echo 'Service not found'; exit; }

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
  <h4>Edit service</h4>
  <form method="post" action="<?php echo $domain;?>/admin/services/update.php" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo intval($service['id']); ?>">

    <div class="mb-3">
      <label class="form-label">Provider</label>
      <select name="provider_id" class="form-select" required>
        <?php foreach ($providers as $p): ?>
          <option value="<?php echo intval($p['id']); ?>" <?php echo ($service['provider_id']==$p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" required value="<?php echo htmlspecialchars($service['title'], ENT_QUOTES); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Short description</label>
      <input name="short_description" class="form-control" value="<?php echo htmlspecialchars($service['short_description'] ?? '', ENT_QUOTES); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="6"><?php echo htmlspecialchars($service['description'] ?? '', ENT_QUOTES); ?></textarea>
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Price</label>
        <input name="price" class="form-control" value="<?php echo htmlspecialchars($service['price'] ?? '', ENT_QUOTES); ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Currency</label>
        <input name="currency" class="form-control" value="<?php echo htmlspecialchars($service['currency'] ?? 'USD', ENT_QUOTES); ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Duration (minutes)</label>
        <input name="duration_minutes" class="form-control" value="<?php echo htmlspecialchars($service['duration_minutes'] ?? '', ENT_QUOTES); ?>">
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Category</label>
      <select name="category_id" class="form-select">
        <option value="">-- none --</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?php echo intval($c['id']); ?>" <?php echo ($service['category_id']==$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name'], ENT_QUOTES); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Tags</label>
      <select name="tag_ids[]" class="form-select" multiple>
        <?php
          $assignedTagIds = array_map(function($t){ return intval($t['id']); }, $service['tags'] ?? []);
          foreach ($tags as $t): ?>
          <option value="<?php echo intval($t['id']); ?>" <?php echo in_array($t['id'], $assignedTagIds) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name'], ENT_QUOTES); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Existing images</label>
      <div class="d-flex gap-2 mb-2">
        <?php foreach ($service['images'] as $img): ?>
          <img src="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>" style="width:96px;height:96px;object-fit:cover;border-radius:6px;">
        <?php endforeach; ?>
      </div>
      <label class="form-label">Add images</label>
      <input name="images[]" type="file" accept="image/*" multiple class="form-control">
    </div>

    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="draft" <?php if($service['status']==='draft') echo 'selected'; ?>>Draft</option>
        <option value="published" <?php if($service['status']==='published') echo 'selected'; ?>>Published</option>
        <option value="archived" <?php if($service['status']==='archived') echo 'selected'; ?>>Archived</option>
      </select>
    </div>

    <button class="btn btn-primary">Save changes</button>
    <a href="<?php echo $domain;?>/admin/services/index.php" class="btn btn-link">Back</a>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
