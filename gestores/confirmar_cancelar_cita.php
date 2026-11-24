<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
requerirLogin(['recepcionista', 'admin', 'cliente']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idCita      = $_POST['id_cita'] ?? '';
    $nuevoEstado = $_POST['nuevo_estado'] ?? '';
    $volver      = $_POST['volver'] ?? '/reportes/agenda_diaria.php';

    if (in_array($nuevoEstado, ['Confirmada', 'Cancelada'], true) && $idCita !== '') {
        $stmt = $pdo->prepare('UPDATE citas SET estado = ? WHERE id_cita = ?');
        $stmt->execute([$nuevoEstado, $idCita]);
    }
    header('Location: ' . $volver);
    exit;
}

header('Location: /menu_principal.php');
exit;
