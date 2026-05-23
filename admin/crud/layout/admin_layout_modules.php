<?php
require_once __DIR__ . "/../auth/admin_verifier_modules.php";
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
    $assetBase = $basePath . '/assets/admin';
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
        // Global UI Helpers
        function showModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.add('show');
                el.classList.add('active'); // Support both styles
                document.body.style.overflow = 'hidden';
            }
        }

        function hideModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('show');
                el.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function showAlert(type, message) {
            const alertBox = document.getElementById('alertBox');
            if (alertBox) {
                alertBox.innerHTML = message;
                alertBox.className = `alert alert-${type === 'danger' ? 'danger' : 'success'} show`;
                alertBox.classList.remove('d-none');
                setTimeout(() => {
                    alertBox.classList.add('d-none');
                }, 5000);
            } else {
                alert(message);
            }
        }

        function toggleSidebar() {
            document.querySelector('.admin-sidebar').classList.toggle('open');
            document.getElementById('adminSidebarOverlay').classList.toggle('show');
        }

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.nx-modal.show, .nx-modal.active').forEach(m => hideModal(m.id));
            }
        });
    </script>
</body>
</html>
<?php
}


// Compatibility wrapper for CRUD pages generated in the new structure.
if (!function_exists('render_admin_layout')) {
    function render_admin_layout($content, $title = 'Admin', $currentPage = '') {
        return admin_renderLayout($title, $currentPage, $content);
    }
}
