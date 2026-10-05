<?php
declare(strict_types=1);
use App\Services\Auth;
if (!Auth::isAdmin()) return;
?>
<section id="dashboard-users" class="dashboard-section">
    <header class="section-header">
        <div>
            <h1>Users</h1>
            <p>Manage administrator and staff accounts.</p>
        </div>
        <button type="button" class="btn-primary btn-sm" id="userCreateBtn">
            <i class="bi bi-plus-lg"></i> New User
        </button>
    </header>

    <article class="panel">
        <div class="panel__toolbar">
            <input type="search" id="userSearch" class="input-search" placeholder="Search by name or username...">
            <select id="userRoleFilter" class="input-select">
                <option value="">All roles</option>
                <option value="Administrator">Administrator</option>
                <option value="Staff / Officer">Staff / Officer</option>
            </select>
            <select id="userStatusFilter" class="input-select">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th><th>Username</th><th>Role</th>
                        <th>Status</th><th>Created</th>
                        <th class="ta-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <tr><td colspan="6" class="data-table__empty">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </article>
</section>