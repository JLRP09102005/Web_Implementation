<?php 

namespace App\Core;

class Router{

    public const ROLE_PUBLIC = 'readonly-public';
    public const ROLE_ADMIN = 'software-administrator';
    public const ROLE_ADMIN_DB = 'administratorDB';
    public const ROLE_COMISSIONER = 'comissioner-boss';
    public const ROLE_RACE_DIRECTOR = 'race-director';
    public const ROLE_DATA_ANALYST = 'data-analyst';
    public const ROLE_MANUFACTURER_REP = 'manufacturer-representative';
    public const ROLE_MECHANICAL_BOSS = 'mechanical-boss';
    public const ROLE_TEAM_MANAGER = 'team-manager';
    public const ROLE_PILOT = 'pilot';
    public const ROLE_AUTHENTICATED = 'authenticated';

    private array $routes = [];

    //Builds a get array for all the posible accesible uri's
    public function get(string $pattern, array|\Closure $handler, array|string $roles = self::ROLE_PUBLIC): void
    {
        $this->addRoute('GET', $pattern, $handler, $roles);
    }

    public function post(string $pattern, array|\Closure $handler, array|string $roles = self::ROLE_AUTHENTICATED): void 
    {
        $this->addRoute('POST', $pattern, $handler, $roles);
    }

    public function put(string $pattern, array|\Closure $handler, array|string $roles = self::ROLE_AUTHENTICATED): void
    {
        $this->addRoute('PUT', $pattern, $handler, $roles);
    }

    public function delete(string $pattern, array|\Closure $handler, array|string $roles = self::ROLE_PUBLIC): void
    {
        $this->addRoute('DELETE', $pattern, $handler, $roles);
    }

    private function addRoute(string $method, string $pattern, array|\Closure $handler, array|string $roles): void
    {
        [$regex, $params] = $this->compilePattern($pattern);

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $regex,
            'params' => $params,
            'handler' => $handler,
            'roles' => (array) $roles,
        ];
    }

    private function compilePattern(string $pattern): array
    {
        $params = [];

        //This extracts with regular expressions the parameters from the uri without the keys '{}'
        preg_match_all('/\{(\w+)\}/', $pattern, $matches);
        $params = $matches[1];

        $regex = preg_replace_callback('/\{(\w+)\}/', function ($m) {
            $name = $m[1];
            return str_ends_with($name, 'Id') || $name === 'id' ? '(\d+)' : '([^/]+)';
        }, $pattern);

        $regex = '#^' . $regex . '$#';

        return [$regex, $params];
    }

    public function dispatch(?array $sessionUser = null): void
    {
        if (! isset($_SERVER['REQUEST_METHOD'])) { $method = 'GET'; }
        else { $method = $_SERVER['REQUEST_METHOD']; }
        
        $uri = $this->parseUri();

        for($i=0;$i < count($this->routes);$i++)
        {
            $route = $this->routes[$i];

            if ($route['method'] !== $method) { continue; }
            if (!preg_match($route['regex'], $uri, $matches)) { continue; }
            if (!$this->isAuthorized($route['roles'], $sessionUser)) { $this->abort(403, 'Access denied: you dont have permissions for this source'); return; }

            array_shift($matches);
            $urlParams = array_combine($route['params'], $matches) ?: [];

            $this->callHandler($route['handler'], $urlParams);
            return;
        }

        $this->abort(404, 'Route not found');
    }

    private function isAuthorized(array $allowedRoles, ?array $sessionUser): bool
    {
        if (in_array(self::ROLE_PUBLIC, $allowedRoles, true)) { return true; }
        if ($sessionUser === null) { return false; }

        if (! isset($sessionUser['role'])) { $userRole = ''; }
        else { $userRole = $sessionUser['role']; }

        if (in_array($userRole, [self::ROLE_ADMIN, self::ROLE_ADMIN_DB], true)) { return true; }
        if (in_array(self::ROLE_AUTHENTICATED, $allowedRoles, true)) { return true; }

        return in_array($userRole, $allowedRoles, true);
    }

    private function callHandler(array|\Closure $handler, array $urlParams): void
    {
        if ($handler instanceof \Closure) { call_user_func($handler, $urlParams); return; }

        [$controllerClass, $method] = $handler;

        $container = Container::getInstance();
        $controller = $container->make($controllerClass);

        if (!method_exists($controller, $method)) { $this->abort(500, "The method '{$method}' not exists at '{$controllerClass}'."); return; }

        $controller->$method($urlParams);
    }

    private function parseUri(): string
    {
        if (!isset($_SERVER['REQUEST_URI'])) { $uri = '/'; }
        else { $uri = $_SERVER['REQUEST_URI']; }
        
        if (false !== $pos = strpos($uri, '?')) { $uri = substr($uri, 0, $pos); }

        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($basePath !== '' && str_starts_with($uri, $basePath)) { $uri = substr($uri, strlen($basePath)); }

        return '/' . ltrim($uri, '/');
    }

    private function abort(int $code, string $message): void
    {
        http_response_code($code);
        echo json_encode(['error' => $code, 'message' => $message]);
        exit;
    }

}

?>