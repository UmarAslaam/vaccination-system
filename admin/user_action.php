<?php
// admin/user_action.php - Account approve / disable / enable (koi page nahi dikhta)
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

// Wapas kahan jana hai: users page (filters ke saath) ya hospitals page
$qs = $_POST['qs'] ?? '';
if (!preg_match('/^[A-Za-z0-9=&_.%+\-]{0,200}$/', $qs)) { $qs = ''; }     // sirf mehfooz characters
$back = (($_POST['back'] ?? '') === 'hospitals')
    ? 'admin/hospitals.php'
    : 'admin/users.php' . ($qs !== '' ? '?' . $qs : '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect($back);
}

$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

// "role <> 'admin'": admin ka apna account is raste se kabhi nahi badal sakta
$stmt = $pdo->prepare("SELECT status FROM users WHERE id = ? AND role <> 'admin'");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'Account not found.');
    redirect($back);
}

$new = null;
if ($action === 'approve' && $user['status'] === 'pending') {
    $new = 'active';
    $msg = 'Account approved. They can now login.';
} elseif ($action === 'disable' && in_array($user['status'], ['active', 'pending'], true)) {
    $new = 'disabled';
    $msg = 'Account disabled.';
} elseif ($action === 'enable' && $user['status'] === 'disabled') {
    $new = 'active';
    $msg = 'Account enabled.';
}

if ($new === null) {
    set_flash('warning', 'This action is not available for the account in its current state.');
    redirect($back);
}

// Naya status, aur galat-password ka lock bhi saaf
$stmt = $pdo->prepare("UPDATE users SET status = ?, failed_logins = 0, locked_until = NULL WHERE id = ? AND role <> 'admin'");
$stmt->execute([$new, $id]);

set_flash('success', $msg);
redirect($back);
