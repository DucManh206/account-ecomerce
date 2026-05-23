<?php
require_once __DIR__ . '/admin_sidebar_modules.php';

function admin_renderLayout($title, $currentPage, $content = null)
{
    $content = $content ?? ($GLOBALS['content'] ?? '');
    $sidebar = admin_renderSidebar($currentPage);
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
        <link rel="stylesheet" href="../assets/css/admin.css">
    </head>

    <body>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <div class="d-flex">
            <?php echo $sidebar; ?>

            <div class="main-content flex-grow-1">
                <div class="d-flex align-items-center mb-4 d-lg-none">
                    <button class="btn btn-dark me-3 mobile-toggle" onclick="toggleSidebar()">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <span class="fw-bold" style="font-size:1.1rem;">NEXUS Admin</span>
                </div>

                <?php echo $content; ?>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function toggleSidebar() {
                document.querySelector('.sidebar').classList.toggle('open');
                document.getElementById('sidebarOverlay').classList.toggle('show');
            }
        </script>
    </body>

    </html>
<?php
}
