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

$sql = "SELECT * FROM bitacora_login ORDER BY id DESC";
$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora de accesos</title>
    <link rel="stylesheet" href="../../css/estilos.css?v=4">
</head>
<body class="panel-body">

<header>
    <h1>Bitácora de accesos</h1>

    <div class="header-acciones">
        <a class="btn-header-secundario" href="panel_admin.php">Regresar al panel</a>
        <a class="btn-salir" href="../logout.php">Cerrar sesión</a>
    </div>
</header>

<div class="contenedor">

    <section class="bienvenida">
        <div class="bienvenida-top">
            <div>
                <h2>Registro de intentos de acceso</h2>
                <p>Aquí se muestra quién intentó entrar al sistema, aunque no esté registrado.</p>
            </div>

            <div class="admin-etiqueta">
                <span>Administrador</span>
            </div>
        </div>
    </section>

    <section class="seccion-productos">
        <div class="seccion-encabezado">
            <h2>Bitácora</h2>
            <p>Historial de accesos exitosos, contraseñas incorrectas y usuarios no registrados.</p>
        </div>

       <div class="tabla-contenedor">
    <table class="tabla-bitacora">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario intentado</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Estado</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php while ($fila = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $fila["id"]; ?></td>
                            <td><?php echo htmlspecialchars($fila["usuario_intentado"]); ?></td>
                            <td><?php echo $fila["fecha"]; ?></td>
                            <td><?php echo $fila["hora"]; ?></td>
                            <td>
    <?php
        $estado = $fila["estado"];
        $claseEstado = "estado-alerta";

        if ($estado === "Acceso exitoso") {
            $claseEstado = "estado-ok";
        } elseif ($estado === "Contraseña incorrecta") {
            $claseEstado = "estado-error";
        }
    ?>

    <span class="<?php echo $claseEstado; ?>">
        <?php echo htmlspecialchars($estado); ?>
    </span>
</td>
                            <td><?php echo htmlspecialchars($fila["ip"]); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No hay registros en la bitácora.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

</body>
</html>