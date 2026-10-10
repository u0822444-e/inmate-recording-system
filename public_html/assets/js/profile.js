(function () {
  'use strict';

  var section = document.getElementById('dashboard-profile');
  if (!section) return;

  /* ---------- Refs ---------- */
  var viewMode     = document.getElementById('profileViewMode');
  var editForm     = document.getElementById('profileEditForm');
  var editToggle   = document.getElementById('profileEditToggle');
  var cancelBtn    = document.getElementById('profileCancelBtn');
  var saveBtn      = document.getElementById('profileSaveBtn');
  var formError    = document.getElementById('profileEditError');

  var emailInput = document.getElementById('profileEditEmail');
  var emailHint  = document.getElementById('profileEditEmailHint');
  var phoneInput = document.getElementById('profileEditPhone');
  var phoneHint  = document.getElementById('profileEditPhoneHint');

  /* ---------- State ---------- */
  var currentProfile = null;
  var loaded = false;

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

  function formatDate(iso) {
    if (!iso) return '—';
    var d = new Date(iso);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function formatDateTime(iso) {
    if (!iso) return '—';
    var d = new Date(iso.replace(' ', 'T'));
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
  }

  function setText(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = orDash(value);
  }

  function api(action, options) {
    options = options || {};
    return fetch(
      'index.php?route=profile&action=' + action,
      Object.assign(
        { credentials: 'same-origin', headers: { 'Accept': 'application/json' } },
        options
      )
    ).then(function (r) {
      return r.json().then(function (data) {
        if (!r.ok || !data.success) throw new Error(data.message || 'Error');
        return data;
      });
    });
  }

  /* ============================================================
     VALIDATION
     ============================================================ */

  var EMAIL_REGEX = /^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/;
  var PHONE_REGEX = /^09[0-9]{9}$/;

  function validateEmail(value) {
    if (!value) return true;
    return EMAIL_REGEX.test(value);
  }

  function validatePhone(value) {
    if (!value) return true;
    var clean = value.replace(/[\s\-()]/g, '');
    return PHONE_REGEX.test(clean);
  }

  function clearFieldError(inputEl, hintEl, defaultHint) {
    if (!inputEl || !hintEl) return;
    inputEl.classList.remove('is-invalid', 'is-valid');
    hintEl.textContent = defaultHint;
    hintEl.classList.remove('is-error', 'is-valid');
  }

  function showFieldError(inputEl, hintEl, message) {
    if (!inputEl || !hintEl) return;
    inputEl.classList.remove('is-valid');
    inputEl.classList.add('is-invalid');
    hintEl.textContent = message;
    hintEl.classList.remove('is-valid');
    hintEl.classList.add('is-error');
  }

  function showFieldValid(inputEl, hintEl, message) {
    if (!inputEl || !hintEl) return;
    inputEl.classList.remove('is-invalid');
    inputEl.classList.add('is-valid');
    hintEl.textContent = message;
    hintEl.classList.remove('is-error');
    hintEl.classList.add('is-valid');
  }

  if (emailInput) {
    emailInput.addEventListener('input', function () {
      if (!emailInput.value.trim()) {
        clearFieldError(emailInput, emailHint, 'Optional');
        return;
      }
      if (!validateEmail(emailInput.value.trim())) {
        showFieldError(emailInput, emailHint, 'Invalid email address');
      } else {
        showFieldValid(emailInput, emailHint, 'Valid');
      }
    });
  }

  if (phoneInput) {
    phoneInput.addEventListener('input', function () {
      if (!phoneInput.value.trim()) {
        clearFieldError(phoneInput, phoneHint, 'Optional');
        return;
      }
      if (!validatePhone(phoneInput.value.trim())) {
        showFieldError(phoneInput, phoneHint, 'Invalid phone (e.g., 09171234567)');
      } else {
        showFieldValid(phoneInput, phoneHint, 'Valid');
      }
    });
  }

  /* ============================================================
     RENDER VIEW
     ============================================================ */

  function render(profile) {
    currentProfile = profile;

    var fullName = profile.full_name ||
      [(profile.first_name || ''), (profile.middle_name || ''), (profile.last_name || '')]
        .filter(Boolean).join(' ').trim();

    var initial = (fullName || profile.username || 'U').charAt(0).toUpperCase();

    // Hero
    document.getElementById('profileAvatarInitial').textContent = initial;
    document.getElementById('profileFullName').textContent      = orDash(fullName);
    document.getElementById('profileRoleLine').textContent      = orDash(profile.role);

    // Account
    setText('profileUsername', profile.username);
    setText('profileRole', profile.role);
    setText('profileJailUnit', profile.jail_unit_name || 'Unassigned');
    setText('profileEmployeeNo', profile.employee_no);

    // Personal
    setText('profilePersonalName', fullName);
    setText('profileSex', profile.sex);
    setText('profileDob', formatDate(profile.birthdate));
    setText('profileCivilStatus', profile.civil_status);

    // Contact
    setText('profileEmail', profile.email);
    setText('profilePhone', profile.phone);

    // Employment
    setText('profilePosition', profile.position);
    setText('profileBjmpRank', profile.bjmp_rank);
    setText('profileDepartment', profile.department);
    setText('profileDateHired', formatDate(profile.date_hired));
    setText('profileEmploymentStatus', profile.employment_status);
    setText('profilePersonnelType', profile.personnel_type);
    setText('profileEligibility', profile.eligibility);

    // Summary
    setText('profileMemberSince', formatDate(profile.created_at));
    setText('profileLastUpdated', formatDateTime(profile.updated_at));

    var pill = document.getElementById('profileStatusPill');
    if (pill) {
      pill.textContent = profile.is_active ? 'Active' : 'Inactive';
      pill.className = 'status-pill ' + (profile.is_active ? 'status-pill--on' : 'status-pill--off');
    }
  }

  /* ============================================================
     EDIT MODE TOGGLE
     ============================================================ */

  function enterEditMode() {
    if (!currentProfile) return;

    document.getElementById('profileEditFirstName').value    = currentProfile.first_name || '';
    document.getElementById('profileEditMiddleName').value   = currentProfile.middle_name || '';
    document.getElementById('profileEditLastName').value     = currentProfile.last_name || '';
    document.getElementById('profileEditDob').value          = (currentProfile.birthdate || '').slice(0, 10);
    document.getElementById('profileEditSex').value          = currentProfile.sex || '';
    document.getElementById('profileEditCivilStatus').value  = currentProfile.civil_status || '';
    document.getElementById('profileEditEmail').value        = currentProfile.email || '';
    document.getElementById('profileEditPhone').value        = currentProfile.phone || '';
    document.getElementById('profileEditCurrentPassword').value = '';
    document.getElementById('profileEditNewPassword').value     = '';
    document.getElementById('profileEditConfirmPassword').value = '';

    clearFieldError(emailInput, emailHint, 'Optional');
    clearFieldError(phoneInput, phoneHint, 'Optional');

    formError.hidden = true;
    formError.textContent = '';

    viewMode.classList.add('is-hidden');
    editForm.classList.remove('is-hidden');
    editToggle.hidden = true;
  }

  function exitEditMode() {
    viewMode.classList.remove('is-hidden');
    editForm.classList.add('is-hidden');
    editToggle.hidden = false;
    formError.hidden = true;
  }

  if (editToggle) {
    editToggle.addEventListener('click', enterEditMode);
  }
  if (cancelBtn) {
    cancelBtn.addEventListener('click', exitEditMode);
  }

  /* ============================================================
     SUBMIT
     ============================================================ */

  if (editForm) {
    editForm.addEventListener('submit', async function (e) {
      e.preventDefault();

      var payload = {
        first_name:       document.getElementById('profileEditFirstName').value.trim(),
        middle_name:      document.getElementById('profileEditMiddleName').value.trim(),
        last_name:        document.getElementById('profileEditLastName').value.trim(),
        birthdate:        document.getElementById('profileEditDob').value,
        sex:              document.getElementById('profileEditSex').value,
        civil_status:     document.getElementById('profileEditCivilStatus').value,
        email:            document.getElementById('profileEditEmail').value.trim(),
        phone:            document.getElementById('profileEditPhone').value.trim(),
        current_password: document.getElementById('profileEditCurrentPassword').value,
        new_password:     document.getElementById('profileEditNewPassword').value,
        confirm_password: document.getElementById('profileEditConfirmPassword').value,
      };

      if (!payload.first_name || !payload.last_name) {
        formError.textContent = 'First name and last name are required.';
        formError.hidden = false;
        return;
      }

      if (!validateEmail(payload.email)) {
        formError.textContent = 'Please enter a valid email address.';
        formError.hidden = false;
        if (emailInput) emailInput.focus();
        return;
      }

      if (!validatePhone(payload.phone)) {
        formError.textContent = 'Please enter a valid phone number.';
        formError.hidden = false;
        if (phoneInput) phoneInput.focus();
        return;
      }

      if (payload.new_password !== '' && payload.new_password !== payload.confirm_password) {
        formError.textContent = 'New password and confirmation do not match.';
        formError.hidden = false;
        return;
      }

      var confirmed = await window.confirmDialog({
        title: 'Save Changes',
        message: 'Apply the changes to your profile?',
        confirmLabel: 'Save Changes',
      });
      if (!confirmed) return;

      formError.hidden = true;
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      api('update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      })
        .then(function () {
          exitEditMode();
          window.showSuccess({
            title: 'Profile Updated',
            message: 'Your profile has been updated successfully.',
          });
          load(true);
        })
        .catch(function (err) {
          formError.textContent = err.message;
          formError.hidden = false;
        })
        .finally(function () {
          saveBtn.disabled = false;
          saveBtn.textContent = 'Save Changes';
        });
    });
  }

  /* ============================================================
     LOAD
     ============================================================ */

  function load(force) {
    if (loaded && !force) return;

    api('get').then(function (data) {
      render(data.profile || {});
      loaded = true;
    }).catch(function (err) {
      console.error('profile:', err);
    });
  }

  /* ============================================================
     SECTION ENTRY
     ============================================================ */

  document.addEventListener('section:changed', function (e) {
    if (e.detail && e.detail.section === 'profile') {
      exitEditMode();
      load();
    }
  });

  if (section.classList.contains('is-visible')) {
    load();
  }
})();