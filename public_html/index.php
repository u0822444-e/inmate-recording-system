<?php
declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Controllers\LoginController;
use App\Controllers\UserController;
use App\Services\Auth;

/* ----------------------------------------------------------
   Start the session FIRST — before any file is required,
   so nothing can accidentally send output before session_start().
   ---------------------------------------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ----------------------------------------------------------
   Bootstrap
   ---------------------------------------------------------- */
require_once __DIR__ . '/../app/helpers/Response.php';
require_once __DIR__ . '/../app/services/Auth.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Dashboard.php';
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';

/* Auth::start() confirms the session is active. */
Auth::start();

/* ----------------------------------------------------------
   Database
   ---------------------------------------------------------- */
$cfg = require __DIR__ . '/../config/database.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name']);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('DB fail: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed.');
}

/* ----------------------------------------------------------
   Routing
   ---------------------------------------------------------- */
$route  = $_GET['route']  ?? 'home';
$action = $_GET['action'] ?? '';

/* --- JSON API (must come before view rendering) --- */
if ($route === 'users') {
    (new UserController($db))->handle($action);
    exit;
}

if ($route === 'dashboard') {
    (new DashboardController($db))->handle($action);
    exit;
}

if ($route === 'login') {
    (new LoginController($db))->handle();
    exit;
}

if ($route === 'logout') {
    Auth::logout();
    header('Location: index.php');
    exit;
}

/* ----------------------------------------------------------
   Views
   ---------------------------------------------------------- */
$isAuthenticated = Auth::isLoggedIn();
$isAdmin         = Auth::isAdmin();
$pageTitle       = 'Ipil District Jail - Inmate Recording System';

require __DIR__ . '/../views/components/header.php';

if ($isAuthenticated) {
    $user = Auth::user();
    require __DIR__ . '/../views/pages/dashboard.php';
} else {
    require __DIR__ . '/../views/pages/auth.php';
}

require __DIR__ . '/../views/components/footer.php';