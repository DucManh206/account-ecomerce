<?php
require_once __DIR__ . "/../../admin_lib/admin_layout_modules.php";
require_once __DIR__ . "/../../admin_lib/admin_deposit_modules.php";

// Xử lý action
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    if ($action === 'approve') {
        $result = admin_approveDeposit($id, $_SESSION['user_id']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
    } elseif ($action === 'reject') {
        $reason = $_POST['reason'] ?? '';
        $result = admin_rejectDeposit($id, $_SESSION['user_id'], $reason);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
    }
}

// Lấy filter
$statusFilter = $_GET['status'] ?? 'all';
$requests = admin_getDepositRequests($statusFilter === 'all' ? null : $statusFilter);
$stats = admin_getDepositStats();

ob_start();
?>

<div id="alertBox" class="alert d-none mb-3" role="alert"></div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #6E56CF, #4F46E5); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fa-solid fa-list"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="stat-label">Tổng yêu cầu</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #F59E0B, #D97706); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
            <div class="stat-label">Đang chờ duyệt</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #10B981, #059669); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['approved_today']); ?></div>
            <div class="stat-label">Đã duyệt hôm nay</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #38BDF8, #0EA5E9); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fa-solid fa-coins"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['amount_today'], 0, ',', '.'); ?>đ</div>
            <div class="stat-label">Nạp hôm nay</div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="btn-group" role="group">
            <a href="?status=all" class="btn btn-sm <?php echo $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                Tất cả
            </a>
            <a href="?status=pending" class="btn btn-sm <?php echo $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-secondary'; ?>">
                <i class="fa-solid fa-clock me-1"></i>Chờ duyệt
            </a>
            <a href="?status=approved" class="btn btn-sm <?php echo $statusFilter === 'approved' ? 'btn-success' : 'btn-outline-secondary'; ?>">
                <i class="fa-solid fa-check me-1"></i>Đã duyệt
            </a>
            <a href="?status=rejected" class="btn btn-sm <?php echo $statusFilter === 'rejected' ? 'btn-danger' : 'btn-outline-secondary'; ?>">
                <i class="fa-solid fa-times me-1"></i>Từ chối
            </a>
        </div>
    </div>
</div>

<!-- Requests Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-list me-2"></i>Danh sách yêu cầu</h5>
    </div>
    <div class="card-body p-0">
        <?php if (count($requests) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Người dùng</th>
                            <th>Số tiền</th>
                            <th>Ngân hàng</th>
                            <th>Ngày yêu cầu</th>
                            <th>Trạng thái</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#<?php echo str_pad($req['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6E56CF,#38BDF8);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.7rem;">
                                            <?php echo strtoupper(substr($req['username'], 0, 1)); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($req['username']); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-success fw-bold"><?php echo number_format($req['amount']); ?>đ</span>
                                </td>
                                <td><?php echo htmlspecialchars($req['bank_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($req['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    $statusClass = [
                                        'pending' => 'bg-warning text-dark',
                                        'approved' => 'bg-success',
                                        'rejected' => 'bg-danger'
                                    ];
                                    $statusText = [
                                        'pending' => 'Chờ duyệt',
                                        'approved' => 'Đã duyệt',
                                        'rejected' => 'Từ chối'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $statusClass[$req['status']]; ?>">
                                        <?php echo $statusText[$req['status']]; ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if ($req['status'] === 'pending'): ?>
                                        <div class="btn-group">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Duyệt yêu cầu nạp tiền này?');">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" title="Duyệt">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="openRejectModal(<?php echo $req['id']; ?>)" title="Từ chối">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fa-solid fa-inbox fa-3x mb-3 opacity-25"></i>
                <h5>Không có yêu cầu nạp tiền nào</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fa-solid fa-times-circle me-2"></i>Từ chối yêu cầu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="id" id="rejectId">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Lý do từ chối (tùy chọn)</label>
                        <textarea name="reason" id="rejectReason" class="form-control" rows="3" placeholder="Nhập lý do từ chối..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xác nhận từ chối</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let rejectModal;

document.addEventListener('DOMContentLoaded', function() {
    rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
});

function openRejectModal(id) {
    document.getElementById('rejectId').value = id;
    document.getElementById('rejectReason').value = '';
    rejectModal.show();
}

function showAlert(type, message) {
    const box = document.getElementById('alertBox');
    box.className = 'alert alert-' + type + ' alert-dismissible fade show';
    box.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    box.classList.remove('d-none');
    setTimeout(() => { if (!type.includes('danger')) box.classList.add('d-none'); }, 5000);
}
</script>

<?php
$content = ob_get_clean();
admin_renderLayout('Quản lý Nạp tiền', 'deposits');
?>
