<?php
// check-files.php - Day 1 se 8 tak: server kaunsa folder padh raha hai aur kaun si file missing hai
// Sirf apne computer par chalane ke liye. Project submit karne se pehle ye file delete kar do.

$base = __DIR__;

$expected = [
    'config/config.php', 'config/db.php',
    'includes/header.php', 'includes/footer.php', 'includes/functions.php', 'includes/auth.php',
    'includes/admin_header.php', 'includes/admin_footer.php',
    'includes/parent_header.php', 'includes/parent_footer.php', 'includes/schedule.php', 'includes/hospital_header.php', 'includes/hospital_footer.php',
    'assets/css/style.css', 'assets/js/script.js', 'assets/img/logo.svg',
    'index.php', 'login.php', 'register.php', 'logout.php', 'verify.php',
    'admin/suggestions.php', 'admin/suggestion_action.php', 'admin/booking_status.php', 'parent/suggest_hospital.php', 'admin/users.php', 'admin/user_action.php', 'admin/past_records.php', 'admin/past_action.php', 'parent/past_vaccinations.php', 'parent/past_delete.php', 'admin/dashboard.php', 'admin/hospitals.php', 'admin/hospital_form.php', 'admin/hospital_delete.php',
    'admin/vaccines.php', 'admin/vaccine_form.php', 'admin/vaccine_toggle.php', 'admin/vaccine_delete.php',
    'admin/children.php', 'admin/child_view.php', 'admin/requests.php', 'admin/request_action.php',
    'admin/bookings.php', 'admin/reports.php',
    'hospital/dashboard.php', 'hospital/appointments.php', 'hospital/update_status.php', 'hospital/profile.php', 'parent/dashboard.php', 'parent/book_hospital.php', 'parent/my_bookings.php', 'parent/booking_cancel.php', 'parent/reports.php', 'parent/certificate.php', 'parent/profile.php', 'parent/children.php', 'parent/child_form.php', 'parent/child_delete.php',
];

$missing = [];
foreach ($expected as $file) {
    if (!is_file($base . '/' . $file)) {
        $missing[] = $file;
    }
}

// Parent dashboard purani hai ya nayi?
$dash = $base . '/parent/dashboard.php';
$dashIsNew = is_file($dash) && strpos(file_get_contents($dash), 'parent_header.php') !== false;

// Offline assets (assets/vendor): optional, lekin demo ke din internet na chale to kaam aate hain
$vendorExpected = [
    'bootstrap/bootstrap.min.css', 'bootstrap/bootstrap.bundle.min.js',
    'bootstrap-icons/bootstrap-icons.css', 'bootstrap-icons/fonts/bootstrap-icons.woff2', 'bootstrap-icons/fonts/bootstrap-icons.woff',
    'chartjs/chart.umd.min.js',
    'poppins/poppins-latin-400-normal.woff2', 'poppins/poppins-latin-500-normal.woff2',
    'poppins/poppins-latin-600-normal.woff2', 'poppins/poppins-latin-700-normal.woff2',
];
$vendorMissing = [];
foreach ($vendorExpected as $v) {
    if (!is_file($base . '/assets/vendor/' . $v)) { $vendorMissing[] = $v; }
}

// Zip ko ghalat extract karne par folder ke andar folder ban jata hai
$nested = is_dir($base . '/vaccination-system');

function h($t) { return htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>File Check</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 760px; margin: 2rem auto; padding: 0 1rem; color: #1f2937; }
    .ok  { background: #dcfce7; border-left: 5px solid #16a34a; padding: .8rem 1rem; margin: .6rem 0; border-radius: 6px; }
    .bad { background: #fee2e2; border-left: 5px solid #dc2626; padding: .8rem 1rem; margin: .6rem 0; border-radius: 6px; }
    code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; }
    li { margin: .2rem 0; }
  </style>
</head>
<body>
  <h2>Project File Check</h2>

  <p>Server is folder se files padh raha hai:<br><code><?= h($base) ?></code></p>

  <?php if ($nested): ?>
    <div class="bad"><strong>Folder ke andar folder mila:</strong> <code>vaccination-system/vaccination-system</code>.
      Zip ghalat jagah extract hua hai. Andar wale folder ki <em>files</em> bahar wale folder mein copy karo (Replace dabao).</div>
  <?php endif; ?>

  <?php if ($missing): ?>
    <div class="bad"><strong><?= count($missing) ?> file(s) missing hain:</strong>
      <ul><?php foreach ($missing as $m): ?><li><code><?= h($m) ?></code></li><?php endforeach; ?></ul>
    </div>
  <?php else: ?>
    <div class="ok"><strong>Sab zaroori files maujood hain (<?= count($expected) ?>).</strong></div>
  <?php endif; ?>

  <?php if ($dashIsNew): ?>
    <div class="ok"><code>parent/dashboard.php</code> naya hai (Day 8).</div>
  <?php else: ?>
    <div class="bad"><code>parent/dashboard.php</code> <strong>purana hai</strong>. Zip wali nayi file se Replace karo.</div>
  <?php endif; ?>

  <h3>Offline assets</h3>
  <?php if (!$vendorMissing): ?>
    <div class="ok"><strong>Sab <?= count($vendorExpected) ?> offline files maujood hain.</strong> Website internet ke baghair bhi chalegi.</div>
  <?php else: ?>
    <div class="bad"><strong><?= count($vendorMissing) ?> of <?= count($vendorExpected) ?> offline files missing hain.</strong>
      Abhi website CDN (internet) se chal rahi hai. <code>download-vendor.ps1</code> chalao.
      <ul><?php foreach ($vendorMissing as $m): ?><li><code><?= h($m) ?></code></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>
</body>
</html>
