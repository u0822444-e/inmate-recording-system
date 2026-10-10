(function () {
  'use strict';

  var shell = document.getElementById('dashboardScreen');
  if (!shell) return;

  var sidebar  = shell.querySelector('.dashboard-sidebar');
  var menuBtn  = document.getElementById('dashboardMenuButton');
  var navItems = shell.querySelectorAll('.sidebar-nav__item[data-section]');
  var triggers = shell.querySelectorAll('[data-section]');
  var sections = shell.querySelectorAll('.dashboard-section');
  var groups   = shell.querySelectorAll('.sidebar-nav__group[data-group]');

  /* ============================================================
     GROUP HELPERS
     ============================================================ */

  /**
   * Returns the group name that owns the given section, or null.
   */
  function groupOwning(section) {
    var owner = null;
    groups.forEach(function (btn) {
      var group = btn.dataset.group;
      var children = shell.querySelector(
        '.sidebar-nav__children[data-group-children="' + group + '"]'
      );
      if (!children) return;
      if (children.querySelector('.sidebar-nav__item[data-section="' + section + '"]')) {
        owner = group;
      }
    });
    return owner;
  }

  function closeAllGroups(exceptGroup) {
    groups.forEach(function (btn) {
      if (btn.dataset.group === exceptGroup) return;
      btn.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');

      var children = shell.querySelector(
        '.sidebar-nav__children[data-group-children="' + btn.dataset.group + '"]'
      );
      if (children) children.classList.remove('is-open');
    });
  }

  function openGroup(group) {
    var btn = shell.querySelector('.sidebar-nav__group[data-group="' + group + '"]');
    var children = shell.querySelector(
      '.sidebar-nav__children[data-group-children="' + group + '"]'
    );
    if (!btn || !children) return;

    btn.classList.add('is-open');
    btn.setAttribute('aria-expanded', 'true');
    children.classList.add('is-open');
  }

  /* Manual toggle — clicking the group header itself */
  groups.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var group = btn.dataset.group;
      var children = shell.querySelector(
        '.sidebar-nav__children[data-group-children="' + group + '"]'
      );
      if (!children) return;

      var isOpen = btn.classList.toggle('is-open');
      children.classList.toggle('is-open', isOpen);
      btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  });

  /* ============================================================
     SECTION HELPERS
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
    if (!validSection(name)) {
      console.warn('[dashboard] section not found:', name);
      return;
    }

    var target = document.getElementById('dashboard-' + name);

    sections.forEach(function (s) {
      var on = s === target;
      s.hidden = !on;
      s.classList.toggle('is-visible', on);
    });

    navItems.forEach(function (item) {
      var on = item.dataset.section === name;
      item.classList.toggle('is-active', on);
      if (on) item.setAttribute('aria-current', 'page');
      else item.removeAttribute('aria-current');
    });

    /* -------- BEHAVIOR B: close group unless section is a child -------- */
    var owner = groupOwning(name);
    if (owner) {
      // Section belongs to a group → open that group, close others.
      closeAllGroups(owner);
      openGroup(owner);
    } else {
      // Section is outside every group → close all groups.
      closeAllGroups(null);
    }
    /* ------------------------------------------------------------------- */

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
      if (name) {
        e.preventDefault();
        showSection(name);
      }
    });
  });

  /* ============================================================
     SIDEBAR TOGGLE (mobile)
     ============================================================ */

  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', function () {
      sidebar.classList.toggle('is-open');
      menuBtn.setAttribute('aria-expanded',
        sidebar.classList.contains('is-open') ? 'true' : 'false');
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