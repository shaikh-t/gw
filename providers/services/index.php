<?php
// providers/services/index.php
require_once __DIR__ . '/../lib/middleware.php';
require_login();
require_once __DIR__ . '/../lib/services_helpers.php';
require_once __DIR__ . '/../lib/providers_helpers.php';

$current = current_user();
if (empty($current['id'])) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// find provider owned by current user
$res = $mysqli->query("SELECT id, name FROM providers WHERE owner_user_id = " . intval($current['id']) . " LIMIT 1");
if (!$res || $res->num_rows === 0) {
    $_SESSION['flash_errors'] = ['You do not have a provider profile yet.'];
    header('Location: /providers/dashboard.php');
    exit;
}
$prov = $res->fetch_assoc();
$provider_id = intval($prov['id']);
$res->free();

// fetch services for provider
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$total = 0;
if ($r = $mysqli->query("SELECT COUNT(*) AS cnt FROM services WHERE provider_id = $provider_id")) {
    $row = $r->fetch_assoc();
    $total = intval($row['cnt']);
    $r->free();
}

$services = [];
$sql = "SELECT * FROM services WHERE provider_id = $provider_id ORDER BY created_at DESC LIMIT $offset, $perPage";
if ($r = $mysqli->query($sql)) {
    while ($s = $r->fetch_assoc()) $services[] = $s;
    $r->free();
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>
<div class="card mt-4 p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">My Services — <?php echo htmlspecialchars($prov['name'], ENT_QUOTES); ?></h4>
    <a href="/providers/services/create.php" class="btn btn-primary">Create service</a>
  </div>

  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger"><?php foreach ($_SESSION['flash_errors'] as $e) echo htmlspecialchars($e, ENT_QUOTES) . '<br>'; unset($_SESSION['flash_errors']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>

  <?php if (empty($services)): ?>
    <div class="alert alert-info">You have no services yet.</div>
  <?php else: ?>
    <table class="table table-hover">
      <thead><tr><th>Title</th><th>Price</th><th>Status</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr>
            <td><?php echo htmlspecialchars($s['title'], ENT_QUOTES); ?></td>
            <td><?php echo $s['price'] !== null ? htmlspecialchars($s['currency'] . ' ' . $s['price'], ENT_QUOTES) : '-'; ?></td>
            <td><?php echo htmlspecialchars($s['status'], ENT_QUOTES); ?></td>
            <td><?php echo htmlspecialchars($s['created_at'], ENT_QUOTES); ?></td>
            <td class="text-end">
              <a href="/providers/services/edit.php?id=<?php echo intval($s['id']); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <form method="post" action="/providers/services/delete.php" class="d-inline-block" onsubmit="return confirm('Delete service?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo intval($s['id']); ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php
      $pages = (int)ceil($total / $perPage);
      if ($pages > 1):
    ?>
    <nav>
      <ul class="pagination">
        <?php for ($i=1;$i<=$pages;$i++): ?>
          <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
