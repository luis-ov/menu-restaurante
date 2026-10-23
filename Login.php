<?php
require __DIR__ . '/auth.php';
 
if (admin_autenticado()) {
    header('Location: admin.php');
    exit;
}
 
$error = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
 
    $usuario = trim((string)($_POST['usuario'] ?? ''));
    $clave   = (string)($_POST['clave'] ?? '');
 
    $usuario_ok = hash_equals(ADMIN_USER, $usuario);
    $clave_ok   = password_verify($clave, ADMIN_PASS_HASH);
 
    if ($usuario_ok && $clave_ok) {
        session_regenerate_id(true);
        $_SESSION['admin_ok'] = true;
        header('Location: admin.php');
        exit;
    }
 
    usleep(600000); // pequeña pausa para frenar intentos repetidos
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administración</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #0f172a; color: #f8fafc; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-card { background: #1e293b; width: 100%; max-width: 380px; padding: 30px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }
        h1 { color: #d97706; font-size: 1.5em; margin: 0 0 20px; text-align: center; }
        label { display: block; font-weight: 600; font-size: 0.9em; color: #94a3b8; margin-bottom: 5px; }
        input { width: 100%; padding: 10px; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; margin-bottom: 15px; font-family: inherit; }
        button { width: 100%; background: #d97706; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 700; cursor: pointer; font-family: inherit; }
        .error { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9em; }
        .volver { display: block; text-align: center; margin-top: 15px; color: #94a3b8; font-size: 0.85em; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Control del Restaurante</h1>
 
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
 
        <form method="POST" action="login.php" autocomplete="off">
            <?php echo csrf_campo(); ?>
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required autofocus>
 
            <label for="clave">Contraseña</label>
            <input type="password" id="clave" name="clave" required>
 
            <button type="submit">Entrar</button>
        </form>
 
        <a class="volver" href="index.php">← Volver al menú</a>
    </div>
</body>
</html>