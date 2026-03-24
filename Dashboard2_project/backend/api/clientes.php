<?php
// ENDPOINT to obtain the client list with JSON
// Method to get the list: GET

require_once "../includes/conexion.php";

header("Content-Type: application/json");

try{
    $sql = '
    SELECT 
        id, 
        nombre, 
        email 
    FROM clientes 
    ORDER BY nombre ASC';

    $stmt = $pdo->query($sql);
    $clients = $stmt->fetchAll();

    // Send data as JSON
    echo json_encode($cliente);
}
catch(PDOException $e){
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener clientes " . $e->getMessage()]);
}

?>