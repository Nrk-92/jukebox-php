# Bloque 9 · Sesión, registro, login y logout
- Archivos: `lib/sesion.php`, `lib/acceso.php`, `register.php`, `login.php`, `home.php`, `logout.php`, `data/usuarios.json`.
- Cookie `JUKEBOXSESSID` (httponly, SameSite=Lax); el servidor guarda `usuario_id`, `alias` y `csrf`.
- Token CSRF en todos los formularios; si falta o no coincide: 403 sin escribir.
- Registro con `password_hash`; login con `password_verify` y `session_regenerate_id(true)`.
- `actualizarJson()`: `fopen r+` → `flock LOCK_EX` → leer/validar → modificar → `rewind`/`fwrite`/`ftruncate`/`fflush` → `LOCK_UN`/`fclose`.
