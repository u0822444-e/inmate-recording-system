<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isLoggedIn() || !Auth::isAdmin()) return;

$isJailUnitsActive = ($currentSection ?? '') === 'jail-units';
?>
<section id="dashboard-jail-units"
         class="dashboard-section <?= $isJailUnitsActive ? 'is-visible' : '' ?>">

    <header class="section-header">
        <div>
            <h1>Jail Units</h1>
            <p>Manage jail facilities under the province.</p>
        </div>
        <div class="section-header__tools">
            <button type="button" class="btn-primary btn-sm" id="jailUnitCreateBtn">
                <i class="bi bi-plus-lg"></i> New Jail Unit
            </button>
        </div>
    </header>

    <div class="card card--compact" style="padding: 0;">

        <div class="panel__toolbar">
            <div class="input-wrap">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="jailUnitSearch" class="input-search"
                       placeholder="Search by name or municipality" aria-label="Search jail units">
            </div>

            <select id="jailUnitTypeFilter" class="input-select" aria-label="Filter by type">
                <option value="">All types</option>
                <option value="District">District</option>
                <option value="Municipal">Municipal</option>
                <option value="City">City</option>
            </select>

            <select id="jailUnitStatusFilter" class="input-select" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Dormitory</th>
                        <th>Municipality</th>
                        <th>Warden</th>
                        <th>Status</th>
                        <th class="ta-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="jailUnitTableBody">
                    <tr>
                        <td colspan="7" class="data-table__empty">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</section>

<!-- ============================================================
     JAIL UNIT MODAL
     ============================================================ -->
<div class="modal-backdrop is-hidden" id="jailUnitModal">
    <div class="dj-modal" role="dialog" aria-modal="true" aria-labelledby="jailUnitModalTitle">
        <header class="dj-modal__header">
            <div class="dj-modal__header-title">
                <h2 id="jailUnitModalTitle">New Jail Unit</h2>
            </div>
            <button type="button" class="dj-modal__close" data-jail-unit-close aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <form id="jailUnitForm" autocomplete="off" style="display: contents;">
            <div class="dj-modal__body">

                <input type="hidden" id="jailUnitId">

                <fieldset class="user-form-section">
                    <legend>Identification</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Jail Unit Name</span>
                            <input type="text" id="jailUnitName" required maxlength="150"
                                   placeholder="e.g. Ipil District Jail">
                        </label>
                        <label class="field">
                            <span>Type</span>
                            <select id="jailUnitType">
                                <option value="Municipal">Municipal</option>
                                <option value="District">District</option>
                                <option value="City">City</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Dormitory</span>
                            <select id="jailUnitDormitory">
                                <option value="Combined">Combined</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Location &amp; Personnel</legend>
                    <div class="form-grid-row">
                        <label class="field">
                            <span>Municipality</span>
                            <input type="text" id="jailUnitMunicipality" maxlength="100"
                                   placeholder="e.g. Ipil">
                        </label>
                        <label class="field">
                            <span>Province</span>
                            <input type="text" id="jailUnitProvince" maxlength="100"
                                   value="Zamboanga Sibugay">
                        </label>
                        <label class="field">
                            <span>Warden</span>
                            <input type="text" id="jailUnitWarden" maxlength="150"
                                   placeholder="Full name of the warden">
                        </label>
                    </div>
                </fieldset>

                <fieldset class="user-form-section">
                    <legend>Status</legend>
                    <label class="field field-checkbox">
                        <input type="checkbox" id="jailUnitIsActive" value="1" checked>
                        <span>Active — available for new records</span>
                    </label>
                </fieldset>

                <p class="modal__error" id="jailUnitFormError" hidden></p>
            </div>

            <footer class="dj-modal__footer">
                <button type="button" class="btn-secondary" data-jail-unit-close>Cancel</button>
                <button type="submit" class="btn-primary" id="jailUnitSaveBtn">Save</button>
            </footer>
        </form>
    </div>
</div>