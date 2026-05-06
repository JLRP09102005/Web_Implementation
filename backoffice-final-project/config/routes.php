<?php

/**
 * config/routes_test.php
 *
 * Rutas de prueba SIN controladores reales.
 * Usa closures directamente para verificar que Router y App funcionan.
 * BORRAR cuando los Controllers estén listos.
 */

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;

// -----------------------------------------------------------------------------
// RUTA PÚBLICA — sin autenticación
// -----------------------------------------------------------------------------
$router->get('/test/public', function () {
    echo json_encode([
        'status' => 'ok',
        'ruta'   => '/test/public',
        'acceso' => 'público',
    ]);
});

// -----------------------------------------------------------------------------
// RUTA CON PARÁMETRO — verifica que {id} se captura bien
// -----------------------------------------------------------------------------
$router->get('/test/races/{id}', function (array $params) {
    echo json_encode([
        'status'  => 'ok',
        'ruta'    => '/test/races/{id}',
        'id_recibido' => $params['id'],
    ]);
});

// -----------------------------------------------------------------------------
// RUTA AUTENTICADA — requiere sesión activa (cualquier rol)
// -----------------------------------------------------------------------------
$router->get('/test/auth', function () {
    echo json_encode([
        'status' => 'ok',
        'ruta'   => '/test/auth',
        'acceso' => 'autenticado',
    ]);
}, Router::ROLE_AUTHENTICATED);

// -----------------------------------------------------------------------------
// RUTA DE ADMIN — solo software-administrator o administratorDB
// -----------------------------------------------------------------------------
$router->get('/test/admin', function () {
    echo json_encode([
        'status' => 'ok',
        'ruta'   => '/test/admin',
        'acceso' => 'admin',
    ]);
}, [Router::ROLE_ADMIN, Router::ROLE_ADMIN_DB]);

// -----------------------------------------------------------------------------
// RUTA POST — verifica que el método HTTP se distingue
// -----------------------------------------------------------------------------
$router->post('/test/public', function () {
    echo json_encode([
        'status' => 'ok',
        'ruta'   => 'POST /test/public',
        'body'   => $_POST,
    ]);
});

$router->get('/login', function () {
    require_once dirname(__DIR__) . '/views/auth/login.php';
});

$router->post('/login',  [AuthController::class, 'login'],  Router::ROLE_PUBLIC);
$router->post('/guest-login', [AuthController::class, 'guestLogin'], Router::ROLE_PUBLIC);
$router->post('/logout', [AuthController::class, 'logout'], Router::ROLE_AUTHENTICATED);

$router->get('/dashboard', function () {
    require_once dirname(__DIR__) . '/views/dashboard/dashboard.php';
}, Router::ROLE_AUTHENTICATED);

$router->get('/', function () {
    if (isset($_SESSION['user'])) {
        header('Location: /dashboard');
    } else {
        header('Location: /login');
    }
    exit;
});

$router->get('/api/public/race-calendar', function () {
    header('Content-Type: application/json');

    $container = App\Core\Container::getInstance();
    $pdo = $container->make('db.readonly');

    $stmt = $pdo->query('CALL sp_public_race_calendar()');
    $stmt->execute();
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    echo json_encode($rows);
});

// ── API routes Dashboard ─────────────────────────────────
$router->get('/api/overview',     [DashboardController::class, 'overview'],     Router::ROLE_AUTHENTICATED);
$router->get('/api/pilots',       [DashboardController::class, 'pilots'],       Router::ROLE_AUTHENTICATED);
$router->get('/api/races',        [DashboardController::class, 'races'],        Router::ROLE_AUTHENTICATED);
$router->get('/api/teams',        [DashboardController::class, 'teams'],        Router::ROLE_AUTHENTICATED);
$router->get('/api/vehicles',     [DashboardController::class, 'vehicles'],     Router::ROLE_AUTHENTICATED);
$router->get('/api/penalties',    [DashboardController::class, 'penalties'],    Router::ROLE_AUTHENTICATED);
$router->get('/api/results',      [DashboardController::class, 'results'],      Router::ROLE_AUTHENTICATED);
$router->get('/api/manufacturer', [DashboardController::class, 'manufacturer'], Router::ROLE_AUTHENTICATED);