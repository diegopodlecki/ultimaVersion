<?php
/**
 * db.php — Inicialización de la base de datos (SQLite via PDO)
 *
 * Responsabilidades:
 * - Crear la conexión PDO y exponerla como `$db` global.
 * - Asegurar la existencia de la tabla `reservas` mediante `CREATE TABLE IF NOT EXISTS`.
 * - Configurar modos de error y algunas `PRAGMA` útiles para SQLite.
 *
 * Notas:
 * - El archivo no define funciones; es requerido por `funciones.php`.
 * - No se retorna; se espera que `$db` quede inicializado.
 */

try {
    $dbPath = __DIR__ . '/reservas.db';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // PRAGMA recomendadas para SQLite en aplicaciones pequeñas
    $db->exec('PRAGMA foreign_keys = ON;');
    $db->exec('PRAGMA journal_mode = WAL;');
    $db->exec('PRAGMA synchronous = NORMAL;');

    // Migración: crear tabla si no existe
    $db->exec('CREATE TABLE IF NOT EXISTS reservas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        apellido TEXT NOT NULL,
        dni TEXT NOT NULL,
        cargo TEXT NOT NULL,
        fecha TEXT NOT NULL,
        horario TEXT NOT NULL,
        espacio TEXT NOT NULL,
        duracion INTEGER NOT NULL
    )');

    // Índices opcionales (mejoran búsquedas y detección de duplicados)
    $db->exec('CREATE INDEX IF NOT EXISTS idx_reservas_fecha ON reservas(fecha)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_reservas_horario ON reservas(horario)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_reservas_espacio ON reservas(espacio)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_reservas_dni ON reservas(dni)');

} catch (Throwable $e) {
    // En producción conviene loguear y mostrar un mensaje genérico
    die('Error de base de datos: ' . htmlspecialchars($e->getMessage()));
}
