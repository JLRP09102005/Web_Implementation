<?php 

namespace App\Core;

class App{

    private static ?App $instance = null;

    private Container $container;
    private Router $router;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (!isset($instance)) { $instance = new App(); }
        
        return $instance;
    }

    public function __clone() { throw new \Exception("No clonable Container"); }
    public function __sleep() { throw new \Exception("No serializable Container"); }
    public function __wakeup() { throw new \Exception("No unserializable Container"); }

    public function boot(Container $container): void
    {
        $this->container = $container;
        $this->router = new Router();

        $this->container->singleton(Router::class, fn() => $this->router);

        $router = $this->router;
        require_once dirname(__DIR__, 2) . '/config/routes.php';

        $this->startSession();

        if (!isset($_SESSION['user'])) { $sessionUser = null; }
        else { $sessionUser = $_SESSION['user']; }
        
        $this->router->dispatch($sessionUser);
    }

    private function startSession(): void
    {
        if (session_start() === PHP_SESSION_ACTIVE) { return; }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            // 'secure' => isset($_SERVER['HTTPS']),
            // 'httponly' => true,
            'samesite' => 'Strict'
        ]);

        session_start();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') { session_regenerate_id(true); }
    }

    public function getContainer(): Container { return $this->container; }
    public function getRouter(): Router { return $this->router; }

}

?>