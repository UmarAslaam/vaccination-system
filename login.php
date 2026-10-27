<?php
// login.php - teeno roles (admin, parent, hospital) ka ek hi login
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

// Pehle se login hai to apne dashboard par bhej do
if (is_logged_in()) {
    redirect(current_role() . '/dashboard.php');
}

$maxAttempts = 5;    // itni galat koshishon ke baad
$lockMinutes = 5;    // itne minute ke liye login band

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!verify_csrf()) {
        $error = 'Session expired. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        // Prepared statement: email seedha query mein nahi jata (SQL injection se bachao)
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $lockedUntil = ($user && $user['locked_until']) ? strtotime($user['locked_until']) : 0;

        if ($lockedUntil > time()) {
            // Account lock hai: password check hi nahi karte
            $mins  = max(1, (int)ceil(($lockedUntil - time()) / 60));
            $error = "Too many failed attempts. Please try again in $mins minute(s).";

        } elseif ($user && password_verify($password, $user['password'])) {
            // Password sahi. Ab account ki halat dekho
            if ($user['status'] === 'pending') {
                $error = 'Your account is waiting for approval. You can login once it is approved.';
            } elseif ($user['status'] === 'disabled') {
                $error = 'Your account has been disabled. Please contact support.';
            } else {
                // Kamyab login: galat koshishon ki ginti saaf
                $pdo->prepare('UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?')->execute([$user['id']]);

                session_regenerate_id(true);   // session fixation attack se bachao
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];

                // Hospital ho to uska hospital_id bhi yaad rakho (baad mein kaam aayega)
                if ($user['role'] === 'hospital') {
                    $h = $pdo->prepare('SELECT id FROM hospitals WHERE user_id = ?');
                    $h->execute([$user['id']]);
                    $_SESSION['hospital_id'] = $h->fetchColumn() ?: null;
                }

                set_flash('success', 'Welcome back, ' . $user['name'] . '!');
                redirect($user['role'] . '/dashboard.php');
            }

        } else {
            // Password galat (ya email hi galat)
            $error = 'Invalid email or password.';
            if ($user) {
                $failed = (int)$user['failed_logins'] + 1;
                if ($failed >= $maxAttempts) {
                    // 5 galat koshishen: kuch minute ke liye lock (waqt PHP se, taake time zone ka farq na ho)
                    $until = date('Y-m-d H:i:s', time() + $lockMinutes * 60);
                    $pdo->prepare('UPDATE users SET failed_logins = 0, locked_until = ? WHERE id = ?')->execute([$until, $user['id']]);
                    $error = "Too many failed attempts. This account is locked for $lockMinutes minutes.";
                } else {
                    $pdo->prepare('UPDATE users SET failed_logins = ? WHERE id = ?')->execute([$failed, $user['id']]);
                }
            }
        }
    }
}

// Login ka error ab toast mein (header usay flash ki tarah dikhata hai)
if ($error) {
    set_flash('danger', $error);
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-xl-9">
        <div class="card auth-card auth-split fade-in-up">
          <div class="row g-0">

            <!-- Left: brand panel (sirf bade screen par) -->
            <div class="col-lg-5 auth-side d-none d-lg-flex">
              <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="" width="52" height="52">
              <h2>Welcome back to <?= APP_NAME ?></h2>
              <p>Track every vaccination, book appointments and download certificates in one place.</p>
              <ul>
                <li><i class="bi bi-check-circle-fill"></i>See what is due for your child</li>
                <li><i class="bi bi-check-circle-fill"></i>Book hospital appointments online</li>
                <li><i class="bi bi-check-circle-fill"></i>Print a verifiable certificate</li>
              </ul>
            </div>

            <!-- Right: form -->
            <div class="col-lg-7 p-4 p-md-5">
          <div class="text-center mb-4">
            <div class="icon-box mb-3"><i class="bi bi-person-lock"></i></div>
            <h3 class="mb-1">Welcome back</h3>
            <p class="text-muted mb-0">Login to your account</p>
          </div>

          <form method="post" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email"
                     value="<?= e($email) ?>" placeholder="you@example.com" required>
              <div class="invalid-feedback">Please enter a valid email.</div>
            </div>

            <div class="mb-4">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="Your password" required>
                <button class="btn btn-outline-secondary" type="button" data-toggle-password="#password" aria-label="Show password">
                  <i class="bi bi-eye"></i>
                </button>
                <div class="invalid-feedback">Please enter your password.</div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
          </form>

          <p class="text-center text-muted mt-4 mb-0">
            Don't have an account? <a href="<?= BASE_URL ?>/register.php">Register here</a>
          </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
