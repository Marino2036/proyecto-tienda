<?php

require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarTicketPorCorreo($conexion, $compra_id, $usuario_id)
{
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
        return false;
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
            body{
                font-family: Arial, sans-serif;
                color:#111827;
                font-size:13px;
            }

            .encabezado{
                text-align:center;
                margin-bottom:20px;
                border-bottom:2px solid #111827;
                padding-bottom:10px;
            }

            .encabezado h1{
                margin:0;
            }

            table{
                width:100%;
                border-collapse:collapse;
                margin-top:20px;
            }

            th{
                background:#111827;
                color:white;
                padding:10px;
                text-align:left;
            }

            td{
                border-bottom:1px solid #ddd;
                padding:10px;
                vertical-align:middle;
            }

            .imagen-producto{
                width:55px;
                height:55px;
                object-fit:contain;
                border:1px solid #ddd;
                border-radius:6px;
                padding:4px;
            }

            .sin-imagen{
                font-size:11px;
                color:#6b7280;
            }

            .total{
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

            $imagenHtml = '
                <img 
                    class="imagen-producto"
                    src="data:image/jpeg;base64,' . $imagenBase64 . '"
                >
            ';
        }

        $html .= '
            <tr>
                <td>' . $imagenHtml . '</td>
                <td>' . htmlspecialchars($fila["nombre"]) . '</td>
                <td>' . (int)$fila["cantidad"] . '</td>
                <td>$' . number_format((float)$fila["precio_unitario"], 2) . '</td>
                <td>$' . number_format((float)$fila["subtotal"], 2) . '</td>
            </tr>
        ';
    }

    $html .= '
            </tbody>
        </table>

        <div class="total">
            Total: $' . number_format((float)$compra["total"], 2) . '
        </div>

    </body>
    </html>
    ';

    $options = new Options();
    $options->set("isRemoteEnabled", true);
    $options->set("isHtml5ParserEnabled", true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper("A4", "portrait");
    $dompdf->render();

    $pdf = $dompdf->output();

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;

        $mail->Username = "lonjaamani@gmail.com";
        $mail->Password = "hdvdzjljthnxcpmk";

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = "UTF-8";

        $mail->setFrom(
            "lonjaamani@gmail.com",
            "Tienda de electrónicos"
        );

        $mail->addAddress(
            $compra["correo"],
            $compra["nombre"]
        );

        $mail->isHTML(true);
        $mail->Subject = "Ticket de compra #" . $compra["id"];

        $mail->Body = "
            <h2>Gracias por tu compra</h2>
            <p>Adjuntamos tu ticket en PDF.</p>
            <p><strong>Total:</strong> $" . number_format((float)$compra["total"], 2) . "</p>
        ";

        $mail->addStringAttachment(
            $pdf,
            "ticket_compra_" . $compra["id"] . ".pdf",
            "base64",
            "application/pdf"
        );

        $mail->send();

        return true;

    } catch (Exception $e) {

        file_put_contents(
            __DIR__ . "/error_correo.txt",
            "Error PHPMailer: " . $mail->ErrorInfo . PHP_EOL,
            FILE_APPEND
        );

        return false;
    }
}
?>