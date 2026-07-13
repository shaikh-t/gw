<?php
require_once __DIR__ . '/lib/settings_helper.php';
require_once __DIR__ . '/lib/services_helpers.php';
require_once __DIR__ . '/lib/db_mysqli.php';

$s = get_all_settings();

// Fetch dynamic testimonials
$testimonials_res = $mysqli->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3");
$testimonials = [];
if ($testimonials_res) {
    while($row = $testimonials_res->fetch_assoc()) $testimonials[] = $row;
}

// Fetch dynamic landing features
$features_res = $mysqli->query("SELECT * FROM landing_features ORDER BY sort_order ASC, id ASC LIMIT 6");
$features = [];
if ($features_res) {
    while($row = $features_res->fetch_assoc()) $features[] = $row;
}

// Fetch dynamic services
$featured_services = services_paginated(1, 8, ['status' => 'published']);

$testi_head_res = $mysqli->query("SELECT * FROM testimonials WHERE is_active = 1 and stars>=4 ORDER BY RAND()  LIMIT 1");
$testi_head = [];
if ($testi_head_res) {
    while($row = $testi_head_res->fetch_assoc()) $testi_head[] = $row;
}


include __DIR__ . '/partials/frontend_header.php';
?>

  <main>
    <!-- Hero -->
    <section class="hero-section d-flex align-items-center bg-white">
      <div class="hero-blob" style="top:-12rem;right:-12rem;width:680px;height:680px;background:radial-gradient(circle,rgba(17,101,239,0.07),rgba(112,165,247,0.03),transparent 70%)"></div>
      <div class="container-xl py-5 position-relative">
        <div class="row align-items-center g-5">
          <div class="col-lg-6 fade-in">
            <div class="badge-gw d-inline-flex align-items-center gap-2 mb-4">
              <span class="rounded-circle bg-primary" style="width:6px;height:6px"></span>
              Consultancy Agency
            </div>
            <h1 class="font-serif mb-4" style="font-size:clamp(2.6rem,5vw,4.2rem);line-height:1.04">
              <span class="text-gradient-blue"><?= htmlspecialchars($s['hero_title_gradient'] ?? 'Measurable') ?></span><br>
              <?= nl2br(htmlspecialchars($s['hero_title_rest'] ?? "Performance for\nbusinesses")) ?>
            </h1>
            <p class="text-secondary mb-4 pe-lg-4"><?= htmlspecialchars($s['hero_subtitle'] ?? 'GlobalWays is a marketplace built for teams that need direction, structure, and execution — not vague advice or long decks. Compare 500+ verified vendors and get guaranteed UAE results.') ?></p>
            <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
              <a href="<?= htmlspecialchars($s['hero_cta_url'] ?? 'services.php') ?>" class="btn btn-gw-dark"><?= htmlspecialchars($s['hero_cta_text'] ?? 'Start a Free Meeting') ?> <i class="bi bi-arrow-right ms-1"></i></a>
              <div class="d-flex align-items-center gap-2">
                <div class="d-flex" style="margin-left:-0.5rem">
                  <span class="avatar-circle border border-2 border-white" style="background:#1165EF;margin-left:-0.5rem">AH</span>
                  <span class="avatar-circle border border-2 border-white" style="background:#3F83F4;margin-left:-0.5rem">ST</span>
                  <span class="avatar-circle border border-2 border-white" style="background:#70A5F7;margin-left:-0.5rem">PS</span>
                </div>
                <span class="font-mono text-uppercase text-muted" style="font-size:0.65rem;letter-spacing:0.15em">Trusted worldwide</span>
              </div>
            </div>
            <form class="hero-search d-flex align-items-center bg-white shadow-sm" action="services.php">
              <i class="bi bi-search text-muted ms-3"></i>
              <input type="search" name="q" class="form-control" placeholder="Search for Golden Visa, Business Setup…">
              <button type="submit" class="btn btn-gw-blue m-1">Search</button>
            </form>
          </div>
          <div class="col-lg-6 d-none d-lg-block position-relative fade-in">
            <div class="rounded-4 overflow-hidden" style="height:520px">
              <img src="<?= htmlspecialchars($s['hero_bg_image'] ?? 'https://images.unsplash.com/photo-1713947506827-c646da3ad1db?w=900&q=85') ?>" alt="Professional consultation" class="w-100 h-100 object-fit-cover">
            </div>
            <div class="card review-card-float shadow-lg p-4">
              <div class="text-warning mb-2">
                  <?php for($i=0; $i<$testi_head[0]['stars']; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
            </div>
              <p class="small mb-3">"<?= htmlspecialchars($testi_head[0]['quote']) ?>"</p>
              <div class="d-flex align-items-center gap-2">
                <span class="avatar-circle" style="background:linear-gradient(135deg,#1165EF,#3F83F4)"><?= htmlspecialchars($testi_head[0]['avatar_text']) ?></span>
                <div>
                  <div class="small fw-bold"><?= htmlspecialchars($testi_head[0]['client_name']) ?></div>
                  <div class="font-mono text-muted" style="font-size:0.65rem"><?= htmlspecialchars($testi_head[0]['client_role']) ?></div>
                </div>
              </div>
            </div>
            <div class="card vendor-badge-float shadow px-3 py-2">
              <span class="d-flex align-items-center gap-2 small font-mono"><span class="rounded-circle bg-primary" style="width:8px;height:8px"></span><?= htmlspecialchars($s['stat_card1_number'] ?? '500+') ?> Live Vendors</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Trust bar -->
    <section class="trust-bar py-3">
      <div class="container-xl">
        <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 gap-md-4">
          <span class="font-mono text-muted" style="font-size:0.625rem;letter-spacing:0.18em">RECOGNIZED BY</span>
          <?php
          $partners_str = $s['trust_bar_partners'] ?? 'Dubai Economy, GDRFA, Ministry of Labour, MOHRE, AMER Centers, Tas\'heel, ICP UAE';
          $partners = array_map('trim', explode(',', $partners_str));
          foreach ($partners as $partner) {
              if ($partner !== '') {
                  echo '<span class="text-muted small fw-medium">' . htmlspecialchars($partner) . '</span>';
              }
          }
          ?>
        </div>
      </div>
    </section>

    <!-- Stats -->
    <section class="py-5 bg-white">
      <div class="container-xl py-4">
        <div class="row align-items-start mb-5 g-4">
          <div class="col-lg-8 fade-in">
            <p class="label-mono"><?= htmlspecialchars($s['stat_result_label'] ?? 'Consultancy Result') ?></p>
            <h2 class="font-serif" style="font-size:clamp(2rem,4vw,3.5rem);line-height:1.06;max-width:700px">
              <span class="text-gradient-blue"><?= htmlspecialchars($s['stat_result_heading_gradient'] ?? '99.8%') ?></span> <?= htmlspecialchars($s['stat_result_heading_rest'] ?? 'success rate across every UAE service. Once we verify your vendor, track your application, and secure your payment — friction disappears.') ?>
            </h2>
          </div>
          <div class="col-lg-4 fade-in">
            <a href="services.php" class="btn btn-gw-outline">Start a Free Meeting <i class="bi bi-arrow-right ms-1"></i></a>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-4 fade-in">
            <div class="stat-card bg-mint">
              <div class="stat-number"><?= htmlspecialchars($s['stat_card1_number'] ?? '500+') ?></div>
              <p class="small fw-semibold font-mono mb-3"><?= htmlspecialchars($s['stat_card1_label'] ?? 'Verified Partners') ?></p>
              <p class="small text-primary mb-0"><?= htmlspecialchars($s['stat_card1_desc'] ?? 'By connecting you with verified vendors, removing redundant searches, and aligning your needs around a unified marketplace model.') ?></p>
            </div>
          </div>
          <div class="col-md-4 fade-in">
            <div class="stat-card bg-warm border">
              <div class="stat-number"><?= htmlspecialchars($s['stat_card2_number'] ?? '3x') ?></div>
              <p class="small fw-semibold font-mono mb-3"><?= htmlspecialchars($s['stat_card2_label'] ?? 'Faster Processing') ?></p>
              <p class="small text-secondary mb-0"><?= htmlspecialchars($s['stat_card2_desc'] ?? 'Our framework reduces ambiguity and brings clarity to every layer of the application.') ?></p>
            </div>
          </div>
          <div class="col-md-4 fade-in">
            <div class="stat-card bg-blk text-white">
              <div class="stat-number text-white"><?= htmlspecialchars($s['stat_card3_number'] ?? '150+') ?></div>
              <p class="small fw-semibold font-mono mb-3 text-white-50"><?= htmlspecialchars($s['stat_card3_label'] ?? 'Supported Globally') ?></p>
              <p class="small text-white-50 mb-0"><?= htmlspecialchars($s['stat_card3_desc'] ?? 'We\'ve worked with customers across SaaS, fintech, agencies, and high-growth companies worldwide.') ?></p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Services -->
    <section class="py-5 bg-warm">
      <div class="container-xl py-3">
        <div class="d-flex justify-content-between align-items-end mb-5 fade-in">
          <div>
            <p class="label-mono">All Services</p>
            <h2 class="font-serif mb-0" style="font-size:clamp(2rem,3.5vw,2.8rem)">Everything You Need,<br><span class="text-gradient-blue">All In One Place</span></h2>
          </div>
          <a href="services.php" class="font-mono small text-decoration-none text-dark d-none d-sm-inline">View all <i class="bi bi-arrow-up-right"></i></a>
        </div>
        <div class="row g-3">
          <?php foreach ($featured_services as $service): ?>
            <div class="col-sm-6 col-lg-3 fade-in">
              <a href="service-detail.php?id=<?= htmlspecialchars($service['slug']) ?>&uuid=<?= htmlspecialchars($service['uuid']) ?>" class="service-card">
                <div class="service-icon"><i class="bi <?= htmlspecialchars($service['icon_class'] ?? 'bi-award') ?>"></i></div>
                <h3 class="h6 font-serif"><?= htmlspecialchars($service['title']) ?></h3>
                <p class="small text-secondary"><?= htmlspecialchars($service['short_description']) ?></p>
                <div class="d-flex justify-content-between border-top pt-3 mt-3">
                  <span class="small fw-medium">From <?= htmlspecialchars($service['currency'] ?? 'AED') ?> <?= number_format($service['price'] ?? 0, 0) ?></span>
                  <span class="small text-muted font-mono"><i class="bi bi-clock"></i> <?= htmlspecialchars($service['duration_text'] ?? '5–7 days') ?></span>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section class="py-5 bg-white">
      <div class="container-xl py-3">
        <div class="mb-5 fade-in">
          <p class="label-mono">Platform</p>
          <h2 class="font-serif" style="font-size:clamp(2rem,3.5vw,2.8rem)">Built for<br><span class="text-gradient-blue">Peace of Mind</span></h2>
        </div>
        <div class="row g-3">
          <?php foreach ($features as $f): ?>
            <div class="col-md-6 col-lg-4 fade-in">
              <div class="feature-card d-flex gap-3">
                <div class="service-icon flex-shrink-0 bg-warm"><i class="bi <?= htmlspecialchars($f['icon_class'] ?? 'bi-star') ?>"></i></div>
                <div>
                  <h3 class="h6 font-serif"><?= htmlspecialchars($f['title']) ?></h3>
                  <p class="small text-secondary mb-0"><?= htmlspecialchars($f['description']) ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="py-5 bg-warm">
      <div class="container-xl py-3">
        <div class="text-center mb-5 fade-in">
          <div class="text-warning mb-3">
            <i class="bi bi-star-fill fs-5"></i><i class="bi bi-star-fill fs-5"></i><i class="bi bi-star-fill fs-5"></i><i class="bi bi-star-fill fs-5"></i><i class="bi bi-star-fill fs-5"></i>
          </div>
          <p class="label-mono">Success Stories</p>
          <h2 class="font-serif" style="font-size:clamp(2rem,3.5vw,2.8rem)"><span class="text-gradient-blue">Success Validated</span><br>by Our Clients</h2>
        </div>
        <div class="row g-3">
          <?php foreach ($testimonials as $t): ?>
            <div class="col-md-6 col-lg-4 fade-in">
              <div class="testimonial-card">
                <div class="text-warning small mb-3">
                  <?php for($i=0; $i<intval($t['stars'] ?? 5); $i++): ?>
                    <i class="bi bi-star-fill"></i>
                  <?php endfor; ?>
                </div>
                <p class="small fst-italic mb-4">"<?= htmlspecialchars($t['quote']) ?>"</p>
                <div class="d-flex align-items-center gap-3 border-top pt-3">
                  <span class="avatar-circle bg-blk"><?= htmlspecialchars($t['avatar_text'] ?? 'SA') ?></span>
                  <div>
                    <div class="small fw-medium font-serif"><?= htmlspecialchars($t['client_name']) ?></div>
                    <div class="font-mono text-muted" style="font-size:0.65rem">
                      <?= htmlspecialchars($t['client_role']) ?> · <?= htmlspecialchars($t['client_location']) ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="cta-section d-flex align-items-center justify-content-center text-center text-white" style="background-image:url('<?= htmlspecialchars($s['cta_banner_bg'] ?? 'https://images.unsplash.com/photo-1539630417222-d685b659ffcc?w=1400&q=85') ?>')">
      <div class="cta-overlay"></div>
      <div class="container position-relative py-5 fade-in">
        <p class="font-mono small text-white-50 mb-3" style="letter-spacing:0.18em">GlobalWays® is the most trusted UAE marketplace</p>
        <h2 class="font-serif mb-4" style="font-size:clamp(2.2rem,5vw,4rem);max-width:700px;margin:0 auto"><?= htmlspecialchars($s['cta_banner_title'] ?? 'Ready to take your UAE journey with us!') ?></h2>
        <a href="register.php" class="btn btn-gw-blue btn-lg">Start a Free Meeting <i class="bi bi-arrow-right ms-1"></i></a>
      </div>
    </section>
  </main>

<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
