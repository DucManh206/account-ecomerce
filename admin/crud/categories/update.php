<?php
require_once __DIR__ . '/categories.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: list.php?error=' . urlencode('ID danh mục không hợp lệ.'));
    exit;
}

$category = getCategoryById($pdo, $id);
if (!$category) {
    header('Location: list.php?error=' . urlencode('Danh mục không tồn tại.'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? '')
    ];

    if ($data['name'] === '') {
        $error = 'Vui lòng nhập tên danh mục.';
    } else {
        if (updateCategory($pdo, $id, $data)) {
            header('Location: list.php?success=' . urlencode('Cập nhật danh mục thành công.'));
            exit;
        } else {
            $error = 'Có lỗi xảy ra khi cập nhật danh mục.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa danh mục - <?= SITE_NAME ?></title>
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
            <a href="../accounts/list.php" class="nav-item">
                <span class="nav-icon">&#x1F4CB;</span>
                <span>Quản lý tài khoản</span>
            </a>
            <a href="list.php" class="nav-item active">
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
            <h1>Chỉnh sửa danh mục (ID: #<?= htmlspecialchars($category['id']) ?>)</h1>
            <a href="list.php" class="btn btn-secondary">Quay lại</a>
        </header>
        
        <div class="content-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="name">Tên danh mục</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Mô tả danh mục</label>
                    <textarea id="description" name="description" rows="5"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Cập nhật danh mục</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
