<?php
// how-it-works.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';

// Fetch How It Works page CMS content
$res = $mysqli->query("SELECT content FROM cms_pages WHERE page_name = 'how_it_works' LIMIT 1");
$cms = [];
if ($res && $row = $res->fetch_assoc()) {
    $cms = json_decode($row['content'], true) ?: [];
}

include __DIR__ . '/partials/frontend_header.php';
?>

  <main class="hiw-main">
    <section class="hiw-hero">
      <div class="container-xl">
        <div class="hiw-hero-inner fade-in">
          <p class="label-mono mb-2">How It Works</p>
          <h1 class="font-serif mb-2">From Application to<br>Approval in 4 Steps</h1>
          <p class="text-secondary mb-4">We've simplified UAE bureaucracy into a transparent, guaranteed process — whether you need a visa, a trade license, or an Emirates ID.</p>
          <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="register.html" class="btn btn-gw-dark">Get Started Free</a>
            <a href="services.html" class="btn btn-gw-outline">Browse Services</a>
          </div>
        </div>
      </div>
    </section>

    <section class="hiw-steps-wrap">
      <div class="container-xl">
        <?php foreach (($cms['steps'] ?? []) as $index => $step):
            $is_even = ($index % 2 === 1);
        ?>
          <div class="hiw-step-row fade-in">
            <span class="step-number-bg" aria-hidden="true"><?= htmlspecialchars($step['num'] ?? '') ?></span>
            <div class="row g-4 align-items-center <?= $is_even ? 'flex-lg-row-reverse' : '' ?>">
              <div class="col-lg-6">
                <article class="hiw-step-copy">
                  <span class="hiw-icon-pill"><i class="bi <?= htmlspecialchars($step['icon'] ?? 'bi-search') ?>"></i></span>
                  <h2 class="font-serif"><?= htmlspecialchars($step['title'] ?? '') ?></h2>
                  <p><?= htmlspecialchars($step['desc'] ?? '') ?></p>
                  <ul class="list-unstyled">
                    <?php foreach (($step['bullets'] ?? []) as $bullet): ?>
                      <li><i class="bi bi-check2-circle"></i><?= htmlspecialchars($bullet) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </article>
              </div>
              <div class="col-lg-6">
                <div class="step-mockup hiw-mini-board">
                  <p class="label-mono hiw-card-label"><?= htmlspecialchars($step['mockup_label'] ?? 'Details') ?></p>

                  <?php if (!empty($step['mockup_items'])): ?>
                    <?php foreach ($step['mockup_items'] as $m_item): ?>
                      <div class="hiw-mini-row <?= !empty($m_item['active']) ? 'active' : '' ?>">
                        <span class="avatar-mini"><?= htmlspecialchars($m_item['avatar'] ?? 'A') ?></span>
                        <span>
                          <strong><?= htmlspecialchars($m_item['name'] ?? '') ?></strong>
                          <small><?= htmlspecialchars($m_item['sub'] ?? '') ?></small>
                        </span>
                        <span class="rating-mini"><i class="bi bi-star-fill"></i><?= htmlspecialchars($m_item['rating'] ?? '4.9') ?></span>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>

                  <?php if (!empty($step['mockup_title'])): ?>
                    <p class="hiw-mini-title"><?= htmlspecialchars($step['mockup_title']) ?></p>
                    <?php foreach (($step['mockup_lines'] ?? []) as $m_line): ?>
                      <div class="hiw-pay-line"><span><?= htmlspecialchars($m_line['label'] ?? '') ?></span><strong><?= htmlspecialchars($m_line['amount'] ?? '') ?></strong></div>
                    <?php endforeach; ?>
                    <div class="hiw-pay-line total"><span>Total</span><strong><?= htmlspecialchars($step['mockup_total'] ?? '') ?></strong></div>
                    <div class="hiw-escrow-pill"><i class="bi bi-shield-lock"></i>Held in escrow until you confirm delivery</div>
                  <?php endif; ?>

                  <?php if (!empty($step['mockup_statuses'])): ?>
                    <?php foreach ($step['mockup_statuses'] as $m_status): ?>
                      <div class="hiw-status-row <?= htmlspecialchars($m_status['status'] ?? '') ?>"><?= htmlspecialchars($m_status['label'] ?? '') ?></div>
                    <?php endforeach; ?>
                  <?php endif; ?>

                  <?php if (!empty($step['mockup_docs'])): ?>
                    <?php foreach ($step['mockup_docs'] as $m_doc): ?>
                      <div class="hiw-doc-row"><span><?= htmlspecialchars($m_doc['name'] ?? '') ?></span><small><?= htmlspecialchars($m_doc['expires'] ?? '') ?></small></div>
                    <?php endforeach; ?>
                  <?php endif; ?>

                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="hiw-vendor-panel">
      <div class="container-xl">
        <div class="vendor-join-section fade-in">
          <div class="text-center mb-4">
            <p class="label-mono label-mono-light"><?= htmlspecialchars($cms['vendor_kicker'] ?? 'For Vendors') ?></p>
            <h2 class="font-serif mb-2"><?= htmlspecialchars($cms['vendor_title'] ?? '') ?></h2>
            <p class="text-white-50 mb-0"><?= htmlspecialchars($cms['vendor_sub'] ?? '') ?></p>
          </div>
          <div class="row g-3 mb-4">
            <?php foreach (($cms['vendor_steps'] ?? []) as $v_step): ?>
              <div class="col-sm-6 col-lg-3">
                <div class="about-stat-card h-100">
                  <i class="bi <?= htmlspecialchars($v_step['icon'] ?? 'bi-globe2') ?>"></i>
                  <p class="vendor-step-kicker"><?= htmlspecialchars($v_step['step'] ?? '') ?></p>
                  <h3><?= htmlspecialchars($v_step['title'] ?? '') ?></h3>
                  <p><?= htmlspecialchars($v_step['desc'] ?? '') ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="text-center"><a href="vendor-onboard.php" class="btn btn-gw-blue">Apply to Become a Vendor <i class="bi bi-arrow-right ms-1"></i></a></div>
        </div>
      </div>
    </section>

    <section class="final-cta-black d-flex align-items-center justify-content-center text-center text-white">
      <div class="container position-relative py-5 fade-in">
        <p class="label-mono label-mono-light mb-2"><?= htmlspecialchars($cms['cta_kicker'] ?? 'Ready to Start?') ?></p>
        <h2 class="font-serif mb-3"><?= htmlspecialchars($cms['cta_title'] ?? 'UAE Documentation, Simplified.') ?></h2>
        <p class="text-white-50 mb-4"><?= htmlspecialchars($cms['cta_sub'] ?? '') ?></p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
          <a href="services.php" class="btn btn-gw-blue">Explore Services</a>
          <a href="vendors.php" class="btn btn-gw-outline text-white border-white">Browse Vendors</a>
        </div>
      </div>
    </section>
  </main>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('how-it-works-page');
    document.body.classList.add('has-custom-cursor');
});
</script>
<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
