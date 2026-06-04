<?php
require_once "../config/db.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

$sql = "SELECT * FROM productos WHERE id = ? AND activo = 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if (!$resultado || $resultado->num_rows === 0) {
    die("Producto no encontrado o no disponible.");
}

$producto = $resultado->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($producto["nombre"]); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    margin:0;
    font-family:'Segoe UI', Arial, sans-serif;
    background:linear-gradient(135deg,#eef5ff,#f8fbff);
    color:#0f172a;
}

.contenedor{
    max-width:520px;
    margin:35px auto;
    padding:20px;
}

.card{
    background:white;
    border-radius:26px;
    overflow:hidden;
    box-shadow:0 20px 45px rgba(0,0,0,.12);
    border:1px solid #e5e7eb;
}

.card-img{
    background:#f8fafc;
    text-align:center;
    padding:30px;
}

.card-img img{
    max-width:240px;
    max-height:240px;
    object-fit:contain;
    border-radius:18px;
}

.info{
    padding:28px;
}

.info h1{
    margin:0 0 12px;
    font-size:30px;
    color:#1d4ed8;
}

.descripcion{
    color:#475569;
    margin-bottom:22px;
    line-height:1.5;
}

.dato{
    display:flex;
    justify-content:space-between;
    gap:15px;
    padding:15px 0;
    border-bottom:1px solid #e5e7eb;
}

.dato span{
    font-weight:800;
    color:#64748b;
}

.dato strong{
    color:#0f172a;
    text-align:right;
}

.precio{
    color:#16a34a !important;
    font-size:23px;
}

.stock-ok{
    color:#16a34a !important;
}

.stock-vacio{
    color:#dc2626 !important;
}

.footer{
    text-align:center;
    margin-top:20px;
    color:#64748b;
    font-size:13px;
}
</style>
</head>

<body>

<div class="contenedor">
    <div class="card">

        <div class="card-img">
            <?php if (!empty($producto["imagen"])) { ?>
                <img
                    src="data:image/jpeg;base64,<?php echo base64_encode($producto['imagen']); ?>"
                    alt="<?php echo htmlspecialchars($producto["nombre"]); ?>"
                >
            <?php } else { ?>
                <h2>Sin imagen</h2>
            <?php } ?>
        </div>

        <div class="info">
            <h1><?php echo htmlspecialchars($producto["nombre"]); ?></h1>

            <div class="descripcion">
                <?php echo htmlspecialchars($producto["descripcion"]); ?>
            </div>

            <div class="dato">
                <span>Precio</span>
                <strong class="precio">$<?php echo number_format((float)$producto["precio"], 2); ?></strong>
            </div>

            <div class="dato">
                <span>Stock</span>
                <strong class="<?php echo ((int)$producto["stock"] > 0) ? 'stock-ok' : 'stock-vacio'; ?>">
                    <?php echo (int)$producto["stock"]; ?> disponibles
                </strong>
            </div>

            <div class="footer">
                Información del producto actualizada desde la tienda
            </div>
        </div>

    </div>
</div>

</body>
</html>