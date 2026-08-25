<?php
// Quản lý CSRF Token để bảo vệ form POST

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(bool $jsonResponse = false) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(419);
            if ($jsonResponse) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Phiên thao tác đã hết hạn. Vui lòng tải lại trang và thử lại.',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            die('Phiên thao tác đã hết hạn. Vui lòng tải lại trang và thử lại.');
        }
    }
}
?>
