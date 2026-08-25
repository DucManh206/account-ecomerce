    <footer>
        <div class="container footer-content">
            <p>&copy; <?= date('Y') ?> <strong><?= htmlspecialchars(SITE_SHORT_NAME) ?></strong>. <?= htmlspecialchars((string) app_setting('site_tagline', 'Tài khoản số giao tự động')) ?></p>
            <p class="footer-meta">Giao dịch có lịch sử &bull; Nhận thông tin trong hồ sơ<?php if (app_setting('support_contact', '') !== ''): ?> &bull; Hỗ trợ: <?= htmlspecialchars((string) app_setting('support_contact')) ?><?php endif; ?></p>
        </div>
    </footer>

    <!-- Khung chứa danh sách Toast Notification -->
    <div id="toastContainer" class="toast-container"></div>

    <script>
    function escapeHtml(value) {
        const node = document.createElement('span');
        node.textContent = String(value);
        return node.innerHTML;
    }

    function showToast(message, isError = false, title = '', actionUrl = '', actionText = '') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toastType = isError ? 'toast-error' : 'toast-success';
        const defaultTitle = isError ? 'Lỗi' : 'Thành công';
        const displayTitle = title || defaultTitle;

        const iconSvg = isError
            ? `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`
            : `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;

        let actionHtml = '';
        if (actionUrl && actionText) {
            actionHtml = `<a href="${actionUrl}" class="toast-action-btn">${escapeHtml(actionText)}</a>`;
        }

        const toastItem = document.createElement('div');
        toastItem.className = `toast-item ${toastType}`;
        toastItem.innerHTML = `
            <div class="toast-icon">${iconSvg}</div>
            <div class="toast-body">
                <div class="toast-title">${escapeHtml(displayTitle)}</div>
                <div class="toast-desc">${escapeHtml(message)}</div>
                ${actionHtml}
            </div>
            <button type="button" class="toast-close-btn" title="Đóng">&times;</button>
            <div class="toast-progress"><div class="toast-progress-bar"></div></div>
        `;

        container.appendChild(toastItem);

        // Kích hoạt animation xuất hiện
        requestAnimationFrame(() => {
            toastItem.classList.add('show');
            const pBar = toastItem.querySelector('.toast-progress-bar');
            if (pBar) {
                pBar.style.transition = 'width 3000ms linear';
                pBar.style.width = '0%';
            }
        });

        // Hàm đóng toast mượt mà
        function removeToast() {
            toastItem.classList.remove('show');
            toastItem.classList.add('hide');
            setTimeout(() => {
                if (toastItem.parentElement) {
                    toastItem.parentElement.removeChild(toastItem);
                }
            }, 350);
        }

        // Đóng khi bấm nút x
        const closeBtn = toastItem.querySelector('.toast-close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', removeToast);
        }

        // Tự động đóng sau 3.2s
        const autoDismiss = setTimeout(removeToast, 3200);

        // Dừng timer khi hover chuột vào toast
        toastItem.addEventListener('mouseenter', () => clearTimeout(autoDismiss));
    }

    function addToCart(accountId, element) {
        fetch('<?= BASE_PATH ?>cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: new URLSearchParams({
                cart_action: 'add',
                id: String(accountId),
                ajax: '1',
                csrf_token: <?= json_encode(csrf_token()) ?>
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const cartCount = document.getElementById('cartCount');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                        // Thêm hiệu ứng nhấp nháy badge giỏ hàng
                        cartCount.parentElement.style.transform = 'scale(1.1)';
                        setTimeout(() => {
                            cartCount.parentElement.style.transform = 'scale(1)';
                        }, 200);
                    }
                    
                    if (element) {
                        element.textContent = 'Đã thêm';
                        element.style.backgroundColor = '#059669';
                        element.onclick = function() {
                            window.location.href = '<?= BASE_PATH ?>cart.php';
                        };
                    }
                    
                    showToast(
                        'Đã thêm tài khoản vào giỏ hàng thành công!',
                        false,
                        'Đã thêm vào giỏ',
                        '<?= BASE_PATH ?>cart.php',
                        'Xem giỏ hàng &rarr;'
                    );
                } else {
                    showToast(data.error || 'Có lỗi xảy ra khi thêm giỏ hàng!', true, 'Không thể thêm');
                }
            })
            .catch(err => {
                showToast('Lỗi kết nối máy chủ!', true, 'Lỗi mạng');
            });
    }

    const navToggle = document.querySelector('.nav-toggle');
    const primaryNavigation = document.getElementById('primaryNavigation');
    if (navToggle && primaryNavigation) {
        navToggle.addEventListener('click', () => {
            const isOpen = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', String(!isOpen));
            primaryNavigation.classList.toggle('is-open', !isOpen);
        });
    }
    </script>
</body>
</html>
