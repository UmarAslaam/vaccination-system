<?php
// admin/vaccine_form.php - Vaccine Add aur Edit (ek hi file)
//   vaccine_form.php       -> nayi vaccine
//   vaccine_form.php?id=4  -> vaccine 4 ko edit
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$errors = [];

// Umar ki unit -> kitne din (din mein save karte hain, taake baad mein hisaab asaan ho)
$units = ['days' => 1, 'weeks' => 7, 'months' => 30, 'years' => 365];

$data = [
    'vaccine_name' => '',
    'description'  => '',
    'age_value'    => '0',
    'age_unit'     => 'weeks',
    'availability' => 'available',
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM vaccines WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('danger', 'Vaccine not found.');
        redirect('admin/vaccines.php');
    }
    [$value, $unit] = split_age_days($row['recommended_age_days']);   // 42 din -> 6 weeks
    $data = [
        'vaccine_name' => $row['vaccine_name'],
        'description'  => (string)$row['description'],
        'age_value'    => (string)$value,
        'age_unit'     => $unit,
        'availability' => $row['availability'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $value) {
        $data[$key] = trim($_POST[$key] ?? '');
    }

    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try again.';
    }
    if (mb_strlen($data['vaccine_name']) < 3 || mb_strlen($data['vaccine_name']) > 120) {
        $errors[] = 'Vaccine name must be between 3 and 120 characters.';
    }
    if (mb_strlen($data['description']) > 255) {
        $errors[] = 'Description can be at most 255 characters.';
    }
    if (!isset($units[$data['age_unit']])) {
        $errors[] = 'Please select a valid age unit.';
    }
    if (!ctype_digit($data['age_value'])) {
        $errors[] = 'Recommended age must be a whole number (0 or more).';
    }
    if (!in_array($data['availability'], ['available', 'unavailable'], true)) {
        $errors[] = 'Please select a valid availability.';
    }

    $days = 0;
    if (!$errors) {
        $days = (int)$data['age_value'] * $units[$data['age_unit']];
        if ($days > 365 * 18) {
            $errors[] = 'Recommended age is too large (maximum 18 years).';
        }
    }

    if (!$errors) {
        $label = age_label($days);       // "6 weeks" - dikhane ke liye text
        $desc  = $data['description'] === '' ? null : $data['description'];

        if ($isEdit) {
            $stmt = $pdo->prepare(
                'UPDATE vaccines
                 SET vaccine_name = ?, description = ?, recommended_age = ?, recommended_age_days = ?, availability = ?
                 WHERE id = ?'
            );
            $stmt->execute([$data['vaccine_name'], $desc, $label, $days, $data['availability'], $id]);
            set_flash('success', 'Vaccine updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO vaccines (vaccine_name, description, recommended_age, recommended_age_days, availability)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$data['vaccine_name'], $desc, $label, $days, $data['availability']]);
            set_flash('success', 'Vaccine added successfully.');
        }
        redirect('admin/vaccines.php');
    }
}

$pageTitle  = $isEdit ? 'Edit Vaccine' : 'Add Vaccine';
$activeMenu = 'vaccines.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
  <a href="<?= BASE_URL ?>/admin/vaccines.php" class="text-decoration-none small">
    <i class="bi bi-arrow-left me-1"></i>Back to vaccines
  </a>
  <h4 class="mt-2 mb-0"><?= e($pageTitle) ?></h4>
</div>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body p-4">

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" class="needs-validation" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-12">
              <label for="vaccine_name" class="form-label">Vaccine Name</label>
              <input type="text" class="form-control" id="vaccine_name" name="vaccine_name"
                     value="<?= e($data['vaccine_name']) ?>" minlength="3" maxlength="120" required>
              <div class="invalid-feedback">Please enter the vaccine name (min 3 characters).</div>
            </div>

            <div class="col-12">
              <label for="description" class="form-label">Description <span class="text-muted">(optional)</span></label>
              <input type="text" class="form-control" id="description" name="description"
                     value="<?= e($data['description']) ?>" maxlength="255">
            </div>

            <div class="col-md-6">
              <label for="age_value" class="form-label">Recommended Age</label>
              <div class="input-group">
                <input type="number" class="form-control" id="age_value" name="age_value"
                       value="<?= e($data['age_value']) ?>" min="0" max="999" required>
                <select class="form-select" name="age_unit" aria-label="Age unit">
                  <?php foreach ($units as $name => $mult): ?>
                    <option value="<?= $name ?>" <?= $data['age_unit'] === $name ? 'selected' : '' ?>><?= ucfirst($name) ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Enter a number (0 or more).</div>
              </div>
              <div class="form-text">Enter 0 for "At birth".</div>
            </div>

            <div class="col-md-6">
              <label for="availability" class="form-label">Availability</label>
              <select class="form-select" id="availability" name="availability">
                <option value="available"   <?= $data['availability'] === 'available'   ? 'selected' : '' ?>>Available</option>
                <option value="unavailable" <?= $data['availability'] === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
              </select>
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Update Vaccine' : 'Save Vaccine' ?>
            </button>
            <a href="<?= BASE_URL ?>/admin/vaccines.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
