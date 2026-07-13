<?php
// admin/settings/menus.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('settings.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

// CSRF check function/helpers
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $menu_id = intval($_POST['menu_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);

        if ($menu_id <= 0 || $title === '' || $url === '') {
            $errors[] = "Title and URL are required.";
        } else {
            $stmt = $mysqli->prepare("INSERT INTO menu_items (menu_id, title, url, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('issi', $menu_id, $title, $url, $sort_order);
            if ($stmt->execute()) {
                $success = "Menu item added successfully.";
            } else {
                $errors[] = "Database error: " . $mysqli->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'edit') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);

        if ($item_id <= 0 || $title === '' || $url === '') {
            $errors[] = "Title and URL are required.";
        } else {
            $stmt = $mysqli->prepare("UPDATE menu_items SET title = ?, url = ?, sort_order = ? WHERE id = ?");
            $stmt->bind_param('ssii', $title, $url, $sort_order, $item_id);
            if ($stmt->execute()) {
                $success = "Menu item updated successfully.";
            } else {
                $errors[] = "Database error: " . $mysqli->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'delete') {
        $item_id = intval($_POST['item_id'] ?? 0);
        if ($item_id <= 0) {
            $errors[] = "Invalid menu item.";
        } else {
            $stmt = $mysqli->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->bind_param('i', $item_id);
            if ($stmt->execute()) {
                $success = "Menu item deleted successfully.";
            } else {
                $errors[] = "Database error: " . $mysqli->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all menus
$menus_res = $mysqli->query("SELECT * FROM menus ORDER BY id ASC");
$menus = [];
while ($row = $menus_res->fetch_assoc()) {
    $menus[] = $row;
}

// Determine current menu to edit
$selected_menu_id = intval($_GET['menu_id'] ?? ($menus[0]['id'] ?? 0));
$current_menu = null;
foreach ($menus as $m) {
    if ($m['id'] === $selected_menu_id) {
        $current_menu = $m;
        break;
    }
}

if (!$current_menu && !empty($menus)) {
    $current_menu = $menus[0];
    $selected_menu_id = $current_menu['id'];
}

// Fetch menu items for the selected menu
$items = [];
if ($selected_menu_id > 0) {
    $stmt = $mysqli->prepare("SELECT * FROM menu_items WHERE menu_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->bind_param('i', $selected_menu_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<main class="main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Menu Builder Management</h2>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): ?>
                <div><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body bg-light">
            <form method="GET" class="row align-items-center g-3">
                <div class="col-auto">
                    <label class="form-label mb-0 fw-bold">Select a menu to edit:</label>
                </div>
                <div class="col-auto">
                    <select name="menu_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($menus as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $m['id'] === $selected_menu_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['location']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($current_menu): ?>
        <div class="row">
            <!-- Menu items list -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <strong>Existing items in: <?= htmlspecialchars($current_menu['name']) ?></strong>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($items)): ?>
                            <div class="p-4 text-center text-muted">No items in this menu yet. Add some on the right.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;">Order</th>
                                            <th>Label</th>
                                            <th>URL</th>
                                            <th style="width: 150px; text-align: right;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <form method="POST">
                                                    <?= csrf_field(); ?>
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                    <td>
                                                        <input type="number" name="sort_order" class="form-control form-control-sm" value="<?= $item['sort_order'] ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" name="title" class="form-control form-control-sm" value="<?= htmlspecialchars($item['title']) ?>" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="url" class="form-control form-control-sm" value="<?= htmlspecialchars($item['url']) ?>" required>
                                                    </td>
                                                    <td style="text-align: right;">
                                                        <div class="btn-group">
                                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Save changes">
                                                                Save
                                                            </button>
                                                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this menu item?')" title="Delete item">
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </td>
                                                </form>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Add new menu item -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <strong>Add New Menu Item</strong>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="menu_id" value="<?= $selected_menu_id ?>">

                            <div class="mb-3">
                                <label class="form-label">Link Title / Label</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Services" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Target URL</label>
                                <input type="text" name="url" class="form-control" placeholder="e.g. services.php" required>
                                <small class="text-muted">Can be local page (e.g. services.php) or external link.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="0">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Add Item</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
