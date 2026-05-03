<?php 

namespace App\Core;

class Container {
    
    private static ?Container $instance = null;
    private array $bindings = [];
    private array $instances = [];

    ## This function storage another function referred with a key used as an a ID and execute it if was not executed previously
    public function singleton(string $key, callable $factory): void
    {
        $this->bindings[$key] = function () use ($key, $factory){

            if (!isset($this->instances[$key])) 
            {
                $this->instances[$key] = $factory($this);
            }

            return $this->instances[$key];
        };
    }

    public function make(string $key): mixed
    {
        if (!isset($this->bindings[$key])) { throw new \Exception("No registered: {$key}"); }

        return ($this->bindings[$key]($this));
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) { self::$instance = new self(); }

        return self::$instance;
    }

    public function __clone() { throw new \Exception("No clonable Container"); }
    public function __sleep() { throw new \Exception("No serializable Container"); }
    public function __wakeup() { throw new \Exception("No unserializable Container"); }

}

?>