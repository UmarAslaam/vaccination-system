</main>

<footer class="site-footer mt-5">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="Logo" width="36" height="36">
          <span class="fs-5 fw-semibold text-white"><?= APP_NAME ?></span>
        </div>
        <p class="small mb-0">An online system that helps parents never miss a vaccination, and helps hospitals manage every appointment in one place.</p>
      </div>
      <div class="col-6 col-lg-2 offset-lg-1">
        <h6 class="text-white mb-3">Quick Links</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/index.php#features">Features</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/index.php#faq">FAQ</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/verify.php">Verify Certificate</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/login.php">Login</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/register.php">Register</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="text-white mb-3">Join Us</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= BASE_URL ?>/register.php?role=parent">For Parents</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>/register.php?role=hospital">For Hospitals</a></li>
        </ul>
      </div>
      <div class="col-lg-3">
        <h6 class="text-white mb-3">Contact</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><i class="bi bi-envelope me-2"></i>support@vaxcare.com</li>
          <li class="mb-2"><i class="bi bi-telephone me-2"></i>+92 300 0000000</li>
          <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>Karachi, Pakistan</li>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer-bottom text-center small py-3">
    &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
  </div>
</footer>

<!-- Back to top button -->
<button id="backToTop" class="btn btn-primary rounded-circle shadow" aria-label="Back to top">
  <i class="bi bi-arrow-up"></i>
</button>

<?php foot_scripts(); ?>
</body>
</html>
