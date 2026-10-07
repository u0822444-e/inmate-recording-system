(function () {
  'use strict';

  var shell = document.getElementById('dashboardScreen');
  if (!shell) return;

  var sidebar  = shell.querySelector('.dashboard-sidebar');
  var menuBtn  = document.getElementById('dashboardMenuButton');
  var navItems = shell.querySelectorAll('.sidebar-nav__item[data-section]');
  var triggers = shell.querySelectorAll('[data-section]');
  var sections = shell.querySelectorAll('.dashboard-section');

  /* ============================================================
     HELPERS
     ============================================================ */

  function getSectionFromUrl() {
    var url = new URL(window.location.href);
    return url.searchParams.get('section') || shell.dataset.initialSection || 'overview';
  }

  function validSection(name) {
    var target = document.getElementById('dashboard-' + name);
    return target && shell.contains(target);
  }

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('is-open');
    if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
  }

  function showSection(name, updateUrl) {
    if (!validSection(name)) name = 'overview';

    var target = document.getElementById('dashboard-' + name);

    sections.forEach(function (s) {
      var on = s === target;
      s.hidden = !on;
      s.classList.toggle('is-visible', on);
    });

    navItems.forEach(function (item) {
      var on = item.dataset.section === name;
      item.classList.toggle('is-active', on);
    });

    closeSidebar();

    if (updateUrl !== false) {
      var url = new URL(window.location.href);
      if (url.searchParams.get('section') !== name) {
        url.searchParams.set('section', name);
        history.replaceState(null, '', url.toString());
      }
    }

    document.dispatchEvent(new CustomEvent('section:changed', {
      detail: { section: name }
    }));
  }

  /* ============================================================
     SECTION TRIGGERS
     ============================================================ */

  triggers.forEach(function (t) {
    t.addEventListener('click', function (e) {
      var name = e.currentTarget.dataset.section;
      if (name) showSection(name);
    });
  });

  /* ============================================================
     SIDEBAR TOGGLE
     ============================================================ */

  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', function () {
      sidebar.classList.toggle('is-open');
      menuBtn.setAttribute('aria-expanded', sidebar.classList.contains('is-open') ? 'true' : 'false');
    });
  }

  document.addEventListener('click', function (e) {
    if (window.innerWidth > 991) return;
    if (!sidebar || !sidebar.classList.contains('is-open')) return;
    if (sidebar.contains(e.target) || (menuBtn && menuBtn.contains(e.target))) return;
    closeSidebar();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });

  /* ============================================================
     LOGOUT CONFIRMATION
     ============================================================ */

  var logoutBtn   = document.getElementById('sidebarLogoutBtn');
  var logoutModal = document.getElementById('logoutModal');

  function openLogoutModal() {
    if (!logoutModal) return;
    logoutModal.classList.remove('is-hidden');
    document.body.classList.add('modal-open');

    setTimeout(function () {
      var okBtn = logoutModal.querySelector('#logoutConfirmBtn');
      if (okBtn) okBtn.focus();
    }, 50);
  }

  function closeLogoutModal() {
    if (!logoutModal) return;
    logoutModal.classList.add('is-hidden');
    document.body.classList.remove('modal-open');
  }

  if (logoutBtn && logoutModal) {
    logoutBtn.addEventListener('click', openLogoutModal);

    logoutModal.addEventListener('click', function (e) {
      if (e.target === logoutModal) closeLogoutModal();
      if (e.target.closest('[data-logout-close]')) closeLogoutModal();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !logoutModal.classList.contains('is-hidden')) {
        closeLogoutModal();
      }
    });
  }

  /* ============================================================
     ALERT BANNER — auto-dismiss
     ============================================================ */

  var alertBanner = document.querySelector('.alert-banner');

  if (alertBanner) {
    var dismissAlert = function () {
      if (alertBanner.classList.contains('is-dismissing')) return;
      alertBanner.classList.add('is-dismissing');
      setTimeout(function () { alertBanner.remove(); }, 400);
    };

    var closeBtn = alertBanner.querySelector('.alert-banner__close');
    if (closeBtn) closeBtn.addEventListener('click', dismissAlert);

    setTimeout(dismissAlert, 4000);
  }

  /* ============================================================
     TAB GROUP
     ============================================================ */

  document.querySelectorAll('.tab-group').forEach(function (group) {
    group.addEventListener('click', function (e) {
      var btn = e.target.closest('.tab-btn');
      if (!btn) return;

      group.querySelectorAll('.tab-btn').forEach(function (b) {
        b.classList.remove('is-active');
      });
      btn.classList.add('is-active');
    });
  });

  /* ============================================================
     BACK / FORWARD
     ============================================================ */

  window.addEventListener('popstate', function () {
    showSection(getSectionFromUrl(), false);
  });

  /* ============================================================
     INITIAL RENDER
     ============================================================ */

  showSection(getSectionFromUrl(), false);
})();