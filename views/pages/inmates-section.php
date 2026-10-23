<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isLoggedIn())
    return;

$isInmatesActive = ($currentSection ?? '') === 'inmates';
$isAdmin = Auth::isAdmin();
?>
<section id="dashboard-inmates" class="dashboard-section <?= $isInmatesActive ? 'is-visible' : '' ?>"
    data-is-admin="<?= $isAdmin ? '1' : '0' ?>">

    <header class="section-header">
        <div>
            <h1>Inmate Records</h1>
            <p>Search, filter, and review PDL records.</p>
        </div>
        <div class="section-header__tools">
            <button type="button" class="btn-primary btn-sm" id="inmateCreateBtn">
                <i class="bi bi-plus-lg"></i> New PDL
            </button>
            <?php if ($isAdmin): ?>
                <button type="button" class="btn-secondary btn-sm" id="inmateExportBtn">
                    <i class="bi bi-download"></i> Export CSV
                </button>
            <?php endif; ?>
        </div>
    </header>

    <div class="card card--compact" style="padding: 0;">

        <div class="filter-toolbar">
            <div class="filter-toolbar__row">
                <div class="input-wrap">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="inmateSearch" class="input-search"
                        placeholder="Search by inmate no., case ref., or name" aria-label="Search inmates">
                </div>

                <!-- Jail Unit filter removed -->

                <select id="inmateStatusFilter" class="input-select" aria-label="Filter by custody status">
                    <option value="">All statuses</option>
                    <option value="In Custody">In Custody</option>
                    <option value="Transferred">Transferred</option>
                </select>

                <select id="inmateOffenseFilter" class="input-select" aria-label="Filter by offense">
                    <option value="">All offenses</option>
                </select>
            </div>

            <div class="filter-toolbar__row filter-toolbar__row--address">
                <span class="filter-toolbar__label">
                    <i class="bi bi-geo-alt"></i> Address
                </span>

                <div id="inmateProvinceFilterWrap" class="filter-toolbar__field"></div>
                <div id="inmateMunicipalityFilterWrap" class="filter-toolbar__field"></div>
                <div id="inmateBarangayFilterWrap" class="filter-toolbar__field"></div>

                <button type="button" class="btn-ghost btn-sm" id="inmateClearAddressBtn" title="Clear address filters">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table data-table--compact">
                <thead>
                    <tr>
                        <th>Inmate No.</th>
                        <th>Name</th>
                        <th>Offense</th>
                        <th>Status</th>
                        <th class="ta-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="inmateTableBody">
                    <tr>
                        <td colspan="5" class="data-table__empty">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-pagination" id="inmatePagination">
            <span class="pagination__summary" id="inmatePaginationSummary"></span>
            <div class="pagination__controls">
                <button type="button" class="btn-secondary btn-sm" id="inmatePrevBtn" disabled>
                    <i class="bi bi-chevron-left"></i> Prev
                </button>
                <button type="button" class="btn-secondary btn-sm" id="inmateNextBtn" disabled>
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

    </div>
</section>

<!-- ============================================================
     INMATE DETAIL MODAL — TABBED
     ============================================================ -->
<div class="modal-backdrop is-hidden" id="inmateModal">
    <div class="dj-modal dj-modal--wide" role="dialog" aria-modal="true" aria-labelledby="inmateModalTitle">
        <header class="dj-modal__header">
            <div class="dj-modal__header-title">
                <span class="detail-header__badge" id="inmateHeaderNumber">—</span>
                <h2 id="inmateModalTitle">Inmate Details</h2>
            </div>
            <button type="button" class="dj-modal__close" data-inmate-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <!-- Tab nav -->
        <nav class="detail-tabs" role="tablist">
            <button type="button" class="detail-tab is-active" data-tab="overview" role="tab">Overview</button>
            <button type="button" class="detail-tab" data-tab="profile" role="tab">Profile</button>
            <button type="button" class="detail-tab" data-tab="case" role="tab">Case</button>
            <button type="button" class="detail-tab" data-tab="custody" role="tab">Custody</button>
            <button type="button" class="detail-tab" data-tab="history" role="tab">History</button>
        </nav>

        <div class="dj-modal__body" id="inmateDetailBody">
            <div class="data-table__empty">Loading...</div>
        </div>

        <footer class="dj-modal__footer">
            <button type="button" class="btn-secondary" data-inmate-close>Close</button>
        </footer>
    </div>
</div>

<!-- ============================================================
     INMATE EDIT MODAL
     ============================================================ -->
<div class="modal-backdrop is-hidden" id="inmateEditModal">
    <div class="dj-modal dj-modal--wide" role="dialog" aria-modal="true" aria-labelledby="inmateEditTitle">
        <header class="dj-modal__header">
            <div class="dj-modal__header-title">
                <span class="detail-header__badge" id="inmateEditNumber">—</span>
                <h2 id="inmateEditTitle">Edit Inmate</h2>
            </div>
            <button type="button" class="dj-modal__close" data-inmate-edit-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <form id="inmateEditForm" autocomplete="off" style="display: contents;">
            <div class="dj-modal__body">

                <input type="hidden" id="editInmateId">
                <input type="hidden" id="editJailUnitId">
                <input type="hidden" id="editMunicipalityId">
                <input type="hidden" id="editBarangayId">
                <input type="hidden" id="editOffenseId">
                <input type="hidden" id="editProvinceValue">
                <input type="hidden" id="editSentenceManual" value="0">

                <!-- Identification -->
                <fieldset class="user-form-section">
                    <legend>Identification</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Inmate No.</span>
                            <input type="text" id="editInmateNumber" readonly>
                        </label>
                        <label class="field">
                            <span>Case Reference</span>
                            <input type="text" id="editCaseReference">
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Jail Unit</span>
                            <div id="editJailUnitWrap"></div>
                        </label>
                    </div>
                </fieldset>

                <!-- Personal -->
                <fieldset class="user-form-section">
                    <legend>Personal Information</legend>
                    
                    <!-- Avatar Upload -->
                    <div class="form-grid-row" style="align-items: center; margin-bottom: 1rem;">
                        <div class="field">
                            <span>Profile Picture</span>
                            <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                                <img id="editAvatarPreview" src="" alt="Avatar" 
                                     style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; background: #e2e8f0; border: 1px solid #cbd5e1; display: none;">
                                <div>
                                    <input type="file" id="editAvatarInput" accept="image/jpeg, image/png, image/webp" style="display: none;">
                                    <button type="button" class="btn-secondary btn-sm" id="editAvatarBtn" onclick="document.getElementById('editAvatarInput').click();">
                                        <i class="bi bi-camera"></i> Change Photo
                                    </button>
                                    <span class="field__hint" style="display: block; margin-top: 0.25rem;">Max 5MB. JPG, PNG, WEBP.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid-row">
                        <label class="field">
                            <span>First name</span>
                            <input type="text" id="editFirstName" required maxlength="100">
                        </label>
                        <label class="field">
                            <span>Middle name</span>
                            <input type="text" id="editMiddleName" maxlength="100">
                        </label>
                        <label class="field">
                            <span>Last name</span>
                            <input type="text" id="editLastName" required maxlength="100">
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Suffix</span>
                            <input type="text" id="editSuffix" maxlength="20">
                        </label>
                        <label class="field">
                            <span>Date of birth</span>
                            <input type="date" id="editDob">
                        </label>
                        <label class="field">
                            <span>Sex</span>
                            <select id="editSex">
                                <option value="Unspecified">Unspecified</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Civil status</span>
                            <select id="editCivilStatus">
                                <option value="">—</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                            </select>
                        </label>
                    </div>
                </fieldset>

                <!-- Address -->
                <fieldset class="user-form-section">
                    <legend>Address</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Province</span>
                            <div id="editProvinceWrap"></div>
                        </label>
                        <label class="field">
                            <span>Municipality</span>
                            <div id="editMunicipalityWrap"></div>
                        </label>
                        <label class="field">
                            <span>Barangay</span>
                            <div id="editBarangayWrap"></div>
                        </label>
                    </div>
                </fieldset>

                <!-- Case & Custody -->
                <fieldset class="user-form-section">
                    <legend>Case &amp; Custody</legend>
                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span>Offense</span>
                            <div id="editOffenseWrap"></div>
                        </label>
                    </div>

                    <div class="form-grid-row">
                        <label class="field">
                            <span>Classification</span>
                            <select id="editClassification">
                                <option value="">—</option>
                                <option>Detainee</option>
                                <option>Sentenced</option>
                                <option>Awaiting Trial</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Committed date</span>
                            <input type="date" id="editCommittedAt">
                        </label>
                        <label class="field field-checkbox">
                            <input type="checkbox" id="editIsDrugCase" value="1">
                            <span>Drug-related case (R.A. 9165 / 6425)</span>
                        </label>
                    </div>

                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span class="field-label-row">
                                Sentence
                                <span class="sentence-badge" id="editSentenceBadge" hidden>auto</span>
                            </span>
                            <div class="sentence-input">
                                <input type="text" id="editSentence" placeholder="Auto-filled from offense">
                                <button type="button" class="sentence-reset" id="editSentenceResetBtn" hidden
                                    title="Reset to default">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </div>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Years (min)</span>
                            <input type="number" step="0.1" min="0" id="editSentenceMin">
                        </label>
                        <label class="field">
                            <span>Years (max)</span>
                            <input type="number" step="0.1" min="0" id="editSentenceMax">
                        </label>
                        <label class="field">
                            <span>Admission date</span>
                            <input type="date" id="editAdmissionDate" required>
                        </label>
                        <label class="field">
                            <span>Custody status</span>
                            <select id="editCustodyStatus">
                                <option value="In Custody">In Custody</option>
                                <option value="Transferred">Transferred</option>
                            </select>
                        </label>
                    </div>
                </fieldset>

                <!-- Notes -->
                <fieldset class="user-form-section">
                    <legend>Notes</legend>
                    <label class="field">
                        <span>Additional notes</span>
                        <textarea id="editNotes" rows="3"></textarea>
                    </label>
                </fieldset>

                <p class="modal__error" id="inmateEditError" hidden></p>
            </div>

            <footer class="dj-modal__footer">
                <button type="button" class="btn-secondary" data-inmate-edit-close>Cancel</button>
                <button type="submit" class="btn-primary" id="inmateEditSaveBtn">Save Changes</button>
            </footer>
        </form>
    </div>
</div>