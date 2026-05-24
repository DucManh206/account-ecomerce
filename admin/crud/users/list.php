<?php
require_once __DIR__ . "/../../../admin/crud/layout/admin_layout_modules.php";
require_once __DIR__ . "/admin_user_modules.php";

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
    <button class="nx-btn nx-btn-primary" onclick="openAddModal()">
        <i class="fa-solid fa-user-plus me-1"></i> Thêm người dùng
    </button>
</div>

<div id="alertBox" class="nx-alert d-none mb-3"></div>

<div class="nx-card">
    <div class="nx-card-body p-0">
        <div class="table-responsive">
            <table class="nx-table" id="usersTable">
                <thead>
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
                                                <span class="nx-badge nx-badge-primary ms-1" style="font-size:0.6rem;">Bạn</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:0.7rem;color:#9ca3af;">ID: <?php echo $u['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($isAdmin): ?>
                                    <span class="nx-badge nx-badge-danger"><i class="fa-solid fa-shield-halved me-1"></i>Admin</span>
                                <?php else: ?>
                                    <span class="nx-badge nx-badge-muted"><i class="fa-regular fa-user me-1"></i>Client</span>
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
                                <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick="openTopupModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>', <?php echo $u['balance']; ?>)" title="Nạp tiền">
                                    <i class="fa-solid fa-wallet"></i>
                                </button>
                                <?php endif; ?>
                                <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick='editUser(<?php echo json_encode($u); ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <?php if (!$isCurrent): ?>
                                <button class="nx-btn nx-btn-sm nx-btn-danger" onclick="deleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['username'])); ?>')">
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

<div class="nx-modal" id="userModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" id="modalTitle"><i class="fa-solid fa-user-plus me-2"></i>Thêm người dùng</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('userModal')"></button>
        </div>
        <form id="userForm" onsubmit="saveUser(event)">
            <input type="hidden" id="editId" name="id" value="">
            <input type="hidden" name="action" id="formAction" value="create">
            <div class="nx-modal-body">
                <div class="nx-form-group">
                    <label class="nx-label">Tên đăng nhập <span class="text-danger">*</span></label>
                    <input type="text" class="nx-input" id="fUsername" name="username" required placeholder="VD: nguyenvana" minlength="3">
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Mật khẩu <span class="text-danger">*</span></label>
                    <input type="password" class="nx-input" id="fPassword" name="password" placeholder="Ít nhất 6 ký tự">
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;" id="passwordHint">Bắt buộc khi tạo mới</div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="nx-label">Vai trò</label>
                        <select class="nx-select" id="fRole" name="role">
                            <option value="0">Client</option>
                            <option value="1">Admin</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="nx-label">Số dư ban đầu</label>
                        <input type="number" class="nx-input" id="fBalance" name="balance" min="0" value="0" placeholder="0">
                    </div>
                </div>
            </div>
            <div class="nx-modal-footer">
                <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('userModal')">Hủy</button>
                <button type="submit" class="nx-btn nx-btn-primary" id="submitBtn">
                    <i class="fa-solid fa-check me-1"></i>Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<div class="nx-modal" id="topupModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" style="color: var(--success);"><i class="fa-solid fa-wallet me-2"></i>Nạp tiền</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('topupModal')"></button>
        </div>
        <form id="topupForm" onsubmit="submitTopup(event)">
            <input type="hidden" name="action" value="topup">
            <input type="hidden" id="topupUserId" name="id" value="">
            <div class="nx-modal-body">
                <p>Người dùng: <strong id="topupUsername"></strong></p>
                <p>Số dư hiện tại: <strong class="text-success" id="topupCurrentBalance"></strong></p>
                <hr class="nx-divider">
                <div class="nx-form-group">
                    <label class="nx-label">Số tiền nạp (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" class="nx-input" id="topupAmount" name="amount" required min="1000" step="1000" placeholder="VD: 100000">
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ([10000, 50000, 100000, 200000, 500000, 1000000] as $amt): ?>
                    <button type="button" class="nx-btn nx-btn-sm nx-btn-secondary" onclick="document.getElementById('topupAmount').value=<?php echo $amt; ?>">
                        <?php echo number_format($amt, 0, ',', '.'); ?>đ
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="nx-modal-footer">
                <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('topupModal')">Hủy</button>
                <button type="submit" class="nx-btn nx-btn-success">
                    <i class="fa-solid fa-check me-1"></i>Xác nhận nạp tiền
                </button>
            </div>
        </form>
    </div>
</div>

<div class="nx-modal" id="deleteModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" style="color: var(--danger);"><i class="fa-solid fa-triangle-exclamation me-2"></i>Xác nhận xóa</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('deleteModal')"></button>
        </div>
        <div class="nx-modal-body">
            Bạn có chắc muốn xóa người dùng <strong id="delUserName"></strong>?
            <br><span class="text-muted" style="font-size:0.85rem;">Hành động này không thể hoàn tác.</span>
        </div>
        <div class="nx-modal-footer">
            <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('deleteModal')">Hủy</button>
            <button type="button" class="nx-btn nx-btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">
                <i class="fa-solid fa-trash me-1"></i>Xóa
            </button>
        </div>
    </div>
</div>

<script>
let deleteTarget = null;

function showModal(id) {
    document.getElementById(id).classList.add('show');
}

function hideModal(id) {
    document.getElementById(id).classList.remove('show');
}

function showAlert(type, message) {
    const box = document.getElementById('alertBox');
    box.className = 'nx-alert nx-alert-' + type;
    box.innerHTML = message;
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
    showModal('userModal');
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
    showModal('userModal');
}

function saveUser(e) {
    e.preventDefault();
    const form = document.getElementById('userForm');
    const formData = new FormData(form);

    fetch('../../lib/admin_user_modules.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            hideModal('userModal');
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
    showModal('topupModal');
}

function submitTopup(e) {
    e.preventDefault();
    const form = document.getElementById('topupForm');
    const formData = new FormData(form);

    fetch('../../lib/admin_user_modules.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            hideModal('topupModal');
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
    showModal('deleteModal');
}

function confirmDelete() {
    if (!deleteTarget) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', deleteTarget);

    fetch('../../lib/admin_user_modules.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        hideModal('deleteModal');
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
        hideModal('deleteModal');
        showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>Đã xảy ra lỗi');
    });
}
</script>
<?php
$content = ob_get_clean();
admin_renderLayout('Quản lý Người dùng', 'users');
?>
