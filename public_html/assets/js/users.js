(function () {
  'use strict';

  var section = document.getElementById('dashboard-users');
  if (!section) return;

  /* ---------- User modal refs ---------- */
  var modal          = document.getElementById('userModal');
  var createBtn      = document.getElementById('userCreateBtn');
  var archiveBtn     = document.getElementById('userArchiveToggleBtn');
  var archiveLabel   = document.getElementById('userArchiveToggleLabel');
  var dateColHeader  = document.getElementById('userDateColHeader');
  var tbody          = document.getElementById('userTableBody');
  var form           = document.getElementById('userForm');
  var formError      = document.getElementById('userFormError');
  var saveBtn        = document.getElementById('userSaveBtn');
  var search         = document.getElementById('userSearch');
  var roleFilter     = document.getElementById('userRoleFilter');
  var statusFilter   = document.getElementById('userStatusFilter');

  /* ---------- Confirm modal refs ---------- */
  var confirmModal   = document.getElementById('confirmModal');
  var confirmTitle   = document.getElementById('confirmModalTitle');
  var confirmMessage = document.getElementById('confirmModalMessage');
  var confirmOkBtn   = document.getElementById('confirmModalOk');

  var users = [];
  var loaded = false;
  var scope = 'active';

  /* ============================================================
     HELPERS
     ============================================================ */

  function esc(v) {
    var d = document.createElement('div');
    d.textContent = String(v == null ? '' : v);
    return d.innerHTML;
  }

  function api(action, options) {
    options = options || {};
    return fetch('index.php?route=users&action=' + action, Object.assign({
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    }, options)).then(function (r) {
      return r.json().then(function (data) {
        if (!r.ok || !data.success) throw new Error(data.message || 'Error');
        return data;
      });
    });
  }

  /* ============================================================
     CONFIRM MODAL
     ============================================================ */

  var confirmResolve = null;

  function confirmDialog(opts) {
    return new Promise(function (resolve) {
      if (!confirmModal) { resolve(window.confirm(opts.message)); return; }

      confirmResolve = resolve;
      confirmTitle.textContent = opts.title || 'Confirm';
      confirmMessage.textContent = opts.message || 'Are you sure?';

      confirmOkBtn.textContent = opts.confirmLabel || 'Confirm';
      confirmOkBtn.className = 'btn-primary';

      if (opts.variant === 'danger') {
        confirmOkBtn.className = 'btn-danger';
      } else if (opts.variant === 'warning') {
        confirmOkBtn.className = 'btn-warning';
      }

      confirmModal.classList.remove('is-hidden');
      document.body.classList.add('modal-open');
      setTimeout(function () { confirmOkBtn.focus(); }, 50);
    });
  }

  function closeConfirm(result) {
    if (confirmModal) {
      confirmModal.classList.add('is-hidden');
      document.body.classList.remove('modal-open');
    }
    if (confirmResolve) {
      var r = confirmResolve;
      confirmResolve = null;
      r(result);
    }
  }

  if (confirmModal) {
    confirmModal.addEventListener('click', function (e) {
      if (e.target === confirmModal) closeConfirm(false);
      if (e.target.closest('[data-confirm-close]')) closeConfirm(false);
    });

    confirmOkBtn.addEventListener('click', function () { closeConfirm(true); });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !confirmModal.classList.contains('is-hidden')) {
        closeConfirm(false);
      }
    });
  }

  /* ============================================================
     RENDER
     ============================================================ */

  function render() {
    if (!users.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="data-table__empty">' +
        (scope === 'archived' ? 'No archived users.' : 'No users found.') +
        '</td></tr>';
      return;
    }

    tbody.innerHTML = users.map(function (u) {
      var roleClass   = u.role === 'Administrator' ? 'admin' : 'staff';
      var statusClass = u.is_active ? 'status-pill--on' : 'status-pill--off';
      var statusText  = u.is_active ? 'Active' : 'Inactive';
      var dateShown   = scope === 'archived'
        ? (u.archived_at || '').slice(0, 10)
        : (u.created_at || '').slice(0, 10);

      var actions;
      if (scope === 'archived') {
        actions =
          '<button type="button" class="icon-btn" data-action="restore" title="Restore">' +
            '<i class="bi bi-arrow-counterclockwise"></i>' +
          '</button>' +
          '<button type="button" class="icon-btn icon-btn--danger" data-action="delete" title="Permanently delete">' +
            '<i class="bi bi-trash"></i>' +
          '</button>';
      } else {
        actions =
          '<button type="button" class="icon-btn" data-action="edit" title="Edit">' +
            '<i class="bi bi-pencil"></i>' +
          '</button>' +
          '<button type="button" class="icon-btn" data-action="toggle" title="Toggle status">' +
            '<i class="bi bi-toggle-on"></i>' +
          '</button>' +
          '<button type="button" class="icon-btn" data-action="archive" title="Archive">' +
            '<i class="bi bi-archive"></i>' +
          '</button>';
      }

      return '<tr data-id="' + u.id + '">' +
        '<td><strong>' + esc(u.full_name) + '</strong></td>' +
        '<td><code>' + esc(u.username) + '</code></td>' +
        '<td><span class="role-badge role-badge--' + roleClass + '">' + esc(u.role) + '</span></td>' +
        '<td><span class="status-pill ' + statusClass + '">' + statusText + '</span></td>' +
        '<td>' + esc(dateShown) + '</td>' +
        '<td class="ta-right"><div class="row-actions">' + actions + '</div></td>' +
      '</tr>';
    }).join('');
  }

  /* ============================================================
     LOAD
     ============================================================ */

  function load(force) {
    if (loaded && !force) return;

    var params = new URLSearchParams();
    if (search.value.trim()) params.set('q', search.value.trim());
    if (roleFilter.value) params.set('role', roleFilter.value);
    if (statusFilter.value) params.set('status', statusFilter.value);
    params.set('scope', scope);

    tbody.innerHTML = '<tr><td colspan="6" class="data-table__empty">Loading...</td></tr>';

    api('list&' + params.toString()).then(function (data) {
      users = data.users || [];
      loaded = true;
      render();
    }).catch(function (err) {
      tbody.innerHTML = '<tr><td colspan="6" class="data-table__empty">' + esc(err.message) + '</td></tr>';
    });
  }

  /* ============================================================
     USER MODAL
     ============================================================ */

  function openModal(user) {
    if (!modal) return;
    form.reset();
    formError.hidden = true;
    formError.textContent = '';

    document.getElementById('userModalTitle').textContent = user ? 'Edit User' : 'New User';
    document.getElementById('userId').value = user ? user.id : '';

    if (user) {
      document.getElementById('userFullName').value = user.full_name;
      document.getElementById('userUsername').value = user.username;
      document.getElementById('userRole').value = user.role;
      document.getElementById('userPasswordHint').textContent = 'leave blank to keep current';
    } else {
      document.getElementById('userPasswordHint').textContent = 'min 8 characters';
    }

    modal.classList.remove('is-hidden');
    document.body.classList.add('modal-open');
    document.getElementById('userFullName').focus();
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.add('is-hidden');
    document.body.classList.remove('modal-open');
  }

  if (createBtn) {
    createBtn.addEventListener('click', function () { openModal(null); });
  }

  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
      if (e.target.closest('[data-modal-close]')) closeModal();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !modal.classList.contains('is-hidden')) closeModal();
    });
  }

  /* ============================================================
     SUBMIT FORM
     ============================================================ */

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var id = document.getElementById('userId').value;
      var payload = {
        id: id ? Number(id) : 0,
        full_name: document.getElementById('userFullName').value.trim(),
        username: document.getElementById('userUsername').value.trim(),
        role: document.getElementById('userRole').value,
        password: document.getElementById('userPassword').value
      };

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      api(id ? 'update' : 'create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      }).then(function () {
        closeModal();
        load(true);
      }).catch(function (err) {
        formError.textContent = err.message;
        formError.hidden = false;
      }).finally(function () {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save';
      });
    });
  }

  /* ============================================================
     TABLE ACTIONS
     ============================================================ */

  if (tbody) {
    tbody.addEventListener('click', async function (e) {
      var btn = e.target.closest('button[data-action]');
      if (!btn) return;
      var id = Number(btn.closest('tr').dataset.id);
      var user = users.find(function (u) { return u.id === id; });
      if (!user) return;

      var action = btn.dataset.action;

      if (action === 'edit') {
        openModal(user);
        return;
      }

      if (action === 'toggle') {
        var toggleOk = await confirmDialog({
          title: 'Change Status',
          message: (user.is_active ? 'Deactivate' : 'Activate') + ' "' + user.full_name + '"?',
          confirmLabel: user.is_active ? 'Deactivate' : 'Activate'
        });
        if (!toggleOk) return;

        api('toggle', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id })
        }).then(function () { load(true); })
          .catch(function (err) { alert(err.message); });
        return;
      }

      if (action === 'archive') {
        var archiveOk = await confirmDialog({
          title: 'Archive User',
          message: 'Archive "' + user.full_name + '"? They will no longer appear in the active list.',
          confirmLabel: 'Archive',
          variant: 'warning'
        });
        if (!archiveOk) return;

        api('archive', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id })
        }).then(function () { load(true); })
          .catch(function (err) { alert(err.message); });
        return;
      }

      if (action === 'restore') {
        var restoreOk = await confirmDialog({
          title: 'Restore User',
          message: 'Restore "' + user.full_name + '" to the active list?',
          confirmLabel: 'Restore'
        });
        if (!restoreOk) return;

        api('restore', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id })
        }).then(function () { load(true); })
          .catch(function (err) { alert(err.message); });
        return;
      }

      if (action === 'delete') {
        var deleteOk = await confirmDialog({
          title: 'Delete Permanently',
          message: 'Permanently delete "' + user.full_name + '"? This cannot be undone.',
          confirmLabel: 'Delete Forever',
          variant: 'danger'
        });
        if (!deleteOk) return;

        api('delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id })
        }).then(function () { load(true); })
          .catch(function (err) { alert(err.message); });
      }
    });
  }

  /* ============================================================
     ARCHIVE TOGGLE
     ============================================================ */

  if (archiveBtn) {
    archiveBtn.addEventListener('click', function () {
      scope = scope === 'archived' ? 'active' : 'archived';
      loaded = false;

      archiveLabel.textContent = scope === 'archived' ? 'View Active' : 'View Archived';
      dateColHeader.textContent = scope === 'archived' ? 'Archived' : 'Created';

      if (createBtn) createBtn.hidden = scope === 'archived';

      load(true);
    });
  }

  /* ============================================================
     FILTERS
     ============================================================ */

  var timer;
  if (search) {
    search.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () { load(true); }, 250);
    });
  }
  if (roleFilter) roleFilter.addEventListener('change', function () { load(true); });
  if (statusFilter) statusFilter.addEventListener('change', function () { load(true); });

  /* ============================================================
     SECTION EVENT
     ============================================================ */

  document.addEventListener('section:changed', function (e) {
    if (e.detail && e.detail.section === 'users') {
      load();
    }
  });

  if (section.classList.contains('is-visible')) load();
})();