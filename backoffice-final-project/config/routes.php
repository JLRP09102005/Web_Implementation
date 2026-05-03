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