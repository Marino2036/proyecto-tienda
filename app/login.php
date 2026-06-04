<?php
session_start();
require_once "../config/db.php";

$mensaje = "";
$correo = "";

function registrarBitacora($conexion, $usuario, $estado) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

    $stmt = $conexion->prepare("
        INSERT INTO bitacora_login 
        (usuario_intentado, fecha, hora, estado, ip, user_agent)
        VALUES (?, CURDATE(), CURTIME(), ?, ?, ?)
    ");

    $stmt->bind_param("ssss", $usuario, $estado, $ip, $userAgent);
    $stmt->execute();
    $stmt->close();
}

if (isset($_SESSION["id"])) {
    if ($_SESSION["rol"] === "admin") {
        header("Location: admin/panel_admin.php");
    } else {
        header("Location: panel.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = trim($_POST["correo"] ?? "");
    $password = trim($_POST["password"] ?? "");

    $sql = "SELECT id, nombre, correo, password, rol FROM usuarios WHERE correo = ? LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();

        if ($password === $fila["password"]) {

            registrarBitacora($conexion, $correo, "Acceso exitoso");

            $_SESSION["id"] = $fila["id"];
            $_SESSION["nombre"] = $fila["nombre"];
            $_SESSION["correo"] = $fila["correo"];
            $_SESSION["rol"] = $fila["rol"];

            if ($fila["rol"] === "admin") {
                header("Location: admin/panel_admin.php");
            } else {
                header("Location: panel.php");
            }
            exit();

        } else {
            registrarBitacora($conexion, $correo, "Contraseña incorrecta");
            $mensaje = "Correo o contraseña incorrectos";
        }

    } else {
        registrarBitacora($conexion, $correo, "Usuario no registrado");
        $mensaje = "Correo o contraseña incorrectos";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="../css/estilos.css?v=3">
</head>
<body>

    <div class="contenedor-centrado">
        <div class="caja caja-login">

            <div class="login-encabezado">
                <div class="login-badge">Acceso</div>
                <h2>Iniciar sesión</h2>
                <p>Ingresa tus datos para acceder a la tienda.</p>
            </div>

            <?php if ($mensaje != "") { ?>
                <div class="mensaje-error"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php } ?>

            <form method="POST" action="" class="form-login" novalidate>
                <label for="correo">Correo electrónico</label>
                <input 
                    type="email" 
                    name="correo" 
                    id="correo" 
                    value="<?php echo htmlspecialchars($correo); ?>" 
                    placeholder="ejemplo@correo.com"
                    required
                >

                <label for="password">Contraseña</label>
                <div class="input-password">
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        placeholder="Ingresa tu contraseña"
                        required
                    >
                </div>

                <button type="submit">Entrar</button>
            </form>

            <div class="enlace enlace-login">
                <span>¿No tienes cuenta?</span>
                <a href="registro.php">Registrar nuevo usuario</a>
            </div>
        </div>
    </div>

</body>
</html>