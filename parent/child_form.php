<?php
// parent/child_form.php - Bacha add (Create) aur edit (Update), ek hi file
//   child_form.php        -> naya bacha
//   child_form.php?id=2   -> bacha 2 ko edit (sirf agar wo is parent ka ho)
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';

$parentId = (int)$_SESSION['user_id'];
$id       = (int)($_GET['id'] ?? 0);
$isEdit   = $id > 0;
$errors   = [];

$bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$data = ['name' => '', 'dob' => '', 'gender' => 'male', 'blood_group' => ''];

if ($isEdit) {
    // "AND parent_id = ?" ka matlab: kisi aur parent ka bacha URL mein id badal kar nahi khul sakta
    $stmt = $pdo->prepare('SELECT * FROM children WHERE id = ? AND parent_id = ?');
    $stmt->execute([$id, $parentId]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('danger', 'Child not found.');
        redirect('parent/children.php');
    }
    $data = [
        'name'        => $row['name'],
        'dob'         => $row['dob'],
        'gender'      => $row['gender'],
        'blood_group' => (string)$row['blood_group'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $value) {
        $data[$key] = trim($_POST[$key] ?? '');
    }

    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try again.';
    }
    if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 100) {
        $errors[] = 'Name must be between 2 and 100 characters.';
    }
    if (!is_valid_date($data['dob'])) {
        $errors[] = 'Please enter a valid date of birth.';
    } elseif ($data['dob'] > date('Y-m-d')) {
        $errors[] = 'Date of birth cannot be in the future.';
    } elseif ($data['dob'] < date('Y-m-d', strtotime('-18 years'))) {
        $errors[] = 'This system is for children under 18 years.';
    }
    if (!in_array($data['gender'], ['male', 'female'], true)) {
        $errors[] = 'Please select a gender.';
    }
    if ($data['blood_group'] !== '' && !in_array($data['blood_group'], $bloodGroups, true)) {
        $errors[] = 'Please select a valid blood group.';
    }

    // Wohi naam + wohi DOB dobara add na ho (edit mein khud ko chhor kar)
    if (!$errors) {
        $dup = $pdo->prepare('SELECT id FROM children WHERE parent_id = ? AND name = ? AND dob = ? AND id <> ?');
        $dup->execute([$parentId, $data['name'], $data['dob'], $id]);
        if ($dup->fetch()) {
            $errors[] = 'You have already added a child with this name and date of birth.';
        }
    }

    if (!$errors) {
        $blood = $data['blood_group'] === '' ? null : $data['blood_group'];

        if ($isEdit) {
            $stmt = $pdo->prepare(
                'UPDATE children SET name = ?, dob = ?, gender = ?, blood_group = ? WHERE id = ? AND parent_id = ?'
            );
            $stmt->execute([$data['name'], $data['dob'], $data['gender'], $blood, $id, $parentId]);
            set_flash('success', 'Child details updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO children (parent_id, name, dob, gender, blood_group) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$parentId, $data['name'], $data['dob'], $data['gender'], $blood]);
            set_flash('success', $data['name'] . ' was added successfully.');
        }
        redirect('parent/children.php');
    }
}

$pageTitle  = $isEdit ? 'Edit Child' : 'Add Child';
$activeMenu = 'children.php';
require_once __DIR__ . '/../includes/parent_header.php';
?>

<div class="mb-4">
  <a href="<?= BASE_URL ?>/parent/children.php" class="text-decoration-none small">
    <i class="bi bi-arrow-left me-1"></i>Back to my children
  </a>
  <h4 class="mt-2 mb-0"><?= e($pageTitle) ?></h4>
</div>

<div class="row">
  <div class="col-lg-7">
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
              <label for="name" class="form-label">Child's Full Name</label>
              <input type="text" class="form-control" id="name" name="name"
                     value="<?= e($data['name']) ?>" minlength="2" maxlength="100" required>
              <div class="invalid-feedback">Please enter the child's name.</div>
            </div>

            <div class="col-md-6">
              <label for="dob" class="form-label">Date of Birth</label>
              <input type="date" class="form-control" id="dob" name="dob" value="<?= e($data['dob']) ?>"
                     min="<?= date('Y-m-d', strtotime('-18 years')) ?>" max="<?= date('Y-m-d') ?>" required>
              <div class="invalid-feedback">Please choose a valid date of birth.</div>
            </div>

            <div class="col-md-6">
              <label for="blood_group" class="form-label">Blood Group <span class="text-muted">(optional)</span></label>
              <select class="form-select" id="blood_group" name="blood_group">
                <option value="">Not sure</option>
                <?php foreach ($bloodGroups as $bg): ?>
                  <option value="<?= $bg ?>" <?= $data['blood_group'] === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label d-block">Gender</label>
              <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="gender" id="g_male" value="male"
                       <?= $data['gender'] === 'male' ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="g_male"><i class="bi bi-gender-male me-1"></i>Male</label>

                <input type="radio" class="btn-check" name="gender" id="g_female" value="female"
                       <?= $data['gender'] === 'female' ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="g_female"><i class="bi bi-gender-female me-1"></i>Female</label>
              </div>
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Update Child' : 'Save Child' ?>
            </button>
            <a href="<?= BASE_URL ?>/parent/children.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/parent_footer.php'; ?>
