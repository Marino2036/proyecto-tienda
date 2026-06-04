<?php

$host = getenv("DB_HOST") ?: "127.0.0.1";
$usuario = getenv("DB_USER") ?: "root";
$contrasena = getenv("DB_PASS") ?: "root";
$basedatos = getenv("DB_NAME") ?: "tienda";
$puerto = getenv("DB_PORT") ?: 3306;

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