<?php
// blog.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';

// Category filter
$cat_filter = $_GET['category'] ?? '';
$search_filter = trim($_GET['q'] ?? '');

$where_clauses = ["b.status = 'published'"];
if ($cat_filter !== '') {
    $where_clauses[] = "b.category = '" . $mysqli->real_escape_string($cat_filter) . "'";
}
if ($search_filter !== '') {
    $where_clauses[] = "(b.title LIKE '%" . $mysqli->real_escape_string($search_filter) . "%' OR b.excerpt LIKE '%" . $mysqli->real_escape_string($search_filter) . "%')";
}
$where_sql = implode(' AND ', $where_clauses);

// Fetch latest 3 published articles for the hero sidebar
$latest_res = $mysqli->query("SELECT b.*, u.name as author_name FROM blog_posts b LEFT JOIN users u ON u.id = b.author_user_id WHERE b.status = 'published' ORDER BY b.created_at DESC LIMIT 3");
$latest_posts = [];
if ($latest_res) {
    while($row = $latest_res->fetch_assoc()) $latest_posts[] = $row;
    $latest_res->free();
}

// Fetch posts for the main grid
$grid_res = $mysqli->query("SELECT b.*, u.name as author_name, u.bio as author_bio FROM blog_posts b LEFT JOIN users u ON u.id = b.author_user_id WHERE $where_sql ORDER BY b.created_at DESC");
$grid_posts = [];
if ($grid_res) {
    while($row = $grid_res->fetch_assoc()) $grid_posts[] = $row;
    $grid_res->free();
}

include __DIR__ . '/partials/frontend_header.php';
?>

  <main>
    <section class="page-hero blog-hero">
      <div class="container-xl">
        <div class="row g-4 align-items-center">
          <div class="col-lg-7">
            <p class="blog-hero-kicker">GLOBALWAYS BLOG</p>
            <h1 class="blog-hero-title font-serif">Insights for Your UAE Journey</h1>
            <p class="blog-hero-sub">Expert guides, industry news, and practical advice from our team of UAE documentation specialists.</p>
            <div class="blog-hero-stats">
              <div><strong>50+</strong><span>Articles</span></div>
              <div><strong>8</strong><span>Categories</span></div>
              <div><strong>Weekly</strong><span>New Posts</span></div>
            </div>
          </div>
          <div class="col-lg-5 fade-in">
            <div class="blog-latest-wrap">
              <p class="blog-latest-heading">• LATEST ARTICLES</p>
              <?php foreach ($latest_posts as $post): ?>
                <a href="<?= htmlspecialchars($post['slug']) ?>" class="latest-article-item">
                  <img src="<?= htmlspecialchars($post['image_url'] ?: 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=120&q=80') ?>" alt="">
                  <div class="latest-article-content">
                    <div class="latest-article-meta">
                      <span class="latest-cat-pill"><?= htmlspecialchars($post['category']) ?></span>
                      <span class="latest-read-time"><?= htmlspecialchars($post['reading_time']) ?></span>
                    </div>
                    <p class="latest-article-title"><?= htmlspecialchars($post['title']) ?></p>
                  </div>
                  <i class="bi bi-arrow-right"></i>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="blog-filter-strip">
      <div class="container-xl">
        <div class="d-flex flex-wrap gap-2" role="group" aria-label="Filter by category">
          <a href="blog.php" class="filter-pill-btn text-decoration-none <?= $cat_filter === '' ? 'active' : '' ?>">All</a>
          <?php
            $cats = ["Visa & Immigration", "Business Setup", "Documentation", "Platform Guides", "Case Studies", "Consultancy", "Advisory", "Marketing"];
            foreach ($cats as $cat):
          ?>
            <a href="blog.php?category=<?= urlencode($cat) ?>" class="filter-pill-btn text-decoration-none <?= $cat_filter === $cat ? 'active' : '' ?>"><?= htmlspecialchars($cat) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="blog-grid-section">
      <div class="container-xl py-3">
        <?php if (!empty($search_filter)): ?>
          <p class="text-muted mb-4">Showing search results for "<strong><?= htmlspecialchars($search_filter) ?></strong>":</p>
        <?php endif; ?>

        <div class="row g-4 blog-grid-row">
          <?php if (empty($grid_posts)): ?>
            <div class="col-12 text-center py-5">
              <i class="bi bi-journal-x fs-1 text-muted"></i>
              <p class="text-muted mt-3">No articles found in this category.</p>
            </div>
          <?php else: ?>
            <?php foreach ($grid_posts as $p):
                $initials = '';
                $names = explode(' ', $p['author_name'] ?? 'GlobalWays Staff');
                foreach($names as $name) {
                    $initials .= mb_substr($name, 0, 1);
                }
                $initials = mb_strtoupper(mb_substr($initials, 0, 2));
            ?>
              <div class="col-md-6 col-lg-4 fade-in">
                <article class="blog-post-card h-100">
                  <a href="<?= htmlspecialchars($p['slug']) ?>" class="blog-post-link">
                    <div class="blog-post-media">
                      <img src="<?= htmlspecialchars($p['image_url'] ?: 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=600&q=80') ?>" alt="<?= htmlspecialchars($p['title']) ?>">
                      <span class="blog-post-cat"><?= htmlspecialchars($p['category']) ?></span>
                    </div>
                    <div class="blog-post-body">
                      <p class="blog-post-meta"><?= htmlspecialchars(date('F j, Y', strtotime($p['created_at']))) ?> <span class="meta-dot">•</span> <i class="bi bi-clock"></i> <?= htmlspecialchars($p['reading_time']) ?></p>
                      <h2 class="blog-post-title font-serif"><?= htmlspecialchars($p['title']) ?></h2>
                      <p class="blog-post-excerpt"><?= htmlspecialchars($p['excerpt'] ?? '') ?></p>
                      <div class="blog-post-footer">
                        <div class="blog-post-author">
                          <span class="blog-post-avatar"><?= htmlspecialchars($initials) ?></span>
                          <span><?= htmlspecialchars($p['author_name'] ?? 'GlobalWays Staff') ?></span>
                        </div>
                        <span class="blog-post-read">Read <i class="bi bi-arrow-right"></i></span>
                      </div>
                    </div>
                  </a>
                </article>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="blog-newsletter-section text-white">
      <div class="container-xl py-3">
        <div class="blog-newsletter-inner text-center fade-in">
          <p class="label-mono label-mono-light">Newsletter</p>
          <h2 class="font-serif mb-2">Get UAE <span class="text-gradient-blue">Insights</span> in Your Inbox</h2>
          <p class="text-white-50 mb-4">Weekly updates, expert guides, and policy changes — delivered weekly.</p>
          <form class="newsletter-input blog-newsletter-form d-flex align-items-center gap-2 mx-auto" action="#" method="post">
            <input type="email" placeholder="yourmail@gmail.com" aria-label="Email for newsletter" required>
            <button type="submit" class="btn btn-gw-blue btn-sm flex-shrink-0">Subscribe</button>
          </form>
        </div>
      </div>
    </section>
  </main>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('blog-page');
    document.body.classList.add('has-custom-cursor');
    document.getElementById('gwNav').classList.add('dark-hero');
});
</script>
<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
