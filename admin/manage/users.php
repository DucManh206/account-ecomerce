<?php
require_once __DIR__ . "/../../admin_lib/admin_layout_modules.php";
require_once __DIR__ . "/../../admin_lib/admin_user_modules.php";

$users = admin_getUsers();
$currentUsername = $_SESSION['username'] ?? '';
ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý Người dùng</h1>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            <?php echo count($users); ?> người dùng —
            <?php echo admin_countUsersByRole(1); ?> admin —
            <?php echo admin_countUsersByRole(0); ?> client
        </p>
    </div>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fa-solid fa-user-plus me-1"></i> Thêm người dùng
    </button>
</div>

<div id="alertBox" class="alert d-none mb-3" role="alert"></div>

<div class="card bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="usersTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width:60px;">ID</th>
                        <th>Tên đăng nhập</th>
                        <th style="width:130px;">Vai trò</th>
                        <th style="width:160px;">Số dư</th>
                        <th style="width:120px;">ID</th>
                        <th class="text-end pe-4" style="width:180px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $u):
                            $isAdmin = intval($u['role']) === 1;
                            $isCurrent = $u['username'] === $currentUsername;
                        ?>
                        <tr id="row-<?php echo $u['id']; ?>">
                            <td class="ps-4 fw-bold text-muted">#<?php echo str_pad($u['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,<?php echo $isAdmin ? '#EF4444,#F97316' : '#6E56CF,#38BDF8'; ?>);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.75rem;flex-shrink:0;">
                                        <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:0.9rem;">
                                            <?php echo htmlspecialchars($u['username']); ?>
                                            <?php if ($isCurrent): ?>
                                                <span class="badge bg-primary ms-1" style="font-size:0.6rem;">Bạn</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:0.7rem;color:#9ca3af;">ID: <?php echo $u['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($isAdmin): ?>
                                    <span class="badge bg-danger"><i class="fa-solid fa-shield-halved me-1"></i>Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fa-regular fa-user me-1"></i>Client</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-success fw-bold"><?php echo number_format($u['balance'], 0, ',', '.'); ?>đ</span>
                            </td>
                            <td class="text-muted" style="font-size:0.82rem;">
                                #<?php echo $u['id']; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if (!$isCurrent): ?>
                                <button class="btn btn-sm btn-outline-success" onclick="openTopupModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>', <?php echo $u['balance']; ?>)" title="Nạp tiền">
                                    <i class="fa-solid fa-wallet"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-primary" onclick='editUser(<?php echo json_encode($u); ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <?php if (!$isCurrent): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['username'])); ?>')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-users fa-2x mb-2 d-block opacity-25"></i>
                            Chưa có người dùng nào
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fa-solid fa-user-plus me-2"></i>Thêm người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm" onsubmit="saveUser(event)">
                <input type="hidden" id="editId" name="id" value="">
                <input type="hidden" name="action" id="formAction" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fUsername" name="username" required placeholder="VD: nguyenvana" minlength="3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="fPassword" name="password" placeholder="Ít nhất 6 ký tự">
                        <div class="form-text" id="passwordHint">Bắt buộc khi tạo mới</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Vai trò</label>
                            <select class="form-select" id="fRole" name="role">
                                <option value="0">Client</option>
                                <option value="1">Admin</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Số dư ban đầu</label>
                            <input type="number" class="form-control" id="fBalance" name="balance" min="0" value="0" placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fa-solid fa-check me-1"></i>Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="topupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-success"><i class="fa-solid fa-wallet me-2"></i>Nạp tiền</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="topupForm" onsubmit="submitTopup(event)">
                <input type="hidden" name="action" value="topup">
                <input type="hidden" id="topupUserId" name="id" value="">
                <div class="modal-body">
                    <p>Người dùng: <strong id="topupUsername"></strong></p>
                    <p>Số dư hiện tại: <strong class="text-success" id="topupCurrentBalance"></strong></p>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Số tiền nạp (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="topupAmount" name="amount" required min="1000" step="1000" placeholder="VD: 100000">
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ([10000, 50000, 100000, 200000, 500000, 1000000] as $amt): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('topupAmount').value=<?php echo $amt; ?>">
                            <?php echo number_format($amt, 0, ',', '.'); ?>đ
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-check me-1"></i>Xác nhận nạp tiền
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc muốn xóa người dùng <strong id="delUserName"></strong>?
                <br><span class="text-muted" style="font-size:0.85rem;">Hành động này không thể hoàn tác.</span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">
                    <i class="fa-solid fa-trash me-1"></i>Xóa
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let userModal, topupModal, deleteModal;
let deleteTarget = null;

document.addEventListener('DOMContentLoaded', function() {
    userModal = new bootstrap.Modal(document.getElementById('userModal'));
    topupModal = new bootstrap.Modal(document.getElementById('topupModal'));
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
});

function showAlert(type, message) {
    const box = document.getElementById('alertBox');
    box.className = 'alert alert-' + type + ' alert-dismissible fade show';
    box.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    box.classList.remove('d-none');
    setTimeout(() => { if (!type.includes('danger')) box.classList.add('d-none'); }, 5000);
}

function openAddModal() {
    document.getElementById('userForm').reset();
    document.getElementById('editId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('fPassword').required = true;
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-user-plus me-2"></i>Thêm người dùng';
    document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Tạo';
    document.getElementById('passwordHint').textContent = 'Bắt buộc khi tạo mới';
    document.getElementById('fUsername').readOnly = false;
    userModal.show();
}

function editUser(data) {
    document.getElementById('userForm').reset();
    document.getElementById('editId').value = data.id;
    document.getElementById('formAction').value = 'update';
    document.getElementById('fPassword').required = false;
    document.getElementById('fUsername').value = data.username || '';
    document.getElementById('fRole').value = data.role || 0;
    document.getElementById('fBalance').value = data.balance || 0;
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Sửa người dùng';
    document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Cập nhật';
    document.getElementById('passwordHint').textContent = 'Để trống nếu không đổi mật khẩu';
    document.getElementById('fUsername').readOnly = true;
    userModal.show();
}

function saveUser(e) {
    e.preventDefault();
    const form = document.getElementById('userForm');
    const formData = new FormData(form);

    fetch('../admin_lib/admin_user_modules.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            userModal.hide();
            showAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + data.message);
            location.reload();
        } else {
            showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
        }
    })
    .catch(() => {
        showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>Đã xảy ra lỗi');
    });
}

function openTopupModal(id, username, balance) {
    document.getElementById('topupUserId').value = id;
    document.getElementById('topupUsername').textContent = username;
    document.getElementById('topupCurrentBalance').textContent = new Intl.NumberFormat('vi-VN').format(balance) + 'đ';
    document.getElementById('topupAmount').value = '';
    topupModal.show();
}

function submitTopup(e) {
    e.preventDefault();
    const form = document.getElementById('topupForm');
    const formData = new FormData(form);

    fetch('../admin_lib/admin_user_modules.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            topupModal.hide();
            showAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + data.message);
            location.reload();
        } else {
            showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
        }
    })
    .catch(() => {
        showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>Đã xảy ra lỗi');
    });
}

function deleteUser(id, name) {
    deleteTarget = id;
    document.getElementById('delUserName').textContent = '"' + name + '"';
    deleteModal.show();
}

function confirmDelete() {
    if (!deleteTarget) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', deleteTarget);

    fetch('../admin_lib/admin_user_modules.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        deleteModal.hide();
        if (data.success) {
            const row = document.getElementById('row-' + deleteTarget);
            if (row) row.remove();
            showAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + data.message);
        } else {
            showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
        }
        deleteTarget = null;
    })
    .catch(() => {
        deleteModal.hide();
        showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>Đã xảy ra lỗi');
    });
}
</script>
<?php
$content = ob_get_clean();
admin_renderLayout('Quản lý Người dùng', 'users');
?>
