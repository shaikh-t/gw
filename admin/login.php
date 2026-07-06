<?php
// login.php
require_once __DIR__ . '/../lib/csrf.php';
// echo password_hash('lefkedev',PASSWORD_DEFAULT);
// If already logged in, redirect
if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

include __DIR__ . '/../partials/header.php';
?>
<div class="container mt-5" style="max-width:400px;">
  <h3 class="mb-3">Login</h3>
  <?php if (!empty($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger">
      <?php foreach ($_SESSION['flash_errors'] as $e) echo htmlspecialchars($e, ENT_QUOTES) . '<br>'; ?>
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
<?php include __DIR__ . '/../partials/footer.php'; ?>
