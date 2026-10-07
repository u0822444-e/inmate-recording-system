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

    /* Persist to URL query string so PHP can render on reload */
    if (updateUrl !== false) {
      var url = new URL(window.location.href);
      if (url.searchParams.get('section') !== name) {
        url.searchParams.set('section', name);
        history.replaceState(null, '', url.toString());
      }
    }

    /* Notify other modules (users.js etc.) */
    document.dispatchEvent(new CustomEvent('section:changed', {
      detail: { section: name }
    }));
  }

  /* ============================================================
     EVENT WIRING — section triggers
     ============================================================ */

  triggers.forEach(function (t) {
    t.addEventListener('click', function (e) {
      var name = e.currentTarget.dataset.section;
      if (name) showSection(name);
    });
  });

  /* ============================================================
     EVENT WIRING — sidebar toggle
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
     BACK / FORWARD — history navigation
     ============================================================ */

  window.addEventListener('popstate', function () {
    showSection(getSectionFromUrl(), false);
  });

  /* ============================================================
     INITIAL RENDER
     PHP already made the correct section visible — this call
     only syncs the sidebar and fires section:changed for JS.
     ============================================================ */

  showSection(getSectionFromUrl(), false);
})();