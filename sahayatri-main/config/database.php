<?php
// system/config/database.php
// SAHAYATRI Database Connection & Auto-Setup Helper

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sahayatri_db');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // First attempt connection to specific database
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // If database does not exist, try creating it and importing schema
            if ($e->getCode() == 1049) { // Unknown database error code
                try {
                    $serverPdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
                    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    
                    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);

                    // Auto-seed schema if file exists
                    $schemaFile = __DIR__ . '/../schema.sql';
                    if (file_exists($schemaFile)) {
                        $sql = file_get_contents($schemaFile);
                        $pdo->exec($sql);
                    }
                } catch (PDOException $ex) {
                    die("Database Initialization Error: " . $ex->getMessage());
                }
            } else {
                die("Database Connection Failed: " . $e->getMessage() . "<br><small>Make sure MySQL is running in XAMPP on port 3306.</small>");
            }
        }
    }
    return $pdo;
}
