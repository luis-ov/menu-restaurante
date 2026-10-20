<?php
include 'conexion.php';

// Obtener todas las categorías
$query_cat = "SELECT * FROM categorias";
$resultado_cat = $conexion->query($query_cat);

// Capturar parámetros de filtrado, búsqueda y detalle
$categoria_id = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$busqueda = isset($_GET['buscar']) ?$conexion->real_escape_string($_GET['buscar']) : '';$detalle_id = isset($_GET['detalle']) ? (int)$_GET['detalle'] : 0;

// Consulta dinámica de platillos ordenada alfabéticamente (A-Z)
$query = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.imagen, p.categoria_id, c.nombre AS categoria 
          FROM platillos p 
          JOIN categorias c ON p.categoria_id = c.id WHERE 1=1";

if ($categoria_id > 0) {$query .= " AND p.categoria_id = $categoria_id";
}

if (!empty($busqueda)) {$query .= " AND (p.nombre LIKE '%$busqueda\%' OR p.descripcion LIKE '\%$busqueda%')";
}

$query .= " ORDER BY p.nombre ASC";
$resultado = $conexion->query($query);

// Consulta para el Modal de Detalle Estilo DoorDash
$platillo_detalle = null;
$recomendaciones = [];
if ($detalle_id > 0) {
    $res_det =$conexion->query("SELECT p.*, c.nombre AS categoria FROM platillos p JOIN categorias c ON p.categoria_id = c.id WHERE p.id = $detalle_id");
    if ($res_det &&$res_det->num_rows > 0) {
        $platillo_detalle =$res_det->fetch_assoc();
        $cat_actual_id = (int)$platillo_detalle['categoria_id'];
        $recomendaciones =$conexion->query("SELECT p.*, c.nombre AS categoria FROM platillos p JOIN categorias c ON p.categoria_id = c.id WHERE p.categoria_id = $cat_actual_id AND p.id !=$detalle_id ORDER BY RAND() LIMIT 4");
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabor & Tradición - Menú Gourmet</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --bg-main: #f8fafc;
        --card-bg: #ffffff;
        --border-color: #e2e8f0;
        --text-primary: #0f172a;
        --text-secondary: #64748b;
        --brand-red: #eb1700; /* Color distintivo estilo DoorDash */
        --brand-hover: #c41300;
        --green-price: #16a34a;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body { 
        font-family: 'Inter', sans-serif; 
        background-color: var(--bg-main);
        color: var(--text-primary);
        padding-bottom: 90px;
    }

    /* Banner Principal Estilo Storefront DoorDash */
    .hero-banner {
        width: 100%;
        height: 220px;
        background: url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
        position: relative;
    }

    .store-header-container {
        max-width: 1100px;
        margin: -50px auto 20px;
        padding: 0 20px;
        position: relative;
        z-index: 10;
    }

    .store-card-info {
        background: var(--card-bg);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: 1px solid var(--border-color);
    }

    .store-title {
        font-size: 2em;
        font-weight: 700;
        color: var(--text-primary);
    }

    .store-subtitle {
        color: var(--text-secondary);
        font-size: 0.95em;
        margin-top: 4px;
    }

    .store-badges {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 15px;
        flex-wrap: wrap;
    }

    .badge-time {
        background-color: #f1f5f9;
        border: 1px solid var(--border-color);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: 600;
        color: var(--text-primary);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* Buscador y Categorías */
    .filtros-wrapper {
        max-width: 1100px;
        margin: 20px auto;
        padding: 0 20px;
    }

    .busqueda-form { 
        display: flex; 
        gap: 10px; 
        width: 100%; 
        max-width: 600px; 
        margin-bottom: 20px;
    }

    .busqueda-input { 
        flex: 1; 
        padding: 12px 18px; 
        background-color: var(--card-bg);
        border: 1px solid var(--border-color); 
        border-radius: 8px; 
        color: var(--text-primary);
        font-size: 0.95em;
        outline: none;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

    .busqueda-input:focus { border-color: var(--brand-red); }

    .btn-buscar { 
        padding: 12px 24px; 
        background-color: var(--brand-red); 
        color: white; 
        border: none; 
        border-radius: 8px; 
        cursor: pointer; 
        font-weight: 600;
        transition: 0.2s;
    }

    .btn-buscar:hover { background-color: var(--brand-hover); }

    .categorias-buttons { 
        display: flex; 
        gap: 10px; 
        overflow-x: auto;
        padding-bottom: 10px;
        scrollbar-width: thin;
    }

    .btn-filtro { 
        padding: 8px 18px; 
        background-color: var(--card-bg); 
        color: var(--text-secondary); 
        text-decoration: none; 
        border-radius: 20px; 
        font-size: 0.9em;
        font-weight: 500; 
        white-space: nowrap;
        border: 1px solid var(--border-color);
        transition: 0.2s;
    }

    .btn-filtro:hover, .btn-filtro.activo { 
        background-color: var(--text-primary); 
        color: white; 
        border-color: var(--text-primary);
    }

    /* Menú de Platillos */
    .section-title {
        max-width: 1100px;
        margin: 30px auto 15px;
        padding: 0 20px;
        font-size: 1.4em;
        font-weight: 700;
    }

    .menu-container { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
        gap: 20px; 
        max-width: 1100px; 
        margin: 0 auto; 
        padding: 0 20px; 
    }

    /* Tarjetas estilo DoorDash */
    .card { 
        background: var(--card-bg); 
        border-radius: 12px; 
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04); 
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        border: 1px solid var(--border-color);
    }

    .card:hover { 
        transform: translateY(-3px); 
        box-shadow: 0 8px 20px rgba(0,0,0,0.08); 
    }

    .card-img-container {
        position: relative;
        width: 100%;
        height: 180px;
        cursor: pointer;
    }

    .card-img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
    }

    .categoria-badge { 
        position: absolute;
        top: 10px;
        left: 10px;
        background-color: rgba(255, 255, 255, 0.95); 
        color: var(--text-primary); 
        padding: 4px 10px; 
        border-radius: 6px; 
        font-size: 0.75em; 
        font-weight: 700;
        text-transform: uppercase; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .card-content {
        padding: 16px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .card-title { font-size: 1.1em; font-weight: 600; margin-bottom: 6px; color: var(--text-primary); cursor: pointer; }
    .card-desc { color: var(--text-secondary); font-size: 0.85em; line-height: 1.4; margin-bottom: 12px; flex-grow: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
    }

    .precio { color: var(--text-primary); font-weight: 700; font-size: 1.15em; }

    .btn-add-cart {
        background-color: #f1f5f9;
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        padding: 8px 14px;
        border-radius: 20px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
        font-size: 0.85em;
    }

    .btn-add-cart:hover { 
        background-color: var(--brand-red); 
        color: white; 
        border-color: var(--brand-red);
    }

    /* Modal Limpio de Detalle (Estilo DoorDash) */
    .detalle-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6);
        backdrop-filter: blur(3px); display: flex; justify-content: center; align-items: center; z-index: 200; padding: 20px;
    }
    .detalle-modal {
        background: var(--card-bg); width: 100%; max-width: 650px; border-radius: 16px; overflow: hidden;
        border: 1px solid var(--border-color); max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.15);
    }
    .detalle-img { width: 100%; height: 280px; object-fit: cover; }
    .detalle-body { padding: 25px; }
    .detalle-title { font-size: 1.8em; font-weight: 700; color: var(--text-primary); margin-bottom: 8px; }
    
    .detalle-info-grid { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 12px; 
        margin: 15px 0; 
        background: #f8fafc; 
        padding: 14px; 
        border-radius: 8px; 
        border: 1px solid var(--border-color);
    }
    .detalle-info-item span { display: block; font-size: 0.75em; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; }
    .detalle-info-item p { font-size: 0.95em; font-weight: 600; color: var(--text-primary); margin-top: 2px; }

    .btn-cerrar-detalle {
        display: block; margin-top: 15px; background: #f1f5f9; color: var(--text-primary); padding: 12px 20px;
        border-radius: 8px; text-decoration: none; font-weight: 600; text-align: center; width: 100%;
    }

    .recomendaciones-section { margin-top: 25px; border-top: 1px solid var(--border-color); padding-top: 20px; }
    .recomendaciones-section h3 { font-size: 1em; margin-bottom: 15px; color: var(--text-primary); font-weight: 700; }
    .recomendaciones-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; }
    .rec-card { background: #fff; border-radius: 8px; overflow: hidden; text-decoration: none; color: inherit; border: 1px solid var(--border-color); transition: 0.2s; }
    .rec-card:hover { border-color: var(--brand-red); transform: translateY(-2px); }
    .rec-img { width: 100%; height: 90px; object-fit: cover; }
    .rec-info { padding: 8px; }
    .rec-title { font-size: 0.85em; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-primary); }
    .rec-precio { font-size: 0.8em; color: var(--text-primary); font-weight: 700; }

    /* Botón Flotante del Carrito Estilo DoorDash */
    .cart-float-btn {
        position: fixed;
        bottom: 25px;
        right: 25px;
        background-color: var(--brand-red);
        color: white;
        padding: 14px 24px;
        border-radius: 30px;
        box-shadow: 0 10px 25px rgba(235, 23, 0, 0.3);
        cursor: pointer;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 99;
        transition: 0.2s;
    }

    .cart-float-btn:hover { transform: scale(1.03); background-color: var(--brand-hover); }

    .cart-count {
        background-color: white;
        color: var(--brand-red);
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 0.85em;
        font-weight: 800;
    }

    /* Panel Lateral del Carrito */
    .cart-modal {
        position: fixed;
        top: 0;
        right: -420px;
        width: 100%;
        max-width: 400px;
        height: 100%;
        background-color: var(--card-bg);
        box-shadow: -5px 0 25px rgba(0,0,0,0.1);
        z-index: 100;
        transition: right 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .cart-modal.open { right: 0; }

    .cart-header {
        padding: 20px;
        background-color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
    }

    .cart-close-btn {
        background: none;
        border: none;
        color: var(--text-secondary);
        font-size: 1.5em;
        cursor: pointer;
    }

    .cart-items {
        flex-grow: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    .cart-item-info h4 { font-size: 0.95em; margin-bottom: 4px; color: var(--text-primary); }
    .cart-item-info p { color: var(--text-primary); font-weight: 700; font-size: 0.85em; }

    .cart-controls { display: flex; align-items: center; gap: 8px; }
    .btn-qty {
        background-color: #fff;
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        width: 28px;
        height: 28px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
    }

    .cart-footer {
        padding: 20px;
        background-color: #fff;
        border-top: 1px solid var(--border-color);
    }

    .cart-total-row {
        display: flex;
        justify-content: space-between;
        font-size: 1.2em;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .btn-checkout {
        width: 100%;
        padding: 14px;
        background-color: var(--brand-red);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: 1em;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-checkout:hover { background-color: var(--brand-hover); }

    .cart-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(2px);
        display: none;
        z-index: 98;
    }
    .cart-overlay.show { display: block; }

    /* Modal Checkout */
    .checkout-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.9);
        width: 90%;
        max-width: 500px;
        background-color: var(--card-bg);
        border-radius: 12px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        z-index: 101;
        padding: 25px;
        display: none;
        opacity: 0;
        transition: all 0.3s ease;
        border: 1px solid var(--border-color);
        max-height: 90vh;
        overflow-y: auto;
    }

    .checkout-modal.active {
        display: block;
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }

    .checkout-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 10px;
    }

    .form-group-pay { margin-bottom: 15px; }

    .form-group-pay label {
        display: block;
        font-size: 0.88em;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }

    .form-group-pay input, .form-group-pay select, .form-group-pay textarea {
        width: 100%;
        padding: 10px 14px;
        background-color: #fff;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-primary);
        font-size: 0.95em;
        outline: none;
        font-family: inherit;
    }

    .form-group-pay input:focus, .form-group-pay select:focus, .form-group-pay textarea:focus { 
        border-color: var(--brand-red); 
    }

    .tiempo-box {
        background: #fef2f2;
        border: 1px solid #fca5a5;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-confirm-pay {
        width: 100%;
        padding: 12px;
        background-color: var(--brand-red);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        margin-top: 10px;
        transition: 0.2s;
    }

    .btn-confirm-pay:hover { background-color: var(--brand-hover); }

    .ticket-box { text-align: center; padding: 10px; }
    .ticket-icon { font-size: 3em; color: var(--green-price); margin-bottom: 10px; }
</style>
</head>
<body>

    <!-- Hero Banner Panorámico Estilo DoorDash -->
    <div class="hero-banner"></div>

    <!-- Encabezado con Información del Restaurante -->
    <div class="store-header-container">
        <div class="store-card-info">
            <h1 class="store-title">SABOR & TRADICIÓN</h1>
            <p class="store-subtitle">Experiencia Gastronómica & Alta Cocina</p>
            
            <div class="store-badges">
                <span class="badge-time">⏱️ 15 - 25 min</span>
                <span class="badge-time">⭐ 4.9 (500+ reseñas)</span>
                <span class="badge-time">🛍️ Entrega & Recoger</span>
            </div>
        </div>
    </div>

    <!-- Buscador y Categorías -->
    <div class="filtros-wrapper">
        <form method="GET" action="index.php" class="busqueda-form">
            <?php if ($categoria_id > 0): ?>
                <input type="hidden" name="categoria" value="<?php echo $categoria_id; ?>">
            <?php endif; ?>
            <input type="text" name="buscar" class="busqueda-input" placeholder="Buscar platillo o ingrediente..." value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" class="btn-buscar">Buscar</button>
        </form>

        <div class="categorias-buttons">
            <a href="index.php" class="btn-filtro <?php echo $categoria_id == 0 ? 'activo' : ''; ?>">Todos (A-Z)</a>
            <?php while($cat =$resultado_cat->fetch_assoc()): ?>
                <a href="index.php?categoria=<?php echo $cat['id']; ?><?php echo !empty($busqueda) ? '&buscar='.urlencode($busqueda) : ''; ?>" 
                   class="btn-filtro <?php echo $categoria_id ==$cat['id'] ? 'activo' : ''; ?>">
                    <?php echo htmlspecialchars($cat['nombre']); ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <h2 class="section-title">Platillos Destacados</h2>

    <!-- Grid de Platillos en A-Z -->
    <div class="menu-container">
        <?php if ($resultado->num_rows > 0): ?>
            <?php while($platillo =$resultado->fetch_assoc()): ?>
                <div class="card">
                    <div class="card-img-container" onclick="location.href='index.php?detalle=<?php echo $platillo['id']; ?><?php echo $categoria_id ? '&categoria='.$categoria_id : ''; ?>';">
                        <img src="uploads/<?php echo $platillo['imagen']; ?>" class="card-img" onerror="this.src='https://via.placeholder.com/280x180/f1f5f9/0f172a?text=Sabor+%26+Tradici%C3%B3n'">
                        <span class="categoria-badge"><?php echo htmlspecialchars($platillo['categoria']); ?></span>
                    </div>
                    
                    <div class="card-content">
                        <h3 class="card-title" onclick="location.href='index.php?detalle=<?php echo $platillo['id']; ?><?php echo $categoria_id ? '&categoria='.$categoria_id : ''; ?>';"><?php echo htmlspecialchars($platillo['nombre']); ?></h3>
                        <p class="card-desc"><?php echo htmlspecialchars($platillo['descripcion']); ?></p>
                        
                        <div class="card-footer">
                            <div class="precio">$<?php echo number_format($platillo['precio'], 2); ?></div>
                            <button class="btn-add-cart" onclick="agregarAlCarrito(<?php echo $platillo['id']; ?>, '<?php echo addslashes($platillo['nombre']); ?>', <?php echo$platillo['precio']; ?>)">+ Agregar</button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; grid-column: 1/-1; padding: 40px; color: var(--text-secondary);">
                <h3>No encontramos platillos disponibles en esta categoría.</h3>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL DETALLE DE PLATILLO (Estilo DoorDash) -->
    <?php if ($platillo_detalle): ?>
    <div class="detalle-overlay">
        <div class="detalle-modal">
            <img src="uploads/<?php echo $platillo_detalle['imagen']; ?>" class="detalle-img" onerror="this.src='https://via.placeholder.com/600x280/f1f5f9/0f172a?text=Sabor+%26+Tradici%C3%B3n'">
            <div class="detalle-body">
                <span style="font-size:0.8em; font-weight:700; color:var(--brand-red); text-transform:uppercase; display:block; margin-bottom:4px;"><?php echo htmlspecialchars($platillo_detalle['categoria']); ?></span>
                <h2 class="detalle-title"><?php echo htmlspecialchars($platillo_detalle['nombre']); ?></h2>
                <p style="color: var(--text-secondary); font-size: 0.95em; line-height: 1.5; margin-bottom: 15px;"><?php echo htmlspecialchars($platillo_detalle['descripcion']); ?></p>
                
                <div class="detalle-info-grid">
                    <div class="detalle-info-item">
                        <span>Ingredientes</span>
                        <p><?php echo !empty($platillo_detalle['ingredientes']) ? htmlspecialchars($platillo_detalle['ingredientes']) : 'Ingredientes frescos de la casa'; ?></p>
                    </div>
                    <div class="detalle-info-item">
                        <span>Tiempo estimado</span>
                        <p>⏱️ <?php echo !empty($platillo_detalle['tiempo_preparacion']) ? htmlspecialchars($platillo_detalle['tiempo_preparacion']) : '15-20'; ?> mins</p>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                    <span class="precio" style="font-size: 1.5em;">$<?php echo number_format($platillo_detalle['precio'], 2); ?></span>
                    <button class="btn-confirm-pay" style="width:auto; padding: 12px 24px; margin-top:0;" onclick="agregarAlCarrito(<?php echo $platillo_detalle['id']; ?>, '<?php echo addslashes($platillo_detalle['nombre']); ?>', <?php echo$platillo_detalle['precio']; ?>)">+ Agregar al carrito</button>
                </div>

                <!-- RECOMENDACIONES DE LA MISMA CATEGORÍA -->
                <?php if ($recomendaciones &&$recomendaciones->num_rows > 0): ?>
                <div class="recomendaciones-section">
                    <h3>💡 Te recomendamos también de "<?php echo htmlspecialchars($platillo_detalle['categoria']); ?>"</h3>
                    <div class="recomendaciones-grid">
                        <?php while($rec =$recomendaciones->fetch_assoc()): ?>
                            <a href="index.php?detalle=<?php echo $rec['id']; ?><?php echo $categoria_id ? '&categoria='.$categoria_id : ''; ?>" class="rec-card">
                                <img src="uploads/<?php echo $rec['imagen']; ?>" class="rec-img" onerror="this.src='https://via.placeholder.com/150x90/f1f5f9/0f172a?text=Sabor'">
                                <div class="rec-info">
                                    <div class="rec-title"><?php echo htmlspecialchars($rec['nombre']); ?></div>
                                    <div class="rec-precio">$<?php echo number_format($rec['precio'], 2); ?></div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>

                <a href="index.php<?php echo $categoria_id ? '?categoria='.$categoria_id : ''; ?>" class="btn-cerrar-detalle">Cerrar detalle</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Botón Flotante del Carrito -->
    <div class="cart-float-btn" onclick="toggleCarrito()">
        🛒 Ver Pedido <span class="cart-count" id="cartCount">0</span>
    </div>

    <div class="cart-overlay" id="cartOverlay" onclick="cerrarModales()"></div>

    <!-- Panel Lateral del Carrito -->
    <div class="cart-modal" id="cartModal">
        <div class="cart-header">
            <h3>Tu Pedido</h3>
            <button class="cart-close-btn" onclick="toggleCarrito()">&times;</button>
        </div>

        <div class="cart-items" id="cartItems"></div>

        <div class="cart-footer">
            <div class="cart-total-row">
                <span>Total:</span>
                <span id="cartTotal" style="color: var(--text-primary);">$0.00</span>
            </div>
            <button class="btn-checkout" onclick="abrirCheckout()">Proceder al Pago ➔</button>
        </div>
    </div>

    <!-- Modal Checkout -->
    <div class="checkout-modal" id="checkoutModal">
        <div class="checkout-header">
            <h3>Finalizar Pedido</h3>
            <button class="cart-close-btn" onclick="cerrarModales()">&times;</button>
        </div>

        <div id="checkoutForm">
            <div class="form-group-pay">
                <label>Nombre del Cliente:</label>
                <input type="text" id="clienteNombre" placeholder="Ej. Juan Pérez" required>
            </div>

            <div class="form-group-pay">
                <label>Tipo de Servicio:</label>
                <select id="tipoServicio" onchange="evaluarTipoServicio()">
                    <option value="Mesa">🍽️ En Restaurante (Auto-pedido)</option>
                    <option value="Domicilio">🛵 Envío a Domicilio</option>
                    <option value="Recoger">🛍️ Para Recoger en Sucursal</option>
                </select>
            </div>

            <div class="form-group-pay" id="campoMesaGroup">
                <label>Número de Mesa:</label>
                <input type="text" id="clienteMesa" placeholder="Ej. Mesa 4">
            </div>

            <div class="form-group-pay" id="campoDireccionGroup" style="display:none;">
                <label>Dirección Completa de Entrega:</label>
                <textarea id="clienteDireccion" rows="2" placeholder="Calle, Número, Colonia, Referencias..."></textarea>
            </div>

            <div class="tiempo-box">
                <span>⏱️</span>
                <div>
                    <strong style="font-size:0.9em; display:block;">Tiempo Estimado de Espera:</strong>
                    <span id="tiempoTexto" style="color:var(--brand-red); font-weight:700; font-size:1em;">15 - 20 minutos</span>
                </div>
            </div>

            <div class="form-group-pay">
                <label>Forma de Pago:</label>
                <select id="metodoPago" onchange="evaluarMetodoPago()">
                    <option value="Efectivo">💵 Efectivo</option>
                    <option value="Tarjeta">💳 Tarjeta / TPV</option>
                    <option value="Transferencia">📱 Transferencia / CoDi / Mercado Pago</option>
                </select>
            </div>

            <div class="form-group-pay" id="efectivoGroup">
                <label>Paga con ($ MXN):</label>
                <input type="number" id="pagoEfectivo" placeholder="Ej. 500" oninput="calcularCambio()">
                <p id="cambioTexto" style="color: var(--green-price); font-size: 0.85em; font-weight: 600; margin-top: 5px;"></p>
            </div>

            <div style="background: #f8fafc; padding: 12px 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid var(--border-color);">
                <div style="display:flex; justify-content: space-between; font-weight: bold;">
                    <span>Total a Pagar:</span>
                    <span id="checkoutTotalText" style="color: var(--text-primary);">$0.00</span>
                </div>
            </div>

            <button class="btn-confirm-pay" onclick="procesarPedido()">Confirmar y Enviar Pedido</button>
        </div>

        <div id="ticketSuccess" style="display:none;" class="ticket-box">
            <div class="ticket-icon">✔</div>
            <h2 style="color: var(--green-price); margin-bottom: 5px;">¡Pedido Recibido!</h2>
            <p style="color: var(--text-secondary); font-size: 0.9em; margin-bottom: 15px;">Tu orden ha sido enviada con éxito.</p>
            
            <div id="ticketDetalle" style="text-align: left; background: #f8fafc; padding: 15px; border-radius: 8px; font-size: 0.88em; margin-bottom: 15px; border: 1px solid var(--border-color);"></div>

            <button class="btn-confirm-pay" onclick="finalizarTodo()">Aceptar</button>
        </div>
    </div>

    <script>
        let carrito = JSON.parse(localStorage.getItem('carrito_restaurante')) || [];

        function guardarCarrito() {
            localStorage.setItem('carrito_restaurante', JSON.stringify(carrito));
            actualizarInterfazCarrito();
        }

        function agregarAlCarrito(id, nombre, precio) {
            let existe = carrito.find(item => item.id === id);
            if (existe) {
                existe.cantidad++;
            } else {
                carrito.push({ id, nombre, precio, cantidad: 1 });
            }
            guardarCarrito();
            
            document.getElementById('cartModal').classList.add('open');
            document.getElementById('cartOverlay').classList.add('show');
        }

        function cambiarCantidad(id, cambio) {
            let item = carrito.find(i => i.id === id);
            if (item) {
                item.cantidad += cambio;
                if (item.cantidad <= 0) {
                    carrito = carrito.filter(i => i.id !== id);
                }
            }
            guardarCarrito();
        }

        function toggleCarrito() {
            document.getElementById('cartModal').classList.toggle('open');
            document.getElementById('cartOverlay').classList.toggle('show');
        }

        function cerrarModales() {
            document.getElementById('cartModal').classList.remove('open');
            document.getElementById('checkoutModal').classList.remove('active');
            document.getElementById('cartOverlay').classList.remove('show');
        }

        function actualizarInterfazCarrito() {
            let cartItems = document.getElementById('cartItems');
            let cartCount = document.getElementById('cartCount');
            let cartTotal = document.getElementById('cartTotal');

            cartItems.innerHTML = '';
            let total = 0;
            let totalCantidad = 0;

            if (carrito.length === 0) {
                cartItems.innerHTML = '<p style="text-align:center; color: var(--text-secondary); margin-top: 30px;">Tu carrito está vacío.</p>';
            } else {
                carrito.forEach(item => {
                    total += item.precio * item.cantidad;
                    totalCantidad += item.cantidad;

                    cartItems.innerHTML += `
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <h4>${item.nombre}</h4>
                                <p>$${(item.precio * item.cantidad).toFixed(2)}</p>
                            </div>
                            <div class="cart-controls">
                                <button class="btn-qty" onclick="cambiarCantidad(${item.id}, -1)">-</button>
                                <span>${item.cantidad}</span>
                                <button class="btn-qty" onclick="cambiarCantidad(${item.id}, 1)">+</button>
                            </div>
                        </div>
                    `;
                });
            }

            cartCount.innerText = totalCantidad;
            cartTotal.innerText = '$' + total.toFixed(2);
            document.getElementById('checkoutTotalText').innerText = '$' + total.toFixed(2);
        }

        function calcularTotalSum() {
            return carrito.reduce((acc, i) => acc + (i.precio * i.cantidad), 0);
        }

        function abrirCheckout() {
            if (carrito.length === 0) {
                alert('Agrega al menos un platillo al carrito antes de pagar.');
                return;
            }
            document.getElementById('cartModal').classList.remove('open');
            document.getElementById('checkoutModal').classList.add('active');
            evaluarTipoServicio();
            evaluarMetodoPago();
        }

        function evaluarTipoServicio() {
            let servicio = document.getElementById('tipoServicio').value;
            let campoMesa = document.getElementById('campoMesaGroup');
            let campoDireccion = document.getElementById('campoDireccionGroup');
            let tiempoTexto = document.getElementById('tiempoTexto');

            if (servicio === 'Mesa') {
                campoMesa.style.display = 'block';
                campoDireccion.style.display = 'none';
                tiempoTexto.innerText = '15 - 20 minutos (Directo a tu mesa)';
            } else if (servicio === 'Domicilio') {
                campoMesa.style.display = 'none';
                campoDireccion.style.display = 'block';
                tiempoTexto.innerText = '40 - 50 minutos (Incluye repartidor)';
            } else if (servicio === 'Recoger') {
                campoMesa.style.display = 'none';
                campoDireccion.style.display = 'none';
                tiempoTexto.innerText = '25 - 30 minutos (Listos al llegar)';
            }
        }

        function evaluarMetodoPago() {
            let metodo = document.getElementById('metodoPago').value;
            let efectivoGroup = document.getElementById('efectivoGroup');
            if (metodo === 'Efectivo') {
                efectivoGroup.style.display = 'block';
            } else {
                efectivoGroup.style.display = 'none';
            }
        }

        function calcularCambio() {
            let total = calcularTotalSum();
            let pago = parseFloat(document.getElementById('pagoEfectivo').value) || 0;
            let cambioTexto = document.getElementById('cambioTexto');

            if (pago >= total) {
                let cambio = pago - total;
                cambioTexto.innerText = 'Cambio a devolver: $' + cambio.toFixed(2);
                cambioTexto.style.color = '#16a34a';
            } else {
                cambioTexto.innerText = 'Monto insuficiente';
                cambioTexto.style.color = '#ef4444';
            }
        }

        function procesarPedido() {
            let nombre = document.getElementById('clienteNombre').value.trim();
            let servicio = document.getElementById('tipoServicio').value;
            let metodo = document.getElementById('metodoPago').value;
            let total = calcularTotalSum();
            let pago = parseFloat(document.getElementById('pagoEfectivo').value) || 0;
            let tiempo = document.getElementById('tiempoTexto').innerText;
            
            let ubicacion = '';

            if (!nombre) {
                alert('Por favor ingresa tu nombre.');
                return;
            }

            if (servicio === 'Mesa') {
                let mesa = document.getElementById('clienteMesa').value.trim();
                if (!mesa) { alert('Por favor ingresa el número de mesa.'); return; }
                ubicacion = 'Mesa: ' + mesa;
            } else if (servicio === 'Domicilio') {
                let dir = document.getElementById('clienteDireccion').value.trim();
                if (!dir) { alert('Por favor ingresa tu dirección de entrega.'); return; }
                ubicacion = 'Domicilio: ' + dir;
            } else {
                ubicacion = 'Para Recoger en Sucursal';
            }

            if (metodo === 'Efectivo' && pago < total) {
                alert('El monto ingresado para pago en efectivo es menor al total.');
                return;
            }

            let datosPedido = {
                cliente_nombre: nombre,
                mesa_entrega: ubicacion,
                metodo_pago: metodo,
                pago_con: pago,
                total: total,
                items: carrito
            };

            fetch('guardar_pedido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datosPedido)
            })
            .then(res => res.json())
            .then(data => {
                if (data.exito) {
                    let ticketHtml = `
                        <p><strong>N° de Orden:</strong> #${data.pedido_id}</p>
                        <p><strong>Cliente:</strong> ${nombre}</p>
                        <p><strong>Servicio:</strong> ${ubicacion}</p>
                        <p><strong>Método de Pago:</strong> ${metodo}</p>
                        <p style="color:var(--brand-red); font-weight:bold; margin-top:5px;"><strong>⏱️ Tiempo Estimado:</strong> ${tiempo}</p>
                        <hr style="border-color: var(--border-color); margin: 8px 0;">
                        <p><strong>Resumen del Pedido:</strong></p>
                        <ul style="padding-left: 18px; margin: 5px 0;">
                    `;

                    carrito.forEach(i => {
                        ticketHtml += `<li>${i.cantidad}x ${i.nombre} - $${(i.precio * i.cantidad).toFixed(2)}</li>`;
                    });

                    ticketHtml += `</ul>
                        <hr style="border-color: var(--border-color); margin: 8px 0;">
                        <p style="font-size: 1.1em;"><strong>Total: $${total.toFixed(2)}</strong></p>
                    `;

                    document.getElementById('checkoutForm').style.display = 'none';
                    document.getElementById('ticketDetalle').innerHTML = ticketHtml;
                    document.getElementById('ticketSuccess').style.display = 'block';
                } else {
                    alert('Error al guardar el pedido: ' + data.mensaje);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Ocurrió un error al procesar el pedido.');
            });
        }

        function finalizarTodo() {
            carrito = [];
            guardarCarrito();
            cerrarModales();
            document.getElementById('checkoutForm').style.display = 'block';
            document.getElementById('ticketSuccess').style.display = 'none';
            document.getElementById('clienteNombre').value = '';
            document.getElementById('clienteMesa').value = '';
            document.getElementById('clienteDireccion').value = '';
            document.getElementById('pagoEfectivo').value = '';
        }

        actualizarInterfazCarrito();
    </script>
</body>
</html>