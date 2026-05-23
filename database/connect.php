<?php
require_once __DIR__ . '/../config/database_config.php';

global $conn;

// Connect WITHOUT database first to create it if needed
$conn_root = @mysqli_connect($host, $username, $password);
if (!$conn_root) {
    die("Ket noi MySQL that bai: " . mysqli_connect_error());
}

// Create database if not exists
$dbname_escaped = $conn_root->real_escape_string($dbname);
$result = $conn_root->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname_escaped'");
if ($result && $result->num_rows == 0) {
    if (!$conn_root->query("CREATE DATABASE `$dbname_escaped` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        $conn_root->close();
        die("Tao database that bai: " . $conn_root->error);
    }
}
$conn_root->close();

// Now connect WITH the database
$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Ket noi CSDL that bai: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
