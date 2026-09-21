<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "restaurante_db";

$conexion = new mysqli($host, $user, $password, $database);

if ($conexion->connect_error) {
    die("Error de conexión a la base de datos: " . $conexion->connect_error);
}
?>