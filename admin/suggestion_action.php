<?php
// admin/suggestion_action.php - Hospital request approve / reject (koi page nahi dikhta)
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect('admin/suggestions.php');
}

$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
if (!in_array($action, ['approve', 'reject'], true)) {
    set_flash('danger', 'Invalid action.');
    redirect('admin/suggestions.php');
}

// Sirf wo request jo abhi pending hai
$stmt = $pdo->prepare("SELECT * FROM hospital_suggestions WHERE id = ? AND status = 'pending'");
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) {
    set_flash('warning', 'This request was not found or has already been processed.');
    redirect('admin/suggestions.php');
}

if ($action === 'reject') {
    $pdo->prepare("UPDATE hospital_suggestions SET status = 'rejected', decided_at = ? WHERE id = ? AND status = 'pending'")
        ->execute([date('Y-m-d H:i:s'), $id]);
    set_flash('success', 'Request rejected.');
    redirect('admin/suggestions.php');
}

// ---------- Approve ----------
try {
    $pdo->beginTransaction();

    // Pehle se list mein ho to naya hospital nahi banate (duplicate se bachao)
    $stmt = $pdo->prepare('SELECT id FROM hospitals WHERE LOWER(hospital_name) = LOWER(?) AND LOWER(location) = LOWER(?) LIMIT 1');
    $stmt->execute([$s['hospital_name'], $s['city']]);
    $hospitalId = $stmt->fetchColumn();

    if (!$hospitalId) {
        // Admin ka add kiya hua hospital (abhi uska apna login account nahi)
        $stmt = $pdo->prepare(
            'INSERT INTO hospitals (user_id, hospital_name, address, location, phone) VALUES (NULL, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $s['hospital_name'],
            ($s['address'] !== null && $s['address'] !== '') ? $s['address'] : 'Address not provided',
            $s['city'],
            $s['phone'],
        ]);
        $hospitalId = $pdo->lastInsertId();
    }

    $pdo->prepare("UPDATE hospital_suggestions SET status = 'approved', hospital_id = ?, decided_at = ? WHERE id = ? AND status = 'pending'")
        ->execute([$hospitalId, date('Y-m-d H:i:s'), $id]);

    $pdo->commit();
    set_flash('success', '"' . $s['hospital_name'] . '" is now in the hospital list. The parent can book there.');
} catch (PDOException $ex) {
    $pdo->rollBack();
    set_flash('danger', 'Could not approve the request. Please try again.');
}
redirect('admin/suggestions.php');
