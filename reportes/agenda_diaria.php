<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
requerirLogin(['recepcionista', 'admin']);

$fechaFiltro = $_GET['fecha'] ?? date('Y-m-d');
$vetFiltro   = $_GET['veterinario'] ?? '';
$estadoFiltro = $_GET['estado'] ?? '';

$sql = "
    SELECT c.*, m.nombre AS mascota_nombre, m.especie
    FROM citas c
    JOIN mascotas m ON c.id_mascota = m.id_mascota
    WHERE c.fecha_hora_inicio LIKE ?
";
$params = [$fechaFiltro . '%'];

if ($vetFiltro !== '') {
    $sql .= " AND c.id_veterinario = ?";
    $params[] = $vetFiltro;
}
if ($estadoFiltro !== '') {
    $sql .= " AND c.estado = ?";
    $params[] = $estadoFiltro;
}
$sql .= " ORDER BY c.fecha_hora_inicio ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$citas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Diaria - VetAgenda</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="../menu_principal.php">
                <img src="../assets/logo.png" alt="Logo" class="brand-img">
                <span class="brand-text"><strong>VetAgenda</strong><small>Agenda Diaria por Veterinario</small></span>
            </a>
            <a href="../menu_principal.php" class="topbar-note" style="color:#fff; text-decoration:none;">← Volver al Menú</a>
        </div>
    </header>

    <main class="page">
        <section class="card">
            <header class="card-head card-head-split">
                <div>
                    <span class="tag">KAN-20 · RF-04 · KAN-22</span>
                    <h2>Agenda diaria por veterinario</h2>
                </div>
                <form method="GET" class="filtros">
                    <input type="date" name="fecha" value="<?= htmlspecialchars($fechaFiltro) ?>">
                    <select name="veterinario">
                        <option value="">Todos los veterinarios</option>
                        <?php foreach ($VETERINARIOS as $idV => $nomV): ?>
                            <option value="<?= htmlspecialchars($idV) ?>" <?= $vetFiltro === $idV ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nomV) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="estado">
                        <option value="">Todos los estados</option>
                        <option value="Pendiente" <?= $estadoFiltro === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="Confirmada" <?= $estadoFiltro === 'Confirmada' ? 'selected' : '' ?>>Confirmada</option>
                        <option value="Cancelada" <?= $estadoFiltro === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                    <button type="submit" class="btn btn-ghost">Ver agenda</button>
                </form>
            </header>

            <div class="tabla-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Horario</th>
                            <th>Veterinario</th>
                            <th>Paciente / Especie</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($citas)): ?>
                            <tr><td colspan="6" class="vacio"><strong>Día tranquilo</strong>No hay citas para este filtro.</td></tr>
                        <?php else: ?>
                            <?php foreach ($citas as $c): ?>
                                <tr>
                                    <td class="hora"><?= htmlspecialchars(substr($c['fecha_hora_inicio'], 11, 5)) ?> - <?= htmlspecialchars(substr($c['fecha_hora_fin'], 11, 5)) ?></td>
                                    <td><span class="badge <?= $c['id_veterinario'] === '2' ? 'badge-vet2' : 'badge-vet1' ?>"><?= htmlspecialchars($VETERINARIOS[$c['id_veterinario']] ?? 'Desconocido') ?></span></td>
                                    <td class="mascota"><?= htmlspecialchars($c['mascota_nombre']) ?> (<?= htmlspecialchars($c['especie']) ?>)</td>
                                    <td><?= htmlspecialchars($c['tipo']) ?></td>
                                    <td><span class="badge badge-estado"><?= htmlspecialchars($c['estado']) ?></span></td>
                                    <td>
                                        <?php if ($c['estado'] === 'Pendiente'): ?>
                                            <form method="POST" action="../gestores/confirmar_cancelar_cita.php" style="display:inline;">
                                                <input type="hidden" name="id_cita" value="<?= htmlspecialchars($c['id_cita']) ?>">
                                                <input type="hidden" name="nuevo_estado" value="Confirmada">
                                                <input type="hidden" name="volver" value="/reportes/agenda_diaria.php?fecha=<?= urlencode($fechaFiltro) ?>&veterinario=<?= urlencode($vetFiltro) ?>">
                                                <button class="btn-mini btn-confirmar" type="submit">Confirmar</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($c['estado'] !== 'Cancelada'): ?>
                                            <form method="POST" action="../gestores/confirmar_cancelar_cita.php" style="display:inline;">
                                                <input type="hidden" name="id_cita" value="<?= htmlspecialchars($c['id_cita']) ?>">
                                                <input type="hidden" name="nuevo_estado" value="Cancelada">
                                                <input type="hidden" name="volver" value="/reportes/agenda_diaria.php?fecha=<?= urlencode($fechaFiltro) ?>&veterinario=<?= urlencode($vetFiltro) ?>">
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
