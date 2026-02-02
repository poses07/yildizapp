<?php
$host = 'localhost';
$db   = 'yildizapp_db';
$user = 'root';
$pass = ''; // XAMPP default password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In production, log error and show generic message
    die(json_encode(['error' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]));
}

