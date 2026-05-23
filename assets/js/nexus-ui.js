/**
 * NEXUS STORE - Shared UI JavaScript
 * Sử dụng chung: assets/js/nexus-ui.js
 *
 * Hướng dẫn:
 *   - NexusToast.success(message) - Toast thành công
 *   - NexusToast.error(message)  - Toast lỗi
 *   - NexusToast.warning(message) - Toast cảnh báo
 *   - NexusToast.info(message)   - Toast thông tin
 *   - NexusToast.show(type, message) - Toast tùy chỉnh
 *   - NexusUtils.copyToClipboard(text) - Copy text vào clipboard
 */

const NexusToast = {
    /**
     * Show a toast notification
     * @param {'success'|'error'|'warning'|'info'} type
     * @param {string} message
     * @param {number} duration ms
     */
    show(type, message, duration = 3000) {
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

    success(message, duration) { this.show('success', message, duration); },
    error(message, duration) { this.show('error', message, duration); },
    warning(message, duration) { this.show('warning', message, duration); },
    info(message, duration) { this.show('info', message, duration); },

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

const NexusUtils = {
    /**
     * Copy text to clipboard
     * @param {string} text
     * @param {string|null} successMsg Custom message, or null for auto message
     */
    async copyToClipboard(text, successMsg = null) {
        try {
            await navigator.clipboard.writeText(text);
            NexusToast.success(successMsg || 'Đã sao chép!');
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
                NexusToast.success(successMsg || 'Đã sao chép!');
            } catch (e2) {
                NexusToast.error('Không thể sao chép. Vui lòng sao chép thủ công.');
            }
        }
    },

    /**
     * Format number to Vietnamese currency
     * @param {number} num
     * @returns {string}
     */
    formatPrice(num) {
        return new Intl.NumberFormat('vi-VN').format(num) + 'đ';
    },

    /**
     * Debounce function
     * @param {Function} func
     * @param {number} wait
     */
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
    },

    /**
     * Format date to Vietnamese format
     * @param {string|Date} date
     * @returns {string}
     */
    formatDate(date, format = 'dd/mm/yyyy HH:MM') {
        const d = new Date(date);
        const pad = n => String(n).padStart(2, '0');
        return format
            .replace('dd', pad(d.getDate()))
            .replace('mm', pad(d.getMonth() + 1))
            .replace('yyyy', d.getFullYear())
            .replace('HH', pad(d.getHours()))
            .replace('MM', pad(d.getMinutes()))
            .replace('SS', pad(d.getSeconds()));
    }
};

/**
 * Copy buttons - auto-attach to elements with data-copy attribute
 * Usage: <button data-copy="#elementId">Copy</button>
 *   or: <span data-copy-value="text to copy">Copy</span>
 */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-copy]').forEach(function(el) {
        el.style.cursor = 'pointer';
        el.addEventListener('click', function() {
            const targetId = this.getAttribute('data-copy');
            const targetEl = targetId ? document.querySelector(targetId) : null;
            const text = targetEl ? targetEl.textContent.trim() : this.getAttribute('data-copy-value') || '';
            NexusUtils.copyToClipboard(text);
        });
    });
});
