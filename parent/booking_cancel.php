<?php
// parent/booking_cancel.php - Pending booking cancel (koi page nahi dikhta)
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect('parent/my_bookings.php');
}

$id       = (int)($_POST['id'] ?? 0);
$parentId = (int)$_SESSION['user_id'];

// Teen shartein ek hi query mein: meri booking ho, aur abhi pending ho
//   parent_id = ?                  -> kisi aur ki booking cancel nahi ho sakti
//   approval_status = 'pending'    -> admin approve kar chuka ho to cancel nahi
$stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND parent_id = ? AND approval_status = 'pending'");
$stmt->execute([$id, $parentId]);

if ($stmt->rowCount() > 0) {
    set_flash('success', 'Your appointment request has been cancelled.');
} else {
    set_flash('warning', 'This booking cannot be cancelled. It may already be processed.');
}
redirect('parent/my_bookings.php');
