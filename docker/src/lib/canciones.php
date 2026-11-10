<?php
// Bloques 5, 6 y 10 · Reglas comunes de las canciones.

/** Bloque 5: texto con minutos y segundos (no imprime HTML). */
function duracionLegible(int $total): string
{
    $minutes = intdiv($total, 60);
    $seconds = $total % 60;
    return "$minutes minutos y $seconds segundos";
}

/** Escapa un texto para insertarlo en HTML (contenido o atributo). */
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Categorías admitidas. Se añade "clasica" porque el jukebox de cliente es de música clásica. */
const CATEGORIAS = [
    'electronica'    => 'Electrónica',
    'rock'           => 'Rock',
    'bandas-sonoras' => 'Bandas sonoras',
    'clasica'        => 'Clásica',
];

/** Audios admitidos: los únicos valores posibles de src (nunca una ruta libre). */
const AUDIOS_PERMITIDOS = [
    'audio/mozart_serenata_mov1.mp3',
    'audio/mozart_lacrimosa.mp3',
    'audio/mozart_marcha_turca.mp3',
    'audio/mozart_figaro.mp3',
    'audio/mozart_reina_noche.mp3',
    'audio/beethoven_para_elisa.mp3',
    'audio/beethoven_claro_luna_mov1.mp3',
    'audio/beethoven_patetica_mov2.mp3',
    'audio/beethoven_minueto.mp3',
    'audio/beethoven_marcha_atenas.mp3',
    'audio/bach_preludio_do.mp3',
    'audio/bach_aire.mp3',
    'audio/bach_cello_preludio.mp3',
    'audio/bach_tocata.mp3',
    'audio/bach_alegria.mp3',
];

/**
 * Bloque 10 · Misma validación al crear y al editar.
 * Devuelve [$datos, $errores]. $datos contiene los valores que se pueden
 * volver a mostrar en el formulario; solo se guardan si $errores está vacío.
 */
function validarCancion(array $entrada): array
{
    $errores = [];
    $datos = ['title' => '', 'artist' => '', 'category' => '', 'duration' => '', 'src' => ''];

    // Título y artista: texto, sin espacios sobrantes, de 1 a 80 caracteres.
    foreach (['title' => 'El título', 'artist' => 'El artista'] as $campo => $nombre) {
        $valor = $entrada[$campo] ?? '';
        if (!is_string($valor)) {
            $errores[$campo] = "$nombre debe ser un texto.";
            continue;
        }
        $valor = trim($valor);
        $datos[$campo] = $valor;
        if ($valor === '') {
            $errores[$campo] = "$nombre es obligatorio.";
        } elseif (mb_strlen($valor) > 80) {
            $errores[$campo] = "$nombre admite como máximo 80 caracteres.";
        }
    }

    // Categoría admitida.
    $category = $entrada['category'] ?? '';
    if (!is_string($category) || !array_key_exists($category, CATEGORIAS)) {
        $errores['category'] = 'Elige una categoría admitida.';
    } else {
        $datos['category'] = $category;
    }

    // Duración: primero texto, después entero, después intervalo.
    $duration = $entrada['duration'] ?? '';
    if (!is_string($duration)) {
        $errores['duration'] = 'La duración debe ser un texto numérico.';
    } else {
        $datos['duration'] = trim($duration);
        $numero = filter_var($datos['duration'], FILTER_VALIDATE_INT);
        if ($numero === false) {
            $errores['duration'] = 'La duración debe ser un número entero de segundos.';
        } elseif ($numero < 1 || $numero > 1800) {
            $errores['duration'] = 'La duración debe estar entre 1 y 1800 segundos.';
        } else {
            $datos['duration'] = $numero;
        }
    }

    // Audio elegido de la lista.
    $src = $entrada['src'] ?? '';
    if (!is_string($src) || !in_array($src, AUDIOS_PERMITIDOS, true)) {
        $errores['src'] = 'Elige uno de los audios admitidos.';
    } else {
        $datos['src'] = $src;
    }

    return [$datos, $errores];
}

/** Lee un id entero positivo de un array de entrada ($_GET o $_POST). */
function leerIdPositivo(array $entrada): ?int
{
    $id = $entrada['id'] ?? null;
    if (!is_string($id)) {
        return null;
    }
    $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}
