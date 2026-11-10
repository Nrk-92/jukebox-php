# Jukebox de la asociación · PHP + JSON

Aplicación de servidor en PHP para la asociación: registro, login, home privada con el
jukebox de cliente y CRUD del catálogo musical guardado en JSON.

## Requisitos

- Docker Desktop con Docker Compose v2.
- Visual Studio Code (o cualquier editor).
- No hace falta instalar PHP en Windows.

## Carpetas

```
asociacion-jukebox-php/
  compose.yaml          Etapa inicial (puerto 8090), congelada.
  src/                  Pasos 01 a 10 y lib/.
  docs/                 Resúmenes por bloque.
  data-ejemplo/         Datos ficticios para preparar una copia nueva.
  cliente-original/     Copia del jukebox de cliente antes de adaptarlo.
  docker/
    compose.yaml        Etapa multiservicio (puerto 8091): web + PostgreSQL + MariaDB.
    Dockerfile          PHP 8.5 + Apache + pdo_pgsql + pdo_mysql.
    .env.example        Plantilla de claves (copiar como .env).
    apache/jukebox.conf Virtual host (DocumentRoot /var/www/html).
    src/                Código público: pantallas, api/, lib/, assets/, scripts/, audio/.
    data/               canciones.json y usuarios.json (privados, fuera del DocumentRoot).
```

## Arranque

Etapa inicial:

```powershell
cd C:\server\asociacion-jukebox-php
docker compose up -d
curl.exe -i http://localhost:8090/01-bienvenida.php
```

Etapa final:

```powershell
cd C:\server\asociacion-jukebox-php\docker
copy .env.example .env          # y cambia las claves ficticias
docker compose config --quiet
docker compose up -d --build
docker compose ps               # postgres y mariadb deben aparecer healthy
```

**Solo en una copia nueva** (nunca sobre datos que quieras conservar), prepara los datos:

```powershell
mkdir data
copy ..\data-ejemplo\canciones.json data\
copy ..\data-ejemplo\usuarios.json data\
```

## URL

| URL | Uso |
|---|---|
| http://localhost:8091/register.php | Crear la primera cuenta ficticia |
| http://localhost:8091/login.php | Iniciar sesión |
| http://localhost:8091/home.php | Home privada con el jukebox |
| http://localhost:8091/canciones.php | Catálogo (CRUD) |
| http://localhost:8091/api/canciones.php | Lista JSON para el reproductor (requiere sesión) |
| http://localhost:8091/11-conexiones.php | Diagnóstico PDO de PostgreSQL y MariaDB |

## Primera cuenta

1. Abre `register.php`.
2. Alias de 2 a 30 caracteres, un correo ficticio (por ejemplo `prueba@example.test`)
   y una contraseña de laboratorio de 12 a 64 caracteres que no uses en otro sitio.
3. Tras registrarte, entra desde `login.php`.

## Notas

- El CRUD usa JSON; las bases de datos solo se comprueban con `11-conexiones.php`.
- Eliminar una ficha no borra su MP3.
- No se entrega `docker/.env` ni `docker/data/`.
- No ejecutes `docker compose down -v`: borraría los volúmenes de los motores.
