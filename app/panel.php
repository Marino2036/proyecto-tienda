<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

$busqueda = trim($_GET["busqueda"] ?? "");

$sql = "SELECT * FROM productos WHERE activo = 1";
$tipos = "";
$parametros = [];

if ($busqueda !== "") {
    $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
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

$cantidadCarrito = 0;
if (isset($_SESSION["carrito"]) && is_array($_SESSION["carrito"])) {
    foreach ($_SESSION["carrito"] as $item) {
        $cantidadCarrito += (int)$item["cantidad"];
    }
}

$mensajeCarrito = $_SESSION["mensaje_carrito"] ?? "";
unset($_SESSION["mensaje_carrito"]);

$scrollGuardado = isset($_GET["scroll"]) ? (int)$_GET["scroll"] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda de electrónicos</title>
    <link rel="stylesheet" href="../css/estilos.css?v=23">
</head>
<body class="panel-body">

<header>
    <h1>Tienda de electrónicos</h1>

    <div class="header-acciones">
        <a class="btn-header-secundario" href="mis_compras.php">Mis compras</a>

        <a class="btn-carrito-header" href="carrito.php" title="Ver carrito">
            <span class="icono-carrito" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M3 4H5L7.2 14.4C7.4 15.3 8.1 16 9 16H17.8C18.7 16 19.5 15.4 19.7 14.5L21 8H6.2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="10" cy="20" r="1.6" fill="currentColor"/>
                    <circle cx="18" cy="20" r="1.6" fill="currentColor"/>
                </svg>
            </span>
            <span>Carrito</span>
            <span class="contador-carrito"><?php echo $cantidadCarrito; ?></span>
        </a>

        <a class="btn-salir" href="logout.php">Cerrar sesión</a>
    </div>
</header>

<?php if ($mensajeCarrito !== "") { ?>
    <div id="toastCarrito" class="toast-flotante toast-exito">
        <div class="toast-contenido">
            <span class="toast-icono">✔</span>
            <span><?php echo htmlspecialchars($mensajeCarrito); ?></span>
        </div>
        <button type="button" class="toast-cerrar" onclick="cerrarToast()">×</button>
    </div>
<?php } ?>

<div class="contenedor">

    <section class="bienvenida">
        <div class="bienvenida-top">
            <div>
                <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION["nombre"]); ?></h2>
                <p>Explora los productos disponibles y encuentra lo que necesitas fácilmente.</p>
            </div>

            <?php if (isset($_SESSION["rol"]) && $_SESSION["rol"] === "admin") { ?>
                <div class="admin-acceso">
                    <a class="btn-admin" href="admin/panel_admin.php">Panel de administración</a>
                </div>
            <?php } ?>
        </div>
    </section>

    <section class="seccion-productos">
        <div class="seccion-encabezado">
            <div>
                <h2>Productos disponibles</h2>
                <p>Busca por nombre o descripción</p>
            </div>
        </div>

        <form method="GET" class="barra-busqueda">
            <input
                type="text"
                name="busqueda"
                value="<?php echo htmlspecialchars($busqueda); ?>"
                placeholder="Buscar producto..."
            >
            <button type="submit">Buscar</button>
            <?php if ($busqueda !== "") { ?>
                <a class="btn-limpiar-busqueda" href="panel.php">Limpiar</a>
            <?php } ?>
        </form>

        <div class="productos productos-catalogo">
            <?php if ($resultado && $resultado->num_rows > 0) { ?>
                <?php while ($producto = $resultado->fetch_assoc()) { ?>
                    <article class="card card-catalogo" id="producto_<?php echo $producto['id']; ?>">
                        <div class="badge-stock en-stock">Disponible</div>

                        <div class="marco-imagen">
                            <img
                                src="ver_imagen.php?id=<?php echo $producto['id']; ?>"
                                alt="<?php echo htmlspecialchars($producto["nombre"]); ?>"
                            >
                        </div>

                        <div class="card-contenido">
                            <h3><?php echo htmlspecialchars($producto["nombre"]); ?></h3>
                            <p class="descripcion-producto"><?php echo htmlspecialchars($producto["descripcion"]); ?></p>
                            <p class="stock-texto">Stock: <strong><?php echo (int)$producto["stock"]; ?></strong></p>
                            <div class="precio">$<?php echo number_format((float)$producto["precio"], 2); ?></div>

                            <?php if ((int)$producto["stock"] > 0) { ?>
                                <form action="agregar_carrito.php" method="POST" class="form-carrito" onsubmit="guardarPosicionAntesDeEnviar(this)">
                                    <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                    <input type="hidden" name="scroll_pos" value="0">
                                    <input type="hidden" name="busqueda_actual" value="<?php echo htmlspecialchars($busqueda); ?>">

                                    <label>Cantidad</label>

                                    <div class="control-cantidad">
                                        <button type="button" onclick="cambiarCantidad(<?php echo $producto['id']; ?>, -1)">−</button>

                                        <input
                                            type="number"
                                            id="cantidad_<?php echo $producto['id']; ?>"
                                            name="cantidad"
                                            value="1"
                                            min="1"
                                            max="<?php echo (int)$producto['stock']; ?>"
                                            readonly
                                        >

                                        <button type="button" onclick="cambiarCantidad(<?php echo $producto['id']; ?>, 1)">+</button>
                                    </div>

                                    <button type="submit">Agregar al carrito</button>
                                </form>
                            <?php } else { ?>
                                <p class="texto-sin-stock">Sin stock por el momento</p>
                            <?php } ?>
                        </div>
                    </article>
                <?php } ?>
            <?php } else { ?>
                <div class="sin-productos">
                    <p>No se encontraron productos.</p>
                </div>
            <?php } ?>
        </div>
    </section>
</div>

<script>
function cambiarCantidad(id, cambio) {
    const input = document.getElementById("cantidad_" + id);
    let valor = parseInt(input.value) || 1;
    const min = parseInt(input.min) || 1;
    const max = parseInt(input.max) || 999;

    valor += cambio;

    if (valor < min) valor = min;
    if (valor > max) valor = max;

    input.value = valor;
}

function guardarPosicionAntesDeEnviar(formulario) {
    const inputScroll = formulario.querySelector('input[name="scroll_pos"]');
    if (inputScroll) {
        inputScroll.value = window.scrollY || window.pageYOffset || 0;
    }
}

function cerrarToast() {
    const toast = document.getElementById("toastCarrito");
    if (toast) {
        toast.classList.add("toast-ocultar");
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const scrollGuardado = <?php echo $scrollGuardado; ?>;

    if (scrollGuardado > 0) {
        window.scrollTo({
            top: scrollGuardado,
            behavior: "auto"
        });

        const url = new URL(window.location.href);
        url.searchParams.delete("scroll");
        window.history.replaceState({}, document.title, url.pathname + (url.searchParams.toString() ? "?" + url.searchParams.toString() : ""));
    }

    const toast = document.getElementById("toastCarrito");
    if (toast) {
        setTimeout(() => {
            cerrarToast();
        }, 2600);
    }
});
</script>

</body>
</html>