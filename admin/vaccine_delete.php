<?php
// admin/vaccine_delete.php - Vaccine delete (koi page nahi dikhta)
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect('admin/vaccines.php');
}

$id = (int)($_POST['id'] ?? 0);

// Agar is vaccine ki bookings hain to delete nahi karte, warna bachon ka vaccination record ud jayega
$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE vaccine_id = ?');
$stmt->execute([$id]);
$used = (int)$stmt->fetchColumn();

// Purane (past) records mein bhi use ho rahi ho to delete nahi
$stmt = $pdo->prepare('SELECT COUNT(*) FROM past_vaccinations WHERE vaccine_id = ?');
$stmt->execute([$id]);
$used += (int)$stmt->fetchColumn();

if ($used > 0) {
    set_flash('warning', "This vaccine is used in $used record(s), so it cannot be deleted. Mark it Unavailable instead.");
    redirect('admin/vaccines.php');
}

$stmt = $pdo->prepare('DELETE FROM vaccines WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    set_flash('success', 'Vaccine deleted successfully.');
} else {
    set_flash('danger', 'Vaccine not found.');
}
redirect('admin/vaccines.php');
