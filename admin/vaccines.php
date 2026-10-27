<?php
// admin/vaccines.php - Vaccines ki list, availability toggle, edit, delete
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

$vaccines = $pdo->query(
    "SELECT v.*, (SELECT COUNT(*) FROM bookings b WHERE b.vaccine_id = v.id) AS total_bookings
     FROM vaccines v
     ORDER BY v.recommended_age_days ASC, v.vaccine_name ASC"
)->fetchAll();

$pageTitle  = 'Vaccines';
$activeMenu = 'vaccines.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h4 class="mb-1">Vaccines</h4>
    <p class="text-muted mb-0">Manage the vaccine list and its availability.</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/vaccine_form.php" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Add Vaccine
  </a>
</div>

<div class="card">
  <div class="card-body">
    <input type="search" class="form-control mb-3" style="max-width: 320px;"
           placeholder="Search vaccines..." data-table-search="#vaccinesTable">

    <div class="table-responsive">
      <table class="table table-hover table-stack mb-0" id="vaccinesTable">
        <thead>
          <tr>
            <th>Vaccine</th>
            <th>Recommended Age</th>
            <th>Availability</th>
            <th class="d-none d-md-table-cell">Bookings</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$vaccines): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No vaccines yet. Click "Add Vaccine".</td></tr>
          <?php endif; ?>

          <?php foreach ($vaccines as $v): ?>
            <tr>
              <td data-label="Vaccine">
                <div class="fw-semibold"><?= e($v['vaccine_name']) ?></div>
                <small class="text-muted"><?= e($v['description']) ?></small>
              </td>
              <td data-label="Recommended Age"><?= e($v['recommended_age']) ?></td>
              <td data-label="Availability"><?= status_badge($v['availability']) ?></td>
              <td class="d-none d-md-table-cell" data-label="Bookings"><?= (int)$v['total_bookings'] ?></td>
              <td class="text-end text-nowrap">

                <!-- Availability toggle (POST + CSRF) -->
                <form method="post" action="<?= BASE_URL ?>/admin/vaccine_toggle.php" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-secondary"
                          title="Change availability">
                    <i class="bi bi-toggle-<?= $v['availability'] === 'available' ? 'on' : 'off' ?>"></i>
                    <span class="d-none d-lg-inline"><?= $v['availability'] === 'available' ? 'Mark Unavailable' : 'Mark Available' ?></span>
                  </button>
                </form>

                <a href="<?= BASE_URL ?>/admin/vaccine_form.php?id=<?= (int)$v['id'] ?>" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-pencil"></i><span class="d-none d-sm-inline"> Edit</span>
                </a>

                <form method="post" action="<?= BASE_URL ?>/admin/vaccine_delete.php" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this vaccine?">
                    <i class="bi bi-trash"></i><span class="d-none d-sm-inline"> Delete</span>
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
