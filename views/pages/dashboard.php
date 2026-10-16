<?php
declare(strict_types=1);
use App\Services\Auth;

$isAdmin = Auth::isAdmin();
$user = Auth::user();
$today = date('M j, Y');

$allowedSections = ['overview', 'inmates', 'visitors', 'incidents', 'headcount', 'users', 'jail-units', 'reports', 'profile'];
$requested = $_GET['section'] ?? 'overview';
$currentSection = in_array($requested, $allowedSections, true) ? $requested : 'overview';
?>
<section id="dashboardScreen" class="dashboard-shell" data-role="<?= $isAdmin ? 'admin' : 'staff' ?>"
    data-initial-section="<?= htmlspecialchars($currentSection, ENT_QUOTES) ?>"
    data-user-id="<?= (int) ($user['id'] ?? 0) ?>">

    <script>
        document.body.dataset.userId = <?= (int) ($user['id'] ?? 0) ?>;
    </script>

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
                    <h1><?= $isAdmin ? 'Overview' : 'My Dashboard' ?></h1>
                    <p>Welcome back,
                        <strong><?= htmlspecialchars($user['full_name'] ?: $user['username'], ENT_QUOTES) ?></strong>
                    </p>
                </div>
                <div class="section-header__tools">
                    <span class="meta-text"><?= htmlspecialchars($today, ENT_QUOTES) ?></span>
                </div>
            </header>

            <!-- Alert banner -->
            <div class="alert-banner" role="status">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <div class="alert-banner__content">
                    <?php if ($isAdmin): ?>
                        <strong>Daily headcount due by 18:00</strong>
                        <span>2 shifts are still pending for today's population verification.</span>
                    <?php else: ?>
                        <strong>Shift Reminder</strong>
                        <span>Submit your headcount before the end of your shift.</span>
                    <?php endif; ?>
                </div>
                <button type="button" class="alert-banner__close" aria-label="Dismiss">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <!-- KPI STRIP -->
            <div class="kpi-grid">
                <?php if ($isAdmin): ?>
                    <article class="kpi">
                        <div class="kpi__header">
                            <span class="kpi__label">Total PDL</span>
                            <span class="kpi__icon"><i class="bi bi-people-fill"></i></span>
                        </div>
                        <strong class="kpi__value" id="statTotalInmates">—</strong>
                        <span class="kpi__hint">Persons deprived of liberty</span>
                    </article>
                    <article class="kpi">
                        <div class="kpi__header">
                            <span class="kpi__label">Visitors Today</span>
                            <span class="kpi__icon"><i class="bi bi-person-check-fill"></i></span>
                        </div>
                        <strong class="kpi__value" id="statVisitors">—</strong>
                        <span class="kpi__hint">Currently recorded</span>
                    </article>
                    <article class="kpi">
                        <div class="kpi__header">
                            <span class="kpi__label">Open Incidents</span>
                            <span class="kpi__icon"><i class="bi bi-exclamation-diamond-fill"></i></span>
                        </div>
                        <strong class="kpi__value" id="statIncidents">—</strong>
                        <span class="kpi__hint">Pending review</span>
                    </article>
                    <article class="kpi">
                        <div class="kpi__header">
                            <span class="kpi__label">Active Users</span>
                            <span class="kpi__icon"><i class="bi bi-person-badge-fill"></i></span>
                        </div>
                        <strong class="kpi__value" id="statUsers">—</strong>
                        <span class="kpi__hint">System accounts</span>
                    </article>
                <?php else: ?>
                    <article class="kpi">
                        <div class="kpi__header">
                            <span class="kpi__label">My Entries Today</span>
                            <span class="kpi__icon"><i class="bi bi-pencil-square"></i></span>
                        </div>
                        <strong class="kpi__value" id="statMyEntries">—</strong>
                        <span class="kpi__hint">Records you created</span>
                    </article>
                    <article class="kpi">
                        <div class="kpi__header">
                            <span class="kpi__label">Headcounts Logged</span>
                            <span class="kpi__icon"><i class="bi bi-clipboard2-check-fill"></i></span>
                        </div>
                        <strong class="kpi__value" id="statMyHeadcount">—</strong>
                        <span class="kpi__hint">Submitted today</span>
                    </article>
                    <article class="kpi">
                        <div class="kpi__header">
                            <span class="kpi__label">Total PDL</span>
                            <span class="kpi__icon"><i class="bi bi-people-fill"></i></span>
                        </div>
                        <strong class="kpi__value" id="statTotalInmates">—</strong>
                        <span class="kpi__hint">Facility population</span>
                    </article>
                    <article class="kpi">
                        <div class="kpi__header">
                            <span class="kpi__label">Visitors Today</span>
                            <span class="kpi__icon"><i class="bi bi-person-check-fill"></i></span>
                        </div>
                        <strong class="kpi__value" id="statVisitors">—</strong>
                        <span class="kpi__hint">Currently on-site</span>
                    </article>
                <?php endif; ?>
            </div>

            <?php if ($isAdmin): ?>

                <!-- CHART ROW 1 — Population trend + Classification -->
                <div class="chart-grid chart-grid--2">
                    <article class="card">
                        <header class="card__header">
                            <h2 class="card__title">Population Trend</h2>
                            <div class="tab-group" role="tablist" data-chart-range="population">
                                <button type="button" class="tab-btn">7D</button>
                                <button type="button" class="tab-btn is-active">30D</button>
                                <button type="button" class="tab-btn">12M</button>
                            </div>
                        </header>
                        <div class="chart-container">
                            <canvas id="chartPopulationTrend" height="220"></canvas>
                        </div>
                    </article>

                    <article class="card">
                        <header class="card__header">
                            <h2 class="card__title">Classification</h2>
                        </header>
                        <div class="chart-container">
                            <canvas id="chartClassification" height="220"></canvas>
                        </div>
                    </article>
                </div>

                <!-- CHART ROW 2 — Top offenses + Visitors per day -->
                <div class="chart-grid chart-grid--2">
                    <article class="card">
                        <header class="card__header">
                            <h2 class="card__title">Top Offenses</h2>
                        </header>
                        <div class="chart-container">
                            <canvas id="chartTopOffenses" height="240"></canvas>
                        </div>
                    </article>

                    <article class="card">
                        <header class="card__header">
                            <h2 class="card__title">Visitors per Day</h2>
                        </header>
                        <div class="chart-container">
                            <canvas id="chartVisitors" height="240"></canvas>
                        </div>
                    </article>
                </div>

                <!-- CHART ROW 3 — Incidents (type + severity) -->
                <div class="chart-grid chart-grid--2">
                    <article class="card">
                        <header class="card__header">
                            <h2 class="card__title">Incidents by Type</h2>
                        </header>
                        <div class="chart-container">
                            <canvas id="chartIncidentsType" height="240"></canvas>
                        </div>
                    </article>

                    <article class="card">
                        <header class="card__header">
                            <h2 class="card__title">Incidents by Severity</h2>
                        </header>
                        <div class="chart-container">
                            <canvas id="chartIncidentsSeverity" height="240"></canvas>
                        </div>
                    </article>
                </div>

                <!-- CHART ROW 4 — Age distribution + Inmates per unit -->
                <div class="chart-grid chart-grid--2">
                    <article class="card">
                        <header class="card__header">
                            <h2 class="card__title">Age Distribution</h2>
                        </header>
                        <div class="chart-container">
                            <canvas id="chartAge" height="220"></canvas>
                        </div>
                    </article>

                    <article class="card">
                        <header class="card__header">
                            <h2 class="card__title">Inmates per Jail Unit</h2>
                        </header>
                        <div class="chart-container">
                            <canvas id="chartUnits" height="220"></canvas>
                        </div>
                    </article>
                </div>

                <!-- CHART ROW 5 — Admissions + Drug cases + Compliance -->
                <div class="chart-grid chart-grid--3">
                    <article class="card">
                        <header class="card__header">
                            <h2 class="card__title">Admissions (6 mo.)</h2>
                        </header>
                        <div class="chart-container">
                            <canvas id="chartAdmissions" height="200"></canvas>
                        </div>
                    </article>

                    <article class="card">
                        <header class="card__header">
                            <h2 class="card__title">Drug Cases</h2>
                        </header>
                        <div class="chart-container">
                            <canvas id="chartDrugCases" height="200"></canvas>
                        </div>
                    </article>

                    <article class="card">
                        <header class="card__header">
                            <h2 class="card__title">Headcount Compliance</h2>
                            <span class="meta-text">today</span>
                        </header>
                        <div id="complianceList" class="compliance-list">
                            <p class="meta-text">Loading…</p>
                        </div>
                    </article>
                </div>

            <?php endif; ?>

            <article class="card">
                <header class="card__header">
                    <h2 class="card__title">Quick Actions</h2>
                </header>
                <div class="quick-actions">
                    <?php if ($isAdmin): ?>
                        <button type="button" class="action-tile" data-section="headcount">
                            <i class="bi bi-clipboard2-check"></i><span>Review headcount</span>
                        </button>
                        <button type="button" class="action-tile" data-section="incidents">
                            <i class="bi bi-exclamation-diamond"></i><span>Log an incident</span>
                        </button>
                        <button type="button" class="action-tile" data-section="users">
                            <i class="bi bi-person-gear"></i><span>Manage users</span>
                        </button>
                    <?php else: ?>
                        <button type="button" class="action-tile" id="quickRecordInmateBtn">
                            <i class="bi bi-pencil-square"></i><span>Record new inmate</span>
                        </button>
                        <button type="button" class="action-tile" data-section="headcount">
                            <i class="bi bi-clipboard2-check"></i><span>Submit headcount</span>
                        </button>
                        <button type="button" class="action-tile" data-section="visitors">
                            <i class="bi bi-person-plus"></i><span>Log visitor</span>
                        </button>
                        <button type="button" class="action-tile" data-section="incidents">
                            <i class="bi bi-exclamation-diamond"></i><span>Report incident</span>
                        </button>
                    <?php endif; ?>
                </div>
            </article>

            <footer class="dashboard-statusbar">
                <span class="statusbar__item">
                    <i class="bi bi-circle-fill statusbar__dot statusbar__dot--ok"></i>
                    Database connected
                </span>
                <span class="statusbar__sep">·</span>
                <span class="statusbar__item">
                    <i class="bi bi-clock"></i>
                    Last sync: just now
                </span>
                <span class="statusbar__sep">·</span>
                <span class="statusbar__item">
                    <i class="bi bi-person-badge"></i>
                    Session: <?= htmlspecialchars($user['role'], ENT_QUOTES) ?>
                </span>
                <span class="statusbar__spacer"></span>
                <span class="statusbar__item statusbar__item--muted">
                    IRS v1.0 · <?= htmlspecialchars($today, ENT_QUOTES) ?>
                </span>
            </footer>
        </section>



        <!-- ============================================================
             INMATES
             ============================================================ -->
        <?php require __DIR__ . '/inmates-section.php'; ?>


        <!-- VISITORS -->
        <?php require __DIR__ . '/visitors-section.php'; ?>

        <!-- INCIDENTS -->
        <?php require __DIR__ . '/incidents-section.php'; ?>

        <!-- HEADCOUNT -->
        <?php require __DIR__ . '/headcount-section.php'; ?>

        <!-- ============================================================
             PROFILE (shared — admin & staff)
             ============================================================ -->
        <?php require __DIR__ . '/profile-section.php'; ?>

        <!-- ============================================================
             ADMIN-ONLY SECTIONS
             ============================================================ -->
        <?php if ($isAdmin): ?>
            <?php require __DIR__ . '/users-section.php'; ?>
            <?php require __DIR__ . '/jail-units-section.php'; ?>
            <?php require __DIR__ . '/reports-section.php'; ?>
        <?php endif; ?>

    </main>
</section>

<?php if ($isAdmin): ?>
    <?php require __DIR__ . '/../components/modal-user.php'; ?>
<?php endif; ?>

<?php require __DIR__ . '/../components/modal-inmate.php'; ?>
<?php require __DIR__ . '/../components/modal-confirm.php'; ?>
<?php require __DIR__ . '/../components/modal-success.php'; ?>
<?php require __DIR__ . '/../components/modal-logout.php'; ?>