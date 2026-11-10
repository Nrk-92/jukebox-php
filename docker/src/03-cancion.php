<?php
$title = '<b>Demo</b>';
$artist = 'Grupo del aula';
$duration = 185;
$volume = 0.5;
$available = true;
const APP_NAME = 'Jukebox de la asociación';
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"></head>
<body>
    <h1><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
    <p>La aplicación es: <?= APP_NAME ?></p>
</body>
</html>