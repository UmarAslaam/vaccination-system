<?php
// includes/hospital_header.php - Hospital panel ka layout (sidebar + topbar)
// Har hospital page mein:
//   require_once __DIR__ . '/../includes/auth.php';
//   require_role('hospital');                    <- sab se pehle
//   $pageTitle = 'Appointments';  $activeMenu = 'appointments.php';
//   require_once __DIR__ . '/../includes/hospital_header.php';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle  = $pageTitle ?? 'Hospital';
$activeMenu = $activeMenu ?? basename($_SERVER['PHP_SELF']);
$flash      = get_flash();

// Sidebar badge: guzri hui appointments jin ka status abhi update nahi hua
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM bookings b
     JOIN hospitals h ON h.id = b.hospital_id
     WHERE h.user_id = ? AND b.approval_status = 'approved'
       AND b.vaccination_status = 'pending' AND b.appointment_date <= ?"
);
$stmt->execute([$_SESSION['user_id'] ?? 0, date('Y-m-d')]);
$needsUpdate = (int)$stmt->fetchColumn();

$menu = [
    ['dashboard.php',    'Dashboard',        'bi-speedometer2'],
    ['appointments.php', 'Appointments',     'bi-calendar-check'],
    ['profile.php',      'Hospital Profile', 'bi-building'],
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

<aside class="admin-sidebar">
  <a href="<?= BASE_URL ?>/hospital/dashboard.php" class="side-brand">
    <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="Logo" width="34" height="34">
    <span><?= APP_NAME ?></span>
  </a>

  <div class="side-label">HOSPITAL MENU</div>
  <nav class="side-nav">
    <?php foreach ($menu as [$file, $label, $icon]): ?>
      <a class="side-link <?= $activeMenu === $file ? 'active' : '' ?>" href="<?= BASE_URL ?>/hospital/<?= $file ?>">
        <i class="bi <?= $icon ?>"></i><span><?= $label ?></span>
        <?php if ($file === 'appointments.php' && $needsUpdate > 0): ?>
          <span class="badge rounded-pill text-bg-warning ms-auto"><?= $needsUpdate ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="side-label mt-4">ACCOUNT</div>
  <nav class="side-nav">
    <a class="side-link" href="<?= BASE_URL ?>/index.php"><i class="bi bi-globe"></i><span>View Website</span></a>
    <a class="side-link" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
  </nav>
</aside>

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
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($_SESSION['name'] ?? 'H', 0, 1))) ?></span>
        <span class="d-none d-sm-inline"><?= e($_SESSION['name'] ?? 'Hospital') ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0">
        <li><a class="dropdown-item" href="<?= BASE_URL ?>/index.php"><i class="bi bi-globe me-2"></i>View Website</a></li>
        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      </ul>
    </div>
  </header>

  <main class="admin-main">
    <?= render_flash($flash) ?>
