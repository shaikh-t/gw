<?php
// vendors.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';

// Fetch query filters
$q = trim($_GET['q'] ?? '');
$city = trim($_GET['city'] ?? 'All Cities');
$type = trim($_GET['type'] ?? 'All Types');
$verified = isset($_GET['verified']) && $_GET['verified'] == '1';
$top_rated = isset($_GET['top_rated']) && $_GET['top_rated'] == '1';
$min_rating = trim($_GET['rating'] ?? 'Any');
$selected_langs = isset($_GET['langs']) ? (array)$_GET['langs'] : [];
$member_since = trim($_GET['member_since'] ?? 'Any time');
$sort = trim($_GET['sort'] ?? 'Most Relevant');

$where = ["1=1"]; // always true base
$types = "";
$params = [];

// 1. Search Query
if ($q !== '') {
    $where[] = "(name LIKE ? OR description LIKE ? OR specialties LIKE ?)";
    $like_q = "%" . $q . "%";
    $types .= "sss";
    array_push($params, $like_q, $like_q, $like_q);
}

// 2. City Filter
if ($city !== 'All Cities') {
    $where[] = "city = ?";
    $types .= "s";
    $params[] = $city;
}

// 3. Specialty Type Filter
if ($type !== 'All Types') {
    $where[] = "specialties LIKE ?";
    $like_type = "%" . $type . "%";
    $types .= "s";
    $params[] = $like_type;
}

// 4. Verification Checkbox
if ($verified) {
    $where[] = "verification_status = 'verified'";
}

// 5. Top Rated Checkbox or rating selector
if ($top_rated) {
    $where[] = "rating_avg >= 4.8";
} elseif ($min_rating !== 'Any') {
    $rating_val = floatval(preg_replace('/[^0-9.]/', '', $min_rating));
    if ($rating_val > 0) {
        $where[] = "rating_avg >= ?";
        $types .= "d";
        $params[] = $rating_val;
    }
}

// 6. Languages Filter
if (!empty($selected_langs)) {
    $lang_clauses = [];
    foreach ($selected_langs as $lang) {
        $lang_clauses[] = "languages LIKE ?";
        $like_lang = "%" . $lang . "%";
        $types .= "s";
        $params[] = $like_lang;
    }
    $where[] = "(" . implode(' OR ', $lang_clauses) . ")";
}

// 7. Member Since Filter
if ($member_since === '2+ years') {
    $where[] = "created_at <= DATE_SUB(NOW(), INTERVAL 2 YEAR)";
} elseif ($member_since === '5+ years') {
    $where[] = "created_at <= DATE_SUB(NOW(), INTERVAL 5 YEAR)";
} elseif ($member_since === '10+ years') {
    $where[] = "created_at <= DATE_SUB(NOW(), INTERVAL 10 YEAR)";
}

$where_sql = implode(' AND ', $where);

// Sort Order
$order_by = "created_at DESC";
if ($sort === 'Highest Rated') {
    $order_by = "rating_avg DESC";
} elseif ($sort === 'Lowest Price') {
    $order_by = "starting_price ASC";
} elseif ($sort === 'Newest') {
    $order_by = "created_at DESC";
}

$sql = "SELECT * FROM providers WHERE $where_sql ORDER BY $order_by";
$stmt = $mysqli->prepare($sql);
$providers = [];
if ($stmt) {
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $providers[] = $row;
        }
        $res->free();
    }
    $stmt->close();
}

include __DIR__ . '/partials/frontend_header.php';
?>

  <main>
    <section class="page-hero vendors-hero">
      <div class="container-xl">
        <nav class="breadcrumb-gw" aria-label="Breadcrumb"><a href="index.php">Pages</a> / Vendors</nav>
        <h1 class="font-serif vendors-hero-title">Find Your Perfect Vendor</h1>
        <p class="vendors-hero-sub">Compare verified UAE service providers by rating, price and speciality. Every vendor is background-checked.</p>
        <form class="vendors-hero-search" action="vendors.php" method="get" role="search">
          <i class="bi bi-search"></i>
          <input type="search" name="q" placeholder="Search by name, service or speciality..." aria-label="Search vendors" value="<?= htmlspecialchars($q) ?>">
          <div class="vendors-hero-divider"></div>
          <select name="city" aria-label="Filter by city" onchange="this.form.submit()">
            <option value="All Cities" <?= $city === 'All Cities' ? 'selected' : '' ?>>All Cities</option>
            <option value="Dubai" <?= $city === 'Dubai' ? 'selected' : '' ?>>Dubai</option>
            <option value="Abu Dhabi" <?= $city === 'Abu Dhabi' ? 'selected' : '' ?>>Abu Dhabi</option>
            <option value="Sharjah" <?= $city === 'Sharjah' ? 'selected' : '' ?>>Sharjah</option>
            <option value="Ajman" <?= $city === 'Ajman' ? 'selected' : '' ?>>Ajman</option>
          </select>
        </form>
      </div>
    </section>

    <section class="vendors-list-section">
      <div class="container-xl">
        <div class="d-flex flex-wrap gap-2 vendors-type-tabs" role="group" aria-label="Filter by type">
          <?php
            $types = ['All Types', 'PRO Services', 'Business Setup', 'Visa & Immigration', 'Education Visa', 'Golden / Residential'];
            foreach ($types as $t_option):
          ?>
            <a href="vendors.php?type=<?= urlencode($t_option) ?>&q=<?= urlencode($q) ?>&city=<?= urlencode($city) ?>" class="filter-pill-btn text-decoration-none <?= $type === $t_option ? 'active' : '' ?>">
              <i class="bi bi-clipboard-check"></i> <?= htmlspecialchars($t_option) ?>
            </a>
          <?php endforeach; ?>
        </div>
        <hr class="section-tabs-divider">

        <div class="row vendors-layout g-0">
          <aside class="col-lg-3 vendors-sidebar-col">
            <form action="vendors.php" method="get" class="filter-sidebar vendors-filter-card fade-in">
              <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
              <input type="hidden" name="city" value="<?= htmlspecialchars($city) ?>">
              <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

              <h3>Verification</h3>
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="verified" value="1" id="verifiedOnly" <?= $verified ? 'checked' : '' ?> onchange="this.form.submit()">
                <label class="form-check-label" for="verifiedOnly">Verified only</label>
              </div>
              <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="top_rated" value="1" id="topRated" <?= $top_rated ? 'checked' : '' ?> onchange="this.form.submit()">
                <label class="form-check-label" for="topRated">Top Rated only</label>
              </div>

              <h3>Minimum Rating</h3>
              <div class="d-flex flex-wrap gap-2 mb-4 rating-pills">
                <?php
                  $ratings = ['Any', '4+', '4.5+', '4.8+'];
                  foreach ($ratings as $r_opt):
                ?>
                  <a href="vendors.php?rating=<?= urlencode($r_opt) ?>&q=<?= urlencode($q) ?>&city=<?= urlencode($city) ?>&type=<?= urlencode($type) ?>" class="filter-pill-btn text-decoration-none <?= $min_rating === $r_opt ? 'active' : '' ?>"><?= htmlspecialchars($r_opt) ?></a>
                <?php endforeach; ?>
              </div>

              <h3>Languages</h3>
              <div class="d-flex flex-wrap gap-2 mb-4 lang-pills">
                <?php
                  $langs = ['English', 'Arabic', 'Russian', 'Urdu', 'Tagalog', 'Chinese', 'French'];
                  foreach ($langs as $lang):
                      $is_selected = in_array($lang, $selected_langs);
                      // Toggle language selection in query parameters
                      $langs_param = $selected_langs;
                      if ($is_selected) {
                          $langs_param = array_diff($langs_param, [$lang]);
                      } else {
                          $langs_param[] = $lang;
                      }
                      $query_str = http_build_query([
                          'q' => $q,
                          'city' => $city,
                          'type' => $type,
                          'verified' => $verified ? 1 : 0,
                          'top_rated' => $top_rated ? 1 : 0,
                          'rating' => $min_rating,
                          'member_since' => $member_since,
                          'langs' => $langs_param
                      ]);
                ?>
                  <a href="vendors.php?<?= $query_str ?>" class="filter-pill-btn text-decoration-none <?= $is_selected ? 'active' : '' ?>"><?= htmlspecialchars($lang) ?></a>
                <?php endforeach; ?>
              </div>

              <h3>Member Since</h3>
              <div class="member-since-list">
                <?php
                  $since_opts = ['Any time', '2+ years', '5+ years', '10+ years'];
                  foreach ($since_opts as $s_opt):
                ?>
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="member_since" value="<?= htmlspecialchars($s_opt) ?>" id="since_<?= htmlspecialchars(str_replace('+', '', $s_opt)) ?>" <?= $member_since === $s_opt ? 'checked' : '' ?> onchange="this.form.submit()">
                    <label class="form-check-label" for="since_<?= htmlspecialchars(str_replace('+', '', $s_opt)) ?>"><?= htmlspecialchars($s_opt) ?></label>
                  </div>
                <?php endforeach; ?>
              </div>
            </form>
          </aside>

          <div class="col-lg-9 vendors-results-col">
            <div class="d-flex justify-content-between align-items-center results-toolbar flex-wrap gap-2">
              <span class="vendors-found-count font-mono" id="vendorsFoundCount"><?= count($providers) ?> vendors found</span>
              <div class="d-flex align-items-center gap-2">
                <form action="vendors.php" method="get" class="d-inline-block">
                  <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
                  <input type="hidden" name="city" value="<?= htmlspecialchars($city) ?>">
                  <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                  <input type="hidden" name="verified" value="<?= $verified ? 1 : 0 ?>">
                  <input type="hidden" name="top_rated" value="<?= $top_rated ? 1 : 0 ?>">
                  <input type="hidden" name="rating" value="<?= htmlspecialchars($min_rating) ?>">
                  <input type="hidden" name="member_since" value="<?= htmlspecialchars($member_since) ?>">
                  <?php foreach ($selected_langs as $sl): ?>
                    <input type="hidden" name="langs[]" value="<?= htmlspecialchars($sl) ?>">
                  <?php endforeach; ?>
                  <select name="sort" class="form-select form-select-sm vendors-sort-select" aria-label="Sort vendors" onchange="this.form.submit()">
                    <option value="Most Relevant" <?= $sort === 'Most Relevant' ? 'selected' : '' ?>>Most Relevant</option>
                    <option value="Highest Rated" <?= $sort === 'Highest Rated' ? 'selected' : '' ?>>Highest Rated</option>
                    <option value="Lowest Price" <?= $sort === 'Lowest Price' ? 'selected' : '' ?>>Lowest Price</option>
                    <option value="Newest" <?= $sort === 'Newest' ? 'selected' : '' ?>>Newest</option>
                  </select>
                </form>
                <button type="button" class="view-toggle active" data-view="grid" aria-label="Grid view"><i class="bi bi-grid-3x2-gap-fill"></i></button>
                <button type="button" class="view-toggle" data-view="list" aria-label="List view"><i class="bi bi-list-ul"></i></button>
              </div>
            </div>

            <div class="row g-3 vendors-grid">
              <?php if (empty($providers)): ?>
                <div class="col-12 text-center py-5">
                  <i class="bi bi-person-exclamation fs-1 text-muted"></i>
                  <p class="text-muted mt-3">No verified vendors found matching these filters.</p>
                </div>
              <?php else: ?>
                <?php foreach ($providers as $vnd):
                    // Avatar Initials
                    $initials = '';
                    $words = explode(' ', $vnd['name']);
                    foreach ($words as $w) {
                        $initials .= mb_substr($w, 0, 1);
                    }
                    $initials = mb_strtoupper(mb_substr($initials, 0, 2));

                    $vnd_price = !empty($vnd['starting_price']) ? 'AED ' . number_format($vnd['starting_price']) : 'AED 500';
                    $vnd_rating = !empty($vnd['rating_avg']) ? round($vnd['rating_avg'], 1) : '4.8';
                    $vnd_count = !empty($vnd['rating_count']) ? $vnd['rating_count'] : '120';
                    $vnd_langs = !empty($vnd['languages']) ? $vnd['languages'] : 'English, Arabic';
                    $vnd_team = !empty($vnd['team_size']) ? $vnd['team_size'] : '12';
                ?>
                  <div class="col-md-6 fade-in">
                    <article class="vendor-list-card">
                      <span class="featured-partner-chip"><i class="bi bi-award-fill"></i> Featured Partner</span>
                      <div class="vnd-price-from"><span>From</span><strong><?= htmlspecialchars($vnd_price) ?></strong></div>
                      <div class="vnd-card-head">
                        <?php if (!empty($vnd['logo'])): ?>
                          <img src="<?= htmlspecialchars($domain.$vnd['logo']) ?>" style="width:48px;height:48px;object-fit:contain;border-radius:50%;margin-right:12px;">
                        <?php else: ?>
                          <span class="vnd-avatar"><?= htmlspecialchars($initials) ?></span>
                        <?php endif; ?>
                        <div class="vnd-card-meta">
                          <div class="vnd-name-row">
                            <h2 class="vnd-name"><?= htmlspecialchars($vnd['name']) ?></h2>
                            <?php if ($vnd_rating >= 4.8): ?>
                              <span class="vnd-top-badge">TOP</span>
                            <?php endif; ?>
                            <?php if ($vnd['verification_status'] === 'verified'): ?>
                              <i class="bi bi-patch-check-fill vnd-verified" title="Verified"></i>
                            <?php endif; ?>
                          </div>
                          <div class="vnd-rating-row">
                            <span class="vnd-rating"><i class="bi bi-star-fill text-warning"></i> <?= htmlspecialchars($vnd_rating) ?> <span>(<?= htmlspecialchars($vnd_count) ?>)</span></span>
                            <span class="vnd-loc"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($vnd['city'] ?? 'Dubai') ?></span>
                          </div>
                        </div>
                      </div>
                      <p class="vnd-desc"><?= htmlspecialchars($vnd['description']) ?></p>
                      <div class="vnd-tags">
                        <?php
                          $specs_arr = array_filter(array_map('trim', explode(',', $vnd['specialties'] ?? 'Golden Visa, Business Setup, PRO Services')));
                          foreach (array_slice($specs_arr, 0, 3) as $spec):
                        ?>
                          <span><?= htmlspecialchars($spec) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($specs_arr) > 3): ?>
                          <span class="more">+<?= count($specs_arr) - 3 ?></span>
                        <?php endif; ?>
                      </div>
                      <div class="vnd-card-footer">
                        <span class="vnd-specs"><?= htmlspecialchars($vnd_team) ?> specialists, <?= htmlspecialchars($vnd_langs) ?></span>
                        <a href="vendor-profile.php?id=<?= htmlspecialchars($vnd['slug']) ?>" class="vnd-view-link">View Profile <i class="bi bi-arrow-right"></i></a>
                      </div>
                    </article>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="vendors-cta-wrap">
      <div class="container-xl">
        <div class="vendors-cta-card text-center fade-in">
          <div class="vendors-cta-icon"><i class="bi bi-lightning-charge-fill"></i></div>
          <h2 class="font-serif vendors-cta-title">Are you a UAE service provider?</h2>
          <p class="vendors-cta-sub">Join 500+ verified vendors on GlobalWays. Get discovered by thousands of customers actively looking for your services.</p>
          <a href="vendor-onboard.php" class="btn btn-gw-blue vendors-cta-btn">Apply to Become a Vendor <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
      </div>
    </section>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const vendorsGrid = document.querySelector('.vendors-grid');
      document.querySelectorAll('.results-toolbar .view-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
          document.querySelectorAll('.results-toolbar .view-toggle').forEach((b) => {
            b.classList.remove('active');
            b.setAttribute('aria-pressed', 'false');
          });
          btn.classList.add('active');
          btn.setAttribute('aria-pressed', 'true');
          if (vendorsGrid) {
            vendorsGrid.classList.toggle('list-view', btn.dataset.view === 'list');
          }
        });
      });
    });
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('vendors-page');
    // document.body.classList.add('has-custom-cursor');
    document.getElementById('gwNav').classList.add('dark-hero');
  });
  </script>
<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
