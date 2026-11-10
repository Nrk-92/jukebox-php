<?php
// Formulario compartido por cancion-nueva.php y cancion-editar.php.
// Espera $datos (valores a mostrar), $errores y $accion (URL de envío).
?>
<form method="post" action="<?= e($accion) ?>" class="ficha-form">
    <?= campoCsrf() ?>
    <?php if (isset($idFicha)): ?><input type="hidden" name="id" value="<?= $idFicha ?>"><?php endif; ?>

    <label>Título <input type="text" name="title" maxlength="80" required value="<?= e((string) $datos['title']) ?>"></label>
    <?= errorCampo($errores, 'title') ?>

    <label>Artista <input type="text" name="artist" maxlength="80" required value="<?= e((string) $datos['artist']) ?>"></label>
    <?= errorCampo($errores, 'artist') ?>

    <label>Categoría
        <select name="category" required>
            <option value="">-- Elige --</option>
            <?php foreach (CATEGORIAS as $valor => $texto): ?>
                <option value="<?= e($valor) ?>" <?= $datos['category'] === $valor ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?= errorCampo($errores, 'category') ?>

    <label>Duración (segundos, 1-1800) <input type="number" name="duration" min="1" max="1800" required value="<?= e((string) $datos['duration']) ?>"></label>
    <?= errorCampo($errores, 'duration') ?>

    <label>Audio
        <select name="src" required>
            <option value="">-- Elige --</option>
            <?php foreach (AUDIOS_PERMITIDOS as $audio): ?>
                <option value="<?= e($audio) ?>" <?= $datos['src'] === $audio ? 'selected' : '' ?>><?= e($audio) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?= errorCampo($errores, 'src') ?>

    <div>
        <button type="submit">Guardar</button>
        <a href="canciones.php">Cancelar</a>
    </div>
</form>
