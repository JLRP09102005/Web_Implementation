<?php

/**
 * config/routes_test.php
 *
 * Rutas de prueba SIN controladores reales.
 * Usa closures directamente para verificar que Router y App funcionan.
 * BORRAR cuando los Controllers estén listos.
 */

use App\Core\Router;

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

$router->post('/login', function () {
    header('Content-Type: application/json');
    // De momento respuesta dummy para probar el JS
    echo json_encode(['success' => true, 'redirect' => '/test/public']);
});

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

    $stmt = $pdo->query('SELECT iduser, email, role FROM users LIMIT 5');
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'count'  => count($rows),
        'rows'   => $rows
    ]);
});

$router->get('/test/circuits', function () {
    $container = \App\Core\Container::getInstance();
    $pdo = $container->make('db.readonly');

    $stmt = $pdo->query('CALL sp_public_all_circuits()');
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($rows);
});