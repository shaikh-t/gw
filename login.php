<?php
// login.php
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/uuid_helper.php';

// Intercept Demo Login
if (isset($_GET['action']) && $_GET['action'] === 'demo_login') {
    $demo_email = 'demo.customer@globalways.ae';
    $stmt = $mysqli->prepare("SELECT id, uuid, name, email FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $demo_email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user_row = $res->fetch_assoc();
    $stmt->close();

    if (!$user_row) {
        // Create demo user
        $uuid = generate_uuid();
        $name = 'Ahmed Hassan';
        $dummy_pass = password_hash('democustomer77', PASSWORD_BCRYPT);
        $phone = '+971 50 123 4567';
        $nationality = 'United Arab Emirates';
        $goal = 'Golden Visa';
        $emirate = 'Dubai';

        $stmt_ins = $mysqli->prepare("INSERT INTO users (uuid, name, email, password, phone, nationality, goal, emirate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_ins->bind_param('ssssssss', $uuid, $name, $demo_email, $dummy_pass, $phone, $nationality, $goal, $emirate);
        $stmt_ins->execute();
        $user_id = $stmt_ins->insert_id;
        $stmt_ins->close();

        // Assign viewer role (3)
        $mysqli->query("INSERT INTO user_roles (user_id, role_id) VALUES ($user_id, 3)");

        $user_row = [
            'id' => $user_id,
            'uuid' => $uuid,
            'name' => $name,
            'email' => $demo_email
        ];
    }

    // Log them in
    $_SESSION['user'] = [
        'id' => (int)$user_row['id'],
        'uuid' => $user_row['uuid'],
        'name' => $user_row['name'],
        'email' => $user_row['email'],
        'avatar' => null
    ];
    session_regenerate_id(true);

    // Seed customer records
    require_once __DIR__ . '/lib/customer_helpers.php';
    ensure_customer_seeded((int)$user_row['id']);

    header('Location: ' . $domain . '/customer/index.php');
    exit;
}

// If already logged in, redirect based on role
if (!empty($_SESSION['user'])) {
    require_once __DIR__ . '/lib/permissions.php';
    if (is_role('admin') || is_role('Super Admin')) {
        header('Location: ' . $domain . '/admin/dashboard.php');
    } else if (is_role('provider')) {
        header('Location: ' . $domain . '/vendor/index.php');
    } else {
        header('Location: ' . $domain . '/customer/index.php');
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="en" data-base="">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — GlobalWays</title>
  <meta name="description" content="Sign in to your GlobalWays customer, vendor, or admin dashboard.">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/globalways.css" rel="stylesheet">
</head>
<body class="login-page">

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
    <!-- Split login -->
    <div class="container-fluid g-0">
      <div class="row g-0 auth-split-row">
        <!-- Left: form -->
        <div class="col-lg-6 auth-form-panel d-flex align-items-center py-5 px-4 px-lg-5">
          <div class="w-100 fade-in auth-form-inner">
            <h1 class="font-serif mb-2" style="font-size:clamp(2rem,4vw,2.75rem)"><span class="text-gradient-blue">Welcome</span> back,</h1>
            <p class="text-secondary mb-4">Sign in to your account to continue.</p>

            <?php if (!empty($_SESSION['flash_errors'])): ?>
              <div class="alert alert-danger mb-4">
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

            <form id="loginForm" method="post" action="login_post.php">
              <?= csrf_field(); ?>
              <div class="mb-3">
                <label for="email" class="auth-form-label">Email address</label>
                <input type="email" class="form-control auth-input" id="email" name="email" placeholder="you@example.com" required>
              </div>
              <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label for="password" class="auth-form-label mb-0">Password</label>
                  <a href="#" class="small text-secondary text-decoration-none">Forgot password?</a>
                </div>
                <div class="input-group auth-input-group">
                  <input type="password" class="form-control auth-input border-end-0" id="password" name="password" placeholder="••••••••" required>
                  <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword" aria-label="Toggle password visibility">
                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                  </button>
                </div>
              </div>
              <button type="submit" class="btn btn-gw-dark w-100 mb-4 py-3">Sign In <i class="bi bi-chevron-right ms-1"></i></button>

              <div class="auth-divider mb-4"><span>Or continue with</span></div>

              <div class="row g-2 mb-4">
                <div class="col-6">
                  <button type="button" class="btn btn-auth-social w-100"><i class="bi bi-google me-2"></i>Google</button>
                </div>
                <div class="col-6">
                  <button type="button" class="btn btn-auth-social w-100"><i class="bi bi-apple me-2"></i>Apple</button>
                </div>
              </div>

              <p class="small text-secondary mb-2">Don't have an account? <a href="register.php" class="text-dark fw-medium">Create account</a></p>
              <p class="small text-secondary mb-0">Are you a vendor? <a href="vendor-onboard.php" class="text-dark fw-medium">Apply here</a></p>
            </form>
          </div>
        </div>

        <!-- Right: blue panel -->
        <div class="col-lg-6 login-hero-panel d-none d-lg-flex align-items-center py-5 px-4 px-lg-5">
          <div class="w-100 fade-in auth-panel-inner login-hero-inner">
            <div class="login-hero-brand">
              <span class="login-hero-brand-icon"><i class="bi bi-globe2"></i></span>
              <span class="login-hero-brand-name font-serif">globalways.</span>
            </div>
            <h2 class="login-hero-title font-serif">Your UAE journey in one dashboard</h2>
            <p class="login-hero-sub">Track applications, store documents securely, communicate with vendors, and manage payments — all in one place.</p>
            <ul class="list-unstyled login-hero-features">
              <li>
                <span class="login-hero-feature-icon"><i class="bi bi-shield-check"></i></span>
                <span>All transactions protected by escrow</span>
              </li>
              <li>
                <span class="login-hero-feature-icon"><i class="bi bi-check-circle"></i></span>
                <span>Real-time tracking at every stage</span>
              </li>
              <li>
                <span class="login-hero-feature-icon"><i class="bi bi-lock"></i></span>
                <span>Bank-grade document encryption</span>
              </li>
            </ul>
            <div class="login-hero-badge">
              <div class="login-hero-avatars">
                <span style="background:#0C4AC7">AH</span>
                <span style="background:#1A73E8">ST</span>
                <span style="background:#70A5F7">PS</span>
              </div>
              <span class="login-hero-badge-text">50,000+ happy customers worldwide</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Track Every Step -->
    <section class="py-5 bg-white">
      <div class="container-xl py-4">
        <div class="row g-5 align-items-center">
          <div class="col-lg-5 fade-in">
            <p class="label-mono text-primary mb-3">Live Tracking</p>
            <h2 class="font-serif mb-4" style="font-size:clamp(1.9rem,3.5vw,2.75rem);line-height:1.1">
              <span class="text-gradient-blue">Track</span> Every Step<br>Like Your Package
            </h2>
            <p class="text-secondary mb-4">FedEx-style tracking gives you complete visibility into your application. No more chasing your agent or wondering what's happening.</p>
            <ul class="list-unstyled auth-feature-list mb-4">
              <li><i class="bi bi-check-circle-fill text-primary"></i>Instant WhatsApp &amp; email notifications</li>
              <li><i class="bi bi-check-circle-fill text-primary"></i>Milestone-by-milestone progress view</li>
              <li><i class="bi bi-check-circle-fill text-primary"></i>Estimated completion countdown</li>
              <li><i class="bi bi-check-circle-fill text-primary"></i>Document upload status tracking</li>
              <li><i class="bi bi-check-circle-fill text-primary"></i>Direct vendor messaging in-platform</li>
            </ul>
            <a href="login.php?action=demo_login" class="btn btn-gw-dark">See Demo Dashboard <i class="bi bi-arrow-right ms-1"></i></a>
          </div>
          <div class="col-lg-7 fade-in">
            <div class="tracking-mockup">
              <div class="tracking-mockup-chrome">
                <span class="chrome-dot bg-danger"></span>
                <span class="chrome-dot bg-warning"></span>
                <span class="chrome-dot bg-success"></span>
                <span class="font-mono text-muted flex-grow-1 text-center" style="font-size:0.65rem">app.globalways.ae/track</span>
              </div>
              <div class="tracking-mockup-body">
                <p class="font-mono text-muted mb-1" style="font-size:0.6rem;letter-spacing:0.12em">GOLDEN VISA APPLICATION</p>
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <p class="small fw-medium font-serif mb-0">Ahmed Al-Rashidi · #GV-2026-4821</p>
                  <span class="badge rounded-pill bg-mint text-primary font-mono" style="font-size:0.6rem">67% Done</span>
                </div>
                <div class="progress mb-4 rounded-pill" style="height:6px">
                  <div class="progress-bar bg-primary rounded-pill" style="width:67%"></div>
                </div>
                <div class="tracking-timeline">
                  <div class="tracking-step done">
                    <span class="tracking-step-icon"><i class="bi bi-check-lg"></i></span>
                    <div class="flex-grow-1">
                      <div class="small fw-medium">Application Submitted</div>
                      <div class="font-mono text-muted" style="font-size:0.65rem">Jun 1 · 9:00 AM</div>
                    </div>
                  </div>
                  <div class="tracking-step done">
                    <span class="tracking-step-icon"><i class="bi bi-check-lg"></i></span>
                    <div class="flex-grow-1">
                      <div class="small fw-medium">Documents Verified</div>
                      <div class="font-mono text-muted" style="font-size:0.65rem">Jun 1 · 2:30 PM</div>
                    </div>
                  </div>
                  <div class="tracking-step done">
                    <span class="tracking-step-icon"><i class="bi bi-check-lg"></i></span>
                    <div class="flex-grow-1">
                      <div class="small fw-medium">Government Submission</div>
                      <div class="font-mono text-muted" style="font-size:0.65rem">Jun 2 · 10:15 AM</div>
                    </div>
                  </div>
                  <div class="tracking-step done">
                    <span class="tracking-step-icon"><i class="bi bi-check-lg"></i></span>
                    <div class="flex-grow-1">
                      <div class="small fw-medium">Biometrics Scheduled</div>
                      <div class="font-mono text-muted" style="font-size:0.65rem">Jun 3 · 9:00 AM</div>
                    </div>
                  </div>
                  <div class="tracking-step active">
                    <span class="tracking-step-icon pulse-dot-icon"></span>
                    <div class="flex-grow-1">
                      <div class="small fw-medium">Final Approval Pending</div>
                      <div class="font-mono text-muted" style="font-size:0.65rem">Estimated Jun 5</div>
                    </div>
                  </div>
                  <div class="tracking-step pending">
                    <span class="tracking-step-icon"></span>
                    <div class="flex-grow-1">
                      <div class="small fw-medium text-muted">Visa Stamped &amp; Ready</div>
                      <div class="font-mono text-muted" style="font-size:0.65rem">—</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/main.js"></script>
  <script>
    document.getElementById('togglePassword')?.addEventListener('click', function () {
      const p = document.getElementById('password');
      const i = document.getElementById('togglePasswordIcon');
      const hidden = p.type === 'password';
      p.type = hidden ? 'text' : 'password';
      i.classList.toggle('bi-eye', !hidden);
      i.classList.toggle('bi-eye-slash', hidden);
    });
  </script>
</body>
</html>
