<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = '127.0.0.1';   // try 127.0.0.1 first
$port = 3306;         // XAMPP default
$db   = 'YOUR_DB';
$user = 'YOUR_USER';
$pass = 'YOUR_PASS';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "Connected to DB OK";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
