<?php
// ============================================================
// Kết nối CSDL - Shop bán tài khoản
// ============================================================
require_once __DIR__ . '/config.php';

$host = 'localhost';
$dbname = 'account_shop';
$username = 'web';
$password = '123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Kết nối CSDL thất bại: ' . $e->getMessage());
}
