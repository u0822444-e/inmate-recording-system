<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isLoggedIn()) return;

$isHeadcountActive = ($currentSection ?? '') === 'headcount';
$isAdmin = Auth::isAdmin();

$currentJailUnitId = Auth::jailUnitId();
$lockJailUnit      = !$isAdmin && $currentJailUnitId !== null;
?>
<section id="dashboard-headcount"
         class="dashboard-section <?= $isHeadcountActive ? 'is-visible' : '' ?>"
         data-is-admin="<?= $isAdmin ? '1' : '0' ?>"
         data-lock-jail-unit="<?= $lockJailUnit ? '1' : '0' ?>"
         data-jail-unit-id="<?= (int) ($currentJailUnitId ?? 0) ?>">

    <header class="section-header">
        <div>
            <h1>Headcount</h1>
            <p>Daily population verification.</p>
        </div>
        <div class="section-header__tools">
            <button type="button" class="btn-secondary btn-sm" id="headcountArchiveToggleBtn">
                <i class="bi bi-archive"></i>
                <span id="headcountArchiveToggleLabel">View Archived</span>
            </button>
            <button type="button" class="btn-primary btn-sm" id="headcountSubmitBtn">
                <i class="bi bi-plus-lg"></i> Submit Headcount
            </button>
        </div>
    </header>

    <!-- ============================================================
         TODAY'S SHIFT STATUS STRIP
         ============================================================ -->
    <div class="shift-strip" id="headcountShiftStrip">
        <span class="shift-strip__label">Today</span>
        <span class="shift-pill" data-shift="Morning">Morning · <span class="shift-pill__state">—</span></span>
        <span class="shift-pill" data-shift="Afternoon">Afternoon · <span class="shift-pill__state">—</span></span>
        <span class="shift-pill" data-shift="Night">Night · <span class="shift-pill__state">—</span></span>
    </div>

    <div class="card card--compact" style="padding: 0;">

        <div class="panel__toolbar">
            <select id="headcountJailUnitFilter" class="input-select" aria-label="Filter by jail unit">
                <option value="">All jail units</option>
            </select>

            <select id="headcountShiftFilter" class="input-select" aria-label="Filter by shift">
                <option value="">All shifts</option>
                <option>Morning</option>
                <option>Afternoon</option>
                <option>Night</option>
            </select>

            <select id="headcountStatusFilter" class="input-select" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option>Submitted</option>
                <option>Verified</option>
                <option>Discrepancy</option>
            </select>

            <input type="date" id="headcountDateFilter" class="input-select" aria-label="Filter by date">
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Shift</th>
                        <th>Jail Unit</th>
                        <th class="ta-right">Expected</th>
                        <th class="ta-right">Actual</th>
                        <th class="ta-right">Δ</th>
                        <th>Status</th>
                        <th>Recorded By</th>
                        <th class="ta-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="headcountTableBody">
                    <tr>
                        <td colspan="9" class="data-table__empty">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <span class="pagination__summary" id="headcountPaginationSummary"></span>
            <div class="pagination__controls">
                <button type="button" class="btn-secondary btn-sm" id="headcountPrevBtn" disabled>
                    <i class="bi bi-chevron-left"></i> Prev
                </button>
                <button type="button" class="btn-secondary btn-sm" id="headcountNextBtn" disabled>
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

    </div>
</section>

<!-- ============================================================
     HEADCOUNT MODAL (Submit / Edit)
     ============================================================ -->
<div class="modal-backdrop is-hidden" id="headcountModal">
    <div class="dj-modal dj-modal--wide" role="dialog" aria-modal="true" aria-labelledby="headcountModalTitle">
        <header class="dj-modal__header">
            <div class="dj-modal__header-title">
                <h2 id="headcountModalTitle">Submit Headcount</h2>
            </div>
            <button type="button" class="dj-modal__close" data-headcount-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <form id="headcountForm" autocomplete="off" style="display: contents;">
            <div class="dj-modal__body">

                <input type="hidden" id="headcountId">

                <fieldset class="user-form-section">
                    <legend>Shift</legend>

                    <div class="form-grid-row">
                        <label class="field">
                            <span>Date</span>
                            <input type="date" id="headcountDate" required>
                        </label>
                        <label class="field">
                            <span>Shift</span>
                            <select id="headcountShift" required>
                                <option>Morning</option>
                                <option>Afternoon</option>
                                <option>Night</option>
                            </select>
                        </label>
                    </div>

                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span>Jail Unit</span>
                            <div id="headcountJailUnitWrap"></div>
                            <?php if ($lockJailUnit): ?>
                                <span class="field__hint">Auto-assigned to your station</span>
                            <?php endif; ?>
                        </label>
                    </div>

                    <input type="hidden" id="headcountJailUnitId"
                           value="<?= $lockJailUnit ? (int) $currentJailUnitId : '' ?>">
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Count</legend>

                    <div class="form-grid-row">
                        <label class="field">
                            <span>Expected (system)</span>
                            <input type="number" id="headcountExpected" readonly
                                   style="background:#f7f9fc;color:var(--text-soft);">
                        </label>
                        <label class="field">
                            <span>Actual (physical count)</span>
                            <input type="number" id="headcountActual" min="0" required>
                        </label>
                    </div>

                    <div class="form-grid-row">
                        <div class="field" style="grid-column: span 3;">
                            <span class="field__hint">
                                Expected = PDLs marked "In Custody" in your unit ·
                                Actual = the count you performed ·
                                <span id="headcountDiffHint">—</span>
                            </span>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Remarks</legend>
                    <label class="field">
                        <span>Notes (optional)</span>
                        <textarea id="headcountRemarks" rows="3"
                                  placeholder="Explain any discrepancy or add context"></textarea>
                    </label>
                </fieldset>

                <p class="modal__error" id="headcountFormError" hidden></p>
            </div>

            <footer class="dj-modal__footer">
                <button type="button" class="btn-secondary" data-headcount-close>Cancel</button>
                <button type="submit" class="btn-primary" id="headcountSaveBtn">Submit</button>
            </footer>
        </form>
    </div>
</div>