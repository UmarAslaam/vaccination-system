<?php
// parent/children.php - Parent ke apne bachon ki list
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';

// "WHERE parent_id = ?" sab se zaroori: parent sirf APNE bache dekhe
$stmt = $pdo->prepare(
    "SELECT c.*,
            (SELECT COUNT(*) FROM bookings b WHERE b.child_id = c.id) AS total_bookings,
            (SELECT COUNT(*) FROM bookings b WHERE b.child_id = c.id AND b.vaccination_status = 'vaccinated') AS vaccinated_count
     FROM children c
     WHERE c.parent_id = ?
     ORDER BY c.id DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$children = $stmt->fetchAll();

$pageTitle  = 'My Children';
$activeMenu = 'children.php';
require_once __DIR__ . '/../includes/parent_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h4 class="mb-1">My Children</h4>
    <p class="text-muted mb-0">Add and manage your children's details.</p>
  </div>
  <a href="<?= BASE_URL ?>/parent/child_form.php" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Add Child
  </a>
</div>

<?php if (!$children): ?>
  <div class="card">
    <div class="card-body p-5 text-center">
      <div class="icon-box mx-auto mb-3"><i class="bi bi-person-hearts"></i></div>
      <h5>No children added yet</h5>
      <p class="text-muted mb-3">Add your child's details to start tracking vaccinations.</p>
      <a href="<?= BASE_URL ?>/parent/child_form.php" class="btn btn-primary">Add your first child</a>
    </div>
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($children as $c): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card card-hover h-100">
          <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="icon-box flex-shrink-0">
                <i class="bi bi-gender-<?= $c['gender'] === 'male' ? 'male' : 'female' ?>"></i>
              </div>
              <div class="min-w-0">
                <h5 class="mb-0 text-truncate"><?= e($c['name']) ?></h5>
                <small class="text-muted"><?= e(child_age($c['dob'])) ?> old</small>
              </div>
            </div>

            <ul class="list-unstyled small mb-3">
              <li class="d-flex justify-content-between py-1"><span class="text-muted">Date of birth</span><span><?= format_date($c['dob']) ?></span></li>
              <li class="d-flex justify-content-between py-1"><span class="text-muted">Gender</span><span><?= e(ucfirst($c['gender'])) ?></span></li>
              <li class="d-flex justify-content-between py-1"><span class="text-muted">Blood group</span><span><?= e($c['blood_group'] ?: '-') ?></span></li>
              <li class="d-flex justify-content-between py-1">
                <span class="text-muted">Vaccinations</span>
                <span><?= (int)$c['vaccinated_count'] ?> done / <?= (int)$c['total_bookings'] ?> booked</span>
              </li>
            </ul>

            <div class="d-flex gap-2">
              <a href="<?= BASE_URL ?>/parent/child_form.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary flex-fill">
                <i class="bi bi-pencil me-1"></i>Edit
              </a>
              <form method="post" action="<?= BASE_URL ?>/parent/child_delete.php" class="flex-fill">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger w-100"
                        data-confirm="Delete <?= e($c['name']) ?>? This cannot be undone.">
                  <i class="bi bi-trash me-1"></i>Delete
                </button>
              </form>
            </div>
            <a href="<?= BASE_URL ?>/parent/past_vaccinations.php?child=<?= (int)$c['id'] ?>" class="btn btn-sm btn-light w-100 mt-2">
              <i class="bi bi-clock-history me-1"></i>Past vaccinations
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/parent_footer.php'; ?>
