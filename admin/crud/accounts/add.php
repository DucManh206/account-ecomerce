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
    if ($data['name'] === '' || $data['price'] === '') $error = 'Vui lòng nhập tên tài khoản và giá bán.';
    else {
        addAccount($pdo, $data);
        header('Location: list.php?success=' . urlencode('Thêm tài khoản thành công'));
        exit;
    }
}
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Thêm tài khoản</title><link rel="stylesheet" href="../../../assets/admin/css/admin.css"></head><body>
<div class="admin-layout"><aside class="sidebar"><div class="sidebar-header"><h2>Account Shop</h2><span class="sidebar-role">Admin</span></div><nav class="sidebar-nav"><a href="../../dashboard.php" class="nav-item">Dashboard</a><a href="list.php" class="nav-item active">Quản lý tài khoản</a><a href="../categories/list.php" class="nav-item">Danh mục</a><hr class="nav-divider"><a href="../../logout.php" class="nav-item nav-logout">Đăng xuất</a></nav></aside>
<main class="main-content"><header class="topbar"><h1>Thêm tài khoản</h1><a href="list.php" class="btn btn-secondary">Quay lại</a></header><div class="content-body">
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="POST" class="form-card">
<div class="form-row"><div class="form-group"><label>Tên tài khoản</label><input type="text" name="name" required></div><div class="form-group"><label>Giá bán</label><input type="number" name="price" min="0" required></div></div>
<div class="form-row"><div class="form-group"><label>Danh mục</label><select name="category_id"><option value="">-- Chọn danh mục --</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Trạng thái</label><select name="status"><option value="available">Đang bán</option><option value="sold">Đã bán</option></select></div></div>
<div class="form-group"><label>Ảnh sản phẩm (URL)</label><input type="text" name="image" placeholder="https://..."></div>
<div class="form-group"><label>Mô tả</label><textarea name="description" rows="5"></textarea></div>
<div class="form-group"><label>Thông tin tài khoản</label><textarea name="account_detail" rows="6" placeholder="Tên đăng nhập, mật khẩu, email, ghi chú..."></textarea></div>
<button type="submit" class="btn btn-primary">Lưu tài khoản</button>
</form></div></main></div></body></html>
