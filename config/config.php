<?php
// config/config.php - poore project ki general settings
// Har page ki shuru mein include hoti hai (header.php isko khud bulata hai)

// ---------- Debug mode ----------
// true  = errors screen par dikhte hain (banate waqt kaam ki cheez)
// false = errors chhup jate hain, log mein jate hain (final demo/submit ke liye)
define('DEBUG', true);

error_reporting(E_ALL);
ini_set('display_errors', DEBUG ? '1' : '0');
ini_set('log_errors', '1');

// ---------- Session (login yaad rakhna) ----------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');          // anjaan session ID qabool nahi
    session_set_cookie_params([
        'httponly' => true,                           // JavaScript cookie nahi padh sakti
        'samesite' => 'Lax',                          // dusri website se aayi request mein cookie nahi jati
    ]);
    session_start();
}

// ---------- Security headers ----------
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');            // koi hamare page ko apni site mein frame nahi kar sakta
    header('X-Content-Type-Options: nosniff');        // browser file ki type apni marzi se nahi guess karta
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Certificate ke number ko jaali hone se bachane ka secret. Koi bhi lamba, random text likh do.
define('CERT_SECRET', 'vaxcare-change-this-secret-7f3a9c2e51d8');

define('APP_NAME', 'VaxCare');                    // website ka naam
define('APP_TAGLINE', 'Smart Vaccination Management');
define('BASE_URL', '/vaccination-system');        // project folder ka naam (htdocs ke andar)

date_default_timezone_set('Asia/Karachi');
