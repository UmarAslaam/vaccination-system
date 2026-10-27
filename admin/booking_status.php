<?php
// admin/booking_status.php - Jis hospital ka apna login account nahi, uski booking ka vaccination status admin likhta hai
// (registered hospital ka status sirf wahi hospital likh sakta hai)
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

$qs = $_POST['qs'] ?? '';
if (!preg_match('/^[A-Za-z0-9=&_.%+\-]{0,200}$/', $qs)) { $qs = ''; }
$back = 'admin/bookings.php' . ($qs !== '' ? '?' . $qs : '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect($back);
}

$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
if (!in_array($action, ['vaccinated', 'not_vaccinated'], true)) {
    set_flash('danger', 'Invalid action.');
    redirect($back);
}

$stmt = $pdo->prepare(
    'SELECT b.approval_status, b.vaccination_status, b.appointment_date, h.user_id AS hospital_user
     FROM bookings b JOIN hospitals h ON h.id = b.hospital_id
     WHERE b.id = ?'
);
$stmt->execute([$id]);
$b = $stmt->fetch();

if (!$b) {
    set_flash('danger', 'Booking not found.');
} elseif ($b['hospital_user'] !== null) {
    set_flash('warning', 'This hospital has its own account, so only the hospital can record the vaccination.');
} elseif ($b['approval_status'] !== 'approved' || $b['vaccination_status'] !== 'pending') {
    set_flash('warning', 'This booking cannot be updated.');
} elseif ($b['appointment_date'] > date('Y-m-d')) {
    set_flash('warning', 'You can update the status only on or after the appointment day.');
} else {
    $stmt = $pdo->prepare("UPDATE bookings SET vaccination_status = ? WHERE id = ? AND vaccination_status = 'pending'");
    $stmt->execute([$action, $id]);
    set_flash('success', $action === 'vaccinated' ? 'Marked as vaccinated.' : 'Marked as not vaccinated.');
}
redirect($back);
