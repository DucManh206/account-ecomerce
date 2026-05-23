<?php
/**
 * Account Field Types Modules - Quản lý loại field tài khoản
 */
require_once __DIR__ . '/../../database/connect.php';

function fieldType_getAll() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM account_field_types ORDER BY sort_order ASC, id ASC");
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) { $items[] = $row; }
    return $items;
}

function fieldType_getByKey($key) {
    global $conn;
    $key = $conn->real_escape_string($key);
    $result = mysqli_query($conn, "SELECT * FROM account_field_types WHERE `key` = '$key' LIMIT 1");
    return mysqli_fetch_assoc($result) ?: null;
}

function fieldType_getMap() {
    global $conn;
    $result = mysqli_query($conn, "SELECT `key`, label, icon_class FROM account_field_types ORDER BY sort_order ASC");
    $map = [];
    while ($row = mysqli_fetch_assoc($result)) { $map[$row['key']] = $row; }
    return $map;
}

function fieldType_create($data) {
    global $conn;
    $key  = $conn->real_escape_string($data['key'] ?? '');
    $label = $conn->real_escape_string($data['label'] ?? '');
    $icon = $conn->real_escape_string($data['icon_class'] ?? 'fa-key');
    $placeholder = $conn->real_escape_string($data['placeholder'] ?? '');
    $sort = intval($data['sort_order'] ?? 0);

    if (!$key || !$label) return ['success' => false, 'message' => 'Key và Label bắt buộc'];
    if (!preg_match('/^[a-z0-9_]+$/', $key)) return ['success' => false, 'message' => 'Key chỉ chứa a-z, 0-9, dấu gạch dưới'];

    $sql = "INSERT INTO account_field_types (`key`, label, icon_class, placeholder, sort_order) VALUES ('$key','$label','$icon','$placeholder',$sort)";
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Thêm thành công'];
    }
    $err = mysqli_error($conn);
    if (strpos($err, 'Duplicate') !== false) {
        return ['success' => false, 'message' => 'Key đã tồn tại'];
    }
    return ['success' => false, 'message' => $err];
}

function fieldType_update($id, $data) {
    global $conn;
    $id = intval($id);
    $label = $conn->real_escape_string($data['label'] ?? '');
    $icon = $conn->real_escape_string($data['icon_class'] ?? 'fa-key');
    $placeholder = $conn->real_escape_string($data['placeholder'] ?? '');
    $sort = intval($data['sort_order'] ?? 0);

    if (!$label) return ['success' => false, 'message' => 'Label bắt buộc'];

    $sql = "UPDATE account_field_types SET label='$label', icon_class='$icon', placeholder='$placeholder', sort_order=$sort WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Cập nhật thành công'];
    }
    return ['success' => false, 'message' => mysqli_error($conn)];
}

function fieldType_delete($id) {
    global $conn;
    $id = intval($id);
    // Không cho xóa field mặc định
    $r = mysqli_query($conn, "SELECT is_default FROM account_field_types WHERE id=$id");
    if ($r && mysqli_fetch_assoc($r)['is_default']) {
        return ['success' => false, 'message' => 'Không thể xóa field mặc định'];
    }
    mysqli_query($conn, "DELETE FROM account_field_types WHERE id=$id");
    return ['success' => true, 'message' => 'Xóa thành công'];
}

/**
 * Render field display HTML từ account_data JSON + field type map
 * @param string $accountData JSON string hoặc text thuần
 * @param array $fieldMap Key => ['label'=>..., 'icon_class'=>...]
 */
function renderAccountFields($accountData, $fieldMap = []) {
    $defaults = [
        'account'  => ['label' => 'Tài khoản', 'icon_class' => 'fa-user'],
        'password' => ['label' => 'Mật khẩu', 'icon_class' => 'fa-lock'],
        'email'    => ['label' => 'Email', 'icon_class' => 'fa-envelope'],
        'user'     => ['label' => 'Tài khoản', 'icon_class' => 'fa-user'],   // alias
        'pass'     => ['label' => 'Mật khẩu', 'icon_class' => 'fa-lock'],   // alias
        'cookie'   => ['label' => 'Cookie', 'icon_class' => 'fa-cookie-bite'],
        'token'    => ['label' => 'Token', 'icon_class' => 'fa-fingerprint'],
        'key'      => ['label' => 'Key', 'icon_class' => 'fa-key'],
        'pin'      => ['label' => 'PIN', 'icon_class' => 'fa-hashtag'],
        '2fa'      => ['label' => '2FA', 'icon_class' => 'fa-shield-halved'],
        'note'     => ['label' => 'Ghi chú', 'icon_class' => 'fa-sticky-note'],
        'link'     => ['label' => 'Link', 'icon_class' => 'fa-link'],
        'code'     => ['label' => 'Mã', 'icon_class' => 'fa-barcode'],
    ];
    $fieldMap = array_merge($defaults, $fieldMap);

    $decoded = json_decode($accountData, true);

    if (!is_array($decoded)) {
        return '<div class="account-raw">' . nl2br(htmlspecialchars($accountData)) . '</div>';
    }

    $html = '';
    foreach ($decoded as $k => $v) {
        $info = $fieldMap[$k] ?? ['label' => ucfirst(str_replace('_', ' ', $k)), 'icon_class' => 'fa-key'];
        $label = htmlspecialchars($info['label']);
        $icon = htmlspecialchars($info['icon_class']);
        $value = htmlspecialchars(strval($v));
        $fieldId = 'f-' . preg_replace('/[^a-z0-9]/i', '', $k) . '-' . substr(md5($k), 0, 4);
        $isLink = ($k === 'link' || strpos($value, 'http') === 0);

        $html .= '<div class="nx-account-field">';
        $html .= '<div class="nx-account-field-label"><i class="fa-solid ' . $icon . '"></i>' . $label . '</div>';
        if ($isLink) {
            $html .= '<div class="nx-account-field-value">'
                . '<a href="' . $value . '" target="_blank" class="account-link">' . $value . '</a>'
                . '<button class="nx-account-copy" onclick="copyText(\'' . $fieldId . '\')"><i class="fa-solid fa-copy"></i></button>'
                . '<span class="account-field-value-text" id="' . $fieldId . '" style="display:none">' . $value . '</span>'
                . '</div>';
        } else {
            $html .= '<div class="nx-account-field-value">'
                . '<span id="' . $fieldId . '">' . $value . '</span>'
                . '<button class="nx-account-copy" onclick="copyText(\'' . $fieldId . '\')"><i class="fa-solid fa-copy"></i></button>'
                . '</div>';
        }
        $html .= '</div>';
    }

    return '<div class="nx-account-fields">' . $html . '</div>';
}
