<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

$usuario_id = (int)$_SESSION["id"];
$producto_id = (int)$_POST["producto_id"];
$compra_id = (int)$_POST["compra_id"];
$calificacion = (int)$_POST["calificacion"];
$comentario = $_POST["comentario"] ?? "";

if ($calificacion < 1 || $calificacion > 5) {
    header("Location: mis_compras.php");
    exit();
}

$sql = "
INSERT INTO calificaciones
(producto_id, usuario_id, compra_id, calificacion, comentario)
VALUES (?, ?, ?, ?, ?)
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("iiiis", $producto_id, $usuario_id, $compra_id, $calificacion, $comentario);
$stmt->execute();

header("Location: mis_compras.php?calificacion=ok");
exit();
?>