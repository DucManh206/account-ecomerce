<?php
require_once __DIR__ . "/admin_verifier_modules.php";
require_once __DIR__ . "/admin_sidebar_modules.php";

function admin_renderLayout($title, $currentPage, $content = null)
{
    $content = $content ?? ($GLOBALS['content'] ?? '');
    $sidebar = admin_renderSidebar($currentPage);

    // Dynamic asset path - works from any depth
    // /admin/dashboard.php -> /public/admin/css/admin.css
    // /admin/manage/orders.php -> /public/admin/css/admin.css
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $adminPos = strrpos($scriptPath, '/admin');
    if ($adminPos !== false) {
        $basePath = substr($scriptPath, 0, $adminPos);
    } else {
        $basePath = '';
    }
    $assetBase = $basePath . '/public/admin';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> — NEXUS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/admin.css">
</head>
<body>

<div class="admin-sidebar-overlay" id="adminSidebarOverlay" onclick="toggleSidebar()"></div>
<button class="admin-mobile-toggle" onclick="toggleSidebar()">
    <i class="fa-solid fa-bars"></i>
</button>

<div class="admin-wrap">
    <?php echo $sidebar; ?>

    <main class="admin-main" role="main">
        <?php echo $content; ?>
    </main>
</div>

<script src="<?php echo $assetBase; ?>/js/admin.js"></script>
<script>
    function toggleSidebar() {
        var sidebar = document.querySelector('.admin-sidebar');
        var overlay = document.getElementById('adminSidebarOverlay');
        if (sidebar) sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('show');
    }

    function showModal(id) {
        document.getElementById(id).classList.add('show');
    }

    function hideModal(id) {
        document.getElementById(id).classList.remove('show');
    }

    function closeAllModals() {
        document.querySelectorAll('.nx-modal.show').forEach(function(el) {
            el.classList.remove('show');
        });
    }

    // ESC key to close modals and sidebar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
            var sidebar = document.querySelector('.admin-sidebar.open');
            var overlay = document.getElementById('adminSidebarOverlay');
            if (sidebar) sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('show');
        }
    });
</script>
</body>
</html>
<?php
}
