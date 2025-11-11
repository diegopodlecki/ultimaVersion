<?php
/**
 * login.php — Formulario y procesamiento de inicio de sesión (Admin)
 *
 * Responsabilidades:
 * - Mostrar formulario de autenticación para el rol administrador.
 * - Verificar credenciales y establecer `$_SESSION['admin'] = true`.
 * - Redirigir al panel principal (`index.php`) en caso de éxito.
 *
 * Seguridad:
 * - Regenera el ID de sesión tras login exitoso para mitigar fijación de sesión.
 * - Usa `password_hash`/`password_verify` para verificar la contraseña.
 * - Evita filtrar detalles del error (mensaje genérico).
 */
session_start();

// Credenciales: usuario "admin" con contraseña "admin123"
$usuario = 'admin';
$passwordPlano = 'admin123';
// Hash generado previamente con password_hash($passwordPlano, PASSWORD_DEFAULT)
$hashContrasenia = password_hash($passwordPlano, PASSWORD_DEFAULT);

$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['usuario'] ?? '');
    $pass = $_POST['contrasenia'] ?? '';

    if ($user === $usuario && password_verify($pass, $hashContrasenia)) {
        session_regenerate_id(true); // primero regenerar ID
        $_SESSION['admin'] = true;   // luego marcar rol admin
        header('Location: index.php');
        exit;
    } else {
        $mensaje = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar — Admin</title>
    <link rel="stylesheet" href="estilo.css?v=<?= filemtime(__DIR__ . '/estilo.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<header>
    <h1><i class="fa-solid fa-user-shield"></i> Iniciar sesión</h1>
</header>
<main>
    <?php if ($mensaje): ?>
        <div class="alerta peligro"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php" class="formulario">
        <div class="grid">
            <label>Usuario
                <input type="text" name="usuario" required>
            </label>
            <label>Contraseña
                <input type="password" name="contrasenia" required>
            </label>
        </div>
        <button type="submit" class="btn primario"><i class="fa-solid fa-right-to-bracket"></i> Ingresar</button>
    </form>
</main>
<footer>
    <small>Reservas v1 — Escuela</small>
</footer>
</body>
</html>


