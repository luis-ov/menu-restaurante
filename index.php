<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Interactivo</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 20px; }
        .header { text-align: center; padding: 20px; background-color: #333; color: white; border-radius: 8px; }
        .menu-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; margin-top: 20px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 250px; padding: 15px; text-align: center; }
        .precio { color: #e67e22; font-weight: bold; font-size: 1.2em; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Menú del Restaurante</h1>
        <p>¡Elige tus platillos favoritos!</p>
    </div>

    <div class="menu-container">
        <!-- Ejemplo de platillo maquetado -->
        <div class="card">
            <h3>Tacos al Pastor</h3>
            <p>Con piña, cilantro y cebolla.</p>
            <p class="precio">$110.00</p>
        </div>
    </div>

</body>
</html>