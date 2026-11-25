<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
requerirLogin(['recepcionista', 'admin', 'cliente']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idCita      = $_POST['id_cita'] ?? '';
    $nuevoEstado = $_POST['nuevo_estado'] ?? '';
    $volver      = $_POST['volver'] ?? '/reportes/agenda_diaria.php';

    if (in_array($nuevoEstado, ['Confirmada', 'Cancelada'], true) && $idCita !== '') {
        // Regla RF-02 / A-05: solo se puede confirmar si sigue Pendiente
        if ($nuevoEstado === 'Confirmada') {
            $stmt = $pdo->prepare("UPDATE citas SET estado = 'Confirmada' WHERE id_cita = ? AND estado = 'Pendiente'");
            $stmt->execute([$idCita]);
        } else {
            // Al cancelar, el bloque queda libre para reprogramar (A-05)
            $stmt = $pdo->prepare("UPDATE citas SET estado = 'Cancelada' WHERE id_cita = ? AND estado != 'Cancelada'");
            $stmt->execute([$idCita]);
        }
    }
    header('Location: ' . $volver);
    exit;
}

header('Location: /menu_principal.php');
exit;
