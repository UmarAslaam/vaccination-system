<?php
// config/db.php - database connection (PDO)
// Har us page mein include hota hai jahan database chahiye:
// require_once __DIR__ . '/../config/db.php';

$host = 'localhost';
$dbname = 'vaccination_db';
$username = 'root';   // XAMPP ka default user
$password = '';       // XAMPP ka default password khali hota hai

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // error aaye to dikhaye
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // result array ki shakal mein
            PDO::ATTR_EMULATE_PREPARES   => false,                    // asli prepared statements (security)
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    // Debug mein asli wajah, warna user ko sirf saaf sa message (server ki maloomat leak nahi hoti)
    if (defined('DEBUG') && DEBUG) {
        die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
    }
    die('The service is temporarily unavailable. Please try again later.');
}
