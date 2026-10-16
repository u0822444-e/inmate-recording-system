(function () {
  'use strict';

  var section = document.getElementById('dashboard-incidents');
  if (!section) return;

  /* ---------- Lock state ---------- */
  var lockJailUnit     = section.dataset.lockJailUnit === '1';
  var lockedJailUnitId = Number(section.dataset.jailUnitId) || 0;

  /* ---------- Toolbar ---------- */
  var tbody          = document.getElementById('incidentTableBody');
  var search         = document.getElementById('incidentSearch');
  var jailUnitFilter = document.getElementById('incidentJailUnitFilter');
  var typeFilter     = document.getElementById('incidentTypeFilter');
  var severityFilter = document.getElementById('incidentSeverityFilter');
  var statusFilter   = document.getElementById('incidentStatusFilter');
  var dateFilter     = document.getElementById('incidentDateFilter');
  var prevBtn        = document.getElementById('incidentPrevBtn');
  var nextBtn        = document.getElementById('incidentNextBtn');
  var summary        = document.getElementById('incidentPaginationSummary');
  var createBtn      = document.getElementById('incidentCreateBtn');

  /* ---------- Modal ---------- */
  var modal     = document.getElementById('incidentModal');
  var form      = document.getElementById('incidentForm');
  var formError = document.getElementById('incidentFormError');
  var saveBtn   = document.getElementById('incidentSaveBtn');

  var inputId           = document.getElementById('incidentId');
  var inputDate         = document.getElementById('incidentDate');
  var inputTime         = document.getElementById('incidentTime');
  var inputType         = document.getElementById('incidentType');
  var inputSeverity     = document.getElementById('incidentSeverity');
  var inputSpot         = document.getElementById('incidentSpot');
  var inputDescription  = document.getElementById('incidentDescription');
  var inputPersons      = document.getElementById('incidentPersons');
  var inputWitnesses    = document.getElementById('incidentWitnesses');
  var inputStatus       = document.getElementById('incidentStatus');
  var inputResolvedAt   = document.getElementById('incidentResolvedAt');
  var inputActionTaken  = document.getElementById('incidentActionTaken');
  var resolutionSection = document.getElementById('incidentResolutionSection');

  var inputProvinceValue  = document.getElementById('incidentProvinceValue');
  var inputMunicipalityId = document.getElementById('incidentMunicipalityId');
  var inputBarangayId     = document.getElementById('incidentBarangayId');

  var isAdmin = section.dataset.isAdmin === '1';

  /* ============================================================
     SEARCHABLE SELECTS
     ============================================================ */

  var jailUnitSelect = null;
  var jailUnitWrap   = document.getElementById('incidentJailUnitWrap');
  if (jailUnitWrap) {
    jailUnitSelect = new SearchableSelect({
      container: jailUnitWrap,
      placeholder: 'Select jail unit',
      onChange: function (value) {
        document.getElementById('incidentJailUnitId').value = value || '';
      }
    });
  }

  var provinceSelect = new SearchableSelect({
    container: document.getElementById('incidentProvinceWrap'),
    placeholder: 'Select province',
    onChange: function (value) {
      inputProvinceValue.value = value || '';

      municipalitySelect.clear();
      municipalitySelect.setItems([]);
      municipalitySelect.setDisabled(true);

      barangaySelect.clear();
      barangaySelect.setItems([]);
      barangaySelect.setDisabled(true);

      inputMunicipalityId.value = '';
      inputBarangayId.value = '';

      if (value) loadMunicipalities(value);
    }
  });

  var municipalitySelect = new SearchableSelect({
    container: document.getElementById('incidentMunicipalityWrap'),
    placeholder: 'Select municipality',
    onChange: function (value) {
      inputMunicipalityId.value = value || '';

      barangaySelect.clear();
      barangaySelect.setItems([]);
      barangaySelect.setDisabled(true);
      inputBarangayId.value = '';

      if (value) loadBarangays(value);
    }
  });

  var barangaySelect = new SearchableSelect({
    container: document.getElementById('incidentBarangayWrap'),
    placeholder: 'Select barangay',
    onChange: function (value) {
      inputBarangayId.value = value || '';
    }
  });

  municipalitySelect.setDisabled(true);
  barangaySelect.setDisabled(true);

  /* ============================================================
     STATE
     ============================================================ */

  var incidents    = [];
  var total        = 0;
  var limit        = 20;
  var offset       = 0;
  var loaded       = false;
  var jailUnitsLoaded = false;

  /* ============================================================
     HELPERS
     ============================================================ */

  function esc(v) {
    var d = document.createElement('div');
    d.textContent = String(v == null ? '' : v);
    return d.innerHTML;
  }

  function api(action, params, options) {
    var url = 'index.php?route=incidents&action=' + encodeURIComponent(action);
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

  function formatDateTime(dateStr, timeStr) {
    if (!dateStr) return '—';
    var d = new Date(dateStr + 'T' + (timeStr || '00:00:00'));
    if (isNaN(d.getTime())) return esc(dateStr + ' ' + (timeStr || ''));
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

  function severityChipClass(sev) {
    switch (sev) {
      case 'Low':      return 'chip--neutral';
      case 'Medium':   return 'chip--pending';
      case 'High':     return 'chip--danger';
      case 'Critical': return 'chip--danger';
      default:         return 'chip--neutral';
    }
  }

  function statusPillClass(status) {
    switch (status) {
      case 'Open':         return 'status-pill--off';
      case 'Under Review': return 'status-pill--off';
      case 'Resolved':     return 'status-pill--on';
      case 'Closed':       return 'status-pill--on';
      default:             return 'status-pill--off';
    }
  }

  function addressLine(inc) {
    var parts = [];
    if (inc.barangay_name)     parts.push(inc.barangay_name);
    if (inc.municipality_name) parts.push(inc.municipality_name);
    if (inc.province)          parts.push(inc.province);
    return parts.length ? parts.join(', ') : '—';
  }

  /* ============================================================
     JAIL UNIT SELECT — loaded once, then locked if needed
     ============================================================ */

  function initJailUnitSelect() {
    if (!jailUnitSelect || jailUnitsLoaded) return Promise.resolve();

    return refApi('jail-units').then(function (data) {
      jailUnitSelect.setItems((data.jail_units || []).map(function (u) {
        return { value: u.id, label: u.name + ' (' + u.dormitory + ')' };
      }));

      jailUnitsLoaded = true;

      // If staff has a locked unit, preselect it and disable the picker
      if (lockJailUnit && lockedJailUnitId > 0) {
        jailUnitSelect.setValue(lockedJailUnitId);
        jailUnitSelect.setDisabled(true);
        document.getElementById('incidentJailUnitId').value = lockedJailUnitId;
      }
    }).catch(function (err) {
      console.error('jail units:', err);
    });
  }

  /* ============================================================
     LOADERS
     ============================================================ */

  function loadJailUnitFilter() {
    if (!jailUnitFilter) return;

    if (lockJailUnit) {
      // Staff with a fixed unit — hide the filter row entry
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

  function loadProvinces() {
    refApi('provinces').then(function (data) {
      provinceSelect.setItems((data.provinces || []).map(function (p) {
        return { value: p, label: p };
      }));
    }).catch(function (err) { console.error('provinces:', err); });
  }

  function loadMunicipalities(province) {
    municipalitySelect.setDisabled(true);
    municipalitySelect.setItems([]);
    refApi('municipalities', { province: province }).then(function (data) {
      municipalitySelect.setItems((data.municipalities || []).map(function (m) {
        return { value: m.id, label: m.name };
      }));
      municipalitySelect.setDisabled(false);
    }).catch(function (err) { console.error('municipalities:', err); });
  }

  function loadBarangays(municipalityId) {
    barangaySelect.setDisabled(true);
    barangaySelect.setItems([]);
    refApi('barangays', { municipality_id: municipalityId }).then(function (data) {
      barangaySelect.setItems((data.barangays || []).map(function (b) {
        return { value: b.id, label: b.name };
      }));
      barangaySelect.setDisabled(false);
    }).catch(function (err) { console.error('barangays:', err); });
  }

  /* ============================================================
     RENDER
     ============================================================ */

  function render() {
    if (!incidents.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="data-table__empty">No incident records found.</td></tr>';
      return;
    }

    tbody.innerHTML = incidents.map(function (i) {
      var actions = '';
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

      return '<tr data-id="' + i.id + '">' +
        '<td>' + esc(formatDateTime(i.incident_date, i.incident_time)) + '</td>' +
        '<td><strong>' + esc(i.incident_type) + '</strong></td>' +
        '<td><span class="chip ' + severityChipClass(i.severity) + '">' + esc(i.severity) + '</span></td>' +
        '<td class="meta-text">' +
          esc(addressLine(i)) +
          (i.spot ? '<br><small class="meta-text">' + esc(i.spot) + '</small>' : '') +
        '</td>' +
        '<td class="meta-text">' + esc(i.jail_unit_name || '—') + '</td>' +
        '<td class="meta-text">' + esc(i.reported_by_name || '—') + '</td>' +
        '<td><span class="status-pill ' + statusPillClass(i.status) + '">' + esc(i.status) + '</span></td>' +
        '<td class="ta-right"><div class="row-actions">' + actions + '</div></td>' +
      '</tr>';
    }).join('');
  }

  function renderPagination() {
    var showing = Math.min(offset + incidents.length, total);
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
      type:         typeFilter ? typeFilter.value : '',
      severity:     severityFilter ? severityFilter.value : '',
      status:       statusFilter ? statusFilter.value : '',
      date:         dateFilter ? dateFilter.value : '',
      limit:        limit,
      offset:       offset
    };

    api('list', params).then(function (data) {
      incidents = data.incidents || [];
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

  function openModal(incident) {
    if (!modal) return;
    form.reset();
    formError.hidden = true;
    formError.textContent = '';

    provinceSelect.clear();
    municipalitySelect.clear();
    municipalitySelect.setItems([]);
    municipalitySelect.setDisabled(true);
    barangaySelect.clear();
    barangaySelect.setItems([]);
    barangaySelect.setDisabled(true);
    inputProvinceValue.value  = '';
    inputMunicipalityId.value = '';
    inputBarangayId.value     = '';

    document.getElementById('incidentModalTitle').textContent = incident ? 'Edit Incident' : 'Report Incident';
    inputId.value = incident ? incident.id : '';

    loadProvinces();

    // Jail unit — for locked staff, the picker stays locked to their unit.
    // Wait for the units to load before setting the value so setValue() has items to match.
    initJailUnitSelect().then(function () {
      if (!jailUnitSelect) return;

      if (lockJailUnit) {
        // Re-lock on every modal open — the select may have been reset by form.reset()
        jailUnitSelect.setValue(lockedJailUnitId);
        jailUnitSelect.setDisabled(true);
        document.getElementById('incidentJailUnitId').value = lockedJailUnitId;
      } else if (incident && incident.jail_unit_id) {
        jailUnitSelect.setValue(incident.jail_unit_id);
        document.getElementById('incidentJailUnitId').value = incident.jail_unit_id;
      } else {
        jailUnitSelect.clear();
        document.getElementById('incidentJailUnitId').value = '';
      }
    });

    if (incident) {
      inputDate.value         = incident.incident_date || '';
      inputTime.value         = (incident.incident_time || '').slice(0, 5);
      inputType.value         = incident.incident_type || '';
      inputSeverity.value     = incident.severity || 'Medium';
      inputDescription.value  = incident.description || '';
      inputPersons.value      = incident.persons_involved || '';
      inputWitnesses.value    = incident.witnesses || '';
      inputStatus.value       = incident.status || 'Open';
      inputResolvedAt.value   = toDatetimeLocal(incident.resolved_at);
      inputActionTaken.value  = incident.action_taken || '';
      inputSpot.value         = incident.spot || '';

      setTimeout(function () {
        if (incident.province) {
          provinceSelect.setValue(incident.province);
          inputProvinceValue.value = incident.province;

          if (incident.municipality_id) {
            loadMunicipalities(incident.province);

            setTimeout(function () {
              municipalitySelect.setValue(incident.municipality_id);
              inputMunicipalityId.value = incident.municipality_id;

              if (incident.barangay_id) {
                loadBarangays(incident.municipality_id);

                setTimeout(function () {
                  barangaySelect.setValue(incident.barangay_id);
                  inputBarangayId.value = incident.barangay_id;
                }, 400);
              }
            }, 400);
          }
        }
      }, 300);

      resolutionSection.style.display = '';
    } else {
      var now = new Date();
      var pad = function (n) { return String(n).padStart(2, '0'); };
      inputDate.value = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
      inputTime.value = pad(now.getHours()) + ':' + pad(now.getMinutes());
      inputSeverity.value = 'Medium';
      inputStatus.value = 'Open';
      inputSpot.value = '';

      resolutionSection.style.display = 'none';
    }

    modal.classList.remove('is-hidden');
    document.body.classList.add('modal-open');
    setTimeout(function () { inputType.focus(); }, 50);
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
      if (e.target.closest('[data-incident-close]')) closeModal();
    });
  }

  /* ============================================================
     SUBMIT
     ============================================================ */

  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      var payload = {
        id:                inputId.value ? Number(inputId.value) : 0,
        incident_date:     inputDate.value,
        incident_time:     inputTime.value ? inputTime.value + ':00' : '',
        incident_type:     inputType.value,
        severity:          inputSeverity.value,
        status:            inputStatus.value,
        description:       inputDescription.value.trim(),
        persons_involved:  inputPersons.value.trim(),
        witnesses:         inputWitnesses.value.trim(),
        action_taken:      inputActionTaken.value.trim(),
        resolved_at:       inputResolvedAt.value ? inputResolvedAt.value.replace('T', ' ') + ':00' : '',
        jail_unit_id:      document.getElementById('incidentJailUnitId').value,
        province:          inputProvinceValue.value,
        municipality_id:   inputMunicipalityId.value,
        barangay_id:       inputBarangayId.value,
        spot:              inputSpot.value.trim()
      };

      if (!payload.incident_date) {
        formError.textContent = 'Incident date is required.';
        formError.hidden = false;
        return;
      }
      if (!payload.incident_time) {
        formError.textContent = 'Incident time is required.';
        formError.hidden = false;
        return;
      }
      if (!payload.incident_type) {
        formError.textContent = 'Please select the incident type.';
        formError.hidden = false;
        return;
      }
      if (!payload.description) {
        formError.textContent = 'Please describe the incident.';
        formError.hidden = false;
        return;
      }

      var confirmed = await window.confirmDialog({
        title: payload.id ? 'Save Changes' : 'Report Incident',
        message: payload.id
          ? 'Apply the changes to this incident?'
          : 'Report this incident (' + payload.incident_type + ')?',
        confirmLabel: payload.id ? 'Save Changes' : 'Report'
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
          title: payload.id ? 'Incident Updated' : 'Incident Reported',
          message: payload.id ? 'Incident has been updated.' : 'Incident has been reported.'
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
      var incident = incidents.find(function (i) { return i.id === id; });
      if (!incident) return;

      var action = btn.dataset.action;

      if (action === 'edit') { openModal(incident); return; }

      if (action === 'delete') {
        var ok = await window.confirmDialog({
          title: 'Delete Incident',
          message: 'Permanently delete this incident report? This cannot be undone.',
          confirmLabel: 'Delete',
          variant: 'danger'
        });
        if (!ok) return;

        api('delete', null, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: id })
        }).then(function () {
          window.showSuccess({
            title: 'Record Deleted',
            message: 'Incident has been deleted.'
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
  [jailUnitFilter, typeFilter, severityFilter, statusFilter, dateFilter].forEach(function (el) {
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
    if (e.detail && e.detail.section === 'incidents') {
      loadJailUnitFilter();
      initJailUnitSelect();
      loadProvinces();
      load();
    }
  });

  loadJailUnitFilter();
  initJailUnitSelect();
  loadProvinces();
  if (section.classList.contains('is-visible')) load();
})();