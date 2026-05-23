/**
 * NEXUS ADMIN - Shared JavaScript Utilities
 * Sử dụng chung: public/admin/js/admin.js
 *
 * Hướng dẫn:
 *   - AdminToast.success(message)
 *   - AdminToast.error(message)
 *   - AdminToast.warning(message)
 *   - AdminToast.info(message)
 *   - AdminUtils.copyToClipboard(text)
 *   - AdminUtils.formatPrice(num)
 *   - AdminUtils.formatNumber(num)
 *   - AdminUtils.escapeHtml(str)
 */

/* =====================================================
   TOAST NOTIFICATIONS
   ===================================================== */
const AdminToast = {
    show(type, message, duration = 3500) {
        const container = document.getElementById('nexus-toast-container') || this._createContainer();
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-circle-info'
        };

        const toast = document.createElement('div');
        toast.className = `nexus-toast nexus-toast-${type}`;
        toast.innerHTML = `
            <i class="fa-solid ${icons[type] || icons.info} nexus-toast-icon"></i>
            <span>${this._escapeHtml(message)}</span>
        `;

        container.appendChild(toast);
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 350);
        }, duration);
    },

    success(msg, dur) { this.show('success', msg, dur); },
    error(msg, dur) { this.show('error', msg, dur); },
    warning(msg, dur) { this.show('warning', msg, dur); },
    info(msg, dur) { this.show('info', msg, dur); },

    _createContainer() {
        const container = document.createElement('div');
        container.id = 'nexus-toast-container';
        document.body.appendChild(container);
        return container;
    },

    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

/* =====================================================
   UTILITIES
   ===================================================== */
const AdminUtils = {
    async copyToClipboard(text, successMsg = null) {
        try {
            await navigator.clipboard.writeText(text);
            AdminToast.success(successMsg || 'Đã sao chép!');
        } catch (err) {
            try {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                AdminToast.success(successMsg || 'Đã sao chép!');
            } catch (e2) {
                AdminToast.error('Không thể sao chép. Vui lòng sao chép thủ công.');
            }
        }
    },

    formatPrice(num) {
        return new Intl.NumberFormat('vi-VN').format(num) + 'đ';
    },

    formatNumber(num) {
        return new Intl.NumberFormat('vi-VN').format(num || 0);
    },

    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    formatDate(dateStr, format = 'dd/mm/yyyy HH:MM') {
        const d = new Date(dateStr);
        const pad = n => String(n).padStart(2, '0');
        return format
            .replace('dd', pad(d.getDate()))
            .replace('mm', pad(d.getMonth() + 1))
            .replace('yyyy', d.getFullYear())
            .replace('HH', pad(d.getHours()))
            .replace('MM', pad(d.getMinutes()))
            .replace('SS', pad(d.getSeconds()));
    },

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

/* =====================================================
   SIDEBAR TOGGLE (shared across all admin pages)
   ===================================================== */
function toggleSidebar() {
    document.querySelector('.admin-sidebar').classList.toggle('open');
    const overlay = document.getElementById('adminSidebarOverlay');
    if (overlay) overlay.classList.toggle('show');
}

/* =====================================================
   INIT - Auto-attach copy buttons
   ===================================================== */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-copy]').forEach(function(el) {
        el.style.cursor = 'pointer';
        el.addEventListener('click', function() {
            const targetId = this.getAttribute('data-copy');
            const targetEl = targetId ? document.querySelector(targetId) : null;
            const text = targetEl ? targetEl.textContent.trim() : this.getAttribute('data-copy-value') || '';
            AdminUtils.copyToClipboard(text);
        });
    });
});
