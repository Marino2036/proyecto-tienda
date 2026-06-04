<?php

include "./config/db.php";

$consulta = "
SELECT DATE(fecha_compra) AS fecha,
SUM(total) AS ventas
FROM compras
GROUP BY DATE(fecha_compra)
ORDER BY fecha ASC
";

$resultado = mysqli_query($conexion, $consulta);

$fechas = [];
$ventas = [];

while($fila = mysqli_fetch_assoc($resultado)){

    $fechas[] = $fila['fecha'];
    $ventas[] = $fila['ventas'];

}

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gráfica de Ventas</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
    font-family: Arial;
    background: #f4f4f4;
    padding: 20px;
}

.container{
    background: white;
    padding: 20px;
    border-radius: 10px;
}

canvas{
    width: 100% !important;
    max-height: 500px;
}

</style>

</head>
<body>

<div class="container">

<h2>📊 Ventas por Fecha</h2>

<canvas id="graficaVentas"></canvas>

</div>

<script>

const fechas = <?php echo json_encode($fechas); ?>;
const ventas = <?php echo json_encode($ventas); ?>;

const ctx = document.getElementById('graficaVentas');

new Chart(ctx, {

    type: 'line',

    data: {

        labels: fechas,

        datasets: [{
            label: 'Ventas',
            data: ventas,
            borderWidth: 3,
            tension: 0.3,
            fill: false
        }]
    },

    options: {

        responsive: true,

        plugins: {
            legend: {
                display: true
            }
        },

        scales: {
            y: {
                beginAtZero: true
            }
        }
    }

});

</script>

</body>
</html>