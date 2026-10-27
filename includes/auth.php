<?php
// includes/auth.php - page ki hifazat (guard)
// Har protected page ki sab se pehli lines mein:
//   require_once __DIR__ . '/../includes/auth.php';
//   require_role('admin');          // sirf admin andar aa sakta hai

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/db.php';

// Sirf login user ko ijazat, aur us ka account abhi bhi "active" ho
function require_login()
{
    global $pdo;

    if (!is_logged_in()) {
        set_flash('warning', 'Please login to continue.');
        redirect('login.php');
    }

    // Admin ne account band (disable) kar diya ho to chalta hua session bhi khatam
    $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->fetchColumn() !== 'active') {
        $_SESSION = [];
        session_destroy();
        session_start();
        session_regenerate_id(true);
        set_flash('danger', 'Your account is not active. Please contact the admin.');
        redirect('login.php');
    }
}

// Sirf mukarrar role ko ijazat (admin / parent / hospital)
function require_role($role)
{
    require_login();
    if (current_role() !== $role) {
        set_flash('danger', 'You are not allowed to open that page.');
        redirect(current_role() . '/dashboard.php');   // apne hi dashboard par wapas
    }
}
