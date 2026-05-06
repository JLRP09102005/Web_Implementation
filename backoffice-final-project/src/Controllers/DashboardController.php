<?php 

namespace App\Controllers;

class DashboardController {

    public function __construct() {}
    public function __clone() { throw new \Exception("No clonable DashboardController"); }
    public function __sleep() { throw new \Exception("No serializable DashboardController"); }
    public function __wakeup() { throw new \Exception("No unserializable DashboardController"); }

    private function requireAuth(): void
    {
        if (empty($_SESSION['user']))
        {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 401, 'message' => 'No authenticated']);
            exit;
        }
    }

}

?>