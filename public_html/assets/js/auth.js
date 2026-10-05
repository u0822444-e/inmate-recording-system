(function () {
  'use strict';

  var roleSection = document.getElementById('roleSection');
  var loginForm   = document.getElementById('loginForm');
  var hiddenRole  = document.getElementById('hiddenRole');
  var alertBox    = document.getElementById('alertBox');
  var formEl      = document.getElementById('loginFormElement');
  var submitBtn   = document.getElementById('submitBtn');

  var currentRole = '';

  window.selectRole = function (role) {
    currentRole = role;
    hiddenRole.value = role;

    var label = document.getElementById('activeRoleLabel');
    if (label) label.textContent = role;

    if (roleSection) roleSection.hidden = true;
    if (loginForm) loginForm.hidden = false;

    var u = document.getElementById('username');
    if (u) u.focus();
  };

  window.resetRole = function () {
    if (loginForm) loginForm.hidden = true;
    if (roleSection) roleSection.hidden = false;
    if (alertBox) alertBox.innerHTML = '';
    if (formEl) formEl.reset();
    currentRole = '';
    if (hiddenRole) hiddenRole.value = '';
  };

  window.togglePasswordVisibility = function () {
    var input = document.getElementById('password');
    var btn = document.getElementById('togglePassword');
    if (!input || !btn) return;

    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.querySelector('i').className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    input.focus();
  };

  window.submitLogin = function (e) {
    e.preventDefault();

    var username = document.getElementById('username').value.trim();
    var password = document.getElementById('password').value;

    if (!currentRole) {
      alertBox.innerHTML = '<div class="alert alert--error">Select an account type.</div>';
      return;
    }
    if (!username || !password) {
      alertBox.innerHTML = '<div class="alert alert--error">Enter username and password.</div>';
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
          alertBox.innerHTML = '<div class="alert alert--error">' + data.message + '</div>';
          submitBtn.disabled = false;
          submitBtn.textContent = 'Sign in';
          return;
        }
        window.location.href = 'index.php';
      })
      .catch(function () {
        alertBox.innerHTML = '<div class="alert alert--error">Connection error.</div>';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Sign in';
      });
  };
})();