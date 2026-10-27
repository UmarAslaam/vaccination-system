<?php
// parent/child_delete.php - Bacha delete (koi page nahi dikhta)
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect('parent/children.php');
}

$parentId = (int)$_SESSION['user_id'];
$id       = (int)($_POST['id'] ?? 0);

// Pehle check: ye bacha isi parent ka hai?
$stmt = $pdo->prepare('SELECT id FROM children WHERE id = ? AND parent_id = ?');
$stmt->execute([$id, $parentId]);
if (!$stmt->fetch()) {
    set_flash('danger', 'Child not found.');
    redirect('parent/children.php');
}

// Agar bookings (vaccination record) hain to delete nahi, warna medical record ud jayega
$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE child_id = ?');
$stmt->execute([$id]);
if ((int)$stmt->fetchColumn() > 0) {
    set_flash('warning', 'This child has vaccination bookings, so the profile cannot be deleted. Please contact support if needed.');
    redirect('parent/children.php');
}

// Verify ho chuke purane records bhi medical record hain
$stmt = $pdo->prepare("SELECT COUNT(*) FROM past_vaccinations WHERE child_id = ? AND status = 'verified'");
$stmt->execute([$id]);
if ((int)$stmt->fetchColumn() > 0) {
    set_flash('warning', 'This child has verified vaccination records, so the profile cannot be deleted. Please contact support if needed.');
    redirect('parent/children.php');
}

$stmt = $pdo->prepare('DELETE FROM children WHERE id = ? AND parent_id = ?');
$stmt->execute([$id, $parentId]);

set_flash('success', 'Child deleted successfully.');
redirect('parent/children.php');
