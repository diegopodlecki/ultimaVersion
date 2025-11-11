<?php
/**
 * editar.php — Formulario de edición de reserva
 *
 * Responsabilidades:
 * - Restringir acceso a administradores usando `isAdmin()`.
 * - Cargar la reserva por ID y poblar el formulario.
 * - Enviar la edición a `admin.php` para validación y persistencia.
 */
session_start();
require_once __DIR__ . '/funciones.php';

if (!isAdmin()) {
    redirect('login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Volver a usar getDB() y consulta directa con PDO
$db = getDB();
$reserva = null;
if ($id) {
    $stmt = $db->prepare("SELECT * FROM reservas WHERE id = ?");
    $stmt->execute([$id]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$reserva) {
    $_SESSION['flash'] = 'Reserva no encontrada.';
    redirect('index.php');
}

$hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar reserva #<?= (int)$reserva['id'] ?></title>
    <link rel="stylesheet" href="estilo.css?v=<?= filemtime(__DIR__ . '/estilo.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<header>
    <h1><i class="fa-solid fa-pen-to-square"></i> Editar reserva #<?= (int)$reserva['id'] ?></h1>
    <nav>
        <a href="index.php" class="btn secundario"><i class="fa-solid fa-arrow-left"></i> Volver</a>
        <a href="logout.php" class="btn peligro"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
    </nav>
</header>

<main>
    <section class="formulario">
        <form method="POST" action="admin.php">
            <input type="hidden" name="accion" value="actualizar">
            <input type="hidden" name="id" value="<?= (int)$reserva['id'] ?>">

            <div class="grid">
                <label>Nombre
                    <input type="text" name="nombre" value="<?= htmlspecialchars($reserva['nombre']) ?>" required>
                </label>
                <label>Apellido
                    <input type="text" name="apellido" value="<?= htmlspecialchars($reserva['apellido']) ?>" required>
                </label>
                <label>DNI
                    <input type="text" name="dni" value="<?= htmlspecialchars($reserva['dni']) ?>" required>
                </label>
                <label>Cargo
                    <select name="cargo" required>
                        <?php
                        $cargos = ['Alumno','Profesor','Directivo','Personal'];
                        foreach ($cargos as $c) {
                            $sel = ($reserva['cargo'] === $c) ? 'selected' : '';
                            echo "<option value=\"{$c}\" {$sel}>{$c}</option>";
                        }
                        ?>
                    </select>
                </label>
                <label>Fecha
                    <input type="date" name="fecha" value="<?= htmlspecialchars($reserva['fecha']) ?>" required>
                </label>
                <label>Horario
                    <input type="time" name="horario" value="<?= htmlspecialchars($reserva['horario']) ?>" required>
                </label>
                <label>Espacio
                    <select name="espacio" required>
                        <?php
                        $espacios = ['Aula 1','Aula 2','Laboratorio','Gimnasio','Biblioteca'];
                        foreach ($espacios as $e) {
                            $sel = ($reserva['espacio'] === $e) ? 'selected' : '';
                            echo "<option value=\"{$e}\" {$sel}>{$e}</option>";
                        }
                        ?>
                    </select>
                </label>
                <label>Duración (minutos)
                    <input type="number" name="duracion" min="10" max="480" step="5" value="<?= (int)$reserva['duracion'] ?>" required>
                </label>
            </div>

            <button type="submit" class="btn primario"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button>
        </form>
    </section>
</main>

<footer>
    <small>Reservas v1 — Escuela</small>
</footer>
</body>
</html>
