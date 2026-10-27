<?php
// admin/request_action.php - Approve / Reject ka asli kaam (koi page nahi dikhta)
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect('admin/requests.php');
}

$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!in_array($action, ['approve', 'reject'], true)) {
    set_flash('danger', 'Invalid action.');
    redirect('admin/requests.php');
}

// Booking aur uski vaccine ki availability
$stmt = $pdo->prepare(
    'SELECT b.approval_status, v.availability
     FROM bookings b
     JOIN vaccines v ON v.id = b.vaccine_id
     WHERE b.id = ?'
);
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('danger', 'Request not found.');
    redirect('admin/requests.php');
}
if ($booking['approval_status'] !== 'pending') {
    set_flash('warning', 'This request has already been processed.');
    redirect('admin/requests.php');
}
// Business rule: jo vaccine available nahi, uski appointment approve nahi ho sakti
if ($action === 'approve' && $booking['availability'] !== 'available') {
    set_flash('warning', 'This vaccine is currently unavailable, so the request cannot be approved.');
    redirect('admin/requests.php');
}

$newStatus = ($action === 'approve') ? 'approved' : 'rejected';

// "AND approval_status = 'pending'" is liye ke do admin ek saath dabayen to dobara na badle
$stmt = $pdo->prepare("UPDATE bookings SET approval_status = ? WHERE id = ? AND approval_status = 'pending'");
$stmt->execute([$newStatus, $id]);

set_flash('success', 'Request ' . $newStatus . ' successfully.');
redirect('admin/requests.php');
