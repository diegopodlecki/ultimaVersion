<?php
/**
 * funciones.php — Lógica de dominio del sistema de reservas
 *
 * Responsabilidades:
 * - Proveer helpers de autenticación y utilidades de redirección.
 * - Centralizar validaciones de datos (DNI, duración, formatos).
 * - Implementar CRUD sobre la tabla `reservas` y métricas de estado.
 *
 * Dependencias:
 * - Requiere `db.php`, que inicializa `$db` (PDO SQLite) como variable global.
 * - Todas las funciones de acceso a datos usan `global $db`.
 *
 * Convenciones de datos:
 * - Orden de arrays en insertar/actualizar: [nombre, apellido, dni, cargo, fecha, horario, espacio, duracion] (+ id al final en actualizar).
 * - Los formatos esperados son: fecha `YYYY-MM-DD`, horario `HH:MM`, DNI numérico (7-8 dígitos), duración entre 10-480 minutos.
 *
 * Errores:
 * - Lanza `RuntimeException` si la conexión `$db` no está inicializada.
 * - Lanza `InvalidArgumentException`/`Exception` ante datos inválidos.
 * - En caso de conflicto de reserva, algunas funciones devuelven `false` en lugar de lanzar excepción.
 */
require_once __DIR__ . '/db.php';

// Helpers de autenticación
/** Devuelve `true` si la sesión actual tiene rol administrador. */
function isAdmin(): bool {
    return !empty($_SESSION['admin']);
}

// Validaciones y utilidades comunes
/** Redirige a la ruta dada y termina el script. */
function redirect(string $ruta): void {
    header("Location: {$ruta}");
    exit;
}

/** Valida DNI con 7-8 dígitos numéricos. */
function validarDni(string $dni): void {
    if (!preg_match('/^\d{7,8}$/', $dni)) {
        throw new Exception('DNI inválido. Debe tener 7-8 dígitos.');
    }
}

/** Valida que la duración esté entre 10 y 480 minutos. */
function validarDuracion(int $minutos): void {
    if ($minutos < 10 || $minutos > 480) {
        throw new Exception('Duración inválida (rango: 10 a 480 minutos).');
    }
}

/* =======================
   UTILIDAD: duplicados
   ======================= */
/**
 * Verifica si existe una reserva duplicada (misma fecha + horario + espacio).
 * Si `$idExcluir` se pasa, excluye ese ID (útil al actualizar).
 */
function existeReserva(string $fecha, string $horario, string $espacio, ?int $idExcluir = null): bool {
    global $db;
    if (!$db instanceof PDO) {
        throw new RuntimeException('Conexión a base de datos no inicializada ($db).');
    }
    if ($idExcluir !== null) {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM reservas
             WHERE fecha = ? AND horario = ? AND espacio = ? AND id <> ?"
        );
        $stmt->execute([$fecha, $horario, $espacio, $idExcluir]);
    } else {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM reservas
             WHERE fecha = ? AND horario = ? AND espacio = ?"
        );
        $stmt->execute([$fecha, $horario, $espacio]);
    }
    return (int)$stmt->fetchColumn() > 0;
}

/* ============
   CREATE
   ============ */
/**
 * Inserta una nueva reserva.
 * - Sanitiza y valida los campos de entrada.
 * - Rechaza duplicados devolviendo `false`.
 */
function insertarReserva(array $data): bool {
    global $db;
    if (!$db instanceof PDO) {
        throw new RuntimeException('Conexión a base de datos no inicializada ($db).');
    }
    if (count($data) < 8) {
        throw new InvalidArgumentException('insertarReserva: $data incompleto (se esperan 8 elementos).');
    }
    // Sanitización de datos
    $data[0] = htmlspecialchars(trim($data[0]), ENT_QUOTES, 'UTF-8'); // nombre
    $data[1] = htmlspecialchars(trim($data[1]), ENT_QUOTES, 'UTF-8'); // apellido
    $data[2] = preg_replace('/\D/', '', $data[2]); // dni: solo números
    $data[3] = htmlspecialchars(trim($data[3]), ENT_QUOTES, 'UTF-8'); // cargo
    $data[4] = htmlspecialchars(trim($data[4]), ENT_QUOTES, 'UTF-8'); // fecha
    $data[5] = htmlspecialchars(trim($data[5]), ENT_QUOTES, 'UTF-8'); // horario
    $data[6] = htmlspecialchars(trim($data[6]), ENT_QUOTES, 'UTF-8'); // espacio
    $data[7] = (int)$data[7]; // duración
    // Validaciones centralizadas
    validarDni($data[2]);
    validarDuracion($data[7]);
    // Formatos básicos
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data[4])) {
        throw new InvalidArgumentException('Formato de fecha inválido.');
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $data[5])) {
        throw new InvalidArgumentException('Formato de horario inválido.');
    }
    // Duplicado
    if (existeReserva($data[4], $data[5], $data[6])) {
        return false;
    }
    $stmt = $db->prepare(
        "INSERT INTO reservas (nombre, apellido, dni, cargo, fecha, horario, espacio, duracion)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    return $stmt->execute($data);
}

/* ============
   UPDATE
   ============ */
/**
 * Actualiza una reserva existente.
 * - Valida datos y evita duplicados contra otros registros.
 */
function actualizarReserva(array $data): bool {
    global $db;
    if (!$db instanceof PDO) {
        throw new RuntimeException('Conexión a base de datos no inicializada ($db).');
    }
    if (count($data) < 9) {
        throw new InvalidArgumentException('actualizarReserva: $data incompleto (se esperan 9 elementos).');
    }
    $id = (int)$data[8];
    // Evitar duplicado distinto del mismo ID
    if (existeReserva($data[4], $data[5], $data[6], $id)) {
        return false;
    }
    $stmt = $db->prepare(
        "UPDATE reservas
         SET nombre = ?, apellido = ?, dni = ?, cargo = ?, fecha = ?, horario = ?, espacio = ?, duracion = ?
         WHERE id = ?"
    );
    return $stmt->execute($data);
}

/* ============
   DELETE
   ============ */
/** Elimina una reserva por ID. */
function eliminarReserva(int $id): bool {
    global $db;
    if (!$db instanceof PDO) {
        throw new RuntimeException('Conexión a base de datos no inicializada ($db).');
    }
    $stmt = $db->prepare("DELETE FROM reservas WHERE id = ?");
    return $stmt->execute([$id]);
}

/* ============
   READ
   ============ */
/** Lista todas las reservas (ordenadas DESC por id). */
function listarReservas(): array {
    global $db;
    if (!$db instanceof PDO) {
        throw new RuntimeException('Conexión a base de datos no inicializada ($db).');
    }
    $stmt = $db->query("SELECT * FROM reservas ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Obtiene una reserva por ID. */
function obtenerReservaPorId(int $id) {
    global $db;
    if (!$db instanceof PDO) {
        throw new RuntimeException('Conexión a base de datos no inicializada ($db).');
    }
    $stmt = $db->prepare("SELECT * FROM reservas WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ===========================
   MÉTRICAS PARA EL PANEL
   =========================== */
/** Total de reservas registradas. */
function contarReservas(): int {
    global $db;
    if (!$db instanceof PDO) {
        throw new RuntimeException('Conexión a base de datos no inicializada ($db).');
    }
    $stmt = $db->query("SELECT COUNT(*) FROM reservas");
    return (int)$stmt->fetchColumn();
}

/** Total de reservas para una fecha dada. */
function contarReservasPorFecha(string $fecha): int {
    global $db;
    if (!$db instanceof PDO) {
        throw new RuntimeException('Conexión a base de datos no inicializada ($db).');
    }
    $stmt = $db->prepare("SELECT COUNT(*) FROM reservas WHERE fecha = ?");
    $stmt->execute([$fecha]);
    return (int)$stmt->fetchColumn();
}

/** Detecta conflictos (mismo espacio+fecha+horario con más de 1 reserva). */
function detectarConflictos(): array {
    global $db;
    if (!$db instanceof PDO) {
        throw new RuntimeException('Conexión a base de datos no inicializada ($db).');
    }
    $sql = "
        SELECT fecha, horario, espacio, COUNT(*) AS cantidad
        FROM reservas
        GROUP BY fecha, horario, espacio
        HAVING cantidad > 1
    ";
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
