<?php
// admin/blog/create.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('blog.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

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
  <h4>Create Blog Post</h4>
  <form method="post" action="store.php" enctype="multipart/form-data">
    <?= csrf_field(); ?>

    <div class="mb-3">
      <label class="form-label">Title *</label>
      <input type="text" name="title" class="form-control" required placeholder="Enter article title">
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Category *</label>
        <select name="category" class="form-select" required>
          <option value="" disabled selected>Select category...</option>
          <option value="Visa & Immigration">Visa & Immigration</option>
          <option value="Business Setup">Business Setup</option>
          <option value="Documentation">Documentation</option>
          <option value="Platform Guides">Platform Guides</option>
          <option value="Case Studies">Case Studies</option>
          <option value="Consultancy">Consultancy</option>
          <option value="Advisory">Advisory</option>
          <option value="Marketing">Marketing</option>
        </select>
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label">Author *</label>
        <select name="author_user_id" class="form-select" required>
          <option value="" disabled selected>Select author...</option>
          <?php foreach ($authors as $auth): ?>
            <option value="<?= intval($auth['id']) ?>" <?= ($auth['id'] == $_SESSION['user']['id']) ? 'selected' : '' ?>><?= htmlspecialchars($auth['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Excerpt / Short Description *</label>
      <textarea name="excerpt" class="form-control" rows="2" required placeholder="A short, catchy overview of the article"></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Content / Body *</label>
      <textarea name="content" class="form-control" rows="10" required placeholder="Write your full article HTML content here..."></textarea>
      <small class="text-muted">You can write HTML or standard paragraphs here.</small>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Reading Time (e.g. 5 min read)</label>
        <input type="text" name="reading_time" class="form-control" value="5 min read" required>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Tags (comma separated, e.g. UAE, Golden Visa)</label>
        <input type="text" name="tags" class="form-control" placeholder="UAE, Golden Visa">
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Cover Image (URL or Upload)</label>
      <input type="text" name="image_url" class="form-control mb-2" placeholder="https://images.unsplash.com/... or upload below">
      <input type="file" name="cover_file" class="form-control" accept="image/*">
    </div>

    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="draft">Draft</option>
        <option value="published" selected>Published</option>
      </select>
    </div>

    <button class="btn btn-primary px-4">Create Article</button>
    <a href="index.php" class="btn btn-link">Cancel</a>
  </form>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
