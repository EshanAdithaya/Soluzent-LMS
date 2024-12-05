<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    // Create DSN and PDO instance
    $dsn = sprintf(
        "mysql:host=%s;port=%d;dbname=%s;charset=%s",
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    header('Location: /maintenance.html');
    exit;
}