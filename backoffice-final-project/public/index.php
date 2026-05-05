<?php 

ini_set('display_errors', '0');
ini_set('displat_startup_errors', '0');
error_reporting(0);

require_once __DIR__ . '/autoload.php';

use App\Core\Container;
use App\Core\App;

$env = parse_ini_file(__DIR__ . '/../.env');
$container = Container::getInstance();

require __DIR__ . '/../config/database.php';

App::getInstance()->boot($container);

?>