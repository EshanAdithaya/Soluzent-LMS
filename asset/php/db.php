<?php
require_once __DIR__ . '/config.php';

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];

    // Handle SSL Certificate
    $ssl_ca = __DIR__ . '/ca-certificate.crt';
    if (!file_exists($ssl_ca)) {
        $cert = @file_get_contents('https://dl.cacerts.digicert.com/DigiCertGlobalRootCA.crt.pem');
        if ($cert) {
            if (@file_put_contents($ssl_ca, $cert) === false) {
                throw new Exception('Failed to save SSL certificate');
            }
        } else {
            throw new Exception('Failed to download SSL certificate');
        }
    }
    
    if (file_exists($ssl_ca)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $ssl_ca;
    }

    $dsn = sprintf(
        "mysql:host=%s;port=%d;dbname=%s;charset=%s",
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Test the connection
    $pdo->query('SELECT 1');

} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    // For debugging only - remove in production
    error_log("Connection Details: Host=" . DB_HOST . ", Port=" . DB_PORT . ", Database=" . DB_NAME);
    die("Connection failed. Please check the error logs for details.");
}

return $pdo;