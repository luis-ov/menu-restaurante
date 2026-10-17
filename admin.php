<?php
include 'conexion.php';

$mensaje = "";
$platillo_editar = null;

// 1. Cargar datos si se hace clic en "Editar"
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $res_edit = $conexion->query("SELECT * FROM platillos WHERE id = $id_editar");
    if ($res_edit->num_rows > 0) {
        $platillo_editar = $res_edit->fetch_assoc();
    }
}

// 2. Procesar el formulario cuando se envía (Agregar o Actualizar)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_platillo'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $descripcion = $conexion->real_escape_string($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $categoria_id = (int)$_POST['categoria_id'];

    if (!empty($nombre) && $precio > 0 && $categoria_id > 0) {
        
        // Manejo de la subida de imagen
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
            // Actualizar platillo existente
            $sql = "UPDATE platillos 
                    SET nombre = '$nombre', descripcion = '$descripcion', precio = $precio, categoria_id = $categoria_id $imagen_sql 
                    WHERE id = $id";
            $msg_ok = "¡Platillo actualizado correctamente!";
        } else {
            // Agregar nuevo platillo
            $img_nombre = !empty($nombre_imagen) ? $nombre_imagen : 'default.jpg';
            $sql = "INSERT INTO platillos (categoria_id, nombre, descripcion, precio, imagen) 
                    VALUES ($categoria_id, '$nombre', '$descripcion', $precio, '$img_nombre')";
            $msg_ok = "¡Platillo registrado correctamente!";
        }

        if ($conexion->query($sql) === TRUE) {
            header("Location: admin.php?exito=" . urlencode($msg_ok));
            exit();
        } else {
            $mensaje = "<div class='alerta error'>Error: " . $conexion->error . "</div>";
        }
    } else {
        $mensaje = "<div class='alerta error'>Por favor completa todos los campos requeridos.</div>";
    }
}

// Mensaje de éxito tras redirección
if (isset($_GET['exito'])) {
    $mensaje = "<div class='alerta exito'>" . htmlspecialchars($_GET['exito']) . "</div>";
}

// Eliminar platillo
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    $conexion->query("DELETE FROM platillos WHERE id = $id_eliminar");
    header("Location: admin.php");
    exit();
}

$categorias = $conexion->query("SELECT * FROM categorias");
$platillos = $conexion->query("SELECT p.id, p.nombre, p.precio, p.imagen, c.nombre AS categoria FROM platillos p JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración del Menú</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #2c3e50; color: white; padding: 15px 25px; border-radius: 8px; margin-bottom: 20px; }
        .card-panel { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn-guardar { background-color: #2980b9; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: bold; width: 100%; }
        .btn-cancelar { display: inline-block; text-align: center; background-color: #7f8c8d; color: white; padding: 10px; border-radius: 5px; text-decoration: none; font-weight: bold; width: 100%; box-sizing: border-radius; margin-top: 10px; }
        .alerta { padding: 12px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; }
        .alerta.exito { background-color: #d4edda; color: #155724; }
        .alerta.error { background-color: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        .img-tabla { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        .btn-editar { background-color: #f39c12; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.85em; margin-right: 5px; }
        .btn-eliminar { background-color: #e74c3c; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.85em; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Panel de Administración</h1>
        <a href="index.php" style="color: #27ae60; background: white; padding: 8px 12px; border-radius: 5px; text-decoration: none; font-weight: bold;" target="_blank">Ver Menú ↗</a>
    </div>

    <?php echo $mensaje; ?>

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
                <label>Imagen del Platillo: <?php echo $platillo_editar ? "(Opcional: Selecciona una para cambiar la actual)" : ""; ?></label>
                <input type="file" name="imagen" accept="image/*">
            </div>

            <div class="form-group">
                <label>Descripción:</label>
                <textarea name="descripcion" rows="3"><?php echo $platillo_editar ? htmlspecialchars($platillo_editar['descripcion']) : ''; ?></textarea>
            </div>

            <button type="submit" name="guardar_platillo" class="btn-guardar">
                <?php echo $platillo_editar ? "Actualizar Platillo" : "Guardar Platillo"; ?>
            </button>
            
            <?php if ($platillo_editar): ?>
                <a href="admin.php" class="btn-cancelar">Cancelar Edición</a>
            <?php endif; ?>
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
                        <td>
                            <img src="uploads/<?php echo $p['imagen']; ?>" class="img-tabla" onerror="this.src='https://via.placeholder.com/50?text=Sin+Foto'">
                        </td>
                        <td><strong><?php echo htmlspecialchars($p['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($p['categoria']); ?></td>
                        <td>$<?php echo number_format($p['precio'], 2); ?></td>
                        <td>
                            <a href="admin.php?editar=<?php echo $p['id']; ?>" class="btn-editar">Editar</a>
                            <a href="admin.php?eliminar=<?php echo $p['id']; ?>" class="btn-eliminar" onclick="return confirm('¿Deseas eliminar este platillo?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>