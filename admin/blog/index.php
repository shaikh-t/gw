<?php
// admin/blog/index.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('blog.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = trim($_GET['q'] ?? '');
$where = '';
if ($search !== '') {
    $where = "WHERE b.title LIKE '%" . $mysqli->real_escape_string($search) . "%' OR b.category LIKE '%" . $mysqli->real_escape_string($search) . "%'";
}

$count_res = $mysqli->query("SELECT COUNT(*) as cnt FROM blog_posts b $where");
$total = ($count_res) ? $count_res->fetch_assoc()['cnt'] : 0;

$sql = "SELECT b.*, u.name as author_name FROM blog_posts b LEFT JOIN users u ON u.id = b.author_user_id $where ORDER BY b.created_at DESC LIMIT $offset, $perPage";
$posts = [];
if ($res = $mysqli->query($sql)) {
    while ($row = $res->fetch_assoc()) $posts[] = $row;
    $res->free();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Blog Posts</h4>
    <a href="create.php" class="btn btn-primary">Create blog post</a>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>

  <form method="get" class="row g-2 mb-3">
    <div class="col-auto">
      <input type="text" name="q" class="form-control" placeholder="Search by title or category..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-secondary">Search</button>
    </div>
  </form>

  <table class="table table-hover">
    <thead>
      <tr>
        <th>Cover</th>
        <th>Title</th>
        <th>Category</th>
        <th>Author</th>
        <th>Reading Time</th>
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($posts)): ?>
        <tr><td colspan="7" class="text-center text-muted">No blog posts found.</td></tr>
      <?php else: ?>
        <?php foreach ($posts as $p): ?>
          <tr>
            <td>
              <?php if (!empty($p['image_url'])): ?>
                <img src="<?= htmlspecialchars($p['image_url']) ?>" style="width:60px;height:40px;object-fit:cover;border-radius:4px;">
              <?php else: ?>
                <span class="text-muted small">No Image</span>
              <?php endif; ?>
            </td>
            <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
            <td><?= htmlspecialchars($p['category']) ?></td>
            <td><?= htmlspecialchars($p['author_name'] ?? 'System') ?></td>
            <td><?= htmlspecialchars($p['reading_time']) ?></td>
            <td>
              <span class="badge bg-<?= $p['status'] === 'published' ? 'success' : 'secondary' ?>">
                <?= ucfirst(htmlspecialchars($p['status'])) ?>
              </span>
            </td>
            <td class="text-end">
              <a href="edit.php?uuid=<?= htmlspecialchars($p['uuid']) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <form method="post" action="delete.php" class="d-inline-block" onsubmit="return confirm('Delete this blog post?');">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($p['uuid']) ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
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
        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
