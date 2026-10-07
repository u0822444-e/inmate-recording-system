<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isAdmin()) return;
?>
<div class="modal-backdrop is-hidden" id="userModal">
    <div class="dj-modal" role="dialog" aria-modal="true" aria-labelledby="userModalTitle">
        <header class="dj-modal__header">
            <h2 id="userModalTitle">New User</h2>
            <button type="button" class="dj-modal__close" data-modal-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <form id="userForm" autocomplete="off" style="display: contents;">
            <div class="dj-modal__body">
                <input type="hidden" name="id" id="userId">

                <label class="field">
                    <span>Full name</span>
                    <input type="text" name="full_name" id="userFullName" required maxlength="150">
                </label>

                <label class="field">
                    <span>Username</span>
                    <input type="text" name="username" id="userUsername" required maxlength="100" minlength="3">
                </label>

                <label class="field">
                    <span>Role</span>
                    <select name="role" id="userRole" required>
                        <option value="Staff / Officer">Staff / Officer</option>
                        <option value="Administrator">Administrator</option>
                    </select>
                </label>

                <label class="field">
                    <span>
                        Password
                        <small id="userPasswordHint" class="field__hint">min 8 characters</small>
                    </span>
                    <input type="password" name="password" id="userPassword" autocomplete="new-password">
                </label>

                <p class="modal__error" id="userFormError" hidden></p>
            </div>

            <footer class="dj-modal__footer">
                <button type="button" class="btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn-primary" id="userSaveBtn">Save</button>
            </footer>
        </form>
    </div>
</div>