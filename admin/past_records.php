<?php
// admin/past_records.php - Parents ki darj ki hui purani vaccinations: Verify ya Reject
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

$allowed = ['pending', 'verified', 'rejected'];
$status  = in_array($_GET['status'] ?? '', $allowed, true) ? $_GET['status'] : 'pending';

$counts = $pdo->query('SELECT status, COUNT(*) FROM past_vaccinations GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->prepare(
    "SELECT p.id, p.given_date, p.place, p.status, p.created_at,
            c.name AS child_name, c.dob, u.name AS parent_name, u.phone AS parent_phone, v.vaccine_name
     FROM past_vaccinations p
     JOIN children c ON c.id = p.child_id
     JOIN users    u ON u.id = c.parent_id
     JOIN vaccines v ON v.id = p.vaccine_id
     WHERE p.status = ?
     ORDER BY p.created_at DESC, p.id DESC"
);
$stmt->execute([$status]);
$records = $stmt->fetchAll();

$pageTitle  = 'Past Records';
$activeMenu = 'past_records.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">Past Vaccination Records</h4>
  <p class="text-muted mb-0">Earlier vaccinations entered by parents. Verified records appear on the child's certificate.</p>
</div>

<ul class="nav nav-pills mb-3 gap-2">
  <?php foreach ($allowed as $s): ?>
    <li class="nav-item">
      <a class="nav-link <?= $status === $s ? 'active' : '' ?>" href="?status=<?= $s ?>">
        <?= ucfirst($s) ?> <span class="badge text-bg-light ms-1"><?= (int)($counts[$s] ?? 0) ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover table-stack mb-0">
        <thead>
          <tr><th>Child</th><th>Parent</th><th>Vaccine</th><th>Given on</th><th>Place</th>
              <th class="text-end"><?= $status === 'pending' ? 'Action' : 'Status' ?></th></tr>
        </thead>
        <tbody>
          <?php if (!$records): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No <?= e($status) ?> records.</td></tr>
          <?php endif; ?>

          <?php foreach ($records as $r): ?>
            <tr>
              <td data-label="Child">
                <div class="fw-semibold"><?= e($r['child_name']) ?></div>
                <small class="text-muted">Born <?= format_date($r['dob']) ?></small>
              </td>
              <td data-label="Parent">
                <div><?= e($r['parent_name']) ?></div>
                <small class="text-muted"><?= e($r['parent_phone']) ?></small>
              </td>
              <td data-label="Vaccine"><?= e($r['vaccine_name']) ?></td>
              <td data-label="Given on"><?= format_date($r['given_date']) ?></td>
              <td data-label="Place"><?= e($r['place']) ?></td>
              <?php if ($r['status'] === 'pending'): ?>
                <td class="text-end text-nowrap">
                  <?php foreach (['verify' => ['success', 'bi-check-lg', 'Verify'], 'reject' => ['outline-danger', 'bi-x-lg', 'Reject']] as $act => [$btn, $icon, $label]): ?>
                    <form method="post" action="<?= BASE_URL ?>/admin/past_action.php" class="d-inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <input type="hidden" name="action" value="<?= $act ?>">
                      <button type="submit" class="btn btn-sm btn-<?= $btn ?>"
                              <?= $act === 'reject' ? 'data-confirm="Reject this record?"' : '' ?>>
                        <i class="bi <?= $icon ?>"></i> <?= $label ?>
                      </button>
                    </form>
                  <?php endforeach; ?>
                </td>
              <?php else: ?>
                <td class="text-end" data-label="Status"><?= status_badge($r['status']) ?></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
