<?php
declare(strict_types=1);
?>
<div id="authScreen" class="auth-wrapper">

  <aside class="auth-brand-panel" aria-label="Ipil District Jail">
    <div class="auth-brand">
      <img src="assets/images/bjmp-logo.png" alt="BJMP" class="auth__logo">
      <div class="auth-brand__copy">
        <p class="auth-brand__eyebrow">Bureau of Jail Management and Penology</p>
        <h1 class="auth__title">Ipil District Jail</h1>
        <p class="auth__subtitle">Inmate Recording System</p>
      </div>
    </div>
    <div class="auth-brand__meta"><span>Authorized personnel only</span></div>
  </aside>

  <main class="auth-form-panel">
    <div class="auth-panel-inner">
      <div class="auth-card">

        <section id="roleSection" class="roles-wrap">
          <header class="auth-welcome">
            <h2 class="auth-welcome__title">Welcome back!</h2>
            <p class="auth-welcome__sub">Select your account type to continue.</p>
          </header>

          <div class="roles" role="group">
            <button type="button" class="role" onclick="selectRole('Administrator')">
              <span class="role__circle"><i class="bi bi-shield-lock"></i></span>
              <span class="role__label">Administrator</span>
            </button>
            <button type="button" class="role" onclick="selectRole('Staff / Officer')">
              <span class="role__circle"><i class="bi bi-person-badge"></i></span>
              <span class="role__label">Staff / Officer</span>
            </button>
          </div>
        </section>

        <section id="loginForm" class="auth-login" hidden>
          <header class="auth-signin-header">
            <button type="button" class="auth-back-link" onclick="resetRole()">
              <i class="bi bi-arrow-left"></i><span>Back</span>
            </button>
            <h2 class="auth-signin-header__title">
              Sign in as <strong id="activeRoleLabel">Administrator</strong>
            </h2>
          </header>

          <div id="alertBox" class="auth-alert" role="alert" aria-live="polite"></div>

          <form id="loginFormElement" class="auth-form" onsubmit="submitLogin(event)" novalidate>
            <input type="hidden" id="hiddenRole" name="role">

            <div class="field">
              <label for="username">Username</label>
              <input type="text" id="username" name="username" autocomplete="username" required>
            </div>

            <div class="field">
              <label for="password">Password</label>
              <div class="input-password">
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                <button type="button" class="input-password__toggle" id="togglePassword" onclick="togglePasswordVisibility()">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">Sign in</button>
          </form>
        </section>

      </div>

      <footer class="auth__footer">
        <p>&copy; 2026 Bureau of Jail Management and Penology</p>
      </footer>
    </div>
  </main>
</div>