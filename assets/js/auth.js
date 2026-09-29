/* ============================================================
   Authentication — role selection + login handler
   ============================================================ */

const authScreen   = document.getElementById('authScreen');
const roleSection  = document.getElementById('roleSection');
const loginForm    = document.getElementById('loginForm');
const hiddenRole   = document.getElementById('hiddenRole');
const alertBox     = document.getElementById('alertBox');
const formElement  = document.getElementById('loginFormElement');
const submitBtn    = document.getElementById('submitBtn');

let currentRole = '';

function selectRole(role) {
  currentRole = role;
  hiddenRole.value = role;

  roleSection.hidden = true;
  loginForm.hidden = false;
  document.getElementById('username').focus();
}

function resetRole() {
  loginForm.hidden = true;
  roleSection.hidden = false;
  alertBox.innerHTML = '';
  formElement.reset();
  currentRole = '';
}

function renderAlert(success, message) {
  const icon = success
    ? '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
    : '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';

  const type = success ? 'success' : 'error';
  return `<div class="alert alert--${type}">${icon}${escapeHtml(message)}</div>`;
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

async function submitLogin(event) {
  event.preventDefault();

  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();

  if (!username || !password) {
    alertBox.innerHTML = renderAlert(false, 'Please enter both username and password.');
    return;
  }

  submitBtn.disabled = true;
  submitBtn.textContent = 'Signing in…';
  alertBox.innerHTML = '';

  const formData = new FormData(formElement);

  try {
    const res  = await fetch('login.php', { method: 'POST', body: formData });
    const data = await res.json();

    alertBox.innerHTML = renderAlert(data.success, data.message);

    if (data.success) {
      setTimeout(() => {
        openDashboard(data.user.username, data.user.role);
      }, 600);
    }
  } catch {
    alertBox.innerHTML = renderAlert(false, 'Something went wrong. Try again.');
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Login';
  }
}