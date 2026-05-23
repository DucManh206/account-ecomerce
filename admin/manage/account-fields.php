<?php
require_once __DIR__ . "/../../admin_lib/admin_layout_modules.php";
require_once __DIR__ . "/../../admin_lib/admin_account_field_modules.php";

$success = false;
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['field_action'] ?? '';

    if ($action === 'create') {
        $result = fieldType_create($_POST);
        $success = $result['success'];
        $errorMsg = $result['message'];
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $result = fieldType_update($id, $_POST);
        $success = $result['success'];
        $errorMsg = $result['message'];
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $result = fieldType_delete($id);
        $success = $result['success'];
        $errorMsg = $result['message'];
    }
}

$fields = fieldType_getAll();

ob_start();
?>

<?php if ($success): ?>
<div class="nx-alert nx-alert-success mb-3" id="successAlert">
    <i class="fa-solid fa-check-circle me-1"></i> <?php echo htmlspecialchars($errorMsg ?: 'Thành công!'); ?>
</div>
<?php elseif ($errorMsg): ?>
<div class="nx-alert nx-alert-danger mb-3" id="errorAlert">
    <i class="fa-solid fa-xmark-circle me-1"></i> <?php echo htmlspecialchars($errorMsg); ?>
</div>
<?php endif; ?>

<!-- Add Form -->
<div class="nx-add-form">
    <div class="nx-add-form-title">
        <i class="fa-solid fa-plus-circle" style="color:#6E56CF;"></i> Thêm loại field mới
    </div>
    <form method="POST" class="row g-3 align-items-end">
        <input type="hidden" name="field_action" value="create">
        <div class="col-md-2">
            <label class="nx-label small fw-bold">Key <span class="text-danger">*</span></label>
            <input type="text" class="nx-input font-mono" name="key" required placeholder="VD: pin" pattern="[a-z0-9_]+" style="font-size:0.82rem;">
            <div class="form-text" style="font-size:0.7rem;">a-z, 0-9, _</div>
        </div>
        <div class="col-md-2">
            <label class="nx-label small fw-bold">Nhãn hiển thị <span class="text-danger">*</span></label>
            <input type="text" class="nx-input" name="label" required placeholder="VD: Mã PIN">
        </div>
        <div class="col-md-3">
            <label class="nx-label small fw-bold">Icon</label>
            <div class="nx-icon-picker" id="iconGridAdd">
                <?php
                $iconOptions = [
                    'fa-user','fa-lock','fa-envelope','fa-key','fa-cookie-bite',
                    'fa-fingerprint','fa-hashtag','fa-shield-halved','fa-sticky-note',
                    'fa-link','fa-barcode','fa-barcode','fa-gamepad','fa-play',
                    'fa-spotify','fa-youtube','fa-netflix','fa-robot','fa-globe',
                    'fa-mobile','fa-desktop','fa-cloud','fa-wifi','fa-credit-card',
                ];
                foreach ($iconOptions as $ic): ?>
                    <label data-icon="<?php echo $ic; ?>" onclick="selectIconAdd(this)" class="<?php echo $ic === 'fa-key' ? 'selected' : ''; ?>">
                        <i class="fa-solid <?php echo $ic; ?>"></i>
                        <input type="radio" name="icon_class" value="<?php echo $ic; ?>" <?php echo $ic === 'fa-key' ? 'checked' : ''; ?>>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-3">
            <label class="nx-label small fw-bold">Placeholder</label>
            <input type="text" class="nx-input" name="placeholder" placeholder="VD: 123456">
        </div>
        <div class="col-md-1">
            <label class="nx-label small fw-bold">Thứ tự</label>
            <input type="number" class="nx-input" name="sort_order" value="99" min="1">
        </div>
        <div class="col-md-1">
            <button type="submit" class="nx-btn nx-btn-primary w-100"><i class="fa-solid fa-plus"></i></button>
        </div>
    </form>
</div>

<!-- List -->
<div class="row g-3">
<?php foreach ($fields as $f): ?>
    <div class="col-md-6 col-lg-4">
        <div class="nx-field-card">
            <div class="nx-field-icon">
                <i class="fa-solid <?php echo htmlspecialchars($f['icon_class']); ?>"></i>
            </div>
            <div class="nx-field-info">
                <div class="nx-field-key"><?php echo htmlspecialchars($f['key']); ?></div>
                <div class="nx-field-label"><?php echo htmlspecialchars($f['label']); ?></div>
                <?php if ($f['placeholder']): ?>
                <div class="nx-field-placeholder"><i class="fa-solid fa-italic"></i> <?php echo htmlspecialchars($f['placeholder']); ?></div>
                <?php endif; ?>
            </div>
            <?php if ($f['is_default']): ?>
                <span class="nx-badge nx-badge-warning">Mặc định</span>
            <?php else: ?>
                <form method="POST" onsubmit="return confirm('Xóa field \'<?php echo htmlspecialchars($f['label']); ?>\'?')">
                    <input type="hidden" name="field_action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                    <button type="submit" class="nx-btn nx-btn-sm nx-btn-danger">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<script>
function selectIconAdd(el) {
    document.querySelectorAll('#iconGridAdd label').forEach(l => l.classList.remove('selected'));
    el.classList.add('selected');
}
</script>

<?php
$content = ob_get_clean();
admin_renderLayout('Quản lý loại field', 'settings');
