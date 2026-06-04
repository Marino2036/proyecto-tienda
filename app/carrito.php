<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

$carrito = $_SESSION["carrito"] ?? [];
$total = 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito</title>
    <link rel="stylesheet" href="../css/estilos.css?v=10">
</head>
<body class="panel-body">

<header>
    <h1>Carrito de compras</h1>
    <a class="btn-salir" href="panel.php">Volver a tienda</a>
</header>

<div class="contenedor">
    <div class="bienvenida">
        <h2>Productos en tu carrito</h2>
    </div>

    <div class="tabla-contenedor">
        <table class="tabla-productos">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Eliminar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($carrito)) { ?>
                    <?php $i = 1; ?>
                    <?php foreach ($carrito as $item) { ?>
                        <?php $subtotal = $item["precio"] * $item["cantidad"]; ?>
                        <?php $total += $subtotal; ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo htmlspecialchars($item["nombre"]); ?></td>
                            <td>$<?php echo number_format((float)$item["precio"], 2); ?></td>
                            <td><?php echo (int)$item["cantidad"]; ?></td>
                            <td>$<?php echo number_format($subtotal, 2); ?></td>
                            <td>
                                <a class="btn-tabla btn-eliminar" href="eliminar_carrito.php?id=<?php echo (int)$item["producto_id"]; ?>">
                                    Quitar
                                </a>
                            </td>
                        </tr>
                        <?php $i++; ?>
                    <?php } ?>
                    <tr>
                        <td colspan="4"><strong>Total</strong></td>
                        <td colspan="2"><strong>$<?php echo number_format($total, 2); ?></strong></td>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td colspan="6">Tu carrito está vacío.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($carrito)) { ?>
        <div style="margin-top:20px;">
            <a class="btn-tabla btn-editar" href="procesar_compra.php">Confirmar compra</a>
        </div>
    <?php } ?>
</div>

</body>
</html>