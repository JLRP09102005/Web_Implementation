<?php 

require_once 'autoload.php';

use App\Core\Container;
use App\Core\DBConnection;
use App\Core\Test;

$env = parse_ini_file(__DIR__ . '/../.env');
$container = Container::getInstance();

$container->singleton('db.readonly', function() use ($env){

    return (new DBConnection(
        $env['DB_HOST'], $env['DB_NAME'], $env['DB_USER_DEFAULT'], $env['DB_PASS_DEFAULT'], $env['DB_CHARSET']
    ))->getConnection();

});

$container->singleton(Test::class, function ($c){
    return new Test("Funciona!!!");
});

$test = $container->make(Test::class);

echo $test->hola();

?>