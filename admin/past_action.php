<?php
// admin/past_action.php - Purana record verify / reject (koi page nahi dikhta)
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect('admin/past_records.php');
}

$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!in_array($action, ['verify', 'reject'], true)) {
    set_flash('danger', 'Invalid action.');
    redirect('admin/past_records.php');
}
$new = ($action === 'verify') ? 'verified' : 'rejected';

// "AND status = 'pending'": jo pehle se process ho chuka usay dobara nahi badalte
$stmt = $pdo->prepare("UPDATE past_vaccinations SET status = ? WHERE id = ? AND status = 'pending'");
$stmt->execute([$new, $id]);

if ($stmt->rowCount() > 0) {
    set_flash('success', 'Record ' . $new . '.');
} else {
    set_flash('warning', 'This record was not found or has already been processed.');
}
redirect('admin/past_records.php');
