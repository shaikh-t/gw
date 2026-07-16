<?php
// about.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';

// Fetch About page CMS content
$res = $mysqli->query("SELECT content FROM cms_pages WHERE page_name = 'about' LIMIT 1");
$cms = [];
if ($res && $row = $res->fetch_assoc()) {
    $cms = json_decode($row['content'], true) ?: [];
}

include __DIR__ . '/partials/frontend_header.php';
?>

  <main>
    <section class="about-hero">
      <div class="container-xl">
        <div class="row align-items-center g-4 g-xl-5">
          <div class="col-lg-6">
            <p class="about-hero-kicker"><?= htmlspecialchars($cms['story_kicker'] ?? 'Our Story') ?></p>
            <h1 class="about-hero-title font-serif"><?= htmlspecialchars($cms['story_title'] ?? '') ?></h1>
            <p class="about-hero-sub"><?= htmlspecialchars($cms['story_sub'] ?? '') ?></p>
            <a href="<?= htmlspecialchars($cms['story_cta_url'] ?? 'services.php') ?>" class="btn about-hero-cta"><?= htmlspecialchars($cms['story_cta_text'] ?? 'Explore Services') ?> <i class="bi bi-arrow-right ms-1"></i></a>
          </div>
          <div class="col-lg-6">
            <div class="about-hero-stats">
              <?php foreach (($cms['stats'] ?? []) as $index => $stat): ?>
                <div class="about-hero-stat <?= !empty($stat['highlight']) ? 'about-hero-stat-highlight' : '' ?>">
                  <strong><?= htmlspecialchars($stat['number'] ?? '') ?></strong>
                  <span><?= htmlspecialchars($stat['label'] ?? '') ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="about-values-section">
      <div class="container-xl">
        <div class="row g-4 g-xl-5 align-items-center">
          <div class="col-lg-6 fade-in">
            <p class="about-values-kicker"><?= htmlspecialchars($cms['mission_kicker'] ?? 'Our Mission') ?></p>
            <h2 class="about-values-title font-serif"><?= htmlspecialchars($cms['mission_title'] ?? '') ?></h2>
            <p class="about-values-copy"><?= htmlspecialchars($cms['mission_copy'] ?? '') ?></p>
            <a href="services.php" class="btn btn-gw-dark about-values-cta">See All Services <i class="bi bi-arrow-right ms-1"></i></a>
          </div>
          <div class="col-lg-6 fade-in">
            <div class="about-proof-list">
              <?php foreach (($cms['mission_proof'] ?? []) as $proof): ?>
                <div class="about-proof-item">
                  <span class="about-proof-icon"><i class="bi bi-check2"></i></span>
                  <p><?= htmlspecialchars($proof) ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="about-stand-section">
      <div class="container-xl">
        <div class="about-stand-head fade-in">
          <p class="about-stand-kicker"><?= htmlspecialchars($cms['values_kicker'] ?? 'What We Stand For') ?></p>
          <h2 class="about-stand-title font-serif"><?= htmlspecialchars($cms['values_title'] ?? 'Our Values') ?></h2>
        </div>
        <div class="row g-3 g-lg-4">
          <?php foreach (($cms['values'] ?? []) as $val): ?>
            <div class="col-sm-6 col-lg-3 fade-in">
              <article class="about-stand-card">
                <span class="about-stand-icon"><i class="bi <?= htmlspecialchars($val['icon'] ?? 'bi-shield') ?>"></i></span>
                <h3><?= htmlspecialchars($val['title'] ?? '') ?></h3>
                <p><?= htmlspecialchars($val['desc'] ?? '') ?></p>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="about-journey-section">
      <div class="container-xl">
        <div class="about-journey-head fade-in">
          <span class="about-journey-dot" aria-hidden="true"></span>
          <p class="about-journey-kicker"><?= htmlspecialchars($cms['journey_kicker'] ?? 'Our Journey') ?></p>
          <h2 class="about-journey-title font-serif"><?= htmlspecialchars($cms['journey_title'] ?? 'Our Journey') ?></h2>
        </div>
        <div class="about-journey-list fade-in">
          <?php foreach (($cms['journey'] ?? []) as $journey): ?>
            <div class="about-journey-row">
              <span class="about-journey-year"><?= htmlspecialchars($journey['year'] ?? '') ?></span>
              <div class="about-journey-card">
                <h3><?= htmlspecialchars($journey['title'] ?? '') ?></h3>
                <p><?= htmlspecialchars($journey['desc'] ?? '') ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Bottom CTA banner -->
    <?php
      require_once __DIR__ . '/lib/settings_helper.php';
      $s = get_all_settings();
    ?>
    <section class="cta-section d-flex align-items-center justify-content-center text-center text-white" style="background-image:url('<?= htmlspecialchars($s['cta_banner_bg'] ?? '') ?>')">
      <div class="cta-overlay"></div>
      <div class="container position-relative py-5 fade-in">
        <h2 class="font-serif mb-3" style="font-size:clamp(1.8rem,3.5vw,2.8rem)"><?= htmlspecialchars($s['cta_banner_title'] ?? '') ?></h2>
        <p class="text-white-50 mb-4 col-lg-6 mx-auto">Start your UAE journey today with verified vendors, escrow protection, and real-time tracking.</p>
        <a href="login.php" class="btn btn-gw-blue btn-lg">Get Started Free <i class="bi bi-arrow-right ms-1"></i></a>
      </div>
    </section>
  </main>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('about-page');
    // document.body.classList.add('has-custom-cursor');
    document.getElementById('gwNav').classList.add('dark-hero');
});
</script>
<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
