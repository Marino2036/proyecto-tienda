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

$busqueda = trim($_GET["busqueda"] ?? "");
$fecha = trim($_GET["fecha"] ?? "");

$sql = "SELECT * FROM bitacora_qr WHERE 1=1";
$tipos = "";
$parametros = [];

if ($busqueda !== "") {
    $sql .= " AND (producto_nombre LIKE ? OR ip LIKE ? OR url_visitada LIKE ?)";
    $like = "%" . $busqueda . "%";
    $tipos .= "sss";
    $parametros[] = $like;
    $parametros[] = $like;
    $parametros[] = $like;
}

if ($fecha !== "") {
    $sql .= " AND fecha = ?";
    $tipos .= "s";
    $parametros[] = $fecha;
}

$sql .= " ORDER BY id DESC";

$stmt = $conexion->prepare($sql);
if ($tipos !== "") {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$resultado = $stmt->get_result();

$sqlTotal = "SELECT COUNT(*) AS total FROM bitacora_qr";
$totalResultado = $conexion->query($sqlTotal);
$totalAccesos = 0;
if ($totalResultado) {
    $totalFila = $totalResultado->fetch_assoc();
    $totalAccesos = (int)$totalFila["total"];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bitácora de accesos QR</title>
<link rel="stylesheet" href="../../css/estilos.css?v=3">

<style>
.resumen-bitacora{
    background:white;
    border-radius:20px;
    padding:22px;
    margin-bottom:20px;
    box-shadow:0 12px 28px rgba(0,0,0,.08);
    border-left:7px solid #2563eb;
}

.resumen-bitacora h2{
    margin:0 0 8px;
    color:#0f172a;
}

.resumen-bitacora p{
    margin:0;
    color:#64748b;
}

.filtros-bitacora{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    background:white;
    padding:18px;
    border-radius:18px;
    margin-bottom:20px;
    box-shadow:0 8px 20px rgba(0,0,0,.06);
}

.filtros-bitacora input,
.filtros-bitacora button,
.filtros-bitacora a{
    padding:10px 12px;
    border-radius:10px;
    border:1px solid #cbd5e1;
    font-family:'Segoe UI', Arial, sans-serif;
}

.filtros-bitacora button{
    background:#2563eb;
    color:white;
    font-weight:800;
    cursor:pointer;
    border:none;
}

.filtros-bitacora a{
    background:#64748b;
    color:white;
    text-decoration:none;
    font-weight:800;
    border:none;
}

.user-agent-celda{
    max-width:330px;
    word-break:break-word;
    font-size:12px;
}

.url-celda{
    max-width:280px;
    word-break:break-word;
    font-size:12px;
}
</style>
</head>

<body class="panel-body">

<header>
    <h1>Bitácora de accesos QR</h1>

    <div class="header-acciones">
        <a class="btn-header-secundario" href="panel_admin.php">Volver al panel</a>
        <a class="btn-salir" href="../logout.php">Cerrar sesión</a>
    </div>
</header>

<div class="contenedor">

    <section class="resumen-bitacora">
        <h2>📱 Accesos por QR</h2>
        <p>Total de visitas registradas: <strong><?php echo $totalAccesos; ?></strong></p>
    </section>

    <form method="GET" class="filtros-bitacora">
        <input
            type="text"
            name="busqueda"
            value="<?php echo htmlspecialchars($busqueda); ?>"
            placeholder="Buscar por producto, IP o URL"
        >

        <input
            type="date"
            name="fecha"
            value="<?php echo htmlspecialchars($fecha); ?>"
        >

        <button type="submit">Filtrar</button>
        <a href="bitacora_qr.php">Limpiar</a>
    </form>

    <div class="tabla-contenedor">
        <table class="tabla-productos">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>IP</th>
                    <th>Dispositivo/Navegador</th>
                    <th>URL visitada</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($resultado && $resultado->num_rows > 0) { ?>
                <?php while ($fila = $resultado->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo (int)$fila["id"]; ?></td>
                        <td><?php echo htmlspecialchars($fila["producto_nombre"]); ?></td>
                        <td><?php echo htmlspecialchars($fila["fecha"]); ?></td>
                        <td><?php echo htmlspecialchars($fila["hora"]); ?></td>
                        <td><?php echo htmlspecialchars($fila["ip"]); ?></td>
                        <td class="user-agent-celda"><?php echo htmlspecialchars($fila["user_agent"]); ?></td>
                        <td class="url-celda"><?php echo htmlspecialchars($fila["url_visitada"]); ?></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="7">Todavía no hay accesos por QR.</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
