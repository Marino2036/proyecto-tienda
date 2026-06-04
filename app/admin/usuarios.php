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

$sql = "SELECT * FROM usuarios ORDER BY id DESC";
$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar usuarios</title>
    <link rel="stylesheet" href="../../css/estilos.css?v=9">
</head>
<body class="panel-body">

<header>
    <h1>Gestión de usuarios</h1>
    <a class="btn-salir" href="panel_admin.php">Volver</a>
</header>

<div class="contenedor">
    <div class="bienvenida">
        <h2>Usuarios registrados</h2>
        <p>Desde aquí puedes crear, dar de baja o eliminar usuarios.</p>
        <p style="margin-top:10px;">
            <a href="crear_usuario.php">+ Crear usuario</a>
        </p>
    </div>

    <div class="tabla-contenedor">
        <table class="tabla-productos">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Fecha registro</th>
                    <th>Baja / Alta</th>
                    <th>Eliminar</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0) { ?>
                    <?php $indice = 1; ?>
                    <?php while ($fila = $resultado->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $indice; ?></td>
                            <td><?php echo htmlspecialchars($fila["nombre"]); ?></td>
                            <td><?php echo htmlspecialchars($fila["correo"]); ?></td>
                            <td><?php echo htmlspecialchars($fila["rol"]); ?></td>
                            <td><?php echo ((int)$fila["activo"] === 1) ? "Activo" : "Baja"; ?></td>
                            <td>
                                <?php
                                echo isset($fila["fecha_registro"])
                                    ? date("d/m/Y H:i", strtotime($fila["fecha_registro"]))
                                    : "Sin fecha";
                                ?>
                            </td>
                            <td>
                                <?php if ((int)$fila["id"] !== (int)$_SESSION["id"]) { ?>
                                    <?php if ((int)$fila["activo"] === 1) { ?>
<a class="btn-tabla btn-eliminar"
   href="#"
   onclick="abrirModalEstadoUsuario('baja_usuario.php?id=<?php echo $fila['id']; ?>', '<?php echo htmlspecialchars($fila['nombre'], ENT_QUOTES); ?>', 'baja'); return false;">
   Dar de baja
</a>
                                    <?php } else { ?>
<a class="btn-tabla btn-editar"
   href="#"
   onclick="abrirModalEstadoUsuario('alta_usuario.php?id=<?php echo $fila['id']; ?>', '<?php echo htmlspecialchars($fila['nombre'], ENT_QUOTES); ?>', 'alta'); return false;">
   Reactivar
</a>
                                    <?php } ?>
                                <?php } else { ?>
                                    -
                                <?php } ?>
                            </td>
                            <td>
                                <?php if ((int)$fila["id"] !== (int)$_SESSION["id"]) { ?>
<a class="btn-tabla btn-eliminar"
   href="#"
   onclick="abrirModalEliminarUsuario('eliminar_usuario.php?id=<?php echo $fila['id']; ?>', '<?php echo htmlspecialchars($fila['nombre'], ENT_QUOTES); ?>'); return false;">
   Eliminar
</a>
                                <?php } else { ?>
                                    -
                                <?php } ?>
                            </td>
                        </tr>
                        <?php $indice++; ?>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="8">No hay usuarios registrados.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<div id="modalUsuario" class="modal-confirmacion">
    <div class="modal-confirmacion__overlay" onclick="cerrarModalUsuario()"></div>

    <div class="modal-confirmacion__box">
        <div id="iconoModalUsuario" class="modal-confirmacion__icono">⚠️</div>

        <h3 id="tituloModalUsuario">Confirmación</h3>

        <p id="textoModalUsuario"></p>

        <div class="modal-confirmacion__acciones">
            <button type="button" class="btn-modal-cancelar" onclick="cerrarModalUsuario()">
                Cancelar
            </button>

            <a id="btnConfirmarUsuario" href="#" class="btn-modal-eliminar">
                Confirmar
            </a>
        </div>
    </div>
</div>


<script>
function abrirModalEliminarUsuario(url, nombre) {
    const modal = document.getElementById("modalUsuario");
    const texto = document.getElementById("textoModalUsuario");
    const titulo = document.getElementById("tituloModalUsuario");
    const boton = document.getElementById("btnConfirmarUsuario");
    const icono = document.getElementById("iconoModalUsuario");

    titulo.textContent = "Eliminar usuario";
    texto.textContent = '¿Seguro que deseas eliminar a "' + nombre + '"? Esta acción no se puede deshacer.';
    boton.setAttribute("href", url);
    boton.className = "btn-modal-eliminar";

    icono.textContent = "🗑️";

    modal.classList.add("mostrar");
    document.body.classList.add("body-modal-abierto");
}

function abrirModalEstadoUsuario(url, nombre, tipo) {
    const modal = document.getElementById("modalUsuario");
    const texto = document.getElementById("textoModalUsuario");
    const titulo = document.getElementById("tituloModalUsuario");
    const boton = document.getElementById("btnConfirmarUsuario");
    const icono = document.getElementById("iconoModalUsuario");

    if (tipo === "baja") {
        titulo.textContent = "Dar de baja";
        texto.textContent = '¿Deseas dar de baja a "' + nombre + '"?';
        boton.className = "btn-modal-eliminar";
        icono.textContent = "⚠️";
    } else {
        titulo.textContent = "Reactivar usuario";
        texto.textContent = '¿Deseas reactivar a "' + nombre + '"?';
        boton.className = "btn-modal-confirmar";
        icono.textContent = "✅";
    }

    boton.setAttribute("href", url);

    modal.classList.add("mostrar");
    document.body.classList.add("body-modal-abierto");
}

function cerrarModalUsuario() {
    const modal = document.getElementById("modalUsuario");
    modal.classList.remove("mostrar");
    document.body.classList.remove("body-modal-abierto");
}

document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") cerrarModalUsuario();
});
</script>
</body>
</html>