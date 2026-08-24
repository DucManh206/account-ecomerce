<?php
// Quản lý Flash Message (Thông báo 1 lần qua Session)

function set_flash($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][$type] = $message;
}

function get_flash($type) {
    if (isset($_SESSION['flash_messages'][$type])) {
        $msg = $_SESSION['flash_messages'][$type];
        unset($_SESSION['flash_messages'][$type]);
        return $msg;
    }
    return null;
}

function render_flash() {
    $output = '';
    
    // Hiển thị thông báo success nếu có
    $success = get_flash('success');
    if ($success) {
        $output .= '<div class="frontend-alert" style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid var(--success, #10b981); color: #a7f3d0; margin-top: 20px; margin-bottom: 20px;">' . htmlspecialchars($success) . '</div>';
    }
    
    // Hiển thị thông báo error nếu có
    $error = get_flash('error');
    if ($error) {
        $output .= '<div class="frontend-alert frontend-alert-error" style="margin-top: 20px; margin-bottom: 20px;">' . htmlspecialchars($error) . '</div>';
    }
    
    return $output;
}
?>
