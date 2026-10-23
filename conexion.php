<?php
// Conexión a MariaDB/MySQL (XAMPP: usuario root sin contraseña).
// En el servidor real cambia estos datos por un usuario propio con contraseña.
$host     = "localhost";
$user     = "root";
$password = "";
$database = "restaurante_db";
 
// Los errores de MySQL lanzan excepciones (así no se ignoran en silencio).
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
 
try {
    $conexion = new mysqli($host, $user, $password, $database);
    $conexion->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Error de conexión: " . $e->getMessage());
    http_response_code(500);
    die("No se pudo conectar a la base de datos.");
}
 
// Escapa texto para mostrarlo en HTML (previene XSS).
function e($texto): string
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}
 