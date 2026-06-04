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

if ($id === (int)$_SESSION["id"]) {
    header("Location: usuarios.php");
    exit();
}

$sql = "DELETE FROM usuarios WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: usuarios.php");
exit();
?>