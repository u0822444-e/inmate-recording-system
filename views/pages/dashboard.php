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
$allowedSections = ['overview', 'recording', 'inmates', 'visitors', 'incidents', 'headcount', 'users'];
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
                    <h1><?= $isAdmin ? 'Overview' : 'My Dashboard' ?></h1>
                    <p>Welcome back, <strong><?= htmlspecialchars($user['full_name'] ?: $user['username'], ENT_QUOTES) ?></strong></p>
                </div>
                <div class="section-header__tools">
                    <span class="meta-text"><?= htmlspecialchars($today, ENT_QUOTES) ?></span>
                </div>
            </header>

            <?php if ($isAdmin): ?>
            <div class="alert-banner" role="status">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <div class="alert-banner__content">
                    <strong>Daily headcount due by 18:00</strong>
                    <span>2 shifts are still pending for today's population verification.</span>
                </div>
                <button type="button" class="alert-banner__close" aria-label="Dismiss">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <?php else: ?>
            <div class="alert-banner" role="status">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <div class="alert-banner__content">
                    <strong>Shift Reminder</strong>
                    <span>Submit your headcount before the end of your shift.</span>
                </div>
                <button type="button" class="alert-banner__close" aria-label="Dismiss">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <?php endif; ?>

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

            <div class="panel-grid">
                <article class="card">
                    <header class="card__header">
                        <h2 class="card__title">Population Trend</h2>
                        <div class="tab-group" role="tablist">
                            <button type="button" class="tab-btn is-active">7D</button>
                            <button type="button" class="tab-btn">30D</button>
                            <button type="button" class="tab-btn">12M</button>
                        </div>
                    </header>

                    <div class="chart-placeholder">
                        <i class="bi bi-graph-up"></i>
                        <span>Trend data will render here</span>
                    </div>
                </article>

                <article class="card">
                    <header class="card__header">
                        <h2 class="card__title">Upcoming</h2>
                        <button type="button" class="btn-ghost btn-sm">
                            <i class="bi bi-calendar3"></i> Calendar
                        </button>
                    </header>

                    <ul class="event-list" id="upcomingEvents">
                        <li class="event">
                            <span class="event__date"><strong>14</strong><small>OCT</small></span>
                            <div class="event__body">
                                <strong>Court Hearing</strong>
                                <span>RTC Ipil · 09:00 · 3 PDL</span>
                            </div>
                        </li>
                        <li class="event">
                            <span class="event__date"><strong>15</strong><small>OCT</small></span>
                            <div class="event__body">
                                <strong>Visitation Day</strong>
                                <span>Block B · 13:00–17:00</span>
                            </div>
                        </li>
                        <li class="event">
                            <span class="event__date"><strong>18</strong><small>OCT</small></span>
                            <div class="event__body">
                                <strong>Headcount Audit</strong>
                                <span>Region IX · 08:00</span>
                            </div>
                        </li>
                    </ul>
                </article>
            </div>

            <div class="panel-grid">
                <article class="card">
                    <header class="card__header">
                        <h2 class="card__title"><?= $isAdmin ? 'Recent Activity' : 'My Recent Activity' ?></h2>
                    </header>

                    <ul class="activity-list" id="activityFeed">
                        <li class="activity">
                            <span class="activity__icon"><i class="bi bi-clock-history"></i></span>
                            <div class="activity__body">
                                <strong>Loading…</strong>
                                <span>Fetching recent events</span>
                            </div>
                        </li>
                    </ul>
                </article>

                <article class="card">
                    <header class="card__header">
                        <h2 class="card__title">Quick Actions</h2>
                    </header>

                    <div class="quick-actions">
                        <?php if ($isAdmin): ?>
                            <button type="button" class="action-tile" data-section="inmates">
                                <i class="bi bi-person-plus"></i>
                                <span>Add inmate record</span>
                            </button>
                            <button type="button" class="action-tile" data-section="headcount">
                                <i class="bi bi-clipboard2-check"></i>
                                <span>Review headcount</span>
                            </button>
                            <button type="button" class="action-tile" data-section="incidents">
                                <i class="bi bi-exclamation-diamond"></i>
                                <span>Log an incident</span>
                            </button>
                            <button type="button" class="action-tile" data-section="users">
                                <i class="bi bi-person-gear"></i>
                                <span>Manage users</span>
                            </button>
                        <?php else: ?>
                            <button type="button" class="action-tile" data-section="recording">
                                <i class="bi bi-pencil-square"></i>
                                <span>Record new inmate</span>
                            </button>
                            <button type="button" class="action-tile" data-section="headcount">
                                <i class="bi bi-clipboard2-check"></i>
                                <span>Submit headcount</span>
                            </button>
                            <button type="button" class="action-tile" data-section="visitors">
                                <i class="bi bi-person-plus"></i>
                                <span>Log visitor</span>
                            </button>
                            <button type="button" class="action-tile" data-section="incidents">
                                <i class="bi bi-exclamation-diamond"></i>
                                <span>Report incident</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </article>
            </div>

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
             STAFF: RECORD INMATE
             ============================================================ -->
        <?php if (!$isAdmin): ?>
        <section id="dashboard-recording"
                 class="dashboard-section <?= $currentSection === 'recording' ? 'is-visible' : '' ?>">

            <header class="section-header">
                <div>
                    <h1>Record Inmate</h1>
                    <p>Encode new PDL information into the system.</p>
                </div>
            </header>

            <form class="form-grid" id="inmateForm" onsubmit="event.preventDefault();">
                <fieldset class="fieldset">
                    <legend>Personal Information</legend>
                    <div class="form-row">
                        <label>First name<input type="text" name="first_name" required></label>
                        <label>Middle name<input type="text" name="middle_name"></label>
                        <label>Last name<input type="text" name="last_name" required></label>
                    </div>
                    <div class="form-row">
                        <label>Date of birth<input type="date" name="dob"></label>
                        <label>Sex
                            <select name="sex">
                                <option value="">—</option>
                                <option>Male</option>
                                <option>Female</option>
                            </select>
                        </label>
                        <label>Civil status
                            <select name="civil_status">
                                <option value="">—</option>
                                <option>Single</option>
                                <option>Married</option>
                                <option>Widowed</option>
                                <option>Separated</option>
                            </select>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="fieldset">
                    <legend>Address</legend>
                    <div class="form-row">
                        <label>Municipality<input type="text" name="municipality"></label>
                        <label>Barangay<input type="text" name="barangay"></label>
                        <label>Province<input type="text" name="province"></label>
                    </div>
                </fieldset>

                <fieldset class="fieldset">
                    <legend>Sentence</legend>
                    <div class="form-row">
                        <label>Case number<input type="text" name="case_no"></label>
                        <label>Offense<input type="text" name="offense"></label>
                        <label>Sentence<input type="text" name="sentence"></label>
                    </div>
                    <div class="form-row">
                        <label>Date committed<input type="date" name="date_committed"></label>
                        <label>Status
                            <select name="status">
                                <option>Detained</option>
                                <option>Convicted</option>
                                <option>Released</option>
                            </select>
                        </label>
                    </div>
                </fieldset>

                <div class="form-actions">
                    <button type="reset" class="btn-secondary">Clear</button>
                    <button type="submit" class="btn-primary">Save Record</button>
                </div>
            </form>
        </section>
        <?php endif; ?>

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