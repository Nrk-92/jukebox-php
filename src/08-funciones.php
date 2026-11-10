<?php
require_once __DIR__ . '/lib/canciones.php';
$songs = require __DIR__ . '/lib/catalogo-inicial.php';

echo duracionLegible(60) . "<br>";
echo duracionLegible(185) . "<br><hr>";

foreach ($songs as $song) {
    echo $song['title'] . " - " . duracionLegible($song['duration']) . "<br>";
}