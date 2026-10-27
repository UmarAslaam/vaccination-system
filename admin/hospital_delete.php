<?php
// admin/hospital_delete.php - Hospital delete karne wali file (isme koi page nahi dikhta)
// Ye file bhi protected hai, kyunki koi URL type karke seedha chala sakta hai.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

// Sirf POST allow. Link kholne (GET) se kuch delete nahi hona chahiye.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect('admin/hospitals.php');
}

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT user_id FROM hospitals WHERE id = ?');
$stmt->execute([$id]);
$hospital = $stmt->fetch();

if (!$hospital) {
    set_flash('danger', 'Hospital not found.');
    redirect('admin/hospitals.php');
}

try {
    $pdo->beginTransaction();

    // 1) Hospital delete (iski bookings CASCADE se khud delete ho jati hain)
    $pdo->prepare('DELETE FROM hospitals WHERE id = ?')->execute([$id]);

    // 2) Agar is hospital ka login account tha to wo bhi hatao (taake bekar account na bache)
    if ($hospital['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'hospital'")->execute([$hospital['user_id']]);
    }

    $pdo->commit();
    set_flash('success', 'Hospital deleted successfully.');
} catch (PDOException $ex) {
    $pdo->rollBack();
    set_flash('danger', 'Could not delete the hospital. Please try again.');
}

redirect('admin/hospitals.php');
