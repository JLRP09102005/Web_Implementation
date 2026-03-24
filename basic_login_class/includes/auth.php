<?php
require_once 'database.php';

class Auth
{
    //Verify credentials and create the session if the credential is correct
    //From database.php $username and $password

    public static function login($username, $password)
    {
        //PDO with a Singletone
        $conexion = Database::connection();
    }
}

?>