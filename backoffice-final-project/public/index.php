<?php 

require_once 'autoload.php';

use App\Core\Container;

$env = parse_ini_file(__DIR__ . '/../.env');
$container = Container::getInstance();

require __DIR__ . '/../config/database.php'

?>