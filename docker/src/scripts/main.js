// ==========================================
// JUKEBOX DE CLIENTE ADAPTADO AL SERVIDOR PHP (Bloque 11)
// Cambios respecto a la versión de cliente (copia en cliente-original/):
//  - Las canciones ya no salen de music.json: las pide a ./api/canciones.php.
//  - La API devuelve una lista plana con id, title, artist, category, duration y src.
//    Aquí se adapta de forma explícita a los nombres que usa el reproductor
//    (titulo, artista, archivo, imagen).
//  - El desplegable se rellena con los artistas que llegan del servidor.
//  - Lista vacía o error: se explica en pantalla y se desactiva la reproducción.
// ==========================================

// Carátulas por artista. No es otra colección de canciones: solo imágenes de la interfaz.
const CARATULAS = {
    'Wolfgang Amadeus Mozart': 'assets/img/mozzart.jpg',
    'Ludwig van Beethoven': 'assets/img/beethoven.jpg',
    'Johann Sebastian Bach': 'assets/img/bach.jpg'
};
// Imagen transparente para los artistas sin carátula.
const SIN_CARATULA = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

// ==========================================
// VARIABLES GLOBALES (Nuestros "almacenes" de datos)
// ==========================================

// Aquí guardaremos todas las canciones que nos lleguen del servidor (api/canciones.php). Empieza vacía.
let baseDatos = []; 
// Esta será la lista de reproducción que está sonando (puede ser un disco o todas las canciones).
let currentPlaylist = []; 
// Un chivato numérico para saber qué canción de la lista está sonando (el 0 es la primera).
let currentSongIndex = 0; 
// Aquí meteremos la canción "física" que está reproduciendo Howler.js. De momento, nada (null).
let sound = null; 
// Un simple interruptor para saber si la música está sonando (true) o pausada (false).
let isPlaying = false; 

// ==========================================
// ELEMENTOS DEL DOM (Enganchamos el HTML con JavaScript)
// Básicamente, aquí pillamos las etiquetas del HTML para poder cambiarlas luego.
// ==========================================
const listaCancionesUI = document.getElementById('lista-canciones'); // El <ul> donde irán las canciones
const selectorDisco = document.getElementById('selector-disco'); // El desplegable para elegir disco
const caratulaActual = document.getElementById('caratula-actual'); // La imagen de la portada
const tituloActual = document.getElementById('titulo-actual'); // El texto del título de la canción
const artistaActual = document.getElementById('artista-actual'); // El texto del artista
const btnPlayPause = document.getElementById('btn-play-pause'); // El botón de darle al Play/Pause
const btnPrev = document.getElementById('btn-prev'); // Botón de canción anterior
const btnNext = document.getElementById('btn-next'); // Botón de canción siguiente
const barraProgreso = document.getElementById('barra-progreso'); // La barra que avanza con la canción
const tiempoActualUI = document.getElementById('tiempo-actual'); // El texto que dice "01:23"
const barraVolumen = document.getElementById('barra-volumen'); // El deslizador del volumen

// ==========================================
// VARIABLES PARA LA "MAGIA" DEL AUDIO Y EL VISUALIZADOR
// ==========================================
// Un seguro para no encender el motor de audio dos veces.
let audioCtxIniciado = false; 
// Estas variables serán nuestros "cables" y filtros para manipular el sonido.
let analyser, filterLow, filterMid, filterHigh; 
// Pillamos el lienzo (canvas) del HTML donde vamos a pintar las barritas que saltan.
const canvas = document.getElementById('espectro-visual'); 
// Le decimos al canvas que vamos a dibujar en 2 dimensiones (2D).
const canvasCtx = canvas.getContext('2d'); 
// Pillamos las ruedecitas/barras del ecualizador del HTML.
const eqGraves = document.getElementById('eq-graves');
const eqMedios = document.getElementById('eq-medios');
const eqAgudos = document.getElementById('eq-agudos');
const resetEqBtn = document.getElementById('reset-eq'); // El botón para dejar el ecualizador a cero


// ==========================================
// PEDIR LAS CANCIONES AL SERVIDOR (Bloque 11)
// ==========================================

// Pinta un mensaje de estado dentro de la lista (con textContent, nunca innerHTML).
function mostrarEstado(texto, esError) {
    listaCancionesUI.textContent = '';
    const li = document.createElement('li');
    li.className = esError ? 'estado error' : 'estado';
    li.textContent = texto;
    listaCancionesUI.appendChild(li);
}

// Activa o desactiva los botones de reproducción.
function activarControles(activos) {
    [btnPlayPause, btnPrev, btnNext, barraProgreso].forEach(el => el.disabled = !activos);
}

// Comprueba que cada canción trae los campos que necesitamos.
function esCancionValida(c) {
    return c && typeof c.id === 'number' && typeof c.title === 'string'
        && typeof c.artist === 'string' && typeof c.src === 'string';
}

async function cargarCanciones() {
    activarControles(false);
    try {
        // La petición va a la misma web y puerto que home.php: se envía la cookie de sesión.
        const response = await fetch('./api/canciones.php', { headers: { 'Accept': 'application/json' } });

        if (response.status === 401) {
            mostrarEstado('Tu sesión ha caducado. Vuelve a iniciar sesión.', true);
            return;
        }
        if (!response.ok) {
            mostrarEstado('El servidor no ha podido devolver el catálogo (' + response.status + ').', true);
            return;
        }

        const songs = await response.json();
        if (!Array.isArray(songs) || !songs.every(esCancionValida)) {
            mostrarEstado('La respuesta del servidor no es una lista de canciones válida.', true);
            return;
        }

        // Adaptación explícita: nombres de la API -> nombres del reproductor.
        baseDatos = songs.map(c => ({
            id: c.id,
            titulo: c.title,
            artista: c.artist,
            archivo: c.src,
            disco: c.category,
            imagen: CARATULAS[c.artist] || SIN_CARATULA
        }));

        rellenarSelector();
        cargarLista('todos');
    } catch (error) {
        console.error('Error al pedir las canciones:', error);
        mostrarEstado('No se ha podido conectar con el servidor.', true);
    }
}

// Crea una opción del desplegable por cada artista que llega del servidor.
function rellenarSelector() {
    selectorDisco.length = 1; // Deja solo "Mostrar todos".
    const artistas = [...new Set(baseDatos.map(c => c.artista))];
    artistas.forEach(artista => {
        const option = document.createElement('option');
        option.value = artista;
        option.textContent = artista;
        selectorDisco.appendChild(option);
    });
}

// ==========================================
// INICIALIZACIÓN DE LA LISTA DE REPRODUCCIÓN
// ==========================================

// Esta función crea la lista visual de canciones y la lógica de la playlist actual
function cargarLista(filtro) {
    // Vaciamos la playlist actual por si había algo de antes.
    currentPlaylist = [];
    // Borramos todo el texto/HTML de la lista en pantalla.
    listaCancionesUI.textContent = '';

    // Repasamos la base de datos y nos quedamos con las del artista elegido (o todas).
    currentPlaylist = baseDatos.filter(cancion => filtro === 'todos' || cancion.artista === filtro);

    // Lista vacía: lo explicamos y no dejamos reproducir (evita errores de índice).
    if (currentPlaylist.length === 0) {
        mostrarEstado('Todavía no hay canciones en el catálogo. Añade una desde "Gestionar catálogo".', false);
        activarControles(false);
        return;
    }
    activarControles(true);
    // Índice inicial definido antes de consultar currentPlaylist[i].
    currentSongIndex = 0;

    // Ahora que tenemos la lista lista, la vamos a pintar en el HTML.
    currentPlaylist.forEach((cancion, index) => {
        // Creamos una etiqueta <li> (elemento de lista).
        const li = document.createElement('li');
        // Le ponemos el texto: "1. Nombre de la canción". (index + 1 es para que no empiece en 0)
        li.textContent = `${index + 1}. ${cancion.titulo}`;
        // Le añadimos un evento: si alguien hace clic en este <li>, se reproduce esa canción.
        li.addEventListener('click', () => reproducirCancion(index));
        // Finalmente, enganchamos este <li> a la lista del HTML.
        listaCancionesUI.appendChild(li);
    });
}

// Si el usuario cambia el desplegable de discos...
selectorDisco.addEventListener('change', (e) => {
    // Volvemos a cargar la lista, pasándole el nombre del disco que acaba de elegir.
    cargarLista(e.target.value);
});


// ==========================================
// CONFIGURACIÓN DEL AUDIO AVANZADO (Filtros y Ecualizador)
// ==========================================

function inicializarContextoAudio() {
    // Si ya lo habíamos arrancado o Howler no está listo, nos salimos para no hacer cosas raras.
    if (audioCtxIniciado || !Howler.ctx) return;

    // Pillamos el "cerebro" del audio del navegador que usa Howler.
    const ctx = Howler.ctx;

    // --- Creamos el filtro de Graves (Lowshelf) ---
    filterLow = ctx.createBiquadFilter();
    filterLow.type = 'lowshelf'; // Tipo de filtro para frecuencias bajas
    filterLow.frequency.value = 250; // Actúa de 250Hz para abajo

    // --- Creamos el filtro de Medios (Peaking) ---
    filterMid = ctx.createBiquadFilter();
    filterMid.type = 'peaking'; // Tipo campana, sube o baja el centro
    filterMid.frequency.value = 1000; // Actúa sobre los 1000Hz
    filterMid.Q.value = 1; // La anchura de la campana (qué tantas frecuencias vecinas afecta)

    // --- Creamos el filtro de Agudos (Highshelf) ---
    filterHigh = ctx.createBiquadFilter();
    filterHigh.type = 'highshelf'; // Tipo de filtro para frecuencias altas
    filterHigh.frequency.value = 4000; // Actúa de 4000Hz para arriba

    // --- Creamos el analizador (para ver las barritas saltar) ---
    analyser = ctx.createAnalyser();
    analyser.fftSize = 256; // El nivel de detalle del análisis (a mayor número, más barritas)

    // AHORA CONECTAMOS LOS CABLES (Como si fuera un equipo de música físico):
    // 1. Desconectamos la salida general de Howler de los altavoces temporalmente.
    Howler.masterGain.disconnect();
    // 2. Conectamos la salida general al filtro de graves.
    Howler.masterGain.connect(filterLow);
    // 3. De graves pasamos a medios.
    filterLow.connect(filterMid);
    // 4. De medios pasamos a agudos.
    filterMid.connect(filterHigh);
    // 5. De los agudos lo pasamos al analizador (para sacar los datos visuales).
    filterHigh.connect(analyser);
    // 6. Y del analizador, por fin, a los altavoces del ordenador (ctx.destination).
    analyser.connect(ctx.destination);

    // Marcamos que ya hemos montado todo el chiringuito para no volver a hacerlo.
    audioCtxIniciado = true;
    // Empezamos a dibujar las barritas saltarinas.
    dibujarEspectro();
}


// ==========================================
// CONTROL DE REPRODUCCIÓN (La parte de darle al Play)
// ==========================================

function reproducirCancion(index) {
    // Si la lista está vacía, no hacemos nada.
    if (currentPlaylist.length === 0) return;

    // Guardamos el número de la canción que va a sonar.
    currentSongIndex = index;
    // Buscamos los datos de esa canción en concreto.
    const cancion = currentPlaylist[index];

    // Si ya había algo sonando de antes, lo apagamos completamente (unload).
    if (sound) {
        sound.unload();
    }

    // Actualizamos los textos e imágenes de la pantalla.
    tituloActual.textContent = cancion.titulo;
    // (Nota: Corregida la sintaxis del string aquí para que funcione sin dar error)
    artistaActual.textContent = cancion.artista;
    caratulaActual.src = cancion.imagen;
    // Cambiamos el icono del botón de Play a Pause.
    btnPlayPause.textContent = '⏸';

    // Preparamos la nueva canción usando la librería Howler.
    sound = new Howl({
        src: [cancion.archivo], // La ruta del mp3 o wav
        volume: barraVolumen.value / 100, // Ajustamos el volumen de 0 a 1 (por eso dividimos entre 100)
        
        // Esto se ejecuta JUSTO cuando la canción empieza a sonar:
        onplay: function () {
            // A veces los navegadores bloquean el audio. Si está bloqueado (suspend), lo despertamos (resume).
            if (Howler.ctx && Howler.ctx.state === 'suspended'){
                Howler.ctx.resume();
            }
            // Marcamos el interruptor general como "sonando".
            isPlaying = true;
            // Arrancamos el bucle que hace que la barra de tiempo avance.
            requestAnimationFrame(actualizarProgreso);
        },
        
        // Esto se ejecuta cuando la canción termina:
        onend: function () {
            // Llamamos a la función que pone la siguiente.
            siguienteCancion();
        }
    });

    // Ahora que Howler ya ha creado la canción, preparamos nuestros filtros y ecualizadores.
    inicializarContextoAudio();

    // ¡Que suene la música!
    sound.play();
}

// Función del botón Play/Pause
function togglePlay() {
    // Si aún no se ha elegido canción, empezamos por la primera de la lista (acción del usuario).
    if (!sound) {
        reproducirCancion(currentSongIndex);
        return;
    }
    
    // Si está sonando...
    if (isPlaying) {
        sound.pause(); // La pausamos
        isPlaying = false; // Actualizamos nuestro chivato
        btnPlayPause.textContent = '▶';

// Arrancamos: pedimos las canciones al servidor.
cargarCanciones(); // Ponemos el icono de Play
    } else { 
        // Si estaba pausada...
        sound.play(); // La reproducimos
        isPlaying = true; // Actualizamos chivato
        btnPlayPause.textContent = '⏸'; // Ponemos el icono de Pause
    }
}

// Para ir a la canción anterior
function anteriorCancion() {
    if (currentPlaylist.length === 0) return;
    // Restamos 1 al número de canción actual.
    let index = currentSongIndex - 1;
    // Si bajamos de cero (estábamos en la primera), nos vamos a la última de la lista.
    if (index < 0) index = currentPlaylist.length - 1;
    // Reproducimos la canción resultante.
    reproducirCancion(index);
}

// Para ir a la canción siguiente
function siguienteCancion() {
    if (currentPlaylist.length === 0) return;
    // Sumamos 1 al número actual.
    let index = currentSongIndex + 1;
    // Si nos pasamos de la cantidad de canciones que hay, volvemos a la primera (0).
    if (index >= currentPlaylist.length) index = 0;
    // Reproducimos la canción resultante.
    reproducirCancion(index);
}

// Le decimos a los botones del HTML que si les hacen clic, ejecuten estas funciones.
btnPlayPause.addEventListener('click', togglePlay);
btnPrev.addEventListener('click', anteriorCancion);
btnNext.addEventListener('click', siguienteCancion);


// ==========================================
// BARRA DE PROGRESO Y VOLUMEN
// ==========================================

// Esta funcioncita convierte los segundos en formato bonito (ej: 90 segundos = "1:30")
function formatoTiempo(secs) {
    const minutos = Math.floor(secs / 60) || 0; // Calcula los minutos
    const segundos = Math.floor(secs - minutos * 60) || 0; // Calcula los segundos restantes
    // Devuelve el texto. Si los segundos son menos de 10, le pone un '0' delante (09 en vez de 9).
    return minutos + ':' + (segundos < 10 ? '0' : '') + segundos;
}

// Esta función actualiza la barra que avanza mientras suena la canción
function actualizarProgreso() {
    // Solo hacemos esto si hay una canción cargada y está sonando
    if (sound && isPlaying) {
        const seek = sound.seek() || 0; // ¿Por qué segundo vamos?
        const duracion = sound.duration() || 0; // ¿Cuánto dura en total?

        // Calculamos el porcentaje para mover la barrita de progreso (0 a 100).
        barraProgreso.value = (seek / duracion) * 100 || 0;
        // Ponemos el número en bonito en la pantalla.
        tiempoActualUI.textContent = formatoTiempo(seek);

        // Le pedimos al navegador que vuelva a ejecutar esta función en el próximo fotograma.
        // Esto crea un bucle infinito suave que mantiene la barra actualizada en tiempo real.
        requestAnimationFrame(actualizarProgreso);
    }
}

// Si el usuario hace clic o arrastra la barra de progreso (para adelantar la canción)...
barraProgreso.addEventListener('input', () => {
    if (sound) {
        const duracion = sound.duration();
        // Calculamos a qué segundo de la canción corresponde donde ha hecho clic.
        const posicion = (barraProgreso.value / 100) * duracion;
        // Movemos la canción a ese segundo.
        sound.seek(posicion);
    }
});

// Si el usuario mueve la ruedecita/barra de volumen...
barraVolumen.addEventListener('input', (e) => {
    // Le pasamos a Howler el nuevo volumen. (De 0 a 1, por eso dividimos entre 100).
    Howler.volume(e.target.value / 100);
});


// ==========================================
// EVENTOS DEL ECUALIZADOR
// ==========================================

// Si mueven las barras del ecualizador, cambiamos la ganancia (fuerza) del filtro correspondiente
eqGraves.addEventListener('input', (e) => { if (audioCtxIniciado) filterLow.gain.value = e.target.value; });
eqMedios.addEventListener('input', (e) => { if (audioCtxIniciado) filterMid.gain.value = e.target.value; });
eqAgudos.addEventListener('input', (e) => { if (audioCtxIniciado) filterHigh.gain.value = e.target.value; });

// Botón de reset: volver todo el sonido a la normalidad
resetEqBtn.addEventListener('click', () => {
    // Ponemos las barras visuales del HTML a cero
    eqGraves.value = 0;
    eqMedios.value = 0;
    eqAgudos.value = 0;
    // Y si el sistema de audio está encendido, ponemos los filtros internos a cero también.
    if (audioCtxIniciado) {
        filterLow.gain.value = 0;
        filterMid.gain.value = 0;
        filterHigh.gain.value = 0;
    }
});

// ==========================================
// DIBUJAR ESPECTRO VISUAL EN CANVAS (Las barritas que saltan)
// ==========================================

function dibujarEspectro() {
    // Igual que con el progreso, le decimos al navegador que ejecute esto 60 veces por segundo.
    requestAnimationFrame(dibujarEspectro);

    // Si no está sonando nada o el motor de audio no arrancó, no pintamos nada.
    if (!isPlaying || !audioCtxIniciado) return;

    // Averiguamos cuántos datos de frecuencia nos va a dar el analizador.
    const bufferLength = analyser.frequencyBinCount;
    // Preparamos un array (una lista) del tamaño exacto para guardar esos datos.
    const dataArray = new Uint8Array(bufferLength);
    // Le pedimos al analizador que nos rellene esa lista con los datos del sonido en este milisegundo.
    analyser.getByteFrequencyData(dataArray);

    // Borramos lo que sea que hubiera pintado en el canvas antes (para que no quede manchado).
    canvasCtx.clearRect(0, 0, canvas.width, canvas.height);

    // Calculamos cómo de ancha tiene que ser cada barrita para que quepan todas.
    const barWidth = (canvas.width / bufferLength) * 2.5;
    let barHeight; // La altura la calcularemos ahora para cada barrita.
    let x = 0; // Empezamos a pintar desde la izquierda del todo (0).

    // Hacemos un bucle recorriendo todos los datos de frecuencia que sacamos antes.
    for (let i = 0; i < bufferLength; i++) {
        // La altura de la barrita será proporcional a la fuerza de esa frecuencia. (Lo partimos por 2 para que no se salga de la pantalla)
        barHeight = dataArray[i] / 2;

        // Le damos un color rojo dinámico. Cuanto más alta la barra, un rojo un pelín diferente.
        canvasCtx.fillStyle = `rgb(${barHeight + 100}, 28, 28)`;
        
        // Pintamos la barrita. Los parámetros son: posición horizontal, posición vertical (empezando desde abajo), ancho, y alto.
        canvasCtx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);

        // Movemos el "pincel" hacia la derecha para pintar la siguiente barrita al lado, dejando 1px de separación.
        x += barWidth + 1;
    }
}

// Nada más arrancar la página, nos aseguramos de que el texto del botón principal sea 'Play'.
btnPlayPause.textContent = '▶';

// Arrancamos: pedimos las canciones al servidor.
cargarCanciones();