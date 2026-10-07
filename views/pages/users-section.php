<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isAdmin()) return;

$isUsersActive = ($currentSection ?? '') === 'users';
?>
<section id="dashboard-users"
         class="dashboard-section <?= $isUsersActive ? 'is-visible' : '' ?>">

    <header class="section-header">
        <div>
            <h1>Users</h1>
            <p>Manage administrator and staff accounts.</p>
        </div>
        <div class="section-header__tools">
            <button type="button" class="btn-secondary btn-sm" id="userArchiveToggleBtn">
                <i class="bi bi-archive"></i>
                <span id="userArchiveToggleLabel">View Archived</span>
            </button>
            <button type="button" class="btn-primary btn-sm" id="userCreateBtn">
                <i class="bi bi-plus-lg"></i> New User
            </button>
        </div>
    </header>

    <div class="card card--compact" style="padding: 0;">
        <div class="panel__toolbar">
            <div class="input-wrap">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search"
                       id="userSearch"
                       class="input-search"
                       placeholder="Search by name, username, or employee no."
                       aria-label="Search users">
            </div>

            <select id="userRoleFilter" class="input-select" aria-label="Filter by role">
                <option value="">All roles</option>
                <option value="Administrator">Administrator</option>
                <option value="Staff / Officer">Staff / Officer</option>
            </select>

            <select id="userStatusFilter" class="input-select" aria-label="Filter by status">
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
                        <th>Employee No.</th>
                        <th>Position</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th id="userDateColHeader">Created</th>
                        <th class="ta-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <tr>
                        <td colspan="7" class="data-table__empty">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>