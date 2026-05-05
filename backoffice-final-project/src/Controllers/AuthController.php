<?php 

namespace App\Controllers;

use App\Core\Container;

class AuthController{
    
    private function __construct() {}

    public function __clone() { throw new \Exception("No clonable AuthController"); }
    public function __sleep() { throw new \Exception("No serializable AuthController"); }
    public function __wakeup() { throw new \Exception("No unserializable AuthController"); }

    public function login(array $urlParams): void
    {
        header('Content-Type: application/json');

        $body = json_decode(file_get_contents('php://input'), true);

        if (!empty($body['email'])) { $email = $body['email']; }
        else 
        {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            return;
        }
        if (!empty($body['password'])) { $pass = $body['password']; }
        else
        {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Password is required"]);
            return;
        }

        $container = Container::getInstance();
        $pdo = $container->make('db.readonly');

        $stmt = $pdo->prepare('CALL sp_auth_login(?)');
        $stmt->execute([$email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row || !password_verify($pass, $row['password_hash'])) 
        {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            return;
        }
        
        $_SESSION['user'] = [
            'id' => $row['id_user'],
            'username' => $row['username'],
            'email' => $row['email'],
            'role' => $row['role'],
            'team_id' => $row['team_id'],
        ];

        http_response_code(200);
        echo json_encode(['success'  => true, 'redirect' => '/dashboard']);
    }

    public function logout(array $urlParams): void
    {
        header('Content-Type: application/json');
     
        session_unset();
        session_destroy();
        if (ini_get('session.use_cookies'))
        {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'redirect' => '/login']);
    }

}

?>