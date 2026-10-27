<?php
// parent/past_delete.php - Apna darj kiya hua purana record hatana (koi page nahi dikhta)
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';

$childId = (int)($_POST['child_id'] ?? 0);
$back    = 'parent/past_vaccinations.php' . ($childId > 0 ? '?child=' . $childId : '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect($back);
}

$id       = (int)($_POST['id'] ?? 0);
$parentId = (int)$_SESSION['user_id'];

// Teen shartein ek query mein: record mere bache ka ho, aur abhi verified na ho
$stmt = $pdo->prepare(
    "DELETE FROM past_vaccinations
     WHERE id = ?
       AND child_id IN (SELECT id FROM children WHERE parent_id = ?)
       AND status <> 'verified'"
);
$stmt->execute([$id, $parentId]);

if ($stmt->rowCount() > 0) {
    set_flash('success', 'Record removed.');
} else {
    set_flash('warning', 'This record cannot be removed (it may already be verified).');
}
redirect($back);
