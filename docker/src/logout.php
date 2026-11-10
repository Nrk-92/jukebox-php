<?php
// Bloque 9 · Salida: solo POST con token válido. Un enlace GET no cierra la sesión.
require_once __DIR__ . '/lib/sesion.php';

exigirMetodo(['POST']); // GET -> 405
comprobarCsrf();        // Sin token -> 403

$_SESSION = [];

// Borra la cookie con los mismos parámetros con los que se creó.
$params = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => $params['path'],
    'domain' => $params['domain'],
    'secure' => $params['secure'],
    'httponly' => $params['httponly'],
    'samesite' => $params['samesite'],
]);
session_destroy();

header('Location: login.php?salida=1', true, 303);
exit;
