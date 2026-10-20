<?php
// Conexión a la base de datos (ajusta tus credenciales si es necesario)
$host = "localhost";
$user = "root";
$password = "";
$database = "menu_restaurante";

$conexion = new mysqli($host, $user, $password, $database);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
$conexion->set_charset("utf8");

// Obtener filtros de categoría y búsqueda
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$busqueda = isset($_GET['busqueda']) ? $conexion->real_escape_string($_GET['busqueda']) : '';
$detalle_id = isset($_GET['detalle']) ? intval($_GET['detalle']) : 0;

// Consulta de categorías para los botones de filtro
$sql_cat = "SELECT DISTINCT categoria FROM platillos ORDER BY categoria ASC";
$resultado_cat = $conexion->query($sql_cat);

// Construcción de la consulta principal con orden alfabético ASC por nombre
$sql = "SELECT * FROM platillos WHERE 1=1";
if ($categoria_filtro != '') {
    $sql .= " AND categoria = '" . $conexion->real_escape_string($categoria_filtro) . "'";
}
if ($busqueda != '') {
    $sql .= " AND (nombre LIKE '%$busqueda%' OR descripcion LIKE '%$busqueda%')";
}
// ORDEN ALFABÉTICO OBLIGATORIO (A-Z)
$sql .= " ORDER BY nombre ASC";

$resultado = $conexion->query($sql);

// Si se seleccionó un platillo para ver a detalle
$platillo_detalle = null;
$recomendaciones = [];
if ($detalle_id > 0) {
    $sql_det = "SELECT * FROM platillos WHERE id = $detalle_id";
    $res_det = $conexion->query($sql_det);
    if ($res_det->num_rows > 0) {
        $platillo_detalle = $res_det->fetch_assoc();
        // Obtener recomendaciones de la misma categoría (excluyendo el actual)
        $cat_actual = $conexion->real_escape_string($platillo_detalle['categoria']);
        $sql_rec = "SELECT * FROM platillos WHERE categoria = '$cat_actual' AND id != $detalle_id ORDER BY RAND() LIMIT 4";
        $recomendaciones = $conexion->query($sql_rec);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Interactivo - Restaurante</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --accent-color: #d97706;
            --accent-hover: #b45309;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --green-price: #10b981;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(rgba(15, 23, 42, 0.88), rgba(15, 23, 42, 0.95)), 
                        url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed;
            background-size: cover;
            color: var(--text-main);
            padding-bottom: 80px;
        }

        .header { 
            text-align: center; 
            padding: 40px 20px 20px; 
            background: rgba(30, 41, 59, 0.7);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            backdrop-filter: blur(6px);
        }

        .header h1 { font-size: 2.5em; font-weight: 700; color: var(--accent-color); }
        .header p { color: var(--text-muted); font-size: 1em; margin-top: 5px; }

        .filtros-container { 
            display: flex; flex-direction: column; align-items: center; gap: 15px; margin: 25px auto; max-width: 900px; padding: 0 20px;
        }

        .busqueda-form { display: flex; gap: 10px; width: 100%; max-width: 500px; }
        .busqueda-input { 
            flex: 1; padding: 10px 15px; background-color: var(--card-bg); border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 4px; color: var(--text-main); outline: none; font-size: 0.9em;
        }
        .busqueda-input:focus { border-color: var(--accent-color); }
        .btn-buscar { padding: 10px 20px; background-color: var(--accent-color); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-buscar:hover { background-color: var(--accent-hover); }

        .categorias-buttons { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
        .btn-filtro { 
            padding: 6px 16px; background-color: var(--card-bg); color: var(--text-muted); text-decoration: none; 
            border-radius: 4px; font-size: 0.85em; font-weight: 500; border: 1px solid rgba(255,255,255,0.05);
        }
        .btn-filtro:hover, .btn-filtro.activo { background-color: var(--accent-color); color: white; }

        .menu-container { 
            display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; max-width: 1200px; margin: 0 auto; padding: 0 20px; 
        }

        .card { 
            background: var(--card-bg); border-radius: 4px; overflow: hidden; box-shadow: 0 8px 20px rgba(0,0,0,0.4); 
            display: flex; flex-direction: column; border: 1px solid rgba(255,255,255,0.08); transition: 0.2s; text-decoration: none; color: inherit;
        }
        .card:hover { transform: translateY(-4px); border-color: var(--accent-color); }

        .card-img-container { position: relative; width: 100%; height: 160px; }
        .card-img { width: 100%; height: 100%; object-fit: cover; }
        .categoria-badge { 
            position: absolute; top: 10px; left: 10px; background-color: rgba(15, 23, 42, 0.9); 
            color: var(--accent-color); padding: 3px 8px; border-radius: 2px; font-size: 0.7em; font-weight: 700; text-transform: uppercase; 
        }

        .card-content { padding: 15px; display: flex; flex-direction: column; flex-grow: 1; }
        .card-title { font-size: 1.1em; font-weight: 600; margin-bottom: 6px; }
        .card-desc { color: var(--text-muted); font-size: 0.85em; line-height: 1.4; margin-bottom: 12px; flex-grow: 1; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px; }
        .precio { color: var(--green-price); font-weight: 700; font-size: 1.2em; }

        /* Vista de Detalle (Modal / Pantalla Completa de Platillo) */
        .detalle-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px); display: flex; justify-content: center; align-items: center; z-index: 200; padding: 20px;
        }
        .detalle-modal {
            background: var(--card-bg); width: 100%; max-width: 650px; border-radius: 6px; overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1); max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 40px rgba(0,0,0,0.7);
        }
        .detalle-img { width: 100%; height: 280px; object-fit: cover; }
        .detalle-body { padding: 25px; }
        .detalle-title { font-size: 1.8em; font-weight: 700; color: var(--accent-color); margin-bottom: 10px; }
        .detalle-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0; background: rgba(15,23,42,0.4); padding: 12px; border-radius: 4px; }
        .detalle-info-item span { display: block; font-size: 0.75em; color: var(--text-muted); text-transform: uppercase; }
        .detalle-info-item p { font-size: 1em; font-weight: 600; color: var(--text-main); }
        .btn-cerrar-detalle {
            display: inline-block; margin-top: 20px; background: #475569; color: white; padding: 10px 20px;
            border-radius: 4px; text-decoration: none; font-weight: 600; text-align: center; width: 100%;
        }
        .btn-cerrar-detalle:hover { background: #334155; }

        /* Sección de recomendaciones dentro del detalle */
        .recomendaciones-section { margin-top: 25px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px; }
        .recomendaciones-section h3 { font-size: 1.1em; margin-bottom: 15px; color: var(--text-muted); }
        .recomendaciones-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
        .rec-card { background: rgba(15,23,42,0.5); border-radius: 4px; overflow: hidden; text-decoration: none; color: inherit; border: 1px solid rgba(255,255,255,0.05); transition: 0.2s; }
        .rec-card:hover { border-color: var(--accent-color); }
        .rec-img { width: 100%; height: 90px; object-fit: cover; }
        .rec-info { padding: 8px; }
        .rec-title { font-size: 0.85em; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rec-precio { font-size: 0.8em; color: var(--green-price); font-weight: 700; }
    </style>
</head>
<body>

    <div class="header">
        <h1>🍽️ Nuestro Menú</h1>
        <p>Explora nuestros platillos ordenados alfabéticamente. Haz clic en cualquiera para ver más detalles.</p>
    </div>

    <div class="filtros-container">
        <form class="busqueda-form" method="GET" action="index.php">
            <input type="text" name="busqueda" class="busqueda-input" placeholder="Buscar platillo o ingrediente..." value="<?php echo htmlspecialchars($busqueda); ?>">
            <?php if($categoria_filtro != ''): ?>
                <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoria_filtro); ?>">
            <?php endif; ?>
            <button type="submit" class="btn-buscar">Buscar</button>
        </form>

        <div class="categorias-buttons">
            <a href="index.php" class="btn-filtro <?php echo ($categoria_filtro == '') ? 'activo' : ''; ?>">Todos (A-Z)</a>
            <?php while($cat = $resultado_cat->fetch_assoc()): ?>
                <a href="index.php?categoria=<?php echo urlencode($cat['categoria']); ?>" class="btn-filtro <?php echo ($categoria_filtro == $cat['categoria']) ? 'activo' : ''; ?>">
                    <?php echo htmlspecialchars($cat['categoria']); ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- LISTADO DE PLATILLOS (ORDENADOS ALFABÉTICAMENTE) -->
    <div class="menu-container">
        <?php if ($resultado->num_rows > 0): ?>
            <?php while($row = $resultado->fetch_assoc()): ?>
                <a href="index.php?detalle=<?php echo $row['id']; ?><?php echo $categoria_filtro ? '&categoria='.urlencode($categoria_filtro) : ''; ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?>" class="card">
                    <div class="card-img-container">
                        <img src="<?php echo htmlspecialchars($row['imagen']); ?>" alt="<?php echo htmlspecialchars($row['nombre']); ?>" class="card-img">
                        <span class="categoria-badge"><?php echo htmlspecialchars($row['categoria']); ?></span>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?php echo htmlspecialchars($row['nombre']); ?></h3>
                        <p class="card-desc"><?php echo htmlspecialchars(substr($row['descripcion'], 0, 75)); ?>...</p>
                        <div class="card-footer">
                            <span class="precio">$<?php echo number_format($row['precio'], 2); ?></span>
                            <span style="font-size: 0.8em; color: var(--accent-color); font-weight: 600;">Ver detalles +</span>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px;">No se encontraron platillos disponibles.</p>
        <?php endif; ?>
    </div>

    <!-- VENTANA DETALLE DE PLATILLO (MODAL) -->
    <?php if ($platillo_detalle): ?>
    <div class="detalle-overlay">
        <div class="detalle-modal">
            <img src="<?php echo htmlspecialchars($platillo_detalle['imagen']); ?>" alt="<?php echo htmlspecialchars($platillo_detalle['nombre']); ?>" class="detalle-img">
            <div class="detalle-body">
                <span class="categoria-badge" style="position:static; display:inline-block; margin-bottom:8px;"><?php echo htmlspecialchars($platillo_detalle['categoria']); ?></span>
                <h2 class="detalle-title"><?php echo htmlspecialchars($platillo_detalle['nombre']); ?></h2>
                <p style="color: var(--text-muted); font-size: 0.95em; line-height: 1.5; margin-bottom: 15px;"><?php echo htmlspecialchars($platillo_detalle['descripcion']); ?></p>
                
                <div class="detalle-info-grid">
                    <div class="detalle-info-item">
                        <span>Ingredientes principales</span>
                        <p><?php echo isset($platillo_detalle['ingredientes']) ? htmlspecialchars($platillo_detalle['ingredientes']) : 'No especificados'; ?></p>
                    </div>
                    <div class="detalle-info-item">
                        <span>Tiempo estimado</span>
                        <p>⏱️ <?php echo isset($platillo_detalle['tiempo_preparacion']) ? htmlspecialchars($platillo_detalle['tiempo_preparacion']) : '15-20'; ?> mins</p>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                    <span class="precio" style="font-size: 1.6em;">$<?php echo number_format($platillo_detalle['precio'], 2); ?></span>
                </div>

                <!-- RECOMENDACIONES DE LA CATEGORÍA -->
                <?php if ($recomendaciones && $recomendaciones->num_rows > 0): ?>
                <div class="recomendaciones-section">
                    <h3>💡 Te recomendamos de la categoría "<?php echo htmlspecialchars($platillo_detalle['categoria']); ?>"</h3>
                    <div class="recomendaciones-grid">
                        <?php while($rec = $recomendaciones->fetch_assoc()): ?>
                            <a href="index.php?detalle=<?php echo $rec['id']; ?>" class="rec-card">
                                <img src="<?php echo htmlspecialchars($rec['imagen']); ?>" alt="" class="rec-img">
                                <div class="rec-info">
                                    <div class="rec-title"><?php echo htmlspecialchars($rec['nombre']); ?></div>
                                    <div class="rec-precio">$<?php echo number_format($rec['precio'], 2); ?></div>
                                </div>
                            </a>
                        <?endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>

                <a href="index.php<?php echo $categoria_filtro ? '?categoria='.urlencode($categoria_filtro) : ''; ?>" class="btn-cerrar-detalle">Cerrar detalle</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>