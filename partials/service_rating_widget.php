<?php
// partials/service_rating_widget.php
// Usage: set $service array before including this file
$avg = $service['rating_avg'] ?? null;
$count = $service['rating_count'] ?? 0;
?>
<div class="d-flex align-items-center">
  <div class="me-3">
    <?php if ($avg !== null): ?>
      <div class="fs-4 fw-bold"><?php echo htmlspecialchars(number_format($avg, 2), ENT_QUOTES); ?>/5</div>
      <div><?php echo str_repeat('★', round($avg)) . str_repeat('☆', 5 - round($avg)); ?></div>
      <div class="small text-muted"><?php echo intval($count); ?> reviews</div>
    <?php else: ?>
      <div class="fs-5 fw-semibold">No ratings yet</div>
      <div class="small text-muted">Be the first to review</div>
    <?php endif; ?>
  </div>
  <div>
    <a href="/services/reviews_list.php?service_id=<?php echo intval($service['id']); ?>" class="btn btn-outline-secondary btn-sm">View reviews</a>
  </div>
</div>
