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
    $phone_code = trim($_POST['phone_code'] ?? '');
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
            $full_phone = $phone_code.' ' . $phone;

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
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/globalways.css" rel="stylesheet">
  <!-- 1. Ensure the CDN library scripts are explicitly loaded first -->
<script src="js/slimselect.js"></script>
    <link href="css/slimselect.css" rel="stylesheet">
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
                    <!-- <span class="input-group-text">🇦🇪 +971</span> -->
                    <select id="phone-code" name="phone_code" class="input-group-dd" required>
                <option value="" disabled selected>Code</option>
            </select>
                    <input type="tel" class="form-control auth-input" id="phone" name="phone" placeholder="50 000 0000" required>
                  </div>
                </div>
                <div class="mb-3">
                  <label for="nationality" class="auth-form-label">Nationality *</label>
                  <select class="form-select auth-input input-group-dd" id="nationality" name="nationality" required>
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

document.addEventListener('DOMContentLoaded', () => {
    const countries = [
        {code:"AF", name:"Afghanistan", dial:"93"}, {code:"AX", name:"Aland Islands", dial:"358"}, {code:"AL", name:"Albania", dial:"355"}, {code:"DZ", name:"Algeria", dial:"213"}, {code:"AS", name:"American Samoa", dial:"1684"}, {code:"AD", name:"Andorra", dial:"376"}, {code:"AO", name:"Angola", dial:"244"}, {code:"AI", name:"Anguilla", dial:"1264"}, {code:"AQ", name:"Antarctica", dial:"672"}, {code:"AG", name:"Antigua & Barbuda", dial:"1268"}, {code:"AR", name:"Argentina", dial:"54"}, {code:"AM", name:"Armenia", dial:"374"}, {code:"AW", name:"Aruba", dial:"297"}, {code:"AU", name:"Australia", dial:"61"}, {code:"AT", name:"Austria", dial:"43"}, {code:"AZ", name:"Azerbaijan", dial:"994"},
        {code:"BS", name:"Bahamas", dial:"1242"}, {code:"BH", name:"Bahrain", dial:"973"}, {code:"BD", name:"Bangladesh", dial:"880"}, {code:"BB", name:"Barbados", dial:"1246"}, {code:"BY", name:"Belarus", dial:"375"}, {code:"BE", name:"Belgium", dial:"32"}, {code:"BZ", name:"Belize", dial:"501"}, {code:"BJ", name:"Benin", dial:"229"}, {code:"BM", name:"Bermuda", dial:"1441"}, {code:"BT", name:"Bhutan", dial:"975"}, {code:"BO", name:"Bolivia", dial:"591"}, {code:"BA", name:"Bosnia", dial:"387"}, {code:"BW", name:"Botswana", dial:"267"}, {code:"BR", name:"Brazil", dial:"55"}, {code:"BN", name:"Brunei", dial:"673"}, {code:"BG", name:"Bulgaria", dial:"359"}, {code:"BF", name:"Burkina Faso", dial:"226"}, {code:"BI", name:"Burundi", dial:"257"},
        {code:"KH", name:"Cambodia", dial:"855"}, {code:"CM", name:"Cameroon", dial:"237"}, {code:"CA", name:"Canada", dial:"1"}, {code:"CV", name:"Cape Verde", dial:"238"}, {code:"KY", name:"Cayman Islands", dial:"1345"}, {code:"CF", name:"Central African Rep.", dial:"236"}, {code:"TD", name:"Chad", dial:"235"}, {code:"CL", name:"Chile", dial:"56"}, {code:"CN", name:"China", dial:"86"}, {code:"CO", name:"Colombia", dial:"57"}, {code:"KM", name:"Comoros", dial:"269"}, {code:"CG", name:"Congo", dial:"242"}, {code:"CD", name:"DR Congo", dial:"243"}, {code:"CR", name:"Costa Rica", dial:"506"}, {code:"CI", name:"Cote d'Ivoire", dial:"225"}, {code:"HR", name:"Croatia", dial:"385"}, {code:"CU", name:"Cuba", dial:"53"}, {code:"CY", name:"Cyprus", dial:"357"}, {code:"CZ", name:"Czech Republic", dial:"420"},
        {code:"DK", name:"Denmark", dial:"45"}, {code:"DJ", name:"Djibouti", dial:"253"}, {code:"DM", name:"Dominica", dial:"1767"}, {code:"DO", name:"Dominican Rep.", dial:"1809"}, {code:"EC", name:"Ecuador", dial:"593"}, {code:"EG", name:"Egypt", dial:"20"}, {code:"SV", name:"El Salvador", dial:"503"}, {code:"GQ", name:"Equatorial Guinea", dial:"240"}, {code:"ER", name:"Eritrea", dial:"291"}, {code:"EE", name:"Estonia", dial:"372"}, {code:"ET", name:"Ethiopia", dial:"251"}, {code:"FJ", name:"Fiji", dial:"679"}, {code:"FI", name:"Finland", dial:"358"}, {code:"FR", name:"France", dial:"33"}, {code:"GA", name:"Gabon", dial:"241"}, {code:"GM", name:"Gambia", dial:"220"}, {code:"GE", name:"Georgia", dial:"995"}, {code:"DE", name:"Germany", dial:"49"}, {code:"GH", name:"Ghana", dial:"233"}, {code:"GR", name:"Greece", dial:"30"}, {code:"GT", name:"Guatemala", dial:"502"}, {code:"GN", name:"Guinea", dial:"224"}, {code:"GY", name:"Guyana", dial:"592"},
        {code:"HT", name:"Haiti", dial:"509"}, {code:"HN", name:"Honduras", dial:"504"}, {code:"HK", name:"Hong Kong", dial:"852"}, {code:"HU", name:"Hungary", dial:"36"}, {code:"IS", name:"Iceland", dial:"354"}, {code:"IN", name:"India", dial:"91"}, {code:"ID", name:"Indonesia", dial:"62"}, {code:"IR", name:"Iran", dial:"98"}, {code:"IQ", name:"Iraq", dial:"964"}, {code:"IE", name:"Ireland", dial:"353"}, {code:"IL", name:"Israel", dial:"972"}, {code:"IT", name:"Italy", dial:"39"}, {code:"JM", name:"Jamaica", dial:"1876"}, {code:"JP", name:"Japan", dial:"81"}, {code:"JO", name:"Jordan", dial:"962"}, {code:"KZ", name:"Kazakhstan", dial:"7"}, {code:"KE", name:"Kenya", dial:"254"}, {code:"KW", name:"Kuwait", dial:"965"}, {code:"KG", name:"Kyrgyzstan", dial:"996"},
        {code:"LA", name:"Laos", dial:"856"}, {code:"LV", name:"Latvia", dial:"371"}, {code:"LB", name:"Lebanon", dial:"961"}, {code:"LS", name:"Lesotho", dial:"266"}, {code:"LR", name:"Liberia", dial:"231"}, {code:"LY", name:"Libya", dial:"218"}, {code:"LI", name:"Liechtenstein", dial:"423"}, {code:"LT", name:"Lithuania", dial:"370"}, {code:"LU", name:"Luxembourg", dial:"352"}, {code:"MO", name:"Macao", dial:"853"}, {code:"MK", name:"Macedonia", dial:"389"}, {code:"MG", name:"Madagascar", dial:"261"}, {code:"MW", name:"Malawi", dial:"265"}, {code:"MY", name:"Malaysia", dial:"60"}, {code:"MV", name:"Maldives", dial:"960"}, {code:"ML", name:"Mali", dial:"223"}, {code:"MT", name:"Malta", dial:"356"}, {code:"MX", name:"Mexico", dial:"52"}, {code:"MD", name:"Moldova", dial:"373"}, {code:"MC", name:"Monaco", dial:"377"}, {code:"MN", name:"Mongolia", dial:"976"}, {code:"ME", name:"Montenegro", dial:"382"}, {code:"MA", name:"Morocco", dial:"212"}, {code:"MZ", name:"Mozambique", dial:"258"}, {code:"MM", name:"Myanmar", dial:"95"},
        {code:"NA", name:"Namibia", dial:"264"}, {code:"NP", name:"Nepal", dial:"977"}, {code:"NL", name:"Netherlands", dial:"31"}, {code:"NZ", name:"New Zealand", dial:"64"}, {code:"NI", name:"Nicaragua", dial:"505"}, {code:"NE", name:"Niger", dial:"227"}, {code:"NG", name:"Nigeria", dial:"234"}, {code:"NO", name:"Norway", dial:"47"}, {code:"OM", name:"Oman", dial:"968"},
        {code:"PK", name:"Pakistan", dial:"92"}, {code:"PW", name:"Palau", dial:"680"}, {code:"PS", name:"Palestine", dial:"970"}, {code:"PA", name:"Panama", dial:"507"}, {code:"PG", name:"Papua New Guinea", dial:"675"}, {code:"PY", name:"Paraguay", dial:"595"}, {code:"PE", name:"Peru", dial:"51"}, {code:"PH", name:"Philippines", dial:"63"}, {code:"PL", name:"Poland", dial:"48"}, {code:"PT", name:"Portugal", dial:"351"}, {code:"PR", name:"Puerto Rico", dial:"1787"},
        {code:"QA", name:"Qatar", dial:"974"}, {code:"RO", name:"Romania", dial:"40"}, {code:"RU", name:"Russia", dial:"7"}, {code:"RW", name:"Rwanda", dial:"250"}, {code:"KN", name:"Saint Kitts", dial:"1869"}, {code:"LC", name:"Saint Lucia", dial:"1758"}, {code:"VC", name:"Saint Vincent", dial:"1784"}, {code:"WS", name:"Samoa", dial:"685"}, {code:"SM", name:"San Marino", dial:"378"}, {code:"ST", name:"Sao Tome", dial:"239"}, {code:"SA", name:"Saudi Arabia", dial:"966"}, {code:"SN", name:"Senegal", dial:"221"}, {code:"RS", name:"Serbia", dial:"381"}, {code:"SC", name:"Seychelles", dial:"248"}, {code:"SL", name:"Sierra Leone", dial:"232"}, {code:"SG", name:"Singapore", dial:"65"}, {code:"SK", name:"Slovakia", dial:"421"}, {code:"SI", name:"Slovenia", dial:"386"}, {code:"SO", name:"Somalia", dial:"252"}, {code:"ZA", name:"South Africa", dial:"27"}, {code:"KR", name:"South Korea", dial:"82"}, {code:"ES", name:"Spain", dial:"34"}, {code:"LK", name:"Sri Lanka", dial:"94"}, {code:"SD", name:"Sudan", dial:"249"}, {code:"SR", name:"Suriname", dial:"597"}, {code:"SE", name:"Sweden", dial:"46"}, {code:"CH", name:"Switzerland", dial:"41"}, {code:"SY", name:"Syria", dial:"963"},
        {code:"TW", name:"Taiwan", dial:"886"}, {code:"TJ", name:"Tajikistan", dial:"992"}, {code:"TZ", name:"Tanzania", dial:"255"}, {code:"TH", name:"Thailand", dial:"66"}, {code:"TL", name:"Timor-Leste", dial:"670"}, {code:"TG", name:"Togo", dial:"228"}, {code:"TO", name:"Tonga", dial:"676"}, {code:"TT", name:"Trinidad & Tobago", dial:"1868"}, {code:"TN", name:"Tunisia", dial:"216"}, {code:"TR", name:"Turkey", dial:"90"}, {code:"TM", name:"Turkmenistan", dial:"993"}, {code:"UG", name:"Uganda", dial:"256"}, {code:"UA", name:"Ukraine", dial:"380"}, {code:"AE", name:"United Arab Emirates", dial:"971"}, {code:"GB", name:"United Kingdom", dial:"44"}, {code:"US", name:"United States", dial:"1"}, {code:"UY", name:"Uruguay", dial:"598"}, {code:"UZ", name:"Uzbekistan", dial:"998"}, {code:"VU", name:"Vanuatu", dial:"678"}, {code:"VE", name:"Venezuela", dial:"58"}, {code:"VN", name:"Vietnam", dial:"84"}, {code:"YE", name:"Yemen", dial:"967"}, {code:"ZM", name:"Zambia", dial:"260"}, {code:"ZW", name:"Zimbabwe", dial:"263"}
      ];
      const countryDropdown = document.getElementById('nationality');
      const phoneCodeDropdown = document.getElementById('phone-code');
      
      // Dynamically build dropdown options to guarantee complete A-Z list
    countries.forEach(country => {
        // Build Country list element
        const optCountry = document.createElement('option');
        optCountry.value = country.code;
        optCountry.textContent = country.name;
        countryDropdown.appendChild(optCountry);

        // Build Phone Code element
        const optCode = document.createElement('option');
        optCode.value = country.dial;
        optCode.setAttribute('data-iso', country.code);
        // FIXED: Added backticks around the template string literal
        optCode.textContent = `${country.code} (+${country.dial})`;
        phoneCodeDropdown.appendChild(optCode);
    });

     // Dynamic verification loop to handle slow network CDN loading
    function initializeSlimSelect() {
        // Fallback scope resolution check
        const instanceConstructor = window.SlimSelect || SlimSelect;

        if (typeof instanceConstructor === 'undefined') {
            console.log("SlimSelect library file still downloading... retrying in 50ms");
            setTimeout(initializeSlimSelect, 500); 
            return;
        }

        console.log("SlimSelect Engine loaded successfully. Initializing wrappers.");

        const countrySlim = new instanceConstructor({
            select: '#nationality',
            settings: { placeholderText: 'Select a country...' }});
            const phoneSlim = new instanceConstructor({
              select: '#phone-code',settings: { 
                placeholderText: 'Code' 
              }
            });
    // // Initialize Searchable UI wrappers via SlimSelect
    // const countrySlim = new SlimSelect({
    //   select: '#nationality',settings: { 
    //     placeholderText: 'Select a country...' 
    //   }
    // });
    // const phoneSlim = new SlimSelect({
    //   select: '#phone-code',settings: { placeholderText: 'Code' }
    // });
    // Flag tracking variable to prevent infinite update cross-firing loops
    let isSyncing = false;
    // 1. Sync Phone Code when Country/Nationality changes
    countryDropdown.addEventListener('change', function() {
      if (isSyncing) return;
      isSyncing = true;
      const selectedCountryISO = this.value;
      // Search options manually for custom data-iso targets
      for (let i = 0; i < phoneCodeSelectElement.options.length; i++) {
        if (phoneCodeSelectElement.options[i].getAttribute('data-iso') === selectedCountryISO) {
          phoneSlim.setSelected(phoneCodeSelectElement.options[i].value);
          break;
        }
      }
      isSyncing = false;
    });
    // 2. Bidirectional Sync: Change Country when Phone Code changes
    phoneCodeDropdown.addEventListener('change', function() {
      if (isSyncing) return;
      isSyncing = true;
      const selectedOption = this.options[this.selectedIndex];
      const associatedISO = selectedOption.getAttribute('data-iso');
      if (associatedISO) {
        countrySlim.setSelected(associatedISO);
      }
      isSyncing = false;
    });
              }
              // Call the dynamic loop executor
              initializeSlimSelect();
              });
  </script>
</body>
</html>
