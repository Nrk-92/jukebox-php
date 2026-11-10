<?php
require_once __DIR__ . '/lib/canciones.php';

$title = '';
$artist = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $artist = $_POST['artist'] ?? '';
    $duration = filter_var($_POST['duration'] ?? '', FILTER_VALIDATE_INT);
    $category = $_POST['category'] ?? '';
    $allowed = ['electronica', 'rock', 'bandas-sonoras'];

    if (!is_string($title) || trim($title) === '') {
        $errors[] = "El título es obligatorio.";
    }
    if (!is_string($artist) || trim($artist) === '') {
        $errors[] = "El artista es obligatorio.";
    }
    if ($duration === false || $duration < 1 || $duration > 1800) {
        $errors[] = "La duración debe ser un entero entre 1 y 1800.";
    }
    if (!in_array($category, $allowed, true)) {
        $errors[] = "Categoría no permitida.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"></head>
<body>
    <h1>Formulario de Canción</h1>
    <?php if (!empty($errors)): ?>
        <ul><?php foreach($errors as $err): ?> <li><?= $err ?></li> <?php endforeach; ?></ul>
    <?php endif; ?>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)): ?>
        <h2>Vista previa, canción no guardada:</h2>
        <p><?= htmlspecialchars($title) ?> por <?= htmlspecialchars($artist) ?>. 
           Duración: <?= duracionLegible($duration) ?>.</p>
    <?php endif; ?>

    <form method="post" action="10-formulario.php">
        <label>Título: <input type="text" name="title" value="<?= htmlspecialchars($title) ?>"></label><br><br>
        <label>Artista: <input type="text" name="artist" value="<?= htmlspecialchars($artist) ?>"></label><br><br>
        <label>Categoría: 
            <select name="category">
                <option value="electronica">Electrónica</option>
                <option value="rock">Rock</option>
            </select>
        </label><br><br>
        <label>Duración (segundos): <input type="number" name="duration"></label><br><br>
        <input type="submit" value="Verificar">
    </form>
</body>
</html>