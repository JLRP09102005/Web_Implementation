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

$container->singleton(AuthController::class, function($c) use ($env){
    return new AuthController();
});

$container->singleton(RaceModel::class, function($c) use ($env){
    return new RaceModel($c->make('db.readonly'));
});

$container->singleton(PilotModel::class, function($c) use ($env){
    return new PilotModel($c->make('db.readonly'));
});

$container->singleton(TeamModel::class, function($c) use ($env){
    return new TeamModel($c->make('db.readonly'));
});

$container->singleton(VehicleModel::class, function($c) use ($env){
    return new VehicleModel($c->make('db.readonly'));
});

$container->singleton(PenaltyModel::class, function($c) use ($env){
    return new PenaltyModel($c->make('db.readonly'));
});

$container->singleton(ResultModel::class, function($c) use ($env){
    return new ManufacturerModel($c->make('db.readonly'));
});

$container->singleton(DashboardController::class, function($c) use ($env){
    return new DashboardController(
        $c->make(RaceModel::class),
        $c->make(PilotModel::class),
        $c->make(TeamModel::class),
        $c->make(VehicleModel::class),
        $c->make(PenaltyModel::class),
        $c->make(ResultModel::class),
        $c->make(ManufacturerModel::class)
    );
});

?>