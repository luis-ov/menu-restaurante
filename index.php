<?php
include 'conexion.php';

// Consultar platillos junto con su categoría
$query = "SELECT p.nombre, p.descripcion, p.precio, c.nombre AS categoria 
          FROM platillos p 
          JOIN categorias c ON p.categoria_id = c.id";
$resultado = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Interactivo</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; }
        .header { text-align: center; padding: 20px; background-color: #2c3e50; color: white; border-radius: 8px; margin-bottom: 20px; }
        .menu-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
        .card { background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 280px; padding: 20px; text-align: left; position: relative; }
        .categoria { background-color: #e74c3c; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; text-transform: uppercase; display: inline-block; }
        .precio { color: #27ae60; font-weight: bold; font-size: 1.3em; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Menú del Restaurante</h1>
        <p>Consulta nuestras especialidades</p>
    </div>

    <div class="menu-container">
        <?php if ($resultado->num_rows > 0): ?>
            <?php while($platillo = $resultado->fetch_assoc()): ?>
                <div class="card">
                    <span class="categoria"><?php echo htmlspecialchars($platillo['categoria']); ?></span>
                    <h3><?php echo htmlspecialchars($platillo['nombre']); ?></h3>
                    <p><?php echo htmlspecialchars($platillo['descripcion']); ?></p>
                    <div class="precio">$<?php echo number_format($platillo['precio'], 2); ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No hay platillos disponibles en el menú.</p>
        <?php endif; ?>
    </div>

</body>
</html>