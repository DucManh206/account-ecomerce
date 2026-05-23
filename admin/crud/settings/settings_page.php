<?php
require_once __DIR__ . "/../crud/layout/admin_layout_modules.php";

ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Cài đặt</h1>
        <p class="text-muted mb-0" style="font-size:0.85rem;">Quản lý cấu hình hệ thống</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-gear me-2"></i>Cấu hình hệ thống</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fa-solid fa-info-circle me-2"></i>
            Tính năng cài đặt đang được phát triển. Các tùy chọn cấu hình sẽ được cập nhật sớm.
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold mb-3">Cấu hình chung</h6>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tên cửa hàng</label>
                    <input type="text" class="form-control" value="NEXUS STORE" disabled>
                    <div class="form-text">Tính năng sẽ sớm có</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Email liên hệ</label>
                    <input type="email" class="form-control" placeholder="admin@nexus.store" disabled>
                    <div class="form-text">Tính năng sẽ sớm có</div>
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold mb-3">Cấu hình thanh toán</h6>
                <div class="mb-3">
                    <label class="form-label fw-bold">Phí giao dịch (%)</label>
                    <input type="number" class="form-control" value="0" disabled>
                    <div class="form-text">Tính năng sẽ sớm có</div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" disabled>
                        <label class="form-check-label fw-bold">Bật thông báo email</label>
                    </div>
                    <div class="form-text">Tính năng sẽ sớm có</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
admin_renderLayout('Cài đặt', 'settings');
?>
