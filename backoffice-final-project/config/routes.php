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
    require_once dirname(__DIR__) . '/views/dashboard.php';
}, Router::ROLE_AUTHENTICATED);

$router->get('/test/db', function () {
    $container = \App\Core\Container::getInstance();
    try {
        $pdo = $container->make('db.readonly');
        $stmt = $pdo->query('SELECT 1 AS ok');
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'ok', 'db' => 'conectado', 'result' => $row]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

$router->get('/test/users', function () {
    $container = \App\Core\Container::getInstance();
    $pdo = $container->make('db.readonly');

    $stmt = $pdo->query('SELECT id_user, email FROM users LIMIT 5');
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'count'  => count($rows),
        'rows'   => $rows
    ]);
});

$router->get('/test/racecalendar', function () {
    $container = \App\Core\Container::getInstance();
    $pdo = $container->make('db.readonly');

    $stmt = $pdo->query('CALL sp_public_race_calendar');
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($rows);
});