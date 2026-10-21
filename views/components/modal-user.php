<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isAdmin())
    return;

// Load dropdown options
$__opts = require __DIR__ . '/../../config/user_options.php';
$positionOptions = $__opts['positions'];
$bjmpRankOptions = $__opts['bjmp_ranks'];
?>
<div class="modal-backdrop is-hidden" id="userModal">
    <div class="dj-modal dj-modal--wide" role="dialog" aria-modal="true" aria-labelledby="userModalTitle">
        <header class="dj-modal__header">
            <div class="dj-modal__header-title">
                <h2 id="userModalTitle">New User</h2>
            </div>
            <button type="button" class="dj-modal__close" data-modal-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <form id="userForm" autocomplete="off" style="display: contents;">
            <div class="dj-modal__body">

                <input type="hidden" id="userId">
                <input type="hidden" id="userJailUnitId">

                <fieldset class="user-form-section">
                    <legend>Identification</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>First name</span>
                            <input type="text" id="userFirstName" required maxlength="100">
                        </label>
                        <label class="field">
                            <span>Middle name</span>
                            <input type="text" id="userMiddleName" maxlength="100">
                        </label>
                        <label class="field">
                            <span>Last name</span>
                            <input type="text" id="userLastName" required maxlength="100">
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Username</span>
                            <input type="text" id="userUsername" maxlength="100" autocomplete="off">
                        </label>
                        <label class="field">
                            <span>Employee No.</span>
                            <input type="text" id="userEmployeeNo" maxlength="50">
                        </label>
                        <label class="field">
                            <span>Role</span>
                            <select id="userRole" required>
                                <option value="Staff / Officer">Staff / Officer</option>
                                <option value="Administrator">Administrator</option>
                            </select>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field" style="grid-column: span 3;">
                            <span>Assigned Jail Unit</span>
                            <div id="userJailUnitWrap"></div>
                            <span class="field__hint" id="userJailUnitHint">
                                Staff can only manage records for their assigned unit. Leave blank for system-wide
                                admins.
                            </span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Profile Photo</legend>
                    <div class="avatar-editor" id="userAvatarEditor">
                        <div class="avatar-editor__preview">
                            <img id="userAvatarPreview" src="" alt="" hidden>
                            <div id="userAvatarInitials" class="avatar-editor__initials">?</div>
                        </div>
                        <div class="avatar-editor__controls">
                            <label class="btn-secondary btn-sm" for="userAvatarInput">
                                <i class="bi bi-image"></i> Choose image
                            </label>
                            <input type="file" id="userAvatarInput" accept="image/jpeg,image/png,image/webp" hidden>
                            <button type="button" class="btn-secondary btn-sm" id="userAvatarRemove" hidden>
                                <i class="bi bi-trash"></i> Remove
                            </button>
                            <span class="field__hint">JPG, PNG, or WebP. Max 2 MB.</span>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Contact</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Email</span>
                            <input type="email" id="userEmail" autocomplete="off">
                            <span class="field__hint" id="userEmailHint">Optional</span>
                        </label>
                        <label class="field">
                            <span>Phone</span>
                            <input type="tel" id="userPhone" autocomplete="off">
                            <span class="field__hint" id="userPhoneHint">Optional</span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Personal</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Date of birth</span>
                            <input type="date" id="userBirthdate">
                        </label>
                        <label class="field">
                            <span>Sex</span>
                            <select id="userSex">
                                <option value="">—</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Civil status</span>
                            <select id="userCivilStatus">
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
                    <legend>Employment</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Position</span>
                            <select id="userPosition">
                                <option value="">— Select position —</option>
                                <?php foreach ($positionOptions as $pos): ?>
                                    <option value="<?= htmlspecialchars($pos, ENT_QUOTES) ?>">
                                        <?= htmlspecialchars($pos, ENT_QUOTES) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>BJMP Rank</span>
                            <select id="userBjmpRank">
                                <option value="">— Select rank —</option>
                                <?php foreach ($bjmpRankOptions as $rank): ?>
                                    <option value="<?= htmlspecialchars($rank, ENT_QUOTES) ?>">
                                        <?= htmlspecialchars($rank, ENT_QUOTES) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Department</span>
                            <input type="text" id="userDepartment" maxlength="100">
                        </label>
                        <label class="field">
                            <span>Date hired</span>
                            <input type="date" id="userDateHired">
                        </label>
                        <label class="field">
                            <span>Employment status</span>
                            <select id="userEmploymentStatus">
                                <option value="">—</option>
                                <option value="Regular">Regular</option>
                                <option value="Probationary">Probationary</option>
                                <option value="Contractual">Contractual</option>
                                <option value="Casual">Casual</option>
                                <option value="Job Order">Job Order</option>
                            </select>
                        </label>
                    </div>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Personnel Type</span>
                            <select id="userPersonnelType">
                                <option value="">—</option>
                                <option value="Uniformed">Uniformed</option>
                                <option value="Non-Uniformed">Non-Uniformed</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Eligibility</span>
                            <input type="text" id="userEligibility" maxlength="200">
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Security</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Password</span>
                            <input type="password" id="userPassword" autocomplete="new-password">
                            <span class="field__hint" id="userPasswordHint">min 8 characters</span>
                        </label>
                        <label class="field">
                            <span>Confirm password</span>
                            <input type="password" id="userPasswordConfirm" autocomplete="new-password">
                            <span class="field__hint" id="userPasswordConfirmHint">retype password</span>
                        </label>
                    </div>
                </fieldset>

                <p class="modal__error" id="userFormError" hidden></p>
            </div>

            <footer class="dj-modal__footer">
                <button type="button" class="btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn-primary" id="userSaveBtn">Save</button>
            </footer>
        </form>
    </div>
</div>