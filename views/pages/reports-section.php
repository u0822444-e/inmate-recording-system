<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isLoggedIn() || !Auth::isAdmin()) return;

$isReportsActive = ($currentSection ?? '') === 'reports';
?>
<section id="dashboard-reports"
         class="dashboard-section <?= $isReportsActive ? 'is-visible' : '' ?>">

    <header class="section-header">
        <div>
            <h1>Reports</h1>
            <p>Generate BJMP-compliant reports for Ipil District Jail.</p>
        </div>
        <div class="section-header__tools">
            <input type="month" id="reportMonth" class="input-select"
                   value="<?= date('Y-m') ?>" aria-label="Report month" hidden>
            <button type="button" class="btn-secondary btn-sm" id="reportExportBtn">
                <i class="bi bi-download"></i> Export CSV
            </button>
        </div>
    </header>

    <div class="report-tabs" role="tablist">
        <button type="button" class="report-tab is-active" data-report="population" role="tab">
            Population Snapshot
        </button>
        <button type="button" class="report-tab" data-report="flow" role="tab">
            Committed / Released
        </button>
        <button type="button" class="report-tab" data-report="drug-cases" role="tab">
            Drug Cases
        </button>
    </div>

    <div class="card card--compact" id="reportOutput" style="padding: 0;">
        <div class="data-table__empty">Loading…</div>
    </div>
</section>