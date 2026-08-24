    <footer>
        <div class="container footer-content">
            <p>&copy; Nhóm 5. Bài tập lớn Lập trình web và ứng dụng.</p>
            <p>Thành viên: Võ Anh Kiệt Hoàng, Trần Gia Bảo, Nguyễn Đức Mạnh, Nguyễn Hoàng Thái.</p>
        </div>
    </footer>

    <div id="toastNotification" class="toast-notification">Đã thêm vào giỏ hàng thành công!</div>

    <script>
    function showToast(message, isError = false) {
        const toast = document.getElementById('toastNotification');
        if (!toast) return;
        toast.textContent = message;
        if (isError) {
            toast.classList.add('toast-error');
        } else {
            toast.classList.remove('toast-error');
        }
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 2500);
    }

    function addToCart(accountId, element) {
        fetch('<?= BASE_PATH ?>cart.php?action=add&id=' + accountId + '&ajax=1')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const cartCount = document.getElementById('cartCount');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    if (element) {
                        element.textContent = 'Đã thêm';
                        element.style.backgroundColor = '#059669';
                        element.onclick = function() {
                            window.location.href = '<?= BASE_PATH ?>cart.php';
                        };
                    }
                    
                    showToast('Đã thêm sản phẩm vào giỏ hàng!', false);
                } else {
                    showToast(data.error || 'Có lỗi xảy ra!', true);
                }
            })
            .catch(err => {
                showToast('Lỗi kết nối máy chủ!', true);
            });
    }
    </script>
</body>
</html>
