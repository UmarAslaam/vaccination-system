<?php
// verify.php - Certificate number se check karna ke certificate asli hai ya nahi (koi bhi, bina login ke)
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

$no       = mb_substr(trim($_GET['no'] ?? ''), 0, 30);
$searched = $no !== '';
$child    = null;
$records  = [];

if ($searched) {
    $childId = certificate_child_id($no);          // number sahi ho to id, warna 0
    if ($childId > 0) {
        $stmt = $pdo->prepare('SELECT name, dob FROM children WHERE id = ?');
        $stmt->execute([$childId]);
        $child = $stmt->fetch();

        if ($child) {
            // Wohi vaccines jo certificate par hoti hain: hospital mein lagi hui + verify hui purani
            $stmt = $pdo->prepare(
                "SELECT b.appointment_date AS given_on, v.vaccine_name, h.hospital_name AS place
                 FROM bookings b
                 JOIN vaccines  v ON v.id = b.vaccine_id
                 JOIN hospitals h ON h.id = b.hospital_id
                 WHERE b.child_id = ? AND b.approval_status = 'approved' AND b.vaccination_status = 'vaccinated'"
            );
            $stmt->execute([$childId]);
            $records = $stmt->fetchAll();

            $stmt = $pdo->prepare(
                "SELECT p.given_date AS given_on, v.vaccine_name, p.place
                 FROM past_vaccinations p
                 JOIN vaccines v ON v.id = p.vaccine_id
                 WHERE p.child_id = ? AND p.status = 'verified'"
            );
            $stmt->execute([$childId]);
            $records = array_merge($records, $stmt->fetchAll());
            usort($records, fn($a, $b) => strcmp($a['given_on'], $b['given_on']));
        }
    }
}
$valid = ($child && $records);

$pageTitle = 'Verify Certificate';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7">

        <div class="card auth-card p-4 p-md-5 fade-in-up">
          <div class="text-center mb-4">
            <div class="icon-box mb-3"><i class="bi bi-patch-check"></i></div>
            <h3 class="mb-1">Verify a Certificate</h3>
            <p class="text-muted mb-0">Enter the certificate number printed on the vaccination certificate.</p>
          </div>

          <form method="get" class="d-flex gap-2 mb-2">
            <input type="text" class="form-control" name="no" value="<?= e($no) ?>" placeholder="VX-00002-ABCD1234" maxlength="30" required>
            <button type="submit" class="btn btn-primary px-4">Verify</button>
          </form>

          <?php if ($searched && !$valid): ?>
            <div class="alert alert-danger mt-4 mb-0">
              <i class="bi bi-x-octagon-fill me-2"></i><strong>Not valid.</strong>
              No certificate was found for this number. Please check it and try again.
            </div>
          <?php endif; ?>

          <?php if ($valid): ?>
            <div class="alert alert-success mt-4">
              <i class="bi bi-check-circle-fill me-2"></i><strong>This certificate is valid.</strong>
            </div>

            <ul class="list-unstyled small mb-3">
              <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Child</span><span class="fw-semibold"><?= e(mask_name($child['name'])) ?></span></li>
              <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Year of birth</span><span><?= e(date('Y', strtotime($child['dob']))) ?></span></li>
              <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Certificate no.</span><span><?= e(strtoupper($no)) ?></span></li>
              <li class="d-flex justify-content-between py-2"><span class="text-muted">Vaccines recorded</span><span><?= count($records) ?></span></li>
            </ul>

            <div class="table-responsive">
              <table class="table table-sm table-stack mb-0">
                <thead><tr><th>Vaccine</th><th>Date</th><th>Given at</th></tr></thead>
                <tbody>
                  <?php foreach ($records as $r): ?>
                    <tr>
                      <td data-label="Vaccine" class="fw-semibold"><?= e($r['vaccine_name']) ?></td>
                      <td data-label="Date"><?= format_date($r['given_on']) ?></td>
                      <td data-label="Given at"><?= e($r['place']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <p class="text-muted small mt-3 mb-0">For privacy, only a shortened name and the year of birth are shown.</p>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
