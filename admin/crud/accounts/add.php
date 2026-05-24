<?php
require_once __DIR__ . '/accounts.php';
$categories = getCategories($pdo);
$error = '';
$success = '';

// Handle AJAX Save Template
if (isset($_GET['action']) && $_GET['action'] === 'save_template') {
    header('Content-Type: application/json');
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'price' => trim($_POST['price'] ?? 0),
        'category_id' => trim($_POST['category_id'] ?? ''),
        'image' => trim($_POST['image'] ?? ''),
        'description' => trim($_POST['description'] ?? '')
    ];
    if ($data['name'] === '') {
        echo json_encode(['success' => false, 'error' => 'Ten mau khong duoc de trong']);
        exit;
    }
    $res = addTemplate($pdo, $data);
    echo json_encode(['success' => (bool)$res]);
    exit;
}

// Handle AJAX Delete Template
if (isset($_GET['action']) && $_GET['action'] === 'delete_template' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $res = deleteTemplate($pdo, (int)$_GET['id']);
    echo json_encode(['success' => (bool)$res]);
    exit;
}

// Lay danh sach mau tu DB
$customTemplates = getTemplates($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Xu ly anh upload hoac URL
    $imagePath = trim($_POST['image'] ?? '');
    
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_file']['tmp_name'];
        $fileName = $_FILES['image_file']['name'];
        $fileSize = $_FILES['image_file']['size'];
        $fileType = $_FILES['image_file']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            // Tao thu muc uploads neu chua co
            $uploadFileDir = __DIR__ . '/../../../assets/images/uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $imagePath = 'assets/images/uploads/' . $newFileName;
            } else {
                $error = 'Co loi xay ra khi di chuyen file upload.';
            }
        } else {
            $error = 'Dinh dang file anh khong hop le. Chi chap nhan JPG, JPEG, PNG, WEBP, GIF.';
        }
    }

    if (empty($error)) {
        $importMode = $_POST['import_mode'] ?? 'single';
        $commonData = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price' => trim($_POST['price'] ?? 0),
            'category_id' => trim($_POST['category_id'] ?? ''),
            'image' => $imagePath,
            'status' => $_POST['status'] ?? 'available'
        ];

        if ($commonData['name'] === '' || $commonData['price'] === '') {
            $error = 'Vui long nhap ten tai khoan va gia ban.';
        } else {
            if ($importMode === 'bulk') {
                // Nhap hang loat (Bulk Import)
                $bulkText = trim($_POST['bulk_accounts'] ?? '');
                $separator = $_POST['bulk_separator'] ?? '|';
                
                if (empty($bulkText)) {
                    $error = 'Vui long nhap danh sach tai khoan.';
                } else {
                    $lines = explode("\n", $bulkText);
                    $addedCount = 0;
                    
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;
                        
                        // Parse theo separator
                        $parts = explode($separator, $line);
                        $user = trim($parts[0] ?? '');
                        $pass = trim($parts[1] ?? '');
                        $extra = trim($parts[2] ?? '');
                        
                        if ($user === '') continue;
                        
                        $detail = "Ten dang nhap: " . $user . "\n";
                        if ($pass !== '') $detail .= "Mat khau: " . $pass . "\n";
                        if ($extra !== '') $detail .= "Ghi chu/Thong tin: " . $extra;
                        
                        $accData = $commonData;
                        $accData['account_detail'] = $detail;
                        
                        addAccount($pdo, $accData);
                        $addedCount++;
                    }
                    
                    if ($addedCount > 0) {
                        header('Location: list.php?success=' . urlencode('Da nhap hang loat thanh cong ' . $addedCount . ' tai khoan.'));
                        exit;
                    } else {
                        $error = 'Khong co tai khoan hop le nao duoc nhap.';
                    }
                }
            } else {
                // Them mot tai khoan duy nhat
                $commonData['account_detail'] = trim($_POST['account_detail'] ?? '');
                if ($commonData['account_detail'] === '') {
                    $error = 'Vui long nhap thong tin dang nhap tai khoan.';
                } else {
                    addAccount($pdo, $commonData);
                    header('Location: list.php?success=' . urlencode('Them tai khoan thanh cong.'));
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm tài khoản - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
    <style>
        .mode-selector {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        .mode-option {
            flex: 1;
            padding: 16px;
            background: #121212;
            border: 1px solid var(--border-color);
            cursor: pointer;
            text-align: center;
            font-weight: 600;
            transition: var(--transition);
        }
        .mode-option.active {
            border-color: #ffffff;
            background: #ffffff;
            color: #0a0a0a;
        }
        
        .template-section {
            background: #121212;
            border: 1px solid var(--border-color);
            padding: 20px;
            margin-bottom: 24px;
        }
        .template-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #a3a3a3;
            margin-bottom: 12px;
        }
        .template-subtitle {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 16px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .template-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-template {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-color);
            color: #ffffff;
            padding: 8px 16px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-template:hover {
            border-color: #ffffff;
            background: rgba(255,255,255,0.08);
        }

        .btn-template-custom-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 0;
            border: 1px solid var(--border-color);
            background: rgba(255,255,255,0.02);
            transition: var(--transition);
        }
        .btn-template-custom-wrapper:hover {
            border-color: #a78bfa;
        }
        .btn-template-custom-wrapper .btn-template {
            border: none;
            background: transparent;
            color: #a78bfa;
            padding-right: 8px;
        }
        .btn-template-delete {
            background: transparent;
            border: none;
            border-left: 1px solid var(--border-color);
            color: var(--danger);
            cursor: pointer;
            font-weight: 700;
            padding: 8px 12px;
            font-size: 0.8rem;
            transition: var(--transition);
        }
        .btn-template-delete:hover {
            color: #ffffff;
            background: var(--danger);
        }

        .upload-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .image-preview-container {
            border: 1px dashed var(--border-color);
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.01);
            min-height: 120px;
        }
        .image-preview {
            max-height: 100px;
            max-width: 100%;
            display: none;
        }
        .preview-placeholder {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <h1>Thêm tài khoản mới</h1>
            <a href="list.php" class="btn btn-secondary">Quay lại</a>
        </header>
        
        <div class="content-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Template Quick Fill -->
            <div class="template-section">
                <div class="template-title">Nhập nhanh theo mẫu</div>
                <div class="template-subtitle">Mẫu mặc định hệ thống</div>
                <div class="template-buttons" style="margin-bottom: 16px;">
                    <button type="button" class="btn-template" onclick="fillTemplate('netflix')">Netflix Premium</button>
                    <button type="button" class="btn-template" onclick="fillTemplate('spotify')">Spotify Premium</button>
                    <button type="button" class="btn-template" onclick="fillTemplate('game')">Tai khoa Game (LMHT/Steam)</button>
                    <button type="button" class="btn-template" onclick="fillTemplate('software')">Key Office / Software</button>
                </div>

                <div class="template-subtitle">Mẫu tự thiết lập của bạn</div>
                <div class="template-buttons" id="customTemplatesContainer">
                    <?php foreach ($customTemplates as $tpl): ?>
                        <div class="btn-template-custom-wrapper" id="tpl-card-<?= $tpl['id'] ?>">
                            <button type="button" class="btn-template" onclick="fillCustomTemplate(<?= htmlspecialchars(json_encode($tpl)) ?>)">
                                <?= htmlspecialchars($tpl['name']) ?>
                            </button>
                            <button type="button" class="btn-template-delete" onclick="deleteCustomTemplate(<?= $tpl['id'] ?>, event)">✕</button>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($customTemplates)): ?>
                        <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;" id="noCustomTplMsg">Chưa có mẫu tự lưu nào. Điền thông tin và bấm "Lưu làm mẫu" phía dưới để tạo.</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mode Selector -->
            <div class="mode-selector">
                <div class="mode-option active" id="modeSingle" onclick="setMode('single')">Thêm 1 tài khoản</div>
                <div class="mode-option" id="modeBulk" onclick="setMode('bulk')">Nhập hàng loạt (Bulk Import)</div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="form-card" id="productForm">
                <input type="hidden" name="import_mode" id="import_mode" value="single">

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Tên sản phẩm / gói dịch vụ</label>
                        <input type="text" id="name" name="name" required placeholder="Ví dụ: Netflix Premium 4K - 1 tháng">
                    </div>
                    <div class="form-group">
                        <label for="price">Giá bán (đ)</label>
                        <input type="number" id="price" name="price" min="0" required placeholder="Ví dụ: 50000">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Danh mục</label>
                        <select id="category_id" name="category_id">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Trạng thái ban đầu</label>
                        <select id="status" name="status">
                            <option value="available">Đang bán (Available)</option>
                            <option value="sold">Đã bán (Sold)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Image upload section -->
                <div class="upload-wrapper">
                    <div class="form-group">
                        <label for="image_file">Tải ảnh sản phẩm trực tiếp từ thiết bị</label>
                        <input type="file" id="image_file" name="image_file" accept="image/*" onchange="previewUploadImage(this)">
                        
                        <label for="image" style="margin-top: 12px;">Hoặc điền link URL ảnh có sẵn</label>
                        <input type="text" id="image" name="image" placeholder="https://..." oninput="previewUrlImage(this.value)">
                    </div>
                    <div class="form-group">
                        <label>Xem trước hình ảnh</label>
                        <div class="image-preview-container">
                            <span class="preview-placeholder" id="previewPlaceholder">Chưa có ảnh</span>
                            <img class="image-preview" id="imagePreview" src="" alt="Xem trước">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Mô tả sản phẩm hiển thị trên trang chủ</label>
                    <textarea id="description" name="description" rows="4" placeholder="Nhập mô tả sản phẩm dịch vụ..."></textarea>
                </div>
                
                <!-- SINGLE ACCOUNT MODE CONTAINER -->
                <div id="singleModeContainer">
                    <div class="form-group">
                        <label for="account_detail">Thông tin tài khoản bàn giao (Bảo mật - chỉ hiện sau khi khách thanh toán)</label>
                        <textarea id="account_detail" name="account_detail" rows="4" placeholder="Tên đăng nhập, mật khẩu, email, ghi chú bàn giao..." required></textarea>
                    </div>
                    
                    <!-- Bảng tự động điền trực quan -->
                    <div style="background-color: #0d0d0d; border: 1px solid var(--border-color); padding: 20px; margin-top: -10px; margin-bottom: 20px;">
                        <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #a3a3a3; margin-bottom: 12px; letter-spacing: 0.5px;">Bảng trợ giúp chia trường thông tin nhanh</div>
                        <div class="form-row" style="margin-bottom: 12px;">
                            <div class="form-group">
                                <label style="font-size: 0.8rem; color: var(--text-muted);">Tên đăng nhập / Email</label>
                                <input type="text" id="helper_user" placeholder="Ví dụ: accnet@gmail.com" oninput="updateAccountDetailTextarea()">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 0.8rem; color: var(--text-muted);">Mật khẩu</label>
                                <input type="text" id="helper_pass" placeholder="Ví dụ: pass123" oninput="updateAccountDetailTextarea()">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label style="font-size: 0.8rem; color: var(--text-muted);">Hạn dùng / Thông tin phụ</label>
                                <input type="text" id="helper_info" placeholder="Ví dụ: Gói Ultra 4K" oninput="updateAccountDetailTextarea()">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 0.8rem; color: var(--text-muted);">Ghi chú bảo hành</label>
                                <input type="text" id="helper_note" placeholder="Ví dụ: Không thay đổi mật khẩu" oninput="updateAccountDetailTextarea()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BULK IMPORT MODE CONTAINER -->
                <div id="bulkModeContainer" style="display: none;">
                    <div class="form-row" style="margin-bottom: 16px;">
                        <div class="form-group">
                            <label for="bulk_separator">Ký tự ngăn cách (Separator)</label>
                            <select id="bulk_separator" name="bulk_separator" style="width: 200px;">
                                <option value="|">Dấu sổ dọc ( | )</option>
                                <option value=":">Dấu hai chấm ( : )</option>
                                <option value=",">Dấu phẩy ( , )</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulk_accounts">Danh sách tài khoản (Mỗi tài khoản 1 dòng theo định dạng ngăn cách đã chọn)</label>
                        <textarea id="bulk_accounts" name="bulk_accounts" rows="8" placeholder="Format: user|pass|ghi chu&#10;Vi du:&#10;netflix1@gmail.com|passabc|Han dung 30 ngay&#10;netflix2@gmail.com|passxyz|Han dung 60 ngay"></textarea>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Mỗi dòng được thêm thành công sẽ tạo thành 1 sản phẩm bán lẻ riêng biệt có cùng thông tin tên, giá, danh mục, mô tả.</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 16px; margin-top: 12px; width: 100%;">
                    <button type="submit" class="btn btn-primary" style="flex: 2;">Lưu sản phẩm</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="saveAsTemplate()">Lưu làm mẫu</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function setMode(mode) {
    document.getElementById('import_mode').value = mode;
    
    const modeSingle = document.getElementById('modeSingle');
    const modeBulk = document.getElementById('modeBulk');
    const singleContainer = document.getElementById('singleModeContainer');
    const bulkContainer = document.getElementById('bulkModeContainer');
    
    const accountDetailTextarea = document.getElementById('account_detail');
    const bulkAccountsTextarea = document.getElementById('bulk_accounts');
    
    if (mode === 'bulk') {
        modeSingle.classList.remove('active');
        modeBulk.classList.add('active');
        singleContainer.style.display = 'none';
        bulkContainer.style.display = 'block';
        
        // Remove required attribute from single mode input to avoid form submit blocks
        accountDetailTextarea.removeAttribute('required');
        bulkAccountsTextarea.setAttribute('required', 'required');
    } else {
        modeSingle.classList.add('active');
        modeBulk.classList.remove('active');
        singleContainer.style.display = 'block';
        bulkContainer.style.display = 'none';
        
        accountDetailTextarea.setAttribute('required', 'required');
        bulkAccountsTextarea.removeAttribute('required');
    }
}

function updateAccountDetailTextarea() {
    const user = document.getElementById('helper_user').value.trim();
    const pass = document.getElementById('helper_pass').value.trim();
    const info = document.getElementById('helper_info').value.trim();
    const note = document.getElementById('helper_note').value.trim();
    
    let compiled = '';
    if (user) compiled += 'Ten dang nhap: ' + user + '\n';
    if (pass) compiled += 'Mat khau: ' + pass + '\n';
    if (info) compiled += 'Han dung/Thong tin: ' + info + '\n';
    if (note) compiled += 'Ghi chu: ' + note;
    
    document.getElementById('account_detail').value = compiled;
}

function previewUrlImage(url) {
    const preview = document.getElementById('imagePreview');
    const placeholder = document.getElementById('previewPlaceholder');
    if (url.trim() !== '') {
        preview.src = url;
        preview.style.display = 'block';
        placeholder.style.display = 'none';
    } else {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
    }
}

function previewUploadImage(input) {
    const preview = document.getElementById('imagePreview');
    const placeholder = document.getElementById('previewPlaceholder');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
    }
}

function fillTemplate(type) {
    const nameEl = document.getElementById('name');
    const priceEl = document.getElementById('price');
    const categoryEl = document.getElementById('category_id');
    const descriptionEl = document.getElementById('description');
    const imageEl = document.getElementById('image');
    
    // Reset file upload
    document.getElementById('image_file').value = '';
    
    let template = {};
    
    if (type === 'netflix') {
        template = {
            name: 'Netflix Premium 4K UHD - 1 thang',
            price: 55000,
            category: '2', // Streaming
            description: 'Goi xem phim Netflix chat luong Ultra HD 4K, ho tro tieng Viet hoan toan. Su dung rieng 1 Profile ca nhan, co the tai phim xem offline.',
            image: 'https://images.unsplash.com/photo-1574375927938-d5a98e8edd86?q=80&w=600&auto=format&fit=crop'
        };
    } else if (type === 'spotify') {
        template = {
            name: 'Spotify Premium Individual - 1 thang',
            price: 25000,
            category: '2', // Streaming
            description: 'Goi Premium ca nhan nghe nhac khong quang cao, chat luong am thanh cao nhat (320kbps), tai nhac khong gioi han ve thiet bi.',
            image: 'https://images.unsplash.com/photo-1610433572201-110753c6cff9?q=80&w=600&auto=format&fit=crop'
        };
    } else if (type === 'game') {
        template = {
            name: 'Acc Game Full thong tin',
            price: 150000,
            category: '1', // Game
            description: 'Tai khoan game sach, da nang cap day du, trang bi san pham vip. Cung cap full quyen so huu va ho tro doi thong tin email ngay lap tuc.',
            image: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?q=80&w=600&auto=format&fit=crop'
        };
    } else if (type === 'software') {
        template = {
            name: 'Office 365 Personal - 1 nam',
            price: 490000,
            category: '3', // Software
            description: 'Key kich hoat ban quyen Office 365 Personal thoi han 1 nam. Co 1TB luu tru dam may OneDrive di kem. Kich hoat truc tiep tren tai khoan cua ban.',
            image: 'https://images.unsplash.com/photo-1618401471353-b98aedd07871?q=80&w=600&auto=format&fit=crop'
        };
    }
    
    nameEl.value = template.name;
    priceEl.value = template.price;
    categoryEl.value = template.category;
    descriptionEl.value = template.description;
    imageEl.value = template.image;
    
    previewUrlImage(template.image);
}

function fillCustomTemplate(tpl) {
    document.getElementById('name').value = tpl.name || '';
    document.getElementById('price').value = tpl.price || 0;
    document.getElementById('category_id').value = tpl.category_id || '';
    document.getElementById('description').value = tpl.description || '';
    document.getElementById('image').value = tpl.image || '';
    document.getElementById('image_file').value = ''; // clear upload image to favor template URL
    
    previewUrlImage(tpl.image || '');
}

function saveAsTemplate() {
    const name = document.getElementById('name').value.trim();
    const price = document.getElementById('price').value.trim();
    const category_id = document.getElementById('category_id').value;
    const description = document.getElementById('description').value.trim();
    const image = document.getElementById('image').value.trim();
    
    if (!name) {
        alert('Vui long dien ten san pham de lam ten mau.');
        return;
    }
    
    const formData = new FormData();
    formData.append('name', name);
    formData.append('price', price);
    formData.append('category_id', category_id);
    formData.append('description', description);
    formData.append('image', image);
    
    fetch('add.php?action=save_template', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Luu mau thanh cong! Dang tai lai danh sach mau...');
            window.location.reload();
        } else {
            alert('Loi: ' + (data.error || 'Khong the luu mau.'));
        }
    })
    .catch(() => alert('Loi ket noi server!'));
}

function deleteCustomTemplate(id, event) {
    event.stopPropagation();
    if (!confirm('Ban co chac chan muon xoa mau thiet lap nay?')) return;
    
    fetch('add.php?action=delete_template&id=' + id)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById('tpl-card-' + id);
            if (card) card.remove();
            
            // Neu het tpl thi hien thi tin nhan empty
            const container = document.getElementById('customTemplatesContainer');
            if (container.querySelectorAll('.btn-template-custom-wrapper').length === 0) {
                const msg = document.createElement('span');
                msg.id = 'noCustomTplMsg';
                msg.style.fontSize = '0.8rem';
                msg.style.color = 'var(--text-muted)';
                msg.style.fontStyle = 'italic';
                msg.textContent = 'Chua co mau tu luu nao. Dien thong tin va bam "Lưu làm mẫu" phia duoi de tao.';
                container.appendChild(msg);
            }
        } else {
            alert('Khong the xoa mau thiet lap.');
        }
    })
    .catch(() => alert('Loi ket noi server!'));
}
</script>
</body>
</html>
