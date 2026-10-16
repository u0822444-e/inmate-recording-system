(function () {
  "use strict";

  var section = document.getElementById("dashboard-users");
  if (!section) return;

  /* ---------- Table refs ---------- */
  var tbody = document.getElementById("userTableBody");
  var search = document.getElementById("userSearch");
  var roleFilter = document.getElementById("userRoleFilter");
  var statusFilter = document.getElementById("userStatusFilter");
  var createBtn = document.getElementById("userCreateBtn");
  var archiveBtn = document.getElementById("userArchiveToggleBtn");
  var archiveLabel = document.getElementById("userArchiveToggleLabel");
  var jailUnitFilter = document.getElementById("userJailUnitFilter");

  /* ---------- Modal refs ---------- */
  var modal = document.getElementById("userModal");
  var form = document.getElementById("userForm");
  var formError = document.getElementById("userFormError");
  var saveBtn = document.getElementById("userSaveBtn");

  var emailInput = document.getElementById("userEmail");
  var emailHint = document.getElementById("userEmailHint");
  var phoneInput = document.getElementById("userPhone");
  var phoneHint = document.getElementById("userPhoneHint");

  // All modal input refs — declared once, at the top, before any listener uses them
  var firstInput = document.getElementById("userFirstName");
  var lastInput = document.getElementById("userLastName");
  var usernameInput = document.getElementById("userUsername");
  var passwordInput = document.getElementById("userPassword");
  var passwordConfirmInput = document.getElementById("userPasswordConfirm");
  var passwordHint = document.getElementById("userPasswordHint");
  var passwordConfirmHint = document.getElementById("userPasswordConfirmHint");

  /* ---------- State ---------- */
  var users = [];
  var loaded = false;
  var scope = "active";

  /* ---------- Jail unit SearchableSelect ---------- */
  var jailUnitSelect = null;
  var jailUnitWrap = document.getElementById("userJailUnitWrap");
  var jailUnitInput = document.getElementById("userJailUnitId");

  if (jailUnitWrap && window.SearchableSelect) {
    jailUnitSelect = new SearchableSelect({
      container: jailUnitWrap,
      placeholder: "— Unassigned —",
      onChange: function (value) {
        if (jailUnitInput) jailUnitInput.value = value || "";
      },
    });
  }

  /* ============================================================
     HELPERS
     ============================================================ */

  /**
   * Set a <select>'s value. If the value doesn't exist as an <option>,
   * inject it so historic free-text entries aren't lost.
   */
  function setSelectValue(selectEl, value) {
    if (!selectEl) return;
    if (!value) {
      selectEl.value = "";
      return;
    }
    var found = false;
    for (var i = 0; i < selectEl.options.length; i++) {
      if (selectEl.options[i].value === value) {
        found = true;
        break;
      }
    }
    if (!found) {
      var opt = document.createElement("option");
      opt.value = value;
      opt.textContent = value;
      selectEl.appendChild(opt);
    }
    selectEl.value = value;
  }

  function loadJailUnitFilter() {
    if (!jailUnitFilter) return;
    fetch("index.php?route=reference&action=jail-units", {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data.success) return;
        (data.jail_units || []).forEach(function (u) {
          var opt = document.createElement("option");
          opt.value = u.id;
          opt.textContent = u.name + " (" + u.dormitory + ")";
          jailUnitFilter.appendChild(opt);
        });
      })
      .catch(function (err) {
        console.error("jail unit filter:", err);
      });
  }

  function loadJailUnits() {
    if (!jailUnitSelect) return;
    fetch("index.php?route=reference&action=jail-units", {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data.success) return;
        jailUnitSelect.setItems(
          (data.jail_units || []).map(function (u) {
            return { value: u.id, label: u.name + " (" + u.dormitory + ")" };
          })
        );
      })
      .catch(function (err) {
        console.error("jail units:", err);
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
    var clean = value.replace(/[\s\-()]/g, "");
    return PHONE_REGEX.test(clean);
  }

  function clearFieldError(inputEl, hintEl, defaultHint) {
    if (!inputEl || !hintEl) return;
    inputEl.classList.remove("is-invalid", "is-valid");
    hintEl.textContent = defaultHint;
    hintEl.classList.remove("is-error", "is-valid");
  }

  function showFieldError(inputEl, hintEl, message) {
    if (!inputEl || !hintEl) return;
    inputEl.classList.remove("is-valid");
    inputEl.classList.add("is-invalid");
    hintEl.textContent = message;
    hintEl.classList.remove("is-valid");
    hintEl.classList.add("is-error");
  }

  function showFieldValid(inputEl, hintEl, message) {
    if (!inputEl || !hintEl) return;
    inputEl.classList.remove("is-invalid");
    inputEl.classList.add("is-valid");
    hintEl.textContent = message;
    hintEl.classList.remove("is-error");
    hintEl.classList.add("is-valid");
  }

  function esc(v) {
    var d = document.createElement("div");
    d.textContent = String(v == null ? "" : v);
    return d.innerHTML;
  }

  function api(action, options) {
    options = options || {};
    return fetch(
      "index.php?route=users&action=" + action,
      Object.assign(
        {
          credentials: "same-origin",
          headers: { Accept: "application/json" },
        },
        options
      )
    ).then(function (r) {
      return r.json().then(function (data) {
        if (!r.ok || !data.success) throw new Error(data.message || "Error");
        return data;
      });
    });
  }

  /* ============================================================
     RENDER
     ============================================================ */

  function render() {
    if (!users.length) {
      tbody.innerHTML =
        '<tr><td colspan="8" class="data-table__empty">' +
        (scope === "archived" ? "No archived users." : "No users found.") +
        "</td></tr>";
      return;
    }

    tbody.innerHTML = users
      .map(function (u) {
        var roleClass = u.role === "Administrator" ? "admin" : "staff";
        var statusClass = u.is_active ? "status-pill--on" : "status-pill--off";
        var statusText = u.is_active ? "Active" : "Inactive";

        var name =
          u.full_name ||
          ((u.first_name || "") + " " + (u.last_name || "")).trim() ||
          u.username;

        var actions;
        if (scope === "archived") {
          actions =
            '<button type="button" class="icon-btn" data-action="restore" title="Restore">' +
            '<i class="bi bi-arrow-counterclockwise"></i>' +
            "</button>" +
            '<button type="button" class="icon-btn icon-btn--danger" data-action="delete" title="Permanently delete">' +
            '<i class="bi bi-trash"></i>' +
            "</button>";
        } else {
          actions =
            '<button type="button" class="icon-btn" data-action="edit" title="Edit">' +
            '<i class="bi bi-pencil"></i>' +
            "</button>" +
            '<button type="button" class="icon-btn" data-action="toggle" title="Toggle status">' +
            '<i class="bi bi-toggle-on"></i>' +
            "</button>" +
            '<button type="button" class="icon-btn" data-action="archive" title="Archive">' +
            '<i class="bi bi-archive"></i>' +
            "</button>";
        }

        return (
          '<tr data-id="' +
          u.id +
          '">' +
          "<td><strong>" +
          esc(name) +
          "</strong><br>" +
          '<small class="meta-text">' +
          esc(u.username) +
          "</small></td>" +
          "<td>" +
          esc(u.jail_unit_name || "—") +
          "</td>" +
          "<td>" +
          esc(u.employee_no || "—") +
          "</td>" +
          "<td>" +
          esc(u.bjmp_rank || u.position || u.rank || "—") +
          "</td>" +
          '<td><span class="role-badge role-badge--' +
          roleClass +
          '">' +
          esc(u.role) +
          "</span></td>" +
          '<td><span class="status-pill ' +
          statusClass +
          '">' +
          statusText +
          "</span></td>" +
          '<td class="ta-right"><div class="row-actions">' +
          actions +
          "</div></td>" +
          "</tr>"
        );
      })
      .join("");
  }

  /* ============================================================
     LOAD
     ============================================================ */

  function load(force) {
    if (loaded && !force) return;

    var params = new URLSearchParams();
    if (search && search.value.trim()) params.set("q", search.value.trim());
    if (roleFilter && roleFilter.value) params.set("role", roleFilter.value);
    if (jailUnitFilter && jailUnitFilter.value)
      params.set("jail_unit_id", jailUnitFilter.value);
    if (statusFilter && statusFilter.value)
      params.set("status", statusFilter.value);
    params.set("scope", scope);

    tbody.innerHTML =
      '<tr><td colspan="8" class="data-table__empty">Loading...</td></tr>';

    api("list&" + params.toString())
      .then(function (data) {
        users = data.users || [];
        loaded = true;
        render();
      })
      .catch(function (err) {
        tbody.innerHTML =
          '<tr><td colspan="8" class="data-table__empty">' +
          esc(err.message) +
          "</td></tr>";
      });
  }

  /* ============================================================
     USERNAME AUTO-GENERATION
     ============================================================ */

  var usernameTouched = false;
  var unameTimer;

  function syncUsername() {
    if (usernameTouched) return;

    clearTimeout(unameTimer);
    unameTimer = setTimeout(function () {
      var first = document.getElementById("userFirstName").value.trim();
      var last = document.getElementById("userLastName").value.trim();
      if (!first && !last) return;

      var id = document.getElementById("userId").value;
      var url =
        "index.php?route=users&action=suggest-username" +
        "&first_name=" +
        encodeURIComponent(first) +
        "&last_name=" +
        encodeURIComponent(last) +
        "&id=" +
        (id || "0");

      fetch(url, {
        credentials: "same-origin",
        headers: { Accept: "application/json" },
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.success && data.username) {
            document.getElementById("userUsername").value = data.username;
          }
        })
        .catch(function () {
          /* silent */
        });
    }, 250);
  }

  /* ============================================================
     MODAL
     ============================================================ */

  function openModal(user) {
    if (!modal) return;
    form.reset();
    formError.hidden = true;
    formError.textContent = "";
    usernameTouched = false;

    if (passwordConfirmInput) passwordConfirmInput.value = "";

    clearFieldError(emailInput, emailHint, "Optional");
    clearFieldError(phoneInput, phoneHint, "Optional");
    clearFieldError(passwordInput, passwordHint, "min 8 characters");
    clearFieldError(passwordConfirmInput, passwordConfirmHint, "retype password");

    document.getElementById("userModalTitle").textContent = user
      ? "Edit User"
      : "New User";
    document.getElementById("userId").value = user ? user.id : "";

    if (jailUnitSelect) jailUnitSelect.clear();
    if (jailUnitInput) jailUnitInput.value = "";

    if (user) {
      document.getElementById("userFirstName").value = user.first_name || "";
      document.getElementById("userMiddleName").value = user.middle_name || "";
      document.getElementById("userLastName").value = user.last_name || "";
      document.getElementById("userUsername").value = user.username || "";
      document.getElementById("userEmail").value = user.email || "";
      document.getElementById("userPhone").value = user.phone || "";
      document.getElementById("userBirthdate").value = user.birthdate || "";
      document.getElementById("userSex").value = user.sex || "Unspecified";
      document.getElementById("userCivilStatus").value =
        user.civil_status || "";
      document.getElementById("userEmployeeNo").value = user.employee_no || "";
      document.getElementById("userDepartment").value = user.department || "";
      document.getElementById("userDateHired").value = user.date_hired || "";
      document.getElementById("userEmploymentStatus").value =
        user.employment_status || "";
      document.getElementById("userRole").value =
        user.role || "Staff / Officer";
      document.getElementById("userPersonnelType").value =
        user.personnel_type || "";
      document.getElementById("userEligibility").value = user.eligibility || "";
      document.getElementById("userPasswordHint").textContent =
        "leave blank to keep current";

      // Dropdowns: safe-set with fallback injection
      setSelectValue(
        document.getElementById("userPosition"),
        user.position || ""
      );
      setSelectValue(
        document.getElementById("userBjmpRank"),
        user.bjmp_rank || ""
      );

      if (user.jail_unit_id && jailUnitSelect) {
        jailUnitSelect.setValue(user.jail_unit_id);
        jailUnitInput.value = user.jail_unit_id;
      }

      usernameTouched = true;
    } else {
      document.getElementById("userPasswordHint").textContent =
        "min 8 characters";
    }

    modal.classList.remove("is-hidden");
    document.body.classList.add("modal-open");
    document.getElementById("userFirstName").focus();
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.add("is-hidden");
    document.body.classList.remove("modal-open");
  }

  if (createBtn) {
    createBtn.addEventListener("click", function () {
      openModal(null);
    });
  }

  // Modal only closes via explicit [data-modal-close] buttons (X or Cancel)
  // — no backdrop click, no Escape key
  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target.closest("[data-modal-close]")) closeModal();
    });
  }

  if (jailUnitFilter) {
    jailUnitFilter.addEventListener("change", function () {
      load(true);
    });
  }

  if (firstInput) firstInput.addEventListener("input", syncUsername);
  if (lastInput) lastInput.addEventListener("input", syncUsername);

  if (usernameInput) {
    usernameInput.addEventListener("input", function () {
      usernameTouched = true;
    });
  }

  if (emailInput) {
    emailInput.addEventListener("input", function () {
      if (!emailInput.value.trim()) {
        clearFieldError(emailInput, emailHint, "Optional");
        return;
      }
      if (!validateEmail(emailInput.value.trim())) {
        showFieldError(emailInput, emailHint, "Invalid email address");
      } else {
        showFieldValid(emailInput, emailHint, "Valid");
      }
    });
  }

  if (phoneInput) {
    phoneInput.addEventListener("input", function () {
      if (!phoneInput.value.trim()) {
        clearFieldError(phoneInput, phoneHint, "Optional");
        return;
      }
      if (!validatePhone(phoneInput.value.trim())) {
        showFieldError(
          phoneInput,
          phoneHint,
          "Invalid phone (e.g., 09171234567)"
        );
      } else {
        showFieldValid(phoneInput, phoneHint, "Valid");
      }
    });
  }

  /* ---------- Password confirm live validation ---------- */
  function updatePasswordConfirmState() {
    if (!passwordConfirmInput || !passwordInput) return;

    var pw = passwordInput.value;
    var confirm = passwordConfirmInput.value;

    // Both empty — reset to defaults
    if (pw === "" && confirm === "") {
      clearFieldError(passwordInput, passwordHint, "min 8 characters");
      clearFieldError(
        passwordConfirmInput,
        passwordConfirmHint,
        "retype password"
      );
      return;
    }

    // Password length check
    if (pw !== "" && pw.length < 8) {
      showFieldError(
        passwordInput,
        passwordHint,
        "Must be at least 8 characters"
      );
    } else if (pw !== "") {
      showFieldValid(passwordInput, passwordHint, "OK");
    } else {
      clearFieldError(passwordInput, passwordHint, "min 8 characters");
    }

    // Confirm match check
    if (confirm === "") {
      clearFieldError(
        passwordConfirmInput,
        passwordConfirmHint,
        "retype password"
      );
    } else if (pw !== confirm) {
      showFieldError(
        passwordConfirmInput,
        passwordConfirmHint,
        "Passwords do not match"
      );
    } else if (pw.length >= 8) {
      showFieldValid(
        passwordConfirmInput,
        passwordConfirmHint,
        "Passwords match"
      );
    } else {
      clearFieldError(
        passwordConfirmInput,
        passwordConfirmHint,
        "retype password"
      );
    }
  }

  if (passwordInput) {
    passwordInput.addEventListener("input", updatePasswordConfirmState);
  }
  if (passwordConfirmInput) {
    passwordConfirmInput.addEventListener("input", updatePasswordConfirmState);
  }

  /* ============================================================
     SUBMIT FORM
     ============================================================ */

  if (form) {
    form.addEventListener("submit", async function (e) {
      e.preventDefault();
      var id = document.getElementById("userId").value;

      var payload = {
        id: id ? Number(id) : 0,
        first_name: document.getElementById("userFirstName").value.trim(),
        middle_name: document.getElementById("userMiddleName").value.trim(),
        last_name: document.getElementById("userLastName").value.trim(),
        username: document.getElementById("userUsername").value.trim(),
        email: document.getElementById("userEmail").value.trim(),
        phone: document.getElementById("userPhone").value.trim(),
        birthdate: document.getElementById("userBirthdate").value,
        sex: document.getElementById("userSex").value,
        civil_status: document.getElementById("userCivilStatus").value,
        employee_no: document.getElementById("userEmployeeNo").value.trim(),
        position: document.getElementById("userPosition").value,
        department: document.getElementById("userDepartment").value.trim(),
        date_hired: document.getElementById("userDateHired").value,
        employment_status: document.getElementById("userEmploymentStatus")
          .value,
        role: document.getElementById("userRole").value,
        password: document.getElementById("userPassword").value,
        bjmp_rank: document.getElementById("userBjmpRank").value,
        personnel_type: document.getElementById("userPersonnelType").value,
        eligibility: document.getElementById("userEligibility").value.trim(),
        jail_unit_id: jailUnitInput ? jailUnitInput.value : "",
      };

      if (!payload.first_name || !payload.last_name) {
        formError.textContent = "First name and last name are required.";
        formError.hidden = false;
        return;
      }

      if (!validateEmail(payload.email)) {
        formError.textContent = "Please enter a valid email address.";
        formError.hidden = false;
        if (emailInput) emailInput.focus();
        return;
      }

      if (!validatePhone(payload.phone)) {
        formError.textContent = "Please enter a valid phone number.";
        formError.hidden = false;
        if (phoneInput) phoneInput.focus();
        return;
      }

      // ---------- Password checks ----------
      var pw = passwordInput ? passwordInput.value : "";
      var pwConfirm = passwordConfirmInput ? passwordConfirmInput.value : "";

      // On create, password is required
      if (!payload.id && pw.length < 8) {
        formError.textContent = "Password must be at least 8 characters.";
        formError.hidden = false;
        if (passwordInput) passwordInput.focus();
        return;
      }

      // On update, password is optional — but if filled, must be valid
      if (payload.id && pw !== "" && pw.length < 8) {
        formError.textContent = "Password must be at least 8 characters.";
        formError.hidden = false;
        if (passwordInput) passwordInput.focus();
        return;
      }

      // Confirm must match when either is filled
      if (pw !== "" || pwConfirm !== "") {
        if (pw !== pwConfirm) {
          formError.textContent = "Passwords do not match.";
          formError.hidden = false;
          if (passwordConfirmInput) passwordConfirmInput.focus();
          return;
        }
      }

      var fullName =
        (payload.first_name + " " + payload.last_name).trim() ||
        payload.username;

      var confirmed = await window.confirmDialog({
        title: payload.id ? "Save Changes" : "Create User",
        message: payload.id
          ? 'Apply the changes to "' + fullName + '"?'
          : 'Create a new user account for "' + fullName + '"?',
        confirmLabel: payload.id ? "Save Changes" : "Create User",
      });
      if (!confirmed) return;

      saveBtn.disabled = true;
      saveBtn.textContent = "Saving...";
      formError.hidden = true;

      api(id ? "update" : "create", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      })
        .then(function () {
          closeModal();
          window.showSuccess({
            title: payload.id ? "User Updated" : "User Created",
            message:
              fullName +
              " has been " +
              (payload.id ? "updated" : "created") +
              " successfully.",
          });
          load(true);
        })
        .catch(function (err) {
          formError.textContent = err.message;
          formError.hidden = false;
        })
        .finally(function () {
          saveBtn.disabled = false;
          saveBtn.textContent = "Save";
        });
    });
  }

  /* ============================================================
     TABLE ACTIONS
     ============================================================ */

  if (tbody) {
    tbody.addEventListener("click", async function (e) {
      var btn = e.target.closest("button[data-action]");
      if (!btn) return;
      var id = Number(btn.closest("tr").dataset.id);
      var user = users.find(function (u) {
        return u.id === id;
      });
      if (!user) return;

      var name = user.full_name || user.username;
      var action = btn.dataset.action;

      if (action === "edit") {
        openModal(user);
        return;
      }

      if (action === "toggle") {
        var ok1 = await window.confirmDialog({
          title: "Change Status",
          message:
            (user.is_active ? "Deactivate" : "Activate") + ' "' + name + '"?',
          confirmLabel: user.is_active ? "Deactivate" : "Activate",
        });
        if (!ok1) return;

        api("toggle", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: id }),
        })
          .then(function () {
            window.showSuccess({
              title: "Status Updated",
              message:
                name +
                " has been " +
                (user.is_active ? "deactivated" : "activated") +
                ".",
            });
            load(true);
          })
          .catch(function (err) {
            alert(err.message);
          });
        return;
      }

      if (action === "archive") {
        var ok2 = await window.confirmDialog({
          title: "Archive User",
          message: 'Archive "' + name + '"?',
          confirmLabel: "Archive",
          variant: "warning",
        });
        if (!ok2) return;

        api("archive", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: id }),
        })
          .then(function () {
            window.showSuccess({
              title: "User Archived",
              message: name + " has been archived.",
            });
            load(true);
          })
          .catch(function (err) {
            alert(err.message);
          });
        return;
      }

      if (action === "restore") {
        var ok3 = await window.confirmDialog({
          title: "Restore User",
          message: 'Restore "' + name + '" to the active list?',
          confirmLabel: "Restore",
        });
        if (!ok3) return;

        api("restore", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: id }),
        })
          .then(function () {
            window.showSuccess({
              title: "User Restored",
              message: name + " has been restored.",
            });
            load(true);
          })
          .catch(function (err) {
            alert(err.message);
          });
        return;
      }

      if (action === "delete") {
        var ok4 = await window.confirmDialog({
          title: "Delete Permanently",
          message: 'Permanently delete "' + name + '"? This cannot be undone.',
          confirmLabel: "Delete Forever",
          variant: "danger",
        });
        if (!ok4) return;

        api("delete", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: id }),
        })
          .then(function () {
            window.showSuccess({
              title: "User Deleted",
              message: name + " has been permanently deleted.",
            });
            load(true);
          })
          .catch(function (err) {
            alert(err.message);
          });
      }
    });
  }

  /* ============================================================
     ARCHIVE TOGGLE
     ============================================================ */

  if (archiveBtn) {
    archiveBtn.addEventListener("click", function () {
      scope = scope === "archived" ? "active" : "archived";
      loaded = false;

      archiveLabel.textContent =
        scope === "archived" ? "View Active" : "View Archived";

      if (createBtn) createBtn.hidden = scope === "archived";

      load(true);
    });
  }

  /* ============================================================
     FILTERS
     ============================================================ */

  var timer;
  if (search) {
    search.addEventListener("input", function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        load(true);
      }, 250);
    });
  }
  if (roleFilter)
    roleFilter.addEventListener("change", function () {
      load(true);
    });
  if (statusFilter)
    statusFilter.addEventListener("change", function () {
      load(true);
    });

  /* ============================================================
     SECTION EVENTS
     ============================================================ */

  document.addEventListener("section:changed", function (e) {
    if (e.detail && e.detail.section === "users") {
      loadJailUnits();
      load();
    }
  });

  loadJailUnitFilter();
  loadJailUnits();
  if (section.classList.contains("is-visible")) load();
})();