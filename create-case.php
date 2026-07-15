<?php
// create-case.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/uuid_helper.php';
require_once __DIR__ . '/lib/providers_helpers.php';
require_once __DIR__ . '/lib/notifications_helper.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Ensure the user is logged in
if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$current_user = current_user();

$vendor_id_val = $_GET['vendor_id'] ?? '';
$provider = null;

if ($vendor_id_val !== '') {
    $provider = provider_find($vendor_id_val);
}

// If vendor is not found, show error or redirect
if (!$provider) {
    http_response_code(404);
    echo "<h3>Vendor not found</h3><p><a href='vendors.php'>Go back to Vendors</a></p>";
    exit;
}

$provider_id = (int)$provider['id'];

// Fetch services offered by this vendor
$services_sql = "SELECT s.*, c.name as category_name
                 FROM services s
                 LEFT JOIN service_categories c ON c.id = s.category_id
                 WHERE s.provider_id = ? AND s.status = 'published'
                 ORDER BY s.title ASC";
$stmt = $mysqli->prepare($services_sql);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$res = $stmt->get_result();
$services = [];
while ($row = $res->fetch_assoc()) {
    $services[] = $row;
}
$stmt->close();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $service_uuid = trim($_POST['service_id'] ?? '');
    $customer_message = trim($_POST['customer_message'] ?? '');

    // Validate service belongs to this vendor
    $service = null;
    if ($service_uuid !== '') {
        $stmt_s = $mysqli->prepare("SELECT * FROM services WHERE uuid = ? AND provider_id = ? AND status = 'published' LIMIT 1");
        $stmt_s->bind_param('si', $service_uuid, $provider_id);
        $stmt_s->execute();
        $res_s = $stmt_s->get_result();
        if ($row_s = $res_s->fetch_assoc()) {
            $service = $row_s;
        }
        $stmt_s->close();
    }

    if (!$service) {
        $error_message = 'Please select a valid service offered by this vendor.';
    } elseif ($customer_message === '') {
        $error_message = 'Please write a message describing your requirements.';
    } else {
        $case_uuid = generate_uuid();
        $customer_user_id = (int)$current_user['id'];
        $service_id = (int)$service['id'];
        $initial_status = 'Pending';

        $stmt_ins = $mysqli->prepare("INSERT INTO `cases` (`uuid`, `customer_user_id`, `provider_id`, `service_id`, `status`, `customer_message`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_ins->bind_param('siiiss', $case_uuid, $customer_user_id, $provider_id, $service_id, $initial_status, $customer_message);

        if ($stmt_ins->execute()) {
            // Trigger target notification workflows
            $service_title = $service['title'];
            $customer_name = $current_user['name'];

            // A. Trigger Vendor notification
            $vendor_notif_title = "New Case Request";
            $vendor_notif_msg = "Customer $customer_name has requested a quote for the service '$service_title'. Details: $customer_message";
            // Storing the specific quote request page path in target_url
            $vendor_target_url = "vendor/quote-requests.php";
            notify_vendor($provider_id, $vendor_notif_title, $vendor_notif_msg, $vendor_target_url);

            // B. Trigger Admin notification
            $admin_notif_title = "New Case Requested";
            $admin_notif_msg = "Case requested for Vendor '{$provider['name']}' from customer '$customer_name' for service '$service_title'.";
            $admin_target_url = "admin/dashboard.php"; // Or a dedicated admin overview
            notify_admins($admin_notif_title, $admin_notif_msg, $admin_target_url);

            // Set success flash and redirect or show success message
            $_SESSION['flash_success'] = "Your quote request for '$service_title' has been submitted to {$provider['name']}. They will review and reply shortly!";
            header("Location: customer/index.php");
            exit;
        } else {
            $error_message = 'Failed to submit quote request. Please try again: ' . $mysqli->error;
        }
    }
}

// Breadcrumb & Header Setup
$city = !empty($provider['city']) ? $provider['city'] : 'Dubai';
$rating_avg = !empty($provider['rating_avg']) ? round($provider['rating_avg'], 1) : '4.8';

include __DIR__ . '/partials/frontend_header.php';
?>

<main class="py-5" style="margin-top: 5rem;">
  <div class="container-xl py-4">
    <div class="row g-4 justify-content-center">
      <div class="col-lg-8">
        <!-- Back navigation -->
        <div class="mb-4">
          <a href="vendor-profile.php?id=<?= htmlspecialchars($provider['slug']) ?>" class="text-decoration-none text-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to <?= htmlspecialchars($provider['name']) ?> Profile
          </a>
        </div>

        <?php if ($error_message !== ''): ?>
          <div class="alert alert-danger mb-4"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
          <!-- Vendor context header -->
          <div class="p-4 bg-dark text-white d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <?php if (!empty($provider['logo'])): ?>
                <img src="<?= htmlspecialchars($domain.$provider['logo']) ?>" style="width:60px;height:60px;object-fit:contain;border-radius:50%;border:2px solid rgba(255,255,255,0.2);">
              <?php else: ?>
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center font-serif" style="width:60px;height:60px;font-size:1.5rem;font-weight:bold;">
                  <?= strtoupper(substr($provider['name'], 0, 2)) ?>
                </div>
              <?php endif; ?>
              <div>
                <h4 class="font-serif mb-1"><?= htmlspecialchars($provider['name']) ?></h4>
                <div class="small text-white-50 d-flex align-items-center gap-2">
                  <span><i class="bi bi-star-fill text-warning me-1"></i> <?= htmlspecialchars($rating_avg) ?></span>
                  <span>·</span>
                  <span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($city) ?>, UAE</span>
                </div>
              </div>
            </div>
            <?php if ($provider['verification_status'] === 'verified'): ?>
              <span class="badge bg-primary text-white px-3 py-2 rounded-pill"><i class="bi bi-patch-check-fill me-1"></i> Verified Partner</span>
            <?php endif; ?>
          </div>

          <!-- Form body -->
          <div class="card-body p-4 p-lg-5">
            <h2 class="font-serif mb-2 h3">Request a Quote</h2>
            <p class="text-secondary mb-4">Select the service you need and provide a brief description of your requirements. The vendor will reply with a detailed price quote and schedule.</p>

            <form action="create-case.php?vendor_id=<?= htmlspecialchars($provider['uuid']) ?>" method="post">
              <?= csrf_field(); ?>

              <!-- Service dropdown options -->
              <div class="mb-4">
                <label for="service_id" class="form-label fw-semibold">Select Service *</label>
                <select class="form-select form-select-lg" name="service_id" id="service_id" required>
                  <option value="" disabled selected>Select service offered by vendor…</option>
                  <?php foreach ($services as $svc):
                      $price_text = !empty($svc['price']) ? ' - ' . $svc['currency'] . ' ' . number_format($svc['price']) : '';
                  ?>
                    <option value="<?= htmlspecialchars($svc['uuid']) ?>">
                      <?= htmlspecialchars($svc['title']) ?><?= $price_text ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (empty($services)): ?>
                  <div class="form-text text-danger mt-1">This vendor has no published services available. You cannot submit a request.</div>
                <?php endif; ?>
              </div>

              <!-- Message -->
              <div class="mb-4">
                <label for="customer_message" class="form-label fw-semibold">Your Requirements & Details *</label>
                <textarea class="form-control" name="customer_message" id="customer_message" rows="6" placeholder="Please describe what you need, your timeline, nationality details, or any documents you currently have. The more details you share, the faster the vendor can reply with an accurate quote." required></textarea>
                <div class="form-text">All information is securely shared with this vendor only.</div>
              </div>

              <!-- Submit -->
              <button type="submit" class="btn btn-gw-dark btn-lg w-100 py-3" <?= empty($services) ? 'disabled' : '' ?>>
                <i class="bi bi-send me-1"></i> Submit Quote Request
              </button>
            </form>
          </div>
        </div>

        <div class="mt-4 p-3 rounded-3 bg-light text-secondary small d-flex gap-2">
          <i class="bi bi-shield-lock-fill fs-5 text-primary"></i>
          <div>
            <strong>Escrow Protected Marketplace:</strong> Your payments are always held securely in our protected escrow system. The vendor is only paid after you confirm successful completion of your requested documentation.
          </div>
        </div>

      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
