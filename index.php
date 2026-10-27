<?php
// index.php - Landing page (Home)
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

// Asli ginti database se (jhoote numbers nahi). Query mein user ka input nahi, isliye query() theek hai.
$stats = [
    ['Vaccines tracked',       (int)$pdo->query('SELECT COUNT(*) FROM vaccines')->fetchColumn(), 'bi-capsule'],
    ['Partner hospitals',      (int)$pdo->query("SELECT COUNT(*) FROM hospitals h LEFT JOIN users u ON u.id = h.user_id WHERE h.user_id IS NULL OR u.status = 'active'")->fetchColumn(), 'bi-hospital'],
    ['Children registered',    (int)$pdo->query('SELECT COUNT(*) FROM children')->fetchColumn(), 'bi-people'],
    ['Vaccinations completed', (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE approval_status = 'approved' AND vaccination_status = 'vaccinated'")->fetchColumn(), 'bi-shield-check'],
];

// Vaccination schedule (asli data vaccines table se), umar ke hisaab se group: {din => [age, vaccines]}
$groups = [];
foreach ($pdo->query('SELECT vaccine_name, recommended_age_days FROM vaccines ORDER BY recommended_age_days ASC, id ASC')->fetchAll() as $v) {
    $d = (int)$v['recommended_age_days'];
    $groups[$d]['age'] = age_label($d);
    $groups[$d]['vaccines'][] = $v['vaccine_name'];
}
$groups = array_slice($groups, 0, 6, true);    // line saaf rahe, pehle 6 umar-gruh

// [icon, rang ki class, title, text]
$features = [
    ['bi-calendar2-heart', '',         'Smart vaccine schedule',  'Enter your child\'s date of birth and see exactly which vaccines are due, upcoming or already completed.'],
    ['bi-hospital',        'c-blue',   'Easy hospital booking',   'Search nearby hospitals and book a date and time in a few clicks. No queues, no paperwork.'],
    ['bi-bell',            'c-amber',  'Timely reminders',        'Your dashboard highlights appointments in the next 7 days, so a vaccine is never missed.'],
    ['bi-award',           'c-violet', 'Digital certificate',     'Download a printable vaccination certificate with all completed vaccines, anytime you need it.'],
    ['bi-shield-check',    'c-green',  'Confirmed appointments',  'Every appointment request is reviewed and confirmed by the VaxCare team, and hospitals only see confirmed appointments.'],
    ['bi-lock',            'c-rose',   'Safe and private',        'Passwords are encrypted and each parent can only see their own children\'s records.'],
];

$steps = [
    ['Create an account',   'Register as a parent and add your child\'s details.'],
    ['See what is due',     'The system suggests vaccines based on your child\'s age.'],
    ['Book an appointment', 'Pick a vaccine, a hospital, a date and a time.'],
    ['Get vaccinated',      'The hospital records the vaccine and your certificate is ready.'],
];

$faqs = [
    ['Who can use VaxCare?',                'Parents and guardians can register their children, and hospitals can join to manage appointments and record vaccinations.'],
    ['How do I know which vaccine is due?', 'After adding your child, your dashboard shows a schedule built from your child\'s date of birth, with each vaccine marked as due, upcoming, booked or completed.'],
    ['Is my booking confirmed instantly?',  'No. Every request is first reviewed by the VaxCare team. Once approved, it appears as confirmed on your dashboard and the hospital receives the appointment.'],
    ['Can I cancel an appointment?',        'Yes, as long as the request is still pending. After it has been approved, please contact the hospital.'],
    ['Where can I get the certificate?',    'Open Vaccination Report in your account and press Certificate. You can print it or save it as a PDF.'],
    ['Is my data safe?',                    'Yes. Passwords are stored in encrypted form, every form is protected against forged requests, and you can only open records that belong to you.'],
];

$pageTitle       = 'Home';
$metaDescription = 'Track your child\'s vaccinations, see which vaccines are due, book a hospital appointment and download a verifiable vaccination certificate with VaxCare.';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ============ HERO ============ -->
<section class="hero2">
  <span class="blob blob-a"></span><span class="blob blob-b"></span>
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="hero-pill fade-in-up"><i class="bi bi-patch-check-fill"></i>Digital vaccination records for every child</span>
        <h1 class="mt-3 mb-3 fade-in-up">Keep every child's <span class="text-gradient">vaccinations</span> on schedule</h1>
        <p class="lead text-muted mb-4 fade-in-up">Register your child, see which vaccines are due, and book an appointment at a nearby hospital in a few clicks.</p>

        <div class="d-flex flex-wrap gap-2 mb-3 fade-in-up">
          <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>/<?= e(current_role()) ?>/dashboard.php" class="btn btn-primary btn-lg px-4">Go to Dashboard</a>
          <?php else: ?>
            <a href="<?= BASE_URL ?>/register.php?role=parent" class="btn btn-primary btn-lg px-4"><i class="bi bi-people me-2"></i>Register as Parent</a>
            <a href="<?= BASE_URL ?>/register.php?role=hospital" class="btn btn-outline-primary btn-lg px-4"><i class="bi bi-hospital me-2"></i>Register as Hospital</a>
          <?php endif; ?>
        </div>
        <?php if (!is_logged_in()): ?>
          <div class="hero-login-link mb-4 fade-in-up">Already registered? <a href="<?= BASE_URL ?>/login.php" class="fw-semibold">Login</a></div>
        <?php endif; ?>

        <div class="trust-row fade-in-up">
          <span><i class="bi bi-check-circle-fill"></i>Free for parents</span>
          <span><i class="bi bi-check-circle-fill"></i>Works on mobile</span>
          <span><i class="bi bi-check-circle-fill"></i>Printable certificate</span>
        </div>
      </div>

      <!-- Sample preview (sirf design, asli data nahi) -->
      <div class="col-lg-6">
        <div class="hero-art" data-reveal>
          <div class="ring"></div>
          <div class="float-chip float-a"><i class="bi bi-bell-fill text-warning"></i>Appointment tomorrow, 10:00 AM</div>
          <div class="hero-card">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="icon-box"><i class="bi bi-gender-male"></i></div>
              <div>
                <div class="fw-semibold">Sample Child</div>
                <small class="text-muted">3 mo old</small>
              </div>
            </div>
            <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Progress</span><span>3 of 10 done</span></div>
            <div class="progress mb-3" style="height:8px;"><div class="progress-bar bg-success" style="width:30%"></div></div>
            <div class="hero-row"><span>BCG</span><span class="badge rounded-pill text-bg-success">Vaccinated</span></div>
            <div class="hero-row"><span>Pentavalent 1</span><span class="badge rounded-pill text-bg-info">Booked</span></div>
            <div class="hero-row"><span>Pentavalent 2</span><span class="badge rounded-pill text-bg-warning">Due now</span></div>
            <div class="hero-row"><span>Measles 1</span><span class="badge rounded-pill text-bg-secondary">Upcoming</span></div>
          </div>
          <div class="float-chip float-b"><i class="bi bi-award-fill text-primary"></i>Certificate ready</div>
          <div class="float-chip float-c">
            <svg width="34" height="34" viewBox="0 0 36 36" aria-hidden="true">
              <circle cx="18" cy="18" r="15" fill="none" stroke="#e5e7eb" stroke-width="4"/>
              <circle cx="18" cy="18" r="15" fill="none" stroke="#0f766e" stroke-width="4" stroke-linecap="round" stroke-dasharray="28.3 94.2" transform="rotate(-90 18 18)"/>
            </svg>
            30% complete
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Hero ke neeche halki lehar (hard edge nahi) -->
  <div class="hero-wave" aria-hidden="true">
    <svg viewBox="0 0 1440 80" preserveAspectRatio="none"><path d="M0,44 C220,84 460,2 720,34 C980,66 1210,78 1440,22 L1440,80 L0,80 Z" fill="#f4f8fa"/></svg>
  </div>
</section>

<!-- ============ STATS ============ -->
<section class="stats-strip">
  <div class="container">
    <div class="row g-3">
      <?php foreach ($stats as $i => [$label, $value, $icon]): ?>
        <div class="col-6 col-lg-3" data-reveal data-delay="<?= $i * 90 ?>">
          <div class="card">
            <div class="stat-ico"><i class="bi <?= $icon ?>"></i></div>
            <div class="num"><span data-count="<?= $value ?>"><?= $value ?></span></div>
            <div class="lbl"><?= e($label) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FEATURES ============ -->
<section class="sec" id="features">
  <div class="container">
    <div class="section-title" data-reveal>
      <div class="eyebrow">Features</div>
      <h2>Everything you need to keep vaccinations on track</h2>
      <p class="text-muted mb-0">Simple for parents and clear for hospitals.</p>
    </div>
    <div class="row g-4">
      <?php foreach ($features as $i => [$icon, $color, $title, $text]): ?>
        <div class="col-md-6 col-lg-4" data-reveal data-delay="<?= ($i % 3) * 100 ?>">
          <div class="card card-hover feature-card">
            <div class="icon-box <?= $color ?>"><i class="bi <?= $icon ?>"></i></div>
            <h5><?= e($title) ?></h5>
            <p><?= e($text) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ SPOTLIGHT 1: reminders ============ -->
<section class="sec sec-white spot">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6" data-reveal>
        <div class="spot-eyebrow">Smart reminders</div>
        <h3>Always know what is due next</h3>
        <p class="text-muted">VaxCare turns your child's date of birth into a clear vaccination schedule and reminds you before every appointment.</p>
        <ul class="check-list">
          <li><i class="bi bi-check-circle-fill"></i>Due, overdue and upcoming vaccines at a glance</li>
          <li><i class="bi bi-check-circle-fill"></i>Reminders for appointments in the next 7 days</li>
          <li><i class="bi bi-check-circle-fill"></i>Add vaccines your child already received</li>
        </ul>
        <a href="<?= BASE_URL ?>/register.php?role=parent" class="btn btn-primary px-4">Get started free</a>
      </div>
      <div class="col-lg-6" data-reveal data-delay="120">
        <div class="mock-window">
          <div class="mock-bar"><span></span><span></span><span></span></div>
          <div class="mock-body">
            <div class="mock-alert">
              <i class="bi bi-bell-fill"></i>
              <div><strong>Reminder:</strong> you have 1 appointment in the next 7 days.<br>
                <small class="text-muted">Sample Child: Pentavalent 2, <strong>Tomorrow</strong> (10:00 AM)</small></div>
            </div>
            <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Progress</span><span>6 of 10 done</span></div>
            <div class="progress mb-3" style="height:8px;"><div class="progress-bar bg-success" style="width:60%"></div></div>
            <div class="hero-row"><span>Pentavalent 2<br><small class="text-muted">Appointment tomorrow</small></span><span class="badge rounded-pill text-bg-info">Booked</span></div>
            <div class="hero-row"><span>Pneumococcal 1<br><small class="text-muted">Due since 2 weeks ago</small></span><span class="badge rounded-pill text-bg-warning">Due now</span></div>
            <div class="hero-row"><span>Measles 1<br><small class="text-muted">Due in 3 months</small></span><span class="badge rounded-pill text-bg-secondary">Upcoming</span></div>
          </div>
        </div>
        <div class="sample-note">Sample preview</div>
      </div>
    </div>
  </div>
</section>

<!-- ============ SPOTLIGHT 2: certificate ============ -->
<section class="sec spot">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 order-lg-2" data-reveal>
        <div class="spot-eyebrow">Digital certificate</div>
        <h3>A certificate you can print and verify</h3>
        <p class="text-muted">Download a professional certificate with every completed vaccine. Anyone can confirm that it is genuine using its certificate number.</p>
        <ul class="check-list">
          <li><i class="bi bi-check-circle-fill"></i>Print it or save it as a PDF</li>
          <li><i class="bi bi-check-circle-fill"></i>Unique certificate number that cannot be forged</li>
          <li><i class="bi bi-check-circle-fill"></i>Free public page to verify a certificate</li>
        </ul>
        <a href="<?= BASE_URL ?>/verify.php" class="btn btn-outline-primary px-4"><i class="bi bi-patch-check me-2"></i>Verify a certificate</a>
      </div>
      <div class="col-lg-6 order-lg-1" data-reveal data-delay="120">
        <div class="mock-cert">
          <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="" width="42" height="42">
          <div class="t">Vaccination Certificate</div>
          <div class="text-muted small mt-2">This is to certify that</div>
          <div class="nm">Sample Child</div>
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-3">
              <thead class="table-light"><tr><th>Vaccine</th><th>Date</th><th>Hospital</th></tr></thead>
              <tbody>
                <tr><td>BCG</td><td>12 Jan 2026</td><td>City Hospital</td></tr>
                <tr><td>Pentavalent 1</td><td>03 Mar 2026</td><td>Care Clinic</td></tr>
                <tr><td>Pentavalent 2</td><td>14 Apr 2026</td><td>City Hospital</td></tr>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 small text-muted">
            <span>No. VX-00000-XXXXXXXX</span>
            <span class="stamp"><i class="bi bi-patch-check-fill"></i>Verified</span>
          </div>
        </div>
        <div class="sample-note">Sample preview</div>
      </div>
    </div>
  </div>
</section>

<!-- ============ VACCINATION SCHEDULE (asli data) ============ -->
<?php if ($groups): ?>
<section class="sec sec-white" id="schedule">
  <div class="container">
    <div class="section-title" data-reveal>
      <div class="eyebrow">Vaccination schedule</div>
      <h2>The schedule at a glance</h2>
      <p class="text-muted mb-0">Recommended ages for the vaccines tracked in VaxCare. Your dashboard shows the exact dates for your child.</p>
    </div>
    <div class="timeline">
      <?php foreach ($groups as $i => $g): ?>
        <div class="tl-item" data-reveal data-delay="<?= $i * 80 ?>">
          <span class="tl-dot"></span>
          <div class="tl-card">
            <div class="tl-age"><?= e($g['age']) ?></div>
            <ul class="list-unstyled small mb-0">
              <?php foreach ($g['vaccines'] as $name): ?><li><?= e($name) ?></li><?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ HOW IT WORKS ============ -->
<section class="sec" id="how">
  <div class="container">
    <div class="section-title" data-reveal>
      <div class="eyebrow">How it works</div>
      <h2>Four simple steps</h2>
    </div>
    <div class="row g-4">
      <?php foreach ($steps as $i => [$title, $text]): ?>
        <div class="col-sm-6 col-lg-3" data-reveal data-delay="<?= $i * 120 ?>">
          <div class="step">
            <div class="step-num"><?= $i + 1 ?></div>
            <h5><?= e($title) ?></h5>
            <p><?= e($text) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ WHO IS IT FOR ============ -->
<section class="sec sec-white" id="roles">
  <div class="container">
    <div class="section-title" data-reveal>
      <div class="eyebrow">Who is it for</div>
      <h2>Made for parents and hospitals</h2>
    </div>
    <div class="row g-4 justify-content-center">
      <div class="col-lg-5 col-md-6" data-reveal>
        <div class="card card-hover role-card">
          <div class="icon-box"><i class="bi bi-people"></i></div>
          <h5 class="mt-3">Parents</h5>
          <ul>
            <li><i class="bi bi-check2"></i>Add and manage children</li>
            <li><i class="bi bi-check2"></i>See due and upcoming vaccines</li>
            <li><i class="bi bi-check2"></i>Book and track appointments</li>
            <li><i class="bi bi-check2"></i>Print vaccination certificate</li>
          </ul>
          <a href="<?= BASE_URL ?>/register.php?role=parent" class="btn btn-primary w-100">Register as Parent</a>
        </div>
      </div>
      <div class="col-lg-5 col-md-6" data-reveal data-delay="100">
        <div class="card card-hover role-card alt">
          <div class="icon-box c-blue"><i class="bi bi-hospital"></i></div>
          <h5 class="mt-3">Hospitals</h5>
          <ul>
            <li><i class="bi bi-check2"></i>See confirmed appointments</li>
            <li><i class="bi bi-check2"></i>Today and upcoming schedule</li>
            <li><i class="bi bi-check2"></i>Record vaccinated or not</li>
            <li><i class="bi bi-check2"></i>Manage hospital profile</li>
          </ul>
          <a href="<?= BASE_URL ?>/register.php?role=hospital" class="btn btn-outline-primary w-100">Register as Hospital</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="sec" id="faq">
  <div class="container">
    <div class="section-title" data-reveal>
      <div class="eyebrow">FAQ</div>
      <h2>Frequently asked questions</h2>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8" data-reveal>
        <div class="accordion" id="faqList">
          <?php foreach ($faqs as $i => [$q, $a]): ?>
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button"
                        data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>"
                        aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="faq<?= $i ?>">
                  <?= e($q) ?>
                </button>
              </h2>
              <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqList">
                <div class="accordion-body text-muted"><?= e($a) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<section class="sec pt-0">
  <div class="container">
    <div class="cta-band" data-reveal>
      <h2 class="mb-2">Protect your child's health today</h2>
      <p class="mb-4 opacity-75">Create a free account and set up your child's vaccination schedule in minutes.</p>
      <?php if (is_logged_in()): ?>
        <a href="<?= BASE_URL ?>/<?= e(current_role()) ?>/dashboard.php" class="btn btn-light btn-lg px-4">Go to Dashboard</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/register.php?role=parent" class="btn btn-light btn-lg px-4">Create Free Account</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ CONTACT ============ -->
<section class="sec sec-white" id="contact">
  <div class="container">
    <div class="section-title" data-reveal>
      <div class="eyebrow">Contact</div>
      <h2>We are here to help</h2>
    </div>
    <div class="row g-4 justify-content-center">
      <?php foreach ([['bi-envelope', 'Email', 'support@vaxcare.com', ''],
                      ['bi-telephone', 'Phone', '+92 300 0000000', 'c-blue'],
                      ['bi-geo-alt', 'Address', 'Karachi, Pakistan', 'c-amber']] as $i => [$icon, $title, $text, $color]): ?>
        <div class="col-md-4" data-reveal data-delay="<?= $i * 100 ?>">
          <div class="card contact-card">
            <div class="icon-box mx-auto mb-3 <?= $color ?>"><i class="bi <?= $icon ?>"></i></div>
            <h6><?= $title ?></h6>
            <p class="text-muted mb-0"><?= e($text) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
