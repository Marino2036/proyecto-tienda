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

/*
LOCAL:
http://localhost/proyecto

CUANDO LO SUBAS A LA NUBE:
https://tu-proyecto.onrender.com
*/
$baseUrl = "https://proyecto-tienda.onrender.com";

$busqueda = trim($_GET["busqueda"] ?? "");

$sql = "SELECT * FROM productos";
$tipos = "";
$parametros = [];

if ($busqueda !== "") {
    $sql .= " WHERE nombre LIKE ? OR descripcion LIKE ?";
    $like = "%" . $busqueda . "%";
    $tipos = "ss";
    $parametros[] = $like;
    $parametros[] = $like;
}

$sql .= " ORDER BY id DESC";

$stmt = $conexion->prepare($sql);

if ($tipos !== "") {
    $stmt->bind_param($tipos, ...$parametros);
}

$stmt->execute();
$resultado = $stmt->get_result();
$totalProductos = $resultado ? $resultado->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestionar productos</title>
<link rel="stylesheet" href="../../css/estilos.css?v=22">

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
.qr-box{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:8px;
}

.qr-producto{
    width:96px;
    height:96px;
    background:white;
    padding:8px;
    border-radius:14px;
    border:1px solid #e5e7eb;
    box-shadow:0 8px 18px rgba(0,0,0,.08);
    display:flex;
    align-items:center;
    justify-content:center;
}

.qr-producto img{
    width:90px !important;
    height:90px !important;
}

.btn-ver-qr{
    font-size:12px;
    background:#0f172a;
    color:white;
    padding:6px 10px;
    border-radius:8px;
    text-decoration:none;
    font-weight:700;
}

.btn-ver-qr:hover{
    background:#2563eb;
}
</style>
</head>

<body class="panel-body">

<header>
    <h1>Gestión de productos</h1>

    <div class="header-acciones">
        <a class="btn-header-secundario" href="agregar_producto.php">+ Agregar producto</a>
        <a class="btn-salir" href="panel_admin.php">Volver</a>
    </div>
</header>

<div class="contenedor">

    <section class="bienvenida">
        <div class="bienvenida-top">
            <div>
                <h2>Tabla de administración</h2>
                <p>Aquí puedes revisar, buscar, editar, eliminar y generar QR de productos.</p>
            </div>

            <div class="admin-etiqueta">
                <span><?php echo $totalProductos; ?> resultado(s)</span>
            </div>
        </div>
    </section>

    <section class="seccion-productos">
        <div class="seccion-encabezado">
            <div>
                <h2>Listado de productos</h2>
                <p>Cada producto genera su propio QR automáticamente</p>
            </div>
        </div>

        <form method="GET" class="barra-busqueda barra-busqueda-admin">
            <input
                type="text"
                name="busqueda"
                value="<?php echo htmlspecialchars($busqueda); ?>"
                placeholder="Buscar producto en administración..."
            >

            <button type="submit">Buscar</button>

            <?php if ($busqueda !== "") { ?>
                <a class="btn-limpiar-busqueda" href="gestionar_productos.php">Limpiar</a>
            <?php } ?>
        </form>

        <div class="tabla-contenedor tabla-amigable">
            <table class="tabla-productos">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Imagen</th>
                        <th>Estado</th>
                        <th>Actualizar</th>
                        <th>Eliminar</th>
                        <th>QR</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($resultado && $resultado->num_rows > 0) { ?>
                    <?php $indice = 1; ?>

                    <?php while ($fila = $resultado->fetch_assoc()) { 
                        $productoId = (int)$fila["id"];
                        $urlQR = $baseUrl . "/app/ver_producto_qr.php?id=" . $productoId;
                    ?>

                    <tr>
                        <td>
                            <span class="indice-tabla"><?php echo $indice; ?></span>
                        </td>

                        <td class="celda-producto">
                            <strong><?php echo htmlspecialchars($fila["nombre"]); ?></strong>
                        </td>

                        <td class="celda-descripcion">
                            <?php echo htmlspecialchars($fila["descripcion"]); ?>
                        </td>

                        <td>
                            <span class="precio-tabla">
                                $<?php echo number_format((float)$fila["precio"], 2); ?>
                            </span>
                        </td>

                        <td>
                            <span class="stock-badge <?php echo ((int)$fila["stock"] > 0) ? 'stock-ok' : 'stock-vacio'; ?>">
                                <?php echo (int)$fila["stock"]; ?>
                            </span>
                        </td>

                        <td>
                            <img
                                class="miniatura-tabla"
                                src="data:image/jpeg;base64,<?php echo base64_encode($fila['imagen']); ?>"
                                alt="Imagen de <?php echo htmlspecialchars($fila["nombre"]); ?>"
                            >
                        </td>

                        <td>
                            <span class="estado-badge <?php echo ((int)$fila["activo"] === 1) ? 'estado-activo' : 'estado-inactivo'; ?>">
                                <?php echo ((int)$fila["activo"] === 1) ? "Activo" : "Inactivo"; ?>
                            </span>
                        </td>

                        <td>
                            <a class="btn-tabla btn-editar" href="editar_producto.php?id=<?php echo $productoId; ?>">
                                Actualizar
                            </a>
                        </td>

                        <td>
                            <a class="btn-tabla btn-eliminar"
                               href="#"
                               onclick="abrirModalEliminar('eliminar_producto.php?id=<?php echo $productoId; ?>', '<?php echo htmlspecialchars($fila['nombre'], ENT_QUOTES); ?>'); return false;">
                                Eliminar
                            </a>
                        </td>

                        <td>
                            <div class="qr-box">
                                <div
                                    class="qr-producto"
                                    data-url="<?php echo htmlspecialchars($urlQR, ENT_QUOTES); ?>">
                                </div>

                                <a class="btn-ver-qr" href="<?php echo htmlspecialchars($urlQR); ?>" target="_blank">
                                    Ver
                                </a>
                            </div>
                        </td>
                    </tr>

                    <?php $indice++; ?>
                    <?php } ?>

                <?php } else { ?>
                    <tr>
                        <td colspan="10">
                            <div class="sin-productos-tabla">No se encontraron productos.</div>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="modalEliminar" class="modal-confirmacion">
    <div class="modal-confirmacion__overlay" onclick="cerrarModalEliminar()"></div>

    <div class="modal-confirmacion__box" role="dialog" aria-modal="true" aria-labelledby="tituloModalEliminar">
        <div class="modal-confirmacion__icono">🗑️</div>

        <h3 id="tituloModalEliminar">Confirmar eliminación</h3>

        <p id="textoModalEliminar">
            ¿Seguro que deseas eliminar este producto?
        </p>

        <div class="modal-confirmacion__acciones">
            <button type="button" class="btn-modal-cancelar" onclick="cerrarModalEliminar()">
                Cancelar
            </button>

            <a id="btnConfirmarEliminar" href="#" class="btn-modal-eliminar">
                Sí, eliminar
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){
    document.querySelectorAll(".qr-producto").forEach(function(elemento){
        const url = elemento.dataset.url;

        new QRCode(elemento, {
            text: url,
            width: 90,
            height: 90,
            correctLevel: QRCode.CorrectLevel.H
        });
    });
});

function abrirModalEliminar(url, nombreProducto) {
    const modal = document.getElementById("modalEliminar");
    const texto = document.getElementById("textoModalEliminar");
    const btnConfirmar = document.getElementById("btnConfirmarEliminar");

    texto.textContent = '¿Seguro que deseas eliminar el producto "' + nombreProducto + '"? Esta acción no se puede deshacer.';
    btnConfirmar.setAttribute("href", url);

    modal.classList.add("mostrar");
    document.body.classList.add("body-modal-abierto");
}

function cerrarModalEliminar() {
    const modal = document.getElementById("modalEliminar");
    modal.classList.remove("mostrar");
    document.body.classList.remove("body-modal-abierto");
}

document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
        cerrarModalEliminar();
    }
});
</script>

</body>
</html>