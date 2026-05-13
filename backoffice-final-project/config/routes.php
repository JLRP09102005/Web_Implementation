<?php

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;

// ── Raíz ─────────────────────────────────────────────────────
$router->get('/', function () {
    if (isset($_SESSION['user'])) {
        header('Location: /dashboard');
    } else {
        header('Location: /login');
    }
    exit;
});

// ── Auth ─────────────────────────────────────────────────────
$router->get('/login', function () {
    require_once dirname(__DIR__) . '/views/auth/login.php';
});

$router->post('/login',       [AuthController::class, 'login'],       Router::ROLE_PUBLIC);
$router->post('/guest-login', [AuthController::class, 'guestLogin'],  Router::ROLE_PUBLIC);
$router->post('/logout',      [AuthController::class, 'logout'],      Router::ROLE_AUTHENTICATED);

// ── Dashboard (vista) ─────────────────────────────────────────
$router->get('/dashboard', function () {
    require_once dirname(__DIR__) . '/views/dashboard/dashboard.php';
}, Router::ROLE_AUTHENTICATED);

// ── API pública ───────────────────────────────────────────────
$router->get('/api/public/race-calendar', function () {
    header('Content-Type: application/json');
    $pdo  = App\Core\Container::getInstance()->make('db.readonly');
    $stmt = $pdo->query('CALL sp_public_race_calendar()');
    echo json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));
});

// ── API Dashboard ─────────────────────────────────────────────
$router->get('/api/overview',     [DashboardController::class, 'overview'],     Router::ROLE_AUTHENTICATED);
$router->get('/api/pilots',       [DashboardController::class, 'pilots'],       Router::ROLE_AUTHENTICATED);
$router->get('/api/races',        [DashboardController::class, 'races'],        Router::ROLE_AUTHENTICATED);
$router->get('/api/teams',        [DashboardController::class, 'teams'],        Router::ROLE_AUTHENTICATED);
$router->get('/api/vehicles',     [DashboardController::class, 'vehicles'],     Router::ROLE_AUTHENTICATED);
$router->get('/api/penalties',    [DashboardController::class, 'penalties'],    Router::ROLE_AUTHENTICATED);
$router->get('/api/results',      [DashboardController::class, 'results'],      Router::ROLE_AUTHENTICATED);
$router->get('/api/inscriptions', [DashboardController::class, 'inscriptions'], Router::ROLE_AUTHENTICATED);
$router->get('/api/manufacturer', [DashboardController::class, 'manufacturer'], Router::ROLE_AUTHENTICATED);
$router->get('/api/stats',        [DashboardController::class, 'stats'],        Router::ROLE_AUTHENTICATED);
$router->get('/api/admin/list',   [DashboardController::class, 'adminList'],    Router::ROLE_AUTHENTICATED);
$router->post('/api/admin/crud',  [DashboardController::class, 'adminCrud'],    Router::ROLE_AUTHENTICATED);
$router->post('/api/admin/reveal-hash', [DashboardController::class, 'revealHash'], Router::ROLE_AUTHENTICATED);