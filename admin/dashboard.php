<?php
// admin/dashboard.php - Admin ka main page: ginti (stats), charts aur haal ki bookings
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

// ---------- 1) Stat cards ----------
// [label, value, icon, color, link] - in queries mein user ka input nahi, isliye query() theek hai
$stats = [
    ['Total Children',   $pdo->query("SELECT COUNT(*) FROM children")->fetchColumn(),                                        'bi-people',          'primary', 'children.php'],
    ['Hospitals',        $pdo->query("SELECT COUNT(*) FROM hospitals")->fetchColumn(),                                       'bi-hospital',        'info',    'hospitals.php'],
    ['Pending Requests', $pdo->query("SELECT COUNT(*) FROM bookings WHERE approval_status = 'pending'")->fetchColumn(),       'bi-hourglass-split', 'warning', 'requests.php'],
    ['Vaccinated',       $pdo->query("SELECT COUNT(*) FROM bookings WHERE vaccination_status = 'vaccinated'")->fetchColumn(), 'bi-shield-check',    'success', 'bookings.php?vaccination=vaccinated'],
];

// ---------- 2) Charts ka data ----------
// a) Vaccination ki halat (approved bookings)
$st = $pdo->query(
    "SELECT COALESCE(SUM(vaccination_status = 'vaccinated'), 0)     AS vaccinated,
            COALESCE(SUM(vaccination_status = 'pending'), 0)        AS awaiting,
            COALESCE(SUM(vaccination_status = 'not_vaccinated'), 0) AS missed
     FROM bookings WHERE approval_status = 'approved'"
)->fetch();
$statusData = [(int)$st['vaccinated'], (int)$st['awaiting'], (int)$st['missed']];

// b) Pichle 4 mahine + is mahine + agla mahina (6 mahine), jis mahine mein koi booking na ho wahan 0
$start = new DateTime('first day of this month');
$start->modify('-4 months');
$end = (clone $start)->modify('+6 months');

$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(appointment_date, '%Y-%m') AS ym,
            COUNT(*) AS booked,
            SUM(vaccination_status = 'vaccinated') AS vaccinated
     FROM bookings
     WHERE approval_status <> 'rejected' AND appointment_date >= ? AND appointment_date < ?
     GROUP BY ym"
);
$stmt->execute([$start->format('Y-m-d'), $end->format('Y-m-d')]);
$byMonth = [];
foreach ($stmt->fetchAll() as $r) { $byMonth[$r['ym']] = $r; }

$monthLabels = $monthBooked = $monthVaccinated = [];
$cursor = clone $start;
for ($i = 0; $i < 6; $i++) {
    $key = $cursor->format('Y-m');
    $monthLabels[]     = $cursor->format('M Y');
    $monthBooked[]     = (int)($byMonth[$key]['booked'] ?? 0);
    $monthVaccinated[] = (int)($byMonth[$key]['vaccinated'] ?? 0);
    $cursor->modify('+1 month');
}

// c) Sab se zyada bookings wale 5 hospitals
$rows = $pdo->query(
    "SELECT h.hospital_name AS label, COUNT(*) AS total
     FROM bookings b JOIN hospitals h ON h.id = b.hospital_id
     WHERE b.approval_status = 'approved'
     GROUP BY h.id, h.hospital_name ORDER BY total DESC LIMIT 5"
)->fetchAll();
$hospLabels = array_column($rows, 'label');
$hospValues = array_map('intval', array_column($rows, 'total'));

// d) Sab se zyada book hone wali 5 vaccines
$rows = $pdo->query(
    "SELECT v.vaccine_name AS label, COUNT(*) AS total
     FROM bookings b JOIN vaccines v ON v.id = b.vaccine_id
     WHERE b.approval_status <> 'rejected'
     GROUP BY v.id, v.vaccine_name ORDER BY total DESC LIMIT 5"
)->fetchAll();
$vaccLabels = array_column($rows, 'label');
$vaccValues = array_map('intval', array_column($rows, 'total'));

$chartData = [
    'status'    => $statusData,
    'months'    => ['labels' => $monthLabels, 'booked' => $monthBooked, 'vaccinated' => $monthVaccinated],
    'hospitals' => ['labels' => $hospLabels, 'values' => $hospValues],
    'vaccines'  => ['labels' => $vaccLabels, 'values' => $vaccValues],
];
$hasStatus = array_sum($statusData) > 0;
$hasMonths = array_sum($monthBooked) > 0;

// ---------- 3) Haal ki bookings ----------
$recent = $pdo->query(
    "SELECT b.appointment_date, b.approval_status, b.vaccination_status,
            c.name AS child_name, h.hospital_name, v.vaccine_name
     FROM bookings b
     JOIN children  c ON c.id = b.child_id
     JOIN hospitals h ON h.id = b.hospital_id
     JOIN vaccines  v ON v.id = b.vaccine_id
     ORDER BY b.created_at DESC, b.id DESC
     LIMIT 5"
)->fetchAll();

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">Welcome back, <?= e($_SESSION['name']) ?> 👋</h4>
  <p class="text-muted mb-0">Here is what is happening in the vaccination system today.</p>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($stats as [$label, $value, $icon, $color, $link]): ?>
    <div class="col-6 col-xl-3">
      <a href="<?= BASE_URL ?>/admin/<?= $link ?>" class="text-decoration-none d-block h-100">
        <div class="card stat-card card-hover h-100">
          <div class="icon-box text-<?= $color ?>" style="background: var(--bs-<?= $color ?>-bg-subtle);">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <div>
            <div class="stat-number"><?= (int)$value ?></div>
            <div class="stat-label"><?= e($label) ?></div>
          </div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<!-- ============ CHARTS ============ -->
<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="mb-3">Bookings by month</h6>
        <?php if ($hasMonths): ?>
          <div class="chart-box"><canvas id="chartMonths"></canvas></div>
        <?php else: ?>
          <div class="chart-empty">No bookings in these months yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="mb-3">Vaccination status</h6>
        <?php if ($hasStatus): ?>
          <div class="chart-box"><canvas id="chartStatus"></canvas></div>
        <?php else: ?>
          <div class="chart-empty">No approved appointments yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="mb-3">Top hospitals</h6>
        <?php if ($hospValues): ?>
          <div class="chart-box"><canvas id="chartHospitals"></canvas></div>
        <?php else: ?>
          <div class="chart-empty">No data yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="mb-3">Most booked vaccines</h6>
        <?php if ($vaccValues): ?>
          <div class="chart-box"><canvas id="chartVaccines"></canvas></div>
        <?php else: ?>
          <div class="chart-empty">No data yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ============ RECENT BOOKINGS ============ -->
<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Recent Bookings</h5>
      <a href="<?= BASE_URL ?>/admin/bookings.php" class="small text-decoration-none">View all</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover table-stack mb-0">
        <thead>
          <tr><th>Child</th><th>Vaccine</th><th class="d-none d-md-table-cell">Hospital</th><th>Date</th><th>Approval</th><th>Vaccination</th></tr>
        </thead>
        <tbody>
          <?php if (!$recent): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No bookings yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recent as $r): ?>
            <tr>
              <td data-label="Child"><?= e($r['child_name']) ?></td>
              <td data-label="Vaccine"><?= e($r['vaccine_name']) ?></td>
              <td class="d-none d-md-table-cell" data-label="Hospital"><?= e($r['hospital_name']) ?></td>
              <td data-label="Date"><?= format_date($r['appointment_date']) ?></td>
              <td data-label="Approval"><?= status_badge($r['approval_status']) ?></td>
              <td data-label="Vaccination"><?= status_badge($r['vaccination_status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
// ---------- Chart.js: footer ke baad load hota hai ($extraScripts hook) ----------
ob_start();
?>
<script src="<?= vendor('chartjs/chart.umd.min.js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js') ?>"></script>
<script>
(function () {
  if (typeof Chart === 'undefined') { return; }   // CDN na chale to dashboard phir bhi theek rahe

  // JSON_HEX_*: hospital ke naam mein <script> jaisa kuch ho to bhi page nahi tootta
  const data = <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  Chart.defaults.font.family = "'Poppins', system-ui, sans-serif";
  Chart.defaults.color = '#64748b';
  const teal = '#0f766e', teal2 = '#5eead4', amber = '#f59e0b', red = '#ef4444';
  const intTicks = { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } };

  function make(id, config) {
    const el = document.getElementById(id);
    if (el) { new Chart(el, config); }
  }

  make('chartMonths', {
    type: 'bar',
    data: {
      labels: data.months.labels,
      datasets: [
        { label: 'Booked',     data: data.months.booked,     backgroundColor: teal2, borderRadius: 6 },
        { label: 'Vaccinated', data: data.months.vaccinated, backgroundColor: teal,  borderRadius: 6 }
      ]
    },
    options: { responsive: true, maintainAspectRatio: false,
      scales: { y: intTicks, x: { grid: { display: false } } },
      plugins: { legend: { position: 'bottom' } } }
  });

  make('chartStatus', {
    type: 'doughnut',
    data: {
      labels: ['Vaccinated', 'Awaiting', 'Missed'],
      datasets: [{ data: data.status, backgroundColor: [teal, amber, red], borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '65%',
      plugins: { legend: { position: 'bottom' } } }
  });

  make('chartHospitals', {
    type: 'bar',
    data: { labels: data.hospitals.labels,
            datasets: [{ label: 'Appointments', data: data.hospitals.values, backgroundColor: teal, borderRadius: 6 }] },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
      scales: { x: intTicks, y: { grid: { display: false } } }, plugins: { legend: { display: false } } }
  });

  make('chartVaccines', {
    type: 'bar',
    data: { labels: data.vaccines.labels,
            datasets: [{ label: 'Bookings', data: data.vaccines.values, backgroundColor: teal2, borderRadius: 6 }] },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
      scales: { x: intTicks, y: { grid: { display: false } } }, plugins: { legend: { display: false } } }
  });
})();
</script>
<?php
$extraScripts = ob_get_clean();
require_once __DIR__ . '/../includes/admin_footer.php';
