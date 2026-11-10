<?php
// Bloque 9 · Comprobación de acceso en el servidor.
// Se incluye después de lib/sesion.php y antes del HTML en cada página privada.
if (!isset($_SESSION['usuario_id']) || !is_int($_SESSION['usuario_id'])) {
    header('Location: login.php', true, 303);
    exit;
}
