<?php
// Bloque 10 · Delete: GET muestra la confirmación; solo POST borra.
require_once __DIR__ . '/lib/sesion.php';
require_once __DIR__ . '/lib/acceso.php';
require_once __DIR__ . '/lib/almacen-json.php';
require_once __DIR__ . '/lib/canciones.php';
require_once __DIR__ . '/lib/vista.php';

exigirMetodo(['GET', 'POST']); // Otros métodos -> 405.

function terminarConEstado(int $codigo, string $mensaje): never
{
    http_response_code($codigo);
    cabecera('Eliminar canción');
    echo '<h1>Eliminar canción</h1><p class="error">' . e($mensaje) . '</p>'
        . '<p><a href="canciones.php">Volver al catálogo</a></p>';
    pie();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprobarCsrf();
    $id = leerIdPositivo($_POST);
    if ($id === null) {
        terminarConEstado(400, 'Identificador no válido.');
    }
    try {
        actualizarJson(RUTA_CANCIONES, function (array $songs) use ($id): array {
            $position = buscarPosicionPorId($songs, $id);
            if ($position === null) {
                throw new RegistroNoEncontrado("No existe la canción $id");
            }
            unset($songs[$position]);
            // Reindexar: sin huecos el JSON sigue siendo una lista, no un objeto.
            return array_values($songs);
        });
        // Solo se quita la ficha: el MP3 no se borra (no se usa unlink).
        header('Location: canciones.php?ok=eliminada', true, 303);
        exit;
    } catch (RegistroNoEncontrado $e) {
        terminarConEstado(404, 'La canción no existe. El catálogo no ha cambiado.');
    } catch (Throwable $e) {
        error_log('Eliminar canción: ' . $e->getMessage());
        terminarConEstado(500, 'No se ha podido eliminar. El catálogo no ha cambiado.');
    }
}

// GET: confirmación. No escribe nada.
$id = leerIdPositivo($_GET);
if ($id === null) {
    terminarConEstado(400, 'Identificador no válido.');
}
try {
    $songs = leerCanciones();
} catch (Throwable $e) {
    terminarConEstado(500, 'No se ha podido leer el catálogo.');
}
$position = buscarPosicionPorId($songs, $id);
if ($position === null) {
    terminarConEstado(404, 'No existe ninguna canción con ese identificador.');
}
$song = $songs[$position];

cabecera('Eliminar canción');
?>
<h1>Eliminar canción</h1>
<p>¿Seguro que quieres eliminar <strong><?= e($song['title']) ?></strong> de <?= e($song['artist']) ?> (id <?= $id ?>)?</p>
<p>Se elimina solo la ficha del catálogo; el archivo de audio se conserva.</p>
<form method="post" action="cancion-eliminar.php" class="en-linea">
    <?= campoCsrf() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <button type="submit" class="peligro">Sí, eliminar</button>
</form>
<a href="canciones.php">Cancelar</a>
<?php pie();
