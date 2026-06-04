<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: panel.php");
    exit();
}

$producto_id = isset($_POST["producto_id"]) ? (int)$_POST["producto_id"] : 0;
$cantidad = isset($_POST["cantidad"]) ? (int)$_POST["cantidad"] : 1;
$scroll_pos = isset($_POST["scroll_pos"]) ? (int)$_POST["scroll_pos"] : 0;
$busqueda_actual = trim($_POST["busqueda_actual"] ?? "");

function redirigirAlPanel(string $mensaje, int $scroll_pos = 0, string $busqueda_actual = ""): void {
    $_SESSION["mensaje_carrito"] = $mensaje;

    $parametros = [];

    if ($busqueda_actual !== "") {
        $parametros["busqueda"] = $busqueda_actual;
    }

    if ($scroll_pos > 0) {
        $parametros["scroll"] = $scroll_pos;
    }

    $url = "panel.php";
    if (!empty($parametros)) {
        $url .= "?" . http_build_query($parametros);
    }

    header("Location: " . $url);
    exit();
}

if ($producto_id <= 0 || $cantidad <= 0) {
    redirigirAlPanel("Datos inválidos para agregar al carrito.", $scroll_pos, $busqueda_actual);
}

$sql = "SELECT id, nombre, precio, stock, activo FROM productos WHERE id = ? LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    redirigirAlPanel("Producto no encontrado.", $scroll_pos, $busqueda_actual);
}

$producto = $resultado->fetch_assoc();

if ((int)$producto["activo"] !== 1) {
    redirigirAlPanel("Ese producto no está disponible.", $scroll_pos, $busqueda_actual);
}

if ($cantidad > (int)$producto["stock"]) {
    $cantidad = (int)$producto["stock"];
}

if ($cantidad <= 0) {
    redirigirAlPanel("No hay stock disponible.", $scroll_pos, $busqueda_actual);
}

if (!isset($_SESSION["carrito"]) || !is_array($_SESSION["carrito"])) {
    $_SESSION["carrito"] = [];
}

if (isset($_SESSION["carrito"][$producto_id])) {
    $_SESSION["carrito"][$producto_id]["cantidad"] += $cantidad;

    if ($_SESSION["carrito"][$producto_id]["cantidad"] > (int)$producto["stock"]) {
        $_SESSION["carrito"][$producto_id]["cantidad"] = (int)$producto["stock"];
    }
} else {
    $_SESSION["carrito"][$producto_id] = [
        "producto_id" => (int)$producto["id"],
        "nombre" => $producto["nombre"],
        "precio" => (float)$producto["precio"],
        "cantidad" => $cantidad
    ];
}

redirigirAlPanel("Se agregó al carrito: " . $producto["nombre"], $scroll_pos, $busqueda_actual);
?>