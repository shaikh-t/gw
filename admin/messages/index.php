<?php
// admin/messages/index.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('messages.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$count_res = $mysqli->query("SELECT COUNT(*) as cnt FROM contact_messages");
$total = $count_res ? $count_res->fetch_assoc()['cnt'] : 0;

$sql = "SELECT m.*, u.name as replied_by_name
        FROM contact_messages m
        LEFT JOIN users u ON u.id = m.replied_by
        ORDER BY m.created_at DESC
        LIMIT $offset, $perPage";
$messages = [];
if ($res = $mysqli->query($sql)) {
    while ($row = $res->fetch_assoc()) $messages[] = $row;
    $res->free();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Contact Inquiries</h4>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>

  <table class="table table-hover">
    <thead>
      <tr>
        <th>Date</th>
        <th>Name</th>
        <th>Email</th>
        <th>Topic</th>
        <th>Message Excerpt</th>
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($messages)): ?>
        <tr><td colspan="7" class="text-center text-muted">No contact messages received yet.</td></tr>
      <?php else: ?>
        <?php foreach ($messages as $m): ?>
          <tr>
            <td><?= htmlspecialchars(date('M d, Y H:i', strtotime($m['created_at']))) ?></td>
            <td><?= htmlspecialchars($m['name']) ?></td>
            <td><a href="mailto:<?= htmlspecialchars($m['email']) ?>"><?= htmlspecialchars($m['email']) ?></a></td>
            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($m['topic']) ?></span></td>
            <td><?= htmlspecialchars(mb_strimwidth($m['message'], 0, 50, '...')) ?></td>
            <td>
              <?php if (!empty($m['replied_at'])): ?>
                <span class="badge bg-success">Replied</span>
              <?php else: ?>
                <span class="badge bg-warning text-dark">Pending</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a href="reply.php?uuid=<?= htmlspecialchars($m['uuid']) ?>" class="btn btn-sm btn-outline-primary">
                <?= !empty($m['replied_at']) ? 'View / Reply Again' : 'Reply' ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <?php
    $pages = (int)ceil($total / $perPage);
    if ($pages > 1):
  ?>
  <nav>
    <ul class="pagination">
      <?php for ($i=1;$i<=$pages;$i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
