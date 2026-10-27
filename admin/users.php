<?php
// admin/users.php - Parents aur hospitals ke accounts: dekhna, manzoor karna, band/chalu karna
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

// ---------- Filters (sab whitelist/saaf kiye hue) ----------
$roleF   = in_array($_GET['role'] ?? '', ['parent', 'hospital'], true) ? $_GET['role'] : '';
$statusF = in_array($_GET['status'] ?? '', ['active', 'pending', 'disabled'], true) ? $_GET['status'] : '';
$q       = mb_substr(trim($_GET['q'] ?? ''), 0, 60);

$where  = ["u.role <> 'admin'"];
$params = [];
if ($roleF !== '')   { $where[] = 'u.role = ?';   $params[] = $roleF; }
if ($statusF !== '') { $where[] = 'u.status = ?'; $params[] = $statusF; }
if ($q !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR h.hospital_name LIKE ?)';
    $like    = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
$whereSql = ' WHERE ' . implode(' AND ', $where);
$from     = ' FROM users u LEFT JOIN hospitals h ON h.user_id = u.id';

// Har status ki ginti (tabs ke liye)
$counts = $pdo->query("SELECT status, COUNT(*) FROM users WHERE role <> 'admin' GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$counts['all'] = array_sum($counts);

// Kul kitne (pagination)
$stmt = $pdo->prepare('SELECT COUNT(*)' . $from . $whereSql);
$stmt->execute($params);
$p = paginate((int)$stmt->fetchColumn(), 10, $_GET['page'] ?? 1);

// LIMIT/OFFSET mein sirf (int) kiye hue number jaate hain (placeholder nahi, kyunki ye numbers hain)
$stmt = $pdo->prepare(
    "SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.created_at, u.city, h.hospital_name, h.license_no, h.location AS hospital_city,
            (SELECT COUNT(*) FROM children c WHERE c.parent_id = u.id) AS children_count"
    . $from . $whereSql
    . " ORDER BY (u.status = 'pending') DESC, u.created_at DESC, u.id DESC"
    . ' LIMIT ' . (int)$p['per_page'] . ' OFFSET ' . (int)$p['offset']
);
$stmt->execute($params);
$users = $stmt->fetchAll();

$keep = array_filter(['role' => $roleF, 'status' => $statusF, 'q' => $q], fn($v) => $v !== '');

$pageTitle  = 'Users';
$activeMenu = 'users.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">Users</h4>
  <p class="text-muted mb-0">Approve new hospitals, and enable or disable parent and hospital accounts.</p>
</div>

<ul class="nav nav-pills mb-3 gap-2">
  <?php foreach (['' => 'All', 'pending' => 'Pending', 'active' => 'Active', 'disabled' => 'Disabled'] as $key => $label): ?>
    <li class="nav-item">
      <a class="nav-link <?= $statusF === $key ? 'active' : '' ?>"
         href="?<?= e(http_build_query(array_filter(['status' => $key, 'role' => $roleF, 'q' => $q], fn($v) => $v !== ''))) ?>">
        <?= $label ?> <span class="badge text-bg-light ms-1"><?= (int)($key === '' ? $counts['all'] : ($counts[$key] ?? 0)) ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <input type="hidden" name="status" value="<?= e($statusF) ?>">
      <div class="col-md-3">
        <label for="role" class="form-label small mb-1">Role</label>
        <select class="form-select" id="role" name="role">
          <option value="">All roles</option>
          <option value="parent"   <?= $roleF === 'parent'   ? 'selected' : '' ?>>Parents</option>
          <option value="hospital" <?= $roleF === 'hospital' ? 'selected' : '' ?>>Hospitals</option>
        </select>
      </div>
      <div class="col-md-6">
        <label for="q" class="form-label small mb-1">Search</label>
        <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Name, email or hospital name">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button>
        <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="text-muted small mb-2"><?= (int)$p['total'] ?> account(s) found</div>
    <div class="table-responsive">
      <table class="table table-hover table-stack mb-0">
        <thead>
          <tr><th>Name</th><th>Role</th><th>Details</th><th>Joined</th><th>Status</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          <?php if (!$users): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No accounts match.</td></tr>
          <?php endif; ?>

          <?php foreach ($users as $u): ?>
            <tr>
              <td data-label="Name">
                <div class="fw-semibold"><?= e($u['name']) ?></div>
                <small class="text-muted"><?= e($u['email']) ?></small>
              </td>
              <td data-label="Role"><?= e(ucfirst($u['role'])) ?></td>
              <td data-label="Details">
                <?php if ($u['role'] === 'hospital'): ?>
                  <?= e($u['hospital_name'] ?: '-') ?>
                  <div><small class="text-muted">License: <strong><?= e($u['license_no'] ?: 'not given') ?></strong> &bull; <?= e($u['hospital_city'] ?: '-') ?></small></div>
                <?php else: ?>
                  <?= (int)$u['children_count'] ?> child<?= (int)$u['children_count'] === 1 ? '' : 'ren' ?>
                  <div><small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($u['city'] ?: 'City not given') ?></small></div>
                <?php endif; ?>
                <div><small class="text-muted"><?= e($u['phone']) ?></small></div>
              </td>
              <td data-label="Joined"><?= format_date($u['created_at']) ?></td>
              <td data-label="Status"><?= status_badge($u['status']) ?></td>
              <td class="text-end text-nowrap">
                <?php
                  // Is halat mein kaunsi actions mumkin hain
                  $actions = [];
                  if ($u['status'] === 'pending')  { $actions = ['approve' => ['success', 'bi-check-lg', 'Approve'], 'disable' => ['outline-danger', 'bi-x-lg', 'Reject']]; }
                  if ($u['status'] === 'active')   { $actions = ['disable' => ['outline-danger', 'bi-slash-circle', 'Disable']]; }
                  if ($u['status'] === 'disabled') { $actions = ['enable'  => ['outline-primary', 'bi-arrow-counterclockwise', 'Enable']]; }
                ?>
                <?php foreach ($actions as $act => [$btn, $icon, $label]): ?>
                  <form method="post" action="<?= BASE_URL ?>/admin/user_action.php" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <input type="hidden" name="action" value="<?= $act ?>">
                    <input type="hidden" name="qs" value="<?= e($_SERVER['QUERY_STRING'] ?? '') ?>">
                    <button type="submit" class="btn btn-sm btn-<?= $btn ?>"
                            <?= $act === 'disable' ? 'data-confirm="' . e($label . ' ' . $u['name'] . '?') . '"' : '' ?>>
                      <i class="bi <?= $icon ?>"></i> <?= $label ?>
                    </button>
                  </form>
                <?php endforeach; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= pagination_html($p, $keep) ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
