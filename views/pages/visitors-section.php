<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isLoggedIn()) return;

$isVisitorsActive = ($currentSection ?? '') === 'visitors';
$isAdmin = Auth::isAdmin();
?>
<section id="dashboard-visitors"
         class="dashboard-section <?= $isVisitorsActive ? 'is-visible' : '' ?>"
         data-is-admin="<?= $isAdmin ? '1' : '0' ?>">

    <header class="section-header">
        <div>
            <h1>Visitors</h1>
            <p>Log and monitor visitors entering the facility.</p>
        </div>
        <div class="section-header__tools">
            <button type="button" class="btn-primary btn-sm" id="visitorCreateBtn">
                <i class="bi bi-plus-lg"></i> Log Visitor
            </button>
        </div>
    </header>

    <div class="card card--compact" style="padding: 0;">

        <div class="panel__toolbar">
            <div class="input-wrap">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="visitorSearch" class="input-search"
                       placeholder="Search by visitor name, PDL, or ID number"
                       aria-label="Search visitors">
            </div>

            <select id="visitorJailUnitFilter" class="input-select" aria-label="Filter by jail unit">
                <option value="">All jail units</option>
            </select>

            <select id="visitorStatusFilter" class="input-select" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="in">Currently Inside</option>
                <option value="out">Checked Out</option>
            </select>

            <input type="date" id="visitorDateFilter" class="input-select" aria-label="Filter by date">
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Visitor</th>
                        <th>PDL Visited</th>
                        <th>Relationship</th>
                        <th>Jail Unit</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                        <th class="ta-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="visitorTableBody">
                    <tr>
                        <td colspan="8" class="data-table__empty">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <span class="pagination__summary" id="visitorPaginationSummary"></span>
            <div class="pagination__controls">
                <button type="button" class="btn-secondary btn-sm" id="visitorPrevBtn" disabled>
                    <i class="bi bi-chevron-left"></i> Prev
                </button>
                <button type="button" class="btn-secondary btn-sm" id="visitorNextBtn" disabled>
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

    </div>
</section>

<!-- ============================================================
     VISITOR MODAL
     ============================================================ -->
<div class="modal-backdrop is-hidden" id="visitorModal">
    <div class="dj-modal dj-modal--wide" role="dialog" aria-modal="true" aria-labelledby="visitorModalTitle">
        <header class="dj-modal__header">
            <div class="dj-modal__header-title">
                <h2 id="visitorModalTitle">Log Visitor</h2>
            </div>
            <button type="button" class="dj-modal__close" data-visitor-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <form id="visitorForm" autocomplete="off" style="display: contents;">
            <div class="dj-modal__body">

                <input type="hidden" id="visitorId">
                <input type="hidden" id="visitorInmateId">

                <fieldset class="user-form-section">
                    <legend>Visitor</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Full Name</span>
                            <input type="text" id="visitorFullName" required maxlength="150">
                        </label>
                        <label class="field">
                            <span>Relationship to PDL</span>
                            <select id="visitorRelationship" required>
                                <option value="">—</option>
                                <option>Mother</option>
                                <option>Father</option>
                                <option>Spouse</option>
                                <option>Son</option>
                                <option>Daughter</option>
                                <option>Sibling</option>
                                <option>Grandparent</option>
                                <option>Relative</option>
                                <option>Friend</option>
                                <option>Lawyer</option>
                                <option>Paralegal</option>
                                <option>Social Worker</option>
                                <option>Government Official</option>
                                <option value="Others">Others</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Contact No.</span>
                            <input type="tel" id="visitorContactNo" maxlength="30">
                            <span class="field__hint" id="visitorContactHint">Optional · e.g. 09171234567</span>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field" id="visitorRelationshipOtherWrap" style="display:none;">
                            <span>Specify relationship</span>
                            <input type="text" id="visitorRelationshipOther" maxlength="100"
                                   placeholder="Enter relationship">
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span>Address</span>
                            <input type="text" id="visitorAddress" required maxlength="255">
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Identification</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>ID Type</span>
                            <select id="visitorIdType">
                                <option value="">—</option>
                                <option>Driver's License</option>
                                <option>National ID</option>
                                <option>Passport</option>
                                <option>UMID</option>
                                <option>Voter's ID</option>
                                <option>PRC ID</option>
                                <option>Barangay ID</option>
                                <option>Other</option>
                            </select>
                            <span class="field__hint" id="visitorIdTypeHint">Optional</span>
                        </label>
                        <label class="field">
                            <span>ID Number</span>
                            <input type="text" id="visitorIdNumber" maxlength="100">
                            <span class="field__hint" id="visitorIdNumberHint">Optional</span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Visit Details</legend>
                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span>PDL Visited</span>
                            <div id="visitorInmateWrap"></div>
                            <span class="field__hint">Only PDLs currently in custody are shown.</span>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Purpose</span>
                            <select id="visitorPurpose" required>
                                <option value="">—</option>
                                <option>Family Visit</option>
                                <option>Legal Counsel</option>
                                <option>Court-related</option>
                                <option>Medical Follow-up</option>
                                <option>Counseling</option>
                                <option>Religious Visit</option>
                                <option>Social Worker Visit</option>
                                <option>Government Inspection</option>
                                <option>Media Coverage</option>
                                <option value="Others">Others</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Time In</span>
                            <input type="datetime-local" id="visitorTimeIn" required>
                        </label>
                        <label class="field">
                            <span>Time Out</span>
                            <input type="datetime-local" id="visitorTimeOut">
                            <span class="field__hint">Leave blank if still inside</span>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field" id="visitorPurposeOtherWrap" style="display:none;">
                            <span>Specify purpose</span>
                            <input type="text" id="visitorPurposeOther" maxlength="100"
                                   placeholder="Enter purpose">
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Notes</legend>
                    <label class="field">
                        <span>Additional notes</span>
                        <textarea id="visitorNotes" rows="3"></textarea>
                    </label>
                </fieldset>

                <p class="modal__error" id="visitorFormError" hidden></p>
            </div>

            <footer class="dj-modal__footer">
                <button type="button" class="btn-secondary" data-visitor-close>Cancel</button>
                <button type="submit" class="btn-primary" id="visitorSaveBtn">Save</button>
            </footer>
        </form>
    </div>
</div>