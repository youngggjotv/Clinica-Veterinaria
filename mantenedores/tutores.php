<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
requerirLogin(['recepcionista', 'admin']);

$mensaje = '';
$tipoMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombreTutor    = trim($_POST['tutor_nombre'] ?? '');
        $correo         = trim($_POST['tutor_correo'] ?? '');
        $telefono       = trim($_POST['tutor_telefono'] ?? '');
        $consentimiento = isset($_POST['tutor_consentimiento']) ? 1 : 0;
        $nombreMascota  = trim($_POST['mascota_nombre'] ?? '');
        $especie        = $_POST['mascota_especie'] ?? 'Perro';

        $stmtDup = $pdo->prepare('SELECT COUNT(*) FROM tutores WHERE LOWER(correo) = LOWER(?)');
        $stmtDup->execute([$correo]);
        $correoExiste = (int)$stmtDup->fetchColumn() > 0;

        if (!$consentimiento) {
            $mensaje = 'RNF-01: Debe otorgar consentimiento explícito (Ley 21.719).';
            $tipoMsg = 'error';
        } elseif (!preg_match('/^[0-9+\s-]{8,15}$/', $telefono)) {
            $mensaje = 'RF-03: Ingrese un número de teléfono válido (8 a 15 dígitos).';
            $tipoMsg = 'error';
        } elseif ($correoExiste) {
            $mensaje = 'RF-03: Ya existe un tutor registrado con ese correo electrónico.';
            $tipoMsg = 'error';
        } elseif ($nombreTutor && $correo && $telefono && $nombreMascota) {
            $ts = (int)(microtime(true) * 1000);
            $idTutor   = 'TUT-' . $ts;
            $idMascota = 'MAS-' . $ts;

            $stmtT = $pdo->prepare('INSERT INTO tutores (id_tutor, nombre, correo, telefono, consentimiento, fecha_registro) VALUES (?, ?, ?, ?, ?, ?)');
            $stmtT->execute([$idTutor, $nombreTutor, $correo, $telefono, $consentimiento, date('Y-m-d H:i:s')]);

            $stmtM = $pdo->prepare('INSERT INTO mascotas (id_mascota, id_tutor, nombre, especie) VALUES (?, ?, ?, ?)');
            $stmtM->execute([$idMascota, $idTutor, $nombreMascota, $especie]);

            $mensaje = "Tutor $nombreTutor y mascota $nombreMascota registrados con éxito.";
            $tipoMsg = 'success';
        }
    } elseif ($accion === 'editar') {
        $idEditar   = $_POST['id_tutor'] ?? '';
        $nuevoMail  = trim($_POST['nuevo_correo'] ?? '');
        $nuevoFono  = trim($_POST['nuevo_telefono'] ?? '');
        if ($idEditar && $nuevoMail && $nuevoFono) {
            $stmtUp = $pdo->prepare('UPDATE tutores SET correo = ?, telefono = ? WHERE id_tutor = ?');
            $stmtUp->execute([$nuevoMail, $nuevoFono, $idEditar]);
            $mensaje = 'RNF-01: Datos de contacto del tutor actualizados correctamente.';
            $tipoMsg = 'success';
        } // <-- 1. Llave de cierre agregada
    } elseif ($accion === 'editar') {
        $idEditar   = $_POST['id_tutor'] ?? '';
        $nuevoMail  = trim($_POST['nuevo_correo'] ?? '');
        $nuevoFono  = trim($_POST['nuevo_telefono'] ?? '');
        if ($idEditar && $nuevoMail && $nuevoFono) {
            $stmtUp = $pdo->prepare('UPDATE tutores SET correo = ?, telefono = ? WHERE id_tutor = ?');
            $stmtUp->execute([$nuevoMail, $nuevoFono, $idEditar]);
            $mensaje = 'RNF-01: Datos de contacto del tutor actualizados correctamente.';
            $tipoMsg = 'success';
        } // <-- 1. Llave de cierre agregada
    } elseif ($accion === 'eliminar') { // <-- 2. Acción eliminar agregada
        $idEliminar = $_POST['id_tutor'] ?? '';
        if ($idEliminar) {
            $stmtDelM = $pdo->prepare('DELETE FROM mascotas WHERE id_tutor = ?');
            $stmtDelM->execute([$idEliminar]);

            $stmtDelT = $pdo->prepare('DELETE FROM tutores WHERE id_tutor = ?');
            $stmtDelT->execute([$idEliminar]);

            $mensaje = 'RNF-01: Datos del tutor y sus mascotas eliminados correctamente.';
            $tipoMsg = 'success';
        }
    }
}

$tutores = $pdo->query("
    SELECT t.*, COUNT(m.id_mascota) AS total_mascotas
    FROM tutores t
    LEFT JOIN mascotas m ON m.id_tutor = t.id_tutor
    GROUP BY t.id_tutor
    ORDER BY t.fecha_registro DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenedor de Tutores - VetAgenda</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="../menu_principal.php">
                <img src="../assets/logo.png" alt="Logo" class="brand-img">
                <span class="brand-text"><strong>VetAgenda</strong><small>Mantenedor de Tutores y Mascotas</small></span>
            </a>
            <a href="../menu_principal.php" class="topbar-note" style="color:#fff; text-decoration:none;">← Volver al Menú</a>
        </div>
    </header>

    <main class="page">
        <div class="layout">
            <section class="card">
                <header class="card-head">
                    <span class="tag">KAN-18 · RF-03</span>
                    <h2>Registrar tutor y mascota</h2>
                </header>
                <?php if ($mensaje): ?>
                    <p class="mensaje <?= $tipoMsg ?>"><?= htmlspecialchars($mensaje) ?></p>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="accion" value="crear">
                    <div class="form-group">
                        <label>Nombre del tutor</label>
                        <input type="text" name="tutor_nombre" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Correo</label>
                            <input type="email" name="tutor_correo" required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="tel" name="tutor_telefono" required>
                        </div>
                    </div>
                    <div class="form-group" style="display:flex; align-items:center; gap:0.5rem;">
                        <input type="checkbox" id="cons" name="tutor_consentimiento" style="width:auto;" required>
                        <label for="cons" style="margin:0; font-weight:normal;">Consentimiento uso de datos (Ley 21.719)</label>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre mascota</label>
                            <input type="text" name="mascota_nombre" required>
                        </div>
                        <div class="form-group">
                            <label>Especie</label>
                            <select name="mascota_especie">
                                <option value="Perro">Perro</option>
                                <option value="Gato">Gato</option>
                                <option value="Exótico">Exótico</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Registrar Entidades</button>
                </form>
            </section>

            <section class="card">
                <header class="card-head">
                    <span class="tag">KAN-26 · RNF-01</span>
                    <h2>Tutores registrados (Gestión de datos)</h2>
                </header>
                <div class="tabla-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Tutor</th>
                                <th>Correo / Teléfono</th>
                                <th>Mascotas</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tutores)): ?>
                                <tr><td colspan="4" class="vacio">Aún no hay tutores registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($tutores as $t): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($t['nombre']) ?></strong></td>
                                        <td><?= htmlspecialchars($t['correo']) ?><br><small><?= htmlspecialchars($t['telefono']) ?></small></td>
                                        <td><?= (int)$t['total_mascotas'] ?></td>
                                        <td>
                                            <form method="POST" style="display:flex; gap:4px; margin-bottom:6px; flex-wrap:wrap;">
                                                <input type="hidden" name="accion" value="editar">
                                                <input type="hidden" name="id_tutor" value="<?= htmlspecialchars($t['id_tutor']) ?>">
                                                <input type="email" name="nuevo_correo" value="<?= htmlspecialchars($t['correo']) ?>" required style="padding:4px 6px; font-size:0.78rem; width:140px;">
                                                <input type="tel" name="nuevo_telefono" value="<?= htmlspecialchars($t['telefono']) ?>" required style="padding:4px 6px; font-size:0.78rem; width:100px;">
                                                <button type="submit" class="btn-mini btn-confirmar">Actualizar</button>
                                            </form>
                                            <form method="POST" onsubmit="return confirm('¿Eliminar datos de este tutor?');">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id_tutor" value="<?= htmlspecialchars($t['id_tutor']) ?>">
                                                <button type="submit" class="btn-mini btn-cancelar">Eliminar datos</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
