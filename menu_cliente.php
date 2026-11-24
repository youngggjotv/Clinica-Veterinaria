<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
requerirLogin(['cliente', 'admin', 'recepcionista']);
$user = usuarioActual();

$citas = $pdo->query("
    SELECT c.*, m.nombre AS mascota_nombre, m.especie, t.nombre AS tutor_nombre
    FROM citas c
    JOIN mascotas m ON c.id_mascota = m.id_mascota
    JOIN tutores t ON m.id_tutor = t.id_tutor
    ORDER BY c.fecha_hora_inicio ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal del Tutor - VetAgenda</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="menu_cliente.php">
                <img src="assets/logo.png" alt="Logo" class="brand-img">
                <span class="brand-text">
                    <strong>VetAgenda</strong>
                    <small>Portal del Tutor · <?= htmlspecialchars($user['nombre']) ?></small>
                </span>
            </a>
            <a href="logout.php" class="topbar-note" style="color:#fff; text-decoration:none;">Cerrar sesión</a>
        </div>
    </header>

    <main class="page">
        <section class="card">
            <header class="card-head">
                <span class="tag">CU-02 · RF-02 · KAN-22</span>
                <h2>Confirmar o cancelar citas</h2>
            </header>
            <div class="tabla-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha y Horario</th>
                            <th>Veterinario</th>
                            <th>Mascota</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($citas)): ?>
                            <tr><td colspan="5" class="vacio">No hay citas registradas aún.</td></tr>
                        <?php else: ?>
                            <?php foreach ($citas as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars(str_replace('T', ' ', $c['fecha_hora_inicio'])) ?> - <?= htmlspecialchars(substr($c['fecha_hora_fin'], 11)) ?></td>
                                    <td><?= htmlspecialchars($VETERINARIOS[$c['id_veterinario']] ?? 'Desconocido') ?></td>
                                    <td><?= htmlspecialchars($c['mascota_nombre']) ?> (<?= htmlspecialchars($c['especie']) ?>)</td>
                                    <td><span class="badge badge-estado"><?= htmlspecialchars($c['estado']) ?></span></td>
                                    <td>
                                        <?php if ($c['estado'] === 'Pendiente'): ?>
                                            <form method="POST" action="gestores/confirmar_cancelar_cita.php" style="display:inline;">
                                                <input type="hidden" name="id_cita" value="<?= htmlspecialchars($c['id_cita']) ?>">
                                                <input type="hidden" name="nuevo_estado" value="Confirmada">
                                                <input type="hidden" name="volver" value="/menu_cliente.php">
                                                <button class="btn-mini btn-confirmar" type="submit">Confirmar</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($c['estado'] !== 'Cancelada'): ?>
                                            <form method="POST" action="gestores/confirmar_cancelar_cita.php" style="display:inline;">
                                                <input type="hidden" name="id_cita" value="<?= htmlspecialchars($c['id_cita']) ?>">
                                                <input type="hidden" name="nuevo_estado" value="Cancelada">
                                                <input type="hidden" name="volver" value="/menu_cliente.php">
                                                <button class="btn-mini btn-cancelar" type="submit">Cancelar</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
