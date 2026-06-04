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

$sql = "SELECT * FROM productos ORDER BY id DESC";
$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver productos</title>
    <link rel="stylesheet" href="../../css/estilos.css?v=8">
</head>
<body class="panel-body">

<header>
    <h1>Productos registrados</h1>
    <a class="btn-salir" href="panel_admin.php">Volver</a>
</header>

<div class="contenedor">
    <div class="productos">
        <?php if ($resultado && $resultado->num_rows > 0) { ?>
            <?php while ($producto = $resultado->fetch_assoc()) { ?>
                <div class="card">
                    <img
                        src="../ver_imagen.php?id=<?php echo $producto['id']; ?>"
                        alt="<?php echo htmlspecialchars($producto["nombre"]); ?>"
                    >

                    <h3><?php echo htmlspecialchars($producto["nombre"]); ?></h3>
                    <p><?php echo htmlspecialchars($producto["descripcion"]); ?></p>
                    <p>Stock: <?php echo (int)$producto["stock"]; ?></p>
                    <p>Estado: <?php echo ((int)$producto["activo"] === 1) ? "Activo" : "Inactivo"; ?></p>
                    <div class="precio">$<?php echo number_format((float)$producto["precio"], 2); ?></div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p>No hay productos registrados.</p>
        <?php } ?>
    </div>
</div>

</body>
</html>