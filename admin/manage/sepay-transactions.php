<?php
/**
 * SePay Transactions Log - Admin Page
 * Xem lịch sử giao dịch SePay
 */

require_once __DIR__ . "/../lib/admin_layout_modules.php";
require_once __DIR__ . "/../../lib/sepay_modules.php";

$stats = sepay_getStats();
$transactions = sepay_getProcessedTransactions(50);

ob_start();
?>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="nx-stat-card" style="background: linear-gradient(135deg, #6E56CF, #4F46E5); color: white;">
            <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div class="nx-stat-value"><?php echo number_format($stats['total_transactions']); ?></div>
            <div class="nx-stat-label">Tổng giao dịch</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="nx-stat-card" style="background: linear-gradient(135deg, #F59E0B, #D97706); color: white;">
            <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="nx-stat-value"><?php echo number_format($stats['pending_count']); ?></div>
            <div class="nx-stat-label">Đang chờ xử lý</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="nx-stat-card" style="background: linear-gradient(135deg, #10B981, #059669); color: white;">
            <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            <div class="nx-stat-value"><?php echo number_format($stats['matched_count']); ?></div>
            <div class="nx-stat-label">Đã xử lý</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="nx-stat-card" style="background: linear-gradient(135deg, #38BDF8, #0EA5E9); color: white;">
            <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                <i class="fa-solid fa-coins"></i>
            </div>
            <div class="nx-stat-value"><?php echo number_format($stats['month_amount'], 0, ',', '.'); ?>đ</div>
            <div class="nx-stat-label">Tháng này</div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="nx-card">
    <div class="nx-card-header">
        <h5 class="mb-0"><i class="fa-solid fa-history me-2"></i>Lịch sử giao dịch SePay</h5>
    </div>
    <div class="nx-card-body p-0">
        <?php if (!empty($transactions)): ?>
            <div class="table-responsive">
                <table class="nx-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Mã GD</th>
                            <th>Người dùng</th>
                            <th>Số tiền</th>
                            <th>Nội dung</th>
                            <th>Ngày giao dịch</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#<?php echo htmlspecialchars($tx['sepay_id']); ?></td>
                                <td>
                                    <?php if ($tx['username']): ?>
                                        <a href="users.php?search=<?php echo urlencode($tx['username']); ?>">
                                            <?php echo htmlspecialchars($tx['username']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-success fw-bold">
                                        +<?php echo number_format($tx['amount_in'], 0, ',', '.'); ?>đ
                                    </span>
                                </td>
                                <td style="max-width: 200px;">
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars(mb_substr($tx['transaction_content'] ?? '', 0, 50)); ?>
                                        <?php if (mb_strlen($tx['transaction_content'] ?? '') > 50) echo '...'; ?>
                                    </small>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($tx['transaction_date'])); ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'nx-badge nx-badge-warning',
                                        'matched' => 'nx-badge nx-badge-success',
                                        'failed' => 'nx-badge nx-badge-danger',
                                        'duplicate' => 'nx-badge nx-badge-secondary'
                                    ];
                                    $statusText = [
                                        'pending' => 'Chờ xử lý',
                                        'matched' => 'Đã khớp',
                                        'failed' => 'Thất bại',
                                        'duplicate' => 'Trùng lặp'
                                    ];
                                    ?>
                                    <span class="<?php echo $statusClass[$tx['status']] ?? 'nx-badge nx-badge-secondary'; ?>">
                                        <?php echo $statusText[$tx['status']] ?? $tx['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fa-solid fa-inbox fa-3x mb-3 opacity-25"></i>
                <h5>Chưa có giao dịch SePay nào</h5>
                <p>Kích hoạt SePay trong Cài đặt để bắt đầu nhận giao dịch</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
admin_renderLayout('Giao dịch SePay', 'sepay');
