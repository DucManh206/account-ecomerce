<?php
#config database

$host = '127.0.0.1';
$username = 'web';
$password = '123';
$dbname = 'mydb';
// ========================
//    kết nối database
// ========================
$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
?>
