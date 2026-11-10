<?php
// Bloque 10 · Read: consulta del catálogo con enlaces a editar y eliminar.
require_once __DIR__ . '/lib/sesion.php';
require_once __DIR__ . '/lib/acceso.php';
require_once __DIR__ . '/lib/almacen-json.php';
require_once __DIR__ . '/lib/canciones.php';
require_once __DIR__ . '/lib/vista.php';

exigirMetodo(['GET']);

$error = '';
$songs = [];
try {
    $songs = leerCanciones();
} catch (Throwable $e) {
    error_log('Catálogo: ' . $e->getMessage());
    $error = 'No se ha podido leer el catálogo. No se ha modificado nada.';
    http_response_code(500);
}

$avisos = ['creada' => 'Canción añadida.', 'editada' => 'Canción actualizada.', 'eliminada' => 'Canción eliminada.'];
$aviso = $_GET['ok'] ?? '';

cabecera('Catálogo');
?>
<h1>Catálogo musical</h1>
<p><a class="boton" href="cancion-nueva.php">+ Añadir canción</a></p>
<?php if (is_string($aviso) && isset($avisos[$aviso])): ?><p class="ok"><?= e($avisos[$aviso]) ?></p><?php endif; ?>

<?php if ($error !== ''): ?>
    <p class="error"><?= e($error) ?></p>
<?php elseif (empty($songs)): ?>
    <p>Todavía no hay canciones</p>
<?php else: ?>
    <table>
        <thead><tr><th>Id</th><th>Título</th><th>Artista</th><th>Categoría</th><th>Duración</th><th>Audio</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($songs as $song): ?>
            <tr>
                <td><?= $song['id'] ?></td>
                <td><?= e($song['title']) ?></td>
                <td><?= e($song['artist']) ?></td>
                <td><?= e(CATEGORIAS[$song['category']] ?? $song['category']) ?></td>
                <td><?= e(duracionLegible($song['duration'])) ?></td>
                <td><code><?= e($song['src']) ?></code></td>
                <td class="acciones">
                    <a href="cancion-editar.php?id=<?= $song['id'] ?>">Editar</a>
                    <a href="cancion-eliminar.php?id=<?= $song['id'] ?>">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php pie();
