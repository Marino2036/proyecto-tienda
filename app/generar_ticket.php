<?php
session_start();
require_once "../config/db.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET["id"])) {
    die("No se recibió la compra.");
}

$compra_id = (int)$_GET["id"];
$usuario_id = (int)$_SESSION["id"];

$sqlCompra = "
    SELECT c.*, u.nombre, u.correo
    FROM compras c
    INNER JOIN usuarios u ON c.usuario_id = u.id
    WHERE c.id = ? AND c.usuario_id = ?
    LIMIT 1
";

$stmtCompra = $conexion->prepare($sqlCompra);
$stmtCompra->bind_param("ii", $compra_id, $usuario_id);
$stmtCompra->execute();
$resultadoCompra = $stmtCompra->get_result();

if ($resultadoCompra->num_rows === 0) {
    die("Compra no encontrada.");
}

$compra = $resultadoCompra->fetch_assoc();

$sqlDetalle = "
    SELECT dc.*, p.nombre, p.imagen
    FROM detalle_compra dc
    INNER JOIN productos p ON dc.producto_id = p.id
    WHERE dc.compra_id = ?
";

$stmtDetalle = $conexion->prepare($sqlDetalle);
$stmtDetalle->bind_param("i", $compra_id);
$stmtDetalle->execute();
$detalles = $stmtDetalle->get_result();

$html = '
<html>
<head>
<meta charset="UTF-8">
<style>
body { 
    font-family: Arial, sans-serif; 
    font-size: 13px; 
    color: #111827; 
}

.encabezado { 
    text-align:center; 
    margin-bottom:20px; 
    border-bottom:2px solid #111827; 
    padding-bottom:10px; 
}

table { 
    width:100%; 
    border-collapse:collapse; 
    margin-top:20px; 
}

th { 
    background:#111827; 
    color:white; 
    padding:10px; 
    text-align:left; 
}

td { 
    border-bottom:1px solid #ddd; 
    padding:10px; 
    vertical-align: middle;
}

.imagen-producto {
    width: 55px;
    height: 55px;
    object-fit: contain;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 4px;
}

.sin-imagen {
    font-size: 11px;
    color: #6b7280;
}

.total { 
    margin-top:20px; 
    text-align:right; 
    font-size:18px; 
    font-weight:bold; 
}
</style>
</head>
<body>

<div class="encabezado">
    <h1>Tienda de electrónicos</h1>
    <p>Ticket de compra</p>
</div>

<p><strong>Compra:</strong> #' . $compra["id"] . '</p>
<p><strong>Cliente:</strong> ' . htmlspecialchars($compra["nombre"]) . '</p>
<p><strong>Correo:</strong> ' . htmlspecialchars($compra["correo"]) . '</p>
<p><strong>Fecha:</strong> ' . date("d/m/Y H:i", strtotime($compra["fecha_compra"])) . '</p>

<table>
<thead>
<tr>
    <th>Imagen</th>
    <th>Producto</th>
    <th>Cantidad</th>
    <th>Precio</th>
    <th>Subtotal</th>
</tr>
</thead>
<tbody>
';

while ($fila = $detalles->fetch_assoc()) {

    $imagenHtml = '<span class="sin-imagen">Sin imagen</span>';

    if (!empty($fila["imagen"])) {
        $imagenBase64 = base64_encode($fila["imagen"]);
        $imagenHtml = '<img class="imagen-producto" src="data:image/jpeg;base64,' . $imagenBase64 . '">';
    }

    $html .= '
    <tr>
        <td>' . $imagenHtml . '</td>
        <td>' . htmlspecialchars($fila["nombre"]) . '</td>
        <td>' . (int)$fila["cantidad"] . '</td>
        <td>$' . number_format((float)$fila["precio_unitario"], 2) . '</td>
        <td>$' . number_format((float)$fila["subtotal"], 2) . '</td>
    </tr>';
}

$html .= '
</tbody>
</table>

<div class="total">
    Total: $' . number_format((float)$compra["total"], 2) . '
</div>

</body>
</html>';

$options = new Options();
$options->set("isRemoteEnabled", true);
$options->set("isHtml5ParserEnabled", true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "portrait");
$dompdf->render();

$dompdf->stream("ticket_compra_" . $compra_id . ".pdf", ["Attachment" => true]);
exit();
?>