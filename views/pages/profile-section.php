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
        <div class="section-header__tools">
            <button type="button" class="btn-secondary btn-sm" id="profileEditToggle">
                <i class="bi bi-pencil"></i> Edit Profile
            </button>
        </div>
    </header>

    <!-- ============================================================
         PROFILE HERO
         ============================================================ -->
    <article class="profile-hero">
        <div class="profile-hero__avatar" id="profileAvatarInitial">—</div>
        <div class="profile-hero__info">
            <h2 id="profileFullName">—</h2>
            <div class="profile-hero__meta">
                <span class="role-badge" id="profileRoleBadge">—</span>
                <span class="meta-text" id="profileRoleLine">—</span>
            </div>
        </div>
        <div class="profile-hero__status">
            <span class="status-pill status-pill--on" id="profileStatusPill">Active</span>
        </div>
    </article>

    <!-- ============================================================
         VIEW MODE
         ============================================================ -->
    <div id="profileViewMode">
        <div class="panel-grid panel-grid--profile">

            <article class="card">
                <header class="card__header">
                    <h2 class="card__title">Account Information</h2>
                </header>
                <div class="detail-section__body" style="padding: 1rem;">
                    <div class="detail-row"><span class="detail-row__label">Username</span><span class="detail-row__value" id="profileUsername">—</span></div>
                    <div class="detail-row"><span class="detail-row__label">Role</span><span class="detail-row__value" id="profileRole">—</span></div>
                    <div class="detail-row"><span class="detail-row__label">Jail Unit</span><span class="detail-row__value" id="profileJailUnit">—</span></div>
                    <div class="detail-row"><span class="detail-row__label">Employee No.</span><span class="detail-row__value" id="profileEmployeeNo">—</span></div>
                </div>
            </article>

            <article class="card">
                <header class="card__header">
                    <h2 class="card__title">Contact</h2>
                </header>
                <div class="detail-section__body" style="padding: 1rem;">
                    <div class="detail-row"><span class="detail-row__label">Email</span><span class="detail-row__value" id="profileEmail">—</span></div>
                    <div class="detail-row"><span class="detail-row__label">Phone</span><span class="detail-row__value" id="profilePhone">—</span></div>
                </div>
            </article>
        </div>

        <div class="panel-grid panel-grid--profile">

            <article class="card">
                <header class="card__header">
                    <h2 class="card__title">Personal Information</h2>
                </header>
                <div class="detail-section__body" style="padding: 1rem;">
                    <div class="detail-row"><span class="detail-row__label">Full Name</span><span class="detail-row__value" id="profilePersonalName">—</span></div>
                    <div class="detail-row"><span class="detail-row__label">Sex</span><span class="detail-row__value" id="profileSex">—</span></div>
                    <div class="detail-row"><span class="detail-row__label">Date of Birth</span><span class="detail-row__value" id="profileDob">—</span></div>
                    <div class="detail-row"><span class="detail-row__label">Civil Status</span><span class="detail-row__value" id="profileCivilStatus">—</span></div>
                </div>
            </article>

            <article class="card">
                <header class="card__header">
                    <h2 class="card__title">Account Summary</h2>
                </header>
                <div class="detail-section__body" style="padding: 1rem;">
                    <div class="detail-row"><span class="detail-row__label">Member Since</span><span class="detail-row__value" id="profileMemberSince">—</span></div>
                    <div class="detail-row"><span class="detail-row__label">Last Updated</span><span class="detail-row__value" id="profileLastUpdated">—</span></div>
                </div>
            </article>
        </div>

        <article class="card">
            <header class="card__header">
                <h2 class="card__title">Employment</h2>
            </header>
            <div class="detail-section__body detail-section__body--three" style="padding: 1rem;">
                <div class="detail-row"><span class="detail-row__label">Position</span><span class="detail-row__value" id="profilePosition">—</span></div>
                <div class="detail-row"><span class="detail-row__label">BJMP Rank</span><span class="detail-row__value" id="profileBjmpRank">—</span></div>
                <div class="detail-row"><span class="detail-row__label">Salary Grade</span><span class="detail-row__value" id="profileSalaryGrade">—</span></div>
                <div class="detail-row"><span class="detail-row__label">Department</span><span class="detail-row__value" id="profileDepartment">—</span></div>
                <div class="detail-row"><span class="detail-row__label">Date Hired</span><span class="detail-row__value" id="profileDateHired">—</span></div>
                <div class="detail-row"><span class="detail-row__label">Employment Status</span><span class="detail-row__value" id="profileEmploymentStatus">—</span></div>
                <div class="detail-row"><span class="detail-row__label">Personnel Type</span><span class="detail-row__value" id="profilePersonnelType">—</span></div>
                <div class="detail-row"><span class="detail-row__label">Eligibility</span><span class="detail-row__value" id="profileEligibility">—</span></div>
            </div>
        </article>
    </div>

    <!-- ============================================================
         EDIT MODE (hidden by default)
         ============================================================ -->
    <form class="is-hidden" id="profileEditForm" autocomplete="off">

        <article class="card">
            <header class="card__header">
                <h2 class="card__title">Personal Information</h2>
            </header>
            <div class="dj-modal__body">
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
            </div>
        </article>

        <article class="card">
            <header class="card__header">
                <h2 class="card__title">Contact</h2>
            </header>
            <div class="dj-modal__body">
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
            </div>
        </article>

        <article class="card">
            <header class="card__header">
                <h2 class="card__title">Change Password</h2>
            </header>
            <div class="dj-modal__body">
                <p class="meta-text" style="margin: 0 0 1rem;">
                    Leave the password fields blank to keep your current password.
                </p>
                <div class="form-grid-row">
                    <label class="field">
                        <span>Current password</span>
                        <input type="password" id="profileEditCurrentPassword" autocomplete="current-password">
                    </label>
                    <label class="field">
                        <span>New password</span>
                        <input type="password" id="profileEditNewPassword" autocomplete="new-password">
                    </label>
                    <label class="field">
                        <span>Confirm new password</span>
                        <input type="password" id="profileEditConfirmPassword" autocomplete="new-password">
                    </label>
                </div>
            </div>
        </article>

        <p class="modal__error" id="profileEditError" hidden></p>

        <div class="profile-edit-actions">
            <button type="button" class="btn-secondary" id="profileCancelBtn">Cancel</button>
            <button type="submit" class="btn-primary" id="profileSaveBtn">
                <i class="bi bi-check-lg"></i> Save Changes
            </button>
        </div>
    </form>
</section>