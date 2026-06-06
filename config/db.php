<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "acela.proxy.rlwy.net";
$usuario = "root";
$contrasena = "NmrdwmoHIYgGLWQhEBrUKZolSCtvfPrl";
$basedatos = "railway";
$puerto = 28552;

$conexion = new mysqli(
    $host,
    $usuario,
    $contrasena,
    $basedatos,
    $puerto
);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>