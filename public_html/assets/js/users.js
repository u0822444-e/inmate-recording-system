(function () {
  'use strict';

  var section = document.getElementById('dashboard-users');
  if (!section) return;

  var modal      = document.getElementById('userModal');
  var createBtn  = document.getElementById('userCreateBtn');
  var tbody      = document.getElementById('userTableBody');
  var form       = document.getElementById('userForm');
  var formError  = document.getElementById('userFormError');
  var saveBtn    = document.getElementById('userSaveBtn');
  var search     = document.getElementById('userSearch');
  var roleFilter = document.getElementById('userRoleFilter');
  var statusFilter = document.getElementById('userStatusFilter');

  var users = [];

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

  function render() {
    if (!users.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="data-table__empty">No users found.</td></tr>';
      return;
    }
    tbody.innerHTML = users.map(function (u) {
      var roleClass = u.role === 'Administrator' ? 'admin' : 'staff';
      var statusClass = u.is_active ? 'status-pill--on' : 'status-pill--off';
      var statusText = u.is_active ? 'Active' : 'Inactive';
      var created = (u.created_at || '').slice(0, 10);

      return '<tr data-id="' + u.id + '">' +
        '<td><strong>' + esc(u.full_name) + '</strong></td>' +
        '<td><code>' + esc(u.username) + '</code></td>' +
        '<td><span class="role-badge role-badge--' + roleClass + '">' + esc(u.role) + '</span></td>' +
        '<td><span class="status-pill ' + statusClass + '">' + statusText + '</span></td>' +
        '<td>' + esc(created) + '</td>' +
        '<td class="ta-right"><div class="row-actions">' +
          '<button type="button" class="icon-btn" data-action="edit" title="Edit"><i class="bi bi-pencil"></i></button>' +
          '<button type="button" class="icon-btn" data-action="toggle" title="Toggle"><i class="bi bi-toggle-on"></i></button>' +
          '<button type="button" class="icon-btn icon-btn--danger" data-action="delete" title="Delete"><i class="bi bi-trash"></i></button>' +
        '</div></td></tr>';
    }).join('');
  }

  function load(force) {
    if (loaded && !force) return;
    var params = new URLSearchParams();
    if (search.value.trim()) params.set('q', search.value.trim());
    if (roleFilter.value) params.set('role', roleFilter.value);
    if (statusFilter.value) params.set('status', statusFilter.value);

    tbody.innerHTML = '<tr><td colspan="6" class="data-table__empty">Loading...</td></tr>';

    api('list&' + params.toString()).then(function (data) {
      users = data.users || [];
      loaded = true;
      render();
    }).catch(function (err) {
      tbody.innerHTML = '<tr><td colspan="6" class="data-table__empty">' + esc(err.message) + '</td></tr>';
    });
  }
  var loaded = false;

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
    document.getElementById('userFullName').focus();
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.add('is-hidden');
  }

  if (createBtn) {
    createBtn.addEventListener('click', function () { openModal(null); });
  }

  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
    });
    modal.querySelectorAll('[data-modal-close]').forEach(function (b) {
      b.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal();
    });
  }

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

  if (tbody) {
    tbody.addEventListener('click', function (e) {
      var btn = e.target.closest('button[data-action]');
      if (!btn) return;
      var id = Number(btn.closest('tr').dataset.id);
      var user = users.find(function (u) { return u.id === id; });
      if (!user) return;

      if (btn.dataset.action === 'edit') openModal(user);
      if (btn.dataset.action === 'toggle') {
        api('toggle', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) }).then(function () { load(true); });
      }
      if (btn.dataset.action === 'delete') {
        if (!confirm('Delete ' + user.full_name + '?')) return;
        api('delete', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) }).then(function () { load(true); });
      }
    });
  }

  var timer;
  if (search) search.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(function () { load(true); }, 250);
  });
  if (roleFilter) roleFilter.addEventListener('change', function () { load(true); });
  if (statusFilter) statusFilter.addEventListener('change', function () { load(true); });

  /* Load when Users tab is clicked */
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-section="users"]')) setTimeout(load, 0);
  });

  if (section.classList.contains('is-visible')) load();
})();