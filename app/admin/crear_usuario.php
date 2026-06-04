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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"]);
    $correo = trim($_POST["correo"]);
    $password = trim($_POST["password"]);
    $rol = trim($_POST["rol"]);

    if ($nombre === "" || $correo === "" || $password === "" || $rol === "") {
        $mensaje = "Todos los campos son obligatorios";
        $tipoMensaje = "error";
    } else {
        $verificar = "SELECT id FROM usuarios WHERE correo = ? LIMIT 1";
        $stmtVerificar = $conexion->prepare($verificar);
        $stmtVerificar->bind_param("s", $correo);
        $stmtVerificar->execute();
        $resultadoVerificar = $stmtVerificar->get_result();

        if ($resultadoVerificar->num_rows > 0) {
            $mensaje = "Ese correo ya está registrado";
            $tipoMensaje = "error";
        } else {
            $sql = "INSERT INTO usuarios (nombre, correo, password, rol, activo)
                    VALUES (?, ?, ?, ?, 1)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssss", $nombre, $correo, $password, $rol);

            if ($stmt->execute()) {
                $mensaje = "Usuario creado correctamente";
                $tipoMensaje = "exito";
            } else {
                $mensaje = "Error al crear usuario";
                $tipoMensaje = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear usuario</title>
    <link rel="stylesheet" href="../../css/estilos.css?v=9">
</head>
<body>
    <div class="contenedor-centrado">
        <div class="caja">
            <h2>Crear usuario</h2>

            <?php if ($mensaje != "") { ?>
                <div class="<?php echo ($tipoMensaje === 'exito') ? 'mensaje-exito' : 'mensaje-error'; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php } ?>

            <form method="POST">
                <label for="nombre">Nombre</label>
                <input type="text" name="nombre" id="nombre" required>

                <label for="correo">Correo</label>
                <input type="email" name="correo" id="correo" required>

                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" required>

                <label for="rol">Rol</label>
                <select name="rol" id="rol" required>
                    <option value="cliente">Cliente</option>
                    <option value="admin">Admin</option>
                </select>

                <button type="submit">Guardar usuario</button>
            </form>

            <div class="enlace">
                <a href="usuarios.php">Volver a usuarios</a>
            </div>
        </div>
    </div>
</body>
</html>