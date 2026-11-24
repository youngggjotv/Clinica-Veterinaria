<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
requerirLogin(['recepcionista', 'admin']);

$mensaje = '';
$tipoMsg = '';

function aMinutos(string $hora): int {
    [$h, $m] = array_map('intval', explode(':', $hora));
    return $h * 60 + $m;
}

function aHora(int $min): string {
    return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
}

$servicios = $pdo->query('SELECT * FROM servicios ORDER BY id_servicio ASC')->fetchAll();
$duraciones = [];
foreach ($servicios as $s) {
    $duraciones[$s['nombre_servicio']] = (int)$s['duracion_minutos'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idMascota = $_POST['id_mascota'] ?? '';
    $idVet     = $_POST['id_veterinario'] ?? '1';
    $tipo      = $_POST['cita_tipo'] ?? 'Consulta general';
    $fecha     = $_POST['fecha'] ?? '';
    $hora      = $_POST['hora'] ?? '';

    $duracion  = $duraciones[$tipo] ?? 30;
    $iniNuevo  = aMinutos($hora);
    $finNuevo  = $iniNuevo + $duracion;

    // Regla RF-01: validar solapamiento por rango real ignorando citas Canceladas
    $stmt = $pdo->prepare("SELECT * FROM citas WHERE id_veterinario = ? AND fecha_hora_inicio LIKE ? AND LOWER(estado) != 'cancelada'");
    $stmt->execute([$idVet, $fecha . '%']);
    $existentes = $stmt->fetchAll();

    $hayConflicto = false;
    foreach ($existentes as $c) {
        $iniEx = aMinutos(substr($c['fecha_hora_inicio'], 11, 5));
        $finEx = aMinutos(substr($c['fecha_hora_fin'], 11, 5));
        if ($iniEx < $finNuevo && $finEx > $iniNuevo) {
            $hayConflicto = true;
            break;
        }
    }

    if ($hayConflicto) {
        $mensaje = 'Error (RF-01): El veterinario ya tiene una cita que se solapa con ese horario.';
        $tipoMsg = 'error';
    } else {
        $inicioISO = $fecha . 'T' . $hora;
        $finISO    = $fecha . 'T' . aHora($finNuevo);
        $idCita    = 'CIT-' . (int)(microtime(true) * 1000);

        $ins = $pdo->prepare("INSERT INTO citas (id_cita, id_mascota, id_veterinario, tipo, fecha_hora_inicio, fecha_hora_fin, estado) VALUES (?, ?, ?, ?, ?, ?, 'Pendiente')");
        $ins->execute([$idCita, $idMascota, $idVet, $tipo, $inicioISO, $finISO]);

        $mensaje = "Cita agendada con éxito ($hora - " . aHora($finNuevo) . ") en estado Pendiente.";
        $tipoMsg = 'success';
    }
}

$mascotas = $pdo->query("
    SELECT m.id_mascota, m.nombre, m.especie, t.nombre AS tutor_nombre
    FROM mascotas m
    JOIN tutores t ON m.id_tutor = t.id_tutor
    ORDER BY m.nombre ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Citas - VetAgenda</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="../menu_principal.php">
                <img src="../assets/logo.png" alt="Logo" class="brand-img">
                <span class="brand-text"><strong>VetAgenda</strong><small>Gestor de Citas</small></span>
            </a>
            <a href="../menu_principal.php" class="topbar-note" style="color:#fff; text-decoration:none;">← Volver al Menú</a>
        </div>
    </header>

    <main class="page" style="max-width: 640px;">
        <section class="card">
            <header class="card-head">
                <span class="tag">KAN-19 · RF-01 · D-01</span>
                <h2>Agendar nueva cita</h2>
            </header>
            <?php if ($mensaje): ?>
                <p class="mensaje <?= $tipoMsg ?>"><?= htmlspecialchars($mensaje) ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Mascota (Paciente)</label>
                    <select name="id_mascota" required>
                        <option value="">Seleccione una mascota...</option>
                        <?php foreach ($mascotas as $m): ?>
                            <option value="<?= htmlspecialchars($m['id_mascota']) ?>">
                                <?= htmlspecialchars($m['nombre']) ?> (<?= htmlspecialchars($m['especie']) ?>) - Tutor: <?= htmlspecialchars($m['tutor_nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Veterinario/a</label>
                        <select name="id_veterinario" required>
                            <?php foreach ($VETERINARIOS as $idV => $nomV): ?>
                                <option value="<?= htmlspecialchars($idV) ?>"><?= htmlspecialchars($nomV) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Cita</label>
                        <select name="cita_tipo" required>
                            <?php foreach ($servicios as $s): ?>
                                <option value="<?= htmlspecialchars($s['nombre_servicio']) ?>">
                                    <?= htmlspecialchars($s['nombre_servicio']) ?> (<?= (int)$s['duracion_minutos'] ?> min)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha</label>
                        <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Hora de inicio</label>
                        <input type="time" name="hora" value="10:00" min="09:00" max="17:30" required>
                    </div>
                </div>
                <p class="hint">La hora de término se calcula automáticamente según la duración del servicio (D-01) y el sistema rechaza solapamientos (RF-01).</p>
                <button type="submit" class="btn btn-primary">Generar Cita</button>
            </form>
        </section>
    </main>
</body>
</html>
