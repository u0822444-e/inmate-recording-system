(function () {
  'use strict';

  var shell = document.getElementById('dashboardScreen');
  if (!shell) return;

  var sidebar = shell.querySelector('.dashboard-sidebar');
  var menuBtn = document.getElementById('dashboardMenuButton');
  var navItems = shell.querySelectorAll('.sidebar-nav__item[data-section]');
  var triggers = shell.querySelectorAll('[data-section]');
  var sections = shell.querySelectorAll('.dashboard-section');

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('is-open');
    if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
  }

  function showSection(name) {
    var target = document.getElementById('dashboard-' + name);
    if (!target || !shell.contains(target)) return;

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
  }

  triggers.forEach(function (t) {
    t.addEventListener('click', function (e) {
      var name = e.currentTarget.dataset.section;
      if (name) showSection(name);
    });
  });

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

  showSection(shell.dataset.initialSection || 'overview');
})();