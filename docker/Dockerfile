FROM php:8.5-apache-bookworm

# Controladores PDO de PHP (no son los motores de base de datos).
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Sitio de Apache: la ruta es relativa al contexto (la raíz del proyecto).
COPY docker/apache/jukebox.conf /etc/apache2/sites-available/jukebox.conf
RUN a2ensite jukebox && a2dissite 000-default
