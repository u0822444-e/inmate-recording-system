<?php
declare(strict_types=1);
use App\Services\Auth;

$isAdmin  = Auth::isAdmin();
$user     = Auth::user();
$initial  = strtoupper(substr($user['full_name'] ?: $user['username'] ?: 'U', 0, 1));
$current  = $currentSection ?? 'overview';

// Auto-open the dropdown if the current section is one of its children.
$recordsSections = ['inmates', 'visitors', 'incidents', 'headcount'];
$recordsOpen = in_array($current, $recordsSections, true);
?>
<aside id="dashboardSidebar" class="dashboard-sidebar" aria-label="Primary navigation">

    <div class="sidebar-brand">
        <img src="assets/images/bjmp-logo.png" alt="" class="sidebar-brand__logo">
        <div class="sidebar-brand__copy">
            <strong>IPIL DISTRICT JAIL</strong>
            <span>Inmate Recording System</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <p class="sidebar-nav__label">Main</p>
        <button type="button"
                class="sidebar-nav__item <?= $current === 'overview' ? 'is-active' : '' ?>"
                data-section="overview">
            <i class="bi bi-grid-1x2"></i><span>Overview</span>
        </button>

        <?php if ($isAdmin): ?>

            <!-- ============================================================
                 RECORDS (collapsible group)
                 ============================================================ -->
            <button type="button"
                    class="sidebar-nav__group <?= $recordsOpen ? 'is-open' : '' ?>"
                    data-group="records"
                    aria-expanded="<?= $recordsOpen ? 'true' : 'false' ?>">
                <i class="bi bi-folder2-open"></i>
                <span>Records</span>
                <i class="bi bi-chevron-down sidebar-nav__chevron"></i>
            </button>

            <div class="sidebar-nav__children <?= $recordsOpen ? 'is-open' : '' ?>" data-group-children="records">
                <button type="button"
                        class="sidebar-nav__item <?= $current === 'inmates' ? 'is-active' : '' ?>"
                        data-section="inmates">
                    <i class="bi bi-person-badge"></i><span>Inmates</span>
                </button>
                <button type="button"
                        class="sidebar-nav__item <?= $current === 'visitors' ? 'is-active' : '' ?>"
                        data-section="visitors">
                    <i class="bi bi-people"></i><span>Visitors</span>
                </button>
                <button type="button"
                        class="sidebar-nav__item <?= $current === 'incidents' ? 'is-active' : '' ?>"
                        data-section="incidents">
                    <i class="bi bi-exclamation-diamond"></i><span>Incidents</span>
                </button>
                <button type="button"
                        class="sidebar-nav__item <?= $current === 'headcount' ? 'is-active' : '' ?>"
                        data-section="headcount">
                    <i class="bi bi-clipboard2-check"></i><span>Headcount</span>
                </button>
            </div>

            <p class="sidebar-nav__label">Reports</p>
            <button type="button"
                    class="sidebar-nav__item <?= $current === 'reports' ? 'is-active' : '' ?>"
                    data-section="reports">
                <i class="bi bi-file-earmark-bar-graph"></i><span>Reports</span>
            </button>

            <p class="sidebar-nav__label">Administration</p>
            <button type="button"
                    class="sidebar-nav__item <?= $current === 'users' ? 'is-active' : '' ?>"
                    data-section="users">
                <i class="bi bi-person-gear"></i><span>Users</span>
            </button>
            <button type="button"
                    class="sidebar-nav__item <?= $current === 'jail-units' ? 'is-active' : '' ?>"
                    data-section="jail-units">
                <i class="bi bi-building"></i><span>Jail Units</span>
            </button>

        <?php else: ?>

            <!-- ============================================================
                 OPERATIONS (collapsible group for staff)
                 ============================================================ -->
            <button type="button"
                    class="sidebar-nav__group <?= $recordsOpen ? 'is-open' : '' ?>"
                    data-group="operations"
                    aria-expanded="<?= $recordsOpen ? 'true' : 'false' ?>">
                <i class="bi bi-folder2-open"></i>
                <span>Operations</span>
                <i class="bi bi-chevron-down sidebar-nav__chevron"></i>
            </button>

            <div class="sidebar-nav__children <?= $recordsOpen ? 'is-open' : '' ?>" data-group-children="operations">
                <button type="button"
                        class="sidebar-nav__item <?= $current === 'recording' ? 'is-active' : '' ?>"
                        data-section="recording">
                    <i class="bi bi-pencil-square"></i><span>Record Inmate</span>
                </button>
                <button type="button"
                        class="sidebar-nav__item <?= $current === 'inmates' ? 'is-active' : '' ?>"
                        data-section="inmates">
                    <i class="bi bi-person-badge"></i><span>Inmate Records</span>
                </button>
                <button type="button"
                        class="sidebar-nav__item <?= $current === 'visitors' ? 'is-active' : '' ?>"
                        data-section="visitors">
                    <i class="bi bi-people"></i><span>Visitors</span>
                </button>
                <button type="button"
                        class="sidebar-nav__item <?= $current === 'incidents' ? 'is-active' : '' ?>"
                        data-section="incidents">
                    <i class="bi bi-exclamation-diamond"></i><span>Incidents</span>
                </button>
                <button type="button"
                        class="sidebar-nav__item <?= $current === 'headcount' ? 'is-active' : '' ?>"
                        data-section="headcount">
                    <i class="bi bi-clipboard2-check"></i><span>Headcount</span>
                </button>
            </div>

        <?php endif; ?>

        <!-- ============================================================
             ACCOUNT (shared)
             ============================================================ -->
        <p class="sidebar-nav__label">Account</p>
        <button type="button"
                class="sidebar-nav__item <?= $current === 'profile' ? 'is-active' : '' ?>"
                data-section="profile">
            <i class="bi bi-person-circle"></i><span>My Profile</span>
        </button>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user__avatar"><?= htmlspecialchars($initial, ENT_QUOTES) ?></div>
            <div class="sidebar-user__info">
                <strong><?= htmlspecialchars($user['full_name'] ?: $user['username'], ENT_QUOTES) ?></strong>
                <span><?= htmlspecialchars($user['role'], ENT_QUOTES) ?></span>
            </div>
        </div>
        <button type="button" class="sidebar-logout" id="sidebarLogoutBtn">
            <i class="bi bi-box-arrow-right"></i><span>Sign out</span>
        </button>
    </div>
</aside>