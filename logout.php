<?php
// logout.php - session khatam karke login page par bhej do
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$_SESSION = [];                 // session ka saara data khali
session_destroy();              // session khatam
session_start();                // flash message ke liye naya session
session_regenerate_id(true);

set_flash('success', 'You have been logged out successfully.');
redirect('login.php');
