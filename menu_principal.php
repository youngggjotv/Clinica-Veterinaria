<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
requerirLogin(['recepcionista', 'admin']);
$user = usuarioActual();

$hoy = date('Y-m-d');
$citasHoy = (int)$pdo->query("SELECT COUNT(*) FROM citas WHERE fecha_hora_inicio LIKE '$hoy%' AND estado != 'Cancelada'")->fetchColumn();
$totalMascotas = (int)$pdo->query("SELECT COUNT(*) FROM mascotas")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Principal - VetAgenda</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="menu_principal.php">
                <img src="assets/logo.png" alt="Logo" class="brand-img">
                <span class="brand-text">
                    <strong>VetAgenda</strong>
                    <small>Panel de Recepción · <?= htmlspecialchars($user['nombre']) ?></small>
                </span>
            </a>
            <a href="logout.php" class="topbar-note" style="color:#fff; text-decoration:none;">Cerrar sesión</a>
        </div>
    </header>

    <main class="page">
        <section class="intro">
            <div>
                <p class="hand">¡Bienvenido!</p>
                <h1>Menú Principal</h1>
                <p class="lead">Gestiona tutores, mascotas, agendamiento sin solapamientos y agenda diaria.</p>
            </div>
            <ul class="stats">
                <li><span class="stat-num"><?= $citasHoy ?></span><span class="stat-label">citas hoy</span></li>
                <li><span class="stat-num"><?= $totalMascotas ?></span><span class="stat-label">mascotas</span></li>
            </ul>
        </section>

        <div class="menu-grid">
            <a class="card menu-card" href="mantenedores/tutores.php">
                <span class="tag">KAN-18 · KAN-26</span>
                <h2>Mantenedor de Tutores y Mascotas</h2>
                <p>Registrar tutores con consentimiento (Ley 21.719), mascotas y gestionar eliminación de datos.</p>
            </a>
            <a class="card menu-card" href="gestores/gestionar_citas.php">
                <span class="tag">KAN-19 · RF-01</span>
                <h2>Gestor de Citas</h2>
                <p>Agendar citas con cálculo automático de término (D-01) y prevención de solapamientos.</p>
            </a>
            <a class="card menu-card" href="reportes/agenda_diaria.php">
                <span class="tag">KAN-20 · KAN-22</span>
                <h2>Agenda Diaria por Veterinario</h2>
                <p>Consultar citas por fecha y veterinario, confirmar asistencia o cancelar liberando horario.</p>
            </a>
        </div>
    </main>
</body>
</html>
