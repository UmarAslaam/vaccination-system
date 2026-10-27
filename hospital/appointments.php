<?php
// hospital/appointments.php - Approved appointments aur vaccination status update
require_once __DIR__ . '/../includes/auth.php';
require_role('hospital');
require_once __DIR__ . '/../config/db.php';

$hospital = require_hospital($pdo);
$hid      = (int)$hospital['id'];
$today    = date('Y-m-d');

// Tabs (whitelist)
$views = [
    'today'     => 'Today',
    'upcoming'  => 'Upcoming',
    'update'    => 'Needs update',
    'completed' => 'Completed',
    'all'       => 'All',
];
$view = array_key_exists($_GET['view'] ?? '', $views) ? $_GET['view'] : 'today';

// Har tab ki ginti
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(b.appointment_date = ?), 0)                                        AS today,
            COALESCE(SUM(b.appointment_date > ?  AND b.vaccination_status = 'pending'), 0)  AS upcoming,
            COALESCE(SUM(b.appointment_date <= ? AND b.vaccination_status = 'pending'), 0)  AS needs_update,
            COALESCE(SUM(b.vaccination_status <> 'pending'), 0)                             AS completed,
            COUNT(*)                                                                        AS total
     FROM bookings b
     WHERE b.hospital_id = ? AND b.approval_status = 'approved'"
);
$stmt->execute([$today, $today, $today, $hid]);
$c = $stmt->fetch();
$counts = ['today' => $c['today'], 'upcoming' => $c['upcoming'], 'update' => $c['needs_update'],
           'completed' => $c['completed'], 'all' => $c['total']];

// Tab ke hisaab se shart (sab mein hospital_id aur approved ki shart pehle se hai)
$sql = "SELECT b.id, b.appointment_date, b.appointment_time, b.vaccination_status,
               c.name AS child_name, c.dob, v.vaccine_name, u.name AS parent_name, u.phone AS parent_phone
        FROM bookings b
        JOIN children c ON c.id = b.child_id
        JOIN vaccines v ON v.id = b.vaccine_id
        JOIN users    u ON u.id = b.parent_id
        WHERE b.hospital_id = ? AND b.approval_status = 'approved'";
$params = [$hid];
$order  = ' ORDER BY b.appointment_date ASC, b.appointment_time ASC';

if ($view === 'today') {
    $sql .= ' AND b.appointment_date = ?';                                       $params[] = $today;
} elseif ($view === 'upcoming') {
    $sql .= " AND b.appointment_date > ? AND b.vaccination_status = 'pending'";  $params[] = $today;
} elseif ($view === 'update') {
    $sql .= " AND b.appointment_date <= ? AND b.vaccination_status = 'pending'"; $params[] = $today;
} elseif ($view === 'completed') {
    $sql  .= " AND b.vaccination_status <> 'pending'";
    $order = ' ORDER BY b.appointment_date DESC, b.appointment_time DESC';
} else {
    $order = ' ORDER BY b.appointment_date DESC, b.appointment_time DESC';
}

$stmt = $pdo->prepare($sql . $order);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle  = 'Appointments';
$activeMenu = 'appointments.php';
require_once __DIR__ . '/../includes/hospital_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">Appointments</h4>
  <p class="text-muted mb-0">Approved appointments for <?= e($hospital['hospital_name']) ?>. Mark each child once the vaccine is given.</p>
</div>

<ul class="nav nav-pills mb-3 gap-2">
  <?php foreach ($views as $key => $label): ?>
    <li class="nav-item">
      <a class="nav-link <?= $view === $key ? 'active' : '' ?>" href="?view=<?= $key ?>">
        <?= $label ?> <span class="badge text-bg-light ms-1"><?= (int)$counts[$key] ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="card">
  <div class="card-body">
    <input type="search" class="form-control mb-3" style="max-width: 320px;"
           placeholder="Search by child or parent..." data-table-search="#apptTable">

    <div class="table-responsive">
      <table class="table table-hover table-stack mb-0" id="apptTable">
        <thead>
          <tr><th>Date &amp; Time</th><th>Child</th><th>Vaccine</th><th>Parent</th><th>Status</th><th class="text-end">Action</th></tr>
        </thead>
        <tbody>
          <?php if (!$appointments): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No appointments in this list.</td></tr>
          <?php endif; ?>

          <?php foreach ($appointments as $a): ?>
            <tr>
              <td data-label="Date &amp; Time">
                <div><?= format_date($a['appointment_date']) ?></div>
                <small class="text-muted"><?= date('h:i A', strtotime($a['appointment_time'])) ?></small>
              </td>
              <td data-label="Child">
                <div class="fw-semibold"><?= e($a['child_name']) ?></div>
                <small class="text-muted"><?= e(child_age($a['dob'])) ?> old</small>
              </td>
              <td data-label="Vaccine"><?= e($a['vaccine_name']) ?></td>
              <td data-label="Parent">
                <div><?= e($a['parent_name']) ?></div>
                <small class="text-muted"><?= e($a['parent_phone']) ?></small>
              </td>
              <td data-label="Status"><?= status_badge($a['vaccination_status']) ?></td>
              <td class="text-end text-nowrap">
                <?php if ($a['vaccination_status'] !== 'pending'): ?>
                  <small class="text-muted"><i class="bi bi-lock me-1"></i>Recorded</small>
                <?php elseif ($a['appointment_date'] > $today): ?>
                  <small class="text-muted">Opens on appointment day</small>
                <?php else: ?>
                  <form method="post" action="<?= BASE_URL ?>/hospital/update_status.php" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <input type="hidden" name="view" value="<?= e($view) ?>">
                    <input type="hidden" name="action" value="vaccinated">
                    <button type="submit" class="btn btn-sm btn-success"
                            data-confirm="Mark <?= e($a['child_name']) ?> as VACCINATED? This cannot be undone.">
                      <i class="bi bi-check-lg"></i> Vaccinated
                    </button>
                  </form>
                  <form method="post" action="<?= BASE_URL ?>/hospital/update_status.php" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <input type="hidden" name="view" value="<?= e($view) ?>">
                    <input type="hidden" name="action" value="not_vaccinated">
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                            data-confirm="Mark <?= e($a['child_name']) ?> as NOT vaccinated? This cannot be undone.">
                      <i class="bi bi-x-lg"></i> Not vaccinated
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/hospital_footer.php'; ?>
