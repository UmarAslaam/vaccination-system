<?php
// hospital/dashboard.php - Hospital ka main page: aaj ki aur aane wali appointments
require_once __DIR__ . '/../includes/auth.php';
require_role('hospital');
require_once __DIR__ . '/../config/db.php';

$hospital = require_hospital($pdo);
$hid      = (int)$hospital['id'];
$today    = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));

// 4 ginti ek hi query se (sirf approved appointments, sirf is hospital ki)
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(b.appointment_date = ?), 0)                                          AS today,
            COALESCE(SUM(b.appointment_date > ?  AND b.vaccination_status = 'pending'), 0)    AS upcoming,
            COALESCE(SUM(b.appointment_date <= ? AND b.vaccination_status = 'pending'), 0)    AS needs_update,
            COALESCE(SUM(b.vaccination_status = 'vaccinated'), 0)                             AS vaccinated
     FROM bookings b
     WHERE b.hospital_id = ? AND b.approval_status = 'approved'"
);
$stmt->execute([$today, $today, $today, $hid]);
$s = $stmt->fetch();

$base = "SELECT b.appointment_date, b.appointment_time, b.vaccination_status,
                c.name AS child_name, v.vaccine_name, u.name AS parent_name, u.phone AS parent_phone
         FROM bookings b
         JOIN children c ON c.id = b.child_id
         JOIN vaccines v ON v.id = b.vaccine_id
         JOIN users    u ON u.id = b.parent_id
         WHERE b.hospital_id = ? AND b.approval_status = 'approved'";

$stmt = $pdo->prepare($base . ' AND b.appointment_date = ? ORDER BY b.appointment_time ASC');
$stmt->execute([$hid, $today]);
$todayList = $stmt->fetchAll();

$stmt = $pdo->prepare($base . " AND b.appointment_date > ? AND b.appointment_date <= ? AND b.vaccination_status = 'pending'
                                ORDER BY b.appointment_date ASC, b.appointment_time ASC LIMIT 8");
$stmt->execute([$hid, $today, $nextWeek]);
$soonList = $stmt->fetchAll();

$stats = [
    ["Today's Appointments", $s['today'],        'bi-calendar-day',    'primary'],
    ['Upcoming',             $s['upcoming'],     'bi-calendar-event',  'info'],
    ['Needs Update',         $s['needs_update'], 'bi-exclamation-circle', 'warning'],
    ['Total Vaccinated',     $s['vaccinated'],   'bi-shield-check',    'success'],
];

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard.php';
require_once __DIR__ . '/../includes/hospital_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1"><?= e($hospital['hospital_name']) ?></h4>
  <p class="text-muted mb-0"><i class="bi bi-geo-alt me-1"></i><?= e($hospital['address']) ?>, <?= e($hospital['location']) ?></p>
</div>

<?php if ((int)$s['needs_update'] > 0): ?>
  <div class="alert alert-warning d-flex flex-wrap gap-3 align-items-center justify-content-between">
    <div><i class="bi bi-bell-fill me-2"></i><strong><?= (int)$s['needs_update'] ?> appointment<?= (int)$s['needs_update'] > 1 ? 's' : '' ?></strong>
      still need a vaccination status update.</div>
    <a href="<?= BASE_URL ?>/hospital/appointments.php?view=update" class="btn btn-sm btn-warning">Update now</a>
  </div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <?php foreach ($stats as [$label, $value, $icon, $color]): ?>
    <div class="col-6 col-xl-3">
      <div class="card stat-card h-100">
        <div class="icon-box text-<?= $color ?>" style="background: var(--bs-<?= $color ?>-bg-subtle);"><i class="bi <?= $icon ?>"></i></div>
        <div>
          <div class="stat-number"><?= (int)$value ?></div>
          <div class="stat-label"><?= e($label) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Today</h5>
          <a href="<?= BASE_URL ?>/hospital/appointments.php?view=today" class="small text-decoration-none">Manage</a>
        </div>
        <?php if (!$todayList): ?>
          <p class="text-muted mb-0">No appointments today.</p>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($todayList as $a): ?>
              <li class="list-group-item d-flex justify-content-between align-items-start gap-2 px-0">
                <div class="min-w-0">
                  <div class="fw-semibold"><?= date('h:i A', strtotime($a['appointment_time'])) ?> &bull; <?= e($a['child_name']) ?></div>
                  <small class="text-muted"><?= e($a['vaccine_name']) ?> &bull; <?= e($a['parent_name']) ?></small>
                </div>
                <div class="text-nowrap"><?= status_badge($a['vaccination_status']) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Next 7 days</h5>
          <a href="<?= BASE_URL ?>/hospital/appointments.php?view=upcoming" class="small text-decoration-none">View all</a>
        </div>
        <?php if (!$soonList): ?>
          <p class="text-muted mb-0">Nothing scheduled.</p>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($soonList as $a): ?>
              <li class="list-group-item px-0">
                <div class="fw-semibold"><?= e($a['child_name']) ?>: <?= e($a['vaccine_name']) ?></div>
                <small class="text-muted"><?= format_date($a['appointment_date']) ?>, <?= date('h:i A', strtotime($a['appointment_time'])) ?></small>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/hospital_footer.php'; ?>
