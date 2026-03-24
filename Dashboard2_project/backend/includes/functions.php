<?php
require_once "conexion.php";

//CRUD
function ObtenerClientes($pdo)
{
    $query="
        SELECT 
            id, 
            nombre, 
            email 
        FROM clientes 
        ORDER BY nombre
    ";
    $stmt = $pdo->query();

    return $stmt->fetchAll();
}

function ObtenerPedidos($pdo, $estado=null)
{
    $query="
        SELECT 
            p.id, 
            p.fecha, 
            p.estado, 
            c.nombre AS cliente_nombre, 
            GROUP_CONCAT(CONCAT(prod.nombre, ' (' pp.cantidad, ')') SEPARATOR ', ') AS Productos 
        FROM pedidos p 
        INNER JOIN clientes c ON c.id_cliente = p.id_cliente 
        LEFT JOIN pedido_producto pp ON p.id = pp.id_producto 
        LEFT JOIN productos prod ON pp.id_producto = prod.id;
    ";

    if($estado && in_array($estado, ['pendiente','en_camino','entregado']))
    {
        $query .= " WHERE p.estado = :estado";
    }

    $query .= " GROUP BY p.id ORDER BY p.fecha DESC";

    $stmt = $pdo->prepare($query);

    if($estado)
    {
        $stmt->execute([':estado' => $estado]);       
    }
    else
    {
        $stmt->execute();
    }

    return $stmt->fetchAll();
}

?>