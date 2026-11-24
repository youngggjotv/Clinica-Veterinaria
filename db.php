<?php
// Conexión a MySQL en InfinityFree 
$host   = 'sql208.infinityfree.com';   
$dbname = 'if0_43526920_db_vetagenda';    
$user   = 'if0_43526920';               
$pass   = 'W3OFAw8Rjp2j7o';        

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Crea las tablas automáticamente en MySQL si aún no existen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario VARCHAR(50) UNIQUE NOT NULL,
            clave VARCHAR(255) NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            rol VARCHAR(30) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS tutores (
            id_tutor VARCHAR(40) PRIMARY KEY,
            nombre VARCHAR(120) NOT NULL,
            correo VARCHAR(120) NOT NULL,
            telefono VARCHAR(30) NOT NULL,
            consentimiento TINYINT(1) NOT NULL DEFAULT 0,
            fecha_registro DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS mascotas (
            id_mascota VARCHAR(40) PRIMARY KEY,
            id_tutor VARCHAR(40) NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            especie VARCHAR(30) NOT NULL,
            FOREIGN KEY (id_tutor) REFERENCES tutores(id_tutor) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS servicios (
            id_servicio INT AUTO_INCREMENT PRIMARY KEY,
            nombre_servicio VARCHAR(80) UNIQUE NOT NULL,
            duracion_minutos INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS citas (
            id_cita VARCHAR(40) PRIMARY KEY,
            id_mascota VARCHAR(40) NOT NULL,
            id_veterinario VARCHAR(10) NOT NULL,
            tipo VARCHAR(60) NOT NULL,
            fecha_hora_inicio VARCHAR(25) NOT NULL,
            fecha_hora_fin VARCHAR(25) NOT NULL,
            estado VARCHAR(30) NOT NULL DEFAULT 'Pendiente',
            FOREIGN KEY (id_mascota) REFERENCES mascotas(id_mascota) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Usuarios iniciales de prueba
    if ((int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() === 0) {
        $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, clave, nombre, rol) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Dueña Clínica', 'admin']);
        $stmt->execute(['recepcion', password_hash('recepcion123', PASSWORD_DEFAULT), 'Recepcionista', 'recepcionista']);
        $stmt->execute(['cliente', password_hash('cliente123', PASSWORD_DEFAULT), 'Tutor Demo', 'cliente']);
    }

    // Servicios iniciales (Decisión D-01)
    if ((int)$pdo->query("SELECT COUNT(*) FROM servicios")->fetchColumn() === 0) {
        $stmtS = $pdo->prepare("INSERT INTO servicios (nombre_servicio, duracion_minutos) VALUES (?, ?)");
        $stmtS->execute(['Consulta general', 30]);
        $stmtS->execute(['Vacunación', 30]);
        $stmtS->execute(['Control', 20]);
        $stmtS->execute(['Cirugía menor', 60]);
    }
} catch (PDOException $e) {
    die('Error de conexión a la base de datos: ' . htmlspecialchars($e->getMessage()));
}

$VETERINARIOS = [
    '1' => 'Jose Lagos',
    '2' => 'Maria Rodriguez',
];