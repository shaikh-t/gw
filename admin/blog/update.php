<?php
// admin/blog/update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('blog.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF');
}

$uuid = $_POST['id'] ?? '';
$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$excerpt = trim($_POST['excerpt'] ?? '');
$content = trim($_POST['content'] ?? '');
$reading_time = trim($_POST['reading_time'] ?? '5 min read');
$tags = trim($_POST['tags'] ?? '');
$status = trim($_POST['status'] ?? 'draft');
$author_user_id = intval($_POST['author_user_id'] ?? 0);
$image_url = trim($_POST['image_url'] ?? '');

$stmt = $mysqli->prepare("SELECT id, slug FROM blog_posts WHERE uuid = ? LIMIT 1");
$stmt->bind_param('s', $uuid);
$stmt->execute();
$res = $stmt->get_result();
$post = $res->fetch_assoc();
$stmt->close();

if (!$post) {
    $_SESSION['flash_errors'] = ['Invalid blog post ID.'];
    header('Location: index.php');
    exit;
}

$id = $post['id'];

if ($title === '' || $category === '' || $excerpt === '' || $content === '') {
    $_SESSION['flash_errors'] = ['Please fill all required fields.'];
    header('Location: edit.php?uuid=' . $uuid);
    exit;
}

// Check if image file was uploaded
if (!empty($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../../public/uploads/blog';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $resUp = avatar_upload_handle($_FILES['cover_file'], $upload_dir);
    if ($resUp['ok']) {
        $image_url = '/public/uploads/blog/' . $resUp['filename'];
    } else {
        $_SESSION['flash_errors'] = ['Image upload failed: ' . $resUp['error']];
        header('Location: edit.php?uuid=' . $uuid);
        exit;
    }
}

$stmt = $mysqli->prepare("UPDATE blog_posts SET title = ?, excerpt = ?, content = ?, category = ?, reading_time = ?, author_user_id = ?, image_url = ?, tags = ?, status = ? WHERE id = ?");
$stmt->bind_param('sssssisssi', $title, $excerpt, $content, $category, $reading_time, $author_user_id, $image_url, $tags, $status, $id);

if ($stmt->execute()) {
    $_SESSION['flash_success'] = 'Blog post updated successfully.';
    header('Location: index.php');
} else {
    $_SESSION['flash_errors'] = ['Database error: ' . $mysqli->error];
    header('Location: edit.php?uuid=' . $uuid);
}
$stmt->close();
exit;
