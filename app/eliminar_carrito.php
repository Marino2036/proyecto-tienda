<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id > 0 && isset($_SESSION["carrito"][$id])) {
    unset($_SESSION["carrito"][$id]);
}

header("Location: carrito.php");
exit();
?>