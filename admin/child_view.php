<?php
// admin/child_view.php?id=3 - Ek bache ki poori profile aur vaccination history
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);

// URL se aayi id hai, isliye prepare() zaroori hai
$stmt = $pdo->prepare(
    'SELECT c.*, u.name AS parent_name, u.email AS parent_email, u.phone AS parent_phone
     FROM children c
     JOIN users u ON u.id = c.parent_id
     WHERE c.id = ?'
);
$stmt->execute([$id]);
$child = $stmt->fetch();

if (!$child) {
    set_flash('danger', 'Child not found.');
    redirect('admin/children.php');
}

// Is bache ki saari bookings (vaccination history)
$stmt = $pdo->prepare(
    'SELECT b.appointment_date, b.appointment_time, b.approval_status, b.vaccination_status,
            v.vaccine_name, h.hospital_name
     FROM bookings b
     JOIN vaccines  v ON v.id = b.vaccine_id
     JOIN hospitals h ON h.id = b.hospital_id
     WHERE b.child_id = ?
     ORDER BY b.appointment_date DESC'
);
$stmt->execute([$id]);
$history = $stmt->fetchAll();

$pageTitle  = 'Child Details';
$activeMenu = 'children.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
  <a href="<?= BASE_URL ?>/admin/children.php" class="text-decoration-none small">
    <i class="bi bi-arrow-left me-1"></i>Back to children
  </a>
  <h4 class="mt-2 mb-0"><?= e($child['name']) ?></h4>
</div>

<div class="row g-4">
  <!-- Profile -->
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body p-4">
        <div class="text-center mb-3">
          <div class="icon-box mb-2"><i class="bi bi-person-heart"></i></div>
          <h5 class="mb-0"><?= e($child['name']) ?></h5>
          <small class="text-muted"><?= e(child_age($child['dob'])) ?> old</small>
        </div>
        <ul class="list-unstyled small mb-0">
          <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Date of birth</span><span><?= format_date($child['dob']) ?></span></li>
          <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Gender</span><span><?= e(ucfirst($child['gender'])) ?></span></li>
          <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Blood group</span><span><?= e($child['blood_group'] ?: '-') ?></span></li>
          <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Parent</span><span><?= e($child['parent_name']) ?></span></li>
          <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Phone</span><span><?= e($child['parent_phone']) ?></span></li>
          <li class="d-flex justify-content-between py-2"><span class="text-muted">Email</span><span class="text-break text-end"><?= e($child['parent_email']) ?></span></li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Vaccination history -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="mb-3">Vaccination History</h5>
        <div class="table-responsive">
          <table class="table table-hover table-stack mb-0">
            <thead>
              <tr><th>Vaccine</th><th>Date</th><th class="d-none d-md-table-cell">Hospital</th><th>Approval</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php if (!$history): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No vaccination bookings yet.</td></tr>
              <?php endif; ?>
              <?php foreach ($history as $row): ?>
                <tr>
                  <td data-label="Vaccine"><?= e($row['vaccine_name']) ?></td>
                  <td class="text-nowrap" data-label="Date"><?= format_date($row['appointment_date']) ?></td>
                  <td class="d-none d-md-table-cell" data-label="Hospital"><?= e($row['hospital_name']) ?></td>
                  <td data-label="Approval"><?= status_badge($row['approval_status']) ?></td>
                  <td data-label="Status"><?= status_badge($row['vaccination_status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
