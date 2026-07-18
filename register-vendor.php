<?php
// register-vendor.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/uuid_helper.php';
require_once __DIR__ . '/lib/providers_helpers.php';
require_once __DIR__ . '/lib/anti_spam_helper.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// If already logged in, redirect
if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Honeypot System check: silently exit on any payload
    if (!empty($_POST['website_url_verification'])) {
        exit;
    }

    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $companyName = trim($_POST['companyName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($firstName === '' || $lastName === '' || $companyName === '' || $email === '' || $password === '') {
        $error_message = 'Please fill all required fields.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
        $error_message = 'Password must be at least 8 characters long, and contain at least one uppercase letter, one number, and one special character.';
    } elseif (!check_rate_limit($mysqli)) {
        // 2. Form Submission Throttling (Rate Limiting)
        $error_message = 'Too many registration attempts from this connection. Please wait a few minutes before trying again.';
    } elseif (has_url_links($firstName) || has_url_links($lastName)) {
        // 3. String Input Sanity Filters: Name checks
        $error_message = 'Names cannot contain website links or URLs.';
    } elseif (is_disposable_email($email)) {
        // 3. String Input Sanity Filters: Email checks
        $error_message = 'Registration requires a valid, permanent email address.';
    } elseif (!verify_recaptcha($_POST['recaptcha_token'] ?? '', get_client_ip())) {
        // 4. Invisible Google reCAPTCHA v3 Integration
        $error_message = 'Security verification failed. Please refresh the page and try again.';
    } else {
        $fullName = $firstName . ' ' . $lastName;

        // Check unique email
        $stmt_check = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt_check->bind_param('s', $email);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $error_message = 'An account with this email address already exists.';
        } else {
            // Generate UUID
            $userUuid = generate_uuid();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $full_phone = '+971 ' . $phone;

            $stmt_insert = $mysqli->prepare("INSERT INTO users (uuid, name, email, password, phone, nationality, goal, emirate) VALUES (?, ?, ?, ?, ?, 'United Arab Emirates', 'Business Owner', 'Dubai')");
            $stmt_insert->bind_param('sssss', $userUuid, $fullName, $email, $hash, $full_phone);

            if ($stmt_insert->execute()) {
                $user_id = $stmt_insert->insert_id;

                // Assign provider role securely by name subquery
                $stmt_role = $mysqli->prepare("INSERT INTO user_roles (user_id, role_id) SELECT ?, id FROM roles WHERE name = 'provider' LIMIT 1");
                $stmt_role->bind_param('i', $user_id);
                $stmt_role->execute();
                $stmt_role->close();

                // Create provider profile
                $provUuid = generate_uuid();
                $slugBase = provider_slugify($companyName);
                $slug = $slugBase;
                $i = 1;
                while (true) {
                    $res = $mysqli->query("SELECT id FROM providers WHERE slug = '" . $mysqli->real_escape_string($slug) . "' LIMIT 1");
                    if ($res && $res->num_rows === 0) { if ($res) $res->free(); break; }
                    if ($res) $res->free();
                    $slug = $slugBase . '-' . $i++;
                }

                $stmt_prov = $mysqli->prepare("INSERT INTO providers (uuid, owner_user_id, name, slug, email, phone, city, country, status, verification_status) VALUES (?, ?, ?, ?, ?, ?, 'Dubai', 'United Arab Emirates', 'draft', 'unverified')");
                $stmt_prov->bind_param('sissss', $provUuid, $user_id, $companyName, $slug, $email, $full_phone);
                $stmt_prov->execute();
                $stmt_prov->close();

                // Notify Admin about new vendor registration
                require_once __DIR__ . '/lib/notifications_helper.php';
                notify_admins('New Vendor Registered', "Vendor account was created for $companyName (Contact: $fullName, $email).", 'admin/providers/index.php');

                // Auto login
                $_SESSION['user'] = [
                    'id' => $user_id,
                    'uuid' => $userUuid,
                    'name' => $fullName,
                    'email' => $email,
                    'avatar' => null
                ];

                $_SESSION['flash_success'] = 'Vendor account created successfully! Welcome to your Vendor Dashboard.';

                if (!empty($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header('Location: ' . $redirect);
                    exit;
                }

                header('Location: vendor/index.php');
                exit;
            } else {
                $error_message = 'Failed to create account: ' . $mysqli->error;
            }
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Partner Registration — GlobalWays</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/globalways.css" rel="stylesheet">
  <!-- Google reCAPTCHA v3 Integration -->
  <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>"></script>
</head>
<body class="register-page">

  <nav class="navbar navbar-expand-lg gw-navbar scrolled fixed-top" id="gwNav">
  <div class="container-xl">
    <a class="navbar-brand py-0 d-flex align-items-center" href="index.php">
      <img src="assets/logo.png" alt="globalways" class="gw-logo gw-logo-default">
      <img src="assets/logo-white.png" alt="globalways" class="gw-logo gw-logo-on-dark">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-1">
        <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="vendors.php">Vendors</a></li>
        <li class="nav-item"><a class="nav-link" href="pricing.php">Pricing</a></li>
        <li class="nav-item"><a class="nav-link" href="how-it-works.php">How It Works</a></li>
        <li class="nav-item"><a class="nav-link" href="blog.php">Insights</a></li>
        <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2 navbar-actions">
        <a href="login.php" class="btn btn-signin rounded-pill px-4">Sign In</a>
        <a href="register.php" class="btn btn-gw-blue px-4">Get Started Free</a>
      </div>
    </div>
  </div>
</nav>

  <main>
    <div class="container-fluid g-0">
      <div class="row g-0 auth-split-row">
        <!-- Left: blue marketing panel -->
        <div class="col-lg-6 register-hero-panel d-none d-lg-flex align-items-center py-5 px-4 px-lg-5 order-lg-1" style="background: linear-gradient(135deg, #0f172a, #1e293b);">
          <div class="w-100 fade-in auth-panel-inner register-hero-inner">
            <div class="register-hero-brand">
              <span class="register-hero-brand-icon" style="background: #1165EF;"><i class="bi bi-globe2"></i></span>
              <span class="register-hero-brand-name font-serif text-white">GlobalWays Partners</span>
            </div>
            <h2 class="register-hero-title font-serif text-white">Grow your service business with UAE's #1 Marketplace</h2>
            <p class="register-hero-sub text-white-50">Join hundreds of verified partner agencies, documentation firms, and legal consultants who receive high-quality leads daily.</p>
            <ul class="list-unstyled register-hero-features text-white-50">
              <li><span class="register-hero-check" style="background: rgba(17,101,239,0.2); color: #70A5F7;"><i class="bi bi-check-lg"></i></span>Free to list and showcase your company</li>
              <li><span class="register-hero-check" style="background: rgba(17,101,239,0.2); color: #70A5F7;"><i class="bi bi-check-lg"></i></span>Direct client in-app chat & messaging</li>
              <li><span class="register-hero-check" style="background: rgba(17,101,239,0.2); color: #70A5F7;"><i class="bi bi-check-lg"></i></span>Escrow-secured billing and payouts</li>
              <li><span class="register-hero-check" style="background: rgba(17,101,239,0.2); color: #70A5F7;"><i class="bi bi-check-lg"></i></span>Advanced lead management CRM</li>
              <li><span class="register-hero-check" style="background: rgba(17,101,239,0.2); color: #70A5F7;"><i class="bi bi-check-lg"></i></span>Dynamic team & certification display</li>
            </ul>
            <div class="register-hero-testimonial mt-4 p-3 rounded-3" style="background: rgba(255,255,255,0.05);">
              <p class="register-hero-quote text-white-50 mb-0">“Listing our PRO services on GlobalWays doubled our monthly client bookings within 30 days.” — Al Maha Consultants, Dubai</p>
            </div>
          </div>
        </div>

        <!-- Right: registration form -->
        <div class="col-lg-6 auth-form-panel d-flex align-items-center py-5 px-4 px-lg-5 order-lg-2">
          <div class="w-100 fade-in auth-form-inner register-form-wrap">

            <form id="registerVendorForm" method="post" action="register-vendor.php" novalidate>
              <?= csrf_field(); ?>

              <div class="register-form-brand">
                <span class="register-form-brand-icon" style="background: #1165EF;"><i class="bi bi-globe2"></i></span>
                <span class="font-serif">GlobalWays Partners</span>
              </div>
              <h1 class="register-form-title font-serif">Register as a <span class="text-gradient-blue">Partner</span></h1>
              <p class="register-form-sub">Create your vendor portal account. Fast approval within 24 hours.</p>

              <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger mb-4"><?= htmlspecialchars($error_message) ?></div>
              <?php endif; ?>

              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label for="firstName" class="auth-form-label">Contact First name *</label>
                  <input type="text" class="form-control auth-input" id="firstName" name="firstName" placeholder="Ahmed" required>
                </div>
                <div class="col-sm-6">
                  <label for="lastName" class="auth-form-label">Contact Last name *</label>
                  <input type="text" class="form-control auth-input" id="lastName" name="lastName" placeholder="Hassan" required>
                </div>
              </div>

              <div class="mb-3">
                <label for="companyName" class="auth-form-label">Company Name *</label>
                <input type="text" class="form-control auth-input" id="companyName" name="companyName" placeholder="e.g. Al Maha Corporate Services" required>
              </div>

              <div class="mb-3">
                <label for="regEmail" class="auth-form-label">Business Email address *</label>
                <input type="email" class="form-control auth-input" id="regEmail" name="email" placeholder="partner@yourcompany.com" required>
              </div>

              <div class="mb-3">
                <label for="phone" class="auth-form-label">Business Phone number *</label>
                <div class="input-group phone-input-group">
                  <span class="input-group-text">🇦🇪 +971</span>
                  <input type="tel" class="form-control auth-input" id="phone" name="phone" placeholder="50 000 0000" required>
                </div>
              </div>

              <!-- Honeypot System Field (Invisible bot protection) -->
              <div style="display: none;">
                <label for="website_url_verification">Leave this field blank</label>
                <input type="text" id="website_url_verification" name="website_url_verification" autocomplete="off" tabindex="-1">
              </div>

              <div class="mb-4">
                <label for="regPassword" class="auth-form-label">Password *</label>
                <div class="input-group auth-input-group mb-1">
                  <input type="password" class="form-control auth-input" id="regPassword" name="password" placeholder="Min 8 characters" minlength="8" required>
                </div>
                <!-- Visual password strength meter -->
                <div class="password-strength mt-2" style="display: flex; gap: 4px; height: 6px;">
                  <span class="pw-seg flex-grow-1" style="background-color: #e5e7eb; border-radius: 3px; height: 100%;"></span>
                  <span class="pw-seg flex-grow-1" style="background-color: #e5e7eb; border-radius: 3px; height: 100%;"></span>
                  <span class="pw-seg flex-grow-1" style="background-color: #e5e7eb; border-radius: 3px; height: 100%;"></span>
                  <span class="pw-seg flex-grow-1" style="background-color: #e5e7eb; border-radius: 3px; height: 100%;"></span>
                </div>
                <div id="pw-hint" class="small text-muted mt-1" style="font-size: 0.75rem;">Password must contain at least 8 characters, an uppercase letter, a number, and a special character.</div>
              </div>

              <button type="submit" class="btn btn-primary w-100 py-3 mb-3" style="border-radius: 50px; background: #1165EF; border: none; font-weight: 600;">
                <i class="bi bi-briefcase me-1"></i> Register as a Partner
              </button>

              <p class="small text-secondary text-center mb-0">Looking for a customer account instead? <a href="register.php" class="text-dark fw-medium">Register here</a></p>
            </form>

          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Asynchronous email check
      const emailInput = document.getElementById('regEmail');
      if (emailInput) {
        emailInput.addEventListener('blur', function() {
          const email = this.value.trim();
          if (email === '') return;
          fetch('api/check-email.php?email=' + encodeURIComponent(email))
            .then(res => res.json())
            .then(data => {
              if (!data.available) {
                emailInput.setCustomValidity('This email address is already registered.');
                emailInput.classList.add('is-invalid');
                alert('Notice: An account with this email address already exists.');
              } else {
                emailInput.setCustomValidity('');
                emailInput.classList.remove('is-invalid');
              }
            })
            .catch(err => console.error('Email verification error:', err));
        });
      }

      // Password strength meter animation
      const password = document.getElementById('regPassword');
      const strengthSegs = document.querySelectorAll('.password-strength .pw-seg');

      const scorePassword = (value) => {
        let score = 0;
        if (!value) return 0;
        if (value.length >= 8) score += 1;
        if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 1;
        if (/\d/.test(value)) score += 1;
        if (/[^A-Za-z0-9]/.test(value)) score += 1;
        return score;
      };

      if (password) {
        password.addEventListener('input', () => {
          const score = scorePassword(password.value);
          const colors = ['#ef4444', '#f59e0b', '#3b82f6', '#10b981'];
          strengthSegs.forEach((seg, idx) => {
            if (idx < score) {
              seg.style.backgroundColor = colors[score - 1];
            } else {
              seg.style.backgroundColor = '#e5e7eb';
            }
          });
        });
      }

      // Invisible Google reCAPTCHA v3 Token Request & Form Injection
      const registerVendorFormElement = document.getElementById('registerVendorForm');
      if (registerVendorFormElement) {
        registerVendorFormElement.addEventListener('submit', function(e) {
          e.preventDefault();
          grecaptcha.ready(function() {
            grecaptcha.execute('<?= RECAPTCHA_SITE_KEY ?>', {action: 'register_vendor'}).then(function(token) {
              let tokenInput = document.getElementById('recaptcha_token');
              if (!tokenInput) {
                tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = 'recaptcha_token';
                tokenInput.id = 'recaptcha_token';
                registerVendorFormElement.appendChild(tokenInput);
              }
              tokenInput.value = token;
              registerVendorFormElement.submit();
            });
          });
        });
      }
    });
  </script>
</body>
</html>
