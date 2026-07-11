<?php
// pricing.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';

// Get active service name from URL or default
$active_service = $_GET['service'] ?? 'Golden Visa';

// Fetch matching services and their providers
$p_sql = "SELECT p.*, s.price, s.currency, s.duration_minutes, s.slug as service_slug, s.rating_avg as service_rating, s.rating_count as service_reviews_count
          FROM providers p
          JOIN services s ON s.provider_id = p.id
          WHERE s.title LIKE '%" . $mysqli->real_escape_string($active_service) . "%' AND s.status = 'published'
          ORDER BY s.price ASC
          LIMIT 6";
$res = $mysqli->query($p_sql);
$pricing_cards = [];
if ($res) {
    while ($row = $res->fetch_assoc()) $pricing_cards[] = $row;
    $res->free();
}

// Fallback if no matching services are in the database yet
if (empty($pricing_cards)) {
    // Generate dummy/rich mockup pricing cards dynamically to preserve UI
    $pricing_cards = [
        [
            'name' => 'Dubai Business Hub',
            'slug' => 'dubai-business-hub',
            'price' => 4800.00,
            'currency' => 'AED',
            'duration_minutes' => 7 * 24 * 60, // 7 days
            'rating_avg' => 4.9,
            'rating_count' => 1580,
            'city' => 'Dubai',
            'specialties' => 'Golden Visa, Business Setup',
            'bullets' => ['Government preparation', 'Application submission', 'Document follow-up', 'Live tracking', 'Email & WhatsApp updates']
        ],
        [
            'name' => 'Emirates Pro Services',
            'slug' => 'emirates-pro',
            'price' => 5000.00,
            'currency' => 'AED',
            'duration_minutes' => 5 * 24 * 60, // 5 days
            'rating_avg' => 4.9,
            'rating_count' => 1250,
            'city' => 'Dubai',
            'specialties' => 'Golden Visa, Business Setup, Family Visa',
            'bullets' => ['Document preparation', 'Application submission', 'Government follow-up', 'Medical scheduling', 'Dedicated account support', 'Priority processing']
        ],
        [
            'name' => 'Gulf Visa Experts',
            'slug' => 'gulf-advisors',
            'price' => 5500.00,
            'currency' => 'AED',
            'duration_minutes' => 6 * 24 * 60, // 6 days
            'rating_avg' => 4.8,
            'rating_count' => 980,
            'city' => 'Dubai',
            'specialties' => 'Golden Visa, Executive Setup',
            'bullets' => ['Document preparation', 'Application submission', 'Government follow-up', 'Priority processing', 'Airport pickup']
        ]
    ];
}

include __DIR__ . '/partials/frontend_header.php';
?>

  <main>
    <section class="page-hero pricing-hero">
      <div class="container-xl">
        <nav class="breadcrumb-gw justify-content-center" aria-label="Breadcrumb"><a href="services.php">Services</a> / Pricing</nav>
        <h1 class="font-serif mb-3 text-center" style="font-size:clamp(2.1rem,4vw,3.35rem)">Compare Vendor<br>Pricing</h1>
        <p class="text-white-50 text-center pricing-hero-sub mb-0">All prices are vendor quotes, securely paid through our escrow process.<br>Lock in the best value before your vendor starts work.</p>
      </div>
    </section>

    <section class="pricing-main-section">
      <div class="container-xl py-4">
        <div class="d-flex flex-wrap gap-2 pricing-service-tabs" role="group" aria-label="Filter by service">
          <?php
            $services_list = ['Golden Visa', 'Business Setup', 'Family Visa', 'Emirates ID', 'Work Permit', 'PRO Services'];
            foreach ($services_list as $s_item):
          ?>
            <a href="pricing.php?service=<?= urlencode($s_item) ?>" class="filter-pill-btn text-decoration-none <?= $active_service === $s_item ? 'active' : '' ?>">
              <i class="bi bi-tag-fill"></i> <?= htmlspecialchars($s_item) ?>
            </a>
          <?php endforeach; ?>
        </div>
        <hr class="section-tabs-divider">

        <div class="row g-4 align-items-stretch pricing-vendors-row">
          <?php foreach ($pricing_cards as $index => $card):
              $initials = '';
              $words = explode(' ', $card['name']);
              foreach ($words as $w) {
                  $initials .= mb_substr($w, 0, 1);
              }
              $initials = mb_strtoupper(mb_substr($initials, 0, 2));

              $rating = !empty($card['rating_avg']) ? round($card['rating_avg'], 1) : '4.8';
              $reviews_count = !empty($card['rating_count']) ? $card['rating_count'] : '250';

              // Formatting timeline
              $days = !empty($card['duration_minutes']) ? round($card['duration_minutes'] / (24 * 60)) : 5;
              if ($days <= 0) $days = 5;
              $timeline_label = $days . " days processing";

              $bullets = $card['bullets'] ?? [
                  'Document preparation',
                  'Application submission',
                  'Government follow-up',
                  'Live tracking dashboard',
                  'WhatsApp & email updates'
              ];

              $is_featured = ($index === 1); // Make the middle one featured as in HTML template
          ?>
            <div class="col-lg-4 fade-in">
              <article class="price-vendor-card <?= $is_featured ? 'featured' : '' ?> h-100 d-flex flex-column">
                <span class="price-chip"><?= $index === 0 ? 'Best Value' : ($is_featured ? 'Fastest' : 'Premium') ?></span>
                <div class="d-flex align-items-center gap-3 mb-3">
                  <?php if (!empty($card['logo'])): ?>
                    <img src="<?= htmlspecialchars($card['logo']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:50%;">
                  <?php else: ?>
                    <span class="avatar-circle pricing-avatar" style="background:#0C0C0C;"><?= htmlspecialchars($initials) ?></span>
                  <?php endif; ?>
                  <div>
                    <h2 class="h6 font-serif mb-0"><?= htmlspecialchars($card['name']) ?></h2>
                    <div class="text-warning small"><i class="bi bi-star-fill text-warning"></i> <?= htmlspecialchars($rating) ?> <span class="text-muted">(<?= htmlspecialchars($reviews_count) ?>)</span> <i class="bi bi-patch-check-fill text-primary ms-1"></i></div>
                  </div>
                </div>
                <div class="price-box">
                  <div class="font-serif fs-3 mb-1"><?= htmlspecialchars($card['currency'] ?? 'AED') ?> <?= number_format($card['price'] ?? 5000) ?></div>
                  <div class="small text-secondary pricing-process-line"><i class="bi bi-clock"></i> <?= htmlspecialchars($timeline_label) ?></div>
                  <div class="pricing-success-pill"><i class="bi bi-graph-up-arrow"></i> 99.8% success rate</div>
                </div>
                <ul class="list-unstyled small text-secondary mb-4 d-grid gap-2 flex-grow-1">
                  <?php foreach ($bullets as $b): ?>
                    <li><i class="bi bi-check2-circle"></i><?= htmlspecialchars($b) ?></li>
                  <?php endforeach; ?>
                </ul>
                <a href="service-detail.php?id=<?= htmlspecialchars($card['service_slug'] ?? 'golden-visa') ?>" class="btn btn-gw-dark w-100 mt-auto">Book Now — <?= htmlspecialchars($card['currency'] ?? 'AED') ?> <?= number_format($card['price'] ?? 5000) ?></a>
                <a href="vendor-profile.php?id=<?= htmlspecialchars($card['slug']) ?>" class="pricing-link-secondary">Request Custom Quote</a>
              </article>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="protection-banner d-flex align-items-center gap-3 mt-4 fade-in">
          <i class="bi bi-shield-check"></i>
          <div>
            <div class="fw-semibold small">GlobalWays Buyer Protection</div>
            <p class="small text-secondary mb-0">All orders are covered by our money-back guarantee</p>
          </div>
          <a href="#" class="protection-link">Learn more <i class="bi bi-chevron-right"></i></a>
        </div>

        <section class="py-5 bg-transparent pricing-included">
          <div class="text-center mb-4 fade-in">
            <h2 class="font-serif pricing-included-title">Everything <span class="text-gradient-blue">Included</span> on Every Order</h2>
          </div>
          <div class="row g-3">
            <div class="col-sm-6 col-lg-4 fade-in">
              <div class="feature-card">
                <div class="service-icon"><i class="bi bi-shield-lock"></i></div>
                <h3 class="h6 font-serif mb-2">Escrow Payments</h3>
                <p class="small text-secondary mb-0">Money held safely until you confirm</p>
              </div>
            </div>
            <div class="col-sm-6 col-lg-4 fade-in">
              <div class="feature-card">
                <div class="service-icon"><i class="bi bi-broadcast"></i></div>
                <h3 class="h6 font-serif mb-2">Real-Time Tracking</h3>
                <p class="small text-secondary mb-0">FedEx-style progress tracking</p>
              </div>
            </div>
            <div class="col-sm-6 col-lg-4 fade-in">
              <div class="feature-card">
                <div class="service-icon"><i class="bi bi-lock"></i></div>
                <h3 class="h6 font-serif mb-2">Document Vault</h3>
                <p class="small text-secondary mb-0">Encrypted cloud storage</p>
              </div>
            </div>
            <div class="col-sm-6 col-lg-4 fade-in">
              <div class="feature-card">
                <div class="service-icon"><i class="bi bi-chat"></i></div>
                <h3 class="h6 font-serif mb-2">Direct Messaging</h3>
                <p class="small text-secondary mb-0">Chat with vendors in-app</p>
              </div>
            </div>
            <div class="col-sm-6 col-lg-4 fade-in">
              <div class="feature-card">
                <div class="service-icon"><i class="bi bi-people"></i></div>
                <h3 class="h6 font-serif mb-2">Verified Vendors</h3>
                <p class="small text-secondary mb-0">All providers background-checked</p>
              </div>
            </div>
            <div class="col-sm-6 col-lg-4 fade-in">
              <div class="feature-card">
                <div class="service-icon"><i class="bi bi-check-circle"></i></div>
                <h3 class="h6 font-serif mb-2">Money Back Guarantee</h3>
                <p class="small text-secondary mb-0">If standards aren't met</p>
              </div>
            </div>
          </div>
          <div class="pricing-bottom-actions d-flex flex-wrap justify-content-center gap-3 mt-4 fade-in">
            <a href="services.php" class="btn btn-gw-dark">Browse All Services</a>
            <a href="vendors.php" class="btn btn-gw-outline">Compare Vendors</a>
          </div>
        </section>
      </div>
    </section>
  </main>

<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
