<?php
// parent/my_bookings.php - Parent ki apni saari bookings, status ke saath
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';

$parentId = (int)$_SESSION['user_id'];

// Tab (whitelist): all / pending / approved / rejected
$tabs   = ['all', 'pending', 'approved', 'rejected'];
$status = in_array($_GET['status'] ?? '', $tabs, true) ? $_GET['status'] : 'all';

// Har tab ki ginti
$stmt = $pdo->prepare('SELECT approval_status, COUNT(*) FROM bookings WHERE parent_id = ? GROUP BY approval_status');
$stmt->execute([$parentId]);
$counts        = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$counts['all'] = array_sum($counts);

// WHERE parent_id = ? (ownership) hamesha, tab ka filter ho to usay jod do
$sql = "SELECT b.id, b.child_id, b.appointment_date, b.appointment_time, b.approval_status, b.vaccination_status,
               c.name AS child_name, v.vaccine_name, h.hospital_name
        FROM bookings b
        JOIN children  c ON c.id = b.child_id
        JOIN vaccines  v ON v.id = b.vaccine_id
        JOIN hospitals h ON h.id = b.hospital_id
        WHERE b.parent_id = ?";
$params = [$parentId];
if ($status !== 'all') {
    $sql     .= ' AND b.approval_status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY b.appointment_date DESC, b.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle  = 'My Bookings';
$activeMenu = 'my_bookings.php';
require_once __DIR__ . '/../includes/parent_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h4 class="mb-1">My Bookings</h4>
    <p class="text-muted mb-0">Track all your vaccination appointments.</p>
  </div>
  <a href="<?= BASE_URL ?>/parent/book_hospital.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Appointment</a>
</div>

<ul class="nav nav-pills mb-3 gap-2">
  <?php foreach ($tabs as $t): ?>
    <li class="nav-item">
      <a class="nav-link <?= $status === $t ? 'active' : '' ?>" href="?status=<?= $t ?>">
        <?= ucfirst($t) ?> <span class="badge text-bg-light ms-1"><?= (int)($counts[$t] ?? 0) ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="card">
  <div class="card-body">
    <input type="search" class="form-control mb-3" style="max-width: 320px;"
           placeholder="Search bookings..." data-table-search="#bookingsTable">

    <div class="table-responsive">
      <table class="table table-hover table-stack mb-0" id="bookingsTable">
        <thead>
          <tr>
            <th>Child</th><th>Vaccine</th><th>Hospital</th><th>Date &amp; Time</th>
            <th>Approval</th><th>Vaccination</th><th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$bookings): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No bookings here yet.</td></tr>
          <?php endif; ?>

          <?php foreach ($bookings as $b): ?>
            <tr>
              <td data-label="Child" class="fw-semibold"><?= e($b['child_name']) ?></td>
              <td data-label="Vaccine"><?= e($b['vaccine_name']) ?></td>
              <td data-label="Hospital"><?= e($b['hospital_name']) ?></td>
              <td data-label="Date &amp; Time">
                <div><?= format_date($b['appointment_date']) ?></div>
                <small class="text-muted"><?= date('h:i A', strtotime($b['appointment_time'])) ?></small>
              </td>
              <td data-label="Approval"><?= status_badge($b['approval_status']) ?></td>
              <td data-label="Vaccination"><?= status_badge($b['vaccination_status']) ?></td>
              <td class="text-end">
                <?php if ($b['approval_status'] === 'pending'): ?>
                  <!-- Sirf pending booking cancel ho sakti hai -->
                  <form method="post" action="<?= BASE_URL ?>/parent/booking_cancel.php" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                            data-confirm="Cancel this appointment request?"><i class="bi bi-x-lg"></i> Cancel</button>
                  </form>
                <?php elseif ($b['approval_status'] === 'rejected'): ?>
                  <a href="<?= BASE_URL ?>/parent/book_hospital.php?child=<?= (int)$b['child_id'] ?>" class="btn btn-sm btn-outline-primary">Book again</a>
                <?php else: ?>
                  <small class="text-muted">Confirmed</small>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/parent_footer.php'; ?>
