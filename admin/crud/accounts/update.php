<?php
require_once __DIR__ . '/accounts.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: list.php?error=' . urlencode('ID tài khoản không hợp lệ.'));
    exit;
}

$account = getAccountById($pdo, $id);
if (!$account) {
    header('Location: list.php?error=' . urlencode('Tài khoản không tồn tại.'));
    exit;
}

$categories = getCategories($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Convert datetime-local format 'Y-m-d\TH:i' to MySQL standard format 'Y-m-d H:i:s'
    $createdAtInput = trim($_POST['created_at'] ?? '');
    $dbCreatedAt = !empty($createdAtInput) ? date('Y-m-d H:i:s', strtotime($createdAtInput)) : date('Y-m-d H:i:s');

    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price' => trim($_POST['price'] ?? 0),
        'category_id' => trim($_POST['category_id'] ?? ''),
        'image' => trim($_POST['image'] ?? ''),
        'account_detail' => trim($_POST['account_detail'] ?? ''),
        'status' => $_POST['status'] ?? 'available',
        'created_at' => $dbCreatedAt
    ];

    if ($data['name'] === '' || $data['price'] === '') {
        $error = 'Vui lòng nhập tên tài khoản và giá bán.';
    } else {
        if (updateAccount($pdo, $id, $data)) {
            header('Location: list.php?success=' . urlencode('Cập nhật tài khoản thành công.'));
            exit;
        } else {
            $error = 'Có lỗi xảy ra khi cập nhật tài khoản.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa tài khoản - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    
    <main class="main-content">
        <header class="topbar">
            <h1>Chỉnh sửa tài khoản (ID: #<?= htmlspecialchars($account['id']) ?>)</h1>
            <a href="list.php" class="btn btn-secondary">Quay lại</a>
        </header>
        
        <div class="content-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-card">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Tên tài khoản</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($account['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Giá bán (đ)</label>
                        <input type="number" id="price" name="price" min="0" value="<?= htmlspecialchars($account['price']) ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Danh mục</label>
                        <select id="category_id" name="category_id">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $account['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Trạng thái</label>
                        <select id="status" name="status">
                            <option value="available" <?= $account['status'] === 'available' ? 'selected' : '' ?>>Đang bán</option>
                            <option value="sold" <?= $account['status'] === 'sold' ? 'selected' : '' ?>>Đã bán</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="created_at">Ngay dang ban (Chinh sua thoi gian dang)</label>
                        <input type="datetime-local" id="created_at" name="created_at" value="<?= date('Y-m-d\TH:i', strtotime($account['created_at'])) ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image">Ảnh sản phẩm (URL)</label>
                    <input type="text" id="image" name="image" value="<?= htmlspecialchars($account['image'] ?? '') ?>" placeholder="https://...">
                </div>
                
                <div class="form-group">
                    <label for="description">Mô tả hiển thị</label>
                    <textarea id="description" name="description" rows="5"><?= htmlspecialchars($account['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="account_detail">Thông tin tài khoản đăng nhập (Bảo mật - chỉ hiện sau khi mua)</label>
                    <textarea id="account_detail" name="account_detail" rows="4" placeholder="Tên đăng nhập, mật khẩu, email, ghi chú..." required><?= htmlspecialchars($account['account_detail'] ?? '') ?></textarea>
                </div>
                
                <!-- Bảng tự động điền trực quan -->
                <div style="background-color: #0d0d0d; border: 1px solid var(--border-color); padding: 20px; margin-top: -10px; margin-bottom: 10px;">
                    <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #a78bfa; margin-bottom: 12px; letter-spacing: 0.5px;">Bảng điền thông tin trực quan (Tự động cập nhật phía trên)</div>
                    <div class="form-row" style="margin-bottom: 12px;">
                        <div class="form-group">
                            <label style="font-size: 0.8rem; color: var(--text-muted);">Tên đăng nhập / Email bán</label>
                            <input type="text" id="helper_user" placeholder="Netflix email/username..." oninput="updateAccountDetailTextarea()">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.8rem; color: var(--text-muted);">Mật khẩu</label>
                            <input type="text" id="helper_pass" placeholder="Mật khẩu..." oninput="updateAccountDetailTextarea()">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label style="font-size: 0.8rem; color: var(--text-muted);">Hạn sử dụng / Thông tin phụ</label>
                            <input type="text" id="helper_info" placeholder="Ví dụ: Netflix 4K - 6 tháng" oninput="updateAccountDetailTextarea()">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.8rem; color: var(--text-muted);">Ghi chú khác</label>
                            <input type="text" id="helper_note" placeholder="Ví dụ: Đã đổi mật khẩu..." oninput="updateAccountDetailTextarea()">
                        </div>
                    </div>
                </div>
                
                <script>
                    function updateAccountDetailTextarea() {
                        var user = document.getElementById('helper_user').value.trim();
                        var pass = document.getElementById('helper_pass').value.trim();
                        var info = document.getElementById('helper_info').value.trim();
                        var note = document.getElementById('helper_note').value.trim();
                        
                        var compiled = '';
                        if (user) compiled += 'Tên đăng nhập: ' + user + '\n';
                        if (pass) compiled += 'Mật khẩu: ' + pass + '\n';
                        if (info) compiled += 'Hạn dùng/Thông tin: ' + info + '\n';
                        if (note) compiled += 'Ghi chú: ' + note;
                        
                        document.getElementById('account_detail').value = compiled;
                    }

                    // Tự động phân tích dữ liệu có sẵn đưa vào Helper khi tải trang
                    window.addEventListener('DOMContentLoaded', function() {
                        var detail = document.getElementById('account_detail').value;
                        var lines = detail.split('\n');
                        lines.forEach(function(line) {
                            var cleanLine = line.trim();
                            if (cleanLine.indexOf('Tên đăng nhập: ') === 0) {
                                document.getElementById('helper_user').value = cleanLine.replace('Tên đăng nhập: ', '').trim();
                            } else if (cleanLine.indexOf('Mật khẩu: ') === 0) {
                                document.getElementById('helper_pass').value = cleanLine.replace('Mật khẩu: ', '').trim();
                            } else if (cleanLine.indexOf('Hạn dùng/Thông tin: ') === 0) {
                                document.getElementById('helper_info').value = cleanLine.replace('Hạn dùng/Thông tin: ', '').trim();
                            } else if (cleanLine.indexOf('Ghi chú: ') === 0) {
                                document.getElementById('helper_note').value = cleanLine.replace('Ghi chú: ', '').trim();
                            }
                        });
                    });
                </script>
                
                <button type="submit" class="btn btn-primary">Cập nhật tài khoản</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
