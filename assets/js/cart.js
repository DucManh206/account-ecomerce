/**
 * Cart JavaScript - Frontend interactions
 * Sử dụng: Từ cart/index.php hoặc index.php đều dùng ../api/cart.php
 */

const Cart = {
    // Get API URL - tự động detect path
    getApiUrl() {
        // Use absolute route so cart works from root pages, /cart shims, and /crud/* pages.
        return '/api/cart.php';
    },

    // Add product to cart
    async add(productId, quantity = 1, button = null) {
        let originalText = '';
        if (button) {
            originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang thêm...';
        }

        try {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            const response = await fetch(this.getApiUrl(), {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error('API response is not valid JSON:', text);
                this.showToast('error', 'Lỗi server, vui lòng thử lại!');
                return;
            }

            if (result.success) {
                this.showToast('success', result.message);
                this.updateCartCount();
            } else {
                this.showToast('error', result.message || 'Không thể thêm vào giỏ hàng');
            }
        } catch (error) {
            console.error('Add to cart error:', error);
            this.showToast('error', 'Đã xảy ra lỗi khi thêm vào giỏ!');
        } finally {
            if (button) {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }
    },

    // Update quantity
    async update(cartId, quantity, element = null) {
        if (element) {
            element.style.opacity = '0.5';
            element.style.pointerEvents = 'none';
        }

        try {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('cart_id', cartId);
            formData.append('quantity', quantity);

            const response = await fetch(this.getApiUrl(), {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showToast('success', result.message);
                this.updateCartCount();
                setTimeout(() => location.reload(), 300);
            } else {
                this.showToast('error', result.message);
            }
        } catch (error) {
            this.showToast('error', 'Đã xảy ra lỗi!');
            console.error(error);
        } finally {
            if (element) {
                element.style.opacity = '1';
                element.style.pointerEvents = 'auto';
            }
        }
    },

    // Remove from cart
    async remove(cartId, element = null) {
        if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
            return;
        }

        if (element) {
            element.style.opacity = '0.5';
            element.style.pointerEvents = 'none';
        }

        try {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('cart_id', cartId);

            const response = await fetch(this.getApiUrl(), {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showToast('success', result.message);

                if (element) {
                    element.style.transition = 'opacity 0.3s';
                    element.style.opacity = '0';
                    setTimeout(() => {
                        element.remove();
                        this.updateCartTotal();

                        const cartBody = document.querySelector('#cartBody');
                        if (cartBody && cartBody.children.length === 0) {
                            location.reload();
                        }
                    }, 300);
                } else {
                    location.reload();
                }
            } else {
                if (element) {
                    element.style.opacity = '1';
                    element.style.pointerEvents = 'auto';
                }
                this.showToast('error', result.message);
            }
        } catch (error) {
            if (element) {
                element.style.opacity = '1';
                element.style.pointerEvents = 'auto';
            }
            this.showToast('error', 'Đã xảy ra lỗi!');
            console.error(error);
        }
    },

    // Clear cart
    async clear() {
        if (!confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) {
            return;
        }

        const btn = document.querySelector('.btn-clear-all');
        const originalText = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xóa...';
        }

        try {
            const formData = new FormData();
            formData.append('action', 'clear');

            const response = await fetch(this.getApiUrl(), {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showToast('success', 'Đã xóa giỏ hàng');
                setTimeout(() => location.reload(), 500);
            } else {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
                this.showToast('error', result.message);
            }
        } catch (error) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
            this.showToast('error', 'Đã xảy ra lỗi!');
            console.error(error);
        }
    },

    // Update cart count in navbar
    async updateCartCount() {
        try {
            const formData = new FormData();
            formData.append('action', 'count');

            const response = await fetch(this.getApiUrl(), {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                const countElements = document.querySelectorAll('.cart-count, .nexus-cart-count');
                countElements.forEach(el => {
                    const count = result.count.quantity;
                    if (el.classList.contains('nexus-cart-count')) {
                        el.textContent = count;
                        el.style.display = count > 0 ? 'flex' : 'none';
                    } else if (el.tagName === 'SPAN') {
                        el.textContent = count;
                        el.style.display = count > 0 ? 'inline-block' : 'none';
                    } else {
                        el.textContent = count + ' sản phẩm';
                        el.style.display = count > 0 ? 'inline-block' : 'none';
                    }
                });
            }
        } catch (error) {
            console.error('Error updating cart count:', error);
        }
    },

    // Update cart total display
    updateCartTotal() {
        let total = 0;
        document.querySelectorAll('#cartBody .cart-item').forEach(item => {
            total += parseInt(item.dataset.subtotal) || 0;
        });

        const totalElement = document.querySelector('.cart-total-amount');
        if (totalElement) {
            totalElement.textContent = this.formatPrice(total);
        }
    },

    // Load cart items
    async loadCart() {
        try {
            const formData = new FormData();
            formData.append('action', 'list');

            const response = await fetch(this.getApiUrl(), {
                method: 'POST',
                body: formData
            });

            return await response.json();
        } catch (error) {
            console.error('Error loading cart:', error);
            return { success: false, items: [] };
        }
    },

    // Check if product is in cart
    async checkInCart(productId) {
        try {
            const formData = new FormData();
            formData.append('action', 'check');
            formData.append('product_id', productId);

            const response = await fetch(this.getApiUrl(), {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            return result.success && result.in_cart;
        } catch (error) {
            console.error('Error checking cart:', error);
            return false;
        }
    },

    // Format price
    formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
    },

    // Show toast notification (delegates to global NexusToast)
    showToast(type, message) {
        if (typeof NexusToast !== 'undefined') {
            NexusToast.show(type, message);
        }
    },
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    Cart.updateCartCount();
});

// Quick add to cart from product cards
document.addEventListener('click', function(e) {
    const addBtn = e.target.closest('.add-to-cart-btn');
    if (addBtn) {
        e.preventDefault();
        const productId = addBtn.dataset.productId;
        Cart.add(productId, 1, addBtn);
    }
});
