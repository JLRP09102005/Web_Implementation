<?php
require_once 'config.php';
 
class Database {
    private static $conexion = null;
    
    public static function connection() {
        if (self::$conexion === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$conexion = new PDO($dsn, DB_USER, DB_PASS);
                self::$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $e) {
                die("Error BD: " . $e->getMessage());
            }
        }
        return self::$conexion;
    }
}
?>