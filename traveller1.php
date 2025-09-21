<?php
// db.php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'traveller_db';   // <- change if yours is different
$DB_USER = 'root';
$DB_PASS = '';              // default for XAMPP

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
  exit('DB connection failed: ' . htmlspecialchars($e->getMessage()));
}
