<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isAdmin()) return;

$isActive = ($currentSection ?? '') === 'activity-logs';
?>
<section id="dashboard-activity-logs" class="dashboard-section <?= $isActive ? 'is-visible' : '' ?>">
    <header class="section-header">
        <div>
            <h1>Activity Logs</h1>
            <p>System-wide audit trail of user actions.</p>
        </div>
    </header>

    <div class="card card--compact" style="padding: 0;">
        <div class="panel__toolbar">
            <div class="input-wrap">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="logSearch" class="input-search" placeholder="Search description or user..." aria-label="Search logs">
            </div>
            <select id="logModuleFilter" class="input-select" aria-label="Filter by module">
                <option value="">All Modules</option>
                <option value="Visitor">Visitors</option>
                <option value="Inmate">Inmates</option>
                <option value="Incident">Incidents</option>
                <option value="Headcount">Headcount</option>
                <option value="User">Users</option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th class="ta-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody">
                    <tr><td colspan="7" class="data-table__empty">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>