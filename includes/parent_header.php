<?php
// includes/parent_header.php - Parent panel ka layout (sidebar + topbar)
// Har parent page mein:
//   require_once __DIR__ . '/../includes/auth.php';
//   require_role('parent');                      <- sab se pehle
//   $pageTitle = 'My Children';  $activeMenu = 'children.php';
//   require_once __DIR__ . '/../includes/parent_header.php';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$pageTitle  = $pageTitle ?? 'Parent';
$activeMenu = $activeMenu ?? basename($_SERVER['PHP_SELF']);
$flash      = get_flash();

// [file, label, icon, ready] - ready = false wale "Soon" dikhte hain
$menu = [
    ['dashboard.php',     'Dashboard',          'bi-speedometer2',        true],
    ['children.php',      'My Children',        'bi-people',              true],
    ['past_vaccinations.php', 'Past Vaccinations', 'bi-clock-history',   true],
    ['book_hospital.php', 'Book Appointment',   'bi-calendar-plus',       true],
    ['my_bookings.php',   'My Bookings',        'bi-calendar-check',      true],
    ['reports.php',       'Vaccination Report', 'bi-file-earmark-medical', true],
    ['profile.php',       'My Profile',         'bi-person-circle',       true],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
  <?php head_assets(); ?>
</head>
<body class="admin-body">

<div class="admin-overlay"></div>

<!-- ============ SIDEBAR ============ -->
<aside class="admin-sidebar">
  <a href="<?= BASE_URL ?>/parent/dashboard.php" class="side-brand">
    <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="Logo" width="34" height="34">
    <span><?= APP_NAME ?></span>
  </a>

  <div class="side-label">PARENT MENU</div>
  <nav class="side-nav">
    <?php foreach ($menu as [$file, $label, $icon, $ready]): ?>
      <?php if ($ready): ?>
        <a class="side-link <?= $activeMenu === $file ? 'active' : '' ?>" href="<?= BASE_URL ?>/parent/<?= $file ?>">
          <i class="bi <?= $icon ?>"></i><span><?= $label ?></span>
        </a>
      <?php else: ?>
        <span class="side-link disabled">
          <i class="bi <?= $icon ?>"></i><span><?= $label ?></span>
          <small class="badge text-bg-secondary ms-auto">Soon</small>
        </span>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>

  <div class="side-label mt-4">ACCOUNT</div>
  <nav class="side-nav">
    <a class="side-link" href="<?= BASE_URL ?>/index.php"><i class="bi bi-globe"></i><span>View Website</span></a>
    <a class="side-link" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
  </nav>
</aside>

<!-- ============ MAIN AREA ============ -->
<div class="admin-content">

  <header class="admin-topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-light d-lg-none" type="button" data-sidebar-toggle aria-label="Toggle menu">
        <i class="bi bi-list fs-5"></i>
      </button>
      <h5 class="mb-0"><?= e($pageTitle) ?></h5>
    </div>

    <div class="dropdown">
      <button class="btn btn-light d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($_SESSION['name'] ?? 'P', 0, 1))) ?></span>
        <span class="d-none d-sm-inline"><?= e($_SESSION['name'] ?? 'Parent') ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0">
        <li><a class="dropdown-item" href="<?= BASE_URL ?>/index.php"><i class="bi bi-globe me-2"></i>View Website</a></li>
        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      </ul>
    </div>
  </header>

  <main class="admin-main">
    <?= render_flash($flash) ?>
