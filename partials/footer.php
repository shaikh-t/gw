<?php
// partials/footer.php
?>
</div> <!-- /.container -->
<footer class="footer">
  <div class="container">
    <small>© <?php echo date('Y'); ?> GW Admin</small>
  </div>
</footer>

<script nonce="<?php echo $cspNonce ?? ''; ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script nonce="<?php echo $cspNonce ?? ''; ?>" src="<?php echo $domain; ?>/public/assets/js/app.js"></script>
<script nonce="<?php echo $cspNonce ?? ''; ?>" src="<?php echo $domain; ?>/js/notifications.js"></script>
</body>
</html>
