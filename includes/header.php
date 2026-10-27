<?php
// includes/header.php - har public/parent/hospital page ka upar wala hissa
// Istemal:
//   $pageTitle = 'Login';
//   require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Home';
$currentPage = basename($_SERVER['PHP_SELF']);   // active link ke liye
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
  <meta name="description" content="<?= e($metaDescription ?? 'VaxCare helps parents track their children\'s vaccinations, book hospital appointments and download a verifiable vaccination certificate.') ?>">
  <meta property="og:title" content="<?= e($pageTitle) ?> | <?= APP_NAME ?>">
  <meta property="og:description" content="<?= e($metaDescription ?? 'Never miss a vaccination again.') ?>">
  <meta property="og:type" content="website">

  <!-- Bootstrap 5 + Icons + Google Font -->
  <?php head_assets(); ?>

  <!-- Hamara apna CSS (Bootstrap ke baad, taake override kar sake) -->
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top site-navbar">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
      <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="Logo" width="36" height="36">
      <span class="brand-text"><?= APP_NAME ?></span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
            data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">Home</a>
        </li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#schedule">Schedule</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#how">How it works</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#faq">FAQ</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#contact">Contact</a></li>

        <?php if (is_logged_in()): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/<?= e(current_role()) ?>/dashboard.php">
              <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="btn btn-outline-primary btn-sm px-3" href="<?= BASE_URL ?>/logout.php">
              <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'login.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/login.php">Login</a>
          </li>
          <li class="nav-item mt-2 mt-lg-0">
            <a class="btn btn-primary btn-sm px-3" href="<?= BASE_URL ?>/register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="site-main">
<?= render_flash($flash) ?>
