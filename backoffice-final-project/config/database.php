<?php 

use App\Core\DBConnection;
use App\Models\User;
use App\Controllers\AuthController;

##Check if the variables $container and $env are defined before use it
if (!isset($container) || !isset($env)) { throw new \Exception("database.php require \$container \$env previously defined"); }

$container->singleton('db.readonly', function() use ($env){
    return (new DBConnection(
        $env['DB_HOST'], $env['DB_NAME'], $env['DB_USER_READONLY'], $env['DB_PASS_READONLY'], $env['DB_CHARSET']
    ))->getConnection();
});

$container->singleton('db.admin', function() use ($env){
    return (new DBConnection(
        $env['DB_HOST'], $env['DB_NAME'], $env['DB_USER_ADMIN'], $env['DB_PASS_ADMIN'], $env['DB_CHARSET']
    ))->getConnection();
});

$container->singleton(User::class, function($c) use ($env){
    $domains = explode(',', $env['ALLOWED_DOMAINS']);
    return new User($c->make('db.readonly'), $domains);
});

$container->singleton(AuthController::class, function($c) use ($env){
    return new AuthController();
});

?>