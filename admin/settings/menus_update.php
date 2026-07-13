<?php
// admin/settings/menus_update.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('settings.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $domain . '/admin/settings/menus.php');
    exit;
}

if (!csrf_check($_POST['_csrf'] ?? '')) {
    die('Invalid CSRF');
}

$menu_id = intval($_POST['menu_id'] ?? 0);
if ($menu_id <= 0) {
    $_SESSION['flash_errors'] = ['Invalid menu ID'];
    header('Location: ' . $domain . '/admin/settings/menus.php');
    exit;
}

$mysqli->begin_transaction();

try {
    // Delete existing items for this menu
    $mysqli->query("DELETE FROM menu_items WHERE menu_id = $menu_id");

    // Insert new items
    if (!empty($_POST['items']) && is_array($_POST['items'])) {
        $sort = 0;
        foreach ($_POST['items'] as $item) {
            $label = $mysqli->real_escape_string($item['label'] ?? '');
            $url = $mysqli->real_escape_string($item['url'] ?? '');
            if ($label !== '' && $url !== '') {
                $mysqli->query("INSERT INTO menu_items (menu_id, label, url, sort_order) VALUES ($menu_id, '$label', '$url', $sort)");
                $sort++;
            }
        }
    }

    $mysqli->commit();
    $_SESSION['flash_success'] = 'Menu updated successfully.';
} catch (Exception $e) {
    $mysqli->rollback();
    $_SESSION['flash_errors'] = ['Failed to update menu: ' . $e->getMessage()];
}

header('Location: ' . $domain . '/admin/settings/menus.php');
exit;
