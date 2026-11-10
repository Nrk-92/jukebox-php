<?php
// Bloque 9 · Sesión común. Se carga con require_once ANTES de cualquier HTML.
ini_set('session.use_strict_mode', '1');
session_name('JUKEBOXSESSID'); // Nombre propio para la sesión de este proyecto.
session_set_cookie_params([
    'path' => '/',
    'httponly' => true,   // JavaScript no puede leer la cookie.
    'samesite' => 'Lax',
    'secure' => false,    // true cuando exista HTTPS (en local usamos HTTP).
]);
session_start(); // Abre o recupera la sesión.

// Token CSRF asociado a la sesión.
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/** Campo oculto con el token, ya escapado como atributo HTML. */
function campoCsrf(): string
{
    $token = htmlspecialchars($_SESSION['csrf'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<input type="hidden" name="csrf" value="' . $token . '">';
}

/** En cada POST: si el token falta o no coincide, 403 y no se escribe nada. */
function comprobarCsrf(): void
{
    $recibido = $_POST['csrf'] ?? null;
    if (!is_string($recibido) || !hash_equals($_SESSION['csrf'], $recibido)) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
            . '<title>403</title></head><body><h1>403 · Envío rechazado</h1>'
            . '<p>El formulario no es válido o ha caducado. Vuelve a abrirlo.</p>'
            . '</body></html>';
        exit;
    }
}

/** Responde 405 si el método no está permitido. */
function exigirMetodo(array $permitidos): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'], $permitidos, true)) {
        http_response_code(405);
        header('Allow: ' . implode(', ', $permitidos));
        echo 'Método no permitido';
        exit;
    }
}
