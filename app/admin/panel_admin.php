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
?>

<!DOCTYPE html>

<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de administración</title>
    <link rel="stylesheet" href="../../css/estilos.css?v=3">
</head>
<body class="panel-body">

<header>
    <h1>Panel de administración</h1>

```
<div class="header-acciones">
    <a class="btn-header-secundario" href="../panel.php">Ir a la tienda</a>
    <a class="btn-salir" href="../logout.php">Cerrar sesión</a>
</div>
```

</header>

<div class="contenedor">

```
<section class="bienvenida">
    <div class="bienvenida-top">
        <div>
            <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION["nombre"]); ?></h2>
            <p>Desde aquí puedes administrar productos, usuarios y el funcionamiento general de tu tienda.</p>
        </div>

        <div class="admin-etiqueta">
            <span>Administrador</span>
        </div>
    </div>
</section>

<section class="seccion-productos">
    <div class="seccion-encabezado">
        <h2>Panel de control</h2>
        <p>Selecciona una opción para administrar la tienda</p>
    </div>

    <div class="productos panel-admin-grid">

        <article class="card card-admin">
            <div class="icono-admin">📦</div>
            <h3>Agregar producto</h3>
            <p>Sube nuevos productos con imagen, precio, descripción y stock disponible.</p>
            <a class="btn-admin-card" href="agregar_producto.php">Ir a agregar</a>
        </article>

        <article class="card card-admin">
            <div class="icono-admin">🛍️</div>
            <h3>Ver productos</h3>
            <p>Consulta rápidamente los productos registrados actualmente en la tienda.</p>
            <a class="btn-admin-card" href="productos.php">Ver productos</a>
        </article>

        <article class="card card-admin">
            <div class="icono-admin">🖥️</div>
            <h3>Ir a tienda</h3>
            <p>Visualiza cómo se muestra la tienda desde la perspectiva del cliente.</p>
            <a class="btn-admin-card" href="../panel.php">Abrir tienda</a>
        </article>

        <article class="card card-admin">
            <div class="icono-admin">⚙️</div>
            <h3>Gestionar productos</h3>
            <p>Administra productos en tabla: editar, eliminar y revisar stock de forma detallada.</p>
            <a class="btn-admin-card" href="gestionar_productos.php">Abrir gestión</a>
        </article>

        <article class="card card-admin">
            <div class="icono-admin">👥</div>
            <h3>Gestionar usuarios</h3>
            <p>Crea usuarios, dales de baja, reactívalos o elimínalos según sea necesario.</p>
            <a class="btn-admin-card" href="usuarios.php">Abrir gestión</a>
        </article>

        <article class="card card-admin">
            <div class="icono-admin">📋</div>
            <h3>Bitácora de accesos</h3>
            <p>Consulta quién intentó iniciar sesión, aunque el usuario no esté registrado.</p>
            <a class="btn-admin-card" href="bitacora.php">Ver bitácora</a>
        </article>

        <article class="card card-admin">
            <div class="icono-admin">📱</div>
            <h3>Bitácora de accesos QR</h3>
            <p>Consulta quién escaneó códigos QR, qué producto visualizó y desde qué dispositivo accedió.</p>
            <a class="btn-admin-card" href="bitacora_qr.php">Ver accesos QR</a>
        </article>

        <article class="card card-admin">
            <div class="icono-admin">📊</div>
            <h3>Reportes y gráficas</h3>
            <p>Visualiza las ventas por fecha mediante gráficas automáticas en tiempo real.</p>
            <a class="btn-admin-card" href="reportes.php">Ver reportes</a>
        </article>

    </div>
</section>
```

</div>

</body>
</html>
