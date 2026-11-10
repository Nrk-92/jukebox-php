<?php
$songs = [
    [
        'id' => 1,
        'title' => 'Sesión de prueba',
        'artist' => 'Grupo del aula',
        'category' => 'electronica',
        'duration' => 185,
        'src' => 'audio/demo-01.mp3',
    ],
    [
        'id' => 2,
        'title' => 'Noches de Rock',
        'artist' => 'Los Estudiantes',
        'category' => 'rock',
        'duration' => 210,
        'src' => 'audio/demo-02.mp3',
    ],
    [
        'id' => 3,
        'title' => 'Tema principal',
        'artist' => 'Orquesta Monlau',
        'category' => 'bandas-sonoras',
        'duration' => 305,
        'src' => 'audio/demo-03.mp3',
    ],
    [
        'id' => 4,
        'title' => 'Sintetizadores',
        'artist' => 'DJ Clase',
        'category' => 'electronica',
        'duration' => 195,
        'src' => 'audio/demo-04.mp3',
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Catálogo</title></head>
<body>
    <h1>Catálogo Musical</h1>
    <?php if (empty($songs)): ?>
        <p>Todavía no hay canciones</p>
    <?php else: ?>
        <ul>
            <?php foreach ($songs as $song): ?>
                <li>
                    <strong><?= htmlspecialchars($song['title'], ENT_QUOTES, 'UTF-8') ?></strong> 
                    por <?= htmlspecialchars($song['artist'], ENT_QUOTES, 'UTF-8') ?> 
                    (<?= $song['duration'] ?> segundos)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>