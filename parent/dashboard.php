<?php
// parent/dashboard.php - Parent ka asli dashboard: reminders + vaccination schedule (auto-suggest)
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/schedule.php';

$parentId = (int)$_SESSION['user_id'];
$today    = date('Y-m-d');

// ---------- 1) Data ----------
$stmt = $pdo->prepare('SELECT * FROM children WHERE parent_id = ? ORDER BY dob DESC, id DESC');
$stmt->execute([$parentId]);
$children = $stmt->fetchAll();

$vaccines = $pdo->query(
    'SELECT id, vaccine_name, recommended_age, recommended_age_days, availability
     FROM vaccines ORDER BY recommended_age_days ASC, id ASC'
)->fetchAll();

// Is parent ki saari bookings (rejected ko chhor kar), bache ke hisaab se
$stmt = $pdo->prepare(
    "SELECT child_id, vaccine_id, approval_status, vaccination_status, appointment_date
     FROM bookings
     WHERE parent_id = ? AND approval_status <> 'rejected'"
);
$stmt->execute([$parentId]);
$bookingsByChild = [];
foreach ($stmt->fetchAll() as $b) {
    $bookingsByChild[$b['child_id']][] = $b;
}
// Parent ki darj ki hui purani vaccinations bhi "lag chuki" mein ginti jayen
foreach (past_records_by_child($pdo, array_column($children, 'id')) as $cid => $pastRows) {
    foreach ($pastRows as $pr) { $bookingsByChild[$cid][] = $pr; }
}

// ---------- 2) Har bache ka schedule ----------
$schedules = [];
$dueTotal  = 0;
$doneTotal = 0;
foreach ($children as $c) {
    $rows = vaccine_schedule($c['dob'], $vaccines, $bookingsByChild[$c['id']] ?? []);
    $schedules[$c['id']] = $rows;
    foreach ($rows as $r) {
        if (in_array($r['status'], ['due', 'overdue'], true)) { $dueTotal++; }
        if ($r['status'] === 'done') { $doneTotal++; }
    }
}

// ---------- 3) Aane wali appointments ----------
$stmt = $pdo->prepare(
    "SELECT b.appointment_date, b.appointment_time, b.approval_status,
            c.name AS child_name, v.vaccine_name, h.hospital_name
     FROM bookings b
     JOIN children  c ON c.id = b.child_id
     JOIN vaccines  v ON v.id = b.vaccine_id
     JOIN hospitals h ON h.id = b.hospital_id
     WHERE b.parent_id = ?
       AND b.approval_status IN ('approved', 'pending')
       AND b.vaccination_status = 'pending'
       AND b.appointment_date >= ?
     ORDER BY b.appointment_date ASC, b.appointment_time ASC
     LIMIT 6"
);
$stmt->execute([$parentId, $today]);
$upcoming = $stmt->fetchAll();

// Agle 7 din ki appointments = reminder
$soon = array_filter($upcoming, fn($a) => days_until($a['appointment_date']) <= 7);

// Pichle 7 din mein jin hospital requests ko admin ne manzoor kiya
$stmt = $pdo->prepare(
    "SELECT hospital_name, city FROM hospital_suggestions
     WHERE parent_id = ? AND status = 'approved' AND decided_at >= ?
     ORDER BY decided_at DESC LIMIT 3"
);
$stmt->execute([$parentId, date('Y-m-d H:i:s', strtotime('-7 days'))]);
$newHospitals = $stmt->fetchAll();

$bookingReady = is_file(__DIR__ . '/book_hospital.php');   // Day 10 ke baad button khud chalne lagega

$stats = [
    ['My Children',           count($children),  'bi-people',          'primary'],
    ['Vaccines Due',          $dueTotal,         'bi-exclamation-circle', 'danger'],
    ['Upcoming Appointments', count($upcoming),  'bi-calendar-event', 'warning'],
    ['Vaccinations Done',     $doneTotal,        'bi-shield-check',   'success'],
];

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard.php';
require_once __DIR__ . '/../includes/parent_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">Hello, <?= e($_SESSION['name']) ?> 👋</h4>
  <p class="text-muted mb-0">Keep your child's vaccinations on track.</p>
</div>

<!-- Reminder: agle 7 din -->
<?php if ($soon): ?>
  <div class="alert alert-warning d-flex gap-3 align-items-start">
    <i class="bi bi-bell-fill fs-4"></i>
    <div>
      <strong>Reminder:</strong> you have <?= count($soon) ?> appointment<?= count($soon) > 1 ? 's' : '' ?> in the next 7 days.
      <ul class="mb-0 mt-1 ps-3">
        <?php foreach ($soon as $a): ?>
          <li><?= e($a['child_name']) ?>: <?= e($a['vaccine_name']) ?>, <strong><?= e(day_label(days_until($a['appointment_date']))) ?></strong>
              (<?= format_date($a['appointment_date']) ?>, <?= date('h:i A', strtotime($a['appointment_time'])) ?>)</li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<?php if ($dueTotal > 0): ?>
  <div class="alert alert-danger d-flex gap-3 align-items-center">
    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
    <div><strong><?= $dueTotal ?> vaccine<?= $dueTotal > 1 ? 's are' : ' is' ?> due or overdue.</strong>
      See the schedule below and book an appointment.</div>
  </div>
<?php endif; ?>

<?php if ($newHospitals): ?>
  <div class="alert alert-success d-flex flex-wrap gap-3 align-items-center justify-content-between">
    <div>
      <i class="bi bi-hospital-fill me-2"></i><strong>Good news!</strong>
      <?php foreach ($newHospitals as $i => $nh): ?><?= $i ? ', ' : ' ' ?><?= e($nh['hospital_name']) ?> (<?= e($nh['city']) ?>)<?php endforeach; ?>
      is now available for booking.
    </div>
    <?php if ($bookingReady): ?>
      <a href="<?= BASE_URL ?>/parent/book_hospital.php" class="btn btn-sm btn-success">Book now</a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- Stat cards -->
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

<?php if (!$children): ?>
  <div class="card">
    <div class="card-body p-5 text-center">
      <div class="icon-box mx-auto mb-3"><i class="bi bi-person-hearts"></i></div>
      <h5>Add your child to get started</h5>
      <p class="text-muted mb-3">We will show the full vaccination schedule based on your child's date of birth.</p>
      <a href="<?= BASE_URL ?>/parent/child_form.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Child</a>
    </div>
  </div>
<?php else: ?>

  <!-- Upcoming appointments -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="mb-3">Upcoming Appointments</h5>
      <?php if (!$upcoming): ?>
        <p class="text-muted mb-0">No upcoming appointments.</p>
      <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($upcoming as $a): $d = days_until($a['appointment_date']); ?>
            <li class="list-group-item d-flex justify-content-between align-items-start gap-2 px-0">
              <div class="min-w-0">
                <div class="fw-semibold"><?= e($a['child_name']) ?>: <?= e($a['vaccine_name']) ?></div>
                <small class="text-muted">
                  <?= e($a['hospital_name']) ?> &bull; <?= format_date($a['appointment_date']) ?>, <?= date('h:i A', strtotime($a['appointment_time'])) ?>
                </small>
              </div>
              <div class="text-end text-nowrap">
                <span class="badge rounded-pill text-bg-<?= $d <= 1 ? 'danger' : ($d <= 7 ? 'warning' : 'secondary') ?>"><?= e(day_label($d)) ?></span>
                <div><small class="text-muted"><?= $a['approval_status'] === 'approved' ? 'Confirmed' : 'Awaiting approval' ?></small></div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- Vaccination schedule (auto-suggest) -->
  <h5 class="mb-3">Vaccination Schedule</h5>
  <div class="row g-3">
    <?php foreach ($children as $c):
        $rows    = $schedules[$c['id']];
        $total   = count($rows);
        $done    = count(array_filter($rows, fn($r) => $r['status'] === 'done'));
        $pct     = $total ? (int)round($done * 100 / $total) : 0;
        $visible = array_slice($rows, 0, 4);
        $hidden  = array_slice($rows, 4);
    ?>
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="icon-box flex-shrink-0"><i class="bi bi-gender-<?= $c['gender'] === 'male' ? 'male' : 'female' ?>"></i></div>
              <div class="min-w-0 flex-fill">
                <h5 class="mb-0 text-truncate"><?= e($c['name']) ?></h5>
                <small class="text-muted"><?= e(child_age($c['dob'])) ?> old</small>
              </div>
            </div>

            <div class="d-flex justify-content-between small mb-1">
              <span class="text-muted">Progress</span><span><?= $done ?> of <?= $total ?> done</span>
            </div>
            <div class="progress mb-3" style="height:8px;">
              <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
            </div>

            <ul class="list-group list-group-flush">
              <?php foreach ($visible as $r) { echo schedule_item_html($r); } ?>
            </ul>

            <?php if ($hidden): ?>
              <div class="collapse" id="sched-<?= (int)$c['id'] ?>">
                <ul class="list-group list-group-flush">
                  <?php foreach ($hidden as $r) { echo schedule_item_html($r); } ?>
                </ul>
              </div>
              <button class="btn btn-sm btn-link text-decoration-none px-0 mt-2" type="button"
                      data-bs-toggle="collapse" data-bs-target="#sched-<?= (int)$c['id'] ?>">
                Show all <?= $total ?> vaccines
              </button>
            <?php endif; ?>

            <div class="mt-3">
              <?php if ($bookingReady): ?>
                <a href="<?= BASE_URL ?>/parent/book_hospital.php?child=<?= (int)$c['id'] ?>" class="btn btn-primary w-100">
                  <i class="bi bi-calendar-plus me-1"></i>Book Appointment
                </a>
              <?php else: ?>
                <button class="btn btn-primary w-100" disabled>Booking opens soon</button>
              <?php endif; ?>
            </div>
            <div class="text-center mt-2">
              <a href="<?= BASE_URL ?>/parent/past_vaccinations.php?child=<?= (int)$c['id'] ?>" class="small text-decoration-none">
                <i class="bi bi-clock-history me-1"></i>Already vaccinated earlier? Add past vaccines
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/parent_footer.php'; ?>
