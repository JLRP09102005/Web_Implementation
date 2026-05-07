<?php
// BORRAR DESPUÉS DE USARLO
require_once dirname(__DIR__) . '/src/autoload.php';
$pdo = App\Core\Container::getInstance()->make('db');

$username = 'jose';
$email    = 'test@gmail.com';
$password = '123';
$role     = 'software-administrator'; // o el rol que necesites

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
$stmt->execute([$username, $email, $hash, $role]);
echo "Usuario creado: $username / $password";

?>