<?php
define('DB_HOST', 'sql111.infinityfree.com');   // your actual DB host from vPanel
define('DB_NAME', 'if0_42551370_cr_portal');   // your actual DB name
define('DB_USER', 'if0_42551370');             // your actual DB username
define('DB_PASS', '0r0Xy8c83XI');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}