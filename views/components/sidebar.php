<?php
declare(strict_types=1);
use App\Services\Auth;

$isAdmin  = Auth::isAdmin();
$user     = Auth::user();
$initial  = strtoupper(substr($user['full_name'] ?: $user['username'] ?: 'U', 0, 1));
$current  = $currentSection ?? 'overview';
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
            <p class="sidebar-nav__label">Records</p>
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

            <p class="sidebar-nav__label">Administration</p>
            <button type="button"
                    class="sidebar-nav__item <?= $current === 'users' ? 'is-active' : '' ?>"
                    data-section="users">
                <i class="bi bi-person-gear"></i><span>Users</span>
            </button>
        <?php else: ?>
            <p class="sidebar-nav__label">Operations</p>
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
        <?php endif; ?>
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