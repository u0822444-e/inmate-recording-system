<?php
declare(strict_types=1);
?>
<div id="authScreen" class="container-fluid p-0">
  <div class="row g-0 min-vh-100">

    <!-- ============================================================
         Brand panel (left)
         ============================================================ -->
    <aside
      class="col-lg-6 d-none d-lg-flex flex-column justify-content-center align-items-center text-white position-relative p-5"
      style="background: linear-gradient(160deg, rgba(27,58,107,0.86) 0%, rgba(13,31,60,0.96) 100%), url('assets/images/bg-login.jpg') center/cover no-repeat; min-height: 100vh;">

      <!-- Centered content -->
      <div class="text-center" style="max-width: 420px;">
        <img src="assets/images/bjmp-logo.png" alt="BJMP" class="mb-4 mx-auto d-block"
          style="width: 96px; height: 96px; object-fit: contain;">

        <p class="text-uppercase fw-semibold mb-2"
          style="font-size: 0.7rem; letter-spacing: 0.16em; color: rgba(255,255,255,0.75);">
          Bureau of Jail Management and Penology
        </p>

        <h1 class="fw-bold mb-3" style="font-size: 2rem; letter-spacing: -0.02em; color: white">
          Ipil District Jail
        </h1>

        <p class="text-uppercase fw-semibold mb-0"
          style="font-size: 0.78rem; letter-spacing: 0.15em; color: rgba(255,255,255,0.78);">
          Inmate Recording System
        </p>
      </div>

      <!-- Bottom footer text -->
      <p class="position-absolute bottom-0 start-50 translate-middle-x mb-4 text-uppercase fw-medium"
        style="font-size: 0.68rem; letter-spacing: 0.1em; color: rgba(255,255,255,0.55); white-space: nowrap;">
        Authorized personnel only
      </p>
    </aside>

    <!-- ============================================================
         Form panel (right)
         ============================================================ -->
    <main class="col-lg-6 d-flex align-items-center justify-content-center bg-body-tertiary p-4">
      <div class="w-100" style="max-width: 440px;">

        <div class="card border-0 shadow-sm rounded-3">
          <div class="card-body p-4 p-md-5">

            <!-- ============================================================
                 Step 1: Role selection
                 ============================================================ -->
            <section id="roleSection">
              <div class="text-center mb-4">
                <h2 class="fw-bold mb-1">Welcome back!</h2>
                <p class="text-secondary small mb-0">Select your account type to continue.</p>
              </div>

              <div class="d-flex justify-content-center gap-4">
                <button type="button" class="btn btn-link text-decoration-none p-0 border-0 role-btn"
                  onclick="selectRole('Administrator')">
                  <span class="d-flex flex-column align-items-center gap-2">
                    <span
                      class="d-flex align-items-center justify-content-center rounded-circle border bg-light role-circle">
                      <i class="bi bi-shield-lock fs-3"></i>
                    </span>
                    <span class="fw-bold small text-body">Administrator</span>
                  </span>
                </button>

                <button type="button" class="btn btn-link text-decoration-none p-0 border-0 role-btn"
                  onclick="selectRole('Staff / Officer')">
                  <span class="d-flex flex-column align-items-center gap-2">
                    <span
                      class="d-flex align-items-center justify-content-center rounded-circle border bg-light role-circle">
                      <i class="bi bi-person-badge fs-3"></i>
                    </span>
                    <span class="fw-bold small text-body">Staff / Officer</span>
                  </span>
                </button>
              </div>
            </section>

            <!-- ============================================================
                 Step 2: Login form
                 ============================================================ -->
            <section id="loginForm" hidden>
              <div class="border-bottom pb-3 mb-3">
                <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none p-0 mb-3"
                  onclick="resetRole()">
                  <i class="bi bi-arrow-left me-1"></i> Back
                </button>

                <h2 class="h5 fw-bold mb-0">
                  Sign in as <span class="text-primary" id="activeRoleLabel">Administrator</span>
                </h2>
              </div>

              <div id="alertBox" role="alert" aria-live="polite"></div>

              <form id="loginFormElement" onsubmit="submitLogin(event)" novalidate>
                <input type="hidden" id="hiddenRole" name="role">

                <div class="mb-3">
                  <label for="username" class="form-label small fw-semibold text-secondary">Username</label>
                  <input type="text" class="form-control" id="username" name="username" autocomplete="username"
                    required>
                </div>

                <div class="mb-4">
                  <label for="password" class="form-label small fw-semibold text-secondary">Password</label>

                  <div class="position-relative">
                    <input type="password" class="form-control pe-5" id="password" name="password"
                      autocomplete="current-password" required>

                    <button type="button"
                      class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary p-2 border-0"
                      id="togglePassword" onclick="togglePasswordVisibility()" tabindex="-1" aria-label="Show password">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="submitBtn">
                  Sign in
                </button>
              </form>
            </section>

          </div>
        </div>

        <p class="text-center text-secondary small mt-4 mb-0">
          &copy; 2026 Bureau of Jail Management and Penology
        </p>

      </div>
    </main>

  </div>
</div>