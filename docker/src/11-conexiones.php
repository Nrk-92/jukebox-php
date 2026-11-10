<?php
// Bloque 7 · Diagnóstico de los dos motores mediante PDO.
// Devuelve JSON con el estado y el nombre de cada base, nunca las claves.
header('Content-Type: application/json; charset=utf-8');

$resultado = [];
$codigo = 200;

// PostgreSQL
try {
    $dsn = 'pgsql:host=postgres;port=5432;dbname=jukebox_pg';
    $db = new PDO($dsn, getenv('PGUSER'), getenv('PGPASSWORD'));
    $name = $db->query('SELECT current_database()')->fetchColumn();
    $resultado['postgres'] = ['estado' => 'ok', 'base' => $name];
} catch (PDOException $error) {
    $codigo = 503;
    $resultado['postgres'] = ['estado' => 'error'];
    error_log('PDO PostgreSQL: ' . $error->getCode()); // Diagnóstico sin secretos.
}

// MariaDB
try {
    $dsn = 'mysql:host=mariadb;port=3306;dbname=jukebox_maria;charset=utf8mb4';
    $db = new PDO($dsn, getenv('MARIADB_USER'), getenv('MARIADB_PASSWORD'));
    $name = $db->query('SELECT DATABASE()')->fetchColumn();
    $resultado['mariadb'] = ['estado' => 'ok', 'base' => $name];
} catch (PDOException $error) {
    $codigo = 503;
    $resultado['mariadb'] = ['estado' => 'error'];
    error_log('PDO MariaDB: ' . $error->getCode());
}

http_response_code($codigo);
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
