<?php
// admin/settings/testimonials.php
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
        $client_name = trim($_POST['client_name'] ?? '');
        $client_role = trim($_POST['client_role'] ?? '');
        $client_location = trim($_POST['client_location'] ?? '');
        $quote = trim($_POST['quote'] ?? '');
        $avatar_text = trim($_POST['avatar_text'] ?? '');
        $stars = intval($_POST['stars'] ?? 5);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($client_name === '' || $quote === '') {
            $errors[] = "Client Name and Quote are required.";
        } else {
            $uuid = generate_uuid();
            $stmt = $mysqli->prepare("INSERT INTO testimonials (uuid, client_name, client_role, client_location, quote, avatar_text, stars, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssssii', $uuid, $client_name, $client_role, $client_location, $quote, $avatar_text, $stars, $is_active);
            if ($stmt->execute()) {
                $success = "Testimonial created successfully.";
            } else {
                $errors[] = "Database error: " . $mysqli->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'edit') {
        $uuid = trim($_POST['uuid'] ?? '');
        $client_name = trim($_POST['client_name'] ?? '');
        $client_role = trim($_POST['client_role'] ?? '');
        $client_location = trim($_POST['client_location'] ?? '');
        $quote = trim($_POST['quote'] ?? '');
        $avatar_text = trim($_POST['avatar_text'] ?? '');
        $stars = intval($_POST['stars'] ?? 5);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($uuid === '' || $client_name === '' || $quote === '') {
            $errors[] = "Client Name and Quote are required.";
        } else {
            $stmt = $mysqli->prepare("UPDATE testimonials SET client_name = ?, client_role = ?, client_location = ?, quote = ?, avatar_text = ?, stars = ?, is_active = ? WHERE uuid = ?");
            $stmt->bind_param('sssssiis', $client_name, $client_role, $client_location, $quote, $avatar_text, $stars, $is_active, $uuid);
            if ($stmt->execute()) {
                $success = "Testimonial updated successfully.";
            } else {
                $errors[] = "Database error: " . $mysqli->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'delete') {
        $uuid = trim($_POST['uuid'] ?? '');
        if ($uuid === '') {
            $errors[] = "Invalid testimonial selection.";
        } else {
            $stmt = $mysqli->prepare("DELETE FROM testimonials WHERE uuid = ?");
            $stmt->bind_param('s', $uuid);
            if ($stmt->execute()) {
                $success = "Testimonial deleted successfully.";
            } else {
                $errors[] = "Database error: " . $mysqli->error;
            }
            $stmt->close();
        }
    }
}

// Check edit state
$edit_testimonial = null;
if (isset($_GET['edit_uuid'])) {
    $edit_uuid = trim($_GET['edit_uuid']);
    $stmt = $mysqli->prepare("SELECT * FROM testimonials WHERE uuid = ? LIMIT 1");
    $stmt->bind_param('s', $edit_uuid);
    $stmt->execute();
    $res = $stmt->get_result();
    $edit_testimonial = $res->fetch_assoc();
    $stmt->close();
}

// Fetch all testimonials
$testimonials_res = $mysqli->query("SELECT * FROM testimonials ORDER BY created_at DESC");
$testimonials = [];
if ($testimonials_res) {
    while ($row = $testimonials_res->fetch_assoc()) {
        $testimonials[] = $row;
    }
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<main class="main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Client Testimonials</h2>
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
                <div class="card-header bg-white"><strong>All Testimonials</strong></div>
                <div class="card-body p-0">
                    <?php if (empty($testimonials)): ?>
                        <div class="p-4 text-center text-muted">No testimonials found. Use the form to create one.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;">Avatar</th>
                                        <th>Client</th>
                                        <th>Quote</th>
                                        <th>Stars</th>
                                        <th style="width: 80px;">Status</th>
                                        <th style="width: 140px; text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($testimonials as $t): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-dark rounded-circle p-2 d-inline-block text-center" style="width: 38px; height: 38px; font-weight: bold; line-height: 22px;">
                                                    <?= htmlspecialchars($t['avatar_text'] ?? '') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($t['client_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($t['client_role'] ?? '') ?> · <?= htmlspecialchars($t['client_location'] ?? '') ?></small>
                                            </td>
                                            <td><small class="text-muted">"<?= htmlspecialchars($t['quote']) ?>"</small></td>
                                            <td class="text-warning">
                                                <?php for($i=0; $i<$t['stars']; $i++): ?>★<?php endfor; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $t['is_active'] ? 'success' : 'secondary' ?>">
                                                    <?= $t['is_active'] ? 'Active' : 'Hidden' ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="btn-group">
                                                    <a href="?edit_uuid=<?= $t['uuid'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this testimonial?')">
                                                        <?= csrf_field(); ?>
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="uuid" value="<?= $t['uuid'] ?>">
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
                    <strong><?= $edit_testimonial ? 'Edit Testimonial' : 'Create Testimonial' ?></strong>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="<?= $edit_testimonial ? 'edit' : 'create' ?>">
                        <?php if ($edit_testimonial): ?>
                            <input type="hidden" name="uuid" value="<?= htmlspecialchars($edit_testimonial['uuid']) ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Client Name</label>
                            <input type="text" name="client_name" class="form-control" value="<?= htmlspecialchars($edit_testimonial['client_name'] ?? '') ?>" placeholder="e.g. Sarah Thompson" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Client Role / Position</label>
                            <input type="text" name="client_role" class="form-control" value="<?= htmlspecialchars($edit_testimonial['client_role'] ?? '') ?>" placeholder="e.g. Business Owner" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Client Location / Emirate</label>
                            <input type="text" name="client_location" class="form-control" value="<?= htmlspecialchars($edit_testimonial['client_location'] ?? '') ?>" placeholder="e.g. Abu Dhabi" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Quote</label>
                            <textarea name="quote" class="form-control" rows="4" placeholder="Client feedback quote..." required><?= htmlspecialchars($edit_testimonial['quote'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Avatar Text (Initials)</label>
                            <input type="text" name="avatar_text" class="form-control" maxlength="3" value="<?= htmlspecialchars($edit_testimonial['avatar_text'] ?? 'ST') ?>" placeholder="e.g. ST" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rating Stars</label>
                            <select name="stars" class="form-select">
                                <?php for($i=5; $i>=1; $i--): ?>
                                    <option value="<?= $i ?>" <?= ($edit_testimonial['stars'] ?? 5) == $i ? 'selected' : '' ?>><?= $i ?> Stars</option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" id="isActiveCheck" class="form-check-input" <?= ($edit_testimonial['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActiveCheck">Is Active (Visible on site)</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><?= $edit_testimonial ? 'Save Changes' : 'Create Testimonial' ?></button>
                        <?php if ($edit_testimonial): ?>
                            <a href="testimonials.php" class="btn btn-link w-100 mt-2 text-center">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
