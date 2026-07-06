<?php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/auth.php';

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) die('Invalid CSRF');
    $tokenPost = $_POST['token'] ?? '';
    $newPass = $_POST['password'] ?? '';
    $tokenEsc = $mysqli->real_escape_string($tokenPost);
    $res = $mysqli->query("SELECT user_id, expires_at FROM password_resets WHERE token = '$tokenEsc' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        if (strtotime($row['expires_at']) >= time()) {
            $uid = (int)$row['user_id'];
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $hashEsc = $mysqli->real_escape_string($hash);
            $mysqli->query("UPDATE users SET password = '$hashEsc' WHERE id = $uid");
            $mysqli->query("DELETE FROM password_resets WHERE user_id = $uid");
            login_user_by_id($uid);
            header('Location: dashboard.php');
            exit;
        }
    }
    echo 'Invalid or expired token.';
    exit;
}

include __DIR__ . '/partials/header.php';
?>
<div class="card mt-4 p-4">
  <h4 class="mb-3">Set new password</h4>
  <form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES); ?>">
    <div class="mb-3">
      <label class="form-label">New password</label>
      <input name="password" type="password" class="form-control" required>
    </div>
    <button class="btn btn-primary">Reset password</button>
  </form>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
