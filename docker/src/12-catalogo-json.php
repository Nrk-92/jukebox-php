<?php
// Bloque 8 · Lectura del catálogo desde el JSON privado.
// Bloque 10 · Protegido con la comprobación de acceso: no es una puerta alternativa.
require_once __DIR__ . '/lib/sesion.php';
require_once __DIR__ . '/lib/acceso.php';
require_once __DIR__ . '/lib/almacen-json.php';
require_once __DIR__ . '/lib/canciones.php';

$error = '';
try {
    // Ruta fija dentro del contenedor, nunca enviada por el navegador.
    $songs = leerCanciones();
} catch (JsonException $e) {
    $error = 'El catálogo tiene un formato JSON incorrecto. No se ha modificado nada.';
} catch (Throwable $e) {
    $error = 'No se ha podido leer el catálogo.';
}
if ($error !== '') {
    http_response_code(500);
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Catálogo JSON</title></head>
<body>
    <h1>Catálogo desde JSON (privado)</h1>
    <?php if ($error !== ''): ?>
        <p><?= e($error) ?></p>
    <?php elseif (empty($songs)): ?>
        <p>Todavía no hay canciones</p>
    <?php else: ?>
        <ul>
            <?php foreach ($songs as $song): ?>
                <li>
                    <strong><?= e($song['title']) ?></strong>
                    por <?= e($song['artist']) ?>
                    (<?= duracionLegible($song['duration']) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <p><a href="canciones.php">Ir a la gestión del catálogo</a></p>
</body>
</html>
