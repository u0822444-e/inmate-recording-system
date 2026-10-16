<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isLoggedIn()) return;

$isAdmin = Auth::isAdmin();
?>
<div class="modal-backdrop is-hidden" id="inmateCreateModal">
    <div class="dj-modal dj-modal--wide" role="dialog" aria-modal="true" aria-labelledby="inmateCreateTitle">
        <header class="dj-modal__header">
            <div class="dj-modal__header-title">
                <h2 id="inmateCreateTitle">New Inmate Record</h2>
            </div>
            <button type="button" class="dj-modal__close" data-inmate-create-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <form id="inmateForm" autocomplete="off" style="display: contents;" novalidate>
            <div class="dj-modal__body">

                <input type="hidden" name="province" id="inmateProvinceValue">
                <input type="hidden" name="municipality_id" id="inmateMunicipalityId">
                <input type="hidden" name="barangay_id" id="inmateBarangayId">
                <input type="hidden" name="offense_id" id="inmateOffenseId">
                <input type="hidden" name="jail_unit_id" id="inmateJailUnitId">

                <fieldset class="user-form-section">
                    <legend>Identification</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Inmate No.</span>
                            <input type="text" name="inmate_number" id="inmateNumber" readonly>
                        </label>
                        <label class="field">
                            <span>Case Reference</span>
                            <input type="text" name="case_reference" id="inmateCaseRef" readonly>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span>Jail Unit</span>
                            <div id="inmateJailUnitWrap"></div>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Personal Information</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>First name</span>
                            <input type="text" name="first_name" id="inmateFirstName" required maxlength="100">
                        </label>
                        <label class="field">
                            <span>Middle name</span>
                            <input type="text" name="middle_name" id="inmateMiddleName" maxlength="100">
                        </label>
                        <label class="field">
                            <span>Last name</span>
                            <input type="text" name="last_name" id="inmateLastName" required maxlength="100">
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Suffix</span>
                            <input type="text" name="suffix" id="inmateSuffix" maxlength="20">
                        </label>
                        <label class="field">
                            <span>Date of birth</span>
                            <input type="date" name="date_of_birth" id="inmateDob">
                        </label>
                        <label class="field">
                            <span>Sex</span>
                            <select name="sex" id="inmateSex">
                                <option value="Unspecified">Unspecified</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Civil status</span>
                            <select name="civil_status" id="inmateCivilStatus">
                                <option value="">—</option>
                                <option>Single</option>
                                <option>Married</option>
                                <option>Widowed</option>
                                <option>Separated</option>
                            </select>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Address</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Province</span>
                            <div id="inmateProvinceWrap"></div>
                        </label>
                        <label class="field">
                            <span>Municipality</span>
                            <div id="inmateMunicipalityWrap"></div>
                        </label>
                        <label class="field">
                            <span>Barangay</span>
                            <div id="inmateBarangayWrap"></div>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Case &amp; Custody</legend>
                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span>Offense</span>
                            <div id="inmateOffenseWrap"></div>
                        </label>
                    </div>

                    <div class="form-grid-row">
                        <label class="field">
                            <span>Classification</span>
                            <select name="classification" id="inmateClassification">
                                <option value="">—</option>
                                <option>Detainee</option>
                                <option>Sentenced</option>
                                <option>Awaiting Trial</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Committed date</span>
                            <input type="date" name="committed_at" id="inmateCommittedAt">
                        </label>
                        <label class="field field-checkbox">
                            <input type="checkbox" name="is_drug_case" id="inmateIsDrugCase" value="1">
                            <span>Drug-related case (R.A. 9165 / 6425)</span>
                        </label>
                    </div>

                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span class="field-label-row">
                                Sentence
                                <span class="sentence-badge" id="sentenceBadge" hidden>auto</span>
                            </span>
                            <div class="sentence-input">
                                <input type="text" name="sentence" id="inmateSentence"
                                    placeholder="Auto-filled from offense">
                                <button type="button" class="sentence-reset" id="sentenceResetBtn" hidden
                                    title="Reset to default">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </div>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Years (min)</span>
                            <input type="number" step="0.1" min="0" name="sentence_years_min" id="inmateSentenceMin">
                        </label>
                        <label class="field">
                            <span>Years (max)</span>
                            <input type="number" step="0.1" min="0" name="sentence_years_max" id="inmateSentenceMax">
                        </label>
                        <label class="field">
                            <span>Admission date</span>
                            <input type="date" name="admission_date" id="inmateAdmissionDate" required>
                        </label>
                        <label class="field">
                            <span>Custody status</span>
                            <select name="custody_status" id="inmateCustodyStatus">
                                <option>In Custody</option>
                                <option>Transferred</option>
                            </select>
                        </label>
                    </div>
                    <input type="hidden" name="sentence_is_manual" id="inmateSentenceManual" value="0">
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Notes</legend>
                    <label class="field">
                        <span>Additional notes</span>
                        <textarea name="notes" id="inmateNotes" rows="3"></textarea>
                    </label>
                </fieldset>

                <p class="modal__error" id="inmateFormError" hidden></p>
            </div>

            <footer class="dj-modal__footer">
                <button type="button" class="btn-secondary" data-inmate-create-close>Cancel</button>
                <button type="submit" class="btn-primary" id="inmateSaveBtn">Save Record</button>
            </footer>
        </form>
    </div>
</div>