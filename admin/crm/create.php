<?php
// admin/crm/create.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.manage');
require_once __DIR__ . '/../../lib/role_helpers.php';

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';

echo '<main class="main-content p-4">';
?>

<div class="container mt-2">
  <div class="card shadow-sm p-4" style="max-width: 700px; margin: 0 auto;">
    <div class="mb-4">
      <h3 class="h4 mb-1 fw-bold text-dark"><i class="bi bi-person-plus-fill text-primary"></i> Add New Customer</h3>
      <p class="text-muted small">Create a standard customer profile with optional onboarding attributes.</p>
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

    <form method="post" action="store.php" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>

      <div class="row g-3">
        <!-- Name -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Full Name *</label>
          <input name="name" class="form-control" placeholder="John Doe" required>
        </div>

        <!-- Email -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Email Address *</label>
          <input name="email" type="email" class="form-control" placeholder="john.doe@example.com" required>
        </div>

        <!-- Password -->
        <div class="col-md-12">
          <label class="form-label fw-semibold">Password *</label>
          <input name="password" type="password" class="form-control" placeholder="••••••••" required>
          <div class="form-text small text-muted">Minimum 8 chars, mixed case, number, symbol</div>
        </div>

        <hr class="my-3">
        <h5 class="h6 fw-bold text-secondary">Customer Onboarding Information</h5>

        <!-- Nationality -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">Nationality</label>
          <input name="nationality" class="form-control" placeholder="e.g. Canadian">
        </div>

        <!-- Emirate -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">Target Emirate</label>
          <select name="emirate" class="form-select">
            <option value="">-- Select --</option>
            <option value="Dubai">Dubai</option>
            <option value="Abu Dhabi">Abu Dhabi</option>
            <option value="Sharjah">Sharjah</option>
            <option value="Ajman">Ajman</option>
            <option value="Umm Al Quwain">Umm Al Quwain</option>
            <option value="Ras Al Khaimah">Ras Al Khaimah</option>
            <option value="Fujairah">Fujairah</option>
          </select>
        </div>

        <!-- Goal -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">Onboarding Goal</label>
          <input name="goal" class="form-control" placeholder="e.g. Business Setup">
        </div>

        <!-- Avatar -->
        <div class="col-md-12">
          <label class="form-label fw-semibold">Avatar Image</label>
          <input name="avatar" type="file" accept="image/*" class="form-control">
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">Create Customer</button>
      </div>
    </form>
  </div>
</div>

</main>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
