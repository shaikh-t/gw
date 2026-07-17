<?php
// admin/crm/edit.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.manage');

$uuid = isset($_GET['uuid']) ? trim($_GET['uuid']) : '';
$customer = null;

$stmt = $mysqli->prepare("SELECT * FROM users WHERE uuid = ? AND deleted_at IS NULL LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $uuid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $customer = $res->fetch_assoc();
    }
    $stmt->close();
}

if (!$customer) {
    $_SESSION['flash_errors'] = 'Customer profile not found.';
    header('Location: index.php');
    exit;
}

// SECURE REMEDIATION GATE: Check if target user has a Super Admin role
$target_user_id = (int)$customer['id'];
$stmt_check = $mysqli->prepare("
    SELECT r.name
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    WHERE ur.user_id = ? AND r.name = 'Super Admin'
");
if ($stmt_check) {
    $stmt_check->bind_param('i', $target_user_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check && $res_check->num_rows > 0) {
        // Only a logged-in Super Admin is authorized to edit another Super Admin
        if (!is_role('Super Admin')) {
            http_response_code(403);
            die("Security Escalation Blocked: Non-Super Admin cannot modify a Super Admin profile.");
        }
    }
    $stmt_check->close();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';

echo '<main class="main-content p-4">';
?>

<div class="container mt-2">
  <div class="card shadow-sm p-4" style="max-width: 700px; margin: 0 auto;">
    <div class="mb-4">
      <h3 class="h4 mb-1 fw-bold text-dark"><i class="bi bi-pencil-square text-secondary"></i> Edit Customer Profile</h3>
      <p class="text-muted small">Update information for customer: <strong><?php echo htmlspecialchars($customer['name'], ENT_QUOTES); ?></strong></p>
    </div>

    <?php if (!empty($_SESSION['flash_errors'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        <?php
          $errors = $_SESSION['flash_errors'];
          if (is_array($errors)) {
              foreach ($errors as $e) echo '<div>' . htmlspecialchars($e, ENT_QUOTES) . '</div>';
          } else {
              echo '<div>' . htmlspecialchars($errors, ENT_QUOTES) . '</div>';
          }
          unset($_SESSION['flash_errors']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <form method="post" action="update.php" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="uuid" value="<?php echo htmlspecialchars($customer['uuid'], ENT_QUOTES); ?>">

      <div class="row g-3">
        <!-- Name -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Full Name *</label>
          <input name="name" class="form-control" value="<?php echo htmlspecialchars($customer['name'], ENT_QUOTES); ?>" required>
        </div>

        <!-- Email -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Email Address *</label>
          <input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($customer['email'], ENT_QUOTES); ?>" required>
        </div>

        <!-- Password -->
        <div class="col-md-12">
          <label class="form-label fw-semibold">New Password</label>
          <input name="password" type="password" class="form-control" placeholder="Leave blank to keep existing password">
          <div class="form-text small text-muted">Minimum 8 chars, mixed case, number, symbol (if changing)</div>
        </div>

        <hr class="my-3">
        <h5 class="h6 fw-bold text-secondary">Customer Onboarding Information</h5>

        <!-- Nationality -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">Nationality</label>
          <input name="nationality" class="form-control" value="<?php echo htmlspecialchars($customer['nationality'] ?? '', ENT_QUOTES); ?>" placeholder="e.g. Canadian">
        </div>

        <!-- Emirate -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">Target Emirate</label>
          <select name="emirate" class="form-select">
            <?php
              $selected_emirate = $customer['emirate'] ?? '';
              $emirates = ["Dubai", "Abu Dhabi", "Sharjah", "Ajman", "Umm Al Quwain", "Ras Al Khaimah", "Fujairah"];
            ?>
            <option value="" <?php echo empty($selected_emirate) ? 'selected' : ''; ?>>-- Select --</option>
            <?php foreach ($emirates as $em): ?>
              <option value="<?php echo $em; ?>" <?php echo $selected_emirate === $em ? 'selected' : ''; ?>><?php echo $em; ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Goal -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">Onboarding Goal</label>
          <input name="goal" class="form-control" value="<?php echo htmlspecialchars($customer['goal'] ?? '', ENT_QUOTES); ?>" placeholder="e.g. Business Setup">
        </div>

        <!-- Avatar -->
        <div class="col-md-12">
          <label class="form-label fw-semibold">Avatar Image</label>
          <?php if (!empty($customer['avatar'])): ?>
            <div class="mb-2">
              <img src="<?php echo htmlspecialchars($customer['avatar'], ENT_QUOTES); ?>" class="rounded border p-1" style="height: 60px;">
              <span class="small text-muted ms-2">Current avatar</span>
            </div>
          <?php endif; ?>
          <input name="avatar" type="file" accept="image/*" class="form-control">
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
      </div>
    </form>
  </div>
</div>

</main>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
