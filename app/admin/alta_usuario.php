<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["id"]) || !isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: usuarios.php");
    exit();
}

$id = (int) $_GET["id"];

$sql = "UPDATE usuarios SET activo = 1 WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: usuarios.php");
exit();
?>