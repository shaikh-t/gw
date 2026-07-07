<?php
// admin/service_tags/index.php
// Admin listing of service tags with pagination

require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.view');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/pagination.php';
require_once __DIR__ . '/../../lib/services_helpers.php';

$page = get_current_page('page', 1);
$perPage = 20;

// total tags
$total = 0;
if ($res = $mysqli->query("SELECT COUNT(*) AS cnt FROM service_tags")) {
    $row = $res->fetch_assoc();
    $total = (int)$row['cnt'];
    $res->free();
}

// pagination meta
$meta = paginate($total, $perPage, $page);
$offset = $meta['offset'];
$limit = $meta['limit'];

// fetch tags for current page
$tags = [];
$sql = "SELECT id, name FROM service_tags ORDER BY name ASC LIMIT " . intval($offset) . ", " . intval($limit);
if ($res = $mysqli->query($sql)) {
    while ($r = $res->fetch_assoc()) $tags[] = $r;
    $res->free();
}

// build base query preserving other GET params except page
$baseQuery = build_query_string([], 'page');
$baseQuery = $baseQuery === '' ? '?' : $baseQuery . '&';

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Service Tags</h4>
    <?php if (can('services.manage')): ?>
      <a href="<?php echo $domain; ?>/admin/service_tags/create.php" class="btn btn-primary">Create tag</a>
    <?php endif; ?>
  </div>

  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger">
      <?php foreach ($_SESSION['flash_errors'] as $e) echo htmlspecialchars($e, ENT_QUOTES) . '<br>'; unset($_SESSION['flash_errors']); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">
      <?php echo htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES); unset($_SESSION['flash_success']); ?>
    </div>
  <?php endif; ?>

  <?php if (empty($tags)): ?>
    <div class="alert alert-info">No tags found.</div>
  <?php else: ?>
    <table class="table table-hover">
      <thead>
        <tr>
          <th style="width:60%;">Name</th>
          <th style="width:40%;" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tags as $t): ?>
          <tr>
            <td><?php echo htmlspecialchars($t['name'], ENT_QUOTES); ?></td>
            <td class="text-end">
              <a href="<?php echo $domain;?>/admin/service_tags/edit.php?uuid=<?php echo htmlspecialchars($t['uuid']); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <?php if (can('services.manage')): ?>
                <form method="post" action="<?php echo $domain;?>/admin/service_tags/delete.php" class="d-inline-block" onsubmit="return confirm('Delete tag?');">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo htmlspecialchars($t['uuid']); ?>">
                  <button class="btn btn-sm btn-danger">Delete</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center">
      <div class="text-muted">
        <?php
          $start = $meta['offset'] + 1;
          $end = min($meta['offset'] + $meta['perPage'], $meta['total']);
          if ($meta['total'] === 0) {
              echo 'No tags';
          } else {
              echo "Showing {$start}–{$end} of " . intval($meta['total']);
          }
        ?>
      </div>
      <div>
        <?php echo render_pagination($meta, $baseQuery, 2); ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
