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
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Account Shop</h2>
            <span class="sidebar-role">Admin</span>
        </div>
        <nav class="sidebar-nav">
            <a href="../../dashboard.php" class="nav-item">
                <span class="nav-icon">&#x1F3E0;</span>
                <span>Dashboard</span>
            </a>
            <a href="list.php" class="nav-item active">
                <span class="nav-icon">&#x1F4CB;</span>
                <span>Quản lý tài khoản</span>
            </a>
            <a href="../categories/list.php" class="nav-item">
                <span class="nav-icon">&#x1F4C1;</span>
                <span>Danh mục</span>
            </a>
            <hr class="nav-divider">
            <a href="../../../index.php" target="_blank" class="nav-item">
                <span class="nav-icon">&#x1F30D;</span>
                <span>Xem trang chủ</span>
            </a>
            <a href="../../logout.php" class="nav-item nav-logout">
                <span class="nav-icon">&#x1F6AA;</span>
                <span>Đăng xuất</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <p>Xin chào, <strong><?= htmlspecialchars($_SESSION['admin_fullname']) ?></strong></p>
        </div>
    </aside>
    
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
                        <label for="name">Tên tài khoản / Sản phẩm</label>
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
                    <textarea id="account_detail" name="account_detail" rows="6" placeholder="Tên đăng nhập, mật khẩu, email, ghi chú bàn giao..."><?= htmlspecialchars($account['account_detail'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Cập nhật tài khoản</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
