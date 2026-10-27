<?php
// includes/admin_header.php - Admin panel ka layout (sidebar + topbar)
// Har admin page mein:
//   require_once __DIR__ . '/../includes/auth.php';
//   require_role('admin');                       <- sab se pehle
//   $pageTitle = 'Hospitals';  $activeMenu = 'hospitals.php';
//   require_once __DIR__ . '/../includes/admin_header.php';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle  = $pageTitle ?? 'Admin';
$activeMenu = $activeMenu ?? basename($_SERVER['PHP_SELF']);   // sidebar mein kaunsa link active
$flash      = get_flash();

// Sidebar ke "Requests" par pending ki ginti dikhane ke liye
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE approval_status = 'pending'")->fetchColumn();
// Manzoori ke intezar mein: naye hospital/parent accounts aur purani vaccinations ke records
$pendingUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
$pendingPast  = (int)$pdo->query("SELECT COUNT(*) FROM past_vaccinations WHERE status = 'pending'")->fetchColumn();
$pendingSug   = (int)$pdo->query("SELECT COUNT(*) FROM hospital_suggestions WHERE status = 'pending'")->fetchColumn();
$badges = ['requests.php' => $pendingCount, 'users.php' => $pendingUsers, 'past_records.php' => $pendingPast, 'suggestions.php' => $pendingSug];

// [file, label, icon, ready]  - ready = false wale link "Soon" dikhte hain (jaise jaise pages bante hain true karte jayenge)
$menu = [
    ['dashboard.php', 'Dashboard',  'bi-speedometer2',   true],
    ['children.php',  'Children',   'bi-people',         true],
    ['vaccines.php',  'Vaccines',   'bi-capsule',        true],
    ['hospitals.php', 'Hospitals',  'bi-hospital',       true],
    ['suggestions.php', 'Hospital Requests', 'bi-geo-alt', true],
    ['users.php',     'Users',      'bi-person-gear',    true],
    ['requests.php',  'Requests',   'bi-inbox',          true],
    ['past_records.php', 'Past Records', 'bi-clock-history', true],
    ['bookings.php',  'Bookings',   'bi-calendar-check', true],
    ['reports.php',   'Reports',    'bi-graph-up',       true],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Admin | <?= APP_NAME ?></title>
  <?php head_assets(); ?>
</head>
<body class="admin-body">

<div class="admin-overlay"></div>

<!-- ============ SIDEBAR ============ -->
<aside class="admin-sidebar">
  <a href="<?= BASE_URL ?>/admin/dashboard.php" class="side-brand">
    <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="Logo" width="34" height="34">
    <span><?= APP_NAME ?></span>
  </a>

  <div class="side-label">MENU</div>
  <nav class="side-nav">
    <?php foreach ($menu as [$file, $label, $icon, $ready]): ?>
      <?php if ($ready): ?>
        <a class="side-link <?= $activeMenu === $file ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/<?= $file ?>">
          <i class="bi <?= $icon ?>"></i><span><?= $label ?></span>
          <?php if (($badges[$file] ?? 0) > 0): ?>
            <span class="badge rounded-pill text-bg-warning ms-auto"><?= (int)$badges[$file] ?></span>
          <?php endif; ?>
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
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($_SESSION['name'] ?? 'A', 0, 1))) ?></span>
        <span class="d-none d-sm-inline"><?= e($_SESSION['name'] ?? 'Admin') ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0">
        <li><a class="dropdown-item" href="<?= BASE_URL ?>/index.php"><i class="bi bi-globe me-2"></i>View Website</a></li>
        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      </ul>
    </div>
  </header>

  <main class="admin-main">
    <?= render_flash($flash) ?>
