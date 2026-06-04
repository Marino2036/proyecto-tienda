<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["id"])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../panel.php");
    exit();
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: gestionar_productos.php");
    exit();
}


$id = (int)$_GET["id"];

$sqlEliminar = "DELETE FROM productos WHERE id = ?";
$stmtEliminar = $conexion->prepare($sqlEliminar);
$stmtEliminar->bind_param("i", $id);
$stmtEliminar->execute();

header("Location: gestionar_productos.php");
exit();
?>