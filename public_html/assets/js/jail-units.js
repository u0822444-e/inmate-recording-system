(function () {
  'use strict';

  var section = document.getElementById('dashboard-jail-units');
  if (!section) return;

  /* ---------- Toolbar / table ---------- */
  var tbody        = document.getElementById('jailUnitTableBody');
  var search       = document.getElementById('jailUnitSearch');
  var typeFilter   = document.getElementById('jailUnitTypeFilter');
  var statusFilter = document.getElementById('jailUnitStatusFilter');
  var createBtn    = document.getElementById('jailUnitCreateBtn');

  /* ---------- Modal ---------- */
  var modal     = document.getElementById('jailUnitModal');
  var form      = document.getElementById('jailUnitForm');
  var formError = document.getElementById('jailUnitFormError');
  var saveBtn   = document.getElementById('jailUnitSaveBtn');

  var inputId           = document.getElementById('jailUnitId');
  var inputName         = document.getElementById('jailUnitName');
  var inputType         = document.getElementById('jailUnitType');
  var inputDormitory    = document.getElementById('jailUnitDormitory');
  var inputMunicipality = document.getElementById('jailUnitMunicipality');
  var inputProvince     = document.getElementById('jailUnitProvince');
  var inputWarden       = document.getElementById('jailUnitWarden');
  var inputIsActive     = document.getElementById('jailUnitIsActive');

  /* ============================================================
     STATE
     ============================================================ */

  var jailUnits = [];
  var loaded    = false;

  /* ============================================================
     HELPERS
     ============================================================ */

  function esc(v) {
    var d = document.createElement('div');
    d.textContent = String(v == null ? '' : v);
    return d.innerHTML;
  }

  function api(action, params, options) {
    var url = 'index.php?route=jail-units&action=' + encodeURIComponent(action);
    if (params) {
      Object.keys(params).forEach(function (k) {
        if (params[k] !== '' && params[k] !== null && params[k] !== undefined) {
          url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }
      });
    }
    options = options || {};
    return fetch(url, Object.assign({
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
     RENDER
     ============================================================ */

  function renderTable() {
    var q      = search ? search.value.trim().toLowerCase() : '';
    var type   = typeFilter ? typeFilter.value : '';
    var status = statusFilter ? statusFilter.value : '';

    var filtered = jailUnits.filter(function (u) {
      if (type && u.type !== type) return false;
      if (status === 'active' && !u.is_active) return false;
      if (status === 'inactive' && u.is_active) return false;
      if (q) {
        var hay = (u.name + ' ' + (u.municipality || '')).toLowerCase();
        if (hay.indexOf(q) === -1) return false;
      }
      return true;
    });

    if (!filtered.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="data-table__empty">No jail units found.</td></tr>';
      return;
    }

    tbody.innerHTML = filtered.map(function (u) {
      var statusClass = u.is_active ? 'status-pill--on' : 'status-pill--off';
      var statusText  = u.is_active ? 'Active' : 'Inactive';

      var toggleIcon = u.is_active ? 'bi-toggle-on' : 'bi-toggle-off';
      var toggleTitle = u.is_active ? 'Deactivate' : 'Activate';

      return '<tr data-id="' + u.id + '">' +
        '<td><strong>' + esc(u.name) + '</strong></td>' +
        '<td><span class="role-badge role-badge--admin">' + esc(u.type) + '</span></td>' +
        '<td>' + esc(u.dormitory || '—') + '</td>' +
        '<td>' + esc(u.municipality || '—') + '</td>' +
        '<td>' + esc(u.warden || '—') + '</td>' +
        '<td><span class="status-pill ' + statusClass + '">' + statusText + '</span></td>' +
        '<td class="ta-right"><div class="row-actions">' +
          '<button type="button" class="icon-btn" data-action="edit" title="Edit">' +
            '<i class="bi bi-pencil"></i>' +
          '</button>' +
          '<button type="button" class="icon-btn" data-action="toggle" title="' + toggleTitle + '">' +
            '<i class="bi ' + toggleIcon + '"></i>' +
          '</button>' +
        '</div></td>' +
      '</tr>';
    }).join('');
  }

  /* ============================================================
     LOAD
     ============================================================ */

  function load(force) {
    if (loaded && !force) return;

    tbody.innerHTML = '<tr><td colspan="7" class="data-table__empty">Loading...</td></tr>';

    api('list', { active_only: 0 }).then(function (data) {
      jailUnits = data.jail_units || [];
      loaded = true;
      renderTable();
    }).catch(function (err) {
      tbody.innerHTML = '<tr><td colspan="7" class="data-table__empty">' + esc(err.message) + '</td></tr>';
    });
  }

  /* ============================================================
     MODAL
     ============================================================ */

  function openModal(unit) {
    if (!modal) return;
    form.reset();
    formError.hidden = true;
    formError.textContent = '';

    document.getElementById('jailUnitModalTitle').textContent = unit ? 'Edit Jail Unit' : 'New Jail Unit';

    if (unit) {
      inputId.value           = unit.id;
      inputName.value         = unit.name || '';
      inputType.value         = unit.type || 'Municipal';
      inputDormitory.value    = unit.dormitory || 'Combined';
      inputMunicipality.value = unit.municipality || '';
      inputProvince.value     = unit.province || 'Zamboanga Sibugay';
      inputWarden.value       = unit.warden || '';
      inputIsActive.checked   = !!unit.is_active;
    } else {
      inputId.value           = '';
      inputProvince.value     = 'Zamboanga Sibugay';
      inputIsActive.checked   = true;
    }

    modal.classList.remove('is-hidden');
    document.body.classList.add('modal-open');
    setTimeout(function () { inputName.focus(); }, 50);
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
      if (e.target.closest('[data-jail-unit-close]')) closeModal();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !modal.classList.contains('is-hidden')) closeModal();
    });
  }

  /* ============================================================
     SUBMIT
     ============================================================ */

  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      var payload = {
        id:           inputId.value ? Number(inputId.value) : 0,
        name:         inputName.value.trim(),
        type:         inputType.value,
        dormitory:    inputDormitory.value,
        municipality: inputMunicipality.value.trim(),
        province:     inputProvince.value.trim() || 'Zamboanga Sibugay',
        warden:       inputWarden.value.trim(),
        is_active:    inputIsActive.checked ? 1 : 0
      };

      if (!payload.name) {
        formError.textContent = 'Jail unit name is required.';
        formError.hidden = false;
        return;
      }

      var confirmed = await window.confirmDialog({
        title: payload.id ? 'Save Changes' : 'Create Jail Unit',
        message: payload.id
          ? 'Apply the changes to "' + payload.name + '"?'
          : 'Create a new jail unit "' + payload.name + '"?',
        confirmLabel: payload.id ? 'Save Changes' : 'Create'
      });
      if (!confirmed) return;

      formError.hidden = true;
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      api(payload.id ? 'update' : 'create', null, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      }).then(function () {
        closeModal();
        window.showSuccess({
          title: payload.id ? 'Jail Unit Updated' : 'Jail Unit Created',
          message: payload.name + ' has been ' + (payload.id ? 'updated' : 'created') + '.'
        });
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
      var unit = jailUnits.find(function (u) { return u.id === id; });
      if (!unit) return;

      var action = btn.dataset.action;

      if (action === 'edit') {
        openModal(unit);
        return;
      }

      if (action === 'toggle') {
        var ok = await window.confirmDialog({
          title: unit.is_active ? 'Deactivate Jail Unit' : 'Activate Jail Unit',
          message: (unit.is_active ? 'Deactivate' : 'Activate') + ' "' + unit.name + '"?',
          confirmLabel: unit.is_active ? 'Deactivate' : 'Activate',
          variant: 'warning'
        });
        if (!ok) return;

        api('toggle', null, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id })
        }).then(function () {
          window.showSuccess({
            title: 'Status Updated',
            message: unit.name + ' has been ' + (unit.is_active ? 'deactivated' : 'activated') + '.'
          });
          load(true);
        }).catch(function (err) {
          alert(err.message);
        });
      }
    });
  }

  /* ============================================================
     FILTERS
     ============================================================ */

  var timer;
  if (search) {
    search.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(renderTable, 200);
    });
  }
  if (typeFilter)   typeFilter.addEventListener('change', renderTable);
  if (statusFilter) statusFilter.addEventListener('change', renderTable);

  /* ============================================================
     SECTION EVENTS
     ============================================================ */

  document.addEventListener('section:changed', function (e) {
    if (e.detail && e.detail.section === 'jail-units') {
      load();
    }
  });

  if (section.classList.contains('is-visible')) {
    load();
  }
})();