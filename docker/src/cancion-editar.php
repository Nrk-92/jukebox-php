<?php
// Bloque 10 · Update: GET carga la ficha por id; POST la guarda.
require_once __DIR__ . '/lib/sesion.php';
require_once __DIR__ . '/lib/acceso.php';
require_once __DIR__ . '/lib/almacen-json.php';
require_once __DIR__ . '/lib/canciones.php';
require_once __DIR__ . '/lib/vista.php';

exigirMetodo(['GET', 'POST']);

function terminarConEstado(int $codigo, string $mensaje): never
{
    http_response_code($codigo);
    cabecera('Editar canción');
    echo '<h1>Editar canción</h1><p class="error">' . e($mensaje) . '</p>'
        . '<p><a href="canciones.php">Volver al catálogo</a></p>';
    pie();
    exit;
}

$errores = [];
$errorGeneral = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprobarCsrf();
    $id = leerIdPositivo($_POST);
    if ($id === null) {
        terminarConEstado(400, 'Identificador no válido.');
    }
    [$datos, $errores] = validarCancion($_POST);

    if (empty($errores)) {
        try {
            // Se vuelve a localizar la ficha bajo bloqueo: pudo borrarse mientras tanto.
            actualizarJson(RUTA_CANCIONES, function (array $songs) use ($id, $datos): array {
                foreach ($songs as $position => $song) {
                    if ($song['id'] === $id) {
                        // $position localiza el elemento; $id identifica la canción.
                        $songs[$position] = [
                            'id' => $id, // El id no cambia.
                            'title' => $datos['title'],
                            'artist' => $datos['artist'],
                            'category' => $datos['category'],
                            'duration' => $datos['duration'],
                            'src' => $datos['src'],
                        ];
                        return $songs;
                    }
                }
                throw new RegistroNoEncontrado("No existe la canción $id");
            });
            header('Location: canciones.php?ok=editada', true, 303);
            exit;
        } catch (RegistroNoEncontrado $e) {
            terminarConEstado(404, 'La canción ya no existe. No se ha modificado nada.');
        } catch (Throwable $e) {
            error_log('Editar canción: ' . $e->getMessage());
            http_response_code(500);
            $errorGeneral = 'No se ha podido guardar. El catálogo no ha cambiado.';
        }
    } else {
        http_response_code(400);
    }
} else {
    // GET: id para CARGAR el formulario, no para guardar.
    $id = leerIdPositivo($_GET);
    if ($id === null) {
        terminarConEstado(400, 'Identificador no válido.');
    }
    try {
        $songs = leerCanciones();
    } catch (Throwable $e) {
        terminarConEstado(500, 'No se ha podido leer el catálogo.');
    }
    $position = buscarPosicionPorId($songs, $id); // Busca por id, no por posición.
    if ($position === null) {
        terminarConEstado(404, 'No existe ninguna canción con ese identificador.');
    }
    $datos = $songs[$position];
}

cabecera('Editar canción');
?>
<h1>Editar canción #<?= $id ?></h1>
<?php if ($errorGeneral !== ''): ?><p class="error"><?= e($errorGeneral) ?></p><?php endif; ?>
<?php if (!empty($errores)): ?><p class="error">Revisa los campos marcados. No se ha guardado nada.</p><?php endif; ?>
<?php $accion = 'cancion-editar.php'; $idFicha = $id; require __DIR__ . '/lib/formulario-cancion.php'; ?>
<?php pie();
