<?php
// blog-detail.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';

$id_val = $_GET['id'] ?? '';
$post = null;

if ($id_val !== '') {
    $val = $mysqli->real_escape_string($id_val);
    $res = $mysqli->query("SELECT b.*, u.name as author_name, u.bio as author_bio FROM blog_posts b LEFT JOIN users u ON u.id = b.author_user_id WHERE b.slug = '$val' OR b.uuid = '$val' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $post = $res->fetch_assoc();
        $res->free();
    }
}

// Fallback to latest post if not found
if (!$post) {
    $res = $mysqli->query("SELECT b.*, u.name as author_name, u.bio as author_bio FROM blog_posts b LEFT JOIN users u ON u.id = b.author_user_id WHERE b.status = 'published' ORDER BY b.created_at DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $post = $res->fetch_assoc();
        $res->free();
    }
}

if (!$post) {
    http_response_code(404);
    echo "No published articles found.";
    exit;
}

// Author initials
$author_name = $post['author_name'] ?? 'GlobalWays Staff';
$initials = '';
$names = explode(' ', $author_name);
foreach($names as $name) {
    $initials .= mb_substr($name, 0, 1);
}
$initials = mb_strtoupper(mb_substr($initials, 0, 2));

// Author bio & role (fallback)
$author_bio = $post['author_bio'] ?? 'Part of the GlobalWays founding team, focused on building systems that make UAE documentation simpler, safer, and more transparent.';
$author_role = ($author_name === 'Admin') ? 'Administrator' : 'Editorial Team';

// Fetch related articles from same category, limit 3, excluding current post
$related_res = $mysqli->query("SELECT b.*, u.name as author_name FROM blog_posts b LEFT JOIN users u ON u.id = b.author_user_id WHERE b.status = 'published' AND b.category = '" . $mysqli->real_escape_string($post['category']) . "' AND b.id != " . intval($post['id']) . " ORDER BY b.created_at DESC LIMIT 3");
$related_posts = [];
if ($related_res) {
    while($row = $related_res->fetch_assoc()) $related_posts[] = $row;
    $related_res->free();
}
// If not enough related posts, fill with other latest posts
if (count($related_posts) < 3) {
    $exclude_ids = array_merge([$post['id']], array_column($related_posts, 'id'));
    $limit_needed = 3 - count($related_posts);
    $fill_res = $mysqli->query("SELECT b.*, u.name as author_name FROM blog_posts b LEFT JOIN users u ON u.id = b.author_user_id WHERE b.status = 'published' AND b.id NOT IN (" . implode(',', array_map('intval', $exclude_ids)) . ") ORDER BY b.created_at DESC LIMIT $limit_needed");
    if ($fill_res) {
        while($row = $fill_res->fetch_assoc()) $related_posts[] = $row;
        $fill_res->free();
    }
}

include __DIR__ . '/partials/frontend_header.php';
?>

  <main>
    <section class="article-hero-dark">
      <div class="container-xl">
        <nav aria-label="breadcrumb" class="article-breadcrumb">
          <a href="blog.php">Insights</a>
          <span>›</span>
          <span id="articleBreadcrumb"><?= htmlspecialchars($post['title']) ?></span>
        </nav>
        <span class="article-tag-pill" id="articleTag"><?= htmlspecialchars($post['category']) ?></span>
        <header class="article-header-block fade-in">
          <h1 class="font-serif" id="articleTitle"><?= htmlspecialchars($post['title']) ?></h1>
          <div class="article-meta-row">
            <div class="article-author-chip">
              <span class="avatar-circle"><?= htmlspecialchars($initials) ?></span>
              <div>
                <p class="mb-0"><?= htmlspecialchars($author_name) ?></p>
                <small id="articleMeta"><?= htmlspecialchars($post['category']) ?> · <?= htmlspecialchars($post['reading_time']) ?> · <?= htmlspecialchars(date('M d, Y', strtotime($post['created_at']))) ?></small>
              </div>
            </div>
            <div class="article-actions">
              <button type="button" aria-label="Share" onclick="navigator.clipboard.writeText(window.location.href); alert('Link copied to clipboard!');"><i class="bi bi-link-45deg"></i></button>
              <button type="button" aria-label="Bookmark"><i class="bi bi-bookmark"></i></button>
              <button type="button" aria-label="Read later">Read later</button>
            </div>
          </div>
        </header>
        <img src="<?= htmlspecialchars($post['image_url'] ?: 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=1200&q=85') ?>" alt="<?= htmlspecialchars($post['title']) ?>" id="articleImage" class="article-feature-image fade-in">
      </div>
    </section>

    <section class="article-content-wrap">
      <div class="container-xl">
        <div class="row g-4 g-xl-5">
          <div class="col-lg-8">
            <div class="article-body fade-in" id="articleBody">
              <?= $post['content'] ?>
            </div>

            <div class="article-summary-card fade-in">
              <p class="label-mono mb-2">Summary</p>
              <h3 class="font-serif">Key Takeaways</h3>
              <ul class="article-summary-list list-unstyled mb-0">
                <li><i class="bi bi-check-circle-fill"></i>Demand and operational health are independent — don't mistake one for the other</li>
                <li><i class="bi bi-check-circle-fill"></i>Siloed leadership teams compound operational fragility at scale</li>
                <li><i class="bi bi-check-circle-fill"></i>Unmanaged complexity is a liability; simplification is a strategic discipline</li>
                <li><i class="bi bi-check-circle-fill"></i>Build platforms that absorb new complexity without breaking existing operations</li>
              </ul>
            </div>

            <div class="article-author-bio fade-in">
              <span class="article-author-bio-avatar"><?= htmlspecialchars($initials) ?></span>
              <div>
                <h3 class="font-serif mb-1"><?= htmlspecialchars($author_name) ?></h3>
                <p class="article-author-role"><?= htmlspecialchars($author_role) ?></p>
                <p class="mb-0"><?= htmlspecialchars($author_bio) ?></p>
              </div>
            </div>
          </div>

          <aside class="col-lg-4">
            <div class="article-sidebar sticky-top">
              <div class="article-side-card article-toc-card fade-in">
                <p class="label-mono mb-3">In this article</p>
                <ul class="article-toc list-unstyled mb-0">
                  <li><a href="#section-01"><span class="toc-num">01</span> The False Comfort of Demand</a></li>
                  <li><a href="#section-02" class="active"><span class="toc-num">02</span> Structural Accountability</a></li>
                  <li><a href="#section-03"><span class="toc-num">03</span> Complexity as a Risk Factor</a></li>
                </ul>
              </div>

              <div class="article-side-card article-cta-card fade-in">
                <span class="article-cta-share" aria-hidden="true"><i class="bi bi-share"></i></span>
                <p class="article-cta-kicker">GlobalWays® — UAE's Most Trusted Marketplace</p>
                <h3 class="font-serif">Ready to Take the Next Step?</h3>
                <a href="register.php" class="btn btn-gw-blue w-100">Get Started Free <i class="bi bi-arrow-right ms-1"></i></a>
              </div>

              <div class="article-side-card article-tags-card fade-in">
                <p class="label-mono mb-3">Tags</p>
                <div class="article-tag-pills">
                  <?php
                    $tags_arr = array_filter(array_map('trim', explode(',', $post['tags'] ?? '')));
                    foreach ($tags_arr as $t):
                  ?>
                    <span><?= htmlspecialchars($t) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </section>

    <section class="article-related-section">
      <div class="container-xl">
        <p class="label-mono article-related-kicker">Keep Reading</p>
        <h2 class="font-serif">Related <span class="text-gradient-blue">Articles</span></h2>
        <div class="row g-4 blog-grid-row">
          <?php foreach ($related_posts as $rp): ?>
            <div class="col-md-4">
              <article class="blog-post-card h-100">
                <a href="<?= htmlspecialchars($rp['slug']) ?>" class="blog-post-link">
                  <div class="blog-post-media">
                    <img src="<?= htmlspecialchars($rp['image_url'] ?: 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=600&q=80') ?>" alt="">
                    <span class="blog-post-cat"><?= htmlspecialchars($rp['category']) ?></span>
                  </div>
                  <div class="blog-post-body">
                    <p class="blog-post-meta"><i class="bi bi-clock"></i> <?= htmlspecialchars($rp['reading_time']) ?></p>
                    <h3 class="blog-post-title font-serif"><?= htmlspecialchars($rp['title']) ?></h3>
                    <span class="blog-post-read">Read <i class="bi bi-arrow-right"></i></span>
                  </div>
                </a>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="article-bottom-cta">
      <div class="container-xl text-center">
        <p class="article-bottom-kicker">GlobalWays® — UAE's Most Trusted Marketplace</p>
        <h2 class="font-serif">Ready to Take the Next Step?</h2>
        <div class="d-flex justify-content-center flex-wrap gap-3 mt-4">
          <a href="register.php" class="btn btn-gw-blue">Get Started Free <i class="bi bi-arrow-right ms-1"></i></a>
          <a href="blog.php" class="btn btn-gw-outline text-white border-white">More Articles</a>
        </div>
      </div>
    </section>
  </main>

<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
