<?php 

use App\Core\DBConnection;

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

?>