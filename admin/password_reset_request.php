<?php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/partials/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) die('Invalid CSRF');
    $email = $mysqli->real_escape_string(trim($_POST['email'] ?? ''));
    $res = $mysqli->query("SELECT id FROM users WHERE email = '$email' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $userId = (int)$row['id'];
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $tokenEsc = $mysqli->real_escape_string($token);
        $mysqli->query("INSERT INTO password_resets (user_id, token, expires_at) VALUES ($userId, '$tokenEsc', '$expires')");
        // TODO: send email with reset link containing token
    }
    echo '<div class="alert alert-success">If that email exists, a reset link was sent.</div>';
}
?>
<div class="card mt-4 p-4">
  <h4 class="mb-3">Reset password</h4>
  <form method="post">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input name="email" type="email" class="form-control" required>
    </div>
    <button class="btn btn-primary">Send reset link</button>
  </form>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
