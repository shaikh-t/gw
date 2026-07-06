<?php
// login.php
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/auth.php';

// If already logged in, redirect based on role
if (!empty($_SESSION['user'])) {
    require_once __DIR__ . '/lib/permissions.php';
    if (is_role('admin') || is_role('Super Admin')) {
        header('Location: ' . $domain . '/admin/dashboard.php');
    } else if (is_role('provider')) {
        header('Location: ' . $domain . '/vendor/index.php');
    } else {
        header('Location: ' . $domain . '/admin/dashboard.php');
    }
    exit;
}

include __DIR__ . '/partials/header.php';
?>
<div class="container mt-5" style="max-width:400px;">
  <h3 class="mb-3 text-center">Sign In</h3>
  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger">
      <?php
        if (is_array($_SESSION['flash_errors'])) {
            foreach ($_SESSION['flash_errors'] as $e) echo htmlspecialchars($e, ENT_QUOTES) . '<br>';
        } else {
            echo htmlspecialchars($_SESSION['flash_errors'], ENT_QUOTES);
        }
      ?>
    </div>
    <?php unset($_SESSION['flash_errors']); ?>
  <?php endif; ?>
  <form method="post" action="login_post.php">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100">Login</button>
  </form>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
