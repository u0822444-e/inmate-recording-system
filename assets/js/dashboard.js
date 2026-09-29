/* ============================================================
   Dashboard — portal state, views, modals
   ============================================================ */

const dashboardScreen = document.getElementById('dashboardScreen');
const portalSidebar   = document.getElementById('portalSidebar');
const portalContent   = document.getElementById('portalContent');
const userNameDisplay = document.getElementById('userNameDisplay');
const userRoleDisplay = document.getElementById('userRoleDisplay');
const userAvatar      = document.getElementById('userAvatar');
const portalSubhead   = document.getElementById('portalSubhead');
const modalContainer  = document.getElementById('modalContainer');
const modalBody       = document.getElementById('modalBody');

let activeTab = 'overview';

// ------------------------------------------------------------
// Mock data (replace with PHP/MySQL queries when wiring backend)
// ------------------------------------------------------------
const mockData = {
  pdlCount: 148,
  cellCapacity: 200,
  guardsOnDuty: 12,
  todayVisitors: 24,
  pdls: [
    { id: 'PDL-2026-001', name: 'Juan Dela Cruz', age: 34, cell: 'Cell Block A-2', crime: 'Illegal Possession', status: 'In Custody' },
    { id: 'PDL-2026-002', name: 'Mario Santos',   age: 29, cell: 'Cell Block B-1', crime: 'Theft',              status: 'In Custody' },
    { id: 'PDL-2026-003', name: 'Roberto Reyes',  age: 41, cell: 'Cell Block A-1', crime: 'Swindling / Fraud', status: 'Court Hearing' },
    { id: 'PDL-2026-004', name: 'Angelo Torres',  age: 27, cell: 'Cell Block C-3', crime: 'Physical Injuries', status: 'In Custody' }
  ],
  visitors: [
    { id: 'VIS-101', name: 'Maria Dela Cruz',  pdlTarget: 'Juan Dela Cruz', relation: 'Spouse',         timeIn: '09:15 AM', status: 'Approved' },
    { id: 'VIS-102', name: 'Carlos Santos',    pdlTarget: 'Mario Santos',   relation: 'Brother',        timeIn: '10:30 AM', status: 'Active Visit' },
    { id: 'VIS-103', name: 'Atty. Elena Gomez', pdlTarget: 'Roberto Reyes', relation: 'Legal Counsel', timeIn: '01:00 PM', status: 'Approved' }
  ],
  incidents: [
    { time: '10:45 AM Today', type: 'Routine Inspection',   details: 'Completed contraband sweep in Cell Block B. No infractions found.', loggedBy: 'SGT. Ramos' },
    { time: '08:00 AM Today', type: 'Headcount Verification', details: 'Morning headcount confirmed 148/148 PDLs present.',               loggedBy: 'CO2 Fernandez' }
  ]
};

// ------------------------------------------------------------
// Dashboard lifecycle
// ------------------------------------------------------------
function openDashboard(username, role) {
  authScreen.hidden = true;
  dashboardScreen.hidden = false;

  if (role === 'Administrator') {
    userNameDisplay.textContent = username ? `Admin (${username})` : 'Warden Admin';
    userRoleDisplay.textContent = 'Administrator Level 1';
    userAvatar.textContent = 'A';
    portalSubhead.textContent = 'Administrative Command Center';
  } else {
    userNameDisplay.textContent = username ? `Duty Off. ${username}` : 'CO3 Dela Cruz';
    userRoleDisplay.textContent = 'Staff / Duty Officer';
    userAvatar.textContent = 'S';
    portalSubhead.textContent = 'Duty Operations & Custody';
  }

  currentRole = role;
  renderSidebar(role);
  switchTab('overview');
}

function logoutPortal() {
  // Clear session server-side, then reload
  window.location.href = 'logout.php';
}

// ------------------------------------------------------------
// Sidebar
// ------------------------------------------------------------
function renderSidebar(role) {
  let navItems = [];

  if (role === 'Administrator') {
    navItems = [
      { id: 'overview', label: 'System Overview',  icon: '<path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>' },
      { id: 'pdls',     label: 'PDL Records',      icon: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>' },
      { id: 'staff',    label: 'Staff & Guards',   icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>' },
      { id: 'visitors', label: 'Visitor Permits',  icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>' },
      { id: 'audit',    label: 'Audit Logs',       icon: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>' }
    ];
  } else {
    navItems = [
      { id: 'overview',  label: 'Duty Overview',      icon: '<path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>' },
      { id: 'headcount', label: 'Headcount Tracker',  icon: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>' },
      { id: 'pdls',      label: 'PDL Directory',      icon: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>' },
      { id: 'visitors',  label: 'Visitor Entry Log',  icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>' },
      { id: 'incidents', label: 'Incident Log',       icon: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>' }
    ];
  }

  portalSidebar.innerHTML = navItems.map(item => `
    <button type="button" class="nav-item ${activeTab === item.id ? 'active' : ''}" onclick="switchTab('${item.id}')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">${item.icon}</svg>
      <span>${item.label}</span>
    </button>
  `).join('');
}

function switchTab(tabId) {
  activeTab = tabId;
  renderSidebar(currentRole);

  if (currentRole === 'Administrator') {
    renderAdminView(tabId);
  } else {
    renderStaffView(tabId);
  }
}

// ------------------------------------------------------------
// Admin views
// ------------------------------------------------------------
function renderAdminView(tab) {
  if (tab === 'overview') {
    portalContent.innerHTML = `
      <div class="page-title-bar">
        <div class="page-title">
          <h2>Administrative Dashboard</h2>
          <p>Overall facility statistics and jail operations status</p>
        </div>
        <button class="btn-primary" onclick="openAddPdlModal()">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add New PDL
        </button>
      </div>

      <div class="stats-grid">
        ${statCard('blue',  'Total PDL Population', mockData.pdlCount,
          '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>')}
        ${statCard('gold',  `Capacity Utilization (${mockData.pdlCount}/${mockData.cellCapacity})`,
          Math.round((mockData.pdlCount / mockData.cellCapacity) * 100) + '%',
          '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/>')}
        ${statCard('green', 'Active Guard Personnel', mockData.guardsOnDuty,
          '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>')}
        ${statCard('red',   'Visitors Scheduled Today', mockData.todayVisitors,
          '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>')}
      </div>

      <div class="content-card">
        <div class="card-header">
          <div class="card-title">Recent Inmate / PDL Admissions</div>
          <button class="btn-action" onclick="switchTab('pdls')">View All PDL Records</button>
        </div>
        ${renderPdlTableHTML()}
      </div>
    `;
  } else if (tab === 'pdls') {
    portalContent.innerHTML = `
      <div class="page-title-bar">
        <div class="page-title">
          <h2>PDL Records Management</h2>
          <p>Database of Persons Deprived of Liberty in Ipil District Jail</p>
        </div>
        <button class="btn-primary" onclick="openAddPdlModal()">+ Add PDL Record</button>
      </div>
      <div class="content-card">
        <div class="search-bar">
          <input type="text" class="search-input" id="pdlSearchInput"
                 placeholder="Search PDL by name, ID or cell block..."
                 onkeyup="filterPdlTable()">
        </div>
        ${renderPdlTableHTML()}
      </div>
    `;
  } else if (tab === 'staff') {
    portalContent.innerHTML = `
      <div class="page-title-bar">
        <div class="page-title">
          <h2>Staff & Guard Personnel</h2>
          <p>Roster of jail officers and shift assignments</p>
        </div>
      </div>
      <div class="content-card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Officer Name</th><th>Rank</th><th>Shift Post</th><th>Duty Hours</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr><td>CO3 Dela Cruz, R.</td><td>Senior Officer</td><td>Main Gate Security</td><td>06:00 - 14:00</td><td><span class="badge badge--success">On Duty</span></td></tr>
            <tr><td>CO2 Fernandez, A.</td><td>Guard Officer</td><td>Cell Block A Patrol</td><td>06:00 - 14:00</td><td><span class="badge badge--success">On Duty</span></td></tr>
            <tr><td>SGT Ramos, M.</td><td>Supervisor</td><td>Armory & Operations</td><td>14:00 - 22:00</td><td><span class="badge badge--warning">Standby</span></td></tr>
          </tbody>
        </table>
      </div>
    `;
  } else if (tab === 'visitors') {
    portalContent.innerHTML = `
      <div class="page-title-bar">
        <div class="page-title">
          <h2>Visitor Permits</h2>
          <p>All visitor permits issued at Ipil District Jail</p>
        </div>
      </div>
      <div class="content-card">${renderVisitorsTableHTML()}</div>
    `;
  } else {
    portalContent.innerHTML = `
      <div class="page-title-bar">
        <div class="page-title">
          <h2>System Audit Logs</h2>
          <p>Security trail and system activities</p>
        </div>
      </div>
      <div class="content-card">${renderIncidentsTableHTML()}</div>
    `;
  }
}

// ------------------------------------------------------------
// Staff views
// ------------------------------------------------------------
function renderStaffView(tab) {
  if (tab === 'overview' || tab === 'headcount') {
    portalContent.innerHTML = `
      <div class="page-title-bar">
        <div class="page-title">
          <h2>Duty Officer Dashboard</h2>
          <p>Current Shift: <strong>06:00 - 14:00 (Day Shift)</strong> | Post: <strong>Block Inspection</strong></p>
        </div>
        <button class="btn-primary" onclick="verifyHeadcount()">Verify Morning Headcount</button>
      </div>

      <div class="stats-grid">
        ${statCard('blue', 'PDL Accounted For', '148 / 148',
          '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
          'headcountVal')}
        ${statCard('gold', 'Visitors Processed Today', mockData.visitors.length,
          '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>')}
      </div>

      <div class="content-card">
        <div class="card-header">
          <div class="card-title">Assigned Cell Blocks (Custody Patrol)</div>
        </div>
        ${renderPdlTableHTML()}
      </div>
    `;
  } else if (tab === 'pdls') {
    portalContent.innerHTML = `
      <div class="page-title-bar">
        <div class="page-title">
          <h2>PDL Directory</h2>
          <p>Inmate directory and cell assignments</p>
        </div>
      </div>
      <div class="content-card">
        <div class="search-bar">
          <input type="text" class="search-input" id="pdlSearchInput"
                 placeholder="Search by name, ID or cell..." onkeyup="filterPdlTable()">
        </div>
        ${renderPdlTableHTML()}
      </div>
    `;
  } else if (tab === 'visitors') {
    portalContent.innerHTML = `
      <div class="page-title-bar">
        <div class="page-title">
          <h2>Visitor Entry Log</h2>
          <p>Manage and log daily visitor entry</p>
        </div>
        <button class="btn-primary" onclick="openLogVisitorModal()">+ Register Visitor</button>
      </div>
      <div class="content-card">${renderVisitorsTableHTML()}</div>
    `;
  } else {
    portalContent.innerHTML = `
      <div class="page-title-bar">
        <div class="page-title">
          <h2>Security Incident Log</h2>
          <p>Report and log shift events</p>
        </div>
        <button class="btn-primary" onclick="openAddIncidentModal()">+ File Event Log</button>
      </div>
      <div class="content-card">${renderIncidentsTableHTML()}</div>
    `;
  }
}

// ------------------------------------------------------------
// Reusable card + table renderers
// ------------------------------------------------------------
function statCard(variant, label, value, iconPath, valueId = '') {
  return `
    <div class="stat-card">
      <div class="stat-icon stat-icon--${variant}">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
          ${iconPath}
        </svg>
      </div>
      <div class="stat-info">
        <div class="stat-value"${valueId ? ` id="${valueId}"` : ''}>${value}</div>
        <div class="stat-label">${label}</div>
      </div>
    </div>`;
}

function renderPdlTableHTML() {
  return `
    <div class="table-wrapper">
      <table class="data-table" id="pdlDataTable">
        <thead>
          <tr><th>PDL ID</th><th>Full Name</th><th>Age</th><th>Cell Assignment</th><th>Offense / Case</th><th>Status</th></tr>
        </thead>
        <tbody>
          ${mockData.pdls.map(pdl => `
            <tr>
              <td><strong>${pdl.id}</strong></td>
              <td>${pdl.name}</td>
              <td>${pdl.age}</td>
              <td>${pdl.cell}</td>
              <td>${pdl.crime}</td>
              <td><span class="badge ${pdl.status === 'In Custody' ? 'badge--success' : 'badge--warning'}">${pdl.status}</span></td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>`;
}

function renderVisitorsTableHTML() {
  return `
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr><th>Permit ID</th><th>Visitor Name</th><th>PDL Visited</th><th>Relationship</th><th>Time Checked In</th><th>Status</th></tr>
        </thead>
        <tbody>
          ${mockData.visitors.map(v => `
            <tr>
              <td><strong>${v.id}</strong></td>
              <td>${v.name}</td>
              <td>${v.pdlTarget}</td>
              <td>${v.relation}</td>
              <td>${v.timeIn}</td>
              <td><span class="badge badge--info">${v.status}</span></td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>`;
}

function renderIncidentsTableHTML() {
  return `
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr><th>Timestamp</th><th>Category</th><th>Activity / Report Details</th><th>Logged By</th></tr>
        </thead>
        <tbody>
          ${mockData.incidents.map(inc => `
            <tr>
              <td><strong>${inc.time}</strong></td>
              <td><span class="badge badge--info">${inc.type}</span></td>
              <td>${inc.details}</td>
              <td>${inc.loggedBy}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>`;
}

// ------------------------------------------------------------
// Modals
// ------------------------------------------------------------
function closeModal() { modalContainer.hidden = true; }

function openAddPdlModal() {
  modalBody.innerHTML = `
    <div class="modal-header">
      <h3>Add New PDL Record</h3>
      <button class="btn-close-modal" onclick="closeModal()">✕</button>
    </div>
    <form onsubmit="saveNewPdl(event)">
      <div class="field"><label>Full Name</label>
        <input type="text" id="pdlNameInput" required placeholder="e.g. Pedro Penduko"></div>
      <div class="field"><label>Age</label>
        <input type="number" id="pdlAgeInput" required placeholder="30"></div>
      <div class="field"><label>Cell Block Assignment</label>
        <select id="pdlCellInput">
          <option>Cell Block A-1</option><option>Cell Block A-2</option>
          <option>Cell Block B-1</option><option>Cell Block C-1</option>
        </select></div>
      <div class="field"><label>Offense / Case</label>
        <input type="text" id="pdlCrimeInput" required placeholder="Alleged Charge"></div>
      <button type="submit" class="btn-submit">Save Inmate Record</button>
    </form>`;
  modalContainer.hidden = false;
}

function saveNewPdl(e) {
  e.preventDefault();
  mockData.pdls.unshift({
    id: `PDL-2026-00${mockData.pdls.length + 1}`,
    name: document.getElementById('pdlNameInput').value,
    age: document.getElementById('pdlAgeInput').value,
    cell: document.getElementById('pdlCellInput').value,
    crime: document.getElementById('pdlCrimeInput').value,
    status: 'In Custody'
  });
  mockData.pdlCount++;
  closeModal();
  switchTab('pdls');
}

function openLogVisitorModal() {
  modalBody.innerHTML = `
    <div class="modal-header">
      <h3>Register Visitor Permit</h3>
      <button class="btn-close-modal" onclick="closeModal()">✕</button>
    </div>
    <form onsubmit="saveNewVisitor(event)">
      <div class="field"><label>Visitor Full Name</label>
        <input type="text" id="visName" required placeholder="e.g. Juana Dela Cruz"></div>
      <div class="field"><label>Target PDL to Visit</label>
        <input type="text" id="visTarget" required placeholder="e.g. Juan Dela Cruz"></div>
      <div class="field"><label>Relationship</label>
        <input type="text" id="visRel" required placeholder="Spouse / Parent / Attorney"></div>
      <button type="submit" class="btn-submit">Issue Visit Pass</button>
    </form>`;
  modalContainer.hidden = false;
}

function saveNewVisitor(e) {
  e.preventDefault();
  mockData.visitors.unshift({
    id: `VIS-${100 + mockData.visitors.length + 1}`,
    name: document.getElementById('visName').value,
    pdlTarget: document.getElementById('visTarget').value,
    relation: document.getElementById('visRel').value,
    timeIn: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    status: 'Active Visit'
  });
  closeModal();
  switchTab('visitors');
}

function openAddIncidentModal() {
  modalBody.innerHTML = `
    <div class="modal-header">
      <h3>Log Security Event</h3>
      <button class="btn-close-modal" onclick="closeModal()">✕</button>
    </div>
    <form onsubmit="saveIncident(event)">
      <div class="field"><label>Log Category</label>
        <input type="text" id="incCategory" required placeholder="Headcount / Inspection / Event"></div>
      <div class="field"><label>Details & Remarks</label>
        <textarea id="incDetails" rows="3" required placeholder="Enter entry log details..."></textarea></div>
      <button type="submit" class="btn-submit">Submit Entry</button>
    </form>`;
  modalContainer.hidden = false;
}

function saveIncident(e) {
  e.preventDefault();
  mockData.incidents.unshift({
    time: 'Just Now',
    type: document.getElementById('incCategory').value,
    details: document.getElementById('incDetails').value,
    loggedBy: userNameDisplay.textContent
  });
  closeModal();
  switchTab('incidents');
}

// ------------------------------------------------------------
// Utilities
// ------------------------------------------------------------
function filterPdlTable() {
  const query = document.getElementById('pdlSearchInput').value.toLowerCase();
  document.querySelectorAll('#pdlDataTable tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
  });
}

function verifyHeadcount() {
  const hc = document.getElementById('headcountVal');
  if (hc) {
    hc.textContent = `${mockData.pdlCount} / ${mockData.pdlCount} (Verified)`;
    hc.style.color = '#6ee7b7';
  }
}