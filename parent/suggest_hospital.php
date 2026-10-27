<?php
// parent/suggest_hospital.php - Parent naya hospital batata hai (admin verify karke list mein jorta hai)
// Koi page nahi dikhta, sirf form ka kaam karke wapas bhej deta hai.
require_once __DIR__ . '/../includes/auth.php';
require_role('parent');
require_once __DIR__ . '/../config/db.php';

$parentId = (int)$_SESSION['user_id'];
$childId  = (int)($_POST['child'] ?? 0);
$back     = 'parent/book_hospital.php' . ($childId > 0 ? '?child=' . $childId : '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('danger', 'Invalid request.');
    redirect($back);
}

$name    = trim($_POST['hospital_name'] ?? '');
$city    = trim($_POST['city'] ?? '');
$address = trim($_POST['address'] ?? '');
$phone   = trim($_POST['phone'] ?? '');

// ---------- Validation ----------
$errors = [];
if (mb_strlen($name) < 3 || mb_strlen($name) > 150) {
    $errors[] = 'Hospital name must be between 3 and 150 characters.';
}
if (mb_strlen($city) < 2 || mb_strlen($city) > 80) {
    $errors[] = 'Please enter the city.';
}
if (mb_strlen($address) > 255) {
    $errors[] = 'Address can be at most 255 characters.';
}
if ($phone !== '' && !preg_match('/^[0-9+\-\s]{10,15}$/', $phone)) {
    $errors[] = 'Please enter a valid phone number or leave it empty.';
}
if ($errors) {
    set_flash('danger', implode(' ', $errors));
    redirect($back);
}

// ---------- Pehle se list mein to nahi? ----------
$stmt = $pdo->prepare('SELECT id FROM hospitals WHERE LOWER(hospital_name) = LOWER(?) AND LOWER(location) = LOWER(?)');
$stmt->execute([$name, $city]);
if ($stmt->fetch()) {
    set_flash('info', 'This hospital is already listed. Search for it by name in the hospital list.');
    redirect($back);
}

// ---------- Kisi ne pehle se request to nahi ki (review ke intezar mein)? ----------
$stmt = $pdo->prepare(
    "SELECT id FROM hospital_suggestions
     WHERE LOWER(hospital_name) = LOWER(?) AND LOWER(city) = LOWER(?) AND status = 'pending'"
);
$stmt->execute([$name, $city]);
if ($stmt->fetch()) {
    set_flash('info', 'This hospital has already been requested and our team is reviewing it.');
    redirect($back);
}

// ---------- Spam se bachao: ek parent ki ziyada se ziyada 3 requests review mein ----------
$stmt = $pdo->prepare("SELECT COUNT(*) FROM hospital_suggestions WHERE parent_id = ? AND status = 'pending'");
$stmt->execute([$parentId]);
if ((int)$stmt->fetchColumn() >= 3) {
    set_flash('warning', 'You already have 3 requests under review. Please wait until our team responds.');
    redirect($back);
}

$stmt = $pdo->prepare(
    'INSERT INTO hospital_suggestions (parent_id, hospital_name, city, address, phone) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$parentId, $name, $city, $address === '' ? null : $address, $phone === '' ? null : $phone]);

set_flash('success', 'Thank you! Our team will review "' . $name . '" and add it if it is verified.');
redirect($back);
