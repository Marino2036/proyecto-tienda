<?php
require_once "../config/db.php";

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $correo = trim($_POST["correo"]);
    $password = trim($_POST["password"]);

    if ($nombre == "" || $correo == "" || $password == "") {
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
            $rol = "cliente";
            $sql = "INSERT INTO usuarios (nombre, correo, password, rol) VALUES (?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssss", $nombre, $correo, $password, $rol);

            if ($stmt->execute()) {
                $mensaje = "Usuario registrado correctamente";
                $tipoMensaje = "exito";
            } else {
                $mensaje = "Error al registrar el usuario";
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
    <title>Registro de usuario</title>
    <link rel="stylesheet" href="../css/estilos.css?v=2">
</head>
<body>
    <div class="contenedor-centrado">
        <div class="caja">
            <h2>Registrar usuario</h2>

            <?php if ($mensaje != "") { ?>
                <div class="<?php echo ($tipoMensaje == 'exito') ? 'mensaje-exito' : 'mensaje-error'; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php } ?>

            <form method="POST" action="">
                <label for="nombre">Nombre</label>
                <input type="text" name="nombre" id="nombre" required>

                <label for="correo">Correo</label>
                <input type="email" name="correo" id="correo" required>

                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" required>
<label>Fecha de registro</label>
<div style="
    padding: 12px;
    border-radius: 10px;
    background: #f3f4f6;
    margin-bottom: 10px;
    font-weight: bold;
    color: #374151;
">
    <?php echo date('d/m/Y'); ?>
</div>
                <button type="submit">Registrar</button>
            </form>

            <div class="enlace">
                <a href="login.php">Volver al login</a>
            </div>
        </div>
    </div>
</body>
</html>