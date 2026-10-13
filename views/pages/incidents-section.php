<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isLoggedIn()) return;

$isIncidentsActive = ($currentSection ?? '') === 'incidents';
$isAdmin = Auth::isAdmin();

$currentJailUnitId = Auth::jailUnitId();
$lockJailUnit      = !$isAdmin && $currentJailUnitId !== null;
?>
<section id="dashboard-incidents"
         class="dashboard-section <?= $isIncidentsActive ? 'is-visible' : '' ?>"
         data-is-admin="<?= $isAdmin ? '1' : '0' ?>"
         data-lock-jail-unit="<?= $lockJailUnit ? '1' : '0' ?>"
         data-jail-unit-id="<?= (int) ($currentJailUnitId ?? 0) ?>">

    <header class="section-header">
        <div>
            <h1>Incidents</h1>
            <p>Report and review facility incidents.</p>
        </div>
        <div class="section-header__tools">
            <button type="button" class="btn-primary btn-sm" id="incidentCreateBtn">
                <i class="bi bi-plus-lg"></i> Report Incident
            </button>
        </div>
    </header>

    <div class="card card--compact" style="padding: 0;">

        <div class="panel__toolbar">
            <div class="input-wrap">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="incidentSearch" class="input-search"
                       placeholder="Search by description, spot, or persons"
                       aria-label="Search incidents">
            </div>

            <select id="incidentJailUnitFilter" class="input-select" aria-label="Filter by jail unit">
                <option value="">All jail units</option>
            </select>

            <select id="incidentTypeFilter" class="input-select" aria-label="Filter by type">
                <option value="">All types</option>
                <option>Assault</option>
                <option>Contraband</option>
                <option>Escape Attempt</option>
                <option>Medical Emergency</option>
                <option>Disturbance</option>
                <option>Self-Harm</option>
                <option>Death</option>
                <option>Fire</option>
                <option>Property Damage</option>
                <option>Other</option>
            </select>

            <select id="incidentSeverityFilter" class="input-select" aria-label="Filter by severity">
                <option value="">All severities</option>
                <option>Low</option>
                <option>Medium</option>
                <option>High</option>
                <option>Critical</option>
            </select>

            <select id="incidentStatusFilter" class="input-select" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option>Open</option>
                <option>Under Review</option>
                <option>Resolved</option>
                <option>Closed</option>
            </select>

            <input type="date" id="incidentDateFilter" class="input-select" aria-label="Filter by date">
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date / Time</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Location</th>
                        <th>Jail Unit</th>
                        <th>Reported By</th>
                        <th>Status</th>
                        <th class="ta-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="incidentTableBody">
                    <tr>
                        <td colspan="8" class="data-table__empty">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <span class="pagination__summary" id="incidentPaginationSummary"></span>
            <div class="pagination__controls">
                <button type="button" class="btn-secondary btn-sm" id="incidentPrevBtn" disabled>
                    <i class="bi bi-chevron-left"></i> Prev
                </button>
                <button type="button" class="btn-secondary btn-sm" id="incidentNextBtn" disabled>
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

    </div>
</section>

<!-- ============================================================
     INCIDENT MODAL
     ============================================================ -->
<div class="modal-backdrop is-hidden" id="incidentModal">
    <div class="dj-modal dj-modal--wide" role="dialog" aria-modal="true" aria-labelledby="incidentModalTitle">
        <header class="dj-modal__header">
            <div class="dj-modal__header-title">
                <h2 id="incidentModalTitle">Report Incident</h2>
            </div>
            <button type="button" class="dj-modal__close" data-incident-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <form id="incidentForm" autocomplete="off" style="display: contents;">
            <div class="dj-modal__body">

                <input type="hidden" id="incidentId">

                <fieldset class="user-form-section">
                    <legend>Incident Details</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Date</span>
                            <input type="date" id="incidentDate" required>
                        </label>
                        <label class="field">
                            <span>Time</span>
                            <input type="time" id="incidentTime" required>
                        </label>
                        <label class="field">
                            <span>Type</span>
                            <select id="incidentType" required>
                                <option value="">—</option>
                                <option>Assault</option>
                                <option>Contraband</option>
                                <option>Escape Attempt</option>
                                <option>Medical Emergency</option>
                                <option>Disturbance</option>
                                <option>Self-Harm</option>
                                <option>Death</option>
                                <option>Fire</option>
                                <option>Property Damage</option>
                                <option>Other</option>
                            </select>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Severity</span>
                            <select id="incidentSeverity">
                                <option>Low</option>
                                <option selected>Medium</option>
                                <option>High</option>
                                <option>Critical</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Jail Unit</span>
                            <div id="incidentJailUnitWrap"></div>
                            <?php if ($lockJailUnit): ?>
                                <span class="field__hint">Auto-assigned to your station</span>
                            <?php endif; ?>
                        </label>
                    </div>

                    <input type="hidden" id="incidentJailUnitId"
                           value="<?= $lockJailUnit ? (int) $currentJailUnitId : '' ?>">
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Location</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Province</span>
                            <div id="incidentProvinceWrap"></div>
                        </label>
                        <label class="field">
                            <span>Municipality</span>
                            <div id="incidentMunicipalityWrap"></div>
                        </label>
                        <label class="field">
                            <span>Barangay</span>
                            <div id="incidentBarangayWrap"></div>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span>Specific spot</span>
                            <input type="text" id="incidentSpot" maxlength="150"
                                   placeholder="e.g. Block B, Cell 3, Mess Hall, Comfort Room">
                            <span class="field__hint">Optional — where within the facility</span>
                        </label>
                    </div>
                    <input type="hidden" id="incidentProvinceValue">
                    <input type="hidden" id="incidentMunicipalityId">
                    <input type="hidden" id="incidentBarangayId">
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Description</legend>
                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span>What happened</span>
                            <textarea id="incidentDescription" rows="4" required
                                      placeholder="Describe the incident in detail"></textarea>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span>Persons involved</span>
                            <textarea id="incidentPersons" rows="2"
                                      placeholder="Names, PDL numbers, or roles of those involved"></textarea>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span>Witnesses</span>
                            <textarea id="incidentWitnesses" rows="2"
                                      placeholder="Names of witnesses"></textarea>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section" id="incidentResolutionSection" style="display:none;">
                    <legend>Resolution</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Status</span>
                            <select id="incidentStatus">
                                <option>Open</option>
                                <option>Under Review</option>
                                <option>Resolved</option>
                                <option>Closed</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Resolved on</span>
                            <input type="datetime-local" id="incidentResolvedAt">
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span>Action taken</span>
                            <textarea id="incidentActionTaken" rows="3"
                                      placeholder="Describe the action taken"></textarea>
                        </label>
                    </div>
                </fieldset>

                <p class="modal__error" id="incidentFormError" hidden></p>
            </div>

            <footer class="dj-modal__footer">
                <button type="button" class="btn-secondary" data-incident-close>Cancel</button>
                <button type="submit" class="btn-primary" id="incidentSaveBtn">Save</button>
            </footer>
        </form>
    </div>
</div>