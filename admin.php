<?php
/**
 * admin.php — Controlador de acciones administrativas
 *
 * Responsabilidades:
 * - Verificar acceso (solo admin) y delegar acciones CRUD a funciones de dominio.
 * - Gestionar mensajes de éxito/error y redirecciones.
 * - Centralizar el flujo mediante `switch` sobre `$_POST['accion']`.
 *
 * Seguridad y buenas prácticas:
 * - Usa `isAdmin()` para validar sesión; no expone detalles sensibles.
 * - Aplica try/catch para capturar validaciones (DNI/duración) y errores de BD.
 * - Sanitiza entradas en `funciones.php` para evitar XSS.
 */
session_start();
require_once __DIR__ . '/funciones.php';

// Acceso restringido al rol admin
if (!isAdmin()) {
    redirect('login.php');
}

// Helper de redirección con mensaje
function redirConMensaje(string $url, string $mensaje): void {
    $_SESSION['flash'] = $mensaje;
    redirect($url);
}

$accion = $_POST['accion'] ?? null;

switch ($accion) {
    case 'insertar':
        // Orden esperado: nombre, apellido, dni, cargo, fecha, horario, espacio, duracion
        $datos = [
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
            if (!insertarReserva($datos)) {
                return redirConMensaje('index.php', 'Conflicto: ya existe una reserva para ese espacio/fecha/hora.');
            }
            return redirConMensaje('index.php', 'Reserva creada correctamente.');
        } catch (Throwable $e) {
            return redirConMensaje('index.php', 'Error al crear: ' . $e->getMessage());
        }

    case 'actualizar':
        // Orden esperado + id al final
        $datos = [
            $_POST['nombre'] ?? '',
            $_POST['apellido'] ?? '',
            $_POST['dni'] ?? '',
            $_POST['cargo'] ?? '',
            $_POST['fecha'] ?? '',
            $_POST['horario'] ?? '',
            $_POST['espacio'] ?? '',
            $_POST['duracion'] ?? 0,
            isset($_POST['id']) ? (int)$_POST['id'] : 0,
        ];
        try {
            if (!actualizarReserva($datos)) {
                return redirConMensaje('index.php', 'Conflicto: otra reserva ya ocupa ese espacio/fecha/hora.');
            }
            return redirConMensaje('index.php', 'Reserva actualizada correctamente.');
        } catch (Throwable $e) {
            return redirConMensaje('index.php', 'Error al actualizar: ' . $e->getMessage());
        }

    case 'eliminar':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        try {
            eliminarReserva($id);
            return redirConMensaje('index.php', 'Reserva eliminada.');
        } catch (Throwable $e) {
            return redirConMensaje('index.php', 'Error al eliminar: ' . $e->getMessage());
        }

    default:
        // Acción desconocida: regresar al panel
        redirect('index.php');
}
