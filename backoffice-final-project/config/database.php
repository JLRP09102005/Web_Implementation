<?php 

use App\Core\DBConnection;
use App\Models\User;
use App\Models\RaceModel;
use App\Models\PilotModel;
use App\Models\TeamModel;
use App\Models\VehicleModel;
use App\Models\PenaltyModel;
use App\Models\ResultModel;
use App\Models\ManufacturerModel;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;

##Check if the variables $container and $env are defined before use them
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

$container->singleton(RaceModel::class,
    fn($c) => new RaceModel($c->make('db.readonly')));

$container->singleton(PilotModel::class,
    fn($c) => new PilotModel($c->make('db.readonly')));

$container->singleton(TeamModel::class,
    fn($c) => new TeamModel($c->make('db.readonly')));

$container->singleton(VehicleModel::class,
    fn($c) => new VehicleModel($c->make('db.readonly')));

$container->singleton(PenaltyModel::class,
    fn($c) => new PenaltyModel($c->make('db.readonly')));

$container->singleton(ResultModel::class,
    fn($c) => new ResultModel($c->make('db.readonly')));

$container->singleton(ManufacturerModel::class,
    fn($c) => new ManufacturerModel($c->make('db.readonly')));

$container->singleton(DashboardController::class,
    fn($c) => new DashboardController(
        $c->make(RaceModel::class),
        $c->make(PilotModel::class),
        $c->make(TeamModel::class),
        $c->make(VehicleModel::class),
        $c->make(PenaltyModel::class),
        $c->make(ResultModel::class),
        $c->make(ManufacturerModel::class),
    )
);

?>