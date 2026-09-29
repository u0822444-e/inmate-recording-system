<div id="dashboardScreen" class="portal" hidden>

  <!-- Top Navigation Header -->
  <header class="portal-topbar">
    <div class="portal-brand">
      <img src="assets/images/bjmp-logo.png"
           onerror="this.onerror=null; this.src='https://placehold.co/100x100/0f213d/ffffff?text=BJMP'"
           alt="BJMP Emblem">
      <div class="portal-brand__info">
        <h1>Ipil District Jail Portal</h1>
        <p id="portalSubhead">Management Information System</p>
      </div>
    </div>

    <div class="portal-user-bar">
      <div class="user-badge">
        <div class="user-avatar" id="userAvatar">A</div>
        <div class="user-details">
          <div class="user-name" id="userNameDisplay">Officer Admin</div>
          <div class="user-role-tag" id="userRoleDisplay">Administrator</div>
        </div>
      </div>

      <button type="button" class="btn-logout" onclick="logoutPortal()">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
          <polyline points="16 17 21 12 16 7"></polyline>
          <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg>
        Logout
      </button>
    </div>
  </header>

  <div class="portal-main">
    <aside class="portal-sidebar" id="portalSidebar"></aside>
    <main class="portal-content" id="portalContent"></main>
  </div>
</div>

<div id="modalContainer" hidden class="modal-backdrop">
  <div class="modal-content" id="modalBody"></div>
</div>