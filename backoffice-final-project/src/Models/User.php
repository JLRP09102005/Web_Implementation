<?php 

namespace App\Models;

class User{

    public function __construct(private \PDO $pdo, private array $allowedDomains) {}

    public function __clone() { throw new \Exception("No clonable User"); }
    public function __sleep() { throw new \Exception("No serializable User"); }
    public function __wakeup() { throw new \Exception("No unserializable User"); }

    public function findByEmail(string $email): ?array{

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { return null; }

        $domain = explode('@', $email)[1];
        if (!in_array($domain, $this->allowedDomains)) { return null; }


        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $result = $stmt->fetch();

        if (!isset($result)) { return null; }
        return $result;
    }

    public function findById(int $id): ?array{

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if (!isset($result)) { return null; }
        return $result;
    }

}

?>