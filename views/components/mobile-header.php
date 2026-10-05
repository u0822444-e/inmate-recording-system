<?php
declare(strict_types=1);
use App\Services\Auth;

$isAdmin = Auth::isAdmin();
$today   = date('M j, Y');
?>
<header class="mobile-header">
    <button type="button" id="dashboardMenuButton" class="mobile-header__menu"
            aria-label="Open navigation" aria-controls="dashboardSidebar" aria-expanded="false">
        <i class="bi bi-list"></i>
    </button>
    <div class="mobile-header__title">
        <strong><?= $isAdmin ? 'Administrator' : 'Staff / Officer' ?></strong>
        <span>Ipil District Jail</span>
    </div>
    <span class="mobile-header__date"><?= htmlspecialchars($today, ENT_QUOTES) ?></span>
</header>