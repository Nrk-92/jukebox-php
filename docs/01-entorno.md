# Bloque 1 · Entorno
- Archivos: `compose.yaml`, `src/01-bienvenida.php`.
- El navegador pide `localhost:8090`; Docker lo dirige al puerto 80 del contenedor, donde Apache ejecuta PHP.
- Pruebas: 200 con el saludo; 404 en `/no-existe.php` (el servidor responde); conexión rechazada con el servicio detenido; `php -l` detecta el `;` que falta.
