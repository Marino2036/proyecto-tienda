<?php
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../panel.php");
    exit();
}

require_once "../../config/db.php";

$fechaInicio = $_GET["fecha_inicio"] ?? date("Y-m-01");
$fechaFin = $_GET["fecha_fin"] ?? date("Y-m-t");

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
    $fechaInicio = date("Y-m-01");
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
    $fechaFin = date("Y-m-t");
}

$inicioSQL = $fechaInicio . " 00:00:00";
$finSQL = $fechaFin . " 23:59:59";

function leerDatos($conexion, $sql, $types = "", $params = []) {
    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {
        return [];
    }

    if ($types != "" && count($params) > 0) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $datos = [];
    while ($fila = mysqli_fetch_assoc($res)) {
        $datos[] = $fila;
    }

    return $datos;
}

/* VENTAS POR FECHA */
$datosVentas = leerDatos($conexion, "
    SELECT DATE(fecha_compra) AS fecha, SUM(total) AS ventas
    FROM compras
    WHERE fecha_compra BETWEEN ? AND ?
    GROUP BY DATE(fecha_compra)
    ORDER BY fecha ASC
", "ss", [$inicioSQL, $finSQL]);

$fechas = [];
$ventas = [];

foreach ($datosVentas as $fila) {
    $fechas[] = $fila["fecha"];
    $ventas[] = $fila["ventas"];
}

/* STOCK DE PRODUCTOS */
$datosStock = leerDatos($conexion, "
    SELECT nombre, stock
    FROM productos
    ORDER BY stock ASC
    LIMIT 10
");

$productosStock = [];
$stockProductos = [];

foreach ($datosStock as $fila) {
    $productosStock[] = $fila["nombre"];
    $stockProductos[] = $fila["stock"];
}

/* PRODUCTOS MÁS VENDIDOS */
$datosVendidos = leerDatos($conexion, "
    SELECT p.nombre, SUM(dc.cantidad) AS cantidad_vendida
    FROM detalle_compra dc
    INNER JOIN compras c ON dc.compra_id = c.id
    INNER JOIN productos p ON dc.producto_id = p.id
    WHERE c.fecha_compra BETWEEN ? AND ?
    GROUP BY p.id, p.nombre
    ORDER BY cantidad_vendida DESC
    LIMIT 10
", "ss", [$inicioSQL, $finSQL]);

$productosVendidos = [];
$cantidadesVendidas = [];

foreach ($datosVendidos as $fila) {
    $productosVendidos[] = $fila["nombre"];
    $cantidadesVendidas[] = $fila["cantidad_vendida"];
}

/* COMPRAS POR USUARIO */
$datosUsuarios = leerDatos($conexion, "
    SELECT u.nombre, COUNT(c.id) AS total_compras
    FROM usuarios u
    INNER JOIN compras c ON u.id = c.usuario_id
    WHERE c.fecha_compra BETWEEN ? AND ?
    GROUP BY u.id, u.nombre
    ORDER BY total_compras DESC
    LIMIT 10
", "ss", [$inicioSQL, $finSQL]);

$usuariosCompras = [];
$totalComprasUsuarios = [];

foreach ($datosUsuarios as $fila) {
    $usuariosCompras[] = $fila["nombre"];
    $totalComprasUsuarios[] = $fila["total_compras"];
}

/* REPORTES POR CALIFICACIÓN */
$datosCalificaciones = leerDatos($conexion, "
    SELECT calificacion, COUNT(*) AS total
    FROM calificaciones
    WHERE fecha BETWEEN ? AND ?
    GROUP BY calificacion
    ORDER BY calificacion ASC
", "ss", [$inicioSQL, $finSQL]);

$calificaciones = [];
$totalCalificaciones = [];

foreach ($datosCalificaciones as $fila) {
    $calificaciones[] = "Calificación " . $fila["calificacion"];
    $totalCalificaciones[] = $fila["total"];
}

$totalVentas = array_sum($ventas);
$totalDias = count($fechas);
$promedioVentas = $totalDias > 0 ? $totalVentas / $totalDias : 0;
?>

<!DOCTYPE html>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reportes y gráficas</title>
<link rel="stylesheet" href="../../css/estilos.css?v=3">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
    margin:0;
    font-family:'Segoe UI', Arial, sans-serif;
    background:linear-gradient(135deg,#eef5ff,#f8fbff);
    color:#1f2937;
}

.contenedor{
    width:90%;
    max-width:1150px;
    margin:35px auto;
}

.bienvenida,
.filtros,
.resumen-card,
.card-grafica{
    background:white;
    border-radius:24px;
    box-shadow:0 14px 35px rgba(0,0,0,.08);
    border:1px solid #e5e7eb;
}

.bienvenida{
    padding:30px;
    border-left:8px solid #2563eb;
    margin-bottom:25px;
}

.bienvenida h2{
    margin:0 0 8px;
    font-size:30px;
    color:#0f172a;
}

.bienvenida p{
    margin:0;
    color:#475569;
}

.filtros{
    padding:22px;
    margin-bottom:25px;
}

.filtros form{
    display:flex;
    gap:15px;
    align-items:end;
    flex-wrap:wrap;
}

.campo{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.campo label{
    font-weight:700;
    color:#334155;
}

.campo input{
    padding:11px 14px;
    border-radius:12px;
    border:1px solid #cbd5e1;
    font-size:15px;
}

.btn-filtrar{
    border:none;
    background:#2563eb;
    color:white;
    padding:12px 18px;
    border-radius:12px;
    font-weight:800;
    cursor:pointer;
}

.btn-filtrar:hover{
    background:#1d4ed8;
}

.resumen-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
    margin-bottom:25px;
}

.resumen-card{
    padding:22px;
}

.resumen-card span{
    color:#64748b;
    font-size:14px;
}

.resumen-card h3{
    margin:8px 0 0;
    font-size:28px;
    color:#1d4ed8;
}

.graficas-grid{
    display:grid;
    grid-template-columns:1fr;
    gap:25px;
}

.card-grafica{
    padding:26px;
}

.card-header-grafica{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    margin-bottom:18px;
}

.card-header-grafica h3{
    margin:0;
    color:#1d4ed8;
    font-size:22px;
}

.btn-exportar{
    border:none;
    background:#0f172a;
    color:white;
    padding:8px 13px;
    border-radius:10px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    width:auto;
    min-width:105px;
    box-shadow:0 8px 18px rgba(15,23,42,.18);
}

.btn-exportar:hover{
    background:#2563eb;
}

canvas{
    width:100% !important;
    max-height:430px;
}

@media(max-width:768px){
    .resumen-grid{
        grid-template-columns:1fr;
    }

    .contenedor{
        width:94%;
    }

    .card-header-grafica{
        flex-direction:column;
        align-items:flex-start;
    }
}
</style>
</head>

<body class="panel-body">

<header>
    <h1>Reportes y gráficas</h1>

    <div class="header-acciones">
        <a class="btn-header-secundario" href="panel_admin.php">Volver al panel</a>
        <a class="btn-salir" href="../logout.php">Cerrar sesión</a>
    </div>
</header>

<div class="contenedor">

    <section class="bienvenida">
        <h2>📊 administración</h2>
        <p></p>
    </section>

    <section class="filtros">
        <form method="GET">
            <div class="campo">
                <label>Fecha inicio</label>
                <input type="date" name="fecha_inicio" value="<?php echo $fechaInicio; ?>">
            </div>

            <div class="campo">
                <label>Fecha fin</label>
                <input type="date" name="fecha_fin" value="<?php echo $fechaFin; ?>">
            </div>

            <button class="btn-filtrar" type="submit">Filtrar gráficas</button>
        </form>
    </section>

    <section class="resumen-grid">
        <div class="resumen-card">
            <span>Total vendido</span>
            <h3>$<?php echo number_format($totalVentas, 2); ?></h3>
        </div>

        <div class="resumen-card">
            <span>Días con ventas</span>
            <h3><?php echo $totalDias; ?></h3>
        </div>

        <div class="resumen-card">
            <span>Promedio diario</span>
            <h3>$<?php echo number_format($promedioVentas, 2); ?></h3>
        </div>
    </section>

    <section class="graficas-grid">

        <div class="card-grafica">
            <div class="card-header-grafica">
                <h3>Ventas por fecha</h3>
                <button class="btn-exportar" onclick="exportarGrafica('graficaVentas', 'ventas_por_fecha')">⬇ Exportar</button>
            </div>
            <canvas id="graficaVentas"></canvas>
        </div>

        <div class="card-grafica">
            <div class="card-header-grafica">
                <h3>Stock actual de productos</h3>
                <button class="btn-exportar" onclick="exportarGrafica('graficaStock', 'stock_productos')">⬇ Exportar</button>
            </div>
            <canvas id="graficaStock"></canvas>
        </div>

        <div class="card-grafica">
            <div class="card-header-grafica">
                <h3>Productos más vendidos</h3>
                <button class="btn-exportar" onclick="exportarGrafica('graficaMasVendidos', 'productos_mas_vendidos')">⬇ Exportar</button>
            </div>
            <canvas id="graficaMasVendidos"></canvas>
        </div>

        <div class="card-grafica">
            <div class="card-header-grafica">
                <h3>Compras por usuario</h3>
                <button class="btn-exportar" onclick="exportarGrafica('graficaComprasUsuario', 'compras_por_usuario')">⬇ Exportar</button>
            </div>
            <canvas id="graficaComprasUsuario"></canvas>
        </div>

        <div class="card-grafica">
            <div class="card-header-grafica">
                <h3>Reportes por calificación</h3>
                <button class="btn-exportar" onclick="exportarGrafica('graficaCalificaciones', 'reportes_por_calificacion')">⬇ Exportar</button>
            </div>
            <canvas id="graficaCalificaciones"></canvas>
        </div>

    </section>

</div>

<script>
const fechas = <?php echo json_encode($fechas); ?>;
const ventas = <?php echo json_encode($ventas); ?>;

const productosStock = <?php echo json_encode($productosStock); ?>;
const stockProductos = <?php echo json_encode($stockProductos); ?>;

const productosVendidos = <?php echo json_encode($productosVendidos); ?>;
const cantidadesVendidas = <?php echo json_encode($cantidadesVendidas); ?>;

const usuariosCompras = <?php echo json_encode($usuariosCompras); ?>;
const totalComprasUsuarios = <?php echo json_encode($totalComprasUsuarios); ?>;

const calificaciones = <?php echo json_encode($calificaciones); ?>;
const totalCalificaciones = <?php echo json_encode($totalCalificaciones); ?>;

new Chart(document.getElementById('graficaVentas'), {
    type: 'line',
    data: {
        labels: fechas,
        datasets: [{
            label: 'Ventas por día',
            data: ventas,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.15)',
            pointBackgroundColor: '#2563eb',
            borderWidth: 4,
            tension: 0.35,
            fill: true
        }]
    },
    options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
});

new Chart(document.getElementById('graficaStock'), {
    type: 'bar',
    data: {
        labels: productosStock,
        datasets: [{
            label: 'Stock disponible',
            data: stockProductos,
            backgroundColor: 'rgba(14, 165, 233, 0.45)',
            borderColor: '#0284c7',
            borderWidth: 2
        }]
    },
    options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
});

new Chart(document.getElementById('graficaMasVendidos'), {
    type: 'bar',
    data: {
        labels: productosVendidos,
        datasets: [{
            label: 'Cantidad vendida',
            data: cantidadesVendidas,
            backgroundColor: 'rgba(34, 197, 94, 0.45)',
            borderColor: '#16a34a',
            borderWidth: 2
        }]
    },
    options: { responsive:true, indexAxis:'y', scales:{ x:{ beginAtZero:true } } }
});

new Chart(document.getElementById('graficaComprasUsuario'), {
    type: 'bar',
    data: {
        labels: usuariosCompras,
        datasets: [{
            label: 'Número de compras',
            data: totalComprasUsuarios,
            backgroundColor: 'rgba(168, 85, 247, 0.45)',
            borderColor: '#7e22ce',
            borderWidth: 2
        }]
    },
    options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
});

new Chart(document.getElementById('graficaCalificaciones'), {
    type: 'doughnut',
    data: {
        labels: calificaciones,
        datasets: [{
            label: 'Reportes',
            data: totalCalificaciones,
            backgroundColor: [
                'rgba(239, 68, 68, 0.70)',
                'rgba(249, 115, 22, 0.70)',
                'rgba(234, 179, 8, 0.70)',
                'rgba(34, 197, 94, 0.70)',
                'rgba(37, 99, 235, 0.70)'
            ],
            borderColor: '#ffffff',
            borderWidth: 3
        }]
    },
    options: { responsive:true }
});

async function exportarGrafica(idCanvas, nombreArchivo){

    const canvas = document.getElementById(idCanvas);

    const imagen = canvas.toDataURL("image/png", 1.0);

    const { jsPDF } = window.jspdf;

    const pdf = new jsPDF(
        'landscape',
        'mm',
        'a4'
    );

    pdf.setFontSize(18);
    pdf.text(nombreArchivo, 15, 15);

    pdf.addImage(
        imagen,
        'PNG',
        10,
        25,
        270,
        130
    );

    pdf.save(nombreArchivo + '.pdf');
}
</script>

</body>
</html>