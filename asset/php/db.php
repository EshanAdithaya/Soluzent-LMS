<?php
// includes/db.php

define('DB_HOST', 'db-mysql-nyc3-14016-do-user-17700770-0.d.db.ondigitalocean.com');
define('DB_NAME', 'defaultdb');
define('DB_USER', 'doadmin');
define('DB_PASS', 'AVNS_l3SW8eljPIvmmGNUCFK');
define('DB_PORT', 25060);

session_start();
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/cert.pem', // Path to the SSL certificate file
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}
