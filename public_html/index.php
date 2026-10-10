<?php
declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Controllers\InmateController;
use App\Controllers\JailUnitController;
use App\Controllers\LoginController;
use App\Controllers\ReferenceController;
use App\Controllers\ReportController;
use App\Controllers\UserController;
use App\Controllers\ProfileController;
use App\Services\Auth;

/* ----------------------------------------------------------
   Start the session FIRST
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
require_once __DIR__ . '/../app/models/Reference.php';
require_once __DIR__ . '/../app/models/JailUnit.php';
require_once __DIR__ . '/../app/models/Report.php';
require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/ReferenceController.php';
require_once __DIR__ . '/../app/controllers/InmateController.php';
require_once __DIR__ . '/../app/controllers/JailUnitController.php';
require_once __DIR__ . '/../app/controllers/ReportController.php';
require_once __DIR__ . '/../app/controllers/ProfileController.php';

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

if ($route === 'users') {
    (new UserController($db))->handle($action);
    exit;
}

if ($route === 'dashboard') {
    (new DashboardController($db))->handle($action);
    exit;
}

if ($route === 'reference') {
    (new ReferenceController($db))->handle($action);
    exit;
}

if ($route === 'inmates') {
    (new InmateController($db))->handle($action);
    exit;
}

if ($route === 'jail-units') {
    (new JailUnitController($db))->handle($action);
    exit;
}

if ($route === 'report') {
    (new ReportController($db))->handle($action);
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

if ($route === 'profile') {
    (new ProfileController($db))->handle($action);
    exit;
}

/* ----------------------------------------------------------
   Views
   ---------------------------------------------------------- */
$isAuthenticated = Auth::isLoggedIn();
$isAdmin         = Auth::isAdmin();
$pageTitle       = 'Ipil District Jail - Inmate Recording System';

require_once __DIR__ . '/../views/components/header.php';

if ($isAuthenticated) {
    $user = Auth::user();
    require_once __DIR__ . '/../views/pages/dashboard.php';
} else {
    require_once __DIR__ . '/../views/pages/auth.php';
}

require_once __DIR__ . '/../views/components/footer.php';