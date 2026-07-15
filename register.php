<?php
// register.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/uuid_helper.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// If already logged in, redirect
if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $password = $_POST['password'] ?? '';
    $goal = trim($_POST['goal'] ?? 'Just Exploring');
    $emirate = trim($_POST['emirate'] ?? 'Dubai');

    if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
        $error_message = 'Please fill all required fields.';
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
            $uuid = generate_uuid();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $full_phone = '+971 ' . $phone;

            $stmt_insert = $mysqli->prepare("INSERT INTO users (uuid, name, email, password, phone, nationality, goal, emirate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param('ssssssss', $uuid, $fullName, $email, $hash, $full_phone, $nationality, $goal, $emirate);

            if ($stmt_insert->execute()) {
                $user_id = $stmt_insert->insert_id;

                // Assign default role (Manager or client role)
                // In roles, role with id 1 is 'admin', role 2 is 'manager', etc. Let's assign default user role (or first user role)
                $mysqli->query("INSERT INTO user_roles (user_id, role_id) VALUES ($user_id, 3)"); // default to Viewer / Customer role

                // Notify Admin about new customer
                require_once __DIR__ . '/lib/notifications_helper.php';
                notify_admins('New Customer Registered', "Customer account was created for $fullName ($email).", 'admin/users/index.php');

                // Auto login
                $_SESSION['user'] = [
                    'id' => $user_id,
                    'uuid' => $uuid,
                    'name' => $fullName,
                    'email' => $email,
                    'avatar' => null
                ];

                $_SESSION['flash_success'] = 'Account created successfully! Welcome to GlobalWays.';

                if (!empty($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header('Location: ' . $redirect);
                    exit;
                }

                header('Location: index.php');
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
<html lang="en" data-base="">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — GlobalWays</title>
  <meta name="description" content="Create your free GlobalWays account to compare vendors and track UAE applications.">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/globalways.css" rel="stylesheet">
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
        <div class="col-lg-6 register-hero-panel d-none d-lg-flex align-items-center py-5 px-4 px-lg-5 order-lg-1">
          <div class="w-100 fade-in auth-panel-inner register-hero-inner">
            <div class="register-hero-brand">
              <span class="register-hero-brand-icon"><i class="bi bi-globe2"></i></span>
              <span class="register-hero-brand-name font-serif">GlobalWays</span>
            </div>
            <h2 class="register-hero-title font-serif">Join 50,000+ happy customers</h2>
            <p class="register-hero-sub">Create your free account in 2 minutes and get instant access to 500+ verified vendors for all UAE documentation needs.</p>
            <ul class="list-unstyled register-hero-features">
              <li><span class="register-hero-check"><i class="bi bi-check-lg"></i></span>Free to browse and compare vendors</li>
              <li><span class="register-hero-check"><i class="bi bi-check-lg"></i></span>No hidden fees — transparent pricing</li>
              <li><span class="register-hero-check"><i class="bi bi-check-lg"></i></span>Escrow-protected payments</li>
              <li><span class="register-hero-check"><i class="bi bi-check-lg"></i></span>Real-time tracking &amp; WhatsApp updates</li>
              <li><span class="register-hero-check"><i class="bi bi-check-lg"></i></span>Encrypted document vault included</li>
            </ul>
            <div class="register-hero-testimonial">
              <div class="register-hero-proof">
                <div class="register-hero-avatars">
                  <span style="background:#0C4AC7">AH</span>
                  <span style="background:#1A73E8">ST</span>
                  <span style="background:#3F83F4">PS</span>
                  <span style="background:#70A5F7">LW</span>
                </div>
                <div class="register-hero-stars">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                </div>
              </div>
              <p class="register-hero-quote">“Signed up in 2 minutes. Had my Golden Visa vendor booked the same day.” — Ahmed, Dubai</p>
            </div>
          </div>
        </div>

        <!-- Right: multi-step registration -->
        <div class="col-lg-6 auth-form-panel d-flex align-items-center py-5 px-4 px-lg-5 order-lg-2">
          <div class="w-100 fade-in auth-form-inner register-form-wrap">

            <!-- Form -->
            <form id="registerForm" method="post" action="register.php" novalidate>
              <?= csrf_field(); ?>

              <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger mb-4"><?= htmlspecialchars($error_message) ?></div>
              <?php endif; ?>

              <!-- Step 1: account details -->
              <div id="regStep1" class="reg-step">
                <div class="register-form-brand">
                  <span class="register-form-brand-icon"><i class="bi bi-globe2"></i></span>
                  <span class="font-serif">GlobalWays</span>
                </div>
                <h1 class="register-form-title font-serif">Create your <span class="text-gradient-blue">account</span></h1>
                <p class="register-form-sub">Free forever. No credit card required.</p>

                <div class="row g-3 mb-3">
                  <div class="col-sm-6">
                    <label for="firstName" class="auth-form-label">First name *</label>
                    <input type="text" class="form-control auth-input" id="firstName" name="firstName" placeholder="Ahmed" required>
                  </div>
                  <div class="col-sm-6">
                    <label for="lastName" class="auth-form-label">Last name *</label>
                    <input type="text" class="form-control auth-input" id="lastName" name="lastName" placeholder="Hassan" required>
                  </div>
                </div>
                <div class="mb-3">
                  <label for="regEmail" class="auth-form-label">Email address *</label>
                  <input type="email" class="form-control auth-input" id="regEmail" name="email" placeholder="you@example.com" required>
                </div>
                <div class="mb-3">
                  <label for="phone" class="auth-form-label">Phone number *</label>
                  <div class="input-group phone-input-group">
                    <span class="input-group-text">🇦🇪 +971</span>
                    <input type="tel" class="form-control auth-input" id="phone" name="phone" placeholder="50 000 0000" required>
                  </div>
                </div>
                <div class="mb-3">
                  <label for="nationality" class="auth-form-label">Nationality *</label>
                  <select class="form-select auth-input" id="nationality" name="nationality" required>
                    <option value="" disabled>Select nationality…</option>
                    <option>United Arab Emirates</option>
                    <option selected>Filipino</option>
                    <option>Indian</option>
                    <option>Pakistani</option>
                    <option>British</option>
                    <option>American</option>
                    <option>Other</option>
                  </select>
                </div>
                <div class="mb-4">
                  <label for="regPassword" class="auth-form-label">Password *</label>
                  <div class="input-group auth-input-group">
                    <input type="password" class="form-control auth-input border-end-0" id="regPassword" name="password" placeholder="Min 8 characters" minlength="8" required>
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="toggleRegPassword" aria-label="Toggle password">
                      <i class="bi bi-eye" id="toggleRegPasswordIcon"></i>
                    </button>
                  </div>
                  <div class="password-strength" aria-hidden="true">
                    <span class="pw-seg"></span>
                    <span class="pw-seg"></span>
                    <span class="pw-seg"></span>
                    <span class="pw-seg"></span>
                  </div>
                </div>
                <button type="button" id="btnToStep2" class="btn btn-gw-dark w-100 py-3 mb-3 register-continue-btn">Continue →</button>
                <p class="small text-secondary text-center mb-2">By signing up you agree to our <a href="#" class="text-dark">Terms of Service</a> and <a href="#" class="text-dark">Privacy Policy</a></p>
                <p class="small text-secondary text-center mb-0">Already have an account? <a href="login.php" class="text-dark fw-medium">Sign in</a></p>
              </div>

              <!-- Step 2: goals -->
              <div id="regStep2" class="reg-step d-none">
                <div class="register-form-brand register-form-brand-center">
                  <span class="register-form-brand-icon"><i class="bi bi-globe2"></i></span>
                  <span class="font-serif">GlobalWays</span>
                </div>
                <h1 class="register-form-title font-serif">What brings you to <span class="text-gradient-blue">UAE</span>?</h1>
                <p class="register-form-sub">We'll personalise your experience based on your goal.</p>

                <div class="goal-grid mb-4" role="group" aria-label="Select your goal">
                  <button type="button" class="goal-card active" data-goal="golden-visa">
                    <span class="goal-icon"><i class="bi bi-trophy"></i></span>
                    <span class="goal-label">Golden Visa</span>
                  </button>
                  <button type="button" class="goal-card" data-goal="business-setup">
                    <span class="goal-icon"><i class="bi bi-buildings"></i></span>
                    <span class="goal-label">Business Setup</span>
                  </button>
                  <button type="button" class="goal-card" data-goal="family-visa">
                    <span class="goal-icon"><i class="bi bi-people"></i></span>
                    <span class="goal-label">Family Visa</span>
                  </button>
                  <button type="button" class="goal-card" data-goal="work-permit">
                    <span class="goal-icon"><i class="bi bi-briefcase"></i></span>
                    <span class="goal-label">Work Permit</span>
                  </button>
                  <button type="button" class="goal-card" data-goal="emirates-id">
                    <span class="goal-icon"><i class="bi bi-person-vcard"></i></span>
                    <span class="goal-label">Emirates ID</span>
                  </button>
                  <button type="button" class="goal-card" data-goal="pro-services">
                    <span class="goal-icon"><i class="bi bi-clipboard-check"></i></span>
                    <span class="goal-label">PRO Services</span>
                  </button>
                  <button type="button" class="goal-card" data-goal="exploring">
                    <span class="goal-icon"><i class="bi bi-search"></i></span>
                    <span class="goal-label">Just Exploring</span>
                  </button>
                </div>
                <input type="hidden" name="goal" id="selectedGoal" value="Golden Visa">

                <div class="mb-4">
                  <label for="emirate" class="auth-form-label">Which emirate are you based in?</label>
                  <select class="form-select auth-input" id="emirate" name="emirate" required>
                    <option selected>Dubai</option>
                    <option>Abu Dhabi</option>
                    <option>Sharjah</option>
                    <option>Ajman</option>
                    <option>Ras Al Khaimah</option>
                    <option>Fujairah</option>
                    <option>Umm Al Quwain</option>
                  </select>
                </div>

                <button type="submit" class="btn btn-gw-blue w-100 py-3 mb-3 register-create-btn">
                  <i class="bi bi-check-lg me-1"></i> Create My Account
                </button>
                <p class="small text-secondary text-center mb-2">
                  <button type="button" class="btn btn-link p-0 text-secondary text-decoration-none" id="backToDetails">← Back to details</button>
                </p>
                <p class="small text-secondary text-center mb-0">Already have an account? <a href="login.php" class="text-dark fw-medium">Sign in</a></p>
              </div>

            </form>

          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/main.js"></script>
  <script>
    const step1 = document.getElementById('regStep1');
    const step2 = document.getElementById('regStep2');
    const password = document.getElementById('regPassword');
    const strengthSegs = document.querySelectorAll('.password-strength .pw-seg');

    document.getElementById('toggleRegPassword')?.addEventListener('click', function () {
      const i = document.getElementById('toggleRegPasswordIcon');
      const hidden = password.type === 'password';
      password.type = hidden ? 'text' : 'password';
      i.classList.toggle('bi-eye', !hidden);
      i.classList.toggle('bi-eye-slash', hidden);
    });

    const scorePassword = (value) => {
      let score = 0;
      if (!value) return 0;
      if (value.length >= 8) score += 1;
      if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 1;
      if (/\d/.test(value)) score += 1;
      if (/[^A-Za-z0-9]/.test(value) || value.length >= 12) score += 1;
      return score;
    };

    password?.addEventListener('input', () => {
      const score = scorePassword(password.value);
      strengthSegs.forEach((seg, idx) => {
        seg.classList.toggle('on', idx < score);
      });
    });

    document.getElementById('btnToStep2')?.addEventListener('click', (e) => {
      // Validate Step 1 Inputs manually before transitioning
      const firstName = document.getElementById('firstName');
      const lastName = document.getElementById('lastName');
      const regEmail = document.getElementById('regEmail');
      const phone = document.getElementById('phone');

      if (!firstName.checkValidity() || !lastName.checkValidity() || !regEmail.checkValidity() || !phone.checkValidity() || !password.checkValidity()) {
        firstName.reportValidity();
        lastName.reportValidity();
        regEmail.reportValidity();
        phone.reportValidity();
        password.reportValidity();
        return;
      }
      step1.classList.add('d-none');
      step2.classList.remove('d-none');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    document.getElementById('backToDetails')?.addEventListener('click', () => {
      step2.classList.add('d-none');
      step1.classList.remove('d-none');
    });

    document.querySelectorAll('.goal-card').forEach((card) => {
      card.addEventListener('click', () => {
        document.querySelectorAll('.goal-card').forEach((c) => c.classList.remove('active'));
        card.classList.add('active');
        document.getElementById('selectedGoal').value = card.querySelector('.goal-label').textContent.trim();
      });
    });
  </script>
</body>
</html>
