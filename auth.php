<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requerirLogin(array $rolesPermitidos = []): void {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: /login.php');
        exit;
    }
    if (!empty($rolesPermitidos) && !in_array($_SESSION['rol'] ?? '', $rolesPermitidos, true)) {
        http_response_code(403);
        echo '<h2>Acceso denegado</h2><p>No tienes permisos para acceder a esta sección.</p><a href="/login.php">Volver</a>';
        exit;
    }
}

function usuarioActual(): array {
    return [
        'id'     => $_SESSION['usuario_id'] ?? null,
        'nombre' => $_SESSION['nombre'] ?? 'Invitado',
        'rol'    => $_SESSION['rol'] ?? '',
    ];
}
