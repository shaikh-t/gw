<?php
// admin/providers/index.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('providers.view');
require_once __DIR__ . '/../../lib/providers_helpers.php';
require_once __DIR__ . '/../../lib/role_helpers.php';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$filters = [];
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['city'])) $filters['city'] = $_GET['city'];

$total = providers_count($filters);
$providers = providers_paginated($page, $perPage, $filters);

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Providers</h4>
    <?php if (can('providers.manage')): ?>
      <a href="<?php echo $domain;?>/admin/providers/create.php" class="btn btn-primary">Create provider</a>
    <?php endif; ?>
   

    <?php if (can('providers.manage')): ?>
  <?php $pending_onb = onboarding_pending_count(); ?>
  <a href="<?php echo $domain;?>/admin/providers/create_onboard.php" class="btn btn-outline-primary">Create & Onboard</a>
  <a href="<?php echo $domain;?>/admin/providers/onboarding_list.php" class="btn btn-outline-secondary">
    Onboarding Queue<?php if ($pending_onb): ?> <span class="badge bg-danger ms-1"><?php echo intval($pending_onb); ?></span><?php endif; ?>
  </a>
<?php endif; ?>

  </div>

  <form method="get" class="row g-2 mb-3">
    <div class="col-auto">
      <select name="status" class="form-select">
        <option value="">All statuses</option>
        <option value="draft" <?php if(($filters['status'] ?? '')==='draft') echo 'selected'; ?>>Draft</option>
        <option value="active" <?php if(($filters['status'] ?? '')==='active') echo 'selected'; ?>>Active</option>
        <option value="inactive" <?php if(($filters['status'] ?? '')==='inactive') echo 'selected'; ?>>Inactive</option>
      </select>
    </div>
    <div class="col-auto">
      <input name="city" class="form-control" placeholder="City" value="<?php echo htmlspecialchars($filters['city'] ?? '', ENT_QUOTES); ?>">
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-secondary">Filter</button>
    </div>
  </form>

  <table class="table table-hover">
    <thead><tr><th></th><th>Name</th><th>Location</th><th>Status</th><th>Verification</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($providers as $p): ?>
        <tr>
          <td><img src="<?php echo $domain.htmlspecialchars($p['logo'] ?: '/public/assets/img/provider-placeholder.png', ENT_QUOTES); ?>" style="width:100px;object-fit:contain;border-radius:6px;"></td>
          <td><?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars(implode(', ', array_filter([$p['city'],$p['state'],$p['country']])), ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($p['status'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($p['verification_status'] ?? 'unverified', ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($p['created_at'], ENT_QUOTES); ?></td>
          <td class="text-end">
           <?php if (can('providers.manage') || $p['owner_user_id'] == $current['id']): ?>
  <a class="btn btn-sm btn-outline-primary" href="<?php echo $domain;?>/admin/providers/dashboard.php?id=<?php echo intval($p['id']); ?>">Admin View</a>
<?php endif; ?>  
          <a href="<?php echo $domain;?>/admin/providers/edit.php?id=<?php echo intval($p['id']); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
            <?php if (can('providers.manage')): ?>
              <form method="post" action="<?php echo $domain;?>/admin/providers/delete.php" class="d-inline-block" onsubmit="return confirm('Delete provider?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo intval($p['id']); ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
            <?php endif; ?>
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
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
