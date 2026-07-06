<?php
// admin/reviews/review.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('reviews.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/reviews_helpers.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /admin/reviews/index.php'); exit; }

$res = $mysqli->query("SELECT r.*, u.name AS user_name, u.email AS user_email, s.title AS service_title, p.name AS provider_name FROM reviews r LEFT JOIN users u ON u.id = r.user_id LEFT JOIN services s ON s.id = r.service_id LEFT JOIN providers p ON p.id = r.provider_id WHERE r.id = $id LIMIT 1");
if (!$res || $res->num_rows === 0) { http_response_code(404); echo 'Not found'; exit; }
$row = $res->fetch_assoc(); $res->free();

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Moderate Review #<?php echo intval($row['id']); ?></h4>
  <div class="mb-3"><strong>User</strong>: <?php echo htmlspecialchars($row['user_name'] . ' <' . $row['user_email'] . '>', ENT_QUOTES); ?></div>
  <div class="mb-3"><strong>Target</strong>: <?php echo htmlspecialchars($row['service_title'] ?? $row['provider_name'] ?? '-', ENT_QUOTES); ?></div>
  <div class="mb-3"><strong>Rating</strong>: <?php echo intval($row['rating']); ?></div>
  <div class="mb-3"><strong>Title</strong>: <?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?></div>
  <div class="mb-3"><strong>Body</strong>: <div class="border p-2"><?php echo nl2br(htmlspecialchars($row['body'], ENT_QUOTES)); ?></div></div>

  <form method="post" action="/admin/reviews/action.php">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo intval($row['id']); ?>">
    <div class="mb-3">
      <label class="form-label">Action</label>
      <select name="action" class="form-select" required>
        <option value="">-- choose --</option>
        <option value="approve">Approve</option>
        <option value="reject">Reject</option>
        <option value="hide">Hide</option>
        <option value="flag_spam">Flag as spam</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Note</label>
      <textarea name="note" class="form-control" rows="3"></textarea>
    </div>
    <button class="btn btn-success">Apply</button>
    <a href="/admin/reviews/index.php" class="btn btn-link">Back</a>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
