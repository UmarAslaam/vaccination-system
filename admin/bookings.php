<?php
// admin/bookings.php - Sab bookings, filters ke saath (status aur date range)
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

// ---------- Filters (URL se: ?approval=approved&from=2026-10-01) ----------
$approval    = $_GET['approval'] ?? '';
$vaccination = $_GET['vaccination'] ?? '';
$from        = $_GET['from'] ?? '';
$to          = $_GET['to'] ?? '';

$where  = [];
$params = [];

// Har filter pehle parakha jata hai, phir ? ke zariye query mein jata hai
if (in_array($approval, ['pending', 'approved', 'rejected'], true)) {
    $where[]  = 'b.approval_status = ?';
    $params[] = $approval;
} else {
    $approval = '';
}
if (in_array($vaccination, ['pending', 'vaccinated', 'not_vaccinated'], true)) {
    $where[]  = 'b.vaccination_status = ?';
    $params[] = $vaccination;
} else {
    $vaccination = '';
}
if (is_valid_date($from)) {
    $where[]  = 'b.appointment_date >= ?';
    $params[] = $from;
} else {
    $from = '';
}
if (is_valid_date($to)) {
    $where[]  = 'b.appointment_date <= ?';
    $params[] = $to;
} else {
    $to = '';
}

$sql = "SELECT b.id, b.appointment_date, b.appointment_time, b.approval_status, b.vaccination_status,
               c.name AS child_name, u.name AS parent_name,
               v.vaccine_name, h.hospital_name, h.user_id AS hospital_user
        FROM bookings b
        JOIN children  c ON c.id = b.child_id
        JOIN users     u ON u.id = b.parent_id
        JOIN vaccines  v ON v.id = b.vaccine_id
        JOIN hospitals h ON h.id = b.hospital_id"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY b.appointment_date DESC, b.id DESC';

// Kul kitni bookings (pagination ke liye): wohi shartein, sirf ginti
$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings b' . ($where ? ' WHERE ' . implode(' AND ', $where) : ''));
$stmt->execute($params);
$p = paginate((int)$stmt->fetchColumn(), 15, $_GET['page'] ?? 1);

// LIMIT/OFFSET: sirf (int) kiye hue numbers
$stmt = $pdo->prepare($sql . ' LIMIT ' . (int)$p['per_page'] . ' OFFSET ' . (int)$p['offset']);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$hasFilter = ($approval !== '' || $vaccination !== '' || $from !== '' || $to !== '');

$pageTitle  = 'Bookings';
$activeMenu = 'bookings.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">All Bookings</h4>
  <p class="text-muted mb-0">Vaccination appointments with date and status filters. For hospitals without their own login, you can record the vaccination here.</p>
</div>

<!-- Filter form: GET hai (sirf dekhna), isliye CSRF ki zaroorat nahi -->
<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-6 col-lg-3">
        <label for="approval" class="form-label small mb-1">Approval</label>
        <select class="form-select" id="approval" name="approval">
          <option value="">All</option>
          <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
            <option value="<?= $s ?>" <?= $approval === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-lg-3">
        <label for="vaccination" class="form-label small mb-1">Vaccination</label>
        <select class="form-select" id="vaccination" name="vaccination">
          <option value="">All</option>
          <option value="pending"        <?= $vaccination === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="vaccinated"     <?= $vaccination === 'vaccinated' ? 'selected' : '' ?>>Vaccinated</option>
          <option value="not_vaccinated" <?= $vaccination === 'not_vaccinated' ? 'selected' : '' ?>>Not Vaccinated</option>
        </select>
      </div>
      <div class="col-6 col-lg-2">
        <label for="from" class="form-label small mb-1">From</label>
        <input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>">
      </div>
      <div class="col-6 col-lg-2">
        <label for="to" class="form-label small mb-1">To</label>
        <input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>">
      </div>
      <div class="col-12 col-lg-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
        <?php if ($hasFilter): ?>
          <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn btn-outline-secondary">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <input type="search" class="form-control" style="max-width: 320px;"
             placeholder="Search this page..." data-table-search="#bookingsTable">
      <span class="text-muted small"><?= (int)$p['total'] ?> booking(s) found</span>
    </div>

    <div class="table-responsive">
      <table class="table table-hover table-stack mb-0" id="bookingsTable">
        <thead>
          <tr>
            <th>Child</th><th>Parent</th><th>Vaccine</th><th>Hospital</th>
            <th>Date &amp; Time</th><th>Approval</th><th>Vaccination</th><th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$bookings): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No bookings match these filters.</td></tr>
          <?php endif; ?>

          <?php foreach ($bookings as $b): ?>
            <tr>
              <td data-label="Child" class="fw-semibold"><?= e($b['child_name']) ?></td>
              <td data-label="Parent"><?= e($b['parent_name']) ?></td>
              <td data-label="Vaccine"><?= e($b['vaccine_name']) ?></td>
              <td data-label="Hospital"><?= e($b['hospital_name']) ?></td>
              <td data-label="Date &amp; Time">
                <div><?= format_date($b['appointment_date']) ?></div>
                <small class="text-muted"><?= date('h:i A', strtotime($b['appointment_time'])) ?></small>
              </td>
              <td data-label="Approval"><?= status_badge($b['approval_status']) ?></td>
              <td data-label="Vaccination"><?= status_badge($b['vaccination_status']) ?></td>
              <?php
                // Sirf: approved, abhi pending, appointment ka din aa chuka, aur hospital ka apna account nahi
                $adminCanRecord = $b['approval_status'] === 'approved' && $b['vaccination_status'] === 'pending'
                               && $b['hospital_user'] === null && $b['appointment_date'] <= date('Y-m-d');
              ?>
              <?php if ($adminCanRecord): ?>
                <td class="text-end text-nowrap">
                  <?php foreach (['vaccinated' => ['success', 'bi-check-lg', 'Vaccinated', ''],
                                  'not_vaccinated' => ['outline-danger', 'bi-x-lg', 'Not vaccinated', 'data-confirm="Mark as NOT vaccinated?"']] as $act => [$btn, $icon, $label, $confirm]): ?>
                    <form method="post" action="<?= BASE_URL ?>/admin/booking_status.php" class="d-inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                      <input type="hidden" name="action" value="<?= $act ?>">
                      <input type="hidden" name="qs" value="<?= e($_SERVER['QUERY_STRING'] ?? '') ?>">
                      <button type="submit" class="btn btn-sm btn-<?= $btn ?>" <?= $confirm ?>><i class="bi <?= $icon ?>"></i> <?= $label ?></button>
                    </form>
                  <?php endforeach; ?>
                </td>
              <?php else: ?>
                <td class="text-end"></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= pagination_html($p, array_filter(['approval' => $approval, 'vaccination' => $vaccination, 'from' => $from, 'to' => $to], fn($v) => $v !== '')) ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
