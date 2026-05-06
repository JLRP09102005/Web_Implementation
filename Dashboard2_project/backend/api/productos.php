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

            if (!isset($input['nombre']) || !isset($input['precio']) || !isset($input['stock']))
            {
                http_response_code(400);
                echo json_encode(['error' => 'Fields not found']);
                exit;
            }

            $sql = 'INSERT INTO productos (nombre, precio, stock) VALUES (:nombre, :precio, :stock)';

            $stmt = $pdo->prepare($sql);
            $stmt -> execute([':nombre' => $input['nombre'], ':precio' => $input['precio'], ':stock' => $input['stock']]);

            $newID = $pdo->lastInsertId();
            http_response_code(201);
            echo json_decode(['id' => $newID, 'mensaje' => 'Registered product succesfuly.']);
        break;

        case "PUT":
            
            if (!isset($_GET['id']))
            {
                http_response_code(400);
                echo json_encode(['error' => 'Product id requiered']);
                exit;
            }

            $id = $_GET['id'];
            $input = json_decode(file_get_contents('php://input',true));

            $fields = [];
            $parameters = [':id' => $id];

            if (empty($fields))
            {
                http_response_code(400);
                echo json_encode(['mensaje' => 'Not fields sent to update product info']);
                exit;
            }

            if (isset($input['nombre']))
            {
                $fields[] = 'nombre = :nombre';
                $parameters[':nombre'] = $input['nombre'];
            }

            if (isset($input['precio']))
            {
                $fields[] = 'precio = :precio';
                $parameters[':precio'] = $input['precio'];
            }

            if (isset($input['stock']))
            {
                $fields[] = 'stock = :stock';
                $parameters[':stock'] = $input['stock'];
            }

            $sql = 'UPDATE productos SET ' . implode('. ', $fields) . "WHERE id = :id";

        break;
    }
}
catch(\Throwable $e){
    http_response_code(400);
}
?>