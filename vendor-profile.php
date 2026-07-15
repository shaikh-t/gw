<?php
// vendor-profile.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/providers_helpers.php';

$id_val = $_GET['id'] ?? '';
$provider = null;

if ($id_val !== '') {
    $provider = provider_find($id_val);
}

// Fallback to first provider if not found
if (!$provider) {
    $res = $mysqli->query("SELECT * FROM providers LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $provider = provider_find($res->fetch_assoc()['id']);
    }
}

if (!$provider) {
    http_response_code(404);
    echo "Vendor not found";
    exit;
}

// Fetch dynamic services offered by this provider
$p_id = intval($provider['id']);
$s_sql = "SELECT s.*, c.name as category_name
          FROM services s
          LEFT JOIN service_categories c ON c.id = s.category_id
          WHERE s.provider_id = $p_id AND s.status = 'published'
          ORDER BY s.created_at DESC";
$res_services = $mysqli->query($s_sql);
$services = [];
if ($res_services) {
    while($row = $res_services->fetch_assoc()) $services[] = $row;
    $res_services->free();
}

// Fetch actual reviews for this provider
$r_sql = "SELECT r.*, u.name as user_name
          FROM reviews r
          JOIN users u ON u.id = r.user_id
          WHERE r.provider_id = $p_id AND r.status = 'published'
          ORDER BY r.created_at DESC";
$res_reviews = $mysqli->query($r_sql);
$reviews = [];
if ($res_reviews) {
    while($row = $res_reviews->fetch_assoc()) $reviews[] = $row;
    $res_reviews->free();
}

// Extract initials for logo placeholder
$words = explode(' ', $provider['name']);
$initials = '';
foreach ($words as $w) {
    $initials .= mb_substr($w, 0, 1);
}
$initials = mb_strtoupper(mb_substr($initials, 0, 2));

// Stats & Fallback Meta
$rating_avg = !empty($provider['rating_avg']) ? round($provider['rating_avg'], 1) : '4.8';
$rating_count = !empty($provider['rating_count']) ? $provider['rating_count'] : count($reviews);
if ($rating_count === 0 && !empty($reviews)) {
    $rating_count = count($reviews);
}
$languages = !empty($provider['languages']) ? $provider['languages'] : 'English, Arabic, Hindi, Urdu';
$team_size = !empty($provider['team_size']) ? $provider['team_size'] : '12';
$city = !empty($provider['city']) ? $provider['city'] : 'Dubai';
$since_year = !empty($provider['created_at']) ? date('Y', strtotime($provider['created_at'])) : '2020';

// Fetch actual team members for this provider
$p_id_int = intval($provider['id']);
$team_res = $mysqli->query("SELECT * FROM provider_team_members WHERE provider_id = $p_id_int ORDER BY id ASC");
$provider_team = [];
if ($team_res) {
    while ($tr = $team_res->fetch_assoc()) $provider_team[] = $tr;
    $team_res->free();
}

include __DIR__ . '/partials/frontend_header.php';
?>

  <main>
    <!-- Dark hero -->
    <section class="vp-hero">
      <div class="container-xl">
        <a href="vendors.php" class="vp-back">← Back to Vendors</a>

        <div class="vp-hero-main fade-in">
          <?php if (!empty($provider['logo'])): ?>
            <img src="<?= htmlspecialchars($domain.$provider['logo']) ?>" style="width:80px;height:80px;object-fit:contain;border-radius:50%;margin-right:20px;border:3px solid rgba(255,255,255,0.2);">
          <?php else: ?>
            <div class="vp-avatar" id="vendorAvatar"><?= htmlspecialchars($initials) ?></div>
          <?php endif; ?>
          <div class="vp-hero-info">
            <div class="vp-title-row">
              <h1 class="vp-title font-serif" id="vendorTitle"><?= htmlspecialchars($provider['name']) ?></h1>
              <?php if ($provider['verification_status'] === 'verified'): ?>
                <span class="vp-verified"><i class="bi bi-patch-check-fill"></i> Verified Partner</span>
              <?php endif; ?>
            </div>
            <p class="vp-tagline" id="vendorTagline">Your Trusted Partner for UAE Documentation</p>
            <div class="vp-meta" id="vendorMeta">
              <span class="vp-stars">
                <?php
                  $full_stars = floor($rating_avg);
                  for ($i=1; $i<=5; $i++) {
                      if ($i <= $full_stars) echo '<i class="bi bi-star-fill text-warning"></i>';
                      else echo '<i class="bi bi-star"></i>';
                  }
                ?>
                <strong><?= htmlspecialchars($rating_avg) ?></strong> (<?= htmlspecialchars($rating_count) ?> reviews)
              </span>
              <span class="vp-meta-sep">·</span>
              <span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($city) ?>, UAE</span>
              <span class="vp-meta-sep">·</span>
              <span>Since <?= htmlspecialchars($since_year) ?></span>
            </div>
          </div>
        </div>

        <div class="vp-hero-stats fade-in">
          <div class="vp-stat vp-stat-blue">
            <strong id="vendorTasks">5,420</strong>
            <span>Total Tasks</span>
          </div>
          <div class="vp-stat vp-stat-light">
            <strong id="vendorSuccess">99.8%</strong>
            <span>Success Rate</span>
          </div>
          <div class="vp-stat vp-stat-dark">
            <strong id="vendorResponse">&lt; 2 hours</strong>
            <span>Avg. Response</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Content -->
    <section class="vp-body">
      <div class="container-xl">
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="vp-card fade-in">
              <p class="vp-section-kicker">About</p>
              <p class="vp-card-text" id="vendorBio"><?= nl2br(htmlspecialchars($provider['description'])) ?></p>
            </div>

            <div class="vp-card fade-in">
              <p class="vp-section-kicker">What We Offer</p>
              <h2 class="vp-card-title font-serif">Services Offered</h2>
              <div class="vp-services-grid" id="vendorServices">
                <?php if (empty($services)): ?>
                  <p class="text-muted small">No services listed yet.</p>
                <?php else: ?>
                  <?php foreach ($services as $svc):
                      $price_text = !empty($svc['price']) ? htmlspecialchars($svc['currency'] . ' ' . number_format($svc['price'])) : 'Price on request';
                      $time_text = !empty($svc['duration_minutes']) ? htmlspecialchars($svc['duration_minutes'] . ' mins') : '3–5 days';
                  ?>
                    <a href="service-detail.php?id=<?= htmlspecialchars($svc['slug']) ?>" class="vp-service-card">
                      <strong><?= htmlspecialchars($svc['title']) ?></strong>
                      <div class="vp-service-meta">
                        <div class="vp-service-row"><span class="vp-service-label">Price</span><span class="vp-service-value"><?= $price_text ?></span></div>
                        <div class="vp-service-row"><span class="vp-service-label">Timeline</span><span class="vp-service-value"><?= $time_text ?></span></div>
                        <div class="vp-service-row"><span class="vp-service-label">Category</span><span class="vp-service-value"><?= htmlspecialchars($svc['category_name'] ?? 'General') ?></span></div>
                      </div>
                    </a>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <div class="vp-card fade-in">
              <p class="vp-section-kicker">What Customers Say</p>
              <h2 class="vp-card-title font-serif">Customer Reviews</h2>
              <div class="vp-reviews" id="vendorReviews">
                <?php if (empty($reviews)): ?>
                  <p class="text-muted">No reviews received yet.</p>
                <?php else: ?>
                  <?php foreach ($reviews as $rev): ?>
                    <article class="vp-review">
                      <div class="vp-review-top">
                        <div>
                          <strong><?= htmlspecialchars($rev['user_name']) ?></strong>
                          <span class="vp-review-meta"><?= htmlspecialchars(date('M d, Y', strtotime($rev['created_at']))) ?></span>
                        </div>
                        <div class="vp-review-stars">
                          <?php for ($i=0; $i<$rev['rating']; $i++): ?><i class="bi bi-star-fill text-warning"></i><?php endfor; ?>
                          <?php for ($i=$rev['rating']; $i<5; $i++): ?><i class="bi bi-star"></i><?php endfor; ?>
                        </div>
                      </div>
                      <?php if (!empty($rev['title'])): ?>
                        <h6 class="font-serif mb-1"><?= htmlspecialchars($rev['title']) ?></h6>
                      <?php endif; ?>
                      <p><?= nl2br(htmlspecialchars($rev['body'])) ?></p>
                    </article>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <?php if (!empty($provider_team)): ?>
            <div class="vp-card fade-in" id="vendorTeam">
              <p class="vp-section-kicker">The People</p>
              <h2 class="vp-card-title font-serif">Our Team</h2>
              <div class="vp-team-grid" id="vendorTeamGrid">
                <?php foreach ($provider_team as $tm):
                    $words = explode(' ', $tm['name']);
                    $initials = '';
                    foreach ($words as $w) $initials .= mb_substr($w, 0, 1);
                    $initials = mb_strtoupper(mb_substr($initials, 0, 2));
                ?>
                  <div class="vp-team-card">
                    <?php if (!empty($tm['avatar'])): ?>
                      <img src="<?= htmlspecialchars($domain . $tm['avatar']) ?>" class="avatar-circle mb-2" style="width:50px;height:50px;object-fit:cover;border-radius:50%;margin:0 auto 10px auto;">
                    <?php else: ?>
                      <span class="vp-team-avatar"><?= htmlspecialchars($initials) ?></span>
                    <?php endif; ?>
                    <strong class="font-serif"><?= htmlspecialchars($tm['name']) ?></strong>
                    <span class="vp-team-role"><?= htmlspecialchars($tm['role']) ?></span>
                    <span class="vp-team-specialties"><?= htmlspecialchars($tm['specialties']) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <div class="col-lg-4">
            <div class="vp-sidebar-card fade-in">
              <p class="vp-section-kicker">Get in Touch</p>
              <h3 class="vp-sidebar-title font-serif">Contact Vendor</h3>
              <ul class="vp-contact-list" id="vendorContact">
                <li><i class="bi bi-geo-alt"></i><span><?= htmlspecialchars($city) ?>, UAE</span></li>
                <li><i class="bi bi-calendar3"></i><span>Member since <?= htmlspecialchars($since_year) ?></span></li>
                <li><i class="bi bi-clock"></i><span>Responds in &lt; 2 hours</span></li>
                <li><i class="bi bi-people"></i><span><?= htmlspecialchars($languages) ?></span></li>
              </ul>
              <a href="create-case.php?vendor_id=<?= htmlspecialchars($provider['uuid']) ?>" class="vp-btn-primary"><i class="bi bi-chat-dots"></i> Request Quote</a>
              <a href="#vendorTeam" class="vp-btn-secondary"><i class="bi bi-globe"></i> View Profile</a>
            </div>

            <div class="vp-sidebar-card fade-in">
              <p class="vp-section-kicker">Trust &amp; Credentials</p>
              <h3 class="vp-sidebar-title font-serif">Certifications</h3>
              <div class="vp-cert-list" id="vendorCerts">
                <?php
                // Fetch public verified documents for this provider
                $p_id_int = intval($provider['id']);
                $doc_res = $mysqli->query("SELECT * FROM provider_documents WHERE provider_id = $p_id_int AND status = 'verified' AND show_on_frontend = 1");
                $public_docs = [];
                if ($doc_res) {
                    while ($dr = $doc_res->fetch_assoc()) $public_docs[] = $dr;
                    $doc_res->free();
                }
                ?>
                <?php if (empty($public_docs)): ?>
                  <div class="vp-cert-pill"><i class="bi bi-award"></i> Dubai Chamber Certified</div>
                  <div class="vp-cert-pill"><i class="bi bi-shield-check"></i> UAE Government Licensed</div>
                  <div class="vp-cert-pill"><i class="bi bi-patch-check"></i> GlobalWays Verified Partner</div>
                <?php else: ?>
                  <?php foreach ($public_docs as $pd): ?>
                    <div class="vp-cert-pill">
                      <i class="bi bi-patch-check-fill text-success"></i>
                      <a href="<?= htmlspecialchars($domain . $pd['file_path']) ?>" target="_blank" class="text-decoration-none text-dark fw-medium">
                        <?= htmlspecialchars($pd['title']) ?>
                      </a>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('vendor-profile-page');
    document.body.classList.add('has-custom-cursor');
    document.getElementById('gwNav').classList.add('dark-hero');
});
</script>
<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
