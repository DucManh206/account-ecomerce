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
        $output .= '
        <div class="custom-alert custom-alert-success" role="alert">
            <div class="alert-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="alert-content">
                <strong>Thành công:</strong> ' . htmlspecialchars($success) . '
            </div>
            <button type="button" class="alert-close" onclick="this.parentElement.remove();" title="Đóng">&times;</button>
        </div>';
    }
    
    // Hiển thị thông báo error nếu có
    $error = get_flash('error');
    if ($error) {
        $output .= '
        <div class="custom-alert custom-alert-error" role="alert">
            <div class="alert-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="alert-content">
                <strong>Lỗi:</strong> ' . htmlspecialchars($error) . '
            </div>
            <button type="button" class="alert-close" onclick="this.parentElement.remove();" title="Đóng">&times;</button>
        </div>';
    }
    
    return $output;
}
?>
