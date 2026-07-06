<?php
// providers/services/create.php
require_once __DIR__ . '/../../lib/middleware.php';
require_login();
require_once __DIR__ . '/../../lib/services_helpers.php';
require_once __DIR__ . '/../../lib/providers_helpers.php';

$current = current_user();
// find provider owned by user
$res = $mysqli->query("SELECT id FROM providers WHERE owner_user_id = " . intval($current['id']) . " LIMIT 1");
if (!$res || $res->num_rows === 0) { http_response_code(403); echo 'No provider profile found'; exit; }
$prov = $res->fetch_assoc(); $provider_id = intval($prov['id']);

$categories = service_categories_all();
$tags = service_tags_all();

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Create service</h4>
  <form method="post" action="/providers/services/store.php" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="provider_id" value="<?php echo $provider_id; ?>">
    <!-- reuse fields similar to admin create -->
    <!-- ... same fields as admin create ... -->
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" required>
    </div>
    <!-- rest omitted for brevity; follow admin create structure -->
    <button class="btn btn-primary">Create service</button>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
