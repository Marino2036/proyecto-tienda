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

$mensaje = "";
$tipoMensaje = "";

function redimensionarImagenABlob($origen, $tipo, $maxW = 800, $maxH = 800)
{
    if ($tipo === "image/png") {
        $img = imagecreatefrompng($origen);
    } elseif ($tipo === "image/jpeg" || $tipo === "image/jpg") {
        $img = imagecreatefromjpeg($origen);
    } elseif ($tipo === "image/webp") {
        $img = imagecreatefromwebp($origen);
    } else {
        return null;
    }

    if (!$img) {
        return null;
    }

    $width = imagesx($img);
    $height = imagesy($img);

    if ($width <= 0 || $height <= 0) {
        imagedestroy($img);
        return null;
    }

    $ratio = min($maxW / $width, $maxH / $height);

    $newW = (int) round($width * $ratio);
    $newH = (int) round($height * $ratio);

    $nueva = imagecreatetruecolor($newW, $newH);

    $fondoBlanco = imagecolorallocate($nueva, 255, 255, 255);
    imagefill($nueva, 0, 0, $fondoBlanco);

    imagecopyresampled($nueva, $img, 0, 0, 0, 0, $newW, $newH, $width, $height);

    ob_start();
    imagejpeg($nueva, null, 85);
    $imagenBinaria = ob_get_clean();

    imagedestroy($img);
    imagedestroy($nueva);

    return $imagenBinaria;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $precio = trim($_POST["precio"]);
    $stock = trim($_POST["stock"]);

    if ($nombre === "" || $descripcion === "" || $precio === "" || $stock === "") {
        $mensaje = "Todos los campos son obligatorios";
        $tipoMensaje = "error";
    } else {
        if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] === 0) {
            $archivoTmp = $_FILES["imagen"]["tmp_name"];
            $tamanoArchivo = $_FILES["imagen"]["size"];
            $tipoMime = mime_content_type($archivoTmp);

            $tiposPermitidos = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
            $infoImagen = getimagesize($archivoTmp);

            if ($infoImagen === false) {
                $mensaje = "El archivo seleccionado no es una imagen válida";
                $tipoMensaje = "error";
            } elseif (!in_array($tipoMime, $tiposPermitidos, true)) {
                $mensaje = "Solo se permiten imágenes JPG, JPEG, PNG o WEBP";
                $tipoMensaje = "error";
            } elseif ($tamanoArchivo > 5 * 1024 * 1024) {
                $mensaje = "La imagen no debe pesar más de 5 MB";
                $tipoMensaje = "error";
            } else {
                $imagenBlob = redimensionarImagenABlob($archivoTmp, $tipoMime, 800, 800);

                if ($imagenBlob === null) {
                    $mensaje = "No se pudo procesar la imagen";
                    $tipoMensaje = "error";
                } else {
                    $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, imagen, activo)
                            VALUES (?, ?, ?, ?, ?, 1)";

                    $stmt = $conexion->prepare($sql);

                    if (!$stmt) {
                        $mensaje = "Error al preparar la consulta: " . $conexion->error;
                        $tipoMensaje = "error";
                    } else {
                        $stmt->bind_param("ssdis", $nombre, $descripcion, $precio, $stock, $imagenBlob);

                        if ($stmt->execute()) {
                            $mensaje = "Producto agregado correctamente";
                            $tipoMensaje = "exito";
                        } else {
                            $mensaje = "Error al guardar el producto en la base de datos: " . $stmt->error;
                            $tipoMensaje = "error";
                        }

                        $stmt->close();
                    }
                }
            }
        } else {
            $mensaje = "Debes seleccionar una imagen";
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
    <title>Agregar producto</title>
    <link rel="stylesheet" href="../../css/estilos.css?v=20">
</head>
<body>
    <div class="contenedor-centrado">
        <div class="caja">
            <h2>Agregar producto</h2>

            <?php if ($mensaje !== "") { ?>
                <div class="<?php echo ($tipoMensaje === 'exito') ? 'mensaje-exito' : 'mensaje-error'; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data">
                <label for="nombre">Nombre</label>
                <input type="text" name="nombre" id="nombre" required>

                <label for="descripcion">Descripción</label>
                <input type="text" name="descripcion" id="descripcion" required>

                <label for="precio">Precio</label>
                <input type="number" step="0.01" name="precio" id="precio" required>

                <label for="stock">Stock</label>
                <input type="number" name="stock" id="stock" required>

                <label for="imagen">Imagen</label>
                <input type="file" name="imagen" id="imagen" accept=".jpg,.jpeg,.png,.webp" required>

                <button type="submit">Guardar producto</button>
            </form>

            <div class="enlace">
                <a href="panel_admin.php">Volver al panel admin</a>
            </div>
        </div>
    </div>
</body>
</html>