<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = $_POST['clave'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ?');
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($clave, $user['clave'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nombre']     = $user['nombre'];
        $_SESSION['rol']        = $user['rol'];

        if ($user['rol'] === 'admin') {
            header('Location: menu_admin.php');
        } elseif ($user['rol'] === 'cliente') {
            header('Location: menu_cliente.php');
        } else {
            header('Location: menu_principal.php');
        }
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - VetAgenda</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="login.php">
                <img src="assets/logo.png" alt="Logo VetAgenda" class="brand-img">
                <span class="brand-text">
                    <strong>VetAgenda</strong>
                    <small>Clínica Veterinaria</small>
                </span>
            </a>
            <p class="topbar-note">Acceso al Sistema</p>
        </div>
    </header>

    <main class="page" style="max-width: 440px;">
        <section class="card">
            <header class="card-head">
                <span class="tag">AUTENTICACIÓN</span>
                <h2>Iniciar sesión</h2>
            </header>
            <?php if ($error): ?>
                <p class="mensaje error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" required placeholder="recepcion / admin / cliente">
                </div>
                <div class="form-group">
                    <label for="clave">Contraseña</label>
                    <input type="password" id="clave" name="clave" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary">Ingresar</button>
            </form>
            <p class="hint" style="margin-top: 1rem;">
                <strong>Cuentas de prueba:</strong><br>
                Recepción: <code>recepcion</code> / <code>recepcion123</code><br>
                Admin: <code>admin</code> / <code>admin123</code><br>
                Tutor: <code>cliente</code> / <code>cliente123</code>
            </p>
        </section>
    </main>
</body>
</html>
