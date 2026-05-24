<?php
require_once __DIR__ . '/accounts.php';
$categories = getCategories($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price' => trim($_POST['price'] ?? 0),
        'category_id' => trim($_POST['category_id'] ?? ''),
        'image' => trim($_POST['image'] ?? ''),
        'account_detail' => trim($_POST['account_detail'] ?? ''),
        'status' => $_POST['status'] ?? 'available'
    ];

    if ($data['name'] === '' || $data['price'] === '') {
        $error = 'Vui lòng nhập tên tài khoản và giá bán.';
    } else {
        addAccount($pdo, $data);
        header('Location: list.php?success=' . urlencode('Thêm tài khoản thành công.'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm tài khoản - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <h1>Thêm tài khoản mới</h1>
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
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Giá bán (đ)</label>
                        <input type="number" id="price" name="price" min="0" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Danh mục</label>
                        <select id="category_id" name="category_id">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Trạng thái</label>
                        <select id="status" name="status">
                            <option value="available">Đang bán</option>
                            <option value="sold">Đã bán</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image">Ảnh sản phẩm (URL)</label>
                    <input type="text" id="image" name="image" placeholder="https://...">
                </div>
                
                <div class="form-group">
                    <label for="description">Mô tả</label>
                    <textarea id="description" name="description" rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="account_detail">Thông tin tài khoản đăng nhập (Bảo mật - chỉ hiện sau khi mua)</label>
                    <textarea id="account_detail" name="account_detail" rows="4" placeholder="Tên đăng nhập, mật khẩu, email, ghi chú..." required></textarea>
                </div>
                
                <!-- Bảng tự động điền trực quan -->
                <div style="background-color: #0d0d0d; border: 1px solid var(--border-color); padding: 20px; margin-top: -10px; margin-bottom: 10px;">
                    <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #a78bfa; margin-bottom: 12px; letter-spacing: 0.5px;">Bảng điền thông tin trực quan (Tự động cập nhật phía trên)</div>
                    <div class="form-row" style="margin-bottom: 12px;">
                        <div class="form-group">
                            <label style="font-size: 0.8rem; color: var(--text-muted);">Tên đăng nhập / Email bán</label>
                            <input type="text" id="helper_user" placeholder="Ví dụ: netflix_acc1@gmail.com" oninput="updateAccountDetailTextarea()">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.8rem; color: var(--text-muted);">Mật khẩu</label>
                            <input type="text" id="helper_pass" placeholder="Ví dụ: abc123" oninput="updateAccountDetailTextarea()">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label style="font-size: 0.8rem; color: var(--text-muted);">Hạn sử dụng / Thông tin phụ</label>
                            <input type="text" id="helper_info" placeholder="Ví dụ: Netflix 4K - 6 tháng" oninput="updateAccountDetailTextarea()">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.8rem; color: var(--text-muted);">Ghi chú khác</label>
                            <input type="text" id="helper_note" placeholder="Ví dụ: Đã link SMS bảo vệ" oninput="updateAccountDetailTextarea()">
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
                </script>
                
                <button type="submit" class="btn btn-primary">Lưu tài khoản</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
