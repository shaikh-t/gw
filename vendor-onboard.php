<?php
// vendor-onboard.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/middleware.php';

// Require login for onboarding
if (!empty($_SESSION['user'])) {
    $current_user = current_user();
} else {
    // If not logged in, redirect to login page with a return pointer
    $_SESSION['flash_errors'] = 'Please login or create an account first to start vendor onboarding.';
    header('Location: login.php');
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    require_once __DIR__ . '/lib/onboarding_helpers.php';
    require_once __DIR__ . '/lib/notifier.php';

    $companyName = trim($_POST['companyName'] ?? '');
    $tradeLicense = trim($_POST['tradeLicense'] ?? '');
    $emirate = trim($_POST['emirate'] ?? '');
    $companyDesc = trim($_POST['companyDesc'] ?? '');
    $avgTurnaround = intval($_POST['avgTurnaround'] ?? 5);
    $contactEmail = trim($_POST['contactEmail'] ?? '');

    // Checked Specialties
    $specialties_arr = $_POST['specialties'] ?? [];
    $specialties_str = implode(', ', $specialties_arr);

    if ($companyName === '' || $tradeLicense === '' || $contactEmail === '') {
        $error_message = 'Please fill all required company information fields.';
    } else {
        $data = [
            'name' => $companyName,
            'owner_user_id' => $current_user['id'],
            'email' => $contactEmail,
            'phone' => $current_user['phone'] ?? '',
            'address' => $emirate . ', UAE',
            'city' => $emirate,
            'state' => $emirate,
            'country' => 'United Arab Emirates',
            'description' => $companyDesc
        ];

        // Start Onboarding Process
        $res = onboarding_start($data);
        if (!$res['ok']) {
            $error_message = $res['error'];
        } else {
            $onb_id = intval($res['onboarding_id']);
            $provider_id = intval($res['provider_id']);

            // Save extra specialties and starting price dynamically to providers table
            $mysqli->query("UPDATE providers SET specialties = '" . $mysqli->real_escape_string($specialties_str) . "', team_size = 10, starting_price = 500.00 WHERE id = $provider_id");

            // Process document uploads
            $uploaded_files = [];
            $upload_inputs = ['licenseDoc', 'ownerId', 'portfolio'];
            foreach ($upload_inputs as $input_name) {
                if (!empty($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
                    $uploaded_files[] = [
                        'name' => $_FILES[$input_name]['name'],
                        'type' => $_FILES[$input_name]['type'],
                        'tmp_name' => $_FILES[$input_name]['tmp_name'],
                        'error' => $_FILES[$input_name]['error'],
                        'size' => $_FILES[$input_name]['size']
                    ];
                }
            }

            if (!empty($uploaded_files)) {
                $r2 = onboarding_submit_documents($onb_id, $uploaded_files, $current_user['id']);
                if (!$r2['ok']) {
                    $error_message = 'Company registered but document submission failed: ' . $r2['error'];
                }
            }

            if (empty($error_message)) {
                // Send notification email to admin
                notifier_send_email('admin@example.com', 'New provider onboarding', 'A new provider has started onboarding: ' . htmlspecialchars($companyName, ENT_QUOTES));

                $_SESSION['flash_success'] = 'Onboarding application submitted successfully! Our team will review within 3 business days.';
                header('Location: providers/onboarding_status.php?onb=' . $onb_id);
                exit;
            }
        }
    }
}

include __DIR__ . '/partials/frontend_header.php';
?>

  <main class="py-5" style="padding-top:7rem!important">
    <div class="container-xl">
      <div class="row justify-content-center">
        <div class="col-lg-8 fade-in">
          <div class="text-center mb-5">
            <p class="label-mono">Vendor Application</p>
            <h1 class="font-serif h2 mb-2">Join the <span class="text-gradient-blue">GlobalWays</span> Network</h1>
            <p class="text-secondary small">Complete all four steps to apply. Our team reviews applications within 3 business days.</p>
          </div>

          <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger mb-4"><?= htmlspecialchars($error_message) ?></div>
          <?php endif; ?>

          <!-- Step indicators -->
          <div class="d-flex justify-content-between mb-4 px-2" id="stepIndicators" aria-hidden="true">
            <div class="text-center flex-fill step-indicator active" data-step="1">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white mb-1" style="width:2rem;height:2rem;font-size:0.75rem">1</span>
              <p class="font-mono small mb-0 d-none d-sm-block">Company</p>
            </div>
            <div class="text-center flex-fill step-indicator" data-step="2">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white mb-1" style="width:2rem;height:2rem;font-size:0.75rem">2</span>
              <p class="font-mono small mb-0 d-none d-sm-block">Services</p>
            </div>
            <div class="text-center flex-fill step-indicator" data-step="3">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white mb-1" style="width:2rem;height:2rem;font-size:0.75rem">3</span>
              <p class="font-mono small mb-0 d-none d-sm-block">Documents</p>
            </div>
            <div class="text-center flex-fill step-indicator" data-step="4">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white mb-1" style="width:2rem;height:2rem;font-size:0.75rem">4</span>
              <p class="font-mono small mb-0 d-none d-sm-block">Review</p>
            </div>
          </div>

          <form id="vendorOnboardForm" class="feature-card" method="post" action="vendor-onboard.php" enctype="multipart/form-data">
            <?= csrf_field(); ?>

            <!-- Step 1: Company -->
            <div class="onboard-step" data-step="1">
              <h2 class="h5 font-serif mb-4">Company Information</h2>
              <div class="row g-3">
                <div class="col-12">
                  <label for="companyName" class="form-label small fw-medium">Company name *</label>
                  <input type="text" name="companyName" class="form-control" id="companyName" required>
                </div>
                <div class="col-sm-6">
                  <label for="tradeLicense" class="form-label small fw-medium">Trade license number *</label>
                  <input type="text" name="tradeLicense" class="form-control" id="tradeLicense" required>
                </div>
                <div class="col-sm-6">
                  <label for="emirate" class="form-label small fw-medium">Emirate *</label>
                  <select name="emirate" class="form-select" id="emirate" required>
                    <option value="">Select emirate</option>
                    <option>Dubai</option>
                    <option>Abu Dhabi</option>
                    <option>Sharjah</option>
                    <option>Ajman</option>
                    <option>Ras Al Khaimah</option>
                    <option>Fujairah</option>
                    <option>Umm Al Quwain</option>
                  </select>
                </div>
                <div class="col-12">
                  <label for="companyDesc" class="form-label small fw-medium">Company description *</label>
                  <textarea name="companyDesc" class="form-control" id="companyDesc" rows="3" required></textarea>
                </div>
              </div>
            </div>

            <!-- Step 2: Services -->
            <div class="onboard-step d-none" data-step="2">
              <h2 class="h5 font-serif mb-4">Services Offered</h2>
              <p class="small text-secondary mb-3">Select all services your company provides.</p>
              <div class="row g-2">
                <div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="specialties[]" id="svcGolden" value="Golden Visa"><label class="form-check-label small" for="svcGolden">Golden Visa</label></div></div>
                <div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="specialties[]" id="svcInvestor" value="Investor Visa"><label class="form-check-label small" for="svcInvestor">Investor Visa</label></div></div>
                <div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="specialties[]" id="svcFamily" value="Family Visa"><label class="form-check-label small" for="svcFamily">Family Visa</label></div></div>
                <div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="specialties[]" id="svcEmployment" value="Employment Visa"><label class="form-check-label small" for="svcEmployment">Employment Visa</label></div></div>
                <div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="specialties[]" id="svcBusiness" value="Business Setup"><label class="form-check-label small" for="svcBusiness">Business Setup</label></div></div>
                <div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="specialties[]" id="svcFreezone" value="Free Zone"><label class="form-check-label small" for="svcFreezone">Free Zone</label></div></div>
                <div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="specialties[]" id="svcMainland" value="Mainland"><label class="form-check-label small" for="svcMainland">Mainland</label></div></div>
                <div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="specialties[]" id="svcPro" value="PRO Services"><label class="form-check-label small" for="svcPro">PRO Services</label></div></div>
                <div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="specialties[]" id="svcEid" value="Emirates ID"><label class="form-check-label small" for="svcEid">Emirates ID</label></div></div>
              </div>
              <div class="mt-3">
                <label for="avgTurnaround" class="form-label small fw-medium">Average turnaround (days)</label>
                <input type="number" name="avgTurnaround" class="form-control" id="avgTurnaround" min="1" max="90" placeholder="e.g. 5" value="5">
              </div>
            </div>

            <!-- Step 3: Documents -->
            <div class="onboard-step d-none" data-step="3">
              <h2 class="h5 font-serif mb-4">Upload Documents</h2>
              <div class="row g-3">
                <div class="col-12">
                  <label for="licenseDoc" class="form-label small fw-medium">Trade license copy *</label>
                  <input type="file" name="licenseDoc" class="form-control" id="licenseDoc" accept=".pdf,.jpg,.png" required>
                </div>
                <div class="col-12">
                  <label for="ownerId" class="form-label small fw-medium">Owner Emirates ID / Passport *</label>
                  <input type="file" name="ownerId" class="form-control" id="ownerId" accept=".pdf,.jpg,.png" required>
                </div>
                <div class="col-12">
                  <label for="portfolio" class="form-label small fw-medium">Portfolio or case studies (optional)</label>
                  <input type="file" name="portfolio" class="form-control" id="portfolio" accept=".pdf">
                </div>
              </div>
            </div>

            <!-- Step 4: Review -->
            <div class="onboard-step d-none" data-step="4">
              <h2 class="h5 font-serif mb-4">Review &amp; Submit</h2>
              <div class="bg-warm rounded-3 p-4 mb-4">
                <p class="label-mono mb-2">Summary</p>
                <dl class="row small mb-0" id="reviewSummary">
                  <dt class="col-sm-4 text-secondary">Company</dt>
                  <dd class="col-sm-8" id="reviewCompany">—</dd>
                  <dt class="col-sm-4 text-secondary">License</dt>
                  <dd class="col-sm-8" id="reviewLicense">—</dd>
                  <dt class="col-sm-4 text-secondary">Emirate</dt>
                  <dd class="col-sm-8" id="reviewEmirate">—</dd>
                </dl>
              </div>
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="vendorTerms" required>
                <label class="form-check-label small" for="vendorTerms">I confirm all information is accurate and agree to GlobalWays vendor terms.</label>
              </div>
              <div class="mb-3">
                <label for="contactEmail" class="form-label small fw-medium">Contact email *</label>
                <input type="email" name="contactEmail" class="form-control" id="contactEmail" required value="<?= htmlspecialchars($current_user['email']) ?>">
              </div>
            </div>

            <!-- Navigation -->
            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
              <button type="button" class="btn btn-gw-outline" id="prevStep" disabled>Back</button>
              <button type="button" class="btn btn-gw-blue" id="nextStep">Continue</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/main.js"></script>
  <script>
    (function () {
      let currentStep = 1;
      const totalSteps = 4;
      const steps = document.querySelectorAll('.onboard-step');
      const indicators = document.querySelectorAll('.step-indicator');
      const prevBtn = document.getElementById('prevStep');
      const nextBtn = document.getElementById('nextStep');
      const form = document.getElementById('vendorOnboardForm');

      function showStep(n) {
        currentStep = n;
        steps.forEach((s) => s.classList.toggle('d-none', parseInt(s.dataset.step, 10) !== n));
        indicators.forEach((ind) => {
          const num = parseInt(ind.dataset.step, 10);
          const circle = ind.querySelector('span');
          const active = num <= n;
          circle.classList.toggle('bg-primary', active);
          circle.classList.toggle('bg-secondary', !active);
          ind.classList.toggle('active', num === n);
        });
        prevBtn.disabled = n === 1;
        nextBtn.textContent = n === totalSteps ? 'Submit Application' : 'Continue';

        if (n === 4) {
          document.getElementById('reviewCompany').textContent = document.getElementById('companyName').value || '—';
          document.getElementById('reviewLicense').textContent = document.getElementById('tradeLicense').value || '—';
          document.getElementById('reviewEmirate').textContent = document.getElementById('emirate').value || '—';
        }
      }

      prevBtn.addEventListener('click', () => { if (currentStep > 1) showStep(currentStep - 1); });

      nextBtn.addEventListener('click', () => {
        if (currentStep < totalSteps) {
          // Client-side validations per step
          if (currentStep === 1) {
            const companyName = document.getElementById('companyName');
            const tradeLicense = document.getElementById('tradeLicense');
            const emirate = document.getElementById('emirate');
            const companyDesc = document.getElementById('companyDesc');
            if (!companyName.checkValidity() || !tradeLicense.checkValidity() || !emirate.checkValidity() || !companyDesc.checkValidity()) {
              companyName.reportValidity();
              tradeLicense.reportValidity();
              emirate.reportValidity();
              companyDesc.reportValidity();
              return;
            }
          } else if (currentStep === 3) {
            const licenseDoc = document.getElementById('licenseDoc');
            const ownerId = document.getElementById('ownerId');
            if (!licenseDoc.checkValidity() || !ownerId.checkValidity()) {
              licenseDoc.reportValidity();
              ownerId.reportValidity();
              return;
            }
          }
          showStep(currentStep + 1);
        } else {
          const vendorTerms = document.getElementById('vendorTerms');
          const contactEmail = document.getElementById('contactEmail');
          if (!vendorTerms.checkValidity() || !contactEmail.checkValidity()) {
            vendorTerms.reportValidity();
            contactEmail.reportValidity();
            return;
          }
          form.submit();
        }
      });
    })();
  </script>

<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
