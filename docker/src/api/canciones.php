<?php
// Bloque 11 · Endpoint del jukebox: devuelve SOLO la lista de canciones.
// No redirige al login: un fetch que espera JSON no debe recibir HTML.
require_once __DIR__ . '/../lib/sesion.php';
require_once __DIR__ . '/../lib/almacen-json.php';

header('Content-Type: application/json; charset=utf-8');

function responderJson(int $codigo, mixed $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    responderJson(405, ['error' => 'Método no permitido']);
}

if (!isset($_SESSION['usuario_id'])) {
    responderJson(401, ['error' => 'Sesión no iniciada']);
}

try {
    $songs = leerCanciones(); // Procedimiento común con LOCK_SH.
} catch (Throwable $e) {
    error_log('API canciones: ' . $e->getMessage()); // Sin rutas en la respuesta.
    responderJson(500, ['error' => 'No se ha podido leer el catálogo']);
}

// Lista JSON sin envolver en una clave "songs": el cliente usa songs[i].src.
responderJson(200, $songs);
