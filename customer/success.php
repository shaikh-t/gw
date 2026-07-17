<?php
// customer/success.php
require_once __DIR__ . '/../lib/auth.php';
require_login();

$user = current_user();
$initials = '';
$words = explode(' ', $user['name'] ?? 'Customer');
foreach ($words as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
if (empty($initials)) $initials = 'CU';

include __DIR__ . '/../partials/frontend_header.php';
?>
<body class="bg-warm">
  <div class="container my-5 py-5 text-center" style="max-width: 600px;">
    <div class="card border-0 shadow-sm p-5 rounded-4 bg-white">
      <div class="text-success fs-1 mb-4">
        <i class="bi bi-check-circle-fill"></i>
      </div>
      <h1 class="font-serif fw-bold mb-3 text-gradient-blue">Payment Successful!</h1>
      <p class="text-secondary mb-4">
        Thank you for your payment. Your transaction has been completed, and your application status has been updated.
      </p>

      <div class="alert alert-light border text-start rounded-3 p-3 mb-4">
        <div class="small text-muted mb-1">Customer Account</div>
        <div class="fw-bold text-dark"><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</div>
      </div>

      <div class="d-grid gap-2">
        <a href="index.php" class="btn btn-gw-blue py-2.5 rounded-pill fw-bold">Go to Customer Dashboard</a>
        <a href="../index.php" class="btn btn-outline-secondary py-2.5 rounded-pill">Return to Home Page</a>
      </div>
    </div>
  </div>
</body>
<?php include __DIR__ . '/../partials/frontend_footer.php'; ?>