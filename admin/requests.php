<?php
// admin/requests.php - Parents ki appointment requests: Approve ya Reject
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

// URL se aaya status sirf in 3 mein se ho sakta hai (whitelist), warna 'pending'
$allowed = ['pending', 'approved', 'rejected'];
$status  = in_array($_GET['status'] ?? '', $allowed, true) ? $_GET['status'] : 'pending';

// Har tab ke saath ginti: ['pending' => 1, 'approved' => 3, ...]
$counts = $pdo->query("SELECT approval_status, COUNT(*) FROM bookings GROUP BY approval_status")
              ->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->prepare(
    "SELECT b.id, b.appointment_date, b.appointment_time, b.approval_status,
            c.name AS child_name, u.name AS parent_name, u.phone AS parent_phone,
            v.vaccine_name, v.availability, h.hospital_name
     FROM bookings b
     JOIN children  c ON c.id = b.child_id
     JOIN users     u ON u.id = b.parent_id
     JOIN vaccines  v ON v.id = b.vaccine_id
     JOIN hospitals h ON h.id = b.hospital_id
     WHERE b.approval_status = ?
     ORDER BY b.appointment_date ASC, b.id ASC"
);
$stmt->execute([$status]);
$requests = $stmt->fetchAll();

$pageTitle  = 'Requests';
$activeMenu = 'requests.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">Appointment Requests</h4>
  <p class="text-muted mb-0">Approve or reject vaccination appointments requested by parents.</p>
</div>

<ul class="nav nav-pills mb-3 gap-2">
  <?php foreach ($allowed as $s): ?>
    <li class="nav-item">
      <a class="nav-link <?= $status === $s ? 'active' : '' ?>" href="?status=<?= $s ?>">
        <?= ucfirst($s) ?>
        <span class="badge text-bg-light ms-1"><?= (int)($counts[$s] ?? 0) ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover table-stack mb-0">
        <thead>
          <tr>
            <th>Child</th><th>Parent</th><th>Vaccine</th><th>Hospital</th><th>Appointment</th>
            <th class="text-end"><?= $status === 'pending' ? 'Action' : 'Status' ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$requests): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No <?= e($status) ?> requests.</td></tr>
          <?php endif; ?>

          <?php foreach ($requests as $r): ?>
            <tr>
              <td data-label="Child" class="fw-semibold"><?= e($r['child_name']) ?></td>
              <td data-label="Parent">
                <div><?= e($r['parent_name']) ?></div>
                <small class="text-muted"><?= e($r['parent_phone']) ?></small>
              </td>
              <td data-label="Vaccine">
                <div><?= e($r['vaccine_name']) ?></div>
                <?php if ($r['availability'] !== 'available'): ?>
                  <small class="text-danger">Currently unavailable</small>
                <?php endif; ?>
              </td>
              <td data-label="Hospital"><?= e($r['hospital_name']) ?></td>
              <td data-label="Appointment">
                <div><?= format_date($r['appointment_date']) ?></div>
                <small class="text-muted"><?= date('h:i A', strtotime($r['appointment_time'])) ?></small>
              </td>

              <?php if ($r['approval_status'] === 'pending'): ?>
                <td class="text-end text-nowrap">
                  <form method="post" action="<?= BASE_URL ?>/admin/request_action.php" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Approve</button>
                  </form>
                  <form method="post" action="<?= BASE_URL ?>/admin/request_action.php" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                            data-confirm="Reject this request?"><i class="bi bi-x-lg"></i> Reject</button>
                  </form>
                </td>
              <?php else: ?>
                <td class="text-end" data-label="Status"><?= status_badge($r['approval_status']) ?></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
