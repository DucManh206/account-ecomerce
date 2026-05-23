<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Redirect tương đối
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $scriptDir = dirname($scriptPath);
    $adminPos = strpos($scriptDir, '/admin');
    $basePath = $adminPos !== false ? substr($scriptDir, 0, $adminPos) : '';
    header("Location: " . $basePath . "/admin/login.php");
    exit();
}
?>
