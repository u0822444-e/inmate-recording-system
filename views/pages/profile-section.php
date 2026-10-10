<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isLoggedIn()) return;

$isProfileActive = ($currentSection ?? '') === 'profile';
$user = Auth::user();
?>
<section id="dashboard-profile"
         class="dashboard-section <?= $isProfileActive ? 'is-visible' : '' ?>">

    <header class="section-header">
        <div>
            <h1>My Profile</h1>
            <p>View and update your account details.</p>
        </div>
    </header>

    <div class="panel-grid" style="grid-template-columns: 2fr 1fr;">

        <!-- ==================================================
             PROFILE DETAILS
             ================================================== -->
        <article class="card" id="profileDetailsCard">
            <header class="card__header">
                <h2 class="card__title">Profile Information</h2>
                <button type="button" class="btn-secondary btn-sm" id="profileEditToggle">
                    <i class="bi bi-pencil"></i> Edit
                </button>
            </header>

            <div class="dj-modal__body" id="profileViewMode">
                <div class="detail-hero">
                    <div class="detail-hero__avatar" id="profileAvatarInitial">—</div>
                    <div class="detail-hero__info">
                        <strong id="profileFullName">—</strong>
                        <span class="meta-text" id="profileRoleLine">—</span>
                    </div>
                </div>

                <section class="detail-section">
                    <header class="detail-section__header"><h3>Account</h3></header>
                    <div class="detail-section__body">
                        <div class="detail-row"><span class="detail-row__label">Username</span><span class="detail-row__value" id="profileUsername">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">Role</span><span class="detail-row__value" id="profileRole">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">Jail Unit</span><span class="detail-row__value" id="profileJailUnit">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">Employee No.</span><span class="detail-row__value" id="profileEmployeeNo">—</span></div>
                    </div>
                </section>

                <section class="detail-section">
                    <header class="detail-section__header"><h3>Personal Information</h3></header>
                    <div class="detail-section__body">
                        <div class="detail-row"><span class="detail-row__label">Full Name</span><span class="detail-row__value" id="profilePersonalName">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">Sex</span><span class="detail-row__value" id="profileSex">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">Date of Birth</span><span class="detail-row__value" id="profileDob">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">Civil Status</span><span class="detail-row__value" id="profileCivilStatus">—</span></div>
                    </div>
                </section>

                <section class="detail-section">
                    <header class="detail-section__header"><h3>Contact</h3></header>
                    <div class="detail-section__body">
                        <div class="detail-row"><span class="detail-row__label">Email</span><span class="detail-row__value" id="profileEmail">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">Phone</span><span class="detail-row__value" id="profilePhone">—</span></div>
                    </div>
                </section>

                <section class="detail-section">
                    <header class="detail-section__header"><h3>Employment</h3></header>
                    <div class="detail-section__body">
                        <div class="detail-row"><span class="detail-row__label">Position</span><span class="detail-row__value" id="profilePosition">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">BJMP Rank</span><span class="detail-row__value" id="profileBjmpRank">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">Department</span><span class="detail-row__value" id="profileDepartment">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">Date Hired</span><span class="detail-row__value" id="profileDateHired">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">Employment Status</span><span class="detail-row__value" id="profileEmploymentStatus">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">Personnel Type</span><span class="detail-row__value" id="profilePersonnelType">—</span></div>
                        <div class="detail-row"><span class="detail-row__label">Eligibility</span><span class="detail-row__value" id="profileEligibility">—</span></div>
                    </div>
                </section>
            </div>

            <!-- ---------- EDIT MODE ---------- -->
            <form class="dj-modal__body is-hidden" id="profileEditForm" autocomplete="off">
                <fieldset class="user-form-section">
                    <legend>Personal Information</legend>
                    <div class="form-grid-row">
                        <label class="field"><span>First name</span><input type="text" id="profileEditFirstName" required maxlength="100"></label>
                        <label class="field"><span>Middle name</span><input type="text" id="profileEditMiddleName" maxlength="100"></label>
                        <label class="field"><span>Last name</span><input type="text" id="profileEditLastName" required maxlength="100"></label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field"><span>Date of birth</span><input type="date" id="profileEditDob"></label>
                        <label class="field"><span>Sex</span>
                            <select id="profileEditSex">
                                <option value="">—</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </label>
                        <label class="field"><span>Civil status</span>
                            <select id="profileEditCivilStatus">
                                <option value="">—</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                            </select>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Contact</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Email</span>
                            <input type="email" id="profileEditEmail">
                            <span class="field__hint" id="profileEditEmailHint">Optional</span>
                        </label>
                        <label class="field">
                            <span>Phone</span>
                            <input type="tel" id="profileEditPhone">
                            <span class="field__hint" id="profileEditPhoneHint">Optional</span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Change Password</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Current password</span>
                            <input type="password" id="profileEditCurrentPassword" autocomplete="current-password">
                        </label>
                        <label class="field">
                            <span>New password</span>
                            <input type="password" id="profileEditNewPassword" autocomplete="new-password">
                            <span class="field__hint">Leave blank to keep current. Min 8 characters.</span>
                        </label>
                        <label class="field">
                            <span>Confirm new password</span>
                            <input type="password" id="profileEditConfirmPassword" autocomplete="new-password">
                        </label>
                    </div>
                </fieldset>

                <p class="modal__error" id="profileEditError" hidden></p>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="profileCancelBtn">Cancel</button>
                    <button type="submit" class="btn-primary" id="profileSaveBtn">Save Changes</button>
                </div>
            </form>
        </article>

        <!-- ==================================================
             SIDE SUMMARY
             ================================================== -->
        <article class="card">
            <header class="card__header"><h2 class="card__title">Account Summary</h2></header>
            <div style="padding: 1rem;">
                <div class="kpi" style="background: transparent; border: 0; padding: 0; margin-bottom: 1rem;">
                    <span class="kpi__label">Member Since</span>
                    <strong class="kpi__value" id="profileMemberSince" style="font-size: 1rem;">—</strong>
                </div>
                <div class="kpi" style="background: transparent; border: 0; padding: 0; margin-bottom: 1rem;">
                    <span class="kpi__label">Last Updated</span>
                    <strong class="kpi__value" id="profileLastUpdated" style="font-size: 1rem;">—</strong>
                </div>
                <div class="kpi" style="background: transparent; border: 0; padding: 0;">
                    <span class="kpi__label">Account Status</span>
                    <strong class="kpi__value" style="font-size: 1rem;">
                        <span class="status-pill status-pill--on" id="profileStatusPill">Active</span>
                    </strong>
                </div>
            </div>
        </article>
    </div>
</section>