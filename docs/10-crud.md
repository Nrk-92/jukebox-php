# Bloque 10 · CRUD
- Archivos: `canciones.php` (Read), `cancion-nueva.php` (Create), `cancion-editar.php` (Update), `cancion-eliminar.php` (Delete), `lib/formulario-cancion.php`, `lib/canciones.php` (`validarCancion`).
- Nuevo id = máximo + 1. Editar y borrar localizan la ficha por id bajo bloqueo.
- Borrar: `unset` + `array_values` para que el JSON siga siendo una lista. No se borra el MP3.
- id no válido 400, inexistente 404, método no permitido 405, token incorrecto 403.
