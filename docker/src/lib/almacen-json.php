<?php
// Bloques 8 y 9 · Lectura y escritura controlada de los JSON privados.
// Todas las pantallas usan estas funciones: no se repite otro procedimiento
// en cada formulario y nunca se escribe una ruta recibida por GET o POST.

const RUTA_CANCIONES = '/var/www/data/canciones.json';
const RUTA_USUARIOS  = '/var/www/data/usuarios.json';

/**
 * Convierte el texto leído en una lista PHP.
 * Lanza una excepción si el JSON está mal formado o si la raíz no es una lista.
 */
function decodificarLista(string $texto): array
{
    $datos = json_decode($texto, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($datos) || !array_is_list($datos)) {
        throw new RuntimeException('El archivo JSON no contiene una lista.');
    }
    return $datos;
}

/**
 * Lee una colección con bloqueo compartido (LOCK_SH) para no observar
 * una escritura a medias. No escribe nada.
 */
function leerJson(string $ruta): array
{
    $archivo = fopen($ruta, 'r');
    if ($archivo === false) {
        throw new RuntimeException('No se puede abrir el almacén de datos.');
    }
    try {
        if (!flock($archivo, LOCK_SH)) {
            throw new RuntimeException('No se puede bloquear el almacén para leer.');
        }
        $texto = stream_get_contents($archivo);
        if ($texto === false) {
            throw new RuntimeException('No se puede leer el almacén de datos.');
        }
        return decodificarLista($texto);
    } finally {
        flock($archivo, LOCK_UN);
        fclose($archivo);
    }
}

/**
 * Procedimiento común de actualización (leer, modificar y guardar con un
 * único bloqueo exclusivo).
 *
 * $cambio recibe la lista recién leída y devuelve la lista nueva. Si detecta
 * un problema (duplicado, id inexistente...) lanza una excepción y el archivo
 * no se modifica.
 */
function actualizarJson(string $ruta, callable $cambio): void
{
    // 1. Ruta fija y existente, modo r+ (no vacía el archivo al abrirlo).
    $archivo = fopen($ruta, 'r+');
    if ($archivo === false) {
        throw new RuntimeException('No se puede abrir el almacén de datos.');
    }
    try {
        // 2. Bloqueo exclusivo durante todo el ciclo.
        if (!flock($archivo, LOCK_EX)) {
            throw new RuntimeException('No se puede bloquear el almacén.');
        }
        $texto = stream_get_contents($archivo);
        if ($texto === false) {
            throw new RuntimeException('No se puede leer el almacén de datos.');
        }
        $registros = decodificarLista($texto); // JSON inválido: se aborta aquí.

        // 3. Modificar y convertir ANTES de tocar el archivo.
        $nuevos = array_values($cambio($registros));
        $json = json_encode(
            $nuevos,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
        ) . "\n";

        rewind($archivo);
        $escritos = fwrite($archivo, $json);
        if ($escritos !== strlen($json)) {
            throw new RuntimeException('La escritura no se completó.');
        }
        if (!ftruncate($archivo, strlen($json)) || !fflush($archivo)) {
            throw new RuntimeException('No se pudo confirmar la escritura.');
        }
    } finally {
        // 4. Liberar y cerrar también si hubo un error.
        flock($archivo, LOCK_UN);
        fclose($archivo);
    }
}

/** Siguiente id: máximo existente + 1, o 1 si la colección está vacía. */
function siguienteId(array $registros): int
{
    $max = 0;
    foreach ($registros as $registro) {
        if (is_int($registro['id'] ?? null) && $registro['id'] > $max) {
            $max = $registro['id'];
        }
    }
    return $max + 1;
}

/** Devuelve la POSICIÓN del registro cuyo id coincide, o null si no existe. */
function buscarPosicionPorId(array $registros, int $id): ?int
{
    foreach ($registros as $position => $registro) {
        if (($registro['id'] ?? null) === $id) {
            return $position;
        }
    }
    return null;
}

/** Lee el catálogo y comprueba que cada ficha tiene los campos esperados. */
function leerCanciones(): array
{
    $songs = leerJson(RUTA_CANCIONES);
    foreach ($songs as $song) {
        if (!is_array($song)
            || !is_int($song['id'] ?? null)
            || !is_string($song['title'] ?? null)
            || !is_string($song['artist'] ?? null)
            || !is_string($song['category'] ?? null)
            || !is_int($song['duration'] ?? null)
            || !is_string($song['src'] ?? null)) {
            throw new RuntimeException('El catálogo contiene una ficha incompleta.');
        }
    }
    return $songs;
}

/** Excepciones de negocio: se traducen a 404 o a un mensaje del formulario. */
class RegistroNoEncontrado extends RuntimeException {}
class RegistroDuplicado extends RuntimeException {}
