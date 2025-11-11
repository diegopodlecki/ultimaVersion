<?php
session_start();
require_once __DIR__ . '/funciones.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$accion = $_POST['accion'] ?? $_GET['accion'] ?? null;

// Guard de acciones sensibles
if (in_array($accion, ['actualizar','eliminar'], true) && !isAdmin()) {
    $_SESSION['error'] = "Acceso restringido: solo administradores pueden actualizar o eliminar.";
    header("Location: index.php");
    exit;
}

switch ($accion) {
    case 'insertar': {
        $data = [
            trim($_POST['nombre'] ?? ''),
            trim($_POST['apellido'] ?? ''),
            $_POST['dni'] ?? '',
            $_POST['cargo'] ?? '',
            $_POST['fecha'] ?? '',
            $_POST['horario'] ?? '',
            $_POST['espacio'] ?? '',
            (int)($_POST['duracion'] ?? 0)
        ];
        try {
            if (insertarReserva($data)) {
                $_SESSION['ok'] = "Reserva creada correctamente.";
            } else {
                $_SESSION['error'] = "Ya existe una reserva en ese espacio, fecha y horario.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        header("Location: index.php");
        exit;
    }
    case 'actualizar': {
        $data = [
            $_POST['nombre'] ?? '',
            $_POST['apellido'] ?? '',
            $_POST['dni'] ?? '',
            $_POST['cargo'] ?? '',
            $_POST['fecha'] ?? '',
            $_POST['horario'] ?? '',
            $_POST['espacio'] ?? '',
            (int)($_POST['duracion'] ?? 0),
            (int)($_POST['id'] ?? 0)
        ];
        try {
            if (actualizarReserva($data)) {
                $_SESSION['ok'] = "Reserva actualizada correctamente.";
            } else {
                $_SESSION['error'] = "Conflicto: ya existe otra reserva en ese espacio, fecha y horario.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        header("Location: index.php");
        exit;
    }
    case 'eliminar': {
        $id = (int)($_POST['id'] ?? 0);
        if (eliminarReserva($id)) {
            $_SESSION['ok'] = "Reserva eliminada.";
        } else {
            $_SESSION['error'] = "No se pudo eliminar la reserva.";
        }
        header("Location: index.php");
        exit;
    }
    default: {
        $_SESSION['error'] = "Acción no válida.";
        header("Location: index.php");
        exit;
    }
}
