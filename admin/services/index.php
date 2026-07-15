<?php
// admin/services/index.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('services.view');
require_once __DIR__ . '/../../lib/services_helpers.php';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$filters = [];
// Admin services page is for managing Master Services (templates)
$filters['master_only'] = true;

if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['category_id'])) $filters['category_id'] = intval($_GET['category_id']);

$total = services_count($filters);
$services = services_paginated($page, $perPage, $filters);
$categories = service_categories_all();

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Master Services</h4>
    <?php if (can('services.manage')): ?>
      <a href="<?php echo $domain;?>/admin/services/create.php" class="btn btn-primary">Create master service</a>
    <?php endif; ?>
  </div>

  <form method="get" class="row g-2 mb-3">
    <div class="col-auto">
      <select name="status" class="form-select">
        <option value="">All statuses</option>
        <option value="draft" <?php if(($filters['status'] ?? '')==='draft') echo 'selected'; ?>>Draft</option>
        <option value="published" <?php if(($filters['status'] ?? '')==='published') echo 'selected'; ?>>Published</option>
        <option value="archived" <?php if(($filters['status'] ?? '')==='archived') echo 'selected'; ?>>Archived</option>
      </select>
    </div>
    <div class="col-auto">
      <select name="category_id" class="form-select">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?php echo intval($c['id']); ?>" <?php if(($filters['category_id'] ?? '')===$c['id']) echo 'selected'; ?>><?php echo htmlspecialchars($c['name'], ENT_QUOTES); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-secondary">Filter</button>
    </div>
  </form>

  <table class="table table-hover">
    <thead><tr><th>Title</th><th>Category</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($services as $s): ?>
        <tr>
          <td>
             <div class="d-flex align-items-center gap-2">
               <?php $imgs = $s['images'] ?: []; ?>
               <img src="<?php echo htmlspecialchars($imgs[0] ?? '/public/assets/img/service-placeholder.png', ENT_QUOTES); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
               <span><?php echo htmlspecialchars($s['title'], ENT_QUOTES); ?></span>
             </div>
          </td>
          <td><?php echo htmlspecialchars($s['category_name'] ?? '', ENT_QUOTES); ?></td>
          <td><span class="badge <?php echo $s['status'] === 'published' ? 'bg-success' : 'bg-secondary'; ?>"><?php echo htmlspecialchars($s['status'], ENT_QUOTES); ?></span></td>
          <td class="text-end">
            <a href="<?php echo $domain;?>/admin/services/edit.php?uuid=<?php echo htmlspecialchars($s['uuid']); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
            <?php if (can('services.manage')): ?>
              <form method="post" action="<?php echo $domain;?>/admin/services/delete.php" class="d-inline-block" onsubmit="return confirm('Delete master service?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($s['uuid']); ?>">
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
