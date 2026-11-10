<?php
// Bloque 10 · Create: alta de una ficha en el catálogo compartido.
require_once __DIR__ . '/lib/sesion.php';
require_once __DIR__ . '/lib/acceso.php';
require_once __DIR__ . '/lib/almacen-json.php';
require_once __DIR__ . '/lib/canciones.php';
require_once __DIR__ . '/lib/vista.php';

exigirMetodo(['GET', 'POST']);

$datos = ['title' => '', 'artist' => '', 'category' => '', 'duration' => '', 'src' => ''];
$errores = [];
$errorGeneral = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprobarCsrf();
    // No se acepta ningún id del formulario de alta: lo calcula PHP.
    [$datos, $errores] = validarCancion($_POST);

    if (empty($errores)) {
        try {
            actualizarJson(RUTA_CANCIONES, function (array $songs) use ($datos): array {
                $songs[] = [
                    'id' => siguienteId($songs), // máximo + 1, nunca count() + 1.
                    'title' => $datos['title'],
                    'artist' => $datos['artist'],
                    'category' => $datos['category'],
                    'duration' => $datos['duration'],
                    'src' => $datos['src'],
                ];
                return $songs;
            });
            header('Location: canciones.php?ok=creada', true, 303);
            exit;
        } catch (Throwable $e) {
            error_log('Alta canción: ' . $e->getMessage());
            http_response_code(500);
            $errorGeneral = 'No se ha podido guardar la canción. El catálogo no ha cambiado.';
        }
    } else {
        http_response_code(400);
    }
}

cabecera('Nueva canción');
?>
<h1>Añadir canción</h1>
<?php if ($errorGeneral !== ''): ?><p class="error"><?= e($errorGeneral) ?></p><?php endif; ?>
<?php if (!empty($errores)): ?><p class="error">Revisa los campos marcados. La canción no se ha guardado.</p><?php endif; ?>
<?php $accion = 'cancion-nueva.php'; require __DIR__ . '/lib/formulario-cancion.php'; ?>
<?php pie();
