(function () {
  'use strict';

  var section = document.getElementById('dashboard-headcount');
  if (!section) return;

  /* ---------- Lock state ---------- */
  var lockJailUnit     = section.dataset.lockJailUnit === '1';
  var lockedJailUnitId = Number(section.dataset.jailUnitId) || 0;
  var isAdmin          = section.dataset.isAdmin === '1';

  /* ---------- Toolbar ---------- */
  var tbody              = document.getElementById('headcountTableBody');
  var jailUnitFilter     = document.getElementById('headcountJailUnitFilter');
  var shiftFilter        = document.getElementById('headcountShiftFilter');
  var statusFilter       = document.getElementById('headcountStatusFilter');
  var dateFilter         = document.getElementById('headcountDateFilter');
  var prevBtn            = document.getElementById('headcountPrevBtn');
  var nextBtn            = document.getElementById('headcountNextBtn');
  var summary            = document.getElementById('headcountPaginationSummary');
  var submitBtn          = document.getElementById('headcountSubmitBtn');
  var shiftStrip         = document.getElementById('headcountShiftStrip');
  var archiveToggleBtn   = document.getElementById('headcountArchiveToggleBtn');
  var archiveToggleLabel = document.getElementById('headcountArchiveToggleLabel');

  /* ---------- Modal ---------- */
  var modal     = document.getElementById('headcountModal');
  var form      = document.getElementById('headcountForm');
  var formError = document.getElementById('headcountFormError');
  var saveBtn   = document.getElementById('headcountSaveBtn');

  var inputId       = document.getElementById('headcountId');
  var inputDate     = document.getElementById('headcountDate');
  var inputShift    = document.getElementById('headcountShift');
  var inputExpected = document.getElementById('headcountExpected');
  var inputActual   = document.getElementById('headcountActual');
  var inputRemarks  = document.getElementById('headcountRemarks');
  var inputDiffHint = document.getElementById('headcountDiffHint');

  /* ---------- Jail Unit SearchableSelect ---------- */
  var jailUnitSelect = null;
  var jailUnitWrap   = document.getElementById('headcountJailUnitWrap');
  if (jailUnitWrap) {
    jailUnitSelect = new SearchableSelect({
      container: jailUnitWrap,
      placeholder: 'Select jail unit',
      onChange: function (value) {
        document.getElementById('headcountJailUnitId').value = value || '';
        refreshExpected();
      }
    });
  }

  /* ============================================================
     STATE
     ============================================================ */

  var headcounts      = [];
  var total           = 0;
  var limit           = 20;
  var offset          = 0;
  var loaded          = false;
  var jailUnitsLoaded = false;
  var scope           = 'active'; // 'active' | 'archived'

  /* ============================================================
     HELPERS
     ============================================================ */

  function esc(v) {
    var d = document.createElement('div');
    d.textContent = String(v == null ? '' : v);
    return d.innerHTML;
  }

  function api(action, params, options) {
    var url = 'index.php?route=headcount&action=' + encodeURIComponent(action);
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

  function refApi(action, params) {
    var url = 'index.php?route=reference&action=' + encodeURIComponent(action);
    if (params) {
      Object.keys(params).forEach(function (k) {
        if (params[k] !== '' && params[k] !== null && params[k] !== undefined) {
          url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }
      });
    }
    return fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) {
        return r.json().then(function (data) {
          if (!r.ok || !data.success) throw new Error(data.message || 'Error');
          return data;
        });
      });
  }

  function shiftBadge(shift) {
    switch (shift) {
      case 'Morning':   return 'chip--neutral';
      case 'Afternoon': return 'chip--pending';
      case 'Night':     return 'chip--active';
      default:          return 'chip--neutral';
    }
  }

  function statusPillClass(status) {
    switch (status) {
      case 'Submitted':   return 'status-pill--off';
      case 'Verified':    return 'status-pill--on';
      case 'Discrepancy': return 'status-pill--off';
      default:            return 'status-pill--off';
    }
  }

  function currentShift() {
    var h = new Date().getHours();
    if (h >= 6 && h < 14)  return 'Morning';
    if (h >= 14 && h < 22) return 'Afternoon';
    return 'Night';
  }

  function todayISO() {
    var d = new Date();
    var pad = function (n) { return String(n).padStart(2, '0'); };
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
  }

  /* ============================================================
     JAIL UNIT INIT
     ============================================================ */

  function initJailUnitSelect() {
    if (!jailUnitSelect || jailUnitsLoaded) return Promise.resolve();

    return refApi('jail-units').then(function (data) {
      jailUnitSelect.setItems((data.jail_units || []).map(function (u) {
        return { value: u.id, label: u.name + ' (' + u.dormitory + ')' };
      }));
      jailUnitsLoaded = true;

      if (lockJailUnit && lockedJailUnitId > 0) {
        jailUnitSelect.setValue(lockedJailUnitId);
        jailUnitSelect.setDisabled(true);
        document.getElementById('headcountJailUnitId').value = lockedJailUnitId;
      }
    }).catch(function (err) { console.error('jail units:', err); });
  }

  function loadJailUnitFilter() {
    if (!jailUnitFilter) return;
    if (lockJailUnit) {
      jailUnitFilter.style.display = 'none';
      return;
    }
    refApi('jail-units').then(function (data) {
      (data.jail_units || []).forEach(function (u) {
        var opt = document.createElement('option');
        opt.value = u.id;
        opt.textContent = u.name + ' (' + u.dormitory + ')';
        jailUnitFilter.appendChild(opt);
      });
    }).catch(function () { /* silent */ });
  }

  /* ============================================================
     EXPECTED COUNT
     ============================================================ */

  function refreshExpected() {
    var unitId = document.getElementById('headcountJailUnitId').value || 0;
    api('expected', { jail_unit_id: unitId }).then(function (data) {
      inputExpected.value = data.expected_count || 0;
      updateDiffHint();
    }).catch(function () {
      inputExpected.value = '';
    });
  }

  function updateDiffHint() {
    var expected = parseInt(inputExpected.value, 10) || 0;
    var actual   = parseInt(inputActual.value, 10);

    if (isNaN(actual)) {
      inputDiffHint.textContent = '—';
      inputDiffHint.classList.remove('is-error', 'is-valid');
      return;
    }

    var diff = actual - expected;
    if (diff === 0) {
      inputDiffHint.textContent = 'Matches expected count';
      inputDiffHint.classList.remove('is-error');
      inputDiffHint.classList.add('is-valid');
    } else {
      var sign = diff > 0 ? '+' : '';
      inputDiffHint.textContent = sign + diff + ' vs expected (' + expected + ')';
      inputDiffHint.classList.remove('is-valid');
      inputDiffHint.classList.add('is-error');
    }
  }

  if (inputActual) {
    inputActual.addEventListener('input', updateDiffHint);
  }

  /* ============================================================
     SHIFT STRIP (today's status)
     ============================================================ */

  function loadShiftStrip() {
    api('today').then(function (data) {
      var shifts = data.shifts || {};
      ['Morning', 'Afternoon', 'Night'].forEach(function (s) {
        var pill = shiftStrip ? shiftStrip.querySelector('[data-shift="' + s + '"]') : null;
        if (!pill) return;
        var state = pill.querySelector('.shift-pill__state');
        var status = shifts[s] || 'upcoming';
        if (state) state.textContent = status;
        pill.classList.remove('is-submitted', 'is-pending', 'is-upcoming');
        pill.classList.add('is-' + status);
      });
    }).catch(function () { /* silent */ });
  }

  /* ============================================================
     RENDER
     ============================================================ */

  function render() {
    if (!headcounts.length) {
      var msg = scope === 'archived' ? 'No archived headcounts.' : 'No headcount records found.';
      tbody.innerHTML = '<tr><td colspan="9" class="data-table__empty">' + msg + '</td></tr>';
      return;
    }

    var currentUserId = 0;
    try {
      currentUserId = parseInt(document.body.dataset.userId || '0', 10) || 0;
    } catch (e) {}

    var isArchivedScope = scope === 'archived';

    tbody.innerHTML = headcounts.map(function (h) {
      var diff = h.actual_count - h.expected_count;
      var diffText = diff === 0 ? '0' : (diff > 0 ? '+' + diff : String(diff));
      var diffClass = diff === 0 ? 'meta-text' : 'chip chip--danger';

      var isOwn = currentUserId > 0 && h.recorded_by === currentUserId;
      var isUnverified = h.status === 'Submitted';
      var isArchived = !!h.archived_at;

      var actions = '';

      /* ---------- ARCHIVED SCOPE ---------- */
      if (isArchivedScope) {
        if (isAdmin) {
          // Restore + permanent delete
          actions +=
            '<button type="button" class="icon-btn" data-action="restore" title="Restore">' +
              '<i class="bi bi-arrow-counterclockwise"></i>' +
            '</button>' +
            '<button type="button" class="icon-btn icon-btn--danger" data-action="delete" title="Delete permanently">' +
              '<i class="bi bi-trash"></i>' +
            '</button>';
        }
      }
      /* ---------- ACTIVE SCOPE ---------- */
      else {
        // Admin: verify + flag on unverified
        if (isAdmin && isUnverified) {
          actions +=
            '<button type="button" class="icon-btn" data-action="verify" title="Verify">' +
              '<i class="bi bi-check2"></i>' +
            '</button>' +
            '<button type="button" class="icon-btn icon-btn--danger" data-action="flag" title="Flag discrepancy">' +
              '<i class="bi bi-flag"></i>' +
            '</button>';
        }

        // Edit: admin any unverified, staff own unverified
        var canEdit = isUnverified && (isAdmin || isOwn);
        if (canEdit) {
          actions +=
            '<button type="button" class="icon-btn" data-action="edit" title="Edit">' +
              '<i class="bi bi-pencil"></i>' +
            '</button>';
        }

        // Archive: admin any, staff own unverified
        var canArchive = isAdmin || (isOwn && isUnverified);
        if (canArchive) {
          actions +=
            '<button type="button" class="icon-btn icon-btn--archive" data-action="archive" title="Archive">' +
              '<i class="bi bi-archive"></i>' +
            '</button>';
        }
      }

      return '<tr data-id="' + h.id + '">' +
        '<td>' + esc(h.recorded_on) + '</td>' +
        '<td><span class="chip ' + shiftBadge(h.shift) + '">' + esc(h.shift) + '</span></td>' +
        '<td class="meta-text">' + esc(h.jail_unit_name || '—') + '</td>' +
        '<td class="ta-right">' + h.expected_count + '</td>' +
        '<td class="ta-right"><strong>' + h.actual_count + '</strong></td>' +
        '<td class="ta-right"><span class="' + diffClass + '">' + esc(diffText) + '</span></td>' +
        '<td><span class="status-pill ' + statusPillClass(h.status) + '">' + esc(h.status) + '</span></td>' +
        '<td class="meta-text">' + esc(h.recorded_by_name || '—') + '</td>' +
        '<td class="ta-right"><div class="row-actions">' + actions + '</div></td>' +
      '</tr>';
    }).join('');
  }

  function renderPagination() {
    var showing = Math.min(offset + headcounts.length, total);
    var from = total === 0 ? 0 : offset + 1;

    if (summary) {
      summary.textContent = 'Showing ' + from + '–' + showing + ' of ' + total + ' records';
    }
    if (prevBtn) prevBtn.disabled = offset <= 0;
    if (nextBtn) nextBtn.disabled = (offset + limit) >= total;
  }

  /* ============================================================
     LOAD
     ============================================================ */

  function load(force) {
    if (loaded && !force) return;

    tbody.innerHTML = '<tr><td colspan="9" class="data-table__empty">Loading...</td></tr>';

    var params = {
      jail_unit_id: jailUnitFilter ? jailUnitFilter.value : '',
      shift:        shiftFilter ? shiftFilter.value : '',
      status:       statusFilter ? statusFilter.value : '',
      date:         dateFilter ? dateFilter.value : '',
      scope:        scope,
      limit:        limit,
      offset:       offset
    };

    api('list', params).then(function (data) {
      headcounts = data.headcounts || [];
      total = data.total || 0;
      loaded = true;
      render();
      renderPagination();
    }).catch(function (err) {
      tbody.innerHTML = '<tr><td colspan="9" class="data-table__empty">' + esc(err.message) + '</td></tr>';
      if (summary) summary.textContent = '';
    });
  }

  /* ============================================================
     MODAL
     ============================================================ */

  function openModal(row) {
    if (!modal) return;

    form.reset();
    formError.hidden = true;
    formError.textContent = '';

    var isEdit = !!row;

    inputId.value = isEdit ? row.id : '';

    var titleEl = document.getElementById('headcountModalTitle');
    if (titleEl) {
      titleEl.textContent = isEdit ? 'Edit Headcount' : 'Submit Headcount';
    }
    saveBtn.textContent = isEdit ? 'Save Changes' : 'Submit';

    inputDate.value     = isEdit ? row.recorded_on : todayISO();
    inputShift.value    = isEdit ? row.shift : currentShift();
    inputActual.value   = isEdit ? row.actual_count : '';
    inputRemarks.value  = isEdit ? (row.remarks || '') : '';
    inputExpected.value = isEdit ? row.expected_count : '';

    inputDiffHint.textContent = '—';
    inputDiffHint.classList.remove('is-error', 'is-valid');

    initJailUnitSelect().then(function () {
      if (!jailUnitSelect) return;

      if (isEdit) {
        jailUnitSelect.setValue(row.jail_unit_id);
        jailUnitSelect.setDisabled(true);
        document.getElementById('headcountJailUnitId').value = row.jail_unit_id;
      } else if (lockJailUnit) {
        jailUnitSelect.setValue(lockedJailUnitId);
        jailUnitSelect.setDisabled(true);
        document.getElementById('headcountJailUnitId').value = lockedJailUnitId;
      } else {
        jailUnitSelect.clear();
        jailUnitSelect.setDisabled(false);
        document.getElementById('headcountJailUnitId').value = '';
      }

      if (!isEdit) refreshExpected();
      else updateDiffHint();
    });

    modal.classList.remove('is-hidden');
    document.body.classList.add('modal-open');
    setTimeout(function () { inputActual.focus(); }, 50);
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.add('is-hidden');
    document.body.classList.remove('modal-open');
    var titleEl = document.getElementById('headcountModalTitle');
    if (titleEl) titleEl.textContent = 'Submit Headcount';
    if (saveBtn) saveBtn.textContent = 'Submit';
  }

  if (submitBtn) {
    submitBtn.addEventListener('click', function () {
      openModal(null);
    });
  }

  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target.closest('[data-headcount-close]')) closeModal();
    });
  }

  /* ============================================================
     SUBMIT / UPDATE
     ============================================================ */

  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      var payload = {
        id:           inputId.value ? Number(inputId.value) : 0,
        shift:        inputShift.value,
        recorded_on:  inputDate.value,
        actual_count: inputActual.value,
        remarks:      inputRemarks.value.trim(),
        jail_unit_id: document.getElementById('headcountJailUnitId').value
      };

      if (!payload.recorded_on) {
        formError.textContent = 'Date is required.';
        formError.hidden = false;
        return;
      }
      if (!payload.jail_unit_id) {
        formError.textContent = 'Please select a jail unit.';
        formError.hidden = false;
        return;
      }
      if (payload.actual_count === '' || isNaN(parseInt(payload.actual_count, 10))) {
        formError.textContent = 'Actual count is required.';
        formError.hidden = false;
        return;
      }

      var confirmed = await window.confirmDialog({
        title: payload.id ? 'Save Changes' : 'Submit Headcount',
        message: payload.id
          ? 'Save changes to the ' + payload.shift + ' headcount for ' + payload.recorded_on + '?'
          : 'Submit the ' + payload.shift + ' headcount for ' + payload.recorded_on + '?',
        confirmLabel: payload.id ? 'Save Changes' : 'Submit'
      });
      if (!confirmed) return;

      formError.hidden = true;
      saveBtn.disabled = true;
      saveBtn.textContent = payload.id ? 'Saving...' : 'Submitting...';

      var apiAction = payload.id ? 'update' : 'submit';
      var successTitle = payload.id ? 'Headcount Updated' : 'Headcount Submitted';
      var successMsg = payload.id
        ? 'The ' + payload.shift + ' headcount has been updated.'
        : 'The ' + payload.shift + ' headcount has been recorded.';

      api(apiAction, null, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      }).then(function () {
        closeModal();
        window.showSuccess({
          title: successTitle,
          message: successMsg
        });
        loadShiftStrip();
        load(true);
      }).catch(function (err) {
        formError.textContent = err.message;
        formError.hidden = false;
      }).finally(function () {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Submit';
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
      var row = headcounts.find(function (h) { return h.id === id; });
      if (!row) return;

      var action = btn.dataset.action;

      /* ---------- EDIT ---------- */
      if (action === 'edit') {
        openModal(row);
        return;
      }

      /* ---------- VERIFY ---------- */
      if (action === 'verify') {
        var okV = await window.confirmDialog({
          title: 'Verify Headcount',
          message: 'Mark the ' + row.shift + ' count on ' + row.recorded_on + ' as verified?',
          confirmLabel: 'Verify'
        });
        if (!okV) return;

        api('verify', null, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id, discrepancy: false })
        }).then(function () {
          window.showSuccess({ title: 'Verified', message: 'Headcount verified.' });
          loadShiftStrip();
          load(true);
        }).catch(function (err) { alert(err.message); });
        return;
      }

      /* ---------- FLAG ---------- */
      if (action === 'flag') {
        var okF = await window.confirmDialog({
          title: 'Flag Discrepancy',
          message: 'Flag this headcount as a discrepancy?',
          confirmLabel: 'Flag',
          variant: 'warning'
        });
        if (!okF) return;

        api('verify', null, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id, discrepancy: true })
        }).then(function () {
          window.showSuccess({ title: 'Flagged', message: 'Headcount flagged as discrepancy.' });
          loadShiftStrip();
          load(true);
        }).catch(function (err) { alert(err.message); });
        return;
      }

      /* ---------- ARCHIVE ---------- */
      if (action === 'archive') {
        var okA = await window.confirmDialog({
          title: 'Archive Headcount',
          message: 'Archive the ' + row.shift + ' headcount on ' + row.recorded_on + '? You can restore it later.',
          confirmLabel: 'Archive',
          variant: 'warning'
        });
        if (!okA) return;

        api('archive', null, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id })
        }).then(function () {
          window.showSuccess({ title: 'Archived', message: 'Headcount archived.' });
          loadShiftStrip();
          load(true);
        }).catch(function (err) { alert(err.message); });
        return;
      }

      /* ---------- RESTORE ---------- */
      if (action === 'restore') {
        var okR = await window.confirmDialog({
          title: 'Restore Headcount',
          message: 'Restore the ' + row.shift + ' headcount on ' + row.recorded_on + ' back to active?',
          confirmLabel: 'Restore'
        });
        if (!okR) return;

        api('restore', null, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id })
        }).then(function () {
          window.showSuccess({ title: 'Restored', message: 'Headcount restored.' });
          loadShiftStrip();
          load(true);
        }).catch(function (err) { alert(err.message); });
        return;
      }

      /* ---------- PERMANENT DELETE ---------- */
      if (action === 'delete') {
        var okD = await window.confirmDialog({
          title: 'Delete Permanently',
          message: 'Permanently delete this headcount? This cannot be undone.',
          confirmLabel: 'Delete Forever',
          variant: 'danger'
        });
        if (!okD) return;

        api('delete', null, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id })
        }).then(function () {
          window.showSuccess({ title: 'Deleted', message: 'Headcount permanently deleted.' });
          loadShiftStrip();
          load(true);
        }).catch(function (err) { alert(err.message); });
      }
    });
  }

  /* ============================================================
     ARCHIVE TOGGLE
     ============================================================ */

  if (archiveToggleBtn) {
    archiveToggleBtn.addEventListener('click', function () {
      scope = scope === 'archived' ? 'active' : 'archived';
      loaded = false;

      if (archiveToggleLabel) {
        archiveToggleLabel.textContent = scope === 'archived' ? 'View Active' : 'View Archived';
      }

      // Hide the Submit button when viewing archived rows
      if (submitBtn) submitBtn.hidden = scope === 'archived';

      load(true);
    });
  }

  /* ============================================================
     FILTERS / PAGINATION
     ============================================================ */

  [jailUnitFilter, shiftFilter, statusFilter, dateFilter].forEach(function (el) {
    if (el) el.addEventListener('change', function () { offset = 0; load(true); });
  });

  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      offset = Math.max(0, offset - limit);
      load(true);
    });
  }
  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      if (offset + limit < total) {
        offset += limit;
        load(true);
      }
    });
  }

  /* ============================================================
     SECTION EVENTS
     ============================================================ */

  document.addEventListener('section:changed', function (e) {
    if (e.detail && e.detail.section === 'headcount') {
      loadJailUnitFilter();
      initJailUnitSelect();
      loadShiftStrip();
      load();
    }
  });

  loadJailUnitFilter();
  initJailUnitSelect();
  loadShiftStrip();
  if (section.classList.contains('is-visible')) load();
})();