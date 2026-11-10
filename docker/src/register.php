<?php
// Bloque 9 · Alta de una cuenta de prueba.
require_once __DIR__ . '/lib/sesion.php';
require_once __DIR__ . '/lib/almacen-json.php';
require_once __DIR__ . '/lib/canciones.php';
require_once __DIR__ . '/lib/vista.php';

exigirMetodo(['GET', 'POST']);

$errors = [];
$alias = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprobarCsrf(); // 403 si falta o no coincide.

    $aliasRecibido = $_POST['alias'] ?? '';
    $emailRecibido = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // 1. Comprobar que todo llega como texto ANTES de usar funciones de texto.
    if (!is_string($aliasRecibido) || !is_string($emailRecibido)
        || !is_string($password) || !is_string($passwordConfirm)) {
        $errors[] = 'Los datos enviados no tienen el formato esperado.';
    } else {
        // trim al alias y al correo, nunca a la contraseña.
        $alias = trim($aliasRecibido);
        $email = strtolower(trim($emailRecibido));

        if (mb_strlen($alias) < 2 || mb_strlen($alias) > 30) {
            $errors[] = 'El alias debe tener entre 2 y 30 caracteres.';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'El formato del correo electrónico no es válido.';
        }
        if (mb_strlen($password) < 12 || mb_strlen($password) > 64) {
            $errors[] = 'La contraseña debe tener entre 12 y 64 caracteres.';
        } elseif (strlen($password) > 72) {
            $errors[] = 'La contraseña ocupa más de 72 bytes (límite de bcrypt).';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'Las contraseñas no coinciden.';
        }
    }

    // 2. Duplicados y alta dentro de la MISMA operación bloqueada.
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            actualizarJson(RUTA_USUARIOS, function (array $users) use ($alias, $email, $hash): array {
                foreach ($users as $user) {
                    if (strtolower($user['email'] ?? '') === $email) {
                        throw new RegistroDuplicado('Correo ya registrado');
                    }
                }
                $users[] = [
                    'id' => siguienteId($users),
                    'alias' => $alias,
                    'email' => $email,
                    'password_hash' => $hash, // Nunca la contraseña original.
                ];
                return $users;
            });
            // 3. Éxito: 303 a login.php para que la siguiente petición sea GET.
            header('Location: login.php?registrado=1', true, 303);
            exit;
        } catch (RegistroDuplicado $error) {
            $errors[] = 'Este correo ya está registrado.';
        } catch (Throwable $error) {
            error_log('Registro: ' . $error->getMessage());
            http_response_code(500);
            $errors[] = 'No se ha podido guardar la cuenta. Inténtalo más tarde.';
        }
    }

    if (!empty($errors) && http_response_code() === 200) {
        http_response_code(400);
    }
}

cabecera('Registro');
?>
<h1>Crear cuenta de prueba</h1>
<p>Usa un correo ficticio y una contraseña que no utilices en ningún otro sitio.</p>
<?php if (!empty($errors)): ?>
    <ul class="errores"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
<?php endif; ?>
<form method="post" action="register.php" class="ficha-form">
    <?= campoCsrf() ?>
    <label>Alias <input type="text" name="alias" value="<?= e($alias) ?>" minlength="2" maxlength="30" required></label>
    <label>Correo <input type="email" name="email" value="<?= e($email) ?>" required></label>
    <label>Contraseña (12-64 caracteres) <input type="password" name="password" minlength="12" maxlength="64" required></label>
    <label>Repetir contraseña <input type="password" name="password_confirm" minlength="12" maxlength="64" required></label>
    <button type="submit">Registrarse</button>
</form>
<p><a href="login.php">¿Ya tienes cuenta? Inicia sesión</a></p>
<?php pie();
