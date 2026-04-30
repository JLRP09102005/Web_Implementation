<?php 

## This namespace is used for the autoload.php file
namespace App\Core;

class DBConnection {
    private \PDO $pdo;

    public function __construct(string $host, string $db, string $user, string $pass, string $charset)
    {
        $dsn = "mysql:host={$host}:dbname={$db}:charset={$charset}";

        try
        {
            $this->pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch(\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    private function __clone() { throw new \Exception("No clonable DBConnection"); }
    public function __wakeup() { throw new \Exception("No serializable DBConnection"); }

    public function getConnection(): \PDO { return $this->pdo; }
}

?>