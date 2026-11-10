# Bloque 8 · Catálogo en JSON
- Archivos: `docker/data/canciones.json`, `docker/src/lib/almacen-json.php`, `docker/src/12-catalogo-json.php`.
- JSON (texto) → `json_decode(..., true)` → array PHP → HTML escapado.
- JSON mal formado: `JsonException`, mensaje controlado, el archivo no se toca.
- `/data/canciones.json` no existe en el DocumentRoot: 404.
