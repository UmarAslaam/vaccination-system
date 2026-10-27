<?php
// admin/vaccine_toggle.php - Available <-> Unavailable badalna (koi page nahi dikhta)
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect('admin/vaccines.php');
}

$id = (int)($_POST['id'] ?? 0);

// IF(...) ek hi query mein ulta kar deta hai: available -> unavailable, unavailable -> available
$stmt = $pdo->prepare(
    "UPDATE vaccines
     SET availability = IF(availability = 'available', 'unavailable', 'available')
     WHERE id = ?"
);
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    set_flash('success', 'Vaccine availability updated.');
} else {
    set_flash('danger', 'Vaccine not found.');
}
redirect('admin/vaccines.php');
