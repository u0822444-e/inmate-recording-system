<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isAdmin()) return;
?>
<div class="modal-backdrop is-hidden" id="confirmModal">
    <div class="dj-modal" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
        <header class="dj-modal__header">
            <h2 id="confirmModalTitle">Confirm</h2>
            <button type="button" class="dj-modal__close" data-confirm-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <div class="dj-modal__body">
            <p id="confirmModalMessage" class="mb-0">Are you sure?</p>
        </div>

        <footer class="dj-modal__footer">
            <button type="button" class="btn-secondary" data-confirm-close>Cancel</button>
            <button type="button" class="btn-primary" id="confirmModalOk">Confirm</button>
        </footer>
    </div>
</div>