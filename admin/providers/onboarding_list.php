<?php
// admin/providers/onboarding_list.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.view');
require_once __DIR__ . '/../../lib/db_mysqli.php';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$total = 0;
if ($res = $mysqli->query("SELECT COUNT(*) AS cnt FROM provider_onboarding")) { $row = $res->fetch_assoc(); $total = intval($row['cnt']); $res->free(); }

$rows = [];
$sql = "SELECT po.id, po.provider_id, p.name AS provider_name, p.email, po.step, po.duplicate_check_status, p.verification_status, po.created_at
        FROM provider_onboarding po
        LEFT JOIN providers p ON p.id = po.provider_id
        ORDER BY po.created_at DESC LIMIT $offset, $perPage";
if ($res = $mysqli->query($sql)) { while ($r = $res->fetch_assoc()) $rows[] = $r; $res->free(); }

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Onboarding queue</h4>
  </div>

  <table class="table">
    <thead><tr><th>Provider</th><th>Email</th><th>Step</th><th>Duplicate</th><th>Verification</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r['provider_name'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($r['email'] ?? '', ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($r['step'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($r['duplicate_check_status'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($r['verification_status'] ?? '', ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES); ?></td>
          <td class="text-end">
            <a href="/admin/providers/onboarding_review.php?uuid=<?php echo htmlspecialchars($r['uuid']); ?>" class="btn btn-sm btn-outline-secondary">Review</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php
    $pages = (int)ceil($total / $perPage);
    if ($pages > 1):
  ?>
  <nav><ul class="pagination">
    <?php for ($i=1;$i<=$pages;$i++): ?>
      <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
    <?php endfor; ?>
  </ul></nav>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
