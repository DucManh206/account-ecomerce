<?php
// CRUD Module for table: security
require_once __DIR__ . '/../../config/db.php';

// From security_modules.php


/**
 * Hash mật khẩu sử dụng bcrypt
 * @param string $password Mật khẩu gốc
 * @return string Mật khẩu đã hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}


function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function getLoginSecure($username, $password) {
    global $conn;
    
    $username = trim($username);
    
    if (empty($username) || empty($password)) {
        return false;
    }
    
    $strSQL = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $strSQL);
    
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Kiểm tra mật khẩu
        if (verifyPassword($password, $user['password'])) {
            return $user;
        }
    }
    
    return false;
}


function registerUserSecure($username, $password) {
    global $conn;
    
    $username = trim($username);
    
    if (empty($username) || empty($password)) {
        return false;
    }
    
    // Kiểm tra username đã tồn tại
    if (checkUserExists($username)) {
        return false;
    }
    
    // Hash mật khẩu
    $hashedPassword = hashPassword($password);
    
    $strSQL = "INSERT INTO users (username, password, role, balance) VALUES (?, ?, 0, 0)";
    $stmt = mysqli_prepare($conn, $strSQL);
    
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $username, $hashedPassword);
    return mysqli_stmt_execute($stmt);
}


function updateUserPassword($user_id, $new_password) {
    global $conn;
    
    $user_id = intval($user_id);
    
    if (empty($new_password)) {
        return false;
    }
    
    $hashedPassword = hashPassword($new_password);
    
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $user_id);
    return mysqli_stmt_execute($stmt);
}

/**
 * Kiểm tra username đã tồn tại
 * @param string $username
 * @return bool
 */
function checkUserExists($username) {
    global $conn;
    
    $username = trim($username);
    
    $strSQL = "SELECT id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $strSQL);
    
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return ($result && mysqli_num_rows($result) > 0);
}



