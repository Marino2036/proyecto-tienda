<?php
require_once "../config/db.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    http_response_code(400);
    exit("ID inválido");
}

$id = (int) $_GET["id"];

$sql = "SELECT imagen FROM productos WHERE id = ? LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    http_response_code(404);
    exit("Imagen no encontrada");
}

$fila = $resultado->fetch_assoc();

if (empty($fila["imagen"])) {
    http_response_code(404);
    exit("Sin imagen");
}

header("Content-Type: image/jpeg");
echo $fila["imagen"];
exit();
?>