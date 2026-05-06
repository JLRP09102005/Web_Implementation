<?php

namespace App\Models;

abstract class BaseModel{
    
    public function __construct(protected \PDO $pdo) {}
    public function __clone() { throw new \Exception("No clonable BaseModel"); }
    public function __sleep() { throw new \Exception("No serializable BaseModel"); }
    public function __wakeup() { throw new \Exception("No unserializable BaseModel"); }

    protected function call(string $sp, array $params = []): array
    {
        $ph = $params ? implode(',', array_fill(0, count($params), '?')) : '';
        $stmt = $this->pdo->prepare("CALL {$sp}(" . $ph . ")");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $rows;
    }

    protected function callOne(string $sp, array $params = []): array
    {
        return $this->call($sp, $params)[0] ?? [];
    }

}

?>