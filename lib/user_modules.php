<?php
require_once __DIR__ . '/../database/connect.php';

function getLogin($user, $pass) {
    global $conn;

    $strSQL = "SELECT id, username, password, role, balance FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $strSQL);

    if (!$stmt) {
        error_log("MySQL Prepare Error: " . mysqli_error($conn));
        return false;
    }

    mysqli_stmt_bind_param($stmt, "s", $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($pass, $row['password'])) {
            return $row;
        }
    }
    return false;
}

function getUserById($userId) {
    global $conn;

    $strSQL = "SELECT id, username, role, balance, created_at FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $strSQL);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}

function getBalance($username) {
    global $conn;

    $strSQL = "SELECT balance FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $strSQL);

    if (!$stmt) {
        error_log("MySQL Prepare Error: " . mysqli_error($conn));
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['balance'] ?? 0;
    }
    return 0;
}

function checkUserExists($username) {
    global $conn;

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

function registerUser($username, $password) {
    global $conn;

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $strSQL = "INSERT INTO users (username, password, role, balance) VALUES (?, ?, 0, 100000)";
    $stmt = mysqli_prepare($conn, $strSQL);

    if (!$stmt) {
        error_log("MySQL Execute Error: " . mysqli_error($conn));
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ss", $username, $hashedPassword);
    if (!mysqli_stmt_execute($stmt)) {
        error_log("MySQL Execute Error: " . mysqli_error($conn));
        return false;
    }

    return mysqli_insert_id($conn);
}

function updateUserBalance($userId, $newBalance) {
    global $conn;

    $strSQL = "UPDATE users SET balance = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $strSQL);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ii", $newBalance, $userId);
    return mysqli_stmt_execute($stmt);
}
