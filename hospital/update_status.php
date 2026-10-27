<?php
// hospital/update_status.php - Vaccinated / Not vaccinated likhna (koi page nahi dikhta)
require_once __DIR__ . '/../includes/auth.php';
require_role('hospital');
require_once __DIR__ . '/../config/db.php';

$views = ['today', 'upcoming', 'update', 'completed', 'all'];
$view  = in_array($_POST['view'] ?? '', $views, true) ? $_POST['view'] : 'today';
$back  = 'hospital/appointments.php?view=' . $view;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect($back);
}

$hospital = require_hospital($pdo);
$id       = (int)($_POST['id'] ?? 0);
$action   = $_POST['action'] ?? '';

if (!in_array($action, ['vaccinated', 'not_vaccinated'], true)) {
    set_flash('danger', 'Invalid action.');
    redirect($back);
}

// "AND hospital_id = ?" : sirf APNE hospital ki booking milegi, kisi aur ki nahi
$stmt = $pdo->prepare(
    'SELECT appointment_date, approval_status, vaccination_status
     FROM bookings WHERE id = ? AND hospital_id = ?'
);
$stmt->execute([$id, $hospital['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('danger', 'Appointment not found.');
    redirect($back);
}
if ($booking['approval_status'] !== 'approved') {
    set_flash('danger', 'This appointment is not approved.');
    redirect($back);
}
if ($booking['vaccination_status'] !== 'pending') {
    set_flash('warning', 'This appointment has already been recorded.');
    redirect($back);
}
if ($booking['appointment_date'] > date('Y-m-d')) {
    set_flash('warning', 'You can update the status only on or after the appointment day.');
    redirect($back);
}

// Phir bhi shartein query mein: do log ek saath dabayen to dobara na badle
$stmt = $pdo->prepare(
    "UPDATE bookings SET vaccination_status = ?
     WHERE id = ? AND hospital_id = ? AND vaccination_status = 'pending'"
);
$stmt->execute([$action, $id, $hospital['id']]);

set_flash('success', $action === 'vaccinated' ? 'Marked as vaccinated.' : 'Marked as not vaccinated.');
redirect($back);
