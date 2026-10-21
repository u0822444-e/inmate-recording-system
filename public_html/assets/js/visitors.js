(function () {
  'use strict';

  var section = document.getElementById('dashboard-visitors');
  if (!section) return;

  /* ---------- Toolbar ---------- */
  var tbody          = document.getElementById('visitorTableBody');
  var search         = document.getElementById('visitorSearch');
  var jailUnitFilter = document.getElementById('visitorJailUnitFilter');
  var statusFilter   = document.getElementById('visitorStatusFilter');
  var dateFilter     = document.getElementById('visitorDateFilter');
  var prevBtn        = document.getElementById('visitorPrevBtn');
  var nextBtn        = document.getElementById('visitorNextBtn');
  var summary        = document.getElementById('visitorPaginationSummary');
  var createBtn      = document.getElementById('visitorCreateBtn');

  /* ---------- Modal ---------- */
  var modal     = document.getElementById('visitorModal');
  var form      = document.getElementById('visitorForm');
  var formError = document.getElementById('visitorFormError');
  var saveBtn   = document.getElementById('visitorSaveBtn');

  var inputId          = document.getElementById('visitorId');
  var inputFullName    = document.getElementById('visitorFullName');
  var selectRelationship = document.getElementById('visitorRelationship');
  var relationshipOtherWrap = document.getElementById('visitorRelationshipOtherWrap');
  var relationshipOtherInput = document.getElementById('visitorRelationshipOther');
  var inputContact     = document.getElementById('visitorContactNo');
  var inputContactHint = document.getElementById('visitorContactHint');
  var inputAddress     = document.getElementById('visitorAddress');
  var inputIdType      = document.getElementById('visitorIdType');
  var inputIdTypeHint  = document.getElementById('visitorIdTypeHint');
  var inputIdNumber    = document.getElementById('visitorIdNumber');
  var inputIdNumberHint = document.getElementById('visitorIdNumberHint');
  var selectPurpose    = document.getElementById('visitorPurpose');
  var purposeOtherWrap = document.getElementById('visitorPurposeOtherWrap');
  var purposeOtherInput = document.getElementById('visitorPurposeOther');
  var inputTimeIn      = document.getElementById('visitorTimeIn');
  var inputTimeOut     = document.getElementById('visitorTimeOut');
  var inputNotes       = document.getElementById('visitorNotes');

  var isAdmin = section.dataset.isAdmin === '1';

  /* ---------- PDL searchable select ---------- */
  var inmateSelect = new SearchableSelect({
    container: document.getElementById('visitorInmateWrap'),
    placeholder: 'Select PDL',
    onChange: function (value) {
      document.getElementById('visitorInmateId').value = value || '';
    }
  });

  /* ============================================================
     STATE
     ============================================================ */

  var visitors      = [];
  var total         = 0;
  var limit         = 20;
  var offset        = 0;
  var loaded        = false;
  var inmatesLoaded = false;

  /* ============================================================
     HELPERS
     ============================================================ */

  function esc(v) {
    var d = document.createElement('div');
    d.textContent = String(v == null ? '' : v);
    return d.innerHTML;
  }

  function api(action, params, options) {
    var url = 'index.php?route=visitors&action=' + encodeURIComponent(action);
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

  function formatDateTime(iso) {
    if (!iso) return '—';
    var d = new Date(String(iso).replace(' ', 'T'));
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleString('en-US', {
      month: 'short', day: 'numeric', year: 'numeric',
      hour: 'numeric', minute: '2-digit'
    });
  }

  function toDatetimeLocal(iso) {
    if (!iso) return '';
    var d = new Date(String(iso).replace(' ', 'T'));
    if (isNaN(d.getTime())) return '';
    var pad = function (n) { return String(n).padStart(2, '0'); };
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
      'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
  }

  /* ============================================================
     VALIDATION — CONTACT
     ============================================================ */

  var PHONE_REGEX = /^09[0-9]{9}$/;

  function validatePhone(value) {
    if (!value) return true;
    var clean = value.replace(/[\s\-()]/g, '');
    return PHONE_REGEX.test(clean);
  }

  if (inputContact) {
    inputContact.addEventListener('input', function () {
      var v = inputContact.value.trim();
      if (!v) {
        inputContact.classList.remove('is-invalid', 'is-valid');
        if (inputContactHint) {
          inputContactHint.textContent = 'Optional · e.g. 09171234567';
          inputContactHint.classList.remove('is-error', 'is-valid');
        }
        return;
      }
      if (!validatePhone(v)) {
        inputContact.classList.remove('is-valid');
        inputContact.classList.add('is-invalid');
        if (inputContactHint) {
          inputContactHint.textContent = 'Invalid phone (e.g., 09171234567)';
          inputContactHint.classList.add('is-error');
          inputContactHint.classList.remove('is-valid');
        }
      } else {
        inputContact.classList.remove('is-invalid');
        inputContact.classList.add('is-valid');
        if (inputContactHint) {
          inputContactHint.textContent = 'Valid';
          inputContactHint.classList.add('is-valid');
          inputContactHint.classList.remove('is-error');
        }
      }
    });
  }

  /* ============================================================
     VALIDATION — ID
     ============================================================ */

  function validateIdNumber(idType, idNumber) {
    if (!idNumber) return true;

    var clean = idNumber.replace(/[\s\-]/g, '').toUpperCase();

    switch (idType) {
      case 'National ID':
      case 'UMID':
      case "Voter's ID":
        return /^[0-9]{8,16}$/.test(clean);

      case "Driver's License":
        return /^[A-Z][0-9]{9,13}$/.test(clean);

      case 'Passport':
        return /^[A-Z][0-9]{6,9}$/.test(clean);

      case 'PRC ID':
        return /^[0-9]{6,8}$/.test(clean);

      case 'Barangay ID':
      case 'Other':
        return /^[A-Z0-9]{3,30}$/.test(clean);

      default:
        return clean.length >= 3;
    }
  }

  function updateIdHint(inputEl, hintEl, message, state) {
    if (!inputEl || !hintEl) return;
    inputEl.classList.remove('is-invalid', 'is-valid');
    hintEl.classList.remove('is-error', 'is-valid');

    if (state === 'error') {
      inputEl.classList.add('is-invalid');
      hintEl.classList.add('is-error');
    } else if (state === 'valid') {
      inputEl.classList.add('is-valid');
      hintEl.classList.add('is-valid');
    }

    hintEl.textContent = message;
  }

  function refreshIdValidation() {
    if (!inputIdType || !inputIdNumber) return;

    var type   = inputIdType.value;
    var number = inputIdNumber.value.trim();

    if (type && !number) {
      updateIdHint(inputIdNumber, inputIdNumberHint,
        'ID number is required when a type is selected', 'error');
      updateIdHint(inputIdType, inputIdTypeHint,
        'Type selected — enter the number', null);
      return;
    }

    if (!type && number) {
      updateIdHint(inputIdType, inputIdTypeHint,
        'Please choose the ID type', 'error');
      updateIdHint(inputIdNumber, inputIdNumberHint,
        'Number entered — pick a type', null);
      return;
    }

    if (!type && !number) {
      updateIdHint(inputIdType, inputIdTypeHint, 'Optional', null);
      updateIdHint(inputIdNumber, inputIdNumberHint, 'Optional', null);
      return;
    }

    var ok = validateIdNumber(type, number);
    if (ok) {
      updateIdHint(inputIdType, inputIdTypeHint, 'Type set', 'valid');
      updateIdHint(inputIdNumber, inputIdNumberHint, 'Valid', 'valid');
    } else {
      updateIdHint(inputIdType, inputIdTypeHint, 'Type set', null);
      updateIdHint(inputIdNumber, inputIdNumberHint,
        'Invalid format for ' + type, 'error');
    }
  }

  if (inputIdType) {
    inputIdType.addEventListener('change', refreshIdValidation);
  }
  if (inputIdNumber) {
    inputIdNumber.addEventListener('input', refreshIdValidation);
  }

  /* ============================================================
     "Others" reveal — Relationship
     ============================================================ */

  if (selectRelationship) {
    selectRelationship.addEventListener('change', function () {
      if (selectRelationship.value === 'Others') {
        relationshipOtherWrap.style.display = '';
        if (relationshipOtherInput) relationshipOtherInput.focus();
      } else {
        relationshipOtherWrap.style.display = 'none';
        if (relationshipOtherInput) relationshipOtherInput.value = '';
      }
    });
  }

  /* ============================================================
     "Others" reveal — Purpose
     ============================================================ */

  if (selectPurpose) {
    selectPurpose.addEventListener('change', function () {
      if (selectPurpose.value === 'Others') {
        purposeOtherWrap.style.display = '';
        if (purposeOtherInput) purposeOtherInput.focus();
      } else {
        purposeOtherWrap.style.display = 'none';
        if (purposeOtherInput) purposeOtherInput.value = '';
      }
    });
  }

  /* ============================================================
     RESOLVE FINAL VALUES
     ============================================================ */

  function resolveRelationship() {
    if (!selectRelationship) return '';
    if (selectRelationship.value === 'Others') {
      return relationshipOtherInput ? relationshipOtherInput.value.trim() : '';
    }
    return selectRelationship.value;
  }

  function resolvePurpose() {
    if (!selectPurpose) return '';
    if (selectPurpose.value === 'Others') {
      return purposeOtherInput ? purposeOtherInput.value.trim() : '';
    }
    return selectPurpose.value;
  }

  /* ============================================================
     PDL LIST
     ============================================================ */

  function loadAllInmates() {
    if (inmatesLoaded) return;
    refApi('inmates-by-unit', {}).then(function (data) {
      inmateSelect.setItems((data.inmates || []).map(function (i) {
        var label = i.inmate_number + ' · ' + i.full_name;
        if (i.jail_unit_name) label += ' — ' + i.jail_unit_name;
        return { value: i.id, label: label };
      }));
      inmatesLoaded = true;
    }).catch(function (err) {
      console.error('inmates-by-unit:', err);
    });
  }

  /* ============================================================
     JAIL UNIT FILTER
     ============================================================ */

  function loadJailUnitFilter() {
    if (!jailUnitFilter) return;
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
     RENDER
     ============================================================ */

  function render() {
    if (!visitors.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="data-table__empty">No visitor records found.</td></tr>';
      return;
    }

    tbody.innerHTML = visitors.map(function (v) {
      var inside = v.time_out === null;
      var statusClass = inside ? 'status-pill--on' : 'status-pill--off';
      var statusText  = inside ? 'Inside' : 'Checked Out';

      var pdlLine = v.inmate_name
        ? esc(v.inmate_name) + (v.inmate_number ? '<br><small class="meta-text">' + esc(v.inmate_number) + '</small>' : '')
        : '—';

      var actions = '';
      if (inside) {
        actions +=
          '<button type="button" class="icon-btn" data-action="checkout" title="Check out">' +
            '<i class="bi bi-box-arrow-right"></i>' +
          '</button>';
      }
      actions +=
        '<button type="button" class="icon-btn" data-action="edit" title="Edit">' +
          '<i class="bi bi-pencil"></i>' +
        '</button>';
      if (isAdmin) {
        actions +=
          '<button type="button" class="icon-btn icon-btn--danger" data-action="delete" title="Delete">' +
            '<i class="bi bi-trash"></i>' +
          '</button>';
      }

      return '<tr data-id="' + v.id + '">' +
        '<td><strong>' + esc(v.full_name) + '</strong>' +
          (v.id_number ? '<br><small class="meta-text">ID: ' + esc(v.id_number) + '</small>' : '') +
        '</td>' +
        '<td>' + pdlLine + '</td>' +
        '<td>' + esc(v.relationship || '—') + '</td>' +
        '<td class="meta-text">' + esc(v.jail_unit_name || '—') + '</td>' +
        '<td>' + formatDateTime(v.time_in) + '</td>' +
        '<td>' + formatDateTime(v.time_out) + '</td>' +
        '<td><span class="status-pill ' + statusClass + '">' + statusText + '</span></td>' +
        '<td class="ta-right"><div class="row-actions">' + actions + '</div></td>' +
      '</tr>';
    }).join('');
  }

  function renderPagination() {
    var showing = Math.min(offset + visitors.length, total);
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

    tbody.innerHTML = '<tr><td colspan="8" class="data-table__empty">Loading...</td></tr>';

    var params = {
      q:            search ? search.value.trim() : '',
      jail_unit_id: jailUnitFilter ? jailUnitFilter.value : '',
      status:       statusFilter ? statusFilter.value : '',
      date:         dateFilter ? dateFilter.value : '',
      limit:        limit,
      offset:       offset
    };

    api('list', params).then(function (data) {
      visitors = data.visitors || [];
      total = data.total || 0;
      loaded = true;
      render();
      renderPagination();
    }).catch(function (err) {
      tbody.innerHTML = '<tr><td colspan="8" class="data-table__empty">' + esc(err.message) + '</td></tr>';
      if (summary) summary.textContent = '';
    });
  }

  /* ============================================================
     MODAL
     ============================================================ */

  function openModal(visitor) {
    if (!modal) return;
    form.reset();
    formError.hidden = true;
    formError.textContent = '';

    inmateSelect.clear();
    document.getElementById('visitorInmateId').value = '';

    if (inputContact) inputContact.classList.remove('is-invalid', 'is-valid');
    if (inputContactHint) {
      inputContactHint.textContent = 'Optional · e.g. 09171234567';
      inputContactHint.classList.remove('is-error', 'is-valid');
    }

    updateIdHint(inputIdType, inputIdTypeHint, 'Optional', null);
    updateIdHint(inputIdNumber, inputIdNumberHint, 'Optional', null);

    loadAllInmates();

    document.getElementById('visitorModalTitle').textContent = visitor ? 'Edit Visitor' : 'Log Visitor';
    inputId.value = visitor ? visitor.id : '';

    if (visitor) {
      inputFullName.value = visitor.full_name || '';
      inputContact.value  = visitor.contact_no || '';
      inputAddress.value  = visitor.address || '';
      inputIdType.value   = visitor.id_type || '';
      inputIdNumber.value = visitor.id_number || '';
      inputTimeIn.value   = toDatetimeLocal(visitor.time_in);
      inputTimeOut.value  = toDatetimeLocal(visitor.time_out);
      inputNotes.value    = visitor.notes || '';

      // Relationship
      var rel = visitor.relationship || '';
      var knownRel = false;
      if (selectRelationship) {
        for (var i = 0; i < selectRelationship.options.length; i++) {
          if (selectRelationship.options[i].value === rel && rel !== '') {
            knownRel = true;
            break;
          }
        }
      }
      if (knownRel) {
        selectRelationship.value = rel;
        relationshipOtherWrap.style.display = 'none';
        relationshipOtherInput.value = '';
      } else if (rel !== '') {
        selectRelationship.value = 'Others';
        relationshipOtherWrap.style.display = '';
        relationshipOtherInput.value = rel;
      } else {
        selectRelationship.value = '';
        relationshipOtherWrap.style.display = 'none';
        relationshipOtherInput.value = '';
      }

      // Purpose
      var purp = visitor.purpose || '';
      var knownPurp = false;
      if (selectPurpose) {
        for (var j = 0; j < selectPurpose.options.length; j++) {
          if (selectPurpose.options[j].value === purp && purp !== '') {
            knownPurp = true;
            break;
          }
        }
      }
      if (knownPurp) {
        selectPurpose.value = purp;
        purposeOtherWrap.style.display = 'none';
        purposeOtherInput.value = '';
      } else if (purp !== '') {
        selectPurpose.value = 'Others';
        purposeOtherWrap.style.display = '';
        purposeOtherInput.value = purp;
      } else {
        selectPurpose.value = '';
        purposeOtherWrap.style.display = 'none';
        purposeOtherInput.value = '';
      }

      refreshIdValidation();

      if (visitor.inmate_id) {
        setTimeout(function () {
          inmateSelect.setValue(visitor.inmate_id);
          document.getElementById('visitorInmateId').value = visitor.inmate_id;
        }, 250);
      }
    } else {
      // New visitor — reset everything
      if (selectRelationship) {
        selectRelationship.value = '';
        relationshipOtherWrap.style.display = 'none';
        relationshipOtherInput.value = '';
      }
      if (selectPurpose) {
        selectPurpose.value = '';
        purposeOtherWrap.style.display = 'none';
        purposeOtherInput.value = '';
      }

      var now = new Date();
      var pad = function (n) { return String(n).padStart(2, '0'); };
      inputTimeIn.value = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) +
        'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
    }

    modal.classList.remove('is-hidden');
    document.body.classList.add('modal-open');
    setTimeout(function () { inputFullName.focus(); }, 50);
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
      if (e.target.closest('[data-visitor-close]')) closeModal();
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
        full_name:    inputFullName.value.trim(),
        relationship: resolveRelationship(),
        contact_no:   inputContact.value.trim(),
        address:      inputAddress.value.trim(),
        id_type:      inputIdType.value,
        id_number:    inputIdNumber.value.trim(),
        inmate_id:    document.getElementById('visitorInmateId').value,
        purpose:      resolvePurpose(),
        time_in:      inputTimeIn.value ? inputTimeIn.value.replace('T', ' ') + ':00' : '',
        time_out:     inputTimeOut.value ? inputTimeOut.value.replace('T', ' ') + ':00' : '',
        notes:        inputNotes.value.trim()
      };

      if (!payload.full_name) {
        formError.textContent = 'Visitor name is required.';
        formError.hidden = false;
        return;
      }
      if (!payload.inmate_id) {
        formError.textContent = 'Please select the PDL being visited.';
        formError.hidden = false;
        return;
      }
      if (selectRelationship && selectRelationship.value === 'Others' && payload.relationship === '') {
        formError.textContent = 'Please specify the relationship.';
        formError.hidden = false;
        if (relationshipOtherInput) relationshipOtherInput.focus();
        return;
      }
      if (selectPurpose && selectPurpose.value === 'Others' && payload.purpose === '') {
        formError.textContent = 'Please specify the purpose.';
        formError.hidden = false;
        if (purposeOtherInput) purposeOtherInput.focus();
        return;
      }
      if (!validatePhone(payload.contact_no)) {
        formError.textContent = 'Please enter a valid contact number (e.g., 09171234567).';
        formError.hidden = false;
        if (inputContact) inputContact.focus();
        return;
      }

      // ID validation
      if (payload.id_type && !payload.id_number) {
        formError.textContent = 'Please enter the ID number.';
        formError.hidden = false;
        if (inputIdNumber) inputIdNumber.focus();
        return;
      }
      if (!payload.id_type && payload.id_number) {
        formError.textContent = 'Please choose the ID type.';
        formError.hidden = false;
        if (inputIdType) inputIdType.focus();
        return;
      }
      if (payload.id_type && payload.id_number && !validateIdNumber(payload.id_type, payload.id_number)) {
        formError.textContent = 'Invalid format for ' + payload.id_type + '.';
        formError.hidden = false;
        if (inputIdNumber) inputIdNumber.focus();
        return;
      }

      var confirmed = await window.confirmDialog({
        title: payload.id ? 'Save Changes' : 'Log Visitor',
        message: payload.id
          ? 'Apply the changes to "' + payload.full_name + '"?'
          : 'Log visitor "' + payload.full_name + '"?',
        confirmLabel: payload.id ? 'Save Changes' : 'Log Visitor'
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
          title: payload.id ? 'Visitor Updated' : 'Visitor Logged',
          message: payload.full_name + ' has been ' + (payload.id ? 'updated' : 'logged') + '.'
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
      var visitor = visitors.find(function (v) { return v.id === id; });
      if (!visitor) return;

      var action = btn.dataset.action;

      if (action === 'edit') { openModal(visitor); return; }

      if (action === 'checkout') {
        var ok = await window.confirmDialog({
          title: 'Check Out Visitor',
          message: 'Record "' + visitor.full_name + '" as checked out now?',
          confirmLabel: 'Check Out'
        });
        if (!ok) return;

        api('checkout', null, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id })
        }).then(function () {
          window.showSuccess({
            title: 'Visitor Checked Out',
            message: visitor.full_name + ' has been checked out.'
          });
          load(true);
        }).catch(function (err) { alert(err.message); });
        return;
      }

      if (action === 'delete') {
        var ok2 = await window.confirmDialog({
          title: 'Delete Visitor Record',
          message: 'Permanently delete the record for "' + visitor.full_name + '"?',
          confirmLabel: 'Delete',
          variant: 'danger'
        });
        if (!ok2) return;

        api('delete', null, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id })
        }).then(function () {
          window.showSuccess({
            title: 'Record Deleted',
            message: 'Visitor record has been deleted.'
          });
          load(true);
        }).catch(function (err) { alert(err.message); });
      }
    });
  }

  /* ============================================================
     FILTERS / PAGINATION
     ============================================================ */

  var timer;
  if (search) {
    search.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () { offset = 0; load(true); }, 250);
    });
  }
  if (jailUnitFilter) jailUnitFilter.addEventListener('change', function () { offset = 0; load(true); });
  if (statusFilter)   statusFilter.addEventListener('change', function () { offset = 0; load(true); });
  if (dateFilter)     dateFilter.addEventListener('change', function () { offset = 0; load(true); });

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
    if (e.detail && e.detail.section === 'visitors') {
      loadJailUnitFilter();
      loadAllInmates();
      load();
    }
  });

  loadJailUnitFilter();
  loadAllInmates();
  if (section.classList.contains('is-visible')) load();

    document.addEventListener("visitors:open-modal", function () {
    var btn = document.getElementById("visitorCreateBtn");
    if (btn) btn.click();
  });
})();