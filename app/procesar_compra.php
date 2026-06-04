<?php
session_start();
require_once "../config/db.php";
require_once "enviar_ticket_correo.php";

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION["carrito"])) {
    header("Location: carrito.php");
    exit();
}

$carrito = $_SESSION["carrito"];
$usuario_id = (int)$_SESSION["id"];
$total = 0;

$conexion->begin_transaction();

try {
    foreach ($carrito as $item) {
        $producto_id = (int)$item["producto_id"];
        $cantidad = (int)$item["cantidad"];

        $sqlProducto = "SELECT stock, precio, activo FROM productos WHERE id = ? LIMIT 1";
        $stmtProducto = $conexion->prepare($sqlProducto);
        $stmtProducto->bind_param("i", $producto_id);
        $stmtProducto->execute();
        $resultadoProducto = $stmtProducto->get_result();

        if ($resultadoProducto->num_rows === 0) {
            throw new Exception("Producto no encontrado.");
        }

        $productoDB = $resultadoProducto->fetch_assoc();

        if ((int)$productoDB["activo"] !== 1) {
            throw new Exception("Producto inactivo.");
        }

        if ((int)$productoDB["stock"] < $cantidad) {
            throw new Exception("Stock insuficiente para uno de los productos.");
        }

        $total += ((float)$productoDB["precio"] * $cantidad);
    }

    $sqlCompra = "INSERT INTO compras (usuario_id, total) VALUES (?, ?)";
    $stmtCompra = $conexion->prepare($sqlCompra);
    $stmtCompra->bind_param("id", $usuario_id, $total);
    $stmtCompra->execute();

    $compra_id = $conexion->insert_id;

    foreach ($carrito as $item) {
        $producto_id = (int)$item["producto_id"];
        $cantidad = (int)$item["cantidad"];

        $sqlProducto = "SELECT stock, precio FROM productos WHERE id = ? LIMIT 1";
        $stmtProducto = $conexion->prepare($sqlProducto);
        $stmtProducto->bind_param("i", $producto_id);
        $stmtProducto->execute();
        $resultadoProducto = $stmtProducto->get_result();
        $productoDB = $resultadoProducto->fetch_assoc();

        $precio_unitario = (float)$productoDB["precio"];
        $subtotal = $precio_unitario * $cantidad;
        $nuevoStock = (int)$productoDB["stock"] - $cantidad;

        $sqlDetalle = "INSERT INTO detalle_compra (compra_id, producto_id, cantidad, precio_unitario, subtotal)
                       VALUES (?, ?, ?, ?, ?)";
        $stmtDetalle = $conexion->prepare($sqlDetalle);
        $stmtDetalle->bind_param("iiidd", $compra_id, $producto_id, $cantidad, $precio_unitario, $subtotal);
        $stmtDetalle->execute();

        $sqlActualizar = "UPDATE productos SET stock = ? WHERE id = ?";
        $stmtActualizar = $conexion->prepare($sqlActualizar);
        $stmtActualizar->bind_param("ii", $nuevoStock, $producto_id);
        $stmtActualizar->execute();
    }

   $conexion->commit();

$ticketEnviado = enviarTicketPorCorreo($conexion, $compra_id, $usuario_id);

unset($_SESSION["carrito"]);

if ($ticketEnviado) {
    header("Location: mis_compras.php?ticket=enviado");
} else {
    header("Location: mis_compras.php?ticket=error");
}
exit();

} catch (Exception $e) {
    $conexion->rollback();
    echo "Error al procesar la compra: " . htmlspecialchars($e->getMessage());
}
?>