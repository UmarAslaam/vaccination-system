<?php
// parent/past_vaccinations.php - Pehle se lag chuki (purani) vaccines darj karna
// Parent darj karta hai, admin verify karta hai. Schedule inhein foran "lag chuki" ginta hai,
// lekin certificate mein sirf VERIFIED records aate hain.
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';

$parentId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM children WHERE parent_id = ? ORDER BY dob DESC, id DESC');
$stmt->execute([$parentId]);
$children = $stmt->fetchAll();

// Kaunsa bacha: sirf apne bachon mein se (URL ya form se)
$childId = (int)($_GET['child'] ?? $_POST['child_id'] ?? 0);
$child   = null;
foreach ($children as $c) {
    if ((int)$c['id'] === $childId) { $child = $c; }
}
if ($childId > 0 && !$child) {
    set_flash('danger', 'Child not found.');
    redirect('parent/past_vaccinations.php');
}
if (!$child && $children) {
    $child = $children[0];
}

$errors  = [];
$old     = ['vaccine_id' => '', 'given_date' => '', 'place' => ''];
$addable = [];
$records = [];

if ($child) {
    $vaccines = $pdo->query('SELECT id, vaccine_name, recommended_age FROM vaccines ORDER BY recommended_age_days ASC, id ASC')->fetchAll();

    // Jin vaccines ka record pehle se hai (booking ya purana record), unhein dobara nahi
    $taken = [];
    $stmt = $pdo->prepare("SELECT vaccine_id FROM bookings WHERE child_id = ? AND approval_status <> 'rejected'");
    $stmt->execute([$child['id']]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $v) { $taken[] = (int)$v; }
    $stmt = $pdo->prepare('SELECT vaccine_id FROM past_vaccinations WHERE child_id = ?');
    $stmt->execute([$child['id']]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $v) { $taken[] = (int)$v; }

    foreach ($vaccines as $v) {
        if (!in_array((int)$v['id'], $taken, true)) { $addable[] = $v; }
    }
    $addableIds = array_map(fn($v) => (int)$v['id'], $addable);

    // ---------- Form submit ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($old as $key => $value) {
            $old[$key] = trim($_POST[$key] ?? '');
        }

        if (!verify_csrf()) {
            $errors[] = 'Session expired. Please try again.';
        }
        if (!in_array((int)$old['vaccine_id'], $addableIds, true)) {
            $errors[] = 'Please select a vaccine from the list.';
        }
        if (!is_valid_date($old['given_date'])) {
            $errors[] = 'Please enter the date the vaccine was given.';
        } elseif ($old['given_date'] > date('Y-m-d')) {
            $errors[] = 'The date cannot be in the future.';
        } elseif ($old['given_date'] < $child['dob']) {
            $errors[] = 'The date cannot be before the child was born.';
        }
        if (mb_strlen($old['place']) < 3 || mb_strlen($old['place']) > 150) {
            $errors[] = 'Please enter where it was given (3 to 150 characters).';
        }

        if (!$errors) {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO past_vaccinations (child_id, vaccine_id, given_date, place) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$child['id'], (int)$old['vaccine_id'], $old['given_date'], $old['place']]);
                set_flash('success', 'Saved. Our team will verify this record. Until then it counts as vaccinated on your schedule.');
                redirect('parent/past_vaccinations.php?child=' . (int)$child['id']);
            } catch (PDOException $ex) {
                $errors[] = 'This vaccine has already been recorded.';
            }
        }
    }

    $stmt = $pdo->prepare(
        'SELECT p.id, p.given_date, p.place, p.status, v.vaccine_name
         FROM past_vaccinations p
         JOIN vaccines v ON v.id = p.vaccine_id
         WHERE p.child_id = ?
         ORDER BY p.given_date DESC, p.id DESC'
    );
    $stmt->execute([$child['id']]);
    $records = $stmt->fetchAll();
}

$pageTitle  = 'Past Vaccinations';
$activeMenu = 'past_vaccinations.php';
require_once __DIR__ . '/../includes/parent_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">Past Vaccinations</h4>
  <p class="text-muted mb-0">Already vaccinated before joining? Add those vaccines here so they are not shown as overdue.</p>
</div>

<?php if (!$child): ?>

  <div class="card">
    <div class="card-body p-5 text-center">
      <div class="icon-box mx-auto mb-3"><i class="bi bi-person-hearts"></i></div>
      <h5>No children added yet</h5>
      <a href="<?= BASE_URL ?>/parent/child_form.php" class="btn btn-primary mt-2">Add Child</a>
    </div>
  </div>

<?php else: ?>

  <?php if (count($children) > 1): ?>
    <ul class="nav nav-pills mb-3 gap-2">
      <?php foreach ($children as $c): ?>
        <li class="nav-item">
          <a class="nav-link <?= (int)$c['id'] === (int)$child['id'] ? 'active' : '' ?>" href="?child=<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-body p-4">
          <h5 class="mb-3">Add a vaccine for <?= e($child['name']) ?></h5>

          <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0 ps-3">
              <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul></div>
          <?php endif; ?>

          <?php if (!$addable): ?>
            <p class="text-muted mb-0">Every vaccine already has a record for <?= e($child['name']) ?>.</p>
          <?php else: ?>
            <form method="post" class="needs-validation" novalidate>
              <?= csrf_field() ?>
              <input type="hidden" name="child_id" value="<?= (int)$child['id'] ?>">

              <div class="mb-3">
                <label for="vaccine_id" class="form-label">Vaccine</label>
                <select class="form-select" id="vaccine_id" name="vaccine_id" required>
                  <option value="">Select a vaccine</option>
                  <?php foreach ($addable as $v): ?>
                    <option value="<?= (int)$v['id'] ?>" <?= (int)$old['vaccine_id'] === (int)$v['id'] ? 'selected' : '' ?>>
                      <?= e($v['vaccine_name']) ?> (<?= e($v['recommended_age']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please select a vaccine.</div>
              </div>

              <div class="mb-3">
                <label for="given_date" class="form-label">Date it was given</label>
                <input type="date" class="form-control" id="given_date" name="given_date" value="<?= e($old['given_date']) ?>"
                       min="<?= e($child['dob']) ?>" max="<?= date('Y-m-d') ?>" required>
                <div class="invalid-feedback">Please choose a valid date.</div>
              </div>

              <div class="mb-4">
                <label for="place" class="form-label">Where was it given?</label>
                <input type="text" class="form-control" id="place" name="place" value="<?= e($old['place']) ?>"
                       placeholder="Hospital or clinic name" minlength="3" maxlength="150" required>
                <div class="invalid-feedback">Please enter the hospital or clinic name.</div>
              </div>

              <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Save Record</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="mb-3">Recorded earlier vaccines</h5>
          <div class="table-responsive">
            <table class="table table-hover table-stack mb-0">
              <thead><tr><th>Vaccine</th><th>Date</th><th>Place</th><th>Status</th><th class="text-end">Action</th></tr></thead>
              <tbody>
                <?php if (!$records): ?>
                  <tr><td colspan="5" class="text-center text-muted py-4">Nothing added yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($records as $r): ?>
                  <tr>
                    <td data-label="Vaccine" class="fw-semibold"><?= e($r['vaccine_name']) ?></td>
                    <td data-label="Date"><?= format_date($r['given_date']) ?></td>
                    <td data-label="Place"><?= e($r['place']) ?></td>
                    <td data-label="Status"><?= status_badge($r['status']) ?></td>
                    <td class="text-end">
                      <?php if ($r['status'] !== 'verified'): ?>
                        <form method="post" action="<?= BASE_URL ?>/parent/past_delete.php" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                          <input type="hidden" name="child_id" value="<?= (int)$child['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Remove this record?">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      <?php else: ?>
                        <small class="text-muted"><i class="bi bi-lock me-1"></i>Verified</small>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/parent_footer.php'; ?>
