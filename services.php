<?php
// services.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/services_helpers.php';

$search_q = trim($_GET['q'] ?? '');
$cat_filter = trim($_GET['category'] ?? 'all'); // can be a slug or 'all'

// Fetch all service categories
$categories = service_categories_all();

// Map category slug to id
$category_id = null;
if ($cat_filter !== 'all') {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $cat_filter) {
            $category_id = $cat['id'];
            break;
        }
    }
}

// Build query
$where = ["s.status = 'published'"];
if ($category_id !== null) {
    $where[] = "s.category_id = " . intval($category_id);
}
if ($search_q !== '') {
    $where[] = "(s.title LIKE '%" . $mysqli->real_escape_string($search_q) . "%' OR s.short_description LIKE '%" . $mysqli->real_escape_string($search_q) . "%')";
}
$where_sql = implode(' AND ', $where);

$sql = "SELECT s.*, c.name as category_name, c.slug as category_slug, p.name as provider_name,
        (SELECT COUNT(DISTINCT s2.provider_id) FROM services s2 WHERE s2.category_id = s.category_id) as vendors_count
        FROM services s
        LEFT JOIN service_categories c ON c.id = s.category_id
        LEFT JOIN providers p ON p.id = s.provider_id
        WHERE $where_sql
        ORDER BY s.created_at DESC";
$res_services = $mysqli->query($sql);
$services = [];
if ($res_services) {
    while($row = $res_services->fetch_assoc()) $services[] = $row;
    $res_services->free();
}

include __DIR__ . '/partials/frontend_header.php';
?>

  <main>
    <section class="page-hero">
      <div class="container-xl">
        <nav class="breadcrumb-gw" aria-label="Breadcrumb"><a href="index.php">Home</a> / Our Services</nav>
        <h1 class="font-serif mb-3" style="font-size:clamp(2.2rem,4vw,3.5rem)">All Services</h1>
        <p class="text-white-50 mb-4 col-lg-7">Browse 50+ UAE services from 500+ verified providers — compare pricing, processing times, and success rates in one place.</p>
        <form class="hero-search d-flex align-items-center shadow-sm" action="services.php" method="get" role="search">
          <i class="bi bi-search text-muted ms-3"></i>
          <input type="search" name="q" id="serviceSearch" class="form-control" placeholder="Search Golden Visa, Business Setup, PRO…" aria-label="Search services" value="<?= htmlspecialchars($search_q) ?>">
          <input type="hidden" name="category" value="<?= htmlspecialchars($cat_filter) ?>">
          <button type="submit" class="btn btn-gw-dark m-1">Search</button>
        </form>
      </div>
    </section>

    <section class="py-5 services-list-section">
      <div class="container-xl py-3">
        <div class="d-flex flex-wrap gap-2 services-filter-row" id="categoryFilters" role="group" aria-label="Filter by category">
          <a href="services.php?q=<?= urlencode($search_q) ?>&category=all" class="filter-pill-btn filter-pill text-decoration-none <?= $cat_filter === 'all' ? 'active' : '' ?>">
            <i class="bi bi-globe2"></i> All Services
          </a>
          <?php foreach ($categories as $cat): ?>
            <a href="services.php?q=<?= urlencode($search_q) ?>&category=<?= urlencode($cat['slug']) ?>" class="filter-pill-btn filter-pill text-decoration-none <?= $cat_filter === $cat['slug'] ? 'active' : '' ?>">
              <i class="bi bi-tag-fill"></i> <?= htmlspecialchars($cat['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>

        <hr class="services-filters-divider">

        <p class="services-found-count font-mono" id="servicesFoundCount"><?= count($services) ?> services found</p>

        <div class="row g-4 services-grid" id="servicesGrid">
          <?php if (empty($services)): ?>
            <div class="col-12 text-center py-5">
              <i class="bi bi-search-heart fs-1 text-muted"></i>
              <p class="text-muted mt-3">No services found matching your criteria.</p>
            </div>
          <?php else: ?>
            <?php foreach ($services as $svc):
                $price_label = !empty($svc['price']) ? 'Starting from' : 'Price on request';
                $price_value = !empty($svc['price']) ? htmlspecialchars($svc['currency'] . ' ' . number_format($svc['price'])) : '';
                $timeline = !empty($svc['duration_minutes']) ? htmlspecialchars($svc['duration_minutes'] . ' mins') : '3–5 days';
                $rating = !empty($svc['rating_avg']) ? round($svc['rating_avg'], 1) : '4.8';
                $vendor_count = !empty($svc['vendors_count']) ? $svc['vendors_count'] : '12';
            ?>
              <div class="col-sm-6 col-lg-4 service-item fade-in">
                <a href="service-detail.php?id=<?= htmlspecialchars($svc['slug']) ?>" class="svc-list-card">
                  <span class="svc-popular-badge"><i class="bi bi-graph-up-arrow"></i> Popular</span>
                  <div class="svc-icon-circle"><i class="bi bi-airplane"></i></div>
                  <h2 class="svc-card-title font-serif"><?= htmlspecialchars($svc['title']) ?></h2>
                  <p class="svc-card-desc"><?= htmlspecialchars($svc['short_description']) ?></p>
                  <div class="svc-meta-row"><span><?= htmlspecialchars($price_label) ?></span><strong><?= $price_value ?></strong></div>
                  <div class="svc-meta-row"><span>Processing</span><span class="svc-meta-val"><?= $timeline ?></span></div>
                  <div class="svc-meta-row"><span>Success Rate</span><span class="svc-rate-pill">99.8%</span></div>
                  <div class="svc-card-footer">
                    <span><i class="bi bi-people"></i> <?= htmlspecialchars($vendor_count) ?> vendors</span>
                    <span class="svc-rating"><i class="bi bi-star-fill"></i> <?= htmlspecialchars($rating) ?></span>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="cta-section d-flex align-items-center justify-content-center text-center text-white bg-blk">
      <div class="cta-overlay" style="background:rgba(0,0,0,0.35)"></div>
      <div class="container position-relative py-5 fade-in">
        <h2 class="font-serif mb-3" style="font-size:clamp(1.8rem,3.5vw,2.8rem)">Can't find what you <span class="text-gradient-blue">need</span>?</h2>
        <p class="text-white-50 mb-4 col-lg-6 mx-auto">Browse our full vendor directory or reach out — our team will match you with the right provider within 24 hours.</p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
          <a href="vendors.php" class="btn btn-gw-blue">Browse Vendors <i class="bi bi-arrow-right ms-1"></i></a>
          <a href="contact.php" class="btn btn-gw-outline text-white border-white">Contact Support</a>
        </div>
      </div>
    </section>
  </main>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('services-page');
    // document.body.classList.add('has-custom-cursor');
    document.getElementById('gwNav').classList.add('dark-hero');
  });
  </script>
<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
