<div id="authScreen" class="auth-wrapper">
  <div class="auth-card-container">
    <main class="auth-card" role="main">

      <header class="auth__header" id="authHeader">
        <img src="assets/images/bjmp-logo.png"
             onerror="this.onerror=null; this.src='https://placehold.co/200x200/0f213d/ffffff?text=BJMP+Logo'"
             alt="BJMP Ipil District Jail logo"
             class="auth__logo">
        <h1 class="auth__title">Bureau of Jail Management and Penology</h1>
        <p class="auth__subtitle">Ipil District Jail</p>
      </header>

      <section id="roleSection" class="roles" aria-label="Select a role">
        <button type="button" class="role" onclick="selectRole('Administrator')">
          <span class="role__circle" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
          </span>
          <span class="role__label">Administrator</span>
        </button>

        <button type="button" class="role" onclick="selectRole('Staff / Officer')">
          <span class="role__circle" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </span>
          <span class="role__label">Staff / Officer</span>
        </button>
      </section>

      <section id="loginForm" class="form" hidden aria-label="Sign in form">
        <div id="alertBox" role="alert" aria-live="polite"></div>

        <form id="loginFormElement" onsubmit="submitLogin(event)" novalidate>
          <input type="hidden" id="hiddenRole" name="role">

          <div class="field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   autocomplete="username" placeholder="Enter username" required>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   autocomplete="current-password" placeholder="••••••••" required>
          </div>

          <button type="submit" class="btn-submit" id="submitBtn">
            Login
          </button>

          <button type="button" class="btn-back" onclick="resetRole()">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="19" y1="12" x2="5" y2="12"></line>
              <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back
          </button>
        </form>
      </section>

    </main>

    <footer class="auth__footer">
      <p>&copy; 2026 Bureau of Jail Management and Penology. All rights reserved.</p>
    </footer>
  </div>
</div>