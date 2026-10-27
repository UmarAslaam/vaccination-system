<?php
// parent/profile.php - Naam, phone, shehar, pata badalna aur password change
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT name, email, phone, city, address, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$profileErrors  = [];
$passwordErrors = [];
$form = [
    'name'    => $user['name'],
    'phone'   => $user['phone'],
    'city'    => (string)$user['city'],
    'address' => (string)$user['address'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verify_csrf()) {
        $profileErrors[] = 'Session expired. Please try again.';
    }
    // ---------- Form 1: Profile ----------
    elseif ($action === 'profile') {
        foreach ($form as $key => $value) {
            $form[$key] = trim($_POST[$key] ?? '');
        }

        if (mb_strlen($form['name']) < 3 || mb_strlen($form['name']) > 100) {
            $profileErrors[] = 'Name must be between 3 and 100 characters.';
        }
        if (!preg_match('/^[0-9+\-\s]{10,15}$/', $form['phone'])) {
            $profileErrors[] = 'Please enter a valid phone number.';
        }
        if (mb_strlen($form['city']) < 2 || mb_strlen($form['city']) > 80) {
            $profileErrors[] = 'Please enter your city (2 to 80 characters).';
        }
        if (mb_strlen($form['address']) > 255) {
            $profileErrors[] = 'Address can be at most 255 characters.';
        }

        if (!$profileErrors) {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ?, city = ?, address = ? WHERE id = ?');
            $stmt->execute([$form['name'], $form['phone'], $form['city'], $form['address'] === '' ? null : $form['address'], $userId]);
            $_SESSION['name'] = $form['name'];          // topbar mein naya naam
            set_flash('success', 'Profile updated successfully.');
            redirect('parent/profile.php');
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
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            session_regenerate_id(true);                // password badalte hi session ID bhi naya
            set_flash('success', 'Password changed successfully.');
            redirect('parent/profile.php');
        }
    }
}

$pageTitle  = 'My Profile';
$activeMenu = 'profile.php';
require_once __DIR__ . '/../includes/parent_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">My Profile</h4>
  <p class="text-muted mb-0">Member since <?= format_date($user['created_at']) ?></p>
</div>

<div class="row g-4">

  <!-- Profile -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body p-4">
        <h5 class="mb-3"><i class="bi bi-person-circle me-2 text-primary"></i>Personal details</h5>

        <?php if (!$user['city']): ?>
          <div class="alert alert-info py-2 small">
            <i class="bi bi-geo-alt-fill me-1"></i>Add your city to see hospitals <strong>near you</strong> first when booking.
          </div>
        <?php endif; ?>

        <?php if ($profileErrors): ?>
          <div class="alert alert-danger"><ul class="mb-0 ps-3">
            <?php foreach ($profileErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
          </ul></div>
        <?php endif; ?>

        <form method="post" class="needs-validation" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="profile">

          <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= e($form['name']) ?>" minlength="3" maxlength="100" required>
            <div class="invalid-feedback">Please enter your name (min 3 characters).</div>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email <span class="text-muted">(cannot be changed)</span></label>
            <input type="email" class="form-control" id="email" value="<?= e($user['email']) ?>" disabled>
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($form['phone']) ?>" pattern="[0-9+\-\s]{10,15}" required>
            <div class="invalid-feedback">Please enter a valid phone number.</div>
          </div>
          <div class="row g-3 mb-4">
            <div class="col-md-5">
              <label for="city" class="form-label">City</label>
              <input type="text" class="form-control" id="city" name="city" value="<?= e($form['city']) ?>" minlength="2" maxlength="80" required>
              <div class="invalid-feedback">Please enter your city.</div>
            </div>
            <div class="col-md-7">
              <label for="address" class="form-label">Address <span class="text-muted">(optional)</span></label>
              <input type="text" class="form-control" id="address" name="address" value="<?= e($form['address']) ?>" maxlength="255">
            </div>
          </div>
          <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Password -->
  <div class="col-lg-6">
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

<?php require_once __DIR__ . '/../includes/parent_footer.php'; ?>
