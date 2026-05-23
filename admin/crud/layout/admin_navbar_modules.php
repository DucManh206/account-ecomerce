<?php
require_once __DIR__ . "/admin_verifier_modules.php";
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="../dashboard.php">Hệ thống Quản Trị</a>
        <div class="d-flex align-items-center">
            <span class="text-light me-3">
                Xin chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
            </span>
            <a href="/admin/logout.php" class="btn btn-outline-danger btn-sm">Đăng xuất</a>
        </div>
    </div>
</nav>
