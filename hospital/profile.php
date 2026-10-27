<?php
// hospital/profile.php - Hospital ki tafseel badalna aur password change
require_once __DIR__ . '/../includes/auth.php';
require_role('hospital');
require_once __DIR__ . '/../config/db.php';

$hospital = require_hospital($pdo);
$userId   = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$profileErrors  = [];
$passwordErrors = [];
$form = [
    'hospital_name' => $hospital['hospital_name'],
    'address'       => $hospital['address'],
    'location'      => $hospital['location'],
    'phone'         => $hospital['phone'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verify_csrf()) {
        $profileErrors[] = 'Session expired. Please try again.';
    }
    // ---------- Form 1: Hospital details ----------
    elseif ($action === 'profile') {
        foreach ($form as $key => $value) {
            $form[$key] = trim($_POST[$key] ?? '');
        }
        if (mb_strlen($form['hospital_name']) < 3 || mb_strlen($form['hospital_name']) > 150) {
            $profileErrors[] = 'Hospital name must be between 3 and 150 characters.';
        }
        if ($form['address'] === '' || $form['location'] === '') {
            $profileErrors[] = 'Address and city / location are required.';
        }
        if (!preg_match('/^[0-9+\-\s]{10,15}$/', $form['phone'])) {
            $profileErrors[] = 'Please enter a valid phone number.';
        }

        if (!$profileErrors) {
            try {
                $pdo->beginTransaction();   // dono tables ek saath badlen, ya koi nahi
                // Account ka naam = hospital ka naam (alag contact person nahi)
                $pdo->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?')
                    ->execute([$form['hospital_name'], $form['phone'], $userId]);
                $pdo->prepare('UPDATE hospitals SET hospital_name = ?, address = ?, location = ?, phone = ? WHERE id = ?')
                    ->execute([$form['hospital_name'], $form['address'], $form['location'], $form['phone'], $hospital['id']]);
                $pdo->commit();

                $_SESSION['name'] = $form['hospital_name'];
                set_flash('success', 'Hospital profile updated successfully.');
                redirect('hospital/profile.php');
            } catch (PDOException $ex) {
                $pdo->rollBack();
                $profileErrors[] = 'Something went wrong. Please try again.';
            }
        }
    }
    // ---------- Form 2: Password ----------
    elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $passwordErrors[] = 'Your current password is incorrect.';
        }
        if (strlen($new) < 6) {
            $passwordErrors[] = 'New password must be at least 6 characters.';
        }
        if ($new !== $confirm) {
            $passwordErrors[] = 'New passwords do not match.';
        }
        if (!$passwordErrors && password_verify($new, $hash)) {
            $passwordErrors[] = 'New password must be different from the current one.';
        }

        if (!$passwordErrors) {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            session_regenerate_id(true);
            set_flash('success', 'Password changed successfully.');
            redirect('hospital/profile.php');
        }
    }
}

$pageTitle  = 'Hospital Profile';
$activeMenu = 'profile.php';
require_once __DIR__ . '/../includes/hospital_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">Hospital Profile</h4>
  <p class="text-muted mb-0">Login email: <?= e($user['email']) ?></p>
</div>

<div class="row g-4">

  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-body p-4">
        <h5 class="mb-3"><i class="bi bi-building me-2 text-primary"></i>Hospital details</h5>

        <?php if ($profileErrors): ?>
          <div class="alert alert-danger"><ul class="mb-0 ps-3">
            <?php foreach ($profileErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
          </ul></div>
        <?php endif; ?>

        <form method="post" class="needs-validation" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="profile">
          <div class="row g-3">
            <div class="col-12">
              <label for="hospital_name" class="form-label">Hospital Name</label>
              <input type="text" class="form-control" id="hospital_name" name="hospital_name" value="<?= e($form['hospital_name']) ?>" minlength="3" maxlength="150" required>
              <div class="invalid-feedback">Hospital name is required.</div>
            </div>
            <div class="col-md-8">
              <label for="address" class="form-label">Address</label>
              <input type="text" class="form-control" id="address" name="address" value="<?= e($form['address']) ?>" required>
              <div class="invalid-feedback">Address is required.</div>
            </div>
            <div class="col-md-4">
              <label for="location" class="form-label">City / Location</label>
              <input type="text" class="form-control" id="location" name="location" value="<?= e($form['location']) ?>" required>
              <div class="invalid-feedback">Location is required.</div>
            </div>
            <div class="col-md-6">
              <label for="license_no" class="form-label">License / Registration No. <span class="text-muted">(set at registration)</span></label>
              <input type="text" class="form-control" id="license_no" value="<?= e($hospital['license_no'] ?: '-') ?>" disabled>
            </div>
            <div class="col-md-6">
              <label for="phone" class="form-label">Phone</label>
              <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($form['phone']) ?>" pattern="[0-9+\-\s]{10,15}" required>
              <div class="invalid-feedback">Please enter a valid phone number.</div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary px-4 mt-4"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-body p-4">
        <h5 class="mb-3"><i class="bi bi-shield-lock me-2 text-primary"></i>Change password</h5>

        <?php if ($passwordErrors): ?>
          <div class="alert alert-danger"><ul class="mb-0 ps-3">
            <?php foreach ($passwordErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
          </ul></div>
        <?php endif; ?>

        <form method="post" class="needs-validation" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="password">
          <div class="mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="current_password" name="current_password" required>
              <button class="btn btn-outline-secondary" type="button" data-toggle-password="#current_password" aria-label="Show password"><i class="bi bi-eye"></i></button>
              <div class="invalid-feedback">Please enter your current password.</div>
            </div>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="password" name="password" minlength="6" required>
              <button class="btn btn-outline-secondary" type="button" data-toggle-password="#password" aria-label="Show password"><i class="bi bi-eye"></i></button>
              <div class="invalid-feedback">Minimum 6 characters.</div>
            </div>
          </div>
          <div class="mb-4">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            <div class="invalid-feedback">Passwords must match.</div>
          </div>
          <button type="submit" class="btn btn-primary px-4"><i class="bi bi-key me-1"></i>Update Password</button>
        </form>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/hospital_footer.php'; ?>
