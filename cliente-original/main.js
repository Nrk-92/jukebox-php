// ==========================================
// VARIABLES GLOBALES (Nuestros "almacenes" de datos)
// ==========================================

// Aquí guardaremos todas las canciones que nos lleguen del archivo JSON. Empieza vacía.
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
// IMPORTAR EL ARCHIVO JSON EXTERNO (Traernos la música)
// ==========================================

// Vamos a buscar el archivo 'music.json' a nuestro servidor/carpeta.
fetch('music.json')
    // Cuando el archivo llegue, lo convertimos a un formato que JavaScript entienda (JSON).
    .then(respuesta => respuesta.json())
    // Una vez traducido, lo metemos en 'datos'.
    .then(datos => {
        // Guardamos todos los datos en nuestra variable global 'baseDatos'.
        baseDatos = datos;
        // Como ya tenemos los datos, construimos la lista de canciones mostrando 'todos' los discos.
        cargarLista('todos');
    })
    // Si algo sale mal (ej: el archivo no existe), capturamos el error aquí.
    .catch(error => {
        console.error('Error al cargar el archivo JSON:', error); // Chivamos el error en la consola
        // Y le mostramos un mensaje en rojo al usuario en la pantalla.
        listaCancionesUI.innerHTML = '<li style="color: red;">Error al cargar music.json. Comprueba que el archivo existe y usas un servidor local (Live Server).</li>';
    });


// ==========================================
// INICIALIZACIÓN DE LA LISTA DE REPRODUCCIÓN
// ==========================================

// Esta función crea la lista visual de canciones y la lógica de la playlist actual
function cargarLista(filtro) {
    // Vaciamos la playlist actual por si había algo de antes.
    currentPlaylist = [];
    // Borramos todo el texto/HTML de la lista en pantalla.
    listaCancionesUI.innerHTML = '';

    // Repasamos disco por disco nuestra base de datos.
    baseDatos.forEach(disco => {
        // Si el usuario quiere ver 'todos' o si el nombre del disco coincide con lo que buscamos...
        if (filtro === 'todos' || disco.Disco === filtro) {
            // ...repasamos cada canción de ese disco.
            disco.Canciones.forEach(cancion => {
                // Y la metemos en nuestra lista de reproducción actual con todos sus detalles.
                currentPlaylist.push({
                    id: cancion.id,
                    titulo: cancion['Canción'],
                    archivo: cancion.archivo,
                    artista: disco.Grupo,
                    disco: disco.Disco,
                    imagen: disco.img
                });
            });
        }
    });

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
    artistaActual.textContent = `${cancion.artista} - ${cancion.disco}`;
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
            if (Howler.ctx && Howler.ctx.state === 'suspend'){
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
    // Si no hay ninguna canción cargada, no hacemos nada.
    if (!sound) return;
    
    // Si está sonando...
    if (isPlaying) {
        sound.pause(); // La pausamos
        isPlaying = false; // Actualizamos nuestro chivato
        btnPlayPause.textContent = '▶'; // Ponemos el icono de Play
    } else { 
        // Si estaba pausada...
        sound.play(); // La reproducimos
        isPlaying = true; // Actualizamos chivato
        btnPlayPause.textContent = '⏸'; // Ponemos el icono de Pause
    }
}

// Para ir a la canción anterior
function anteriorCancion() {
    // Restamos 1 al número de canción actual.
    let index = currentSongIndex - 1;
    // Si bajamos de cero (estábamos en la primera), nos vamos a la última de la lista.
    if (index < 0) index = currentPlaylist.length - 1;
    // Reproducimos la canción resultante.
    reproducirCancion(index);
}

// Para ir a la canción siguiente
function siguienteCancion() {
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