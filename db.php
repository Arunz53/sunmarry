<?php
// db.php - local XAMPP / MariaDB settings (for development)








define('DB_HOST', 'localhost');
define('DB_NAME', 'u478906159_marriage');   // local DB name
define('DB_USER', 'root');       // XAMPP default
define('DB_PASS', '');  




function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            // Friendly debug while developing; remove or log in production
            die("Connection failed: " . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}

$pdo = getDB();

// Start session if not already started and not running in CLI; avoid headers-sent warnings
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
?>
