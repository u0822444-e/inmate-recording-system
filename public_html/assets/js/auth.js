(function () {
  'use strict';

  var roleSection = document.getElementById('roleSection');
  var loginForm   = document.getElementById('loginForm');
  var hiddenRole  = document.getElementById('hiddenRole');
  var alertBox    = document.getElementById('alertBox');
  var formEl      = document.getElementById('loginFormElement');
  var submitBtn   = document.getElementById('submitBtn');
  var usernameEl  = document.getElementById('username');
  var passwordEl  = document.getElementById('password');

  var currentRole = '';

  /* ============================================================
     Enable a field the first time it's focused.
     The field starts as `readonly` to block autofill; we remove
     that attribute on user interaction.
     ============================================================ */

  function unlockField(el) {
    if (!el) return;
    if (el.hasAttribute('readonly')) {
      el.removeAttribute('readonly');
    }
  }

  if (usernameEl) {
    usernameEl.addEventListener('focus', function () { unlockField(usernameEl); });
    usernameEl.addEventListener('mousedown', function () { unlockField(usernameEl); });
    usernameEl.addEventListener('touchstart', function () { unlockField(usernameEl); }, { passive: true });
  }

  if (passwordEl) {
    passwordEl.addEventListener('focus', function () { unlockField(passwordEl); });
    passwordEl.addEventListener('mousedown', function () { unlockField(passwordEl); });
    passwordEl.addEventListener('touchstart', function () { unlockField(passwordEl); }, { passive: true });
  }

  /* ============================================================
     ROLE SELECTION
     ============================================================ */

  window.selectRole = function (role) {
    if (role !== 'Administrator' && role !== 'Staff / Officer') {
      return;
    }

    currentRole = role;
    hiddenRole.value = role;

    var label = document.getElementById('activeRoleLabel');
    if (label) label.textContent = role;

    /* Clear any prior state from the previous role */
    if (formEl) formEl.reset();
    if (alertBox) alertBox.innerHTML = '';

    if (roleSection) roleSection.hidden = true;
    if (loginForm) loginForm.hidden = false;

    /* Re-apply readonly to block autofill after the reset */
    if (usernameEl) usernameEl.setAttribute('readonly', 'readonly');
    if (passwordEl) passwordEl.setAttribute('readonly', 'readonly');

    if (usernameEl) {
      setTimeout(function () { usernameEl.focus(); }, 50);
    }
  };

  /* ============================================================
     RESET ROLE
     ============================================================ */

  window.resetRole = function () {
    if (loginForm) loginForm.hidden = true;
    if (roleSection) roleSection.hidden = false;
    if (alertBox) alertBox.innerHTML = '';
    if (formEl) formEl.reset();

    currentRole = '';
    if (hiddenRole) hiddenRole.value = '';

    if (usernameEl) usernameEl.setAttribute('readonly', 'readonly');
    if (passwordEl) passwordEl.setAttribute('readonly', 'readonly');
  };

  /* ============================================================
     SHOW / HIDE PASSWORD
     ============================================================ */

  window.togglePasswordVisibility = function () {
    if (!passwordEl) return;

    var btn = document.getElementById('togglePassword');
    if (!btn) return;

    unlockField(passwordEl);

    var isHidden = passwordEl.type === 'password';
    passwordEl.type = isHidden ? 'text' : 'password';

    var icon = btn.querySelector('i');
    if (icon) {
      icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    }

    passwordEl.focus();
  };

  /* ============================================================
     SUBMIT LOGIN
     ============================================================ */

  window.submitLogin = function (e) {
    e.preventDefault();

    /* Role must be explicitly selected */
    if (!currentRole) {
      if (alertBox) alertBox.innerHTML = '<div class="alert alert--error">Select an account type.</div>';
      return;
    }

    /* Guard against tampering with the hidden field */
    if (hiddenRole.value !== currentRole) {
      if (alertBox) alertBox.innerHTML = '<div class="alert alert--error">Account type mismatch. Please try again.</div>';
      return;
    }

    var username = usernameEl ? usernameEl.value.trim() : '';
    var password = passwordEl ? passwordEl.value : '';

    if (!username || !password) {
      if (alertBox) alertBox.innerHTML = '<div class="alert alert--error">Enter username and password.</div>';
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing in...';

    var fd = new FormData();
    fd.append('role', currentRole);
    fd.append('username', username);
    fd.append('password', password);

    fetch('index.php?route=login', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) {
          if (alertBox) alertBox.innerHTML = '<div class="alert alert--error">' + data.message + '</div>';
          submitBtn.disabled = false;
          submitBtn.textContent = 'Sign in';
          return;
        }

        if (alertBox) alertBox.innerHTML = '<div class="alert alert--success">Sign-in successful.</div>';

        setTimeout(function () {
          window.location.href = 'index.php';
        }, 250);
      })
      .catch(function () {
        if (alertBox) alertBox.innerHTML = '<div class="alert alert--error">Connection error.</div>';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Sign in';
      });
  };
})();