<?php
// admin/suggestions.php - Parents ke batae hue naye hospitals: verify karke list mein jorna ya reject karna
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

$allowed = ['pending', 'approved', 'rejected'];
$status  = in_array($_GET['status'] ?? '', $allowed, true) ? $_GET['status'] : 'pending';

$counts = $pdo->query('SELECT status, COUNT(*) FROM hospital_suggestions GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);

// existing_id: kya ye hospital pehle se list mein hai? (duplicate se bachne ke liye)
$stmt = $pdo->prepare(
    "SELECT s.id, s.hospital_name, s.city, s.address, s.phone, s.status, s.created_at,
            u.name AS parent_name, u.phone AS parent_phone,
            (SELECT h.id FROM hospitals h
             WHERE LOWER(h.hospital_name) = LOWER(s.hospital_name) AND LOWER(h.location) = LOWER(s.city)
             LIMIT 1) AS existing_id
     FROM hospital_suggestions s
     JOIN users u ON u.id = s.parent_id
     WHERE s.status = ?
     ORDER BY s.created_at DESC, s.id DESC"
);
$stmt->execute([$status]);
$items = $stmt->fetchAll();

$pageTitle  = 'Hospital Requests';
$activeMenu = 'suggestions.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">Hospital Requests</h4>
  <p class="text-muted mb-0">Hospitals suggested by parents. Verify them, then add them to the list so parents can book.</p>
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
          <tr><th>Hospital</th><th>Details</th><th>Requested by</th><th>Date</th>
              <th class="text-end"><?= $status === 'pending' ? 'Action' : 'Status' ?></th></tr>
        </thead>
        <tbody>
          <?php if (!$items): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No <?= e($status) ?> requests.</td></tr>
          <?php endif; ?>

          <?php foreach ($items as $r): ?>
            <tr>
              <td data-label="Hospital">
                <div class="fw-semibold"><?= e($r['hospital_name']) ?></div>
                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($r['city']) ?></small>
                <?php if ($r['existing_id'] && $r['status'] === 'pending'): ?>
                  <div><span class="badge rounded-pill text-bg-info">Already in the list</span></div>
                <?php endif; ?>
              </td>
              <td data-label="Details">
                <div><?= e($r['address'] ?: 'Address not given') ?></div>
                <small class="text-muted"><?= e($r['phone'] ?: 'Phone not given') ?></small>
              </td>
              <td data-label="Requested by">
                <div><?= e($r['parent_name']) ?></div>
                <small class="text-muted"><?= e($r['parent_phone']) ?></small>
              </td>
              <td data-label="Date"><?= format_date($r['created_at']) ?></td>
              <?php if ($r['status'] === 'pending'): ?>
                <td class="text-end text-nowrap">
                  <?php foreach (['approve' => ['success', 'bi-check-lg', $r['existing_id'] ? 'Approve' : 'Approve & add'],
                                  'reject'  => ['outline-danger', 'bi-x-lg', 'Reject']] as $act => [$btn, $icon, $label]): ?>
                    <form method="post" action="<?= BASE_URL ?>/admin/suggestion_action.php" class="d-inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <input type="hidden" name="action" value="<?= $act ?>">
                      <button type="submit" class="btn btn-sm btn-<?= $btn ?>"
                              <?= $act === 'reject' ? 'data-confirm="Reject this hospital request?"' : '' ?>>
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
