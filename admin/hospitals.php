<?php
// admin/hospitals.php - Hospitals ki list (Read) + Edit/Delete ke buttons
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

// Har hospital ke saath uski bookings ki ginti (subquery)
$hospitals = $pdo->query(
    "SELECT h.*, u.status AS user_status,
            (SELECT COUNT(*) FROM bookings b WHERE b.hospital_id = h.id) AS total_bookings
     FROM hospitals h
     LEFT JOIN users u ON u.id = h.user_id
     ORDER BY (u.status = 'pending') DESC, h.id DESC"
)->fetchAll();

$pageTitle  = 'Hospitals';
$activeMenu = 'hospitals.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h4 class="mb-1">Hospitals</h4>
    <p class="text-muted mb-0">Add, update or remove hospitals.</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/hospital_form.php" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Add Hospital
  </a>
</div>

<div class="card">
  <div class="card-body">
    <input type="search" class="form-control mb-3" style="max-width: 320px;"
           placeholder="Search hospitals..." data-table-search="#hospitalsTable">

    <div class="table-responsive">
      <table class="table table-hover table-stack mb-0" id="hospitalsTable">
        <thead>
          <tr><th class="d-none d-md-table-cell">#</th><th>Hospital</th><th>Location</th><th class="d-none d-md-table-cell">Phone</th><th class="d-none d-lg-table-cell">Account</th><th>Bookings</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          <?php if (!$hospitals): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No hospitals yet. Click "Add Hospital".</td></tr>
          <?php endif; ?>

          <?php foreach ($hospitals as $i => $h): ?>
            <tr>
              <td class="d-none d-md-table-cell" data-label="#"><?= $i + 1 ?></td>
              <td data-label="Hospital">
                <div class="fw-semibold"><?= e($h['hospital_name']) ?></div>
                <small class="text-muted"><?= e($h['address']) ?></small>
              </td>
              <td data-label="Location"><?= e($h['location']) ?></td>
              <td class="d-none d-md-table-cell" data-label="Phone"><?= e($h['phone']) ?></td>
              <td class="d-none d-lg-table-cell" data-label="Account">
                <?php if ($h['user_id']): ?>
                  <?= status_badge($h['user_status']) ?>
                <?php else: ?>
                  <span class="badge rounded-pill text-bg-secondary">Added by admin</span>
                <?php endif; ?>
              </td>
              <td data-label="Bookings"><?= (int)$h['total_bookings'] ?></td>
              <td class="text-end text-nowrap">
                <?php if (($h['user_status'] ?? '') === 'pending'): ?>
                  <!-- Naya hospital: admin ki manzoori -->
                  <form method="post" action="<?= BASE_URL ?>/admin/user_action.php" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$h['user_id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="back" value="hospitals">
                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Approve</button>
                  </form>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/admin/hospital_form.php?id=<?= (int)$h['id'] ?>" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-pencil"></i> Edit
                </a>

                <!-- Delete POST form hai (link nahi), CSRF token ke saath -->
                <form method="post" action="<?= BASE_URL ?>/admin/hospital_delete.php" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"
                          data-confirm="Delete this hospital? Its bookings will also be deleted.">
                    <i class="bi bi-trash"></i> Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
