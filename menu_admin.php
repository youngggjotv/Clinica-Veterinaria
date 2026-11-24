<?php
require_once __DIR__ . '/auth.php';
requerirLogin(['admin']);
$user = usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Administración - VetAgenda</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="menu_admin.php">
                <img src="assets/logo.png" alt="Logo" class="brand-img">
                <span class="brand-text">
                    <strong>VetAgenda</strong>
                    <small>Administración · <?= htmlspecialchars($user['nombre']) ?></small>
                </span>
            </a>
            <a href="logout.php" class="topbar-note" style="color:#fff; text-decoration:none;">Cerrar sesión</a>
        </div>
    </header>

    <main class="page">
        <section class="intro">
            <div>
                <p class="hand">Panel de Control</p>
                <h1>Menú de Administración</h1>
                <p class="lead">Configuración de servicios, mantenedores y reportes de asistencia.</p>
            </div>
        </section>

        <div class="menu-grid">
            <a class="card menu-card" href="mantenedores/servicios.php">
                <span class="tag">DECISIÓN D-01</span>
                <h2>Mantenedor de Servicios</h2>
                <p>Definir tipos de cita y duración en minutos para el cálculo automático.</p>
            </a>
            <a class="card menu-card" href="reportes/reporte_asistencia.php">
                <span class="tag">KAN-23</span>
                <h2>Reporte de Asistencia</h2>
                <p>Resumen de citas confirmadas, pendientes y canceladas para seguimiento de no asistencia.</p>
            </a>
            <a class="card menu-card" href="menu_principal.php">
                <span class="tag">OPERACIÓN</span>
                <h2>Ir al Panel de Recepción</h2>
                <p>Acceder a tutores, agendamiento de citas y agenda diaria.</p>
            </a>
        </div>
    </main>
</body>
</html>
