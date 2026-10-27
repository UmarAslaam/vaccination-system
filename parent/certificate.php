<?php
// parent/certificate.php?child=3 - Vaccination certificate (print ya "Save as PDF")
// Ye alag, saaf page hai (sidebar ke baghair) taake print khoobsurat nikle.
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';

$parentId = (int)$_SESSION['user_id'];
$childId  = (int)($_GET['child'] ?? 0);

// Ownership: bacha isi parent ka ho
$stmt = $pdo->prepare('SELECT * FROM children WHERE id = ? AND parent_id = ?');
$stmt->execute([$childId, $parentId]);
$child = $stmt->fetch();
if (!$child) {
    set_flash('danger', 'Child not found.');
    redirect('parent/reports.php');
}

// Sirf wo vaccinations jo approved bhi hain aur lag bhi chuki hain
$stmt = $pdo->prepare(
    "SELECT b.appointment_date, v.vaccine_name, h.hospital_name
     FROM bookings b
     JOIN vaccines  v ON v.id = b.vaccine_id
     JOIN hospitals h ON h.id = b.hospital_id
     WHERE b.child_id = ? AND b.approval_status = 'approved' AND b.vaccination_status = 'vaccinated'
     ORDER BY b.appointment_date ASC, b.id ASC"
);
$stmt->execute([$child['id']]);
$records = $stmt->fetchAll();

// Admin ne jo purani vaccinations VERIFY ki hain wo bhi certificate mein
$stmt = $pdo->prepare(
    "SELECT p.given_date AS appointment_date, v.vaccine_name, p.place AS hospital_name
     FROM past_vaccinations p
     JOIN vaccines v ON v.id = p.vaccine_id
     WHERE p.child_id = ? AND p.status = 'verified'"
);
$stmt->execute([$child['id']]);
$records = array_merge($records, $stmt->fetchAll());
usort($records, fn($a, $b) => strcmp($a['appointment_date'], $b['appointment_date']));

if (!$records) {
    set_flash('warning', 'No completed vaccinations yet, so a certificate cannot be issued.');
    redirect('parent/reports.php?child=' . (int)$child['id']);
}

$certNo    = certificate_number($child['id']);   // secret se bana hua, jaali nahi ban sakta
$verifyUrl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
           . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL . '/verify.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Vaccination Certificate - <?= e($child['name']) ?></title>
  <?php head_assets(); ?>
  <style>
    body { display: block; padding: 1.5rem 0; }
    .cert-wrap { max-width: 900px; margin: 0 auto; }
    .cert {
      background: #fff; padding: 2.2rem 2.5rem; text-align: center;
      border: 6px double var(--brand); border-radius: 6px; position: relative;
    }
    .cert h1 { font-size: 2rem; letter-spacing: .08em; text-transform: uppercase; color: var(--brand); margin: .6rem 0 0; }
    .cert .sub { color: var(--muted); letter-spacing: .15em; font-size: .8rem; text-transform: uppercase; }
    .cert .child-name { font-size: 1.9rem; font-weight: 700; color: #0f172a; border-bottom: 2px solid var(--brand); display: inline-block; padding: 0 1.5rem; margin: .4rem 0 .8rem; }
    .cert table { text-align: left; font-size: .9rem; }
    .cert .sign { border-top: 1px solid #6b7280; padding-top: .3rem; min-width: 180px; font-size: .8rem; color: var(--muted); }
    @page { size: A4 landscape; margin: 10mm; }
    @media print {
      body { background: #fff !important; padding: 0; }
      .cert { border-width: 5px; padding: 1.2rem 1.6rem; }
      .cert h1 { font-size: 1.6rem; }
      .cert .child-name { font-size: 1.5rem; }
      .cert table { font-size: .8rem; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>
<div class="cert-wrap">

  <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="<?= BASE_URL ?>/parent/reports.php?child=<?= (int)$child['id'] ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <button type="button" class="btn btn-primary" data-print><i class="bi bi-printer me-1"></i>Print / Save as PDF</button>
  </div>

  <div class="cert">
    <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="Logo" width="56" height="56">
    <div class="sub mt-2"><?= APP_NAME ?> &bull; <?= APP_TAGLINE ?></div>
    <h1>Vaccination Certificate</h1>

    <p class="mt-3 mb-1 text-muted">This is to certify that</p>
    <div class="child-name"><?= e($child['name']) ?></div>
    <p class="mb-3">
      Date of birth: <strong><?= format_date($child['dob']) ?></strong> &nbsp;&bull;&nbsp;
      Gender: <strong><?= e(ucfirst($child['gender'])) ?></strong> &nbsp;&bull;&nbsp;
      Parent: <strong><?= e($_SESSION['name']) ?></strong>
    </p>
    <p class="mb-3">has received the following vaccinations:</p>

    <div class="table-responsive">
    <table class="table table-sm table-bordered mb-4">
      <thead class="table-light">
        <tr><th style="width:50px;">#</th><th>Vaccine</th><th>Date</th><th>Hospital</th></tr>
      </thead>
      <tbody>
        <?php foreach ($records as $i => $r): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td class="fw-semibold"><?= e($r['vaccine_name']) ?></td>
            <td><?= format_date($r['appointment_date']) ?></td>
            <td><?= e($r['hospital_name']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
      <div class="text-start small">
        Certificate No: <strong><?= e($certNo) ?></strong><br>
        Issued on: <strong><?= date('d M Y') ?></strong><br>
        Verify at: <strong><?= e($verifyUrl) ?></strong>
      </div>
      <div class="sign text-center">Authorized by <?= APP_NAME ?></div>
    </div>
    <p class="text-muted small mt-3 mb-0">This certificate is generated from the vaccination records maintained in the <?= APP_NAME ?> system.</p>
  </div>

</div>
<?php foot_scripts(); ?>
</body>
</html>
