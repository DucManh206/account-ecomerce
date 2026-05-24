<?php
require_once __DIR__ . '/categories.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? '')
    ];

    if ($data['name'] === '') {
        $error = 'Vui lòng nhập tên danh mục.';
    } else {
        if (addCategory($pdo, $data)) {
            header('Location: list.php?success=' . urlencode('Thêm danh mục thành công.'));
            exit;
        } else {
            $error = 'Có lỗi xảy ra khi thêm danh mục.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm danh mục - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    
    <main class="main-content">
        <header class="topbar">
            <h1>Thêm danh mục mới</h1>
            <a href="list.php" class="btn btn-secondary">Quay lại</a>
        </header>
        
        <div class="content-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="name">Tên danh mục</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Mô tả danh mục</label>
                    <textarea id="description" name="description" rows="5"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Thêm danh mục</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
