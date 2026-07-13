<?php
require_once __DIR__ . '/lib/settings_helper.php';
require_once __DIR__ . '/lib/services_helpers.php';
require_once __DIR__ . '/lib/db_mysqli.php';

$s = get_all_settings();

// Fetch latest featured services
$featured_services = services_paginated(1, 4, ['status' => 'published']);

// Fetch active features
$features_res = $mysqli->query("SELECT * FROM landing_features ORDER BY sort_order ASC LIMIT 6");
$features = [];
if ($features_res) {
    while($row = $features_res->fetch_assoc()) $features[] = $row;
}

// Fetch active testimonials
$testimonials_res = $mysqli->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3");
$testimonials = [];
if ($testimonials_res) {
    while($row = $testimonials_res->fetch_assoc()) $testimonials[] = $row;
}

$testi_head_res = $mysqli->query("SELECT * FROM testimonials WHERE is_active = 1 and stars>=4 ORDER BY RAND()  LIMIT 1");
$testi_head = [];
if ($testi_head_res) {
    while($row = $testi_head_res->fetch_assoc()) $testi_head[] = $row;
}


include __DIR__ . '/partials/frontend_header.php';
?>

  <main>
    <!-- Hero Section -->
    <!-- <section class="hero-section d-flex align-items-center" style="background-image:url('<?= htmlspecialchars($s['hero_bg_image'] ?? '') ?>')">
      <div class="hero-overlay"></div>
      <div class="container position-relative z-1 py-5">
        <div class="row">
          <div class="col-lg-7 col-xl-6 py-5 fade-in">
            <h1 class="hero-title font-serif mb-4" style="font-size:clamp(2.5rem,5vw,4.2rem)"><?= htmlspecialchars($s['hero_title'] ?? '') ?></h1>
            <p class="hero-subtitle text-white-50 mb-5 fs-5 pe-lg-5"><?= htmlspecialchars($s['hero_subtitle'] ?? '') ?></p>
            <div class="d-flex flex-wrap gap-3">
              <a href="<?= htmlspecialchars($s['hero_cta_url'] ?? 'services.php') ?>" class="btn btn-gw-blue btn-lg px-4"><?= htmlspecialchars($s['hero_cta_text'] ?? 'Explore Services') ?> <i class="bi bi-arrow-right ms-1"></i></a>
              <a href="about.php" class="btn btn-outline-white btn-lg px-4">Our Approach</a>
            </div>
          </div>
        </div>
      </div>
    </section> -->
<section class="hero-section d-flex align-items-center bg-white">
      <div class="hero-blob" style="top:-12rem;right:-12rem;width:680px;height:680px;background:radial-gradient(circle,rgba(17,101,239,0.07),rgba(112,165,247,0.03),transparent 70%)"></div>
      <div class="container-xl py-5 position-relative fade-in visible">
        <div class="row align-items-center g-5">
          <div class="col-lg-6 fade-in visible">
            <div class="badge-gw d-inline-flex align-items-center gap-2 mb-4">
              <span class="rounded-circle bg-primary" style="width:6px;height:6px"></span>
              Consultancy Agency
            </div>
            <h1 class="font-serif mb-4" style="font-size:clamp(2.6rem,5vw,4.2rem);line-height:1.04">
             <?= htmlspecialchars($s['hero_title'] ?? '') ?>
            </h1>
            <p class="text-secondary mb-4 pe-lg-4">
          <?= htmlspecialchars($s['hero_subtitle'] ?? '') ?>  
          </p>
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
            <form class="hero-search d-flex align-items-center bg-white shadow-sm" action="services.html">
              <i class="bi bi-search text-muted ms-3"></i>
              <input type="search" class="form-control" placeholder="Search for Golden Visa, Business Setup…">
              <button type="submit" class="btn btn-gw-blue m-1">Search</button>
            </form>
          </div>
          <div class="col-lg-6 d-none d-lg-block position-relative fade-in visible">
            <div class="rounded-4 overflow-hidden" style="height:520px">
              <img src="<?= htmlspecialchars($s['hero_bg_image'] ?? '') ?>" alt="Professional consultation" class="w-100 h-100 object-fit-cover">
            </div>
            <!-- <?php echo '<pre>';
            print_r($testimonials);
            echo '</pre>';
            ?> -->
          <!-- <div class="col-md-6 col-lg-4 fade-in">
            <div class="testimonial-card">
              <div class="text-warning small mb-3">
                  <?php for($i=0; $i<$t['stars']; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
              </div>
              <p class="small fst-italic mb-4">"<?= htmlspecialchars($t['quote']) ?>"</p>
              <div class="d-flex align-items-center gap-3 border-top pt-3">
                <span class="avatar-circle bg-blk"><?= htmlspecialchars($t['avatar_text']) ?></span>
                <div><div class="small fw-medium font-serif"><?= htmlspecialchars($t['client_name']) ?></div><div class="font-mono text-muted" style="font-size:0.65rem"><?= htmlspecialchars($t['client_role']) ?> · <?= htmlspecialchars($t['client_location']) ?></div></div>
              </div>
            </div>
          </div> -->

          <?php foreach ($testi_head as $t): ?>
            <div class="card review-card-float shadow-lg p-4">
              <div class="text-warning mb-2">
                  <?php for($i=0; $i<$t['stars']; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
              <!-- <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> -->
            </div>
              <p class="small mb-3">"<?= htmlspecialchars($t['quote']) ?>"</p>
              <div class="d-flex align-items-center gap-2">
                <span class="avatar-circle" style="background:linear-gradient(135deg,#1165EF,#3F83F4)"><?= htmlspecialchars($t['avatar_text']) ?></span>
                <div>
                  <div class="small fw-bold"><?= htmlspecialchars($t['client_name']) ?></div>
                  <div class="font-mono text-muted" style="font-size:0.65rem"><?= htmlspecialchars($t['client_role']) ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
            
            <div class="card vendor-badge-float shadow px-3 py-2">
              <span class="d-flex align-items-center gap-2 small font-mono"><span class="rounded-circle bg-primary" style="width:8px;height:8px"></span>500+ Live Vendors</span>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- Stats Bar -->
    <div class="stats-bar py-4 bg-blk text-white">
      <div class="container-xl">
        <div class="row g-4 align-items-center">
          <div class="col-6 col-md-3 border-end border-white border-opacity-10"><div class="stat-item text-center"><div class="stat-number fw-bold"><?= htmlspecialchars($s['stat_vendors'] ?? '150+') ?></div><div class="stat-label small text-white-50">Vetted Vendors</div></div></div>
          <div class="col-6 col-md-3 border-md-end border-white border-opacity-10"><div class="stat-item text-center"><div class="stat-number fw-bold"><?= htmlspecialchars($s['stat_cases'] ?? '10K+') ?></div><div class="stat-label small text-white-50">Cases Resolved</div></div></div>
          <div class="col-6 col-md-3 border-end border-white border-opacity-10"><div class="stat-item text-center"><div class="stat-number fw-bold"><?= htmlspecialchars($s['stat_success_rate'] ?? '99.8%') ?></div><div class="stat-label small text-white-50">Success Rate</div></div></div>
          <div class="col-6 col-md-3"><div class="stat-item text-center"><div class="stat-number fw-bold">120+</div><div class="stat-label small text-white-50">Legal Specialties</div></div></div>
        </div>
      </div>
    </div>

    <!-- Marketplace Preview -->
    <section class="py-5">
      <div class="container-xl py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-5 gap-3 fade-in">
          <div><p class="label-mono">UAE Marketplace</p><h2 class="font-serif h1 mb-0">Discover Top-Tier<br>Services in One Place</h2></div>
          <a href="services.php" class="btn btn-outline-gw-blue rounded-pill px-4">View Full Marketplace <i class="bi bi-arrow-up-right ms-1"></i></a>
        </div>
        <div class="row g-4">
          <?php foreach ($featured_services as $service): ?>
          <div class="col-md-6 col-lg-3 fade-in">
            <div class="service-card-modern">
              <div class="service-card-img">
                <?php if(!empty($service['images'])): ?>
                    <img src="<?= htmlspecialchars($service['images'][0]) ?>" alt="<?= htmlspecialchars($service['title']) ?>">
                <?php else: ?>
                    <div class="bg-warm w-100 h-100 d-flex align-items-center justify-content-center"><i class="bi bi-image text-muted fs-1"></i></div>
                <?php endif; ?>
                <span class="service-tag"><?= htmlspecialchars($service['category_name'] ?? 'General') ?></span>
              </div>
              <div class="service-card-body p-4">
                <h3 class="h5 font-serif mb-2"><?= htmlspecialchars($service['title']) ?></h3>
                <p class="small text-secondary mb-4"><?= htmlspecialchars($service['short_description']) ?></p>
                <div class="d-flex justify-content-between align-items-center mt-auto">
                  <div class="service-price"><span class="label">Starting from</span><div class="amount"><?= $service['currency'] ?> <?= number_format($service['price'], 0) ?></div></div>
                  <a href="services/view.php?uuid=<?= $service['uuid'] ?>" class="btn-arrow"><i class="bi bi-arrow-right"></i></a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section class="py-5 bg-white border-top">
      <div class="container-xl py-5">
        <div class="text-center mb-5 fade-in">
          <p class="label-mono">The GW Edge</p>
          <h2 class="font-serif display-5">Why Work with Us?</h2>
        </div>
        <div class="row g-4 g-lg-5">
          <?php foreach ($features as $f): ?>
          <div class="col-md-6 col-lg-4 fade-in">
            <div class="feature-card d-flex gap-3">
              <div class="service-icon flex-shrink-0 bg-warm"><i class="bi <?= htmlspecialchars($f['icon_class']) ?>"></i></div>
              <div><h3 class="h6 font-serif"><?= htmlspecialchars($f['title']) ?></h3><p class="small text-secondary mb-0"><?= htmlspecialchars($f['description']) ?></p></div>
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
          <div class="text-warning mb-3"><i class="bi bi-star-fill fs-5"></i><i class="bi bi-star-fill fs-5"></i><i class="bi bi-star-fill fs-5"></i><i class="bi bi-star-fill fs-5"></i><i class="bi bi-star-fill fs-5"></i></div>
          <p class="label-mono">Success Stories</p>
          <h2 class="font-serif" style="font-size:clamp(2rem,3.5vw,2.8rem)">Success Validated<br>by Our Clients</h2>
        </div>
        <div class="row g-3">
          <?php foreach ($testimonials as $t): ?>
          <div class="col-md-6 col-lg-4 fade-in">
            <div class="testimonial-card">
              <div class="text-warning small mb-3">
                  <?php for($i=0; $i<$t['stars']; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
              </div>
              <p class="small fst-italic mb-4">"<?= htmlspecialchars($t['quote']) ?>"</p>
              <div class="d-flex align-items-center gap-3 border-top pt-3">
                <span class="avatar-circle bg-blk"><?= htmlspecialchars($t['avatar_text']) ?></span>
                <div><div class="small fw-medium font-serif"><?= htmlspecialchars($t['client_name']) ?></div><div class="font-mono text-muted" style="font-size:0.65rem"><?= htmlspecialchars($t['client_role']) ?> · <?= htmlspecialchars($t['client_location']) ?></div></div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="cta-section d-flex align-items-center justify-content-center text-center text-white" style="background-image:url('<?= htmlspecialchars($s['cta_banner_bg'] ?? '') ?>')">
      <div class="cta-overlay"></div>
      <div class="container position-relative py-5 fade-in">
        <p class="font-mono small text-white-50 mb-3" style="letter-spacing:0.18em">GlobalWays® is the most trusted UAE marketplace</p>
        <h2 class="font-serif mb-4" style="font-size:clamp(2.2rem,5vw,4rem);max-width:700px;margin:0 auto"><?= htmlspecialchars($s['cta_banner_title'] ?? '') ?></h2>
        <a href="login.php" class="btn btn-gw-blue btn-lg">Start a Free Meeting <i class="bi bi-arrow-right ms-1"></i></a>
      </div>
    </section>
  </main>

<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
