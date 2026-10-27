<?php
// admin/hospital_form.php - Hospital Add (Create) aur Edit (Update), dono ek hi file se
//   hospital_form.php        -> naya hospital
//   hospital_form.php?id=3   -> hospital 3 ko edit
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

$id     = (int)($_GET['id'] ?? 0);      // (int) se URL mein kuch aur likha ho to 0 ban jata hai
$isEdit = $id > 0;
$data   = ['hospital_name' => '', 'address' => '', 'location' => '', 'phone' => '', 'license_no' => ''];
$errors = [];

// Edit mode: pehle purana data nikalo
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM hospitals WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('danger', 'Hospital not found.');
        redirect('admin/hospitals.php');
    }
    $data = array_map('strval', array_intersect_key($row, $data));
}

// Form submit hua
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $value) {
        $data[$key] = trim($_POST[$key] ?? '');
    }

    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try again.';
    }
    if (mb_strlen($data['hospital_name']) < 3 || mb_strlen($data['hospital_name']) > 150) {
        $errors[] = 'Hospital name must be between 3 and 150 characters.';
    }
    if ($data['address'] === '') {
        $errors[] = 'Address is required.';
    }
    if ($data['location'] === '') {
        $errors[] = 'City / location is required.';
    }
    if (!preg_match('/^[0-9+\-\s]{10,15}$/', $data['phone'])) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if ($data['license_no'] !== '' && !preg_match('/^[A-Za-z0-9\-\/\s]{3,60}$/', $data['license_no'])) {
        $errors[] = 'License number can have 3 to 60 letters, numbers, - or /.';
    }

    if (!$errors) {
        if ($isEdit) {
            $stmt = $pdo->prepare(
                'UPDATE hospitals SET hospital_name = ?, address = ?, location = ?, phone = ?, license_no = ? WHERE id = ?'
            );
            $stmt->execute([$data['hospital_name'], $data['address'], $data['location'], $data['phone'], $data['license_no'] === '' ? null : $data['license_no'], $id]);
            set_flash('success', 'Hospital updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO hospitals (hospital_name, address, location, phone, license_no) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$data['hospital_name'], $data['address'], $data['location'], $data['phone'], $data['license_no'] === '' ? null : $data['license_no']]);
            set_flash('success', 'Hospital added successfully.');
        }
        redirect('admin/hospitals.php');
    }
}

$pageTitle  = $isEdit ? 'Edit Hospital' : 'Add Hospital';
$activeMenu = 'hospitals.php';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
  <a href="<?= BASE_URL ?>/admin/hospitals.php" class="text-decoration-none small">
    <i class="bi bi-arrow-left me-1"></i>Back to hospitals
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
              <label for="hospital_name" class="form-label">Hospital Name</label>
              <input type="text" class="form-control" id="hospital_name" name="hospital_name"
                     value="<?= e($data['hospital_name']) ?>" minlength="3" maxlength="150" required>
              <div class="invalid-feedback">Please enter the hospital name (min 3 characters).</div>
            </div>
            <div class="col-md-8">
              <label for="address" class="form-label">Address</label>
              <input type="text" class="form-control" id="address" name="address"
                     value="<?= e($data['address']) ?>" required>
              <div class="invalid-feedback">Address is required.</div>
            </div>
            <div class="col-md-4">
              <label for="location" class="form-label">City / Location</label>
              <input type="text" class="form-control" id="location" name="location"
                     value="<?= e($data['location']) ?>" required>
              <div class="invalid-feedback">Location is required.</div>
            </div>
            <div class="col-md-6">
              <label for="phone" class="form-label">Phone</label>
              <input type="tel" class="form-control" id="phone" name="phone"
                     value="<?= e($data['phone']) ?>" pattern="[0-9+\-\s]{10,15}" required>
              <div class="invalid-feedback">Please enter a valid phone number.</div>
            </div>
            <div class="col-md-6">
              <label for="license_no" class="form-label">License / Registration No. <span class="text-muted">(optional)</span></label>
              <input type="text" class="form-control" id="license_no" name="license_no"
                     value="<?= e($data['license_no']) ?>" maxlength="60">
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Update Hospital' : 'Save Hospital' ?>
            </button>
            <a href="<?= BASE_URL ?>/admin/hospitals.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
