<?php
// includes/config.php
session_start();

// EDIT THESE to match your environment
$DB_HOST = 'localhost';
$DB_NAME = 'tea_shop';
$DB_USER = 'root';
$DB_PASS = '';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
