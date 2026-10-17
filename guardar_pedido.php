<?php
include 'conexion.php';

header('Content-Type: application/json');

// Recibir el cuerpo de la petición JSON sent desde JavaScript
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || empty($data['items']) || empty($data['cliente_nombre']) || empty($data['mesa_entrega'])) {
    echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos para procesar la orden.']);
    exit;
}

$cliente = $conexion->real_escape_string($data['cliente_nombre']);
$mesa = $conexion->real_escape_string($data['mesa_entrega']);
$metodo = $conexion->real_escape_string($data['metodo_pago']);
$pago_con = isset($data['pago_con']) ? (float)$data['pago_con'] : 0.00;
$total = (float)$data['total'];
$items = $data['items'];

// 1. Insertar el pedido en la tabla principal
$sql_pedido = "INSERT INTO pedidos (cliente_nombre, mesa_entrega, metodo_pago, pago_con, total, estado) 
               VALUES ('$cliente', '$mesa', '$metodo', $pago_con, $total, 'Pendiente')";

if ($conexion->query($sql_pedido) === TRUE) {
    $pedido_id = $conexion->insert_id;

    // 2. Insertar cada platillo en el detalle del pedido
    foreach ($items as $item) {
        $nombre_p = $conexion->real_escape_string($item['nombre']);
        $cant = (int)$item['cantidad'];
        $precio = (float)$item['precio'];
        $subtotal = $cant * $precio;

        $sql_detalle = "INSERT INTO detalle_pedidos (pedido_id, platillo_nombre, cantidad, precio_unitario, subtotal) 
                        VALUES ($pedido_id, '$nombre_p', $cant, $precio, $subtotal)";
        $conexion->query($sql_detalle);
    }

    echo json_encode(['exito' => true, 'pedido_id' => $pedido_id]);
} else {
    echo json_encode(['exito' => false, 'mensaje' => $conexion->error]);
}
?>