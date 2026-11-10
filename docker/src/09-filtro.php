<?php
require_once __DIR__ . '/lib/canciones.php';
$songs = require __DIR__ . '/lib/catalogo-inicial.php';

$category = $_GET['category'] ?? 'todas';
$allowed = ['todas', 'electronica', 'rock', 'bandas-sonoras'];

if (!is_string($category) || !in_array($category, $allowed, true)) {
    http_response_code(400);
    echo "Categoría no válida";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Filtro</title></head>
<body>
    <h1>Catálogo Filtrado (<?= htmlspecialchars($category) ?>)</h1>
    <ul>
        <?php foreach ($songs as $song): 
            if ($category === 'todas' || $song['category'] === $category): ?>
                <li><?= htmlspecialchars($song['title']) ?> (<?= htmlspecialchars($song['category']) ?>)</li>
        <?php 
            endif;
        endforeach; ?>
    </ul>
</body>
</html>