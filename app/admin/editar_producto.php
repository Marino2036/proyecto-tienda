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

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: gestionar_productos.php");
    exit();
}

$id = (int)$_GET["id"];
$mensaje = "";
$tipoMensaje = "";

$sql = "SELECT * FROM productos WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    header("Location: gestionar_productos.php");
    exit();
}

$producto = $resultado->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $precio = trim($_POST["precio"]);
    $stock = trim($_POST["stock"]);
    $activo = isset($_POST["activo"]) ? 1 : 0;

    if ($nombre == "" || $descripcion == "" || $precio == "" || $stock == "") {
        $mensaje = "Todos los campos son obligatorios";
        $tipoMensaje = "error";
    } else {
        $sqlUpdate = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, activo = ? WHERE id = ?";
        $stmtUpdate = $conexion->prepare($sqlUpdate);
        $stmtUpdate->bind_param("ssdiii", $nombre, $descripcion, $precio, $stock, $activo, $id);

        if ($stmtUpdate->execute()) {
            $mensaje = "Producto actualizado correctamente";
            $tipoMensaje = "exito";

            $sql = "SELECT * FROM productos WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $producto = $resultado->fetch_assoc();
        } else {
            $mensaje = "Error al actualizar el producto";
            $tipoMensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar producto</title>
    <link rel="stylesheet" href="../../css/estilos.css?v=4">
</head>
<body>
    <div class="contenedor-centrado">
        <div class="caja">
            <h2>Actualizar producto</h2>

            <?php if ($mensaje != "") { ?>
                <div class="<?php echo ($tipoMensaje == "exito") ? "mensaje-exito" : "mensaje-error"; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php } ?>

            <form method="POST">
                <label for="nombre">Nombre</label>
                <input type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($producto["nombre"]); ?>" required>

                <label for="descripcion">Descripción</label>
                <input type="text" name="descripcion" id="descripcion" value="<?php echo htmlspecialchars($producto["descripcion"]); ?>" required>

                <label for="precio">Precio</label>
                <input type="number" step="0.01" name="precio" id="precio" value="<?php echo htmlspecialchars($producto["precio"]); ?>" required>

                <label for="stock">Stock</label>
                <input type="number" name="stock" id="stock" value="<?php echo (int)$producto["stock"]; ?>" required>

                <label style="margin-top: 10px;">
                    <input type="checkbox" name="activo" <?php echo ((int)$producto["activo"] === 1) ? "checked" : ""; ?>>
                    Producto activo
                </label>

                <button type="submit">Guardar cambios</button>
            </form>

            <div class="enlace">
                <a href="gestionar_productos.php">Volver a la tabla</a>
            </div>
        </div>
    </div>
</body>
</html>