(function () {
  'use strict';

  var section = document.getElementById('dashboard-inmates');
  if (!section) return;

  /* ---------- Toolbar / table ---------- */
  var tbody            = document.getElementById('inmateTableBody');
  var search           = document.getElementById('inmateSearch');
  var statusFilter     = document.getElementById('inmateStatusFilter');
  var offenseFilter    = document.getElementById('inmateOffenseFilter');
  var jailUnitFilter   = document.getElementById('inmateJailUnitFilter');
  var prevBtn          = document.getElementById('inmatePrevBtn');
  var nextBtn          = document.getElementById('inmateNextBtn');
  var summary          = document.getElementById('inmatePaginationSummary');
  var exportBtn        = document.getElementById('inmateExportBtn');
  var clearAddressBtn  = document.getElementById('inmateClearAddressBtn');

  /* ---------- Detail modal ---------- */
  var detailModal    = document.getElementById('inmateModal');
  var detailBody     = document.getElementById('inmateDetailBody');
  var headerNumber   = document.getElementById('inmateHeaderNumber');

  /* ---------- Edit modal ---------- */
  var editModal       = document.getElementById('inmateEditModal');
  var editForm        = document.getElementById('inmateEditForm');
  var editError       = document.getElementById('inmateEditError');
  var editSaveBtn     = document.getElementById('inmateEditSaveBtn');
  var editNumberBadge = document.getElementById('inmateEditNumber');

  var editId          = document.getElementById('editInmateId');
  var editInmateNumber = document.getElementById('editInmateNumber');
  var editCaseRef     = document.getElementById('editCaseReference');
  var editFirstName   = document.getElementById('editFirstName');
  var editMiddleName  = document.getElementById('editMiddleName');
  var editLastName    = document.getElementById('editLastName');
  var editSuffix      = document.getElementById('editSuffix');
  var editDob         = document.getElementById('editDob');
  var editSex         = document.getElementById('editSex');
  var editCivilStatus = document.getElementById('editCivilStatus');
  var editAdmission   = document.getElementById('editAdmissionDate');
  var editCustody     = document.getElementById('editCustodyStatus');
  var editClassification = document.getElementById('editClassification');
  var editCommittedAt    = document.getElementById('editCommittedAt');
  var editIsDrugCase     = document.getElementById('editIsDrugCase');
  var editNotes       = document.getElementById('editNotes');
  var editSentence    = document.getElementById('editSentence');
  var editSentenceMin = document.getElementById('editSentenceMin');
  var editSentenceMax = document.getElementById('editSentenceMax');
  var editSentenceManual = document.getElementById('editSentenceManual');
  var editSentenceBadge  = document.getElementById('editSentenceBadge');
  var editSentenceReset  = document.getElementById('editSentenceResetBtn');

  var isAdmin = section.dataset.isAdmin === '1';

  /* ---------- Address filter searchable selects ---------- */
  var provinceSelectFilter = new SearchableSelect({
    container: document.getElementById('inmateProvinceFilterWrap'),
    placeholder: 'Province',
    onChange: function (value) {
      municipalitySelectFilter.clear();
      municipalitySelectFilter.setItems([]);
      municipalitySelectFilter.setDisabled(true);

      barangaySelectFilter.clear();
      barangaySelectFilter.setItems([]);
      barangaySelectFilter.setDisabled(true);

      if (value) loadFilterMunicipalities(value);
      offset = 0;
      load(true);
    }
  });

  var municipalitySelectFilter = new SearchableSelect({
    container: document.getElementById('inmateMunicipalityFilterWrap'),
    placeholder: 'Municipality',
    onChange: function (value) {
      barangaySelectFilter.clear();
      barangaySelectFilter.setItems([]);
      barangaySelectFilter.setDisabled(true);
      if (value) loadFilterBarangays(value);
      offset = 0;
      load(true);
    }
  });

  var barangaySelectFilter = new SearchableSelect({
    container: document.getElementById('inmateBarangayFilterWrap'),
    placeholder: 'Barangay',
    onChange: function () {
      offset = 0;
      load(true);
    }
  });

  municipalitySelectFilter.setDisabled(true);
  barangaySelectFilter.setDisabled(true);

  /* ---------- Edit modal searchable selects ---------- */
  var editJailUnitSelect = new SearchableSelect({
    container: document.getElementById('editJailUnitWrap'),
    placeholder: 'Select jail unit',
    onChange: function (value) {
      document.getElementById('editJailUnitId').value = value;
    }
  });

  var editProvinceSelect = new SearchableSelect({
    container: document.getElementById('editProvinceWrap'),
    placeholder: 'Select province',
    onChange: function (value) {
      document.getElementById('editProvinceValue').value = value;

      editMunicipalitySelect.clear();
      editMunicipalitySelect.setItems([]);
      editMunicipalitySelect.setDisabled(true);

      editBarangaySelect.clear();
      editBarangaySelect.setItems([]);
      editBarangaySelect.setDisabled(true);

      document.getElementById('editMunicipalityId').value = '';
      document.getElementById('editBarangayId').value = '';

      if (value) loadEditMunicipalities(value);
    }
  });

  var editMunicipalitySelect = new SearchableSelect({
    container: document.getElementById('editMunicipalityWrap'),
    placeholder: 'Select municipality',
    onChange: function (value) {
      document.getElementById('editMunicipalityId').value = value;

      editBarangaySelect.clear();
      editBarangaySelect.setItems([]);
      editBarangaySelect.setDisabled(true);
      document.getElementById('editBarangayId').value = '';

      if (value) loadEditBarangays(value);
    }
  });

  var editBarangaySelect = new SearchableSelect({
    container: document.getElementById('editBarangayWrap'),
    placeholder: 'Select barangay',
    onChange: function (value) {
      document.getElementById('editBarangayId').value = value;
    }
  });

  var editOffenseSelect = new SearchableSelect({
    container: document.getElementById('editOffenseWrap'),
    placeholder: 'Select offense',
    onChange: function (value) {
      document.getElementById('editOffenseId').value = value;
      if (value) loadEditOffenseDetails(value);
      else applyEditAutoSentence(null);
    }
  });

  editMunicipalitySelect.setDisabled(true);
  editBarangaySelect.setDisabled(true);

  /* ============================================================
     STATE
     ============================================================ */

  var inmates = [];
  var total   = 0;
  var limit   = 20;
  var offset  = 0;
  var loaded  = false;
  var offenseFilterLoaded = false;
  var jailUnitFilterLoaded = false;
  var editLastAutoSentence = null;

  /* ============================================================
     HELPERS
     ============================================================ */

  function esc(v) {
    var d = document.createElement('div');
    d.textContent = String(v == null ? '' : v);
    return d.innerHTML;
  }

  function orDash(v) {
    return (v === null || v === undefined || v === '') ? '—' : v;
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

  function inmatesApi(action, params) {
    var url = 'index.php?route=inmates&action=' + encodeURIComponent(action);
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

  function formatDate(iso) {
    if (!iso) return '—';
    var d = new Date(iso);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function statusClass(status) {
    return status === 'In Custody' ? 'status-pill--on' : 'status-pill--off';
  }

  function initialsFrom(row) {
    var a = (row.first_name || '').charAt(0);
    var b = (row.last_name || '').charAt(0);
    return (a + b).toUpperCase() || '?';
  }

  function addressLine(row) {
    var parts = [];
    if (row.barangay_name)     parts.push(row.barangay_name);
    if (row.municipality_name) parts.push(row.municipality_name);
    return parts.length ? parts.join(', ') : '—';
  }

  function toDateInput(iso) {
    if (!iso) return '';
    return String(iso).slice(0, 10);
  }

  /* ============================================================
     ADDRESS FILTER LOADERS
     ============================================================ */

  function loadFilterProvinces() {
    refApi('provinces').then(function (data) {
      provinceSelectFilter.setItems(data.provinces.map(function (p) {
        return { value: p, label: p };
      }));
    }).catch(function (err) { console.error(err); });
  }

  function loadFilterMunicipalities(province) {
    municipalitySelectFilter.setDisabled(true);
    municipalitySelectFilter.setItems([]);

    refApi('municipalities', { province: province }).then(function (data) {
      municipalitySelectFilter.setItems(data.municipalities.map(function (m) {
        return { value: m.id, label: m.name };
      }));
      municipalitySelectFilter.setDisabled(false);
    }).catch(function (err) { console.error(err); });
  }

  function loadFilterBarangays(municipalityId) {
    barangaySelectFilter.setDisabled(true);
    barangaySelectFilter.setItems([]);

    refApi('barangays', { municipality_id: municipalityId }).then(function (data) {
      barangaySelectFilter.setItems(data.barangays.map(function (b) {
        return { value: b.id, label: b.name };
      }));
      barangaySelectFilter.setDisabled(false);
    }).catch(function (err) { console.error(err); });
  }

  /* ============================================================
     JAIL UNIT FILTER LOADER
     ============================================================ */

  function loadJailUnitFilter() {
    if (jailUnitFilterLoaded || !jailUnitFilter) return;

    refApi('jail-units').then(function (data) {
      (data.jail_units || []).forEach(function (u) {
        var opt = document.createElement('option');
        opt.value = u.id;
        opt.textContent = u.name + ' (' + u.dormitory + ')';
        jailUnitFilter.appendChild(opt);
      });
      jailUnitFilterLoaded = true;
    }).catch(function () { /* silent */ });
  }

  /* ============================================================
     OFFENSE FILTER LOADER
     ============================================================ */

  function loadOffenseFilter() {
    if (offenseFilterLoaded || !offenseFilter) return;

    refApi('offenses').then(function (data) {
      (data.offenses || []).forEach(function (o) {
        var opt = document.createElement('option');
        opt.value = o.id;
        opt.textContent = o.name;
        offenseFilter.appendChild(opt);
      });
      offenseFilterLoaded = true;
    }).catch(function () { /* silent */ });
  }

  /* ============================================================
     EDIT MODAL — DEPENDENT LOADERS
     ============================================================ */

  function loadEditMunicipalities(province) {
    editMunicipalitySelect.setDisabled(true);
    editMunicipalitySelect.setItems([]);

    refApi('municipalities', { province: province }).then(function (data) {
      editMunicipalitySelect.setItems(data.municipalities.map(function (m) {
        return { value: m.id, label: m.name };
      }));
      editMunicipalitySelect.setDisabled(false);
    }).catch(function (err) { console.error(err); });
  }

  function loadEditBarangays(municipalityId) {
    editBarangaySelect.setDisabled(true);
    editBarangaySelect.setItems([]);

    refApi('barangays', { municipality_id: municipalityId }).then(function (data) {
      editBarangaySelect.setItems(data.barangays.map(function (b) {
        return { value: b.id, label: b.name };
      }));
      editBarangaySelect.setDisabled(false);
    }).catch(function (err) { console.error(err); });
  }

  function loadEditOffenseDetails(id) {
    refApi('offense', { id: id }).then(function (data) {
      var o = data.offense;
      document.getElementById('editOffenseId').value = o.id;
      applyEditAutoSentence(o);
    }).catch(function (err) { console.error(err); });
  }

  /* ============================================================
     EDIT — SENTENCE AUTO-FILL
     ============================================================ */

  function applyEditAutoSentence(offense) {
    if (!offense) {
      editSentence.value = '';
      editSentenceMin.value = '';
      editSentenceMax.value = '';
      editLastAutoSentence = null;
      editSentenceManual.value = '0';
      updateEditSentenceBadge();
      return;
    }

    var text = offense.default_sentence || '';
    var hasMin = offense.min_years !== null && offense.min_years !== undefined;
    var hasMax = offense.max_years !== null && offense.max_years !== undefined;

    if (hasMin && hasMax) {
      var years = (offense.min_years === offense.max_years)
        ? offense.min_years + ' yr' + (offense.min_years === 1 ? '' : 's')
        : offense.min_years + '–' + offense.max_years + ' yrs';

      text = text ? text + ' (' + years + ')' : years;
    }

    editSentence.value = text;
    editSentenceMin.value = hasMin ? offense.min_years : '';
    editSentenceMax.value = hasMax ? offense.max_years : '';

    editLastAutoSentence = text;
    editSentenceManual.value = '0';
    updateEditSentenceBadge();
  }

  function updateEditSentenceBadge() {
    if (!editSentenceBadge) return;

    if (editSentenceManual.value === '1' && editSentence.value.trim() !== '') {
      editSentenceBadge.hidden = false;
      editSentenceBadge.textContent = 'manual';
      editSentenceBadge.classList.add('is-manual');
      if (editSentenceReset) editSentenceReset.hidden = false;
    } else if (editSentence.value.trim() !== '') {
      editSentenceBadge.hidden = false;
      editSentenceBadge.textContent = 'auto';
      editSentenceBadge.classList.remove('is-manual');
      if (editSentenceReset) editSentenceReset.hidden = true;
    } else {
      editSentenceBadge.hidden = true;
      editSentenceBadge.classList.remove('is-manual');
      if (editSentenceReset) editSentenceReset.hidden = true;
    }
  }

  if (editSentence) {
    editSentence.addEventListener('input', function () {
      if (editSentence.value !== editLastAutoSentence) {
        editSentenceManual.value = '1';
      } else {
        editSentenceManual.value = '0';
      }
      updateEditSentenceBadge();
    });
  }

  if (editSentenceReset) {
    editSentenceReset.addEventListener('click', function () {
      if (editLastAutoSentence !== null) {
        editSentence.value = editLastAutoSentence;
        editSentenceManual.value = '0';
        updateEditSentenceBadge();
      }
    });
  }

  /* ============================================================
     TABLE RENDER
     ============================================================ */

  function renderTable() {
    if (!inmates.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="data-table__empty">No inmate records found.</td></tr>';
      return;
    }

    tbody.innerHTML = inmates.map(function (i) {
      var isReleased    = i.custody_status === 'Released';
      var isTransferred = i.custody_status === 'Transferred';

      var releaseBtn = (isAdmin && !isReleased && !isTransferred)
        ? '<button type="button" class="icon-btn icon-btn--release" data-action="release" title="Release inmate">' +
            '<i class="bi bi-box-arrow-up"></i>' +
          '</button>'
        : '';

      var canEdit = !isReleased || isAdmin;

      var editBtn = canEdit
        ? '<button type="button" class="icon-btn" data-action="edit" title="Edit record">' +
            '<i class="bi bi-pencil"></i>' +
          '</button>'
        : '';

      return '<tr data-id="' + i.id + '">' +
        '<td><code class="mono-badge">' + esc(i.inmate_number) + '</code></td>' +
        '<td class="meta-text">' + esc(i.jail_unit_name || '—') + '</td>' +
        '<td>' +
          '<div class="cell-name">' +
            '<span class="cell-avatar">' + esc(initialsFrom(i)) + '</span>' +
            '<div class="cell-name__text">' +
              '<strong>' + esc(i.full_name) + '</strong>' +
              (i.case_reference ? '<small class="meta-text">' + esc(i.case_reference) + '</small>' : '') +
            '</div>' +
          '</div>' +
        '</td>' +
        '<td>' + esc(i.offense_name || '—') +
          (i.offense_code ? '<br><small class="meta-text">' + esc(i.offense_code) + '</small>' : '') +
        '</td>' +
        '<td class="meta-text">' + esc(addressLine(i)) + '</td>' +
        '<td><span class="status-pill ' + statusClass(i.custody_status) + '">' +
          esc(i.custody_status) +
        '</span></td>' +
        '<td>' + esc(formatDate(i.admission_date)) + '</td>' +
        '<td class="ta-right"><div class="row-actions">' +
          '<button type="button" class="icon-btn" data-action="view" title="View details">' +
            '<i class="bi bi-eye"></i>' +
          '</button>' +
          editBtn +
          releaseBtn +
        '</div></td>' +
      '</tr>';
    }).join('');
  }

  /* ============================================================
     PAGINATION
     ============================================================ */

  function renderPagination() {
    var showing = Math.min(offset + inmates.length, total);
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
      q:               search ? search.value.trim() : '',
      status:          statusFilter ? statusFilter.value : '',
      jail_unit_id:    jailUnitFilter ? jailUnitFilter.value : '',
      province:        provinceSelectFilter.getValue(),
      municipality_id: municipalitySelectFilter.getValue(),
      barangay_id:     barangaySelectFilter.getValue(),
      offense_id:      offenseFilter ? offenseFilter.value : '',
      limit:           limit,
      offset:          offset
    };

    inmatesApi('list', params).then(function (data) {
      inmates = data.inmates || [];
      total = data.total || 0;
      loaded = true;
      renderTable();
      renderPagination();
    }).catch(function (err) {
      tbody.innerHTML = '<tr><td colspan="8" class="data-table__empty">' + esc(err.message) + '</td></tr>';
      if (summary) summary.textContent = '';
    });
  }

  /* ============================================================
     DETAIL MODAL
     ============================================================ */

  function detailItem(icon, label, value, wide) {
    return '<div class="detail-row' + (wide ? ' detail-row--wide' : '') + '">' +
      '<span class="detail-row__label">' + esc(label) + '</span>' +
      '<span class="detail-row__value">' + esc(orDash(value)) + '</span>' +
    '</div>';
  }

  function detailSection(icon, title, rowsHtml) {
    return '<section class="detail-section">' +
      '<header class="detail-section__header">' +
        '<h3>' + esc(title) + '</h3>' +
      '</header>' +
      '<div class="detail-section__body">' + rowsHtml + '</div>' +
    '</section>';
  }

  function openDetail(id) {
    if (!detailModal || !detailBody) return;

    detailBody.innerHTML = '<div class="data-table__empty">Loading...</div>';
    if (headerNumber) headerNumber.textContent = '—';

    detailModal.classList.remove('is-hidden');
    document.body.classList.add('modal-open');

    inmatesApi('get', { id: id }).then(function (data) {
      var i = data.inmate;

      if (!i) {
        detailBody.innerHTML = '<div class="data-table__empty">No inmate data returned.</div>';
        return;
      }

      if (headerNumber) headerNumber.textContent = i.inmate_number || '—';

      var nameParts = [i.first_name, i.middle_name, i.last_name, i.suffix];
      var fullName = nameParts.filter(function (p) {
        return p !== null && p !== undefined && String(p).trim() !== '';
      }).join(' ').trim();

      var years;
      if (i.sentence_years_min !== null && i.sentence_years_max !== null) {
        years = (i.sentence_years_min === i.sentence_years_max)
          ? i.sentence_years_min + ' yrs'
          : i.sentence_years_min + ' – ' + i.sentence_years_max + ' yrs';
      } else {
        years = '—';
      }

      var isCustody = i.custody_status === 'In Custody';

      var header =
        '<div class="detail-hero">' +
          '<div class="detail-hero__avatar">' + esc(initialsFrom(i)) + '</div>' +
          '<div class="detail-hero__info">' +
            '<strong>' + esc(fullName || '—') + '</strong>' +
            '<span class="meta-text">' + esc(orDash(i.case_reference)) + '</span>' +
          '</div>' +
          '<span class="detail-hero__status status-pill ' + (isCustody ? 'status-pill--on' : 'status-pill--off') + '">' +
            esc(orDash(i.custody_status)) +
          '</span>' +
        '</div>';

      var identification =
        detailItem('', 'Inmate No.', i.inmate_number) +
        detailItem('', 'Case Reference', i.case_reference) +
        detailItem('', 'Jail Unit', i.jail_unit_name);

      var personal =
        detailItem('', 'Full Name', fullName) +
        detailItem('', 'Sex', i.sex) +
        detailItem('', 'Date of Birth', formatDate(i.date_of_birth)) +
        detailItem('', 'Civil Status', i.civil_status);

      var addressHtml =
        detailItem('', 'Municipality', i.municipality_name) +
        detailItem('', 'Barangay', i.barangay_name);

      var offenseText = (i.offense_name || '') +
        (i.offense_code ? ' (' + i.offense_code + ')' : '');
      if (!offenseText) offenseText = '—';

      var caseHtml =
        detailItem('', 'Offense', offenseText, true) +
        detailItem('', 'Sentence', i.sentence, true) +
        detailItem('', 'Years Range', years);

      var complianceHtml =
        detailItem('', 'Classification', i.classification) +
        detailItem('', 'Drug-related case', (parseInt(i.is_drug_case || 0, 10) === 1) ? 'Yes' : 'No') +
        detailItem('', 'Committed', formatDate(i.committed_at));

      var custodyHtml =
        detailItem('', 'Admission Date', formatDate(i.admission_date)) +
        detailItem('', 'Custody Status', i.custody_status);

      var notes = i.notes
        ? detailItem('', 'Notes', i.notes, true)
        : '';

      detailBody.innerHTML =
        header +
        detailSection('', 'Identification', identification) +
        detailSection('', 'Personal Information', personal) +
        detailSection('', 'Address', addressHtml) +
        detailSection('', 'Case Information', caseHtml) +
        detailSection('', 'Compliance', complianceHtml) +
        detailSection('', 'Custody', custodyHtml) +
        (notes ? detailSection('', 'Notes', notes) : '');
    }).catch(function (err) {
      detailBody.innerHTML = '<div class="data-table__empty">' + esc(err.message) + '</div>';
    });
  }

  function closeDetail() {
    if (!detailModal) return;
    detailModal.classList.add('is-hidden');
    document.body.classList.remove('modal-open');
  }

  if (detailModal) {
    detailModal.addEventListener('click', function (e) {
      if (e.target === detailModal) closeDetail();
      if (e.target.closest('[data-inmate-close]')) closeDetail();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !detailModal.classList.contains('is-hidden')) closeDetail();
    });
  }

  /* ============================================================
     EDIT MODAL
     ============================================================ */

  function openEdit(id) {
    if (!editModal || !editForm) return;

    editError.hidden = true;
    editError.textContent = '';
    editForm.reset();

    editJailUnitSelect.clear();
    editProvinceSelect.clear();
    editMunicipalitySelect.clear();
    editMunicipalitySelect.setItems([]);
    editMunicipalitySelect.setDisabled(true);
    editBarangaySelect.clear();
    editBarangaySelect.setItems([]);
    editBarangaySelect.setDisabled(true);
    editOffenseSelect.clear();

    document.getElementById('editJailUnitId').value = '';
    document.getElementById('editMunicipalityId').value = '';
    document.getElementById('editBarangayId').value = '';
    document.getElementById('editOffenseId').value = '';
    document.getElementById('editProvinceValue').value = '';

    editSentenceManual.value = '0';
    editLastAutoSentence = null;

    editModal.classList.remove('is-hidden');
    document.body.classList.add('modal-open');

    inmatesApi('get', { id: id }).then(function (data) {
      var i = data.inmate;
      if (!i) {
        editError.textContent = 'Inmate not found.';
        editError.hidden = false;
        return;
      }

      editModal.dataset.originalStatus = i.custody_status || '';

      editId.value = i.id;
      editNumberBadge.textContent = i.inmate_number || '—';
      editInmateNumber.value = i.inmate_number || '';
      editCaseRef.value = i.case_reference || '';
      editFirstName.value = i.first_name || '';
      editMiddleName.value = i.middle_name || '';
      editLastName.value = i.last_name || '';
      editSuffix.value = i.suffix || '';
      editDob.value = toDateInput(i.date_of_birth);
      editSex.value = i.sex || 'Unspecified';
      editCivilStatus.value = i.civil_status || '';
      editAdmission.value = toDateInput(i.admission_date);
      editCustody.value = i.custody_status || 'In Custody';
      editClassification.value = i.classification || '';
      editCommittedAt.value = toDateInput(i.committed_at);
      editIsDrugCase.checked = (parseInt(i.is_drug_case || 0, 10) === 1);
      editNotes.value = i.notes || '';
      editSentence.value = i.sentence || '';
      editSentenceMin.value = i.sentence_years_min !== null ? i.sentence_years_min : '';
      editSentenceMax.value = i.sentence_years_max !== null ? i.sentence_years_max : '';

      if (i.sentence_is_manual) {
        editSentenceManual.value = '1';
        editLastAutoSentence = null;
      } else {
        editSentenceManual.value = '0';
        editLastAutoSentence = i.sentence || null;
      }
      updateEditSentenceBadge();

      // Jail units
      refApi('jail-units').then(function (jData) {
        editJailUnitSelect.setItems((jData.jail_units || []).map(function (u) {
          return { value: u.id, label: u.name + ' (' + u.dormitory + ')' };
        }));
        if (i.jail_unit_id) {
          editJailUnitSelect.setValue(i.jail_unit_id);
          document.getElementById('editJailUnitId').value = i.jail_unit_id;
        }
      }).catch(function (err) { console.error(err); });

      refApi('provinces').then(function (pData) {
        var provinces = pData.provinces || [];
        editProvinceSelect.setItems(provinces.map(function (p) {
          return { value: p, label: p };
        }));

        if (provinces.length) {
          var chosenProvince = provinces[0];
          editProvinceSelect.setValue(chosenProvince);
          document.getElementById('editProvinceValue').value = chosenProvince;

          refApi('municipalities', { province: chosenProvince }).then(function (mData) {
            var muns = mData.municipalities || [];
            editMunicipalitySelect.setItems(muns.map(function (m) {
              return { value: m.id, label: m.name };
            }));
            editMunicipalitySelect.setDisabled(false);

            var targetMuni = muns.find(function (m) { return m.id === i.municipality_id; });
            if (targetMuni) {
              editMunicipalitySelect.setValue(targetMuni.id);
              document.getElementById('editMunicipalityId').value = targetMuni.id;

              refApi('barangays', { municipality_id: targetMuni.id }).then(function (bData) {
                var brgys = bData.barangays || [];
                editBarangaySelect.setItems(brgys.map(function (b) {
                  return { value: b.id, label: b.name };
                }));
                editBarangaySelect.setDisabled(false);

                var targetBrgy = brgys.find(function (b) { return b.id === i.barangay_id; });
                if (targetBrgy) {
                  editBarangaySelect.setValue(targetBrgy.id);
                  document.getElementById('editBarangayId').value = targetBrgy.id;
                }
              }).catch(function (err) { console.error(err); });
            }
          }).catch(function (err) { console.error(err); });
        }
      }).catch(function (err) { console.error(err); });

      refApi('offenses').then(function (oData) {
        var offenses = oData.offenses || [];
        editOffenseSelect.setItems(offenses.map(function (o) {
          return {
            value: o.id,
            label: o.name + (o.code ? ' (' + o.code + ')' : ''),
            raw: o
          };
        }));

        var targetOffense = offenses.find(function (o) { return o.id === i.offense_id; });
        if (targetOffense) {
          editOffenseSelect.setValue(targetOffense.id);
          document.getElementById('editOffenseId').value = targetOffense.id;
        }
      }).catch(function (err) { console.error(err); });

      setTimeout(function () { editFirstName.focus(); }, 100);
    }).catch(function (err) {
      editError.textContent = err.message;
      editError.hidden = false;
    });
  }

  function closeEdit() {
    if (!editModal) return;
    editModal.classList.add('is-hidden');
    document.body.classList.remove('modal-open');
  }

  if (editModal) {
    editModal.addEventListener('click', function (e) {
      if (e.target === editModal) closeEdit();
      if (e.target.closest('[data-inmate-edit-close]')) closeEdit();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !editModal.classList.contains('is-hidden')) closeEdit();
    });
  }

  /* ============================================================
     EDIT SUBMIT
     ============================================================ */

  if (editForm) {
    editForm.addEventListener('submit', async function (e) {
      e.preventDefault();

      var payload = {
        id:                 Number(editId.value),
        inmate_number:      editInmateNumber.value,
        jail_unit_id:       document.getElementById('editJailUnitId').value,
        case_reference:     editCaseRef.value.trim(),
        first_name:         editFirstName.value.trim(),
        middle_name:        editMiddleName.value.trim(),
        last_name:          editLastName.value.trim(),
        suffix:             editSuffix.value.trim(),
        date_of_birth:      editDob.value,
        sex:                editSex.value,
        civil_status:       editCivilStatus.value,
        province:           document.getElementById('editProvinceValue').value,
        municipality_id:    document.getElementById('editMunicipalityId').value,
        barangay_id:        document.getElementById('editBarangayId').value,
        offense_id:         document.getElementById('editOffenseId').value,
        classification:     editClassification.value,
        committed_at:       editCommittedAt.value,
        is_drug_case:       editIsDrugCase.checked ? 1 : 0,
        sentence:           editSentence.value.trim(),
        sentence_years_min: editSentenceMin.value,
        sentence_years_max: editSentenceMax.value,
        sentence_is_manual: editSentenceManual.value === '1' ? 1 : 0,
        admission_date:     editAdmission.value,
        custody_status:     editCustody.value,
        notes:              editNotes.value.trim()
      };

      var originalStatus = editModal.dataset.originalStatus || '';
      if (originalStatus === 'Released' && payload.custody_status !== 'Released') {
        payload.custody_status = 'Released';
      }

      if (!payload.first_name || !payload.last_name) {
        editError.textContent = 'First name and last name are required.';
        editError.hidden = false;
        return;
      }
      if (!payload.jail_unit_id) {
        editError.textContent = 'Please select a jail unit.';
        editError.hidden = false;
        return;
      }
      if (!payload.offense_id) {
        editError.textContent = 'Please select an offense.';
        editError.hidden = false;
        return;
      }

      var fullName = (payload.first_name + ' ' + payload.last_name).trim();
      var confirmed = await window.confirmDialog({
        title: 'Save Changes',
        message: 'Apply the changes to inmate "' + fullName + '" (' + payload.inmate_number + ')?',
        confirmLabel: 'Save Changes'
      });
      if (!confirmed) return;

      editError.hidden = true;
      editSaveBtn.disabled = true;
      editSaveBtn.textContent = 'Saving...';

      fetch('index.php?route=inmates&action=update', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      }).then(function (r) {
        return r.json().then(function (data) {
          if (!r.ok || !data.success) throw new Error(data.message || 'Error');
          return data;
        });
      }).then(function () {
        closeEdit();
        load(true);
      }).catch(function (err) {
        editError.textContent = err.message;
        editError.hidden = false;
      }).finally(function () {
        editSaveBtn.disabled = false;
        editSaveBtn.textContent = 'Save Changes';
      });
    });
  }

  /* ============================================================
     RELEASE ACTION
     ============================================================ */

  async function handleRelease(id) {
    var inmate = inmates.find(function (x) { return x.id === id; });
    if (!inmate) return;

    var name = inmate.full_name || inmate.inmate_number;

    var ok = await window.confirmDialog({
      title: 'Release Inmate',
      message: 'Release "' + name + '" (' + inmate.inmate_number + ')?\n\n' +
               'This will set the custody status to Released.',
      confirmLabel: 'Release',
      variant: 'warning'
    });
    if (!ok) return;

    try {
      var res = await fetch('index.php?route=inmates&action=release', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ id: id })
      });
      var data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.message || 'Error');

      window.showSuccess({
        title: 'Inmate Released',
        message: name + ' has been released.'
      });

      load(true);
    } catch (err) {
      alert(err.message || 'Unable to release inmate.');
    }
  }

  /* ============================================================
     WIRE UP
     ============================================================ */

  var timer;
  if (search) {
    search.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        offset = 0;
        load(true);
      }, 250);
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', function () {
      offset = 0;
      load(true);
    });
  }

  if (offenseFilter) {
    offenseFilter.addEventListener('change', function () {
      offset = 0;
      load(true);
    });
  }

  if (jailUnitFilter) {
    jailUnitFilter.addEventListener('change', function () {
      offset = 0;
      load(true);
    });
  }

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

  if (tbody) {
    tbody.addEventListener('click', function (e) {
      var viewBtn    = e.target.closest('button[data-action="view"]');
      var editBtn    = e.target.closest('button[data-action="edit"]');
      var releaseBtn = e.target.closest('button[data-action="release"]');
      var row        = e.target.closest('tr');

      if (!row) return;
      var id = Number(row.dataset.id);

      if (viewBtn) { openDetail(id); return; }
      if (editBtn) { openEdit(id); return; }
      if (releaseBtn) { handleRelease(id); return; }
    });
  }

  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      var params = new URLSearchParams();
      if (search) params.set('q', search.value.trim());
      if (statusFilter) params.set('status', statusFilter.value);
      if (offenseFilter) params.set('offense_id', offenseFilter.value);
      if (jailUnitFilter) params.set('jail_unit_id', jailUnitFilter.value);
      params.set('province', provinceSelectFilter.getValue());
      params.set('municipality_id', municipalitySelectFilter.getValue());
      params.set('barangay_id', barangaySelectFilter.getValue());
      window.location.href = 'index.php?route=inmates&action=export&' + params.toString();
    });
  }

  if (clearAddressBtn) {
    clearAddressBtn.addEventListener('click', function () {
      provinceSelectFilter.clear();
      municipalitySelectFilter.clear();
      municipalitySelectFilter.setItems([]);
      municipalitySelectFilter.setDisabled(true);
      barangaySelectFilter.clear();
      barangaySelectFilter.setItems([]);
      barangaySelectFilter.setDisabled(true);
      offset = 0;
      load(true);
    });
  }

  document.addEventListener('section:changed', function (e) {
    if (e.detail && e.detail.section === 'inmates') {
      loadOffenseFilter();
      loadJailUnitFilter();
      load();
    }
  });

  loadOffenseFilter();
  loadJailUnitFilter();
  loadFilterProvinces();

  if (section.classList.contains('is-visible')) {
    load();
  }
})();