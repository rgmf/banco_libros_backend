# Partimos de la imagen php en su versión 8.2
FROM php:8.1-fpm

# Copiamos los archivos package.json composer.json y composer-lock.json a /var/www/
COPY composer*.json /var/www/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Nos movemos a /var/www/
WORKDIR /var/www/

# Instalamos las dependencias necesarias
RUN apt-get update && apt-get install -y \
    build-essential \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    git \
    curl

# Instalamos extensiones de PHP
RUN docker-php-ext-install pdo_mysql zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Copiamos todos los archivos de la carpeta actual de nuestra
# computadora (los archivos de laravel) a /var/www/
COPY . /var/www/

# Instalamos dependendencias de composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-ansi --no-dev --no-interaction --no-progress --optimize-autoloader --no-scripts

# Exponemos el puerto 9000 a la network
EXPOSE 9000

# Para evitar problemas de permisos: creamos un nuevo grupo usando el GID 1000
# y creamos un usuario llamado "laravel". Asociamos ese usuario con el grupo
# recién creado. Finalmente, configuramos el usuario "laravel" como usuario por
# defecto para ejecutar el contenedor.
RUN addgroup --gid 1000 laravel
RUN adduser --ingroup laravel --shell /bin/sh laravel
USER laravel
