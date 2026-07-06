<?php
// admin/reviews/create.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('reviews.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/users_helpers.php';
require_once __DIR__ . '/../../lib/providers_helpers.php';
require_once __DIR__ . '/../../lib/services_helpers.php';

$users = [];
if ($res = $mysqli->query("SELECT id, name, email FROM users ORDER BY name LIMIT 500")) {
    while ($u = $res->fetch_assoc()) $users[] = $u;
    $res->free();
}
$providers = [];
if ($res = $mysqli->query("SELECT id, name FROM providers ORDER BY name LIMIT 500")) {
    while ($p = $res->fetch_assoc()) $providers[] = $p;
    $res->free();
}
$services = [];
if ($res = $mysqli->query("SELECT id, title, provider_id FROM services ORDER BY title LIMIT 500")) {
    while ($s = $res->fetch_assoc()) $services[] = $s;
    $res->free();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Add Manual Review</h4>
  <form method="post" action="<?php echo $domain;?>/admin/reviews/create_store.php">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">Author user</label>
      <select name="user_id" class="form-select" required>
        <option value="">-- choose user --</option>
        <?php foreach ($users as $u): ?>
          <option value="<?php echo intval($u['id']); ?>"><?php echo htmlspecialchars($u['name'] . ' <' . $u['email'] . '>', ENT_QUOTES); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Target</label>
      <div class="row">
        <div class="col">
          <select name="provider_id" class="form-select">
            <option value="">Provider (optional)</option>
            <?php foreach ($providers as $p): ?>
              <option value="<?php echo intval($p['id']); ?>"><?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col">
          <select name="service_id" class="form-select">
            <option value="">Service (optional)</option>
            <?php foreach ($services as $s): ?>
              <option value="<?php echo intval($s['id']); ?>"><?php echo htmlspecialchars($s['title'], ENT_QUOTES); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-text">Choose either a service or a provider. If both are selected, the review will be attached to the service.</div>
    </div>

    <div class="mb-3">
      <label class="form-label">Rating</label>
      <select name="rating" class="form-select" required>
        <option value="">-- choose --</option>
        <option value="5">5 — Excellent</option>
        <option value="4">4 — Very good</option>
        <option value="3">3 — Good</option>
        <option value="2">2 — Fair</option>
        <option value="1">1 — Poor</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" maxlength="255">
    </div>

    <div class="mb-3">
      <label class="form-label">Body</label>
      <textarea name="body" class="form-control" rows="6"></textarea>
    </div>

    <div class="mb-3 form-check">
      <input type="checkbox" name="publish_now" value="1" class="form-check-input" id="publishNow">
      <label class="form-check-label" for="publishNow">Publish immediately</label>
    </div>

    <div class="mb-3 form-check">
      <input type="checkbox" name="bypass_duplicates" value="1" class="form-check-input" id="bypassDups">
      <label class="form-check-label" for="bypassDups">Bypass duplicate check</label>
    </div>

    <button class="btn btn-primary">Create review</button>
    <a href="/admin/reviews/index.php" class="btn btn-link">Cancel</a>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
