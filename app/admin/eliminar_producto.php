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

function obtenerImagenBase64($imagenBlob) {
    if (empty($imagenBlob)) {
        return "";
    }

    $mime = "image/jpeg";

    if (function_exists("mime_content_type")) {
        $tmp = tempnam(sys_get_temp_dir(), "img");
        file_put_contents($tmp, $imagenBlob);
        $mimeDetectado = mime_content_type($tmp);
        unlink($tmp);

        if (!empty($mimeDetectado)) {
            $mime = $mimeDetectado;
        }
    }

    return "data:" . $mime . ";base64," . base64_encode($imagenBlob);
}

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $precio = (float)$_POST["precio"];
    $stock = (int)$_POST["stock"];
    $activo = isset($_POST["activo"]) ? 1 : 0;

    if ($nombre === "" || $descripcion === "" || $precio <= 0 || $stock < 0) {
        $mensaje = "Revisa los datos del producto.";
        $tipoMensaje = "error";
    } else {
        $hayImagenNueva = isset($_FILES["imagen"])
            && $_FILES["imagen"]["error"] === UPLOAD_ERR_OK
            && $_FILES["imagen"]["size"] > 0;

        if ($hayImagenNueva) {
            $tipoArchivo = mime_content_type($_FILES["imagen"]["tmp_name"]);
            $permitidos = ["image/jpeg", "image/png", "image/webp", "image/gif"];

            if (!in_array($tipoArchivo, $permitidos)) {
                $mensaje = "Solo se permiten imágenes JPG, PNG, WEBP o GIF.";
                $tipoMensaje = "error";
            } else {
                $imagenContenido = file_get_contents($_FILES["imagen"]["tmp_name"]);

                $sqlUpdate = "
                    UPDATE productos
                    SET nombre = ?, descripcion = ?, precio = ?, stock = ?, activo = ?, imagen = ?
                    WHERE id = ?
                ";

                $stmtUpdate = $conexion->prepare($sqlUpdate);
                $imagenNull = null;

                $stmtUpdate->bind_param(
                    "ssdiibi",
                    $nombre,
                    $descripcion,
                    $precio,
                    $stock,
                    $activo,
                    $imagenNull,
                    $id
                );

                $stmtUpdate->send_long_data(5, $imagenContenido);
            }
        } else {
            $sqlUpdate = "
                UPDATE productos
                SET nombre = ?, descripcion = ?, precio = ?, stock = ?, activo = ?
                WHERE id = ?
            ";

            $stmtUpdate = $conexion->prepare($sqlUpdate);

            $stmtUpdate->bind_param(
                "ssdiii",
                $nombre,
                $descripcion,
                $precio,
                $stock,
                $activo,
                $id
            );
        }

        if ($mensaje === "") {
            if ($stmtUpdate->execute()) {
                $mensaje = "Producto actualizado correctamente.";
                $tipoMensaje = "exito";

                $sql = "SELECT * FROM productos WHERE id = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $resultado = $stmt->get_result();
                $producto = $resultado->fetch_assoc();
            } else {
                $mensaje = "Error al actualizar el producto.";
                $tipoMensaje = "error";
            }
        }
    }
}

$imagenActual = obtenerImagenBase64($producto["imagen"] ?? "");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar producto</title>
<link rel="stylesheet" href="../../css/estilos.css?v=4">

<style>
.preview-imagen{
    width:100%;
    min-height:170px;
    border:2px dashed #cbd5e1;
    border-radius:18px;
    background:#f8fafc;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:12px 0 18px;
    overflow:hidden;
}

.preview-imagen img{
    max-width:100%;
    max-height:230px;
    object-fit:contain;
}

.sin-imagen{
    color:#64748b;
    font-weight:700;
}

.input-file{
    background:#f8fafc;
    border:1px solid #cbd5e1;
    padding:12px;
    border-radius:12px;
    width:100%;
    box-sizing:border-box;
    margin-bottom:15px;
}

.nota-imagen{
    font-size:13px;
    color:#64748b;
    margin-top:-8px;
    margin-bottom:14px;
}
</style>
</head>

<body>
<div class="contenedor-centrado">
    <div class="caja">
        <h2>Actualizar producto</h2>

        <?php if ($mensaje !== "") { ?>
            <div class="<?php echo ($tipoMensaje === "exito") ? "mensaje-exito" : "mensaje-error"; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php } ?>

        <form method="POST" enctype="multipart/form-data">
            <label for="nombre">Nombre</label>
            <input type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($producto["nombre"]); ?>" required>

            <label for="descripcion">Descripción</label>
            <input type="text" name="descripcion" id="descripcion" value="<?php echo htmlspecialchars($producto["descripcion"]); ?>" required>

            <label for="precio">Precio</label>
            <input type="number" step="0.01" name="precio" id="precio" value="<?php echo htmlspecialchars($producto["precio"]); ?>" required>

            <label for="stock">Stock</label>
            <input type="number" name="stock" id="stock" value="<?php echo (int)$producto["stock"]; ?>" required>

            <label>Imagen actual</label>
            <div class="preview-imagen">
                <?php if ($imagenActual !== "") { ?>
                    <img id="previewImg" src="<?php echo $imagenActual; ?>" alt="Imagen actual">
                <?php } else { ?>
                    <div class="sin-imagen" id="sinImagen">Este producto no tiene imagen visible</div>
                    <img id="previewImg" src="" alt="Vista previa" style="display:none;">
                <?php } ?>
            </div>

            <label for="imagen">Cambiar imagen</label>
            <input class="input-file" type="file" name="imagen" id="imagen" accept="image/jpeg,image/png,image/webp,image/gif">

            <div class="nota-imagen">
                Si seleccionas una imagen nueva, reemplazará la imagen anterior.
            </div>

            <label style="margin-top:10px;">
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

<script>
const inputImagen = document.getElementById("imagen");
const previewImg = document.getElementById("previewImg");
const sinImagen = document.getElementById("sinImagen");

inputImagen.addEventListener("change", function(){
    const archivo = this.files[0];

    if (!archivo) return;

    const lector = new FileReader();

    lector.onload = function(e) {
        previewImg.src = e.target.result;
        previewImg.style.display = "block";

        if (sinImagen) {
            sinImagen.style.display = "none";
        }
    };

    lector.readAsDataURL(archivo);
});
</script>

</body>
</html>