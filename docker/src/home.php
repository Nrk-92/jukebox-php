<?php
// Bloque 9 · Home privada. Bloque 11 · Integración del jukebox de cliente.
require_once __DIR__ . '/lib/sesion.php';
require_once __DIR__ . '/lib/acceso.php'; // Sin sesión -> login.php
require_once __DIR__ . '/lib/canciones.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Jukebox · <?= e($_SESSION['alias']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/home.css">
    <!-- Howler se carga antes del código que lo utiliza. -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.3/howler.min.js" defer></script>
    <script src="scripts/main.js" defer></script>
</head>

<body>

    <h1>Hola, <?= e($_SESSION['alias']) ?> · Reproductor Jukebox</h1>

    <nav class="barra-usuario">
        <a href="canciones.php">Gestionar catálogo</a>
        <form method="post" action="logout.php">
            <?= campoCsrf() ?>
            <button type="submit">Cerrar sesión</button>
        </form>
    </nav>

    <div class="bloque">
        <h2>Lista de Reproducción</h2>
        <label for="selector-disco">Selecciona un artista:</label>
        <select id="selector-disco">
            <option value="todos">Mostrar todos</option>
            <!-- Las opciones se crean desde los datos que devuelve la API. -->
        </select>

        <ul id="lista-canciones">
            <li>Cargando...</li>
        </ul>
    </div>

    <div class="bloque">
        <h2>Sonando Ahora</h2>
        <img id="caratula-actual" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
            style="background-color: #111219;" alt="Carátula" width="100" height="100">
        <h3 id="titulo-actual">Selecciona una pista</h3>
        <p id="artista-actual">Catálogo de la asociación</p>
    </div>

    <div class="bloque">
        <h2>Controles</h2>
        <button id="btn-prev">Anterior</button>
        <button id="btn-play-pause">Play / Pausa</button>
        <button id="btn-next">Siguiente</button>

        <br><br>
        <label>Posición: </label>
        <input type="range" id="barra-progreso" min="0" max="100" value="0">
        <span id="tiempo-actual">0:00</span>

        <br><br>
        <label>Volumen: </label>
        <input type="range" id="barra-volumen" min="0" max="100" value="80">
    </div>

    <div class="bloque">
        <h2>Ecualizador</h2>
        <canvas id="espectro-visual" width="300" height="100"></canvas>

        <label>Graves: <input type="range" id="eq-graves" min="-10" max="10" value="0"></label>
        <label>Medios: <input type="range" id="eq-medios" min="-10" max="10" value="0"></label>
        <label>Agudos: <input type="range" id="eq-agudos" min="-10" max="10" value="0"></label>

        <br><br>
        <button id="reset-eq">Restablecer ecualizador</button>
    </div>

</body>

</html>
