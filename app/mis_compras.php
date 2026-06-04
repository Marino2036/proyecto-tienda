<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

$usuario_id = (int)$_SESSION["id"];

$sql = "SELECT * FROM compras WHERE usuario_id = ? ORDER BY id DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mis compras</title>
<link rel="stylesheet" href="../css/estilos.css?v=10">

<style>
.compras-header-box{
    background:white;
    padding:25px;
    border-radius:22px;
    margin-bottom:25px;
    box-shadow:0 12px 30px rgba(0,0,0,.08);
    border-left:7px solid #2563eb;
}

.compras-header-box h2{
    margin:0 0 8px;
    color:#0f172a;
}

.compras-header-box p{
    margin:0;
    color:#64748b;
}

.calificacion-lista{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.producto-calificar{
    background:#ffffff;
    border:1px solid #e5e7eb;
    border-radius:18px;
    padding:16px;
    box-shadow:0 8px 20px rgba(0,0,0,.05);
}

.producto-nombre{
    display:flex;
    align-items:center;
    gap:8px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:10px;
}

.producto-nombre span{
    background:#eff6ff;
    color:#2563eb;
    padding:6px 9px;
    border-radius:10px;
}

.calificar-box{
    background:linear-gradient(135deg,#f8fafc,#eef5ff);
    padding:15px;
    border-radius:16px;
    border:1px solid #dbeafe;
}

.estrellas{
    display:flex;
    flex-direction:row-reverse;
    justify-content:flex-end;
    gap:5px;
    margin-bottom:12px;
}

.estrellas input{
    display:none;
}

.estrellas label{
    font-size:30px;
    cursor:pointer;
    color:#cbd5e1;
    transition:.2s ease;
}

.estrellas label:hover,
.estrellas label:hover ~ label,
.estrellas input:checked ~ label{
    color:#facc15;
    transform:scale(1.08);
}

.calificar-box textarea{
    width:100%;
    min-height:70px;
    resize:vertical;
    margin-top:6px;
    padding:11px;
    border-radius:12px;
    border:1px solid #cbd5e1;
    font-family:'Segoe UI', Arial, sans-serif;
    font-size:14px;
    box-sizing:border-box;
}

.btn-calificar{
    margin-top:10px;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:white;
    border:none;
    padding:10px 14px;
    border-radius:12px;
    cursor:pointer;
    font-weight:800;
    box-shadow:0 8px 18px rgba(37,99,235,.25);
}

.btn-calificar:hover{
    transform:translateY(-1px);
}

.calificado{
    background:#ecfdf5;
    border:1px solid #bbf7d0;
    color:#16a34a;
    padding:10px;
    border-radius:12px;
    font-weight:800;
    margin:0;
}

.btn-ticket{
    display:inline-block;
    background:#0f172a;
    color:white;
    text-decoration:none;
    padding:9px 13px;
    border-radius:10px;
    font-weight:700;
}

.btn-ticket:hover{
    background:#2563eb;
}

.tabla-productos td{
    vertical-align:top;
}

@media(max-width:900px){
    .tabla-contenedor{
        overflow-x:auto;
    }

    .estrellas label{
        font-size:26px;
    }
}
</style>
</head>

<body class="panel-body">

<header>
    <h1>Mis compras</h1>
    <a class="btn-salir" href="panel.php">Volver</a>
</header>

<div class="contenedor">

    <section class="compras-header-box">
        <h2>Historial de compras</h2>
        <p>Consulta tus tickets y califica los productos que compraste.</p>
    </section>

    <?php if (isset($_GET["calificacion"]) && $_GET["calificacion"] === "ok") { ?>
        <div class="mensaje-exito">Calificación guardada correctamente.</div>
    <?php } ?>

    <div class="tabla-contenedor">
        <table class="tabla-productos">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha de compra</th>
                    <th>Total</th>
                    <th>Ticket</th>
                    <th>Calificar productos</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($resultado && $resultado->num_rows > 0) { ?>
                <?php $i = 1; ?>

                <?php while ($fila = $resultado->fetch_assoc()) { 
                    $compra_id = (int)$fila["id"];

                    $sqlDetalle = "
                        SELECT dc.producto_id, p.nombre
                        FROM detalle_compra dc
                        INNER JOIN productos p ON dc.producto_id = p.id
                        WHERE dc.compra_id = ?
                    ";

                    $stmtDetalle = $conexion->prepare($sqlDetalle);
                    $stmtDetalle->bind_param("i", $compra_id);
                    $stmtDetalle->execute();
                    $productos = $stmtDetalle->get_result();
                ?>

                <tr>
                    <td><?php echo $i; ?></td>

                    <td>
                        <?php echo date("d/m/Y H:i", strtotime($fila["fecha_compra"])); ?>
                    </td>

                    <td>
                        $<?php echo number_format((float)$fila["total"], 2); ?>
                    </td>

                    <td>
                        <a class="btn-ticket" href="generar_ticket.php?id=<?php echo $compra_id; ?>">
                            Descargar PDF
                        </a>
                    </td>

                    <td>
                        <div class="calificacion-lista">
                            <?php while ($producto = $productos->fetch_assoc()) { 
                                $producto_id = (int)$producto["producto_id"];

                                $sqlExiste = "
                                    SELECT id 
                                    FROM calificaciones 
                                    WHERE usuario_id = ? 
                                    AND producto_id = ? 
                                    AND compra_id = ?
                                ";

                                $stmtExiste = $conexion->prepare($sqlExiste);
                                $stmtExiste->bind_param("iii", $usuario_id, $producto_id, $compra_id);
                                $stmtExiste->execute();
                                $yaCalificado = $stmtExiste->get_result();

                                $grupoEstrellas = "rating_" . $compra_id . "_" . $producto_id;
                            ?>

                            <div class="producto-calificar">
                                <div class="producto-nombre">
                                    <span>★</span>
                                    <?php echo htmlspecialchars($producto["nombre"]); ?>
                                </div>

                                <?php if ($yaCalificado->num_rows > 0) { ?>
                                    <p class="calificado">✅ Ya calificaste este producto</p>
                                <?php } else { ?>

                                <form class="calificar-box" action="guardar_calificacion.php" method="POST">
                                    <input type="hidden" name="compra_id" value="<?php echo $compra_id; ?>">
                                    <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">

                                    <div class="estrellas">
                                        <input type="radio" id="<?php echo $grupoEstrellas; ?>_5" name="calificacion" value="5" required>
                                        <label for="<?php echo $grupoEstrellas; ?>_5">★</label>

                                        <input type="radio" id="<?php echo $grupoEstrellas; ?>_4" name="calificacion" value="4">
                                        <label for="<?php echo $grupoEstrellas; ?>_4">★</label>

                                        <input type="radio" id="<?php echo $grupoEstrellas; ?>_3" name="calificacion" value="3">
                                        <label for="<?php echo $grupoEstrellas; ?>_3">★</label>

                                        <input type="radio" id="<?php echo $grupoEstrellas; ?>_2" name="calificacion" value="2">
                                        <label for="<?php echo $grupoEstrellas; ?>_2">★</label>

                                        <input type="radio" id="<?php echo $grupoEstrellas; ?>_1" name="calificacion" value="1">
                                        <label for="<?php echo $grupoEstrellas; ?>_1">★</label>
                                    </div>

                                    <textarea name="comentario" placeholder="Escribe un comentario opcional sobre este producto"></textarea>

                                    <button class="btn-calificar" type="submit">
                                        Enviar calificación
                                    </button>
                                </form>

                                <?php } ?>
                            </div>

                            <?php } ?>
                        </div>
                    </td>
                </tr>

                <?php $i++; ?>
                <?php } ?>

            <?php } else { ?>
                <tr>
                    <td colspan="5">Todavía no tienes compras registradas.</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>