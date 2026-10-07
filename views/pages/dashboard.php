<?php
declare(strict_types=1);
use App\Services\Auth;

$isAdmin = Auth::isAdmin();
$user    = Auth::user();
$today   = date('M j, Y');

/* ----------------------------------------------------------
   Read current section from the query string so PHP renders
   the correct section on first paint (no flash on refresh).
   ---------------------------------------------------------- */
$allowedSections = ['overview', 'inmates', 'visitors', 'incidents', 'headcount', 'users'];
$requested       = $_GET['section'] ?? 'overview';
$currentSection  = in_array($requested, $allowedSections, true) ? $requested : 'overview';
?>
<section id="dashboardScreen"
         class="dashboard-shell"
         data-role="<?= $isAdmin ? 'admin' : 'staff' ?>"
         data-initial-section="<?= htmlspecialchars($currentSection, ENT_QUOTES) ?>">

    <?php require __DIR__ . '/../components/sidebar.php'; ?>

    <main class="dashboard-main">

        <?php require __DIR__ . '/../components/mobile-header.php'; ?>

        <!-- ============================================================
             OVERVIEW
             ============================================================ -->
        <section id="dashboard-overview"
                 class="dashboard-section <?= $currentSection === 'overview' ? 'is-visible' : '' ?>">
            <header class="section-header">
                <div>
                    <h1>Overview</h1>
                    <p>Welcome back, <strong><?= htmlspecialchars($user['full_name'] ?: $user['username'], ENT_QUOTES) ?></strong></p>
                </div>
                <span class="meta-text"><?= htmlspecialchars($today, ENT_QUOTES) ?></span>
            </header>

            <div class="kpi-grid">
                <article class="kpi"><span class="kpi__label">Total PDL</span><strong class="kpi__value">-</strong></article>
                <article class="kpi"><span class="kpi__label">Visitors</span><strong class="kpi__value">-</strong></article>
                <article class="kpi"><span class="kpi__label">Incidents</span><strong class="kpi__value">-</strong></article>
                <?php if ($isAdmin): ?>
                <article class="kpi"><span class="kpi__label">Users</span><strong class="kpi__value">-</strong></article>
                <?php endif; ?>
            </div>
        </section>

        <!-- ============================================================
             INMATES
             ============================================================ -->
        <section id="dashboard-inmates"
                 class="dashboard-section <?= $currentSection === 'inmates' ? 'is-visible' : '' ?>">
            <header class="section-header">
                <div><h1>Inmate Records</h1><p>Search and review PDL records.</p></div>
            </header>
        </section>

        <!-- ============================================================
             VISITORS
             ============================================================ -->
        <section id="dashboard-visitors"
                 class="dashboard-section <?= $currentSection === 'visitors' ? 'is-visible' : '' ?>">
            <header class="section-header">
                <div><h1>Visitors</h1><p>Visitor log and monitoring.</p></div>
            </header>
        </section>

        <!-- ============================================================
             INCIDENTS
             ============================================================ -->
        <section id="dashboard-incidents"
                 class="dashboard-section <?= $currentSection === 'incidents' ? 'is-visible' : '' ?>">
            <header class="section-header">
                <div><h1>Incidents</h1><p>Facility incident reports.</p></div>
            </header>
        </section>

        <!-- ============================================================
             HEADCOUNT
             ============================================================ -->
        <section id="dashboard-headcount"
                 class="dashboard-section <?= $currentSection === 'headcount' ? 'is-visible' : '' ?>">
            <header class="section-header">
                <div><h1>Headcount</h1><p>Daily population verification.</p></div>
            </header>
        </section>

        <?php if ($isAdmin): ?>
            <?php require __DIR__ . '/users-section.php'; ?>
        <?php endif; ?>

    </main>
</section>

<?php if ($isAdmin): ?>
    <?php require __DIR__ . '/../components/modal-user.php'; ?>
    <?php require __DIR__ . '/../components/modal-confirm.php'; ?>
<?php endif; ?>
<?php require __DIR__ . '/../components/modal-logout.php'; ?>