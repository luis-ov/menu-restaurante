<?php
include 'conexion.php';

$mensaje = "";
$platillo_editar = null;

// Cambiar estado de un pedido
if (isset($_GET['cambiar_estado']) && isset($_GET['pedido_id'])) {
    $p_id = (int)$_GET['pedido_id'];
    $nuevo_estado = $conexion->real_escape_string($_GET['cambiar_estado']);
    $conexion->query("UPDATE pedidos SET estado = '$nuevo_estado' WHERE id = $p_id");
    header("Location: admin.php?tab=pedidos");
    exit();
}

// Cargar datos para editar platillo
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $res_edit = $conexion->query("SELECT * FROM platillos WHERE id = $id_editar");
    if ($res_edit->num_rows > 0) {
        $platillo_editar = $res_edit->fetch_assoc();
    }
}

// Procesar formulario de platillos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_platillo'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $descripcion = $conexion->real_escape_string($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $categoria_id = (int)$_POST['categoria_id'];

    if (!empty($nombre) && $precio > 0 && $categoria_id > 0) {
        $imagen_sql = "";
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $nombre_original = basename($_FILES['imagen']['name']);
            $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
            $nombre_imagen = time() . "_" . uniqid() . "." . $extension;
            $ruta_destino = "uploads/" . $nombre_imagen;

            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                $imagen_sql = ", imagen = '$nombre_imagen'";
            }
        }

        if ($id > 0) {
            $sql = "UPDATE platillos SET nombre = '$nombre', descripcion = '$descripcion', precio = $precio, categoria_id = $categoria_id $imagen_sql WHERE id = $id";
            $msg_ok = "¡Platillo actualizado correctamente!";
        } else {
            $img_nombre = !empty($nombre_imagen) ? $nombre_imagen : 'default.jpg';
            $sql = "INSERT INTO platillos (categoria_id, nombre, descripcion, precio, imagen) VALUES ($categoria_id, '$nombre', '$descripcion', $precio, '$img_nombre')";
            $msg_ok = "¡Platillo registrado correctamente!";
        }

        if ($conexion->query($sql) === TRUE) {
            header("Location: admin.php?exito=" . urlencode($msg_ok));
            exit();
        } else {
            $mensaje = "<div class='alerta error'>Error: " . $conexion->error . "</div>";
        }
    }
}

if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    $conexion->query("DELETE FROM platillos WHERE id = $id_eliminar");
    header("Location: admin.php");
    exit();
}

$tab_activa = isset($_GET['tab']) ? $_GET['tab'] : 'platillos';

// Consultas
$categorias = $conexion->query("SELECT * FROM categorias");
$platillos = $conexion->query("SELECT p.id, p.nombre, p.precio, p.imagen, c.nombre AS categoria FROM platillos p JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC");
$pedidos = $conexion->query("SELECT * FROM pedidos ORDER BY id DESC");

// Métricas rápidas
$total_ventas = $conexion->query("SELECT SUM(total) AS total FROM pedidos WHERE estado != 'Cancelado'")->fetch_assoc()['total'] ?? 0;
$total_pedidos = $conexion->query("SELECT COUNT(*) AS total FROM pedidos")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración & Control de Pedidos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #0f172a; color: #f8fafc; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1e293b; padding: 20px 30px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05); }
        .header h1 { font-size: 1.6em; color: #d97706; margin: 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #1e293b; padding: 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05); text-align: center; }
        .stat-card h3 { color: #94a3b8; font-size: 0.85em; text-transform: uppercase; margin-bottom: 5px; }
        .stat-card p { font-size: 1.6em; font-weight: 700; color: #10b981; margin: 0; }
        .nav-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; background: #1e293b; color: #94a3b8; text-decoration: none; border-radius: 8px; font-weight: 600; border: 1px solid rgba(255,255,255,0.05); }
        .tab-btn.active { background: #d97706; color: white; }
        .card-panel { background: #1e293b; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.05); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: #94a3b8; font-size: 0.9em; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; box-sizing: border-box; }
        .btn-guardar { background-color: #d97706; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: left; font-size: 0.9em; }
        th { color: #94a3b8; }
        .img-tabla { width: 45px; height: 45px; object-fit: cover; border-radius: 6px; }
        .badge-estado { padding: 4px 10px; border-radius: 12px; font-size: 0.75em; font-weight: 700; display: inline-block; }
        .estado-Pendiente { background: #f59e0b; color: #000; }
        .estado-Preparacion { background: #3b82f6; color: #fff; }
        .estado-Entregado { background: #10b981; color: #fff; }
        .estado-Cancelado { background: #ef4444; color: #fff; }
        .btn-estado { color: #38bdf8; text-decoration: none; font-size: 0.8em; margin-right: 5px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Control del Restaurante</h1>
        <a href="index.php" style="color: white; background: #10b981; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold;" target="_blank">Ver Menú ↗</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Ventas Totales</h3>
            <p>$<?php echo number_format($total_ventas, 2); ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Pedidos</h3>
            <p style="color: #38bdf8;"><?php echo $total_pedidos; ?></p>
        </div>
    </div>

    <div class="nav-tabs">
        <a href="admin.php?tab=platillos" class="tab-btn <?php echo $tab_activa == 'platillos' ? 'active' : ''; ?>">🍱 Menú & Platillos</a>
        <a href="admin.php?tab=pedidos" class="tab-btn <?php echo $tab_activa == 'pedidos' ? 'active' : ''; ?>">📋 Gestión de Pedidos</a>
    </div>

    <?php if ($tab_activa == 'platillos'): ?>
        <div class="card-panel">
            <h2><?php echo $platillo_editar ? "Editar Platillo" : "Agregar Nuevo Platillo"; ?></h2>
            <form method="POST" action="admin.php" enctype="multipart/form-data">
                <?php if ($platillo_editar): ?>
                    <input type="hidden" name="id" value="<?php echo $platillo_editar['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Nombre del Platillo:*</label>
                    <input type="text" name="nombre" value="<?php echo $platillo_editar ? htmlspecialchars($platillo_editar['nombre']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Categoría:*</label>
                    <select name="categoria_id" required>
                        <option value="">Selecciona categoría</option>
                        <?php 
                        $categorias->data_seek(0);
                        while($cat = $categorias->fetch_assoc()): 
                            $selected = ($platillo_editar && $platillo_editar['categoria_id'] == $cat['id']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($cat['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Precio ($ MXN):*</label>
                    <input type="number" step="0.01" name="precio" value="<?php echo $platillo_editar ? $platillo_editar['precio'] : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Imagen del Platillo:</label>
                    <input type="file" name="imagen" accept="image/*">
                </div>

                <div class="form-group">
                    <label>Descripción:</label>
                    <textarea name="descripcion" rows="2"><?php echo $platillo_editar ? htmlspecialchars($platillo_editar['descripcion']) : ''; ?></textarea>
                </div>

                <button type="submit" name="guardar_platillo" class="btn-guardar">
                    <?php echo $platillo_editar ? "Actualizar Platillo" : "Guardar Platillo"; ?>
                </button>
            </form>
        </div>

        <div class="card-panel">
            <h2>Platillos Registrados</h2>
            <table>
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = $platillos->fetch_assoc()): ?>
                        <tr>
                            <td><img src="uploads/<?php echo $p['imagen']; ?>" class="img-tabla" onerror="this.src='https://via.placeholder.com/45'"></td>
                            <td><strong><?php echo htmlspecialchars($p['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['categoria']); ?></td>
                            <td>$<?php echo number_format($p['precio'], 2); ?></td>
                            <td>
                                <a href="admin.php?editar=<?php echo $p['id']; ?>" style="color: #f59e0b; text-decoration: none; margin-right: 10px;">Editar</a>
                                <a href="admin.php?eliminar=<?php echo $p['id']; ?>" style="color: #ef4444; text-decoration: none;" onclick="return confirm('¿Eliminar?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>

        <div class="card-panel">
            <h2>Pedidos Entrantes</h2>
            <table>
                <thead>
                    <tr>
                        <th># Orden</th>
                        <th>Cliente</th>
                        <th>Ubicación</th>
                        <th>Método Pago</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Detalle</th>
                        <th>Cambiar Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pedidos->num_rows > 0): ?>
                        <?php while($ped = $pedidos->fetch_assoc()): 
                            $ped_id = $ped['id'];
                            $detalles = $conexion->query("SELECT * FROM detalle_pedidos WHERE pedido_id = $ped_id");
                        ?>
                            <tr>
                                <td><strong>#<?php echo $ped['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($ped['cliente_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($ped['mesa_entrega']); ?></td>
                                <td><?php echo htmlspecialchars($ped['metodo_pago']); ?></td>
                                <td style="color: #10b981; font-weight: bold;">$<?php echo number_format($ped['total'], 2); ?></td>
                                <td>
                                    <span class="badge-estado estado-<?php echo str_replace(' ', '', $ped['estado']); ?>">
                                        <?php echo $ped['estado']; ?>
                                    </span>
                                </td>
                                <td>
                                    <ul style="padding-left: 15px; margin: 0; font-size: 0.85em; color: #94a3b8;">
                                        <?php while($d = $detalles->fetch_assoc()): ?>
                                            <li><?php echo $d['cantidad']; ?>x <?php echo htmlspecialchars($d['platillo_nombre']); ?></li>
                                        <?php endwhile; ?>
                                    </ul>
                                </td>
                                <td>
                                    <a href="admin.php?tab=pedidos&pedido_id=<?php echo $ped['id']; ?>&cambiar_estado=En Preparacion" class="btn-estado">▶ Cocina</a> | 
                                    <a href="admin.php?tab=pedidos&pedido_id=<?php echo $ped['id']; ?>&cambiar_estado=Entregado" class="btn-estado" style="color: #10b981;">✔ Entregado</a> | 
                                    <a href="admin.php?tab=pedidos&pedido_id=<?php echo $ped['id']; ?>&cambiar_estado=Cancelado" class="btn-estado" style="color: #ef4444;">✖ Cancelar</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; color: #94a3b8;">No hay pedidos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

</div>

</body>
</html>