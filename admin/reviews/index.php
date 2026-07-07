<?php
// admin/reviews/index.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('reviews.view');
require_once __DIR__ . '/../../lib/db_mysqli.php';

$page = max(1, intval($_GET['page'] ?? 1));
$per = 25;
$offset = ($page - 1) * $per;

$total = 0;
if ($res = $mysqli->query("SELECT COUNT(*) AS cnt FROM reviews WHERE status = 'pending'")) { $row = $res->fetch_assoc(); $total = intval($row['cnt']); $res->free(); }

$rows = [];
$sql = "SELECT r.*, u.name AS user_name, s.title AS service_title, p.name AS provider_name
        FROM reviews r
        LEFT JOIN users u ON u.id = r.user_id
        LEFT JOIN services s ON s.id = r.service_id
        LEFT JOIN providers p ON p.id = r.provider_id
        WHERE r.status = 'pending'
        ORDER BY r.created_at ASC
        LIMIT $offset, $per";
if ($res = $mysqli->query($sql)) { while ($r = $res->fetch_assoc()) $rows[] = $r; $res->free(); }

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Pending Reviews</h4>
   <?php if (can('reviews.manage')): ?>
      <a href="<?php echo $domain;?>/admin/reviews/create.php" class="btn btn-primary">Create review</a>
    <?php endif; ?>
  </div>
  <?php if (empty($rows)): ?>
    <div class="alert alert-info">No pending reviews.</div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>User</th><th>Target</th><th>Rating</th><th>Excerpt</th><th>Submitted</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['user_name'], ENT_QUOTES); ?></td>
            <td><?php echo htmlspecialchars($r['service_title'] ?? $r['provider_name'] ?? '-', ENT_QUOTES); ?></td>
            <td><?php echo intval($r['rating']); ?></td>
            <td><?php echo htmlspecialchars(mb_strimwidth($r['body'], 0, 120, '...'), ENT_QUOTES); ?></td>
            <td><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES); ?></td>
            <td class="text-end">
              <a href="<?php echo $domain;?>/admin/reviews/review.php?uuid=<?php echo htmlspecialchars($r['uuid']); ?>" class="btn btn-sm btn-outline-secondary">Review</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php $pages = (int)ceil($total / $per); if ($pages > 1): ?>
      <nav><ul class="pagination">
        <?php for ($i=1;$i<=$pages;$i++): ?>
          <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
        <?php endfor; ?>
      </ul></nav>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
