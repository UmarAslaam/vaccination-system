<?php
// admin/children.php - Sab bachon ki list (parent ke naam ke saath), search aur pages ke saath
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

$q = mb_substr(trim($_GET['q'] ?? ''), 0, 60);

$where  = '';
$params = [];
if ($q !== '') {
    $where  = ' WHERE (c.name LIKE ? OR u.name LIKE ?)';
    $like   = '%' . $q . '%';
    $params = [$like, $like];
}
$from = ' FROM children c JOIN users u ON u.id = c.parent_id';

$stmt = $pdo->prepare('SELECT COUNT(*)' . $from . $where);
$stmt->execute($params);
$p = paginate((int)$stmt->fetchColumn(), 10, $_GET['page'] ?? 1);

$stmt = $pdo->prepare(
    "SELECT c.*, u.name AS parent_name, u.phone AS parent_phone,
            (SELECT COUNT(*) FROM bookings b WHERE b.child_id = c.id)                                         AS total_bookings,
            (SELECT COUNT(*) FROM bookings b WHERE b.child_id = c.id AND b.vaccination_status = 'vaccinated') AS vaccinated_count"
    . $from . $where
    . ' ORDER BY c.id DESC LIMIT ' . (int)$p['per_page'] . ' OFFSET ' . (int)$p['offset']
);
$stmt->execute($params);
$children = $stmt->fetchAll();

$pageTitle  = 'Children';
$activeMenu = 'children.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">All Children</h4>
  <p class="text-muted mb-0">Every child registered by parents (<?= (int)$p['total'] ?> found).</p>
</div>

<div class="card">
  <div class="card-body">
    <form method="get" class="d-flex gap-2 mb-3" style="max-width: 480px;">
      <input type="search" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search by child or parent...">
      <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
      <?php if ($q !== ''): ?><a href="<?= BASE_URL ?>/admin/children.php" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
    </form>

    <div class="table-responsive">
      <table class="table table-hover table-stack mb-0" id="childrenTable">
        <thead>
          <tr>
            <th>Child</th>
            <th>Age</th>
            <th>Parent</th>
            <th class="d-none d-md-table-cell">Blood Group</th>
            <th class="d-none d-md-table-cell">Vaccinated</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$children): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No children found.</td></tr>
          <?php endif; ?>

          <?php foreach ($children as $c): ?>
            <tr>
              <td data-label="Child">
                <div class="fw-semibold">
                  <i class="bi bi-gender-<?= $c['gender'] === 'male' ? 'male text-primary' : 'female text-danger' ?> me-1"></i><?= e($c['name']) ?>
                </div>
                <small class="text-muted">Born <?= format_date($c['dob']) ?></small>
              </td>
              <td data-label="Age"><?= e(child_age($c['dob'])) ?></td>
              <td data-label="Parent">
                <div><?= e($c['parent_name']) ?></div>
                <small class="text-muted"><?= e($c['parent_phone']) ?></small>
              </td>
              <td class="d-none d-md-table-cell" data-label="Blood Group"><?= e($c['blood_group'] ?: '-') ?></td>
              <td class="d-none d-md-table-cell" data-label="Vaccinated">
                <span class="badge rounded-pill text-bg-success"><?= (int)$c['vaccinated_count'] ?></span>
                <small class="text-muted">of <?= (int)$c['total_bookings'] ?> bookings</small>
              </td>
              <td class="text-end">
                <a href="<?= BASE_URL ?>/admin/child_view.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-eye"></i><span class="d-none d-sm-inline"> View</span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= pagination_html($p, $q !== '' ? ['q' => $q] : []) ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
