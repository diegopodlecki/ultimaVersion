<?php
/**
 * admin.php — Controlador de acciones (inserción pública, edición/eliminación sólo admin)
 */
session_start();
require_once __DIR__ . '/funciones.php';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? null;

function redir($msg) {
    $_SESSION['flash'] = $msg;
    header('Location: index.php');
    exit;
}

if (!$accion) {
    redir('Acción no especificada');
}

// Permitir insertar sin login, restringir editar/eliminar a admin
switch ($accion) {
    case 'insertar': {
        // Orden requerido: [nombre, apellido, dni, cargo, fecha, horario, espacio, duracion]
        $data = [
            $_POST['nombre'] ?? '',
            $_POST['apellido'] ?? '',
            $_POST['dni'] ?? '',
            $_POST['cargo'] ?? '',
            $_POST['fecha'] ?? '',
            $_POST['horario'] ?? '',
            $_POST['espacio'] ?? '',
            $_POST['duracion'] ?? 0,
        ];
        try {
            $ok = insertarReserva($data);
            if (!$ok) {
                redir('Ya existe una reserva con mismo espacio/fecha/horario.');
            }
            redir('Reserva creada correctamente.');
        } catch (Throwable $e) {
            redir('Error al crear: ' . $e->getMessage());
        }
        break;
    }
    case 'actualizar': {
        if (!isAdmin()) { redir('Acción restringida a administrador'); }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        // Orden requerido + id al final (9 elementos)
        $data = [
            $_POST['nombre'] ?? '',
            $_POST['apellido'] ?? '',
            $_POST['dni'] ?? '',
            $_POST['cargo'] ?? '',
            $_POST['fecha'] ?? '',
            $_POST['horario'] ?? '',
            $_POST['espacio'] ?? '',
            $_POST['duracion'] ?? 0,
            $id,
        ];
        try {
            $ok = actualizarReserva($data);
            if (!$ok) {
                redir('Conflicto: otra reserva ya ocupa ese espacio/fecha/hora.');
            }
            redir('Reserva actualizada correctamente.');
        } catch (Throwable $e) {
            redir('Error al actualizar: ' . $e->getMessage());
        }
        break;
    }
    case 'eliminar': {
        if (!isAdmin()) { redir('Acción restringida a administrador'); }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        try {
            $ok = eliminarReserva($id);
            redir($ok ? 'Reserva eliminada.' : 'No se pudo eliminar.');
        } catch (Throwable $e) {
            redir('Error al eliminar: ' . $e->getMessage());
        }
        break;
    }
    default:
        redir('Acción inválida');
}
