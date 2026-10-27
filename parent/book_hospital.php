<?php
// parent/book_hospital.php - Appointment book karna (bacha -> vaccine -> hospital -> tareekh/waqt)
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/schedule.php';

$parentId   = (int)$_SESSION['user_id'];
$maxPerSlot = 5;                                   // ek hospital mein ek waqt par zyada se zyada 5 bookings
$minDate    = date('Y-m-d', strtotime('+1 day'));  // kal se booking
$maxDate    = date('Y-m-d', strtotime('+90 days'));

// Waqt ke slots: 09:00 se 16:30 tak, har 30 minute
$slots = [];
for ($m = 9 * 60; $m <= 16 * 60 + 30; $m += 30) {
    $value         = sprintf('%02d:%02d:00', intdiv($m, 60), $m % 60);
    $slots[$value] = date('h:i A', strtotime($value));
}

// ---------- Parent ke apne bache ----------
$stmt = $pdo->prepare('SELECT * FROM children WHERE parent_id = ? ORDER BY dob DESC, id DESC');
$stmt->execute([$parentId]);
$children = $stmt->fetchAll();

// Kaunsa bacha? (URL ya form se). Sirf apne bachon mein se hi mil sakta hai.
$childId = (int)($_POST['child_id'] ?? $_GET['child'] ?? 0);
$child   = null;
foreach ($children as $c) {
    if ((int)$c['id'] === $childId) { $child = $c; }
}
if ($childId > 0 && !$child) {
    set_flash('danger', 'Child not found.');
    redirect('parent/book_hospital.php');
}
if (!$child && count($children) === 1) {
    $child = $children[0];                         // ek hi bacha ho to khud chun lo
}

// Sirf wo hospitals jo admin ne manzoor kiye hain (ya admin ne khud add kiye hain)
$hospitals = $pdo->query(
    "SELECT h.id, h.hospital_name, h.address, h.location, h.phone
     FROM hospitals h
     LEFT JOIN users u ON u.id = h.user_id
     WHERE h.user_id IS NULL OR u.status = 'active'
     ORDER BY h.hospital_name"
)->fetchAll();

// Parent ka shehar: us ke shehar ke hospitals pehle dikhte hain ("Near you")
$stmt = $pdo->prepare('SELECT city FROM users WHERE id = ?');
$stmt->execute([$parentId]);
$parentCity = trim((string)$stmt->fetchColumn());
foreach ($hospitals as &$hh) {
    $hh['near'] = ($parentCity !== '' && mb_strtolower(trim($hh['location'])) === mb_strtolower($parentCity));
}
unset($hh);
usort($hospitals, fn($a, $b) => [$a['near'] ? 0 : 1, $a['hospital_name']] <=> [$b['near'] ? 0 : 1, $b['hospital_name']]);

// Kya us ke shehar mein koi hospital hai? Aur us ki purani hospital requests ka haal
$hasNear = false;
foreach ($hospitals as $hh) {
    if ($hh['near']) { $hasNear = true; }
}
$stmt = $pdo->prepare('SELECT hospital_name, city, status FROM hospital_suggestions WHERE parent_id = ? ORDER BY id DESC LIMIT 5');
$stmt->execute([$parentId]);
$mySuggestions = $stmt->fetchAll();

$errors = [];
$old    = ['vaccine_id' => '', 'hospital_id' => '', 'date' => '', 'time' => ''];
$eligibleDue = $eligibleSoon = $unavailable = $bookableIds = [];

if ($child) {
    // ---------- Is bache ka schedule (Day 9 wala dimaagh dobara istemal) ----------
    $stmt = $pdo->prepare(
        "SELECT child_id, vaccine_id, approval_status, vaccination_status, appointment_date
         FROM bookings WHERE child_id = ? AND approval_status <> 'rejected'"
    );
    $stmt->execute([$child['id']]);
    $childBookings = $stmt->fetchAll();
    // Purani (darj ki hui) vaccines bhi "lag chuki" ginti jayen
    foreach (past_records_by_child($pdo, [$child['id']])[$child['id']] ?? [] as $pr) {
        $childBookings[] = $pr;
    }

    $vaccines = $pdo->query(
        'SELECT id, vaccine_name, recommended_age, recommended_age_days, availability
         FROM vaccines ORDER BY recommended_age_days ASC, id ASC'
    )->fetchAll();

    $rows     = vaccine_schedule($child['dob'], $vaccines, $childBookings);
    $rowsById = [];
    foreach ($rows as $r) {
        $v = $r['vaccine'];
        $rowsById[$v['id']] = $r;

        $isDue  = in_array($r['status'], ['due', 'overdue'], true);
        $isSoon = ($r['status'] === 'upcoming' && $r['days_to_due'] <= 30);   // 30 din ke andar due hone wali
        if (!$isDue && !$isSoon) { continue; }

        if ($v['availability'] !== 'available') { $unavailable[] = $r; continue; }

        $bookableIds[] = (int)$v['id'];
        if ($isDue) { $eligibleDue[] = $r; } else { $eligibleSoon[] = $r; }
    }

    // ---------- Form submit ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($old as $key => $value) {
            $old[$key] = trim($_POST[$key] ?? '');
        }
        $vaccineId  = (int)$old['vaccine_id'];
        $hospitalId = (int)$old['hospital_id'];

        if (!verify_csrf()) {
            $errors[] = 'Session expired. Please try again.';
        }

        // 1) Vaccine
        if (!in_array($vaccineId, $bookableIds, true)) {
            $r = $rowsById[$vaccineId] ?? null;
            if (!$r) {
                $errors[] = 'Please select a vaccine.';
            } elseif (in_array($r['status'], ['done', 'booked'], true)) {
                $errors[] = 'This vaccine is already ' . ($r['status'] === 'done' ? 'completed' : 'booked') . ' for ' . $child['name'] . '.';
            } elseif ($r['vaccine']['availability'] !== 'available') {
                $errors[] = 'This vaccine is currently unavailable.';
            } else {
                $errors[] = 'This vaccine is not due yet. You can book it within 30 days of its due date.';
            }
        }

        // 2) Hospital (asli list mein hona chahiye)
        $hospitalOk = false;
        foreach ($hospitals as $h) {
            if ((int)$h['id'] === $hospitalId) { $hospitalOk = true; }
        }
        if (!$hospitalOk) {
            $errors[] = 'Please select a hospital.';
        }

        // 3) Tareekh aur waqt
        if (!is_valid_date($old['date'])) {
            $errors[] = 'Please choose a valid date.';
        } elseif ($old['date'] < $minDate) {
            $errors[] = 'You cannot book a past date or today. Please choose tomorrow or later.';
        } elseif ($old['date'] > $maxDate) {
            $errors[] = 'You can book up to 90 days ahead.';
        }
        if (!isset($slots[$old['time']])) {
            $errors[] = 'Please choose a valid time slot.';
        }

        // 4) Takrao ki jaanch (sirf jab baqi sab theek ho)
        if (!$errors) {
            // Isi bache ki usi din koi aur appointment?
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM bookings
                 WHERE child_id = ? AND appointment_date = ?
                   AND approval_status IN ('pending', 'approved') AND vaccination_status = 'pending'"
            );
            $stmt->execute([$child['id'], $old['date']]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors[] = $child['name'] . ' already has an appointment on that date. Please choose another day.';
            }

            // Hospital ke us slot mein jagah baqi hai?
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM bookings
                 WHERE hospital_id = ? AND appointment_date = ? AND appointment_time = ?
                   AND approval_status IN ('pending', 'approved')"
            );
            $stmt->execute([$hospitalId, $old['date'], $old['time']]);
            if ((int)$stmt->fetchColumn() >= $maxPerSlot) {
                $errors[] = 'That time slot is full at this hospital. Please choose another time.';
            }
        }

        // 5) Sab theek: save
        if (!$errors) {
            $stmt = $pdo->prepare(
                'INSERT INTO bookings (child_id, parent_id, hospital_id, vaccine_id, appointment_date, appointment_time)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$child['id'], $parentId, $hospitalId, $vaccineId, $old['date'], $old['time']]);

            set_flash('success', 'Appointment requested for ' . $child['name'] . ' (' . $rowsById[$vaccineId]['vaccine']['vaccine_name']
                . '). It will be confirmed once the VaxCare team approves it.');
            redirect(is_file(__DIR__ . '/my_bookings.php') ? 'parent/my_bookings.php' : 'parent/dashboard.php');
        }
    }
}

$pageTitle  = 'Book Appointment';
$activeMenu = 'book_hospital.php';
require_once __DIR__ . '/../includes/parent_header.php';
?>

<div class="mb-4">
  <h4 class="mb-1">Book Appointment</h4>
  <p class="text-muted mb-0">Choose a vaccine and a hospital. Our team will confirm your request.</p>
</div>

<?php if (!$children): ?>

  <div class="card">
    <div class="card-body p-5 text-center">
      <div class="icon-box mx-auto mb-3"><i class="bi bi-person-hearts"></i></div>
      <h5>Add a child first</h5>
      <p class="text-muted mb-3">You need to add your child's details before booking.</p>
      <a href="<?= BASE_URL ?>/parent/child_form.php" class="btn btn-primary">Add Child</a>
    </div>
  </div>

<?php elseif (!$child): ?>

  <!-- Kai bache hain: pehle chuno -->
  <h5 class="mb-3">Who is this appointment for?</h5>
  <div class="row g-3">
    <?php foreach ($children as $c): ?>
      <div class="col-md-6 col-xl-4">
        <a href="?child=<?= (int)$c['id'] ?>" class="text-decoration-none">
          <div class="card card-hover p-3 d-flex flex-row align-items-center gap-3">
            <div class="icon-box flex-shrink-0"><i class="bi bi-gender-<?= $c['gender'] === 'male' ? 'male' : 'female' ?>"></i></div>
            <div class="min-w-0">
              <div class="fw-semibold text-dark text-truncate"><?= e($c['name']) ?></div>
              <small class="text-muted"><?= e(child_age($c['dob'])) ?> old</small>
            </div>
            <i class="bi bi-chevron-right ms-auto text-muted"></i>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

<?php elseif (!$bookableIds): ?>

  <div class="card">
    <div class="card-body p-5 text-center">
      <div class="icon-box mx-auto mb-3"><i class="bi bi-shield-check"></i></div>
      <h5>Nothing to book for <?= e($child['name']) ?> right now</h5>
      <p class="text-muted mb-3">All due vaccines are completed or already booked, or no vaccine is due in the next 30 days.</p>
      <a href="<?= BASE_URL ?>/parent/dashboard.php" class="btn btn-primary">Back to dashboard</a>
    </div>
  </div>

<?php else: ?>

  <div class="row g-4">
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
            <input type="hidden" name="child_id" value="<?= (int)$child['id'] ?>">

            <!-- 1) Vaccine -->
            <div class="mb-4">
              <label for="vaccine_id" class="form-label fw-semibold">1. Vaccine</label>
              <select class="form-select" id="vaccine_id" name="vaccine_id" required>
                <option value="">Select a vaccine</option>

                <?php if ($eligibleDue): ?>
                  <optgroup label="Due now">
                    <?php foreach ($eligibleDue as $r): $v = $r['vaccine']; ?>
                      <option value="<?= (int)$v['id'] ?>" <?= (int)$old['vaccine_id'] === (int)$v['id'] ? 'selected' : '' ?>>
                        <?= e($v['vaccine_name']) ?> (<?= $r['status'] === 'overdue' ? 'Overdue' : 'Due now' ?>)
                      </option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>

                <?php if ($eligibleSoon): ?>
                  <optgroup label="Coming up (next 30 days)">
                    <?php foreach ($eligibleSoon as $r): $v = $r['vaccine']; ?>
                      <option value="<?= (int)$v['id'] ?>" <?= (int)$old['vaccine_id'] === (int)$v['id'] ? 'selected' : '' ?>>
                        <?= e($v['vaccine_name']) ?> (due in <?= e(humanize_days($r['days_to_due'])) ?>)
                      </option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>

                <?php if ($unavailable): ?>
                  <optgroup label="Currently unavailable">
                    <?php foreach ($unavailable as $r): ?>
                      <option disabled><?= e($r['vaccine']['vaccine_name']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
              </select>
              <div class="invalid-feedback">Please select a vaccine.</div>
            </div>

            <!-- 2) Hospital -->
            <div class="mb-4">
              <label class="form-label fw-semibold">2. Hospital</label>
              <div class="input-group mb-3">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" placeholder="Search by hospital name or city..."
                       data-filter-items="#hospitalList">
              </div>

              <?php if ($parentCity !== '' && $hasNear): ?>
                <div class="small text-muted mb-2"><i class="bi bi-geo-alt-fill text-success me-1"></i>Hospitals in <strong><?= e($parentCity) ?></strong> are shown first.</div>
              <?php elseif ($parentCity !== ''): ?>
                <div class="alert alert-warning py-2 small mb-3">
                  <i class="bi bi-geo-alt me-1"></i>We do not have a hospital in <strong><?= e($parentCity) ?></strong> yet. Hospitals from other cities are shown below.
                  <a href="#" class="fw-semibold" data-bs-toggle="modal" data-bs-target="#suggestModal">Suggest a hospital in <?= e($parentCity) ?></a>
                </div>
              <?php else: ?>
                <div class="small text-muted mb-2"><i class="bi bi-geo-alt me-1"></i><a href="<?= BASE_URL ?>/parent/profile.php">Add your city</a> to see hospitals near you first.</div>
              <?php endif; ?>

              <div id="hospitalList" class="row g-2">
                <?php foreach ($hospitals as $h): ?>
                  <div class="col-md-6 filter-item">
                    <input type="radio" class="btn-check" name="hospital_id" id="h<?= (int)$h['id'] ?>"
                           value="<?= (int)$h['id'] ?>" <?= (int)$old['hospital_id'] === (int)$h['id'] ? 'checked' : '' ?> required>
                    <label class="card hospital-option p-3 w-100" for="h<?= (int)$h['id'] ?>">
                      <div class="fw-semibold"><i class="bi bi-hospital text-primary me-1"></i><?= e($h['hospital_name']) ?><?= $h['near'] ? ' <span class="badge rounded-pill text-bg-success ms-1">Near you</span>' : '' ?></div>
                      <small class="text-muted d-block"><?= e($h['address']) ?>, <?= e($h['location']) ?></small>
                      <?php if (!empty($h['phone'])): ?>
                        <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($h['phone']) ?></small>
                      <?php endif; ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if (!$hospitals): ?>
                <p class="text-muted mb-0">No hospitals available yet.</p>
              <?php endif; ?>
              <div class="mt-3 small">Can't find your hospital?
                <a href="#" class="fw-semibold" data-bs-toggle="modal" data-bs-target="#suggestModal">Suggest it to us</a>
              </div>
            </div>

            <!-- 3) Date and time -->
            <div class="mb-4">
              <label class="form-label fw-semibold">3. Date and time</label>
              <div class="row g-3">
                <div class="col-sm-6">
                  <input type="date" class="form-control" name="date" value="<?= e($old['date']) ?>"
                         min="<?= $minDate ?>" max="<?= $maxDate ?>" required aria-label="Appointment date">
                  <div class="invalid-feedback">Please choose a date.</div>
                </div>
                <div class="col-sm-6">
                  <select class="form-select" name="time" required aria-label="Appointment time">
                    <option value="">Select a time</option>
                    <?php foreach ($slots as $value => $label): ?>
                      <option value="<?= $value ?>" <?= $old['time'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Please choose a time.</div>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-calendar-check me-1"></i>Request Appointment</button>
            <a href="<?= BASE_URL ?>/parent/dashboard.php" class="btn btn-outline-secondary ms-1">Cancel</a>
          </form>

        </div>
      </div>
    </div>

    <!-- Side info -->
    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <div class="icon-box flex-shrink-0"><i class="bi bi-gender-<?= $child['gender'] === 'male' ? 'male' : 'female' ?>"></i></div>
            <div class="min-w-0">
              <div class="fw-semibold text-truncate"><?= e($child['name']) ?></div>
              <small class="text-muted"><?= e(child_age($child['dob'])) ?> old</small>
            </div>
          </div>
          <?php if (count($children) > 1): ?>
            <a href="<?= BASE_URL ?>/parent/book_hospital.php" class="btn btn-sm btn-outline-secondary w-100 mt-3">Change child</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="card">
        <div class="card-body small">
          <h6 class="mb-3">Good to know</h6>
          <ul class="ps-3 mb-0">
            <li class="mb-2">Your request is sent to our team for approval.</li>
            <li class="mb-2">Appointments can be booked from tomorrow, up to 90 days ahead.</li>
            <li class="mb-2">Each hospital takes up to <?= $maxPerSlot ?> bookings per time slot.</li>
            <li>You will see the status on your dashboard.</li>
          </ul>
        </div>
      </div>

      <?php if ($mySuggestions): ?>
        <div class="card mt-3">
          <div class="card-body">
            <h6 class="mb-3">Your hospital requests</h6>
            <ul class="list-group list-group-flush small">
              <?php foreach ($mySuggestions as $sg): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2 px-0">
                  <span class="min-w-0"><span class="fw-semibold"><?= e($sg['hospital_name']) ?></span><br><span class="text-muted"><?= e($sg['city']) ?></span></span>
                  <?= status_badge($sg['status']) ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Naya hospital batane ka form: booking form ke BAHAR (form ke andar form nahi ho sakta) -->
  <div class="modal fade" id="suggestModal" tabindex="-1" aria-labelledby="suggestTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border:0;border-radius:18px;">
        <form method="post" action="<?= BASE_URL ?>/parent/suggest_hospital.php" class="needs-validation" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="child" value="<?= (int)$child['id'] ?>">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title" id="suggestTitle">Suggest a hospital</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small">Tell us about a hospital that is not in the list. Our team will verify it and add it, and then you can book there.</p>
            <div class="mb-3">
              <label for="sg_name" class="form-label">Hospital name</label>
              <input type="text" class="form-control" id="sg_name" name="hospital_name" minlength="3" maxlength="150" required>
              <div class="invalid-feedback">Please enter the hospital name.</div>
            </div>
            <div class="row g-3">
              <div class="col-sm-5">
                <label for="sg_city" class="form-label">City</label>
                <input type="text" class="form-control" id="sg_city" name="city" value="<?= e($parentCity) ?>" minlength="2" maxlength="80" required>
                <div class="invalid-feedback">Please enter the city.</div>
              </div>
              <div class="col-sm-7">
                <label for="sg_phone" class="form-label">Phone <span class="text-muted">(optional)</span></label>
                <input type="tel" class="form-control" id="sg_phone" name="phone" pattern="[0-9+\-\s]{10,15}" placeholder="03001234567">
              </div>
              <div class="col-12">
                <label for="sg_address" class="form-label">Address <span class="text-muted">(optional)</span></label>
                <input type="text" class="form-control" id="sg_address" name="address" maxlength="255">
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send request</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/parent_footer.php'; ?>
