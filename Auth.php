<?php
// Sesión y autenticación del panel de administración.
 
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}
 
// Credenciales del administrador.
// Usuario: admin | Contraseña inicial: Admin2026!  (CÁMBIALA)
// Para generar otro hash con PHP de XAMPP:
//   C:\xampp\php\php.exe -r "echo password_hash('TuNuevaClave', PASSWORD_DEFAULT);"
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$2y$10$BPOu0SDg1liT2XMeEWCz7OoRl3CVpjKhQLj8WIc2b9aeP/7i5HArK');
 
function admin_autenticado(): bool
{
    return !empty($_SESSION['admin_ok']);
}
 
function requerir_admin(): void
{
    if (!admin_autenticado()) {
        header('Location: login.php');
        exit;
    }
}
 
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
 
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
 
function csrf_validar(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals($_SESSION['csrf'] ?? '', $enviado)) {
        http_response_code(403);
        die('Token de seguridad inválido. Recarga la página e inténtalo de nuevo.');
    }
}
 