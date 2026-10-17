<?php
include 'conexion.php';

// Obtener todas las categorías
$query_cat = "SELECT * FROM categorias";
$resultado_cat = $conexion->query($query_cat);

// Capturar parámetros de filtrado y búsqueda
$categoria_id = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$busqueda = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : '';

// Consulta dinámica de platillos
$query = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.imagen, c.nombre AS categoria 
          FROM platillos p 
          JOIN categorias c ON p.categoria_id = c.id WHERE 1=1";

if ($categoria_id > 0) {$query .= " AND p.categoria_id = $categoria_id";
}

if (!empty($busqueda)) {$query .= " AND (p.nombre LIKE '%$busqueda\%' OR p.descripcion LIKE '\%$busqueda%')";
}

$query .= " ORDER BY c.id, p.nombre";
$resultado = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabor & Tradición - Menú Gourmet</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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
            background-color: var(--bg-color); 
            color: var(--text-main);
            padding-bottom: 80px;
        }

        .header { 
            text-align: center; 
            padding: 50px 20px 30px; 
            background: linear-gradient(180deg, rgba(30,41,59,0.8) 0%, rgba(15,23,42,1) 100%);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .header h1 { 
            font-size: 2.8em; 
            font-weight: 700;
            color: var(--accent-color);
            letter-spacing: 1px;
        }

        .header p { 
            color: var(--text-muted); 
            font-size: 1.1em;
            margin-top: 5px;
        }

        .filtros-container { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            gap: 20px; 
            margin: 30px auto; 
            max-width: 900px;
            padding: 0 20px;
        }

        .busqueda-form { 
            display: flex; 
            gap: 10px; 
            width: 100%; 
            max-width: 500px; 
        }

        .busqueda-input { 
            flex: 1; 
            padding: 12px 18px; 
            background-color: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 30px; 
            color: var(--text-main);
            font-size: 0.95em;
            outline: none;
            transition: 0.3s;
        }

        .busqueda-input:focus { border-color: var(--accent-color); }

        .btn-buscar { 
            padding: 12px 24px; 
            background-color: var(--accent-color); 
            color: white; 
            border: none; 
            border-radius: 30px; 
            cursor: pointer; 
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-buscar:hover { background-color: var(--accent-hover); }

        .categorias-buttons { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 10px; 
            justify-content: center; 
        }

        .btn-filtro { 
            padding: 8px 20px; 
            background-color: var(--card-bg); 
            color: var(--text-muted); 
            text-decoration: none; 
            border-radius: 20px; 
            font-size: 0.9em;
            font-weight: 500; 
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .btn-filtro:hover, .btn-filtro.activo { 
            background-color: var(--accent-color); 
            color: white; 
        }

        .menu-container { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 25px; 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 20px; 
        }

        .card { 
            background: var(--card-bg); 
            border-radius: 16px; 
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3); 
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 30px rgba(0,0,0,0.5); 
        }

        .card-img-container {
            position: relative;
            width: 100%;
            height: 180px;
        }

        .card-img { width: 100%; height: 100%; object-fit: cover; }

        .categoria-badge { 
            position: absolute;
            top: 12px;
            left: 12px;
            background-color: rgba(15, 23, 42, 0.85); 
            color: var(--accent-color); 
            padding: 4px 12px; 
            border-radius: 12px; 
            font-size: 0.75em; 
            font-weight: 700;
            text-transform: uppercase; 
            backdrop-filter: blur(4px);
        }

        .card-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-title { font-size: 1.2em; font-weight: 600; margin-bottom: 8px; color: var(--text-main); }
        .card-desc { color: var(--text-muted); font-size: 0.88em; line-height: 1.4; margin-bottom: 15px; flex-grow: 1; }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .precio { color: var(--green-price); font-weight: 700; font-size: 1.3em; }

        .btn-add-cart {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            font-size: 0.85em;
        }

        .btn-add-cart:hover { background-color: var(--accent-hover); }

        /* Botón Flotante */
        .cart-float-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: var(--accent-color);
            color: white;
            padding: 14px 22px;
            border-radius: 30px;
            box-shadow: 0 10px 25px rgba(217, 119, 6, 0.4);
            cursor: pointer;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 99;
            transition: 0.3s;
        }

        .cart-float-btn:hover { transform: scale(1.05); }

        .cart-count {
            background-color: white;
            color: var(--accent-color);
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.85em;
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
            box-shadow: -5px 0 25px rgba(0,0,0,0.5);
            z-index: 100;
            transition: right 0.4s ease;
            display: flex;
            flex-direction: column;
        }

        .cart-modal.open { right: 0; }

        .cart-header {
            padding: 20px;
            background-color: rgba(15, 23, 42, 0.8);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .cart-close-btn {
            background: none;
            border: none;
            color: var(--text-muted);
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
            background: rgba(15, 23, 42, 0.5);
            padding: 12px;
            border-radius: 10px;
        }

        .cart-item-info h4 { font-size: 0.95em; margin-bottom: 4px; }
        .cart-item-info p { color: var(--green-price); font-weight: 600; font-size: 0.85em; }

        .cart-controls { display: flex; align-items: center; gap: 8px; }
        .btn-qty {
            background-color: var(--accent-color);
            color: white;
            border: none;
            width: 26px;
            height: 26px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .cart-footer {
            padding: 20px;
            background-color: rgba(15, 23, 42, 0.9);
            border-top: 1px solid rgba(255,255,255,0.05);
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
            background-color: var(--green-price);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1em;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-checkout:hover { opacity: 0.9; }

        .cart-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            display: none;
            z-index: 98;
        }
        .cart-overlay.show { display: block; }

        /* Modal de Checkout / Formas de Pago */
        .checkout-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            width: 90%;
            max-width: 500px;
            background-color: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            z-index: 101;
            padding: 25px;
            display: none;
            opacity: 0;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
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
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 10px;
        }

        .form-group-pay { margin-bottom: 15px; }

        .form-group-pay label {
            display: block;
            font-size: 0.88em;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .form-group-pay input, .form-group-pay select, .form-group-pay textarea {
            width: 100%;
            padding: 10px 14px;
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text-main);
            font-size: 0.95em;
            outline: none;
            font-family: inherit;
        }

        .form-group-pay input:focus, .form-group-pay select:focus, .form-group-pay textarea:focus { 
            border-color: var(--accent-color); 
        }

        .tiempo-box {
            background: rgba(217, 119, 6, 0.15);
            border: 1px solid var(--accent-color);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tiempo-box span { font-size: 1.3em; }

        .btn-confirm-pay {
            width: 100%;
            padding: 12px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.2s;
        }

        .btn-confirm-pay:hover { background-color: var(--accent-hover); }

        .ticket-box {
            text-align: center;
            padding: 10px;
        }

        .ticket-icon { font-size: 3em; color: var(--green-price); margin-bottom: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>SABOR & TRADICIÓN</h1>
        <p>Experiencia Gastronómica & Alta Cocina</p>
    </div>

    <div class="filtros-container">
        <form method="GET" action="index.php" class="busqueda-form">
            <?php if ($categoria_id > 0): ?>
                <input type="hidden" name="categoria" value="<?php echo $categoria_id; ?>">
            <?php endif; ?>
            <input type="text" name="buscar" class="busqueda-input" placeholder="Buscar tu platillo favorito..." value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" class="btn-buscar">Buscar</button>
        </form>

        <div class="categorias-buttons">
            <a href="index.php" class="btn-filtro <?php echo $categoria_id == 0 ? 'activo' : ''; ?>">Todos</a>
            <?php while($cat =$resultado_cat->fetch_assoc()): ?>
                <a href="index.php?categoria=<?php echo $cat['id']; ?><?php echo !empty($busqueda) ? '&buscar='.urlencode($busqueda) : ''; ?>" 
                   class="btn-filtro <?php echo $categoria_id ==$cat['id'] ? 'activo' : ''; ?>">
                    <?php echo htmlspecialchars($cat['nombre']); ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="menu-container">
        <?php if ($resultado->num_rows > 0): ?>
            <?php while($platillo =$resultado->fetch_assoc()): ?>
                <div class="card">
                    <div class="card-img-container">
                        <img src="uploads/<?php echo $platillo['imagen']; ?>" class="card-img" onerror="this.src='https://via.placeholder.com/280x180/1e293b/d97706?text=Sabor+%26+Tradici%C3%B3n'">
                        <span class="categoria-badge"><?php echo htmlspecialchars($platillo['categoria']); ?></span>
                    </div>
                    
                    <div class="card-content">
                        <h3 class="card-title"><?php echo htmlspecialchars($platillo['nombre']); ?></h3>
                        <p class="card-desc"><?php echo htmlspecialchars($platillo['descripcion']); ?></p>
                        
                        <div class="card-footer">
                            <div class="precio">$<?php echo number_format($platillo['precio'], 2); ?></div>
                            <button class="btn-add-cart" onclick="agregarAlCarrito(<?php echo $platillo['id']; ?>, '<?php echo addslashes($platillo['nombre']); ?>', <?php echo$platillo['precio']; ?>)">+ Agregar</button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; grid-column: 1/-1; padding: 40px; color: var(--text-muted);">
                <h3>No encontramos platillos que coincidan con tu búsqueda.</h3>
            </div>
        <?php endif; ?>
    </div>

    <!-- Botón Flotante del Carrito -->
    <div class="cart-float-btn" onclick="toggleCarrito()">
        🛒 Carrito <span class="cart-count" id="cartCount">0</span>
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
                <span id="cartTotal" style="color: var(--green-price);">$0.00</span>
            </div>
            <button class="btn-checkout" onclick="abrirCheckout()">Proceder al Pago ➔</button>
        </div>
    </div>

    <!-- Modal de Checkout / Formas de Pago -->
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

            <!-- Campo Dinámico según servicio -->
            <div class="form-group-pay" id="campoMesaGroup">
                <label>Número de Mesa:</label>
                <input type="text" id="clienteMesa" placeholder="Ej. Mesa 4">
            </div>

            <div class="form-group-pay" id="campoDireccionGroup" style="display:none;">
                <label>Dirección Completa de Entrega:</label>
                <textarea id="clienteDireccion" rows="2" placeholder="Calle, Número, Colonia, Referencias..."></textarea>
            </div>

            <!-- Caja de Tiempo Estimado -->
            <div class="tiempo-box">
                <span>⏱️</span>
                <div>
                    <strong style="font-size:0.9em; display:block;">Tiempo Estimado de Espera:</strong>
                    <span id="tiempoTexto" style="color:var(--accent-color); font-weight:700; font-size:1em;">15 - 20 minutos</span>
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

            <div style="background: rgba(15, 23, 42, 0.6); padding: 10px 15px; border-radius: 8px; margin-bottom: 15px;">
                <div style="display:flex; justify-content: space-between; font-weight: bold;">
                    <span>Total a Pagar:</span>
                    <span id="checkoutTotalText" style="color: var(--green-price);">$0.00</span>
                </div>
            </div>

            <button class="btn-confirm-pay" onclick="procesarPedido()">Confirmar y Enviar Pedido</button>
        </div>

        <div id="ticketSuccess" style="display:none;" class="ticket-box">
            <div class="ticket-icon">✔</div>
            <h2 style="color: var(--green-price); margin-bottom: 5px;">¡Pedido Recibido!</h2>
            <p style="color: var(--text-muted); font-size: 0.9em; margin-bottom: 15px;">Tu orden ha sido enviada con éxito.</p>
            
            <div id="ticketDetalle" style="text-align: left; background: rgba(15, 23, 42, 0.6); padding: 15px; border-radius: 10px; font-size: 0.88em; margin-bottom: 15px;"></div>

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
                cartItems.innerHTML = '<p style="text-align:center; color: var(--text-muted); margin-top: 30px;">Tu carrito está vacío.</p>';
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
                cambioTexto.style.color = '#10b981';
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

            // Enviar pedido al servidor
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
                        <p style="color:var(--accent-color); font-weight:bold; margin-top:5px;"><strong>⏱️ Tiempo Estimado:</strong> ${tiempo}</p>
                        <hr style="border-color: rgba(255,255,255,0.1); margin: 8px 0;">
                        <p><strong>Resumen del Pedido:</strong></p>
                        <ul style="padding-left: 18px; margin: 5px 0;">
                    `;

                    carrito.forEach(i => {
                        ticketHtml += `<li>${i.cantidad}x ${i.nombre} - $${(i.precio * i.cantidad).toFixed(2)}</li>`;
                    });

                    ticketHtml += `</ul>
                        <hr style="border-color: rgba(255,255,255,0.1); margin: 8px 0;">
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