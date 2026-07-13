<?php
// admin/settings/features.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('settings.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/uuid_helper.php';

$errors = [];
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon_class = trim($_POST['icon_class'] ?? 'bi-star');
        $sort_order = intval($_POST['sort_order'] ?? 0);

        if ($title === '') {
            $errors[] = "Title is required.";
        } else {
            $uuid = generate_uuid();
            $stmt = $mysqli->prepare("INSERT INTO landing_features (uuid, title, description, icon_class, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssi', $uuid, $title, $description, $icon_class, $sort_order);
            if ($stmt->execute()) {
                $success = "Feature created successfully.";
            } else {
                $errors[] = "Database error: " . $mysqli->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'edit') {
        $uuid = trim($_POST['uuid'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon_class = trim($_POST['icon_class'] ?? 'bi-star');
        $sort_order = intval($_POST['sort_order'] ?? 0);

        if ($uuid === '' || $title === '') {
            $errors[] = "Title is required.";
        } else {
            $stmt = $mysqli->prepare("UPDATE landing_features SET title = ?, description = ?, icon_class = ?, sort_order = ? WHERE uuid = ?");
            $stmt->bind_param('sssis', $title, $description, $icon_class, $sort_order, $uuid);
            if ($stmt->execute()) {
                $success = "Feature updated successfully.";
            } else {
                $errors[] = "Database error: " . $mysqli->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'delete') {
        $uuid = trim($_POST['uuid'] ?? '');
        if ($uuid === '') {
            $errors[] = "Invalid feature selection.";
        } else {
            $stmt = $mysqli->prepare("DELETE FROM landing_features WHERE uuid = ?");
            $stmt->bind_param('s', $uuid);
            if ($stmt->execute()) {
                $success = "Feature deleted successfully.";
            } else {
                $errors[] = "Database error: " . $mysqli->error;
            }
            $stmt->close();
        }
    }
}

// Check edit state
$edit_feature = null;
if (isset($_GET['edit_uuid'])) {
    $edit_uuid = trim($_GET['edit_uuid']);
    $stmt = $mysqli->prepare("SELECT * FROM landing_features WHERE uuid = ? LIMIT 1");
    $stmt->bind_param('s', $edit_uuid);
    $stmt->execute();
    $res = $stmt->get_result();
    $edit_feature = $res->fetch_assoc();
    $stmt->close();
}

// Fetch all features
$features_res = $mysqli->query("SELECT * FROM landing_features ORDER BY sort_order ASC, id ASC");
$features = [];
if ($features_res) {
    while ($row = $features_res->fetch_assoc()) {
        $features[] = $row;
    }
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<main class="main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Landing Features (GW Edge)</h2>
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

    <div class="row">
        <!-- List section -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header bg-white"><strong>All Features</strong></div>
                <div class="card-body p-0">
                    <?php if (empty($features)): ?>
                        <div class="p-4 text-center text-muted">No features found. Use the form to create one.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 80px;">Order</th>
                                        <th style="width: 60px;">Icon</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th style="width: 140px; text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($features as $f): ?>
                                        <tr>
                                            <td><?= $f['sort_order'] ?></td>
                                            <td class="text-center"><i class="bi <?= htmlspecialchars($f['icon_class'] ?? 'bi-star') ?> fs-5 text-primary"></i></td>
                                            <td><strong><?= htmlspecialchars($f['title']) ?></strong></td>
                                            <td><small class="text-muted"><?= htmlspecialchars($f['description']) ?></small></td>
                                            <td style="text-align: right;">
                                                <div class="btn-group">
                                                    <a href="?edit_uuid=<?= $f['uuid'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this feature?')">
                                                        <?= csrf_field(); ?>
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="uuid" value="<?= $f['uuid'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Form section -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <strong><?= $edit_feature ? 'Edit Feature' : 'Create Feature' ?></strong>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="<?= $edit_feature ? 'edit' : 'create' ?>">
                        <?php if ($edit_feature): ?>
                            <input type="hidden" name="uuid" value="<?= htmlspecialchars($edit_feature['uuid']) ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($edit_feature['title'] ?? '') ?>" placeholder="e.g. Escrow Payments" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Brief explanation of feature..." required><?= htmlspecialchars($edit_feature['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Icon Class (Bootstrap Icons)</label>
                            <input type="text" name="icon_class" class="form-control" value="<?= htmlspecialchars($edit_feature['icon_class'] ?? 'bi-shield-check') ?>" placeholder="e.g. bi-shield-check" required>
                            <small class="text-muted">Find classes at <a href="https://icons.getbootstrap.com/" target="_blank">icons.getbootstrap.com</a>.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="<?= htmlspecialchars($edit_feature['sort_order'] ?? '0') ?>">
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><?= $edit_feature ? 'Save Changes' : 'Create Feature' ?></button>
                        <?php if ($edit_feature): ?>
                            <a href="features.php" class="btn btn-link w-100 mt-2 text-center">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
