<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
requerirLogin(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre_servicio'] ?? '');
    $duracion = (int)($_POST['duracion_minutos'] ?? 30);
    if ($nombre && $duracion > 0) {
        $stmt = $pdo->prepare('REPLACE INTO servicios (nombre_servicio, duracion_minutos) VALUES (?, ?)');
        $stmt->execute([$nombre, $duracion]);
    }
}

$servicios = $pdo->query('SELECT * FROM servicios ORDER BY nombre_servicio ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenedor de Servicios - VetAgenda</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="../menu_admin.php">
                <img src="../assets/logo.png" alt="Logo" class="brand-img">
                <span class="brand-text"><strong>VetAgenda</strong><small>Mantenedor de Servicios (D-01)</small></span>
            </a>
            <a href="../menu_admin.php" class="topbar-note" style="color:#fff; text-decoration:none;">← Volver</a>
        </div>
    </header>

    <main class="page">
        <div class="layout">
            <section class="card">
                <header class="card-head">
                    <span class="tag">DECISIÓN D-01</span>
                    <h2>Agregar o actualizar servicio</h2>
                </header>
                <form method="POST">
                    <div class="form-group">
                        <label>Nombre del servicio</label>
                        <input type="text" name="nombre_servicio" required placeholder="Ej. Control post-operatorio">
                    </div>
                    <div class="form-group">
                        <label>Duración (minutos)</label>
                        <input type="number" name="duracion_minutos" value="30" min="10" max="180" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Servicio</button>
                </form>
            </section>

            <section class="card">
                <header class="card-head">
                    <h2>Servicios configurados</h2>
                </header>
                <table>
                    <thead><tr><th>Servicio</th><th>Duración</th></tr></thead>
                    <tbody>
                        <?php foreach ($servicios as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['nombre_servicio']) ?></td>
                                <td><?= (int)$s['duracion_minutos'] ?> min</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>
</body>
</html>
