# Bloque 11 · Jukebox conectado
- Archivos: `api/canciones.php`, `home.php`, `scripts/main.js`, `assets/style.css`, `assets/home.css`, `assets/img/`, `audio/`.
- La API devuelve 200 con la lista JSON (sin clave `songs`) o 401 con un error JSON. No redirige.
- `main.js` pide `./api/canciones.php`, comprueba `response.ok`, valida la lista y adapta `title/artist/src` a `titulo/artista/archivo`.
- Lista vacía: mensaje y controles desactivados.
