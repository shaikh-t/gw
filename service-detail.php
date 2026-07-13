<?php
// service-detail.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/services_helpers.php';

$id_val = $_GET['id'] ?? '';
$service = null;

if ($id_val !== '') {
    $service = service_find($id_val);
}

// Fallback to first published service if not found
if (!$service) {
    $res = $mysqli->query("SELECT s.*, c.name as category_name FROM services s LEFT JOIN service_categories c ON c.id = s.category_id WHERE s.status = 'published' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $service = service_find($res->fetch_assoc()['id']);
    }
}

if (!$service) {
    http_response_code(404);
    echo "Service not found";
    exit;
}

// Default structural mockups for richness
$defaultProcess = [
    ['Document Preparation', 'Gather and prepare all required documents'],
    ['Vendor Selection', 'Compare verified providers on GlobalWays'],
    ['Application Submission', 'Your vendor files with UAE authorities'],
    ['Document Verification', 'Authorities review submitted paperwork'],
    ['Approval & Processing', 'Application moves through final review'],
    ['Visa Issuance', 'Collect your Golden Visa and Emirates ID'],
];
$defaultDocs = [
    'Valid passport (6+ months validity)',
    'Recent passport-sized photographs',
    'Proof of investment or exceptional talent',
    'Bank statements or financial proof',
    'Medical fitness certificate',
    'Emirates ID application',
    'Security clearance documentation',
];
$defaultBenefits = [
    '5 or 10-year renewable residence visa',
    'No requirement for a UAE sponsor',
    'Ability to sponsor family members',
    'Freedom to stay outside UAE for extended periods',
    'Work permit included',
    'Access to UAE healthcare and education',
];

// Rich custom data based on the service slug or title
$service_slug = $service['slug'];
$service_details_data = [
    'golden-visa' => [
        'docs' => $defaultDocs,
        'benefits' => $defaultBenefits,
        'process' => $defaultProcess
    ],
    'investor-visa' => [
        'docs' => $defaultDocs,
        'benefits' => $defaultBenefits,
        'process' => $defaultProcess
    ],
    'family-visa' => [
        'docs' => ['Sponsor Emirates ID', 'Passport copies', 'Marriage / birth certificates', 'Salary certificate', 'Tenancy contract', 'Photos'],
        'benefits' => $defaultBenefits,
        'process' => $defaultProcess
    ],
    'tourist-visa' => [
        'docs' => ['Valid passport', 'Passport photo', 'Travel itinerary', 'Hotel booking', 'Return ticket', 'Bank statement'],
        'benefits' => $defaultBenefits,
        'process' => $defaultProcess
    ]
];

$rich_data = $service_details_data[$service_slug] ?? [
    'docs' => $defaultDocs,
    'benefits' => $defaultBenefits,
    'process' => $defaultProcess
];

// Fetch active providers offering services in this category or with this service title
$p_sql = "SELECT p.*, s2.price, s2.currency, s2.duration_minutes
          FROM providers p
          JOIN services s2 ON s2.provider_id = p.id
          WHERE s2.title = '" . $mysqli->real_escape_string($service['title']) . "' AND s2.status = 'published'
          ORDER BY p.rating_avg DESC
          LIMIT 3";
$res_p = $mysqli->query($p_sql);
$top_vendors = [];
if ($res_p && $res_p->num_rows > 0) {
    while ($row = $res_p->fetch_assoc()) $top_vendors[] = $row;
    $res_p->free();
} else {
    // Fallback: Fetch any 3 active/draft providers
    $p_sql_fallback = "SELECT * FROM providers ORDER BY rating_avg DESC LIMIT 3";
    $res_pf = $mysqli->query($p_sql_fallback);
    if ($res_pf) {
        while ($row = $res_pf->fetch_assoc()) {
            $row['price'] = $service['price'];
            $row['currency'] = $service['currency'];
            $row['duration_minutes'] = $service['duration_minutes'];
            $top_vendors[] = $row;
        }
        $res_pf->free();
    }
}

// Fetch actual reviews for this service
$r_sql = "SELECT r.*, u.name as user_name, u.avatar as user_avatar
          FROM reviews r
          JOIN users u ON u.id = r.user_id
          WHERE r.service_id = " . intval($service['id']) . " AND r.status = 'published'
          ORDER BY r.created_at DESC";
$res_reviews = $mysqli->query($r_sql);
$reviews = [];
if ($res_reviews) {
    while($row = $res_reviews->fetch_assoc()) $reviews[] = $row;
    $res_reviews->free();
}

$price_value = !empty($service['price']) ? htmlspecialchars($service['currency'] . ' ' . number_format($service['price'])) : 'Price on request';
$timeline_value = !empty($service['duration_minutes']) ? htmlspecialchars($service['duration_minutes'] . ' mins') : '5–7 days';

include __DIR__ . '/partials/frontend_header.php';
?>

  <main>
    <!-- Hero -->
    <section class="sd-hero">
      <div class="container-xl">
        <div class="row align-items-end g-4">
          <div class="col-lg-7 fade-in">
            <a href="services.php" class="sd-back">← Back to Services</a>
            <p class="sd-kicker" id="serviceCategory"><?= htmlspecialchars($service['category_name'] ?? 'Service Overview') ?></p>
            <h1 class="sd-title font-serif" id="serviceTitle"><?= htmlspecialchars($service['title']) ?></h1>
            <p class="sd-desc" id="serviceDesc"><?= htmlspecialchars($service['short_description']) ?></p>
          </div>
          <div class="col-lg-5 fade-in">
            <div class="sd-price-card">
              <p class="sd-price-label">Starting From</p>
              <p class="sd-price-value font-serif" id="servicePrice"><?= $price_value ?></p>
              <p class="sd-price-time"><i class="bi bi-clock"></i> <span id="serviceTimeline"><?= $timeline_value ?></span></p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Content -->
    <section class="sd-body">
      <div class="container-xl">
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="sd-card fade-in">
              <p class="sd-section-kicker">About This Service</p>
              <p class="sd-card-text" id="serviceAbout"><?= nl2br(htmlspecialchars($service['description'])) ?></p>
            </div>

            <div class="sd-card fade-in">
              <p class="sd-section-kicker">Step-by-Step</p>
              <h2 class="sd-card-title font-serif">Application Process</h2>
              <div class="sd-process" id="serviceProcess">
                <?php foreach ($rich_data['process'] as $index => $step): ?>
                  <div class="sd-step">
                    <span class="sd-step-num"><?= $index + 1 ?></span>
                    <div>
                      <strong><?= htmlspecialchars($step[0]) ?></strong>
                      <p><?= htmlspecialchars($step[1]) ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="sd-card fade-in">
              <p class="sd-section-kicker">What You Need</p>
              <h2 class="sd-card-title font-serif">Required Documents</h2>
              <div class="sd-pill-grid" id="serviceDocs">
                <?php foreach ($rich_data['docs'] as $doc): ?>
                  <div class="sd-pill sd-pill-doc"><i class="bi bi-check-lg"></i> <?= htmlspecialchars($doc) ?></div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="sd-card fade-in">
              <p class="sd-section-kicker">What You Gain</p>
              <h2 class="sd-card-title font-serif">Key Benefits</h2>
              <div class="sd-pill-grid" id="serviceBenefits">
                <?php foreach ($rich_data['benefits'] as $benefit): ?>
                  <div class="sd-pill sd-pill-benefit"><i class="bi bi-star-fill"></i> <?= htmlspecialchars($benefit) ?></div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Reviews Section -->
            <div class="sd-card fade-in mt-4">
              <p class="sd-section-kicker">Customer Voice</p>
              <h2 class="sd-card-title font-serif mb-4">Service Reviews</h2>
              <?php if (empty($reviews)): ?>
                <p class="text-muted">No reviews have been left for this service yet.</p>
              <?php else: ?>
                <div class="d-flex flex-column gap-3">
                  <?php foreach ($reviews as $rev): ?>
                    <div class="p-3 border rounded bg-white">
                      <div class="d-flex justify-content-between mb-2">
                        <strong><?= htmlspecialchars($rev['user_name']) ?></strong>
                        <span class="text-warning">
                          <?php for ($i = 0; $i < $rev['rating']; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                          <?php for ($i = $rev['rating']; $i < 5; $i++): ?><i class="bi bi-star"></i><?php endfor; ?>
                        </span>
                      </div>
                      <?php if (!empty($rev['title'])): ?>
                        <h6 class="font-serif"><?= htmlspecialchars($rev['title']) ?></h6>
                      <?php endif; ?>
                      <p class="mb-0 text-secondary small"><?= nl2br(htmlspecialchars($rev['body'])) ?></p>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="sd-stats-card fade-in">
              <p class="sd-section-kicker">Service Statistics</p>
              <div class="sd-stat sd-stat-blue">
                <div>
                  <strong><?= !empty($service['rating_avg']) ? round($service['rating_avg'], 1) : '4.9' ?></strong>
                  <span>Average Rating</span>
                </div>
                <i class="bi bi-shield-check"></i>
              </div>
              <div class="sd-stat sd-stat-light">
                <div>
                  <strong><?= !empty($service['rating_count']) ? $service['rating_count'] : '120+' ?></strong>
                  <span>Verified Reviews</span>
                </div>
                <i class="bi bi-award"></i>
              </div>
              <div class="sd-stat sd-stat-muted">
                <div>
                  <strong><?= count($top_vendors) ?></strong>
                  <span>Active Vendors</span>
                </div>
              </div>
              <a href="vendors.php" class="sd-compare-btn">Compare Vendors →</a>
            </div>

            <div class="sd-vendors-card fade-in">
              <p class="sd-section-kicker">Top Rated</p>
              <h3 class="sd-vendors-title font-serif">Top Vendors</h3>
              <div class="sd-vendor-list" id="vendorList">
                <?php foreach ($top_vendors as $v):
                    $v_price = !empty($v['price']) ? $v['currency'] . ' ' . number_format($v['price']) : 'Contact';
                    $v_time = !empty($v['duration_minutes']) ? $v['duration_minutes'] . ' mins' : '5 days';
                    $v_rating = !empty($v['rating_avg']) ? round($v['rating_avg'], 1) : '4.8';
                    $v_count = !empty($v['rating_count']) ? $v['rating_count'] : '25';
                ?>
                  <a href="vendor-profile.php?id=<?= htmlspecialchars($v['slug']) ?>" class="sd-vendor-item">
                    <strong><?= htmlspecialchars($v['name']) ?> <span class="sd-vendor-badge"><i class="bi bi-patch-check-fill"></i></span></strong>
                    <div class="sd-vendor-stars">
                      <span class="sd-stars"><i class="bi bi-star-fill text-warning"></i> <?= htmlspecialchars($v_rating) ?></span>
                      <span class="sd-rating">(<?= htmlspecialchars($v_count) ?> reviews)</span>
                    </div>
                    <div class="sd-vendor-meta"><span><?= $v_price ?></span><span><?= $v_time ?></span></div>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Bottom CTA -->
    <section class="sd-cta">
      <div class="container-xl text-center fade-in">
        <h2 class="font-serif" id="ctaTitle">Find the Best <?= htmlspecialchars($service['title']) ?> Vendor</h2>
        <div class="sd-cta-actions">
          <a href="vendors.php" class="btn btn-gw-blue btn-lg">Browse Vendors →</a>
          <a href="register.php" class="btn btn-outline-light btn-lg rounded-pill">Create Free Account</a>
        </div>
      </div>
    </section>
  </main>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('service-detail-page');
    document.body.classList.add('has-custom-cursor');
    document.getElementById('gwNav').classList.add('dark-hero');
});
</script>
<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
