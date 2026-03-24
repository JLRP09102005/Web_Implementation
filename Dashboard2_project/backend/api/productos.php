<?php
/*
CRUD Products Endpoint
GET -> obtain product information
POST -> create new product regitry
PUT -> update product info
DELETE -> delete product
*/
require_once "../includes/conexion.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METOD"];

try{
    switch($method)
    {
        case "GET":
            if(isset($_GET["id"]))
            {
                $stmt = $pdo->prepare("SELECT id, nombre, precio, stock FROM productos WHERE id = ?");
                $stmt->execute([$_GET["id"]]);
                $products = $stmt->fetch();

                if($products){ echo json_encode($products); }
                else
                {
                    http_response_code(404);
                    echo json_encode(["error" => "Producto no encontrado, el id no existe"]);
                }
            }
            else
            {
                $stmt = $pdo->query("SELECT id, nombre, precio, stock FROM productos ORDER BY nombre ASC");
                $products = $stmt->fetchAll();
                echo json_encode($products);
            }
        break;
        
        case "POST":
            $input = json_decode(file_get_contents("php://input"), true);
        break;
    }
}
catch(){
    
}
?>