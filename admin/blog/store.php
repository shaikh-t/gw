<?php
// admin/blog/store.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('blog.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/upload.php';
require_once __DIR__ . '/../../lib/uuid_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF');
}

$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$excerpt = trim($_POST['excerpt'] ?? '');
$content = trim($_POST['content'] ?? '');
$reading_time = trim($_POST['reading_time'] ?? '5 min read');
$tags = trim($_POST['tags'] ?? '');
$status = trim($_POST['status'] ?? 'draft');
$author_user_id = intval($_POST['author_user_id'] ?? 0);
$image_url = trim($_POST['image_url'] ?? '');

if ($title === '' || $category === '' || $excerpt === '' || $content === '') {
    $_SESSION['flash_errors'] = ['Please fill all required fields.'];
    header('Location: create.php');
    exit;
}

// Generate Slug
function blog_slugify(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[^\p{L}\p{N}\s\-]+/u', '', $s);
    $s = preg_replace('/[\s\-]+/', '-', $s);
    return trim($s, '-');
}

$slug_base = blog_slugify($title);
$slug = $slug_base;
$i = 1;
while (true) {
    $res = $mysqli->query("SELECT id FROM blog_posts WHERE slug = '" . $mysqli->real_escape_string($slug) . "' LIMIT 1");
    if ($res && $res->num_rows === 0) {
        $res->free();
        break;
    }
    if ($res) $res->free();
    $slug = $slug_base . '-' . $i++;
}

// Handle image upload
if (!empty($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../../public/uploads/blog';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $resUp = avatar_upload_handle($_FILES['cover_file'], $upload_dir,900);
    if ($resUp['ok']) {
        $image_url = '/public/uploads/blog/' . $resUp['filename'];
    } else {
        $_SESSION['flash_errors'] = ['Image upload failed: ' . $resUp['error']];
        header('Location: create.php');
        exit;
    }
}

$uuid = generate_uuid();
$stmt = $mysqli->prepare("INSERT INTO blog_posts (uuid, title, slug, excerpt, content, category, reading_time, author_user_id, image_url, tags, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('sssssssisss', $uuid, $title, $slug, $excerpt, $content, $category, $reading_time, $author_user_id, $image_url, $tags, $status);

if ($stmt->execute()) {
    $_SESSION['flash_success'] = 'Blog post created successfully.';
    header('Location: index.php');
} else {
    $_SESSION['flash_errors'] = ['Database error: ' . $mysqli->error];
    header('Location: create.php');
}
$stmt->close();
exit;
