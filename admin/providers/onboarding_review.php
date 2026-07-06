<?php
// admin/providers/onboarding_review.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/onboarding_helpers.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /admin/providers/onboarding_list.php'); exit; }

$res = $mysqli->query("SELECT po.*, p.* FROM provider_onboarding po JOIN providers p ON p.id = po.provider_id WHERE po.id = $id LIMIT 1");
if (!$res || $res->num_rows === 0) { http_response_code(404); echo 'Not found'; exit; }
$row = $res->fetch_assoc(); $res->free();

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Review onboarding for <?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?></h4>

  <h6>Profile</h6>
  <p><?php echo nl2br(htmlspecialchars($row['description'] ?? '', ENT_QUOTES)); ?></p>

  <h6>Verification documents</h6>
  <?php
    $docs = json_decode($row['verification_docs'] ?? '[]', true) ?: [];
    if (!empty($docs)):
  ?>
    <ul>
      <?php foreach ($docs as $d): ?>
        <li><a href="<?php echo htmlspecialchars($d, ENT_QUOTES); ?>" target="_blank"><?php echo htmlspecialchars(basename($d), ENT_QUOTES); ?></a></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>No documents uploaded.</p>
  <?php endif; ?>

  <h6>Duplicate check</h6>
  <?php
    $dup = onboarding_check_duplicates($row['provider_id']);
    if ($dup['status'] === 'possible_duplicate'):
  ?>
    <div class="alert alert-warning">Possible duplicates found:
      <ul><?php foreach ($dup['matches'] as $m) echo '<li>' . htmlspecialchars($m['name'] . ' — ' . ($m['city'] ?? '') . ' — ' . ($m['phone'] ?? ''), ENT_QUOTES) . '</li>'; ?></ul>
    </div>
  <?php else: ?>
    <div class="alert alert-success">No duplicates detected.</div>
  <?php endif; ?>

  <form method="post" action="/admin/providers/onboarding_action.php">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="onb_id" value="<?php echo intval($id); ?>">
    <div class="mb-3">
      <label class="form-label">Action</label>
      <select name="action" class="form-select" required>
        <option value="">-- choose --</option>
        <option value="approve">Approve (verify)</option>
        <option value="request_more">Request more info</option>
        <option value="reject">Reject</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Note (optional)</label>
      <textarea name="note" class="form-control" rows="3"></textarea>
    </div>
    <button class="btn btn-success">Submit</button>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
