<?php
// Cabecera y pie compartidos por las pantallas del catálogo.

function cabecera(string $titulo): void
{
    $alias = isset($_SESSION['alias']) ? e($_SESSION['alias']) : '';
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . e($titulo) . ' · Jukebox</title>'
        . '<link rel="stylesheet" href="assets/app.css"></head><body>'
        . '<header class="barra"><strong>Jukebox de la asociación</strong>';
    if ($alias !== '') {
        echo '<nav><a href="home.php">Home</a> <a href="canciones.php">Catálogo</a> '
            . '<span>' . $alias . '</span>'
            . '<form method="post" action="logout.php" class="en-linea">' . campoCsrf()
            . '<button type="submit">Cerrar sesión</button></form></nav>';
    }
    echo '</header><main>';
}

function pie(): void
{
    echo '</main></body></html>';
}

/** Muestra el error de un campo si existe. */
function errorCampo(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? '<span class="error">' . e($errores[$campo]) . '</span>' : '';
}
