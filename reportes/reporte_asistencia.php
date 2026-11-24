<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
requerirLogin(['admin']);

$totales = $pdo->query("
    SELECT estado, COUNT(*) AS cantidad
    FROM citas
    GROUP BY estado
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Asistencia - VetAgenda</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="../menu_admin.php">
                <img src="../assets/logo.png" alt="Logo" class="brand-img">
                <span class="brand-text"><strong>VetAgenda</strong><small>Reporte de Asistencia (KAN-23)</small></span>
            </a>
            <a href="../menu_admin.php" class="topbar-note" style="color:#fff; text-decoration:none;">← Volver</a>
        </div>
    </header>

    <main class="page" style="max-width: 720px;">
        <section class="card">
            <header class="card-head">
                <span class="tag">KAN-23</span>
                <h2>Resumen de estados de citas</h2>
            </header>
            <table>
                <thead><tr><th>Estado</th><th>Cantidad de citas</th></tr></thead>
                <tbody>
                    <?php if (empty($totales)): ?>
                        <tr><td colspan="2" class="vacio">Sin citas registradas todavía.</td></tr>
                    <?php else: ?>
                        <?php foreach ($totales as $fila): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($fila['estado']) ?></strong></td>
                                <td><?= (int)$fila['cantidad'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
