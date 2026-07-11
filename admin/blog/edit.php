<?php
// admin/blog/edit.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('blog.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

$uuid = $_GET['uuid'] ?? $_GET['id'] ?? '';
$stmt = $mysqli->prepare("SELECT * FROM blog_posts WHERE uuid = ? LIMIT 1");
$stmt->bind_param('s', $uuid);
$stmt->execute();
$res = $stmt->get_result();
$post = $res->fetch_assoc();
$stmt->close();

if (!$post) {
    http_response_code(404);
    echo "Blog post not found";
    exit;
}

$res_users = $mysqli->query("SELECT id, name FROM users ORDER BY name");
$authors = [];
if ($res_users) {
    while ($row = $res_users->fetch_assoc()) $authors[] = $row;
    $res_users->free();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="card mt-4 p-4">
  <h4>Edit Blog Post</h4>
  <form method="post" action="update.php" enctype="multipart/form-data">
    <?= csrf_field(); ?>
    <input type="hidden" name="id" value="<?= htmlspecialchars($post['uuid']) ?>">

    <div class="mb-3">
      <label class="form-label">Title *</label>
      <input type="text" name="title" class="form-control" required placeholder="Enter article title" value="<?= htmlspecialchars($post['title']) ?>">
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Category *</label>
        <select name="category" class="form-select" required>
          <option value="Visa & Immigration" <?= $post['category'] === 'Visa & Immigration' ? 'selected' : '' ?>>Visa & Immigration</option>
          <option value="Business Setup" <?= $post['category'] === 'Business Setup' ? 'selected' : '' ?>>Business Setup</option>
          <option value="Documentation" <?= $post['category'] === 'Documentation' ? 'selected' : '' ?>>Documentation</option>
          <option value="Platform Guides" <?= $post['category'] === 'Platform Guides' ? 'selected' : '' ?>>Platform Guides</option>
          <option value="Case Studies" <?= $post['category'] === 'Case Studies' ? 'selected' : '' ?>>Case Studies</option>
          <option value="Consultancy" <?= $post['category'] === 'Consultancy' ? 'selected' : '' ?>>Consultancy</option>
          <option value="Advisory" <?= $post['category'] === 'Advisory' ? 'selected' : '' ?>>Advisory</option>
          <option value="Marketing" <?= $post['category'] === 'Marketing' ? 'selected' : '' ?>>Marketing</option>
        </select>
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label">Author *</label>
        <select name="author_user_id" class="form-select" required>
          <?php foreach ($authors as $auth): ?>
            <option value="<?= intval($auth['id']) ?>" <?= ($auth['id'] == $post['author_user_id']) ? 'selected' : '' ?>><?= htmlspecialchars($auth['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Excerpt / Short Description *</label>
      <textarea name="excerpt" class="form-control" rows="2" required placeholder="A short, catchy overview of the article"><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Content / Body *</label>
      <textarea name="content" class="form-control" rows="10" required placeholder="Write your full article HTML content here..."><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
      <small class="text-muted">You can write HTML or standard paragraphs here.</small>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Reading Time (e.g. 5 min read)</label>
        <input type="text" name="reading_time" class="form-control" value="<?= htmlspecialchars($post['reading_time']) ?>" required>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Tags (comma separated, e.g. UAE, Golden Visa)</label>
        <input type="text" name="tags" class="form-control" placeholder="UAE, Golden Visa" value="<?= htmlspecialchars($post['tags'] ?? '') ?>">
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Cover Image (URL or Upload)</label>
      <?php if (!empty($post['image_url'])): ?>
        <div class="mb-2">
          <img src="<?= htmlspecialchars($post['image_url']) ?>" style="width:120px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">
        </div>
      <?php endif; ?>
      <input type="text" name="image_url" class="form-control mb-2" placeholder="https://images.unsplash.com/... or upload below" value="<?= htmlspecialchars($post['image_url'] ?? '') ?>">
      <input type="file" name="cover_file" class="form-control" accept="image/*">
    </div>

    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
        <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
      </select>
    </div>

    <button class="btn btn-primary px-4">Save Changes</button>
    <a href="index.php" class="btn btn-link">Cancel</a>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
