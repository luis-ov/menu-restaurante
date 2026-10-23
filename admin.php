<?php
require __DIR__ . '/conexion.php';
require __DIR__ . '/auth.php';
requerir_admin();
 
const ESTADOS_VALIDOS = ['Pendiente', 'En Preparacion', 'Entregado', 'Cancelado'];
const EXT_PERMITIDAS  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
const MIME_PERMITIDOS = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
const MAX_IMAGEN      = 2 * 1024 * 1024; // 2 MB
$DIR_UPLOADS = __DIR__ . '/uploads/';
 
// ---------- Funciones auxiliares ----------
 
// Valida y guarda la imagen subida. Devuelve el nombre del archivo o null si no se subió nada.
function guardar_imagen(array $f, string $dir): ?string
{
    if (!isset($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir la imagen (código ' . (int)$f['error'] . ').');
    }
    if ($f['size'] > MAX_IMAGEN) {
        throw new RuntimeException('La imagen supera el tamaño máximo de 2 MB.');
    }
 
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, EXT_PERMITIDAS, true)) {
        throw new RuntimeException('Formato no permitido. Usa JPG, PNG, WEBP o GIF.');
    }
 
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($f['tmp_name']);
    if (!in_array($mime, MIME_PERMITIDOS, true) || @getimagesize($f['tmp_name']) === false) {
        throw new RuntimeException('El archivo no es una imagen válida.');
    }
 
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
    }
 
    $nombre = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . $nombre)) {
        throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
    }
    return $nombre;
}
 
// Borra una imagen solo si ningún otro platillo la usa
function borrar_imagen_si_huerfana(mysqli $conexion, string $nombre, string $dir): void
{
    $nombre = basename($nombre);
    if ($nombre === '' || $nombre === 'default.jpg') {
        return;
    }
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM platillos WHERE imagen = ?");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $usos = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
 
    if ($usos === 0 && is_file($dir . $nombre)) {
        @unlink($dir . $nombre);
    }
}
 
function flash(string $texto, string $tipo = 'ok'): void
{
    $_SESSION['flash'] = ['texto' => $texto, 'tipo' => $tipo];
}
 
// Botón-formulario para cambiar el estado de un pedido (POST + CSRF)
function boton_estado(int $pedido_id, string $estado, string $etiqueta, string $color): string
{
    return '<form method="POST" action="admin.php?tab=pedidos" style="display:inline;">'
        . csrf_campo()
        . '<input type="hidden" name="accion" value="cambiar_estado">'
        . '<input type="hidden" name="pedido_id" value="' . $pedido_id . '">'
        . '<input type="hidden" name="estado" value="' . e($estado) . '">'
        . '<button type="submit" class="btn-estado" style="color:' . $color . ';">' . $etiqueta . '</button>'
        . '</form>';
}
 
// ---------- Procesar acciones (todas por POST con token CSRF) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
 
    try {
        if ($accion === 'cambiar_estado') {
            $pedido_id = (int)($_POST['pedido_id'] ?? 0);
            $estado    = (string)($_POST['estado'] ?? '');
 
            if ($pedido_id > 0 && in_array($estado, ESTADOS_VALIDOS, true)) {
                $stmt = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
                $stmt->bind_param("si", $estado, $pedido_id);
                $stmt->execute();
                $stmt->close();
                flash("Pedido #$pedido_id actualizado a: $estado");
            } else {
                flash('Datos inválidos al cambiar el estado.', 'error');
            }
            header("Location: admin.php?tab=pedidos");
            exit;
        }
 
        if ($accion === 'eliminar_platillo') {
            $id = (int)($_POST['id'] ?? 0);
 
            $stmt = $conexion->prepare("SELECT imagen FROM platillos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();
 
            if ($fila) {
                $stmt = $conexion->prepare("DELETE FROM platillos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                borrar_imagen_si_huerfana($conexion, (string)$fila['imagen'], $DIR_UPLOADS);
                flash('Platillo eliminado.');
            } else {
                flash('El platillo no existe.', 'error');
            }
            header("Location: admin.php");
            exit;
        }
 
        if ($accion === 'guardar_platillo') {
            $id           = (int)($_POST['id'] ?? 0);
            $nombre       = trim((string)($_POST['nombre'] ?? ''));
            $descripcion  = trim((string)($_POST['descripcion'] ?? ''));
            $precio       = (float)($_POST['precio'] ?? 0);
            $categoria_id = (int)($_POST['categoria_id'] ?? 0);
 
            if ($nombre === '' || $precio <= 0 || $categoria_id <= 0) {
                throw new RuntimeException('Completa nombre, categoría y un precio mayor a 0.');
            }
            $nombre = mb_substr($nombre, 0, 150);
 
            $imagen_nueva = guardar_imagen($_FILES['imagen'] ?? [], $DIR_UPLOADS);
 
            if ($id > 0) {
                // Imagen anterior (para borrarla si se reemplaza)
                $stmt = $conexion->prepare("SELECT imagen FROM platillos WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $anterior = $stmt->get_result()->fetch_assoc();
                $stmt->close();
 
                if ($imagen_nueva !== null) {
                    $stmt = $conexion->prepare(
                        "UPDATE platillos SET nombre = ?, descripcion = ?, precio = ?, categoria_id = ?, imagen = ? WHERE id = ?"
                    );
                    $stmt->bind_param("ssdisi", $nombre, $descripcion, $precio, $categoria_id, $imagen_nueva, $id);
                } else {
                    $stmt = $conexion->prepare(
                        "UPDATE platillos SET nombre = ?, descripcion = ?, precio = ?, categoria_id = ? WHERE id = ?"
                    );
                    $stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $categoria_id, $id);
                }
                $stmt->execute();
                $stmt->close();
 
                if ($imagen_nueva !== null && $anterior) {
                    borrar_imagen_si_huerfana($conexion, (string)$anterior['imagen'], $DIR_UPLOADS);
                }
                flash('¡Platillo actualizado correctamente!');
            } else {
                $imagen = $imagen_nueva ?? 'default.jpg';
                $stmt = $conexion->prepare(
                    "INSERT INTO platillos (categoria_id, nombre, descripcion, precio, imagen) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("issds", $categoria_id, $nombre, $descripcion, $precio, $imagen);
                $stmt->execute();
                $stmt->close();
                flash('¡Platillo registrado correctamente!');
            }
            header("Location: admin.php");
            exit;
        }
    } catch (RuntimeException $ex) {
        // Errores de validación (mensaje seguro para mostrar)
        flash($ex->getMessage(), 'error');
        header("Location: admin.php" . ($accion === 'cambiar_estado' ? '?tab=pedidos' : ''));
        exit;
    } catch (Throwable $ex) {
        // Errores internos (se registran, no se muestran detalles)
        error_log("admin.php: " . $ex->getMessage());
        flash('Ocurrió un error interno. Revisa el registro del servidor.', 'error');
        header("Location: admin.php");
        exit;
    }
}
 
// ---------- Mensaje flash ----------
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
 
// ---------- Datos para la vista ----------
$tab_activa = (($_GET['tab'] ?? '') === 'pedidos') ? 'pedidos' : 'platillos';
 
$platillo_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $stmt = $conexion->prepare("SELECT * FROM platillos WHERE id = ?");
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $platillo_editar = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
 
$categorias = $conexion->query("SELECT id, nombre FROM categorias ORDER BY id")->fetch_all(MYSQLI_ASSOC);
 
$platillos = $conexion->query(
    "SELECT p.id, p.nombre, p.precio, p.imagen, c.nombre AS categoria
     FROM platillos p JOIN categorias c ON p.categoria_id = c.id
     ORDER BY p.id DESC"
)->fetch_all(MYSQLI_ASSOC);
 
// Métricas
$total_ventas  = (float)($conexion->query("SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE estado != 'Cancelado'")->fetch_row()[0] ?? 0);
$total_pedidos = (int)($conexion->query("SELECT COUNT(*) FROM pedidos")->fetch_row()[0] ?? 0);
$pendientes    = (int)($conexion->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'Pendiente'")->fetch_row()[0] ?? 0);
 
// Pedidos (últimos 100) con su detalle en una sola consulta (sin N+1)
$pedidos = [];
$detalles_por_pedido = [];
if ($tab_activa === 'pedidos') {
    $pedidos = $conexion->query("SELECT * FROM pedidos ORDER BY id DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
 
    $res = $conexion->query(
        "SELECT d.pedido_id, d.platillo_nombre, d.cantidad
         FROM detalle_pedidos d
         JOIN (SELECT id FROM pedidos ORDER BY id DESC LIMIT 100) x ON x.id = d.pedido_id
         ORDER BY d.id"
    );
    while ($d = $res->fetch_assoc()) {
        $detalles_por_pedido[$d['pedido_id']][] = $d;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración &amp; Control de Pedidos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #0f172a; color: #f8fafc; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; background: #1e293b; padding: 20px 30px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05); }
        .header h1 { font-size: 1.6em; color: #d97706; margin: 0; }
        .header-links { display: flex; gap: 10px; }
        .btn-link { color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #1e293b; padding: 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05); text-align: center; }
        .stat-card h3 { color: #94a3b8; font-size: 0.85em; text-transform: uppercase; margin: 0 0 5px; }
        .stat-card p { font-size: 1.6em; font-weight: 700; color: #10b981; margin: 0; }
        .nav-tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .tab-btn { padding: 10px 20px; background: #1e293b; color: #94a3b8; text-decoration: none; border-radius: 8px; font-weight: 600; border: 1px solid rgba(255,255,255,0.05); }
        .tab-btn.active { background: #d97706; color: white; }
        .card-panel { background: #1e293b; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.05); overflow-x: auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: #94a3b8; font-size: 0.9em; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; box-sizing: border-box; font-family: inherit; }
        .btn-guardar { background-color: #d97706; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; font-family: inherit; }
        .btn-cancelar { display: block; text-align: center; margin-top: 10px; color: #94a3b8; text-decoration: none; font-size: 0.9em; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: left; font-size: 0.9em; vertical-align: top; }
        th { color: #94a3b8; }
        .img-tabla { width: 45px; height: 45px; object-fit: cover; border-radius: 6px; background: #334155; }
        .badge-estado { padding: 4px 10px; border-radius: 12px; font-size: 0.75em; font-weight: 700; display: inline-block; white-space: nowrap; }
        .estado-Pendiente { background: #f59e0b; color: #000; }
        .estado-EnPreparacion { background: #3b82f6; color: #fff; }
        .estado-Entregado { background: #10b981; color: #fff; }
        .estado-Cancelado { background: #ef4444; color: #fff; }
        .btn-estado { background: none; border: none; padding: 0; cursor: pointer; font-family: inherit; font-size: 0.8em; color: #38bdf8; }
        .btn-estado:hover, .link-btn:hover { text-decoration: underline; }
        .link-btn { background: none; border: none; padding: 0; cursor: pointer; font-family: inherit; font-size: 1em; }
        .alerta { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .alerta.ok { background: rgba(16,185,129,0.15); border: 1px solid #10b981; color: #6ee7b7; }
        .alerta.error { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #fca5a5; }
    </style>
</head>
<body>
 
<div class="container">
    <div class="header">
        <h1>Control del Restaurante</h1>
        <div class="header-links">
            <a class="btn-link" style="background:#10b981;" href="index.php" target="_blank" rel="noopener">Ver Menú ↗</a>
            <a class="btn-link" style="background:#475569;" href="logout.php">Salir</a>
        </div>
    </div>
 
    <?php if ($flash): ?>
        <div class="alerta <?= $flash['tipo'] === 'error' ? 'error' : 'ok' ?>"><?= e($flash['texto']) ?></div>
    <?php endif; ?>
 
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Ventas Totales</h3>
            <p>$<?= number_format($total_ventas, 2) ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Pedidos</h3>
            <p style="color: #38bdf8;"><?= $total_pedidos ?></p>
        </div>
        <div class="stat-card">
            <h3>Pendientes</h3>
            <p style="color: #f59e0b;"><?= $pendientes ?></p>
        </div>
    </div>
 
    <div class="nav-tabs">
        <a href="admin.php?tab=platillos" class="tab-btn <?= $tab_activa === 'platillos' ? 'active' : '' ?>">🍱 Menú &amp; Platillos</a>
        <a href="admin.php?tab=pedidos" class="tab-btn <?= $tab_activa === 'pedidos' ? 'active' : '' ?>">📋 Gestión de Pedidos</a>
    </div>
 
    <?php if ($tab_activa === 'platillos'): ?>
 
        <div class="card-panel">
            <h2><?= $platillo_editar ? "Editar Platillo" : "Agregar Nuevo Platillo" ?></h2>
            <form method="POST" action="admin.php" enctype="multipart/form-data">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="guardar_platillo">
                <?php if ($platillo_editar): ?>
                    <input type="hidden" name="id" value="<?= (int)$platillo_editar['id'] ?>">
                <?php endif; ?>
 
                <div class="form-group">
                    <label for="nombre">Nombre del Platillo:*</label>
                    <input type="text" id="nombre" name="nombre" maxlength="150" value="<?= $platillo_editar ? e($platillo_editar['nombre']) : '' ?>" required>
                </div>
 
                <div class="form-group">
                    <label for="categoria_id">Categoría:*</label>
                    <select id="categoria_id" name="categoria_id" required>
                        <option value="">Selecciona categoría</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= ($platillo_editar && $platillo_editar['categoria_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
 
                <div class="form-group">
                    <label for="precio">Precio ($ MXN):*</label>
                    <input type="number" id="precio" step="0.01" min="0.01" name="precio" value="<?= $platillo_editar ? e($platillo_editar['precio']) : '' ?>" required>
                </div>
 
                <div class="form-group">
                    <label for="imagen">Imagen del Platillo (JPG, PNG, WEBP o GIF, máx. 2 MB):</label>
                    <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif">
                </div>
 
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" rows="2"><?= $platillo_editar ? e($platillo_editar['descripcion']) : '' ?></textarea>
                </div>
 
                <button type="submit" class="btn-guardar"><?= $platillo_editar ? "Actualizar Platillo" : "Guardar Platillo" ?></button>
                <?php if ($platillo_editar): ?>
                    <a href="admin.php" class="btn-cancelar">Cancelar edición</a>
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
                    <?php foreach ($platillos as $p): ?>
                        <tr>
                            <td><img src="uploads/<?= e($p['imagen']) ?>" class="img-tabla" alt="" onerror="this.onerror=null;this.style.visibility='hidden'"></td>
                            <td><strong><?= e($p['nombre']) ?></strong></td>
                            <td><?= e($p['categoria']) ?></td>
                            <td>$<?= number_format((float)$p['precio'], 2) ?></td>
                            <td>
                                <a href="admin.php?editar=<?= (int)$p['id'] ?>" style="color: #f59e0b; text-decoration: none; margin-right: 10px;">Editar</a>
                                <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('¿Eliminar este platillo?');">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="accion" value="eliminar_platillo">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="link-btn" style="color: #ef4444;">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($platillos) === 0): ?>
                        <tr><td colspan="5" style="text-align:center; color:#94a3b8;">Aún no hay platillos registrados.</td></tr>
                    <?php endif; ?>
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
                    <?php if (count($pedidos) > 0): ?>
                        <?php foreach ($pedidos as $ped):
                            $clase_estado = str_replace(' ', '', $ped['estado']);
                        ?>
                            <tr>
                                <td><strong>#<?= (int)$ped['id'] ?></strong></td>
                                <td><?= e($ped['cliente_nombre']) ?></td>
                                <td><?= e($ped['mesa_entrega']) ?></td>
                                <td><?= e($ped['metodo_pago']) ?></td>
                                <td style="color: #10b981; font-weight: bold;">$<?= number_format((float)$ped['total'], 2) ?></td>
                                <td><span class="badge-estado estado-<?= e($clase_estado) ?>"><?= e($ped['estado']) ?></span></td>
                                <td>
                                    <ul style="padding-left: 15px; margin: 0; font-size: 0.85em; color: #94a3b8;">
                                        <?php foreach ($detalles_por_pedido[$ped['id']] ?? [] as $d): ?>
                                            <li><?= (int)$d['cantidad'] ?>x <?= e($d['platillo_nombre']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>
                                    <?= boton_estado((int)$ped['id'], 'En Preparacion', '▶ Cocina', '#38bdf8') ?> |
                                    <?= boton_estado((int)$ped['id'], 'Entregado', '✔ Entregado', '#10b981') ?> |
                                    <?= boton_estado((int)$ped['id'], 'Cancelado', '✖ Cancelar', '#ef4444') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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