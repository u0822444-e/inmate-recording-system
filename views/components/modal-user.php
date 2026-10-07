<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isAdmin()) return;
?>
<div class="modal-backdrop is-hidden" id="userModal">
    <div class="dj-modal dj-modal--wide" role="dialog" aria-modal="true" aria-labelledby="userModalTitle">
        <header class="dj-modal__header">
            <h2 id="userModalTitle">New User</h2>
            <button type="button" class="dj-modal__close" data-modal-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <form id="userForm" autocomplete="off" style="display: contents;">
            <div class="dj-modal__body">

                <input type="hidden" name="id" id="userId">

                <!-- Personal -->
                <fieldset class="user-form-section">
                    <legend>Personal Details</legend>

                    <div class="form-grid-row">
                        <label class="field">
                            <span>First name</span>
                            <input type="text" name="first_name" id="userFirstName" required maxlength="100">
                        </label>

                        <label class="field">
                            <span>Middle name</span>
                            <input type="text" name="middle_name" id="userMiddleName" maxlength="100">
                        </label>

                        <label class="field">
                            <span>Last name</span>
                            <input type="text" name="last_name" id="userLastName" required maxlength="100">
                        </label>
                    </div>

                    <div class="form-grid-row">
                        <label class="field">
                            <span>Email</span>
                            <input type="email" name="email" id="userEmail" maxlength="150">
                        </label>

                        <label class="field">
                            <span>Phone</span>
                            <input type="text" name="phone" id="userPhone" maxlength="30">
                        </label>
                    </div>

                    <div class="form-grid-row">
                        <label class="field">
                            <span>Birthdate</span>
                            <input type="date" name="birthdate" id="userBirthdate">
                        </label>

                        <label class="field">
                            <span>Sex</span>
                            <select name="sex" id="userSex">
                                <option value="">—</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </label>

                        <label class="field">
                            <span>Civil status</span>
                            <select name="civil_status" id="userCivilStatus">
                                <option value="">—</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                            </select>
                        </label>
                    </div>
                </fieldset>

                <!-- Employment -->
                <fieldset class="user-form-section">
                    <legend>Employment Details</legend>

                    <div class="form-grid-row">
                        <label class="field">
                            <span>Employee No.</span>
                            <input type="text" name="employee_no" id="userEmployeeNo" maxlength="50">
                        </label>

                        <label class="field">
                            <span>Date hired</span>
                            <input type="date" name="date_hired" id="userDateHired">
                        </label>

                        <label class="field">
                            <span>Employment status</span>
                            <select name="employment_status" id="userEmploymentStatus">
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
                            <span>Position</span>
                            <input type="text" name="position" id="userPosition" maxlength="100">
                        </label>

                        <label class="field">
                            <span>Rank</span>
                            <input type="text" name="rank" id="userRank" maxlength="100">
                        </label>

                        <label class="field">
                            <span>Department / Unit</span>
                            <input type="text" name="department" id="userDepartment" maxlength="100">
                        </label>
                    </div>
                </fieldset>

                <!-- Account -->
                <fieldset class="user-form-section">
                    <legend>Account &amp; Access</legend>

                    <div class="form-grid-row">
                        <label class="field">
                            <span>
                                Username
                            </span>
                            <input type="text" name="username" id="userUsername" maxlength="100"
                                   placeholder="firstname.lastname">
                        </label>

                        <label class="field">
                            <span>Role</span>
                            <select name="role" id="userRole" required>
                                <option value="Staff / Officer">Staff / Officer</option>
                                <option value="Administrator">Administrator</option>
                            </select>
                        </label>
                    </div>

                    <label class="field">
                        <span>
                            Password
                            <small id="userPasswordHint" class="field__hint">min 8 characters</small>
                        </span>
                        <input type="password" name="password" id="userPassword" autocomplete="new-password">
                    </label>
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