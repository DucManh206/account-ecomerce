// NEXUS Admin helpers
(function () {
  'use strict';

  window.AdminUI = window.AdminUI || {};

  // Toast / notification
  window.AdminUI.toast = function (message, type) {
    if (!message) return;
    type = type || 'info';
    console.log('[Admin]', message);

    // Try to show an inline alert
    var box = document.getElementById('alertBox');
    if (box) {
      box.innerHTML = message;
      box.className = 'nx-alert dash-alert-' + type + ' mb-3';
      box.classList.remove('d-none');
      setTimeout(function () {
        box.classList.add('d-none');
      }, 5000);
      return;
    }
    alert(message);
  };

  // ====== Counter animation for stat values ======
  function animateCounters() {
    var counters = document.querySelectorAll('.stat-counter');
    if (!counters.length) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var el = entry.target;
          var target = parseInt(el.getAttribute('data-target'), 10);
          if (isNaN(target) || target < 1) {
            el.textContent = target || 0;
            return;
          }
          var duration = Math.min(1200, 60 * String(target).length * 50);
          var startTime = null;

          function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = Math.floor(eased * target);
            el.textContent = current.toLocaleString('vi-VN');
            if (progress < 1) {
              requestAnimationFrame(step);
            } else {
              el.textContent = target.toLocaleString('vi-VN');
            }
          }
          requestAnimationFrame(step);
          observer.unobserve(el);
        }
      });
    }, { threshold: 0.3 });

    counters.forEach(function (c) { observer.observe(c); });
  }

  // ====== Hover accent line on stat cards ======
  function setupStatAccents() {
    var cards = document.querySelectorAll('.dash-stat-card');
    var colors = ['#6E56CF', '#38BDF8', '#EF4444', '#10B981'];
    cards.forEach(function (card, i) {
      card.style.setProperty('--accent-color', colors[i % colors.length]);
    });
  }

  // ====== Run on DOM ready ======
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      animateCounters();
      setupStatAccents();
    });
  } else {
    animateCounters();
    setupStatAccents();
  }

})();
