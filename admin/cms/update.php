<?php
// admin/cms/update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('cms.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF');
}

$page_name = $_POST['page_name'] ?? '';
if (!in_array($page_name, ['about', 'contact', 'how_it_works'])) {
    $_SESSION['flash_errors'] = 'Invalid page name.';
    header('Location: index.php');
    exit;
}

$content = $_POST['content'] ?? [];

// Process custom field formats
if ($page_name === 'about') {
    // Mission proof list (split lines)
    $proof_text = $_POST['mission_proof_text'] ?? '';
    $proof_lines = array_filter(array_map('trim', explode("\n", $proof_text)));
    $content['mission_proof'] = array_values($proof_lines);

    // Convert stats highlight checkboxes to boolean
    for ($i = 0; $i < 4; $i++) {
        $content['stats'][$i]['highlight'] = isset($content['stats'][$i]['highlight']) && $content['stats'][$i]['highlight'] == '1';
    }
} elseif ($page_name === 'contact') {
    // Convert hours closed checkbox to boolean
    for ($i = 0; $i < 3; $i++) {
        $content['hours'][$i]['closed'] = isset($content['hours'][$i]['closed']) && $content['hours'][$i]['closed'] == '1';
    }
} elseif ($page_name === 'how_it_works') {
    // Process bullets for each step
    for ($i = 0; $i < 4; $i++) {
        $bullets_text = $_POST["steps_bullets_text_$i"] ?? '';
        $bullets = array_filter(array_map('trim', explode("\n", $bullets_text)));
        $content['steps'][$i]['bullets'] = array_values($bullets);
    }
}

// Update the database
$stmt = $mysqli->prepare("UPDATE cms_pages SET content = ? WHERE page_name = ?");
$json_str = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$stmt->bind_param('ss', $json_str, $page_name);

if ($stmt->execute()) {
    $_SESSION['flash_success'] = 'Page content updated successfully.';
} else {
    $_SESSION['flash_errors'] = 'Database update failed: ' . $mysqli->error;
}
$stmt->close();

header('Location: index.php?tab=' . urlencode($page_name));
exit;
