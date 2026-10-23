<?php
require __DIR__ . '/conexion.php';
 
header('Content-Type: application/json; charset=utf-8');
 
function responder(bool $exito, array $extra = [], int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode(array_merge(['exito' => $exito], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, ['mensaje' => 'Método no permitido.'], 405);
}
 
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    responder(false, ['mensaje' => 'Solicitud inválida.'], 400);
}
 
// ---------- Validación de datos del cliente ----------
$cliente = trim((string)($data['cliente_nombre'] ?? ''));
$mesa    = trim((string)($data['mesa_entrega'] ?? ''));
$metodo  = (string)($data['metodo_pago'] ?? '');
$pago_con = (float)($data['pago_con'] ?? 0);
$items   = $data['items'] ?? null;
 
if ($cliente === '' || $mesa === '') {
    responder(false, ['mensaje' => 'Faltan el nombre o la ubicación de entrega.'], 400);
}
 
$cliente = mb_substr($cliente, 0, 100);
$mesa    = mb_substr($mesa, 0, 255);
 
$metodos_validos = ['Efectivo', 'Tarjeta', 'Transferencia'];
if (!in_array($metodo, $metodos_validos, true)) {
    responder(false, ['mensaje' => 'Método de pago no válido.'], 400);
}
 
if (!is_array($items) || count($items) === 0 || count($items) > 50) {
    responder(false, ['mensaje' => 'El pedido no tiene platillos válidos.'], 400);
}
 
// Agrupar por id de platillo (si viene repetido se suman las cantidades)
$cantidades = [];
foreach ($items as $item) {
    $id   = (int)($item['id'] ?? 0);
    $cant = (int)($item['cantidad'] ?? 0);
    if ($id <= 0 || $cant < 1 || $cant > 99) {
        responder(false, ['mensaje' => 'Hay un platillo con datos inválidos.'], 400);
    }
    $cantidades[$id] = ($cantidades[$id] ?? 0) + $cant;
}
 
// ---------- Recalcular precios con la base de datos (no confiar en el navegador) ----------
$lineas = [];
$total  = 0.0;
 
$stmt_precio = $conexion->prepare("SELECT nombre, precio FROM platillos WHERE id = ?");
foreach ($cantidades as $id => $cant) {
    $stmt_precio->bind_param("i", $id);
    $stmt_precio->execute();
    $platillo = $stmt_precio->get_result()->fetch_assoc();
 
    if (!$platillo) {
        responder(false, ['mensaje' => 'Un platillo de tu carrito ya no está disponible. Actualiza la página.'], 409);
    }
 
    $precio   = (float)$platillo['precio'];
    $subtotal = round($precio * $cant, 2);
    $total   += $subtotal;
 
    $lineas[] = [
        'nombre'   => $platillo['nombre'],
        'cantidad' => $cant,
        'precio'   => $precio,
        'subtotal' => $subtotal,
    ];
}
$total = round($total, 2);
$stmt_precio->close();
 
if ($metodo === 'Efectivo') {
    if ($pago_con + 0.001 < $total) {
        responder(false, ['mensaje' => 'El monto en efectivo es menor al total del pedido.'], 400);
    }
} else {
    $pago_con = 0.0;
}
 
// ---------- Guardar pedido y detalle en una transacción ----------
try {
    $conexion->begin_transaction();
 
    $stmt_pedido = $conexion->prepare(
        "INSERT INTO pedidos (cliente_nombre, mesa_entrega, metodo_pago, pago_con, total, estado)
         VALUES (?, ?, ?, ?, ?, 'Pendiente')"
    );
    $stmt_pedido->bind_param("sssdd", $cliente, $mesa, $metodo, $pago_con, $total);
    $stmt_pedido->execute();
    $pedido_id = $conexion->insert_id;
    $stmt_pedido->close();
 
    $stmt_detalle = $conexion->prepare(
        "INSERT INTO detalle_pedidos (pedido_id, platillo_nombre, cantidad, precio_unitario, subtotal)
         VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($lineas as $l) {
        $stmt_detalle->bind_param("isidd", $pedido_id, $l['nombre'], $l['cantidad'], $l['precio'], $l['subtotal']);
        $stmt_detalle->execute();
    }
    $stmt_detalle->close();
 
    $conexion->commit();
} catch (Throwable $ex) {
    $conexion->rollback();
    error_log("Error al guardar pedido: " . $ex->getMessage());
    responder(false, ['mensaje' => 'No se pudo guardar el pedido. Inténtalo de nuevo.'], 500);
}
 
responder(true, [
    'pedido_id' => $pedido_id,
    'total'     => $total,
    'items'     => $lineas,
]);
 