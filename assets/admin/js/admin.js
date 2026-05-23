// NEXUS Admin helpers
(function () {
  window.AdminUI = window.AdminUI || {};
  window.AdminUI.toast = function (message) {
    if (!message) return;
    console.log('[Admin]', message);
  };
})();
