<?php
// Bloque 9 · Verificación de credenciales.
require_once __DIR__ . '/lib/sesion.php';
require_once __DIR__ . '/lib/almacen-json.php';
require_once __DIR__ . '/lib/canciones.php';
require_once __DIR__ . '/lib/vista.php';

exigirMetodo(['GET', 'POST']);

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprobarCsrf();

    $emailRecibido = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!is_string($emailRecibido) || !is_string($password)) {
        $error = 'Credenciales no válidas.';
    } else {
        // Misma normalización que en register.php.
        $email = strtolower(trim($emailRecibido));
        $foundUser = null;
        try {
            foreach (leerJson(RUTA_USUARIOS) as $user) {
                if (($user['email'] ?? null) === $email) {
                    $foundUser = $user;
                    break;
                }
            }
        } catch (Throwable $e) {
            error_log('Login: no se pudo leer usuarios.json'); // Sin contraseñas en el log.
        }

        if ($foundUser !== null && password_verify($password, $foundUser['password_hash'])) {
            session_regenerate_id(true); // Nuevo identificador tras autenticar.
            $_SESSION['usuario_id'] = $foundUser['id'];
            $_SESSION['alias'] = $foundUser['alias'];
            header('Location: home.php', true, 303);
            exit;
        }
        // Mismo mensaje para correo desconocido o contraseña incorrecta.
        $error = 'Credenciales no válidas.';
    }
    http_response_code(401);
}

cabecera('Iniciar sesión');
?>
<h1>Iniciar sesión</h1>
<?php if (isset($_GET['registrado'])): ?><p class="ok">Cuenta creada. Ya puedes entrar.</p><?php endif; ?>
<?php if (isset($_GET['salida'])): ?><p class="ok">Has cerrado la sesión.</p><?php endif; ?>
<?php if ($error !== ''): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<form method="post" action="login.php" class="ficha-form">
    <?= campoCsrf() ?>
    <label>Correo <input type="email" name="email" value="<?= e($email) ?>" required></label>
    <label>Contraseña <input type="password" name="password" required></label>
    <button type="submit">Entrar</button>
</form>
<p><a href="register.php">Registrar una cuenta nueva</a></p>
<?php pie();
