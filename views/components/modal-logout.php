<?php
declare(strict_types=1);
?>
<div class="modal-backdrop is-hidden" id="logoutModal">
    <div class="dj-modal" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle">
        <header class="dj-modal__header">
            <h2 id="logoutModalTitle">Sign Out</h2>
            <button type="button" class="dj-modal__close" data-logout-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <div class="dj-modal__body">
            <p class="mb-0">Are you sure you want to sign out?</p>
        </div>

        <footer class="dj-modal__footer">
            <button type="button" class="btn-secondary" data-logout-close>Cancel</button>
            <a href="index.php?route=logout" class="btn-danger" id="logoutConfirmBtn">
                <i class="bi bi-box-arrow-right"></i> Sign out
            </a>
        </footer>
    </div>
</div>