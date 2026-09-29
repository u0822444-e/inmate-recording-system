let currentRole = null;
let currentHeadcount = 248;

document.addEventListener('DOMContentLoaded', () => {
  startLiveClock();
});

function startLiveClock() {
  const clockEl = document.getElementById('liveClock');
  const update = () => {
    const now = new Date();
    if (clockEl) {
      clockEl.textContent = now.toLocaleTimeString('en-US', { hour12: false });
    }
  };
  update();
  setInterval(update, 1000);
}

/* 1. SELECT ROLE */
function selectRole(roleName) {
  currentRole = roleName;
  document.getElementById('selectedRoleInput').value = roleName;

  // Hide Role Selection
  document.getElementById('roleSection').hidden = true;

  // Keep authHeader VISIBLE during login form display
  document.getElementById('authHeader').hidden = false;

  hideAlert();
  document.getElementById('username').value = '';
  document.getElementById('password').value = '';

  // Show Login Form
  document.getElementById('loginForm').hidden = false;
}

/* 2. RESET ROLE (BACK BUTTON BELOW LOGIN) */
function resetRole() {
  currentRole = null;

  document.getElementById('loginForm').hidden = true;
  document.getElementById('authHeader').hidden = false;
  document.getElementById('roleSection').hidden = false;

  hideAlert();
}

/* 3. LOGIN SUBMISSION */
function submitLogin(e) {
  e.preventDefault();

  const userVal = document.getElementById('username').value.trim();
  const passVal = document.getElementById('password').value.trim();

  if (!userVal || !passVal) {
    showAlert('Please enter your credentials.', 'danger');
    return;
  }

  showAlert('Authenticating user... Please wait.', 'success');

  setTimeout(() => {
    document.getElementById('authContainer').hidden = true;
    const dashSection = document.getElementById('dashboardSection');
    dashSection.hidden = false;

    document.getElementById('activeRoleDisplay').textContent = currentRole;
    switchTab('overview');
  }, 600);
}

/* 4. LOGOUT */
function logout() {
  document.getElementById('dashboardSection').hidden = true;
  document.getElementById('authContainer').hidden = false;
  resetRole();
}

/* 5. TAB NAVIGATION */
function switchTab(tabId) {
  const menuButtons = document.querySelectorAll('.sidebar-menu .menu-item');
  menuButtons.forEach(btn => btn.classList.remove('active'));

  const clickedBtn = Array.from(menuButtons).find(btn => 
    btn.getAttribute('onclick').includes(tabId)
  );
  if (clickedBtn) clickedBtn.classList.add('active');

  const panels = document.querySelectorAll('.tab-panel');
  panels.forEach(p => p.classList.remove('active'));

  const targetPanel = document.getElementById(`tab-${tabId}`);
  if (targetPanel) targetPanel.classList.add('active');
}

/* 6. HEADCOUNT COUNTER TOOL */
function adjustCount(delta) {
  currentHeadcount += delta;
  if (currentHeadcount < 0) currentHeadcount = 0;
  
  const countDisplay = document.getElementById('liveHeadcount');
  if (countDisplay) countDisplay.textContent = currentHeadcount;
}

function verifyHeadcount() {
  const statCount = document.getElementById('statPdlCount');
  if (statCount) statCount.textContent = currentHeadcount;

  const activityList = document.getElementById('recentActivityList');
  if (activityList) {
    const timeStr = new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' });
    const li = document.createElement('li');
    li.innerHTML = `<span class="activity-time">${timeStr}</span> Headcount verified & synced (${currentHeadcount} PDLs) by ${currentRole}`;
    activityList.prepend(li);
  }

  alert(`Headcount verified: ${currentHeadcount} PDLs synced successfully.`);
}

/* 7. SEARCH & FILTERS */
function filterPdlTable() {
  const searchVal = document.getElementById('pdlSearchInput').value.toLowerCase();
  const blockVal = document.getElementById('pdlCellFilter').value;
  const rows = document.querySelectorAll('#pdlTableBody tr');

  rows.forEach(row => {
    const text = row.innerText.toLowerCase();
    const matchesSearch = text.includes(searchVal);
    const matchesBlock = blockVal === '' || text.includes(blockVal.toLowerCase());

    row.style.display = (matchesSearch && matchesBlock) ? '' : 'none';
  });
}

function viewPdl(pdlCode) {
  alert(`Opening records for ${pdlCode}...`);
}

/* 8. MODAL CONTROLS */
function openModal(modalId) {
  const m = document.getElementById(modalId);
  if (m) m.hidden = false;
}

function closeModal(modalId) {
  const m = document.getElementById(modalId);
  if (m) m.hidden = true;
}

function submitNewPdl(e) {
  e.preventDefault();
  const name = document.getElementById('newPdlName').value;
  const block = document.getElementById('newPdlBlock').value;
  
  const code = `PDL-2026-${Math.floor(1000 + Math.random() * 9000)}`;
  const tbody = document.getElementById('pdlTableBody');

  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><code>${code}</code></td>
    <td>${name}</td>
    <td>${block}</td>
    <td>${new Date().toISOString().split('T')[0]}</td>
    <td><span class="badge badge--success">Detained</span></td>
    <td><button class="btn-sm" onclick="viewPdl('${code}')">Details</button></td>
  `;
  tbody.prepend(tr);

  adjustCount(1);
  closeModal('addPdlModal');
  alert(`PDL ${name} registered under ${code}.`);
}

function submitNewVisitor(e) {
  e.preventDefault();
  const name = document.getElementById('newVisName').value;
  const pdl = document.getElementById('newVisPdl').value;
  const rel = document.getElementById('newVisRelation').value;

  const passCode = `VIS-${Math.floor(1000 + Math.random() * 9000)}`;
  const timeStr = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

  const tbody = document.getElementById('visitorTableBody');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><code>${passCode}</code></td>
    <td>${name}</td>
    <td>${pdl}</td>
    <td>${rel}</td>
    <td>${timeStr}</td>
    <td>-</td>
    <td><span class="badge badge--success">Inside Facility</span></td>
  `;
  tbody.prepend(tr);

  closeModal('addVisitorModal');
  alert(`Visitor pass ${passCode} issued for ${name}.`);
}

function showAlert(msg, type) {
  const box = document.getElementById('alertBox');
  if (box) {
    box.textContent = msg;
    box.className = `alert-box alert-box--${type}`;
    box.hidden = false;
  }
}

function hideAlert() {
  const box = document.getElementById('alertBox');
  if (box) box.hidden = true;
}