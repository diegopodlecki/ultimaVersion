<?php
/**
 * index.php — Panel principal con reservas públicas
 */
session_start();
require_once __DIR__ . '/funciones.php';

// Reintroducir getDB() para compatibilidad
$db = getDB();

$hoy = date('Y-m-d');
$totalReservas = contarReservas();
$reservasHoy = contarReservasPorFecha($hoy);
$conflictos = detectarConflictos();
$lista = listarReservas();
$mensajeFlash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas — Panel</title>
    <link rel="stylesheet" href="estilo.css?v=<?= filemtime(__DIR__ . '/estilo.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<header>
    <h1><i class="fa-solid fa-calendar-check"></i> Reservas</h1>
    <nav>
        <a href="manual.php" class="btn secundario"><i class="fa-solid fa-book"></i> Manual</a>
        <?php if (isAdmin()): ?>
            <a href="logout.php" class="btn peligro"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
        <?php else: ?>
            <a href="login.php" class="btn primario"><i class="fa-solid fa-right-to-bracket"></i> Ingresar (Admin)</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <?php if ($mensajeFlash): ?>
        <div class="alerta info"><i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($mensajeFlash) ?></div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-item">
            <h3><i class="fa-solid fa-list"></i> Total de reservas</h3>
            <p><?= $totalReservas ?></p>
        </div>
        <div class="panel-item">
            <h3><i class="fa-solid fa-sun"></i> Hoy (<?= $hoy ?>)</h3>
            <p><?= $reservasHoy ?></p>
        </div>
        <div class="panel-item">
            <h3><i class="fa-solid fa-triangle-exclamation"></i> Conflictos</h3>
            <?php if (count($conflictos) === 0): ?>
                <p>Sin conflictos</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($conflictos as $c): ?>
                        <li><?= htmlspecialchars($c['fecha']) ?> <?= htmlspecialchars($c['horario']) ?> — <?= htmlspecialchars($c['espacio']) ?> (<?= (int)$c['cantidad'] ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>

    <?php if (isAdmin()): ?>
        <section class="acciones">
            <a href="editar.php" class="btn secundario"><i class="fa-solid fa-user-gear"></i> Panel Admin</a>
        </section>
    <?php endif; ?>

    <section class="tabla">
        <h2><i class="fa-solid fa-table"></i> Reservas registradas</h2>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>DNI</th>
                <th>Cargo</th>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Espacio</th>
                <th>Duración</th>
                <?php if (isAdmin()): ?><th>Acciones</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $r): ?>
                <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td><?= htmlspecialchars($r['nombre']) ?></td>
                    <td><?= htmlspecialchars($r['apellido']) ?></td>
                    <td><?= htmlspecialchars($r['dni']) ?></td>
                    <td><?= htmlspecialchars($r['cargo']) ?></td>
                    <td><?= htmlspecialchars($r['fecha']) ?></td>
                    <td><?= htmlspecialchars($r['horario']) ?></td>
                    <td><?= htmlspecialchars($r['espacio']) ?></td>
                    <td><?= (int)$r['duracion'] ?> min</td>
                    <?php if (isAdmin()): ?>
                        <td class="acciones">
                            <form method="GET" action="editar.php" class="inline">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                                <button type="submit" class="btn secundario"><i class="fa-solid fa-pen-to-square"></i> Editar</button>
                            </form>
                            <form method="POST" action="admin.php" class="inline" onsubmit="return confirm('¿Eliminar la reserva?');">
                                <input type="hidden" name="accion" value="eliminar" />
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                                <button type="submit" class="btn peligro"><i class="fa-solid fa-trash"></i> Eliminar</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="formulario">
        <h2><i class="fa-solid fa-plus"></i> Nueva reserva</h2>
        <form method="POST" action="admin.php">
            <input type="hidden" name="accion" value="insertar">

            <div class="grid">
                <label>Nombre
                    <input type="text" name="nombre" required>
                </label>
                <label>Apellido
                    <input type="text" name="apellido" required>
                </label>
                <label>DNI
                    <input type="text" name="dni" placeholder="Sólo números" required>
                </label>
                <label>Cargo
                    <select name="cargo" required>
                        <option value="Alumno">Alumno</option>
                        <option value="Profesor">Profesor</option>
                        <option value="Directivo">Directivo</option>
                        <option value="Personal">Personal</option>
                    </select>
                </label>
                <label>Fecha
                    <input type="date" name="fecha" value="<?= $hoy ?>" required>
                </label>
                <label>Horario
                    <input type="time" name="horario" required>
                </label>
                <label>Espacio
                    <select name="espacio" required>
                        <option value="Aula 1">Aula 1</option>
                        <option value="Aula 2">Aula 2</option>
                        <option value="Laboratorio">Laboratorio</option>
                        <option value="Gimnasio">Gimnasio</option>
                        <option value="Biblioteca">Biblioteca</option>
                    </select>
                </label>
                <label>Duración (minutos)
                    <input type="number" name="duracion" min="10" max="480" step="5" value="30" required>
                </label>
            </div>

            <button type="submit" class="btn primario"><i class="fa-solid fa-check"></i> Crear</button>
        </form>
    </section>
</main>

<footer>
    <small>Reservas v1 — Escuela</small>
</footer>
</body>
</html>
