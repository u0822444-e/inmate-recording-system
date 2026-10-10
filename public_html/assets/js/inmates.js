(function () {
  'use strict';

  var form = document.getElementById('inmateForm');
  if (!form) return;

  var errorBox = document.getElementById('inmateFormError');
  var saveBtn  = document.getElementById('inmateSaveBtn');

  /* ============================================================
     SEARCHABLE SELECT INSTANCES
     ============================================================ */

  var jailUnitSelect = new SearchableSelect({
    container: document.getElementById('inmateJailUnitWrap'),
    placeholder: 'Select jail unit',
    onChange: function (value) {
      document.getElementById('inmateJailUnitId').value = value;
    }
  });

  var provinceSelect = new SearchableSelect({
    container: document.getElementById('inmateProvinceWrap'),
    placeholder: 'Select province',
    onChange: function (value) {
      document.getElementById('inmateProvinceValue').value = value;

      municipalitySelect.clear();
      municipalitySelect.setItems([]);
      municipalitySelect.setDisabled(true);

      barangaySelect.clear();
      barangaySelect.setItems([]);
      barangaySelect.setDisabled(true);

      document.getElementById('inmateMunicipalityId').value = '';
      document.getElementById('inmateBarangayId').value = '';

      if (value) loadMunicipalities(value);
    }
  });

  var municipalitySelect = new SearchableSelect({
    container: document.getElementById('inmateMunicipalityWrap'),
    placeholder: 'Select municipality',
    onChange: function (value) {
      document.getElementById('inmateMunicipalityId').value = value;

      barangaySelect.clear();
      barangaySelect.setItems([]);
      barangaySelect.setDisabled(true);
      document.getElementById('inmateBarangayId').value = '';

      if (value) loadBarangays(value);
    }
  });

  var barangaySelect = new SearchableSelect({
    container: document.getElementById('inmateBarangayWrap'),
    placeholder: 'Select barangay',
    onChange: function (value) {
      document.getElementById('inmateBarangayId').value = value;
    }
  });

  var offenseSelect = new SearchableSelect({
    container: document.getElementById('inmateOffenseWrap'),
    placeholder: 'Select offense',
    onChange: function (value) {
      document.getElementById('inmateOffenseId').value = value;
      if (value) {
        loadOffenseDetails(value);
      } else {
        applyAutoSentence(null);
      }
    }
  });

  municipalitySelect.setDisabled(true);
  barangaySelect.setDisabled(true);

  /* ============================================================
     SENTENCE AUTO-FILL + OVERRIDE
     ============================================================ */

  var sentenceInput  = document.getElementById('inmateSentence');
  var sentenceMin    = document.getElementById('inmateSentenceMin');
  var sentenceMax    = document.getElementById('inmateSentenceMax');
  var sentenceManual = document.getElementById('inmateSentenceManual');
  var sentenceBadge  = document.getElementById('sentenceBadge');
  var sentenceReset  = document.getElementById('sentenceResetBtn');

  var lastAutoSentence = null;

  function applyAutoSentence(offense) {
    if (!offense) {
      sentenceInput.value = '';
      sentenceMin.value = '';
      sentenceMax.value = '';
      lastAutoSentence = null;
      sentenceManual.value = '0';
      updateSentenceBadge();
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

    sentenceInput.value = text;
    sentenceMin.value = hasMin ? offense.min_years : '';
    sentenceMax.value = hasMax ? offense.max_years : '';

    lastAutoSentence = text;
    sentenceManual.value = '0';
    updateSentenceBadge();
  }

  function updateSentenceBadge() {
    if (!sentenceBadge) return;

    if (sentenceManual.value === '1' && sentenceInput.value.trim() !== '') {
      sentenceBadge.hidden = false;
      sentenceBadge.textContent = 'manual';
      sentenceBadge.classList.add('is-manual');
      if (sentenceReset) sentenceReset.hidden = false;
    } else if (sentenceInput.value.trim() !== '') {
      sentenceBadge.hidden = false;
      sentenceBadge.textContent = 'auto';
      sentenceBadge.classList.remove('is-manual');
      if (sentenceReset) sentenceReset.hidden = true;
    } else {
      sentenceBadge.hidden = true;
      sentenceBadge.classList.remove('is-manual');
      if (sentenceReset) sentenceReset.hidden = true;
    }
  }

  if (sentenceInput) {
    sentenceInput.addEventListener('input', function () {
      sentenceManual.value = (sentenceInput.value !== lastAutoSentence) ? '1' : '0';
      updateSentenceBadge();
    });
  }

  if (sentenceReset) {
    sentenceReset.addEventListener('click', function () {
      if (lastAutoSentence !== null) {
        sentenceInput.value = lastAutoSentence;
        sentenceManual.value = '0';
        updateSentenceBadge();
      }
    });
  }

  /* ============================================================
     API
     ============================================================ */

  function api(action, params) {
    var url = 'index.php?route=reference&action=' + encodeURIComponent(action);
    if (params) {
      Object.keys(params).forEach(function (k) {
        url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
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

  /* ============================================================
     AUTO-FILL NUMBERS
     ============================================================ */

  function loadNextPdl() {
    api('next-pdl').then(function (data) {
      document.getElementById('inmateNumber').value = data.pdl_no;
    }).catch(function (err) { console.error(err); });
  }

  function loadNextCase() {
    api('next-case').then(function (data) {
      document.getElementById('inmateCaseRef').value = data.case_no;
    }).catch(function (err) { console.error(err); });
  }

  /* ============================================================
     JAIL UNITS
     ============================================================ */

  function loadJailUnits() {
    api('jail-units').then(function (data) {
      jailUnitSelect.setItems((data.jail_units || []).map(function (u) {
        return { value: u.id, label: u.name + ' (' + u.dormitory + ')' };
      }));
    }).catch(function (err) { console.error(err); });
  }

  /* ============================================================
     PROVINCES / MUNICIPALITIES / BARANGAYS
     ============================================================ */

  function loadProvinces() {
    api('provinces').then(function (data) {
      provinceSelect.setItems(data.provinces.map(function (p) {
        return { value: p, label: p };
      }));
    }).catch(function (err) { console.error(err); });
  }

  function loadMunicipalities(province) {
    municipalitySelect.setDisabled(true);
    municipalitySelect.setItems([]);

    api('municipalities', { province: province }).then(function (data) {
      municipalitySelect.setItems(data.municipalities.map(function (m) {
        return { value: m.id, label: m.name };
      }));
      municipalitySelect.setDisabled(false);
    }).catch(function (err) { console.error(err); });
  }

  function loadBarangays(municipalityId) {
    barangaySelect.setDisabled(true);
    barangaySelect.setItems([]);

    api('barangays', { municipality_id: municipalityId }).then(function (data) {
      barangaySelect.setItems(data.barangays.map(function (b) {
        return { value: b.id, label: b.name };
      }));
      barangaySelect.setDisabled(false);
    }).catch(function (err) { console.error(err); });
  }

  /* ============================================================
     OFFENSES
     ============================================================ */

  function loadOffenses() {
    api('offenses').then(function (data) {
      offenseSelect.setItems(data.offenses.map(function (o) {
        return {
          value: o.id,
          label: o.name + (o.code ? ' (' + o.code + ')' : ''),
          raw: o
        };
      }));
    }).catch(function (err) { console.error(err); });
  }

  function loadOffenseDetails(id) {
    api('offense', { id: id }).then(function (data) {
      var o = data.offense;
      document.getElementById('inmateOffenseId').value = o.id;
      applyAutoSentence(o);
    }).catch(function (err) { console.error(err); });
  }

  /* ============================================================
     SUBMIT
     ============================================================ */

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    var payload = {
      inmate_number:      document.getElementById('inmateNumber').value,
      jail_unit_id:       document.getElementById('inmateJailUnitId').value,
      case_reference:     document.getElementById('inmateCaseRef').value,
      first_name:         document.getElementById('inmateFirstName').value.trim(),
      middle_name:        document.getElementById('inmateMiddleName').value.trim(),
      last_name:          document.getElementById('inmateLastName').value.trim(),
      suffix:             document.getElementById('inmateSuffix').value.trim(),
      date_of_birth:      document.getElementById('inmateDob').value,
      sex:                document.getElementById('inmateSex').value,
      civil_status:       document.getElementById('inmateCivilStatus').value,
      province:           document.getElementById('inmateProvinceValue').value,
      municipality_id:    document.getElementById('inmateMunicipalityId').value,
      barangay_id:        document.getElementById('inmateBarangayId').value,
      offense_id:         document.getElementById('inmateOffenseId').value,
      classification:     document.getElementById('inmateClassification').value,
      committed_at:       document.getElementById('inmateCommittedAt').value,
      is_drug_case:       document.getElementById('inmateIsDrugCase').checked ? 1 : 0,
      sentence:           sentenceInput.value.trim(),
      sentence_years_min: sentenceMin.value,
      sentence_years_max: sentenceMax.value,
      sentence_is_manual: sentenceManual.value === '1' ? 1 : 0,
      admission_date:     document.getElementById('inmateAdmissionDate').value,
      custody_status:     document.getElementById('inmateCustodyStatus').value,
      notes:              document.getElementById('inmateNotes').value.trim()
    };

    if (!payload.first_name || !payload.last_name) {
      errorBox.textContent = 'First name and last name are required.';
      errorBox.hidden = false;
      return;
    }
    if (!payload.jail_unit_id) {
      errorBox.textContent = 'Please select a jail unit.';
      errorBox.hidden = false;
      return;
    }
    if (!payload.offense_id) {
      errorBox.textContent = 'Please select an offense.';
      errorBox.hidden = false;
      return;
    }

    var fullName = (payload.first_name + ' ' + payload.last_name).trim();
    var confirmed = await window.confirmDialog({
      title: 'Save Inmate Record',
      message: 'Create a new inmate record for "' + fullName + '" with number ' + payload.inmate_number + '?',
      confirmLabel: 'Save Record'
    });
    if (!confirmed) return;

    errorBox.hidden = true;
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    fetch('index.php?route=inmates&action=create', {
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
      window.showSuccess({
        title: 'Inmate Saved',
        message: 'Record ' + payload.inmate_number + ' has been created successfully.'
      });

      form.reset();

      jailUnitSelect.clear();
      provinceSelect.clear();
      municipalitySelect.clear();
      municipalitySelect.setItems([]);
      municipalitySelect.setDisabled(true);
      barangaySelect.clear();
      barangaySelect.setItems([]);
      barangaySelect.setDisabled(true);
      offenseSelect.clear();

      document.getElementById('inmateJailUnitId').value = '';
      document.getElementById('inmateMunicipalityId').value = '';
      document.getElementById('inmateBarangayId').value = '';
      document.getElementById('inmateOffenseId').value = '';
      document.getElementById('inmateClassification').value = '';
      document.getElementById('inmateCommittedAt').value = '';
      document.getElementById('inmateIsDrugCase').checked = false;

      sentenceMin.value = '';
      sentenceMax.value = '';
      sentenceManual.value = '0';
      updateSentenceBadge();

      loadNextPdl();
      loadNextCase();
    }).catch(function (err) {
      errorBox.textContent = err.message;
      errorBox.hidden = false;
    }).finally(function () {
      saveBtn.disabled = false;
      saveBtn.textContent = 'Save Record';
    });
  });

  /* ============================================================
     INITIAL LOAD
     ============================================================ */

  loadNextPdl();
  loadNextCase();
  loadJailUnits();
  loadProvinces();
  loadOffenses();
})();