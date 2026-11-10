# Bloque 7 · Segunda etapa de Docker
- Archivos: `docker/compose.yaml`, `docker/Dockerfile`, `docker/apache/jukebox.conf`, `docker/.env.example`, `.dockerignore`, `docker/src/11-conexiones.php`.
- Servicios: `web` (8091), `postgres:17-bookworm` y `mariadb:11.4` con healthcheck, volumen propio y sin puertos publicados.
- Bind mounts: `./src → /var/www/html` (público) y `./data → /var/www/data` (privado). Volúmenes con nombre: datos de los motores.
- `11-conexiones.php` devuelve JSON con dos estados `ok` (200) o 503 si falla alguno, sin claves.
