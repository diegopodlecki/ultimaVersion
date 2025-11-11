<?php
/**
 * logout.php — Cierre de sesión
 *
 * Responsabilidades:
 * - Destruir de forma segura la sesión actual.
 * - Redirigir al `index.php` tras cerrar sesión.
 *
 * Seguridad:
 * - Regenera el ID si se desea mitigar reutilización de sesión previa.
 */
session_start();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

header('Location: index.php');
exit;
