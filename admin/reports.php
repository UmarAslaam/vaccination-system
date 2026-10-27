<?php
// admin/reports.php - Vaccination report: date wise, vaccine wise, hospital wise, Print aur CSV
// Report mein sirf APPROVED appointments gini jati hain (yehi asli vaccinations hain).
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

// ---------- Filters (URL se) ----------
$from       = $_GET['from'] ?? '';
$to         = $_GET['to'] ?? '';
$hospitalId = (int)($_GET['hospital'] ?? 0);

$where  = ["b.approval_status = 'approved'"];
$params = [];

if (is_valid_date($from)) { $where[] = 'b.appointment_date >= ?'; $params[] = $from; } else { $from = ''; }
if (is_valid_date($to))   { $where[] = 'b.appointment_date <= ?'; $params[] = $to; }   else { $to = ''; }
if ($hospitalId > 0)      { $where[] = 'b.hospital_id = ?';       $params[] = $hospitalId; }

$whereSql = ' WHERE ' . implode(' AND ', $where);

// ---------- 1) Summary (4 ginti ek hi query se) ----------
// SUM(condition) MySQL mein sahi shartein ginta hai. COALESCE: koi row na ho to NULL ki jagah 0.
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(b.vaccination_status = 'vaccinated'), 0)     AS vaccinated,
            COALESCE(SUM(b.vaccination_status = 'not_vaccinated'), 0) AS not_vaccinated,
            COALESCE(SUM(b.vaccination_status = 'pending'), 0)        AS awaiting
     FROM bookings b" . $whereSql
);
$stmt->execute($params);
$summary = $stmt->fetch();

// ---------- 2) Vaccine wise ----------
$stmt = $pdo->prepare(
    "SELECT v.vaccine_name,
            COUNT(*) AS total,
            SUM(b.vaccination_status = 'vaccinated')     AS vaccinated,
            SUM(b.vaccination_status = 'not_vaccinated') AS not_vaccinated,
            SUM(b.vaccination_status = 'pending')        AS awaiting
     FROM bookings b
     JOIN vaccines v ON v.id = b.vaccine_id" . $whereSql . "
     GROUP BY v.id, v.vaccine_name
     ORDER BY total DESC, v.vaccine_name ASC"
);
$stmt->execute($params);
$byVaccine = $stmt->fetchAll();

// ---------- 3) Date wise detail (Child, Vaccination) ----------
$stmt = $pdo->prepare(
    "SELECT b.appointment_date, b.appointment_time, b.vaccination_status,
            c.name AS child_name, u.name AS parent_name, v.vaccine_name, h.hospital_name
     FROM bookings b
     JOIN children  c ON c.id = b.child_id
     JOIN users     u ON u.id = b.parent_id
     JOIN vaccines  v ON v.id = b.vaccine_id
     JOIN hospitals h ON h.id = b.hospital_id" . $whereSql . "
     ORDER BY b.appointment_date DESC, b.id DESC"
);
$stmt->execute($params);
$details = $stmt->fetchAll();

// ---------- CSV download (HTML se pehle, warna file kharab hogi) ----------
if (($_GET['export'] ?? '') === 'csv') {
    // Data hi na ho to khali file download nahi karwate, wapas bhej kar bata dete hain
    if (!$details) {
        set_flash('warning', 'There is no data to export for this selection.');
        $back = http_build_query(array_filter(
            ['from' => $from, 'to' => $to, 'hospital' => $hospitalId ?: ''],
            fn($v) => $v !== ''
        ));
        redirect('admin/reports.php' . ($back ? '?' . $back : ''));
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="vaccination-report-' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF";                                   // BOM: Excel mein Urdu/special letters sahi dikhen
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Time', 'Child', 'Parent', 'Vaccine', 'Hospital', 'Vaccination Status'], ',', '"', '');
    foreach ($details as $d) {
        fputcsv($out, [
            $d['appointment_date'],
            date('h:i A', strtotime($d['appointment_time'])),
            csv_safe($d['child_name']),
            csv_safe($d['parent_name']),
            csv_safe($d['vaccine_name']),
            csv_safe($d['hospital_name']),
            ucfirst(str_replace('_', ' ', $d['vaccination_status'])),
        ], ',', '"', '');
    }
    fclose($out);
    exit;
}

// ---------- Page ke liye tayyari ----------
$hospitals = $pdo->query('SELECT id, hospital_name FROM hospitals ORDER BY hospital_name')->fetchAll();

$hospitalName = 'All hospitals';
foreach ($hospitals as $h) {
    if ((int)$h['id'] === $hospitalId) { $hospitalName = $h['hospital_name']; }
}
$periodLabel = ($from || $to)
    ? ($from ? format_date($from) : 'Start') . '  to  ' . ($to ? format_date($to) : 'Today')
    : 'All time';

// Jaldi ke date buttons
$monthStart = date('Y-m-01');
$today      = date('Y-m-d');
$last30     = date('Y-m-d', strtotime('-30 days'));

// CSV link mein wohi filters
$csvUrl = '?' . http_build_query(array_filter(
    ['from' => $from, 'to' => $to, 'hospital' => $hospitalId ?: '', 'export' => 'csv'],
    fn($v) => $v !== ''
));

$cards = [
    ['Appointments', $summary['total'],          'bi-calendar-check', 'primary'],
    ['Vaccinated',   $summary['vaccinated'],     'bi-shield-check',   'success'],
    ['Not Vaccinated', $summary['not_vaccinated'], 'bi-x-octagon',    'danger'],
    ['Awaiting',     $summary['awaiting'],       'bi-hourglass-split', 'warning'],
];

$pageTitle  = 'Reports';
$activeMenu = 'reports.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Sirf print mein dikhta hai -->
<div class="print-only mb-3">
  <h3><?= APP_NAME ?> - Vaccination Report</h3>
  <p class="mb-0">Period: <?= e($periodLabel) ?> &nbsp;|&nbsp; Hospital: <?= e($hospitalName) ?> &nbsp;|&nbsp; Generated: <?= date('d M Y, h:i A') ?></p>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 no-print">
  <div>
    <h4 class="mb-1">Vaccination Reports</h4>
    <p class="text-muted mb-0">Approved appointments only. Showing: <strong><?= e($periodLabel) ?></strong></p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e($csvUrl) ?>" class="btn btn-outline-primary <?= $details ? '' : 'disabled' ?>"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
    <button type="button" class="btn btn-primary" data-print <?= $details ? '' : 'disabled title="Nothing to print for this selection"' ?>><i class="bi bi-printer me-1"></i>Print</button>
  </div>
</div>

<!-- Filters -->
<div class="card mb-3 no-print">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-6 col-lg-3">
        <label for="from" class="form-label small mb-1">From</label>
        <input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>">
      </div>
      <div class="col-6 col-lg-3">
        <label for="to" class="form-label small mb-1">To</label>
        <input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>">
      </div>
      <div class="col-12 col-lg-3">
        <label for="hospital" class="form-label small mb-1">Hospital</label>
        <select class="form-select" id="hospital" name="hospital">
          <option value="0">All hospitals</option>
          <?php foreach ($hospitals as $h): ?>
            <option value="<?= (int)$h['id'] ?>" <?= $hospitalId === (int)$h['id'] ? 'selected' : '' ?>><?= e($h['hospital_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-lg-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel me-1"></i>Generate</button>
        <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
    <div class="mt-3 small">
      <span class="text-muted me-2">Quick:</span>
      <a class="btn btn-sm btn-light" href="?from=<?= $monthStart ?>&to=<?= $today ?>">This month</a>
      <a class="btn btn-sm btn-light" href="?from=<?= $last30 ?>&to=<?= $today ?>">Last 30 days</a>
      <a class="btn btn-sm btn-light" href="?">All time</a>
    </div>
  </div>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-4">
  <?php foreach ($cards as [$label, $value, $icon, $color]): ?>
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

<!-- Vaccine wise -->
<div class="card mb-4">
  <div class="card-body">
    <h5 class="mb-3">Vaccine-wise Summary</h5>
    <div class="table-responsive">
      <table class="table table-hover table-stack mb-0">
        <thead>
          <tr><th>Vaccine</th><th>Total</th><th>Vaccinated</th><th>Not Vaccinated</th><th>Awaiting</th><th style="min-width:150px;">Coverage</th></tr>
        </thead>
        <tbody>
          <?php if (!$byVaccine): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No data for this selection.</td></tr>
          <?php endif; ?>
          <?php foreach ($byVaccine as $r):
              $pct = $r['total'] > 0 ? (int)round($r['vaccinated'] * 100 / $r['total']) : 0; ?>
            <tr>
              <td data-label="Vaccine" class="fw-semibold"><?= e($r['vaccine_name']) ?></td>
              <td data-label="Total"><?= (int)$r['total'] ?></td>
              <td data-label="Vaccinated"><?= (int)$r['vaccinated'] ?></td>
              <td data-label="Not Vaccinated"><?= (int)$r['not_vaccinated'] ?></td>
              <td data-label="Awaiting"><?= (int)$r['awaiting'] ?></td>
              <td data-label="Coverage">
                <div class="d-flex align-items-center gap-2 justify-content-end justify-content-md-start">
                  <div class="progress flex-fill" style="height:8px; max-width:120px;">
                    <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
                  </div>
                  <small><?= $pct ?>%</small>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Date wise detail -->
<div class="card">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <h5 class="mb-0">Date-wise Report <span class="text-muted fs-6">(<?= count($details) ?>)</span></h5>
      <input type="search" class="form-control no-print" style="max-width: 280px;"
             placeholder="Search in report..." data-table-search="#detailTable">
    </div>
    <div class="table-responsive">
      <table class="table table-hover table-stack mb-0" id="detailTable">
        <thead>
          <tr><th>Date</th><th>Child</th><th>Parent</th><th>Vaccine</th><th>Hospital</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (!$details): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No vaccinations found for this selection.</td></tr>
          <?php endif; ?>
          <?php foreach ($details as $d): ?>
            <tr>
              <td data-label="Date">
                <div><?= format_date($d['appointment_date']) ?></div>
                <small class="text-muted"><?= date('h:i A', strtotime($d['appointment_time'])) ?></small>
              </td>
              <td data-label="Child" class="fw-semibold"><?= e($d['child_name']) ?></td>
              <td data-label="Parent"><?= e($d['parent_name']) ?></td>
              <td data-label="Vaccine"><?= e($d['vaccine_name']) ?></td>
              <td data-label="Hospital"><?= e($d['hospital_name']) ?></td>
              <td data-label="Status"><?= status_badge($d['vaccination_status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
