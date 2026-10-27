<?php
// parent/reports.php - Bache ka vaccination record (report) aur certificate ka button
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/schedule.php';

$parentId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM children WHERE parent_id = ? ORDER BY dob DESC, id DESC');
$stmt->execute([$parentId]);
$children = $stmt->fetchAll();

// Kaunsa bacha: sirf apne bachon mein se
$childId = (int)($_GET['child'] ?? 0);
$child   = null;
foreach ($children as $c) {
    if ((int)$c['id'] === $childId) { $child = $c; }
}
if ($childId > 0 && !$child) {
    set_flash('danger', 'Child not found.');
    redirect('parent/reports.php');
}
if (!$child && $children) {
    $child = $children[0];
}

$records = [];
$vaccinated = $awaiting = $missed = $done = $total = 0;
$pastList = [];
$verifiedPast = 0;

if ($child) {
    // Report mein sirf approved appointments (asli vaccinations)
    $stmt = $pdo->prepare(
        "SELECT b.appointment_date, b.appointment_time, b.vaccination_status, v.vaccine_name, h.hospital_name
         FROM bookings b
         JOIN vaccines  v ON v.id = b.vaccine_id
         JOIN hospitals h ON h.id = b.hospital_id
         WHERE b.child_id = ? AND b.approval_status = 'approved'
         ORDER BY b.appointment_date DESC, b.id DESC"
    );
    $stmt->execute([$child['id']]);
    $records = $stmt->fetchAll();

    foreach ($records as $r) {
        if ($r['vaccination_status'] === 'vaccinated')          { $vaccinated++; }
        elseif ($r['vaccination_status'] === 'not_vaccinated')  { $missed++; }
        else                                                    { $awaiting++; }
    }

    // Poore schedule mein se kitni complete (Day 9 wala function)
    $stmt = $pdo->prepare(
        "SELECT child_id, vaccine_id, approval_status, vaccination_status, appointment_date
         FROM bookings WHERE child_id = ? AND approval_status <> 'rejected'"
    );
    $stmt->execute([$child['id']]);
    $vaccines = $pdo->query(
        'SELECT id, vaccine_name, recommended_age, recommended_age_days, availability FROM vaccines'
    )->fetchAll();
    $childBookings = $stmt->fetchAll();
    foreach (past_records_by_child($pdo, [$child['id']])[$child['id']] ?? [] as $pr) {
        $childBookings[] = $pr;
    }
    $rows  = vaccine_schedule($child['dob'], $vaccines, $childBookings);
    $total = count($rows);
    $done  = count(array_filter($rows, fn($r) => $r['status'] === 'done'));

    // Purani (parent ki darj ki hui) vaccines ki list
    $stmt = $pdo->prepare(
        'SELECT p.given_date, p.place, p.status, v.vaccine_name
         FROM past_vaccinations p JOIN vaccines v ON v.id = p.vaccine_id
         WHERE p.child_id = ? ORDER BY p.given_date DESC'
    );
    $stmt->execute([$child['id']]);
    $pastList = $stmt->fetchAll();
    foreach ($pastList as $pl) {
        if ($pl['status'] === 'verified') { $verifiedPast++; }
    }
}
$pct = $total ? (int)round($done * 100 / $total) : 0;
$hasData = (!empty($records) || !empty($pastList));   // kuch chhapne ko hai ya nahi

$pageTitle  = 'Vaccination Report';
$activeMenu = 'reports.php';
require_once __DIR__ . '/../includes/parent_header.php';
?>

<?php if (!$child): ?>

  <div class="card">
    <div class="card-body p-5 text-center">
      <div class="icon-box mx-auto mb-3"><i class="bi bi-person-hearts"></i></div>
      <h5>No children added yet</h5>
      <p class="text-muted mb-3">Add your child to see the vaccination report.</p>
      <a href="<?= BASE_URL ?>/parent/child_form.php" class="btn btn-primary">Add Child</a>
    </div>
  </div>

<?php else: ?>

  <!-- Sirf print mein -->
  <div class="print-only mb-3">
    <h3><?= APP_NAME ?> - Vaccination Report</h3>
    <p class="mb-0">Child: <strong><?= e($child['name']) ?></strong> &nbsp;|&nbsp; Born: <?= format_date($child['dob']) ?>
      &nbsp;|&nbsp; Parent: <?= e($_SESSION['name']) ?> &nbsp;|&nbsp; Generated: <?= date('d M Y, h:i A') ?></p>
  </div>

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 no-print">
    <div>
      <h4 class="mb-1">Vaccination Report</h4>
      <p class="text-muted mb-0">Record of <?= e($child['name']) ?> (<?= e(child_age($child['dob'])) ?> old)</p>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-primary" data-print <?= $hasData ? '' : 'disabled title="Nothing to print yet"' ?>><i class="bi bi-printer me-1"></i>Print</button>
      <?php if ($vaccinated > 0 || $verifiedPast > 0): ?>
        <a href="<?= BASE_URL ?>/parent/certificate.php?child=<?= (int)$child['id'] ?>" target="_blank" class="btn btn-primary">
          <i class="bi bi-award me-1"></i>Certificate
        </a>
      <?php else: ?>
        <button class="btn btn-primary" disabled title="Available after the first completed vaccination">
          <i class="bi bi-award me-1"></i>Certificate
        </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (count($children) > 1): ?>
    <ul class="nav nav-pills mb-3 gap-2 no-print">
      <?php foreach ($children as $c): ?>
        <li class="nav-item">
          <a class="nav-link <?= (int)$c['id'] === (int)$child['id'] ? 'active' : '' ?>" href="?child=<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <!-- Progress -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between small mb-1">
        <span class="text-muted">Overall schedule progress</span><span><?= $done ?> of <?= $total ?> vaccines (<?= $pct ?>%)</span>
      </div>
      <div class="progress" style="height:10px;"><div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div></div>
    </div>
  </div>

  <!-- Summary -->
  <div class="row g-3 mb-4">
    <?php foreach ([['Vaccinated', $vaccinated, 'bi-shield-check', 'success'],
                    ['Awaiting', $awaiting, 'bi-hourglass-split', 'warning'],
                    ['Missed', $missed, 'bi-x-octagon', 'danger']] as [$label, $value, $icon, $color]): ?>
      <div class="col-4">
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

  <!-- Record -->
  <div class="card">
    <div class="card-body">
      <h5 class="mb-3">Vaccination Record</h5>
      <div class="table-responsive">
        <table class="table table-hover table-stack mb-0">
          <thead><tr><th>Date</th><th>Vaccine</th><th>Hospital</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (!$records): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">No approved appointments yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($records as $r): ?>
              <tr>
                <td data-label="Date">
                  <div><?= format_date($r['appointment_date']) ?></div>
                  <small class="text-muted"><?= date('h:i A', strtotime($r['appointment_time'])) ?></small>
                </td>
                <td data-label="Vaccine" class="fw-semibold"><?= e($r['vaccine_name']) ?></td>
                <td data-label="Hospital"><?= e($r['hospital_name']) ?></td>
                <td data-label="Status"><?= status_badge($r['vaccination_status']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Purani vaccines (parent ki taraf se) -->
  <div class="card mt-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Earlier Vaccinations</h5>
        <a href="<?= BASE_URL ?>/parent/past_vaccinations.php?child=<?= (int)$child['id'] ?>" class="small text-decoration-none no-print">Add or manage</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-stack mb-0">
          <thead><tr><th>Date</th><th>Vaccine</th><th>Place</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (!$pastList): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">No earlier vaccinations added.</td></tr>
            <?php endif; ?>
            <?php foreach ($pastList as $pl): ?>
              <tr>
                <td data-label="Date"><?= format_date($pl['given_date']) ?></td>
                <td data-label="Vaccine" class="fw-semibold"><?= e($pl['vaccine_name']) ?></td>
                <td data-label="Place"><?= e($pl['place']) ?></td>
                <td data-label="Status"><?= status_badge($pl['status']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/parent_footer.php'; ?>
